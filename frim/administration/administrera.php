<?php
	session_start();
    
    // Starta dokumentet.
    require("../subrutiner/head.php");

	// Starta <body> och <div>.
	echo "<body><div id='ALLT'><br />";

    // Kolla så att det inte är administratören som loggat in.
    if ($_SERVER["PHP_AUTH_USER"] <> "9999") {		
		
        // Kolla om det finns något Exlibris till det aktuella biblioteket.
    	#require ("../subrutiner/top_exlibris.php");

   		// Kolla om administratörsläget är på eller inte.
        if (isset($_GET['Admin']) && $_GET['Admin'] == 1) $_SESSION['Admin'] = 1;
   		if (isset($_GET['Admin']) && $_GET['Admin'] == 0) $_SESSION['Admin'] = 0;
	}
        
    // Bestäm vad som ska visas om administratören loggat in.
    if ($_SERVER["PHP_AUTH_USER"] == "9999") {
        $_SESSION['Admin'] = 1;
        $_SESSION['Bibliotek'] = 9999;
        $Exlibrislink = "";
    }
    
    // Kontrollera om årsavgiften är betald.
    $Betald         = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR = ".$_SESSION['Bibliotek']." and BETALT = 1";
    $Betaltresult   = mysqli_query($db,$Betald);
    $Betaltkoll     = mysqli_num_rows($Betaltresult);
    if ($Betaltkoll == "1") $Arsavgift = "";
    else $Arsavgift = "<br />*** &Aring;rsavgiften f&ouml;r $Serverar &auml;r inte registrerad som betald. ***";
    
    // Om administratören är inloggad så kontrollera hur många som betalt årsavgiften.
    if ($_SERVER["PHP_AUTH_USER"] == "9999"){
        $Betald         = "SELECT * FROM bibliotek WHERE BETALT = 1";
        $Betaltresult   = mysqli_query($db,$Betald);
        $Betalat        = mysqli_num_rows($Betaltresult);

        $Betald         = "SELECT * FROM bibliotek WHERE BETALT = 0";
        $Betaltresult   = mysqli_query($db,$Betald);
        $Intebetalat    = mysqli_num_rows($Betaltresult);
        
        $Arsavgift = "<br />*** $Betalat bibliotek har betalat &aring;rsavgiften - $Intebetalat har inte gjort det. ***";
    }
    
	// Räkna hur många titlar som finns registrerade.
	$Raknatitlar	= "SELECT * FROM litteratur";
	$Titlarresult	= mysqli_query($db,$Raknatitlar);
	$Antaltitlar	= mysqli_num_rows($Titlarresult);
	
    // Räkna hur många böcker som finns registrerade.
	$Raknabocker	= "SELECT * FROM bocker WHERE EAN LIKE '".$_SESSION['Bibliotek']."%'";
	if ($_SERVER["PHP_AUTH_USER"] == "9999") $Raknabocker	= "SELECT * FROM bocker";
	$Bockerresult	= mysqli_query($db,$Raknabocker);
	$Antalbocker	= mysqli_num_rows($Bockerresult);
	
    // Räkna hur många bröder som finns registrerade.
	$Raknabroder	= "SELECT * FROM medlem";
	$Broderresult	= mysqli_query($db,$Raknabroder);
	$Antalbroder	= mysqli_num_rows($Broderresult);
    
    // Kolla upp när medlemslistan senast uppdaterades.
    $Medlemsfil     = "../konfiguration/medlemmar.xml";
    if (file_exists($Medlemsfil)) $Medlemsuppdatering = date("Y-m-d",filemtime($Medlemsfil));
	
    // Räkna hur många besök som registrerats idag.
	$Raknabesok		= "SELECT * FROM besokslistan WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." AND DATUM = $Serveridag";
    if ($_SERVER["PHP_AUTH_USER"] == "9999") $Raknabesok = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag";
	$Besokresult	= mysqli_query($db,$Raknabesok);
	$Antalbesok		= mysqli_num_rows($Besokresult);

	// Räkna efter hur många lån som gjorts idag.
	$Antaltotallokal = 0;
	$Raknalanidag	= "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." AND DATUM = $Serveridag";
    if ($_SERVER["PHP_AUTH_USER"] == "9999") $Raknalanidag = "SELECT * FROM aktiva WHERE DATUM = $Serveridag";
	$Lanidagresultat= mysqli_query($db,$Raknalanidag);
	$Antaltotallokal= mysqli_num_rows($Lanidagresultat);

	// Räkna efter hur många böcker som lämnats tillbaka i biblioteket idag.
	$Raknareturer	= "SELECT * FROM returer WHERE RETUREAN LIKE '".$_SESSION['Bibliotek']."%' AND RETURDATUM = '$Serveridag'";
    if ($_SERVER['PHP_AUTH_USER'] == "9999") $Raknareturer = "SELECT * FROM returer WHERE RETURDATUM = '$Serveridag'";
	$Returerresult	= mysqli_query($db,$Raknareturer);
	$Antalreturer	= mysqli_num_rows($Returerresult);
				
    // Räkna efter hur många lån som är aktiva just nu.
	$Raknaaktiva	= "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']."";
    if ($_SERVER["PHP_AUTH_USER"] == "9999") $Raknaaktiva	= "SELECT * FROM aktiva";
	$Aktivaresult	= mysqli_query($db,$Raknaaktiva);
	$Antalaktiva	= mysqli_num_rows($Aktivaresult);
    $Antalaktivatotalt = $Antalaktiva;

    // Kolla efter om det finns några sena lån och räkna dem i så fall.
	$Antalsena = 0;
	
	while ($row = mysqli_fetch_array($Aktivaresult)) {
        $DATUM			= $row["DATUM"];
		$Tillbakadatum = $DATUM+2592000;
		if ($Tillbakadatum <= $Serveridag) $Antalsena = $Antalsena+1;
	}	

    // Definiera antalet utlånade böcker respektive försenade returer.
    if ($Antalaktiva >= 1) {
        $Antalaktiva = $Antalaktiva-$Antalsena;
        if ($Antalaktiva == 0) $VisaAntalaktiva = "$Antalaktiva";
        else $VisaAntalaktiva = "<a href='lan_hantering.php?Steg=Utlanade'>$Antalaktiva</a>";
    }
    else $VisaAntalaktiva = $Antalaktiva;
	
	if ($Antalsena >= 1) $Visaantalsena = "<br />Sena returer: <a href='lan_hantering.php?Steg=Senstatistik'>$Antalsena</a>";
#	if ($Antalsena >= 1) $Visaantalsena = "<br />Sena returer: <a href='lan_hantering.php?Steg=Sena'>$Antalsena</a>";
	elseif ($Antalsena == 0) $Visaantalsena = "";
    
    // Räkna efter hur många speciallån som är aktiva just nu.
	$Raknaspecial	= "SELECT * FROM special WHERE SBIBLIOTEK = ".$_SESSION['Bibliotek']."";
    if ($_SERVER["PHP_AUTH_USER"] == "9999") $Raknaspecial	= "SELECT * FROM special";
	$Specialresult	= mysqli_query($db,$Raknaspecial);
	$Antalspecial	= mysqli_num_rows($Specialresult);
    if ($Antalspecial == 0) $Antalspecial = "";
    else $Antalspecial = "<br />Speciall&aring;n: <a href='lan_hantering.php?Steg=Utlanadespecial'>$Antalspecial</a>";
    
	// Hämta information om vilket bibliotek som är igång om det inte är administratorn som kikar.
	if ($_SERVER["PHP_AUTH_USER"] <> "9999") {
        $BIBLIOTEKNR = $_SESSION['Bibliotek'];
			
    	// Läs data om det valda biblioteket.
    	$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEKNR";
    			
    	// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
    	if (!$result = mysqli_query($db,$Visabibliotek)) {
    		die('Invalid query: ' . mysqli_error($db));
    	}
    	while ($row = mysqli_fetch_array($result)) {
    		$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];
    	}	
    	
    	// Hämta information om kvällens tjänstgörande bibliotekarie.
    	$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID LIMIT 1";
    
    	// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    	if (!$resultbesok = mysqli_query($db,$Hamtabibliotekarie)) {
    		die('Invalid query: ' . mysqli_error($db));
    	}
    
        // Kontrollera att det faktiskt finns en bibliotekarie registrerad idag och om inte hoppa så till inloggningssidan.
    	$Kollatjanstgorande = mysqli_num_rows($resultbesok);
        if ($Kollatjanstgorande == "0") echo "<script language='javascript' type='text/javascript'>window.location.href = \"../fm_inlogg.php\"</script>";			

        
    	// Läs medlemsnummer från databasen medlem.
    	while ($row = mysqli_fetch_array($resultbesok)) {
    		$Besoksmedlem	= $row["MEDLEM"];
            $Besoksmedlem   = substr($Besoksmedlem,2);
    					
    		// Hämta information från databasen medlem.
    		$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";
    
    		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    		if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    			die('Invalid query: ' . mysqli_error($db));
    		}
    
    		// Läs data om brodern i databasen medlem.
    		while ($row = mysqli_fetch_array($resultmedlem)) {
    			$MEDLEM			= $row["MEDLEM"];
    			$MEDLEMNAMN		= $row["MEDLEMNAMN"];
    			$MEDLEMFNAMN	= $row["MEDLEMFNAMN"];
    			$MEDLEMGRAD		= $row["MEDLEMGRAD"];
    			$MEDLEMLOGE		= $row["MEDLEMLOGE"];
    
    			// Skriv ut medlemsgraden i klartext.
    			if ($MEDLEMGRAD == 1) $MEDLEMGRAD = "I";
    			if ($MEDLEMGRAD == 2) $MEDLEMGRAD = "II";
    			if ($MEDLEMGRAD == 3) $MEDLEMGRAD = "III";
    			if ($MEDLEMGRAD == 4) $MEDLEMGRAD = "IV-V";
    			if ($MEDLEMGRAD == 6) $MEDLEMGRAD = "VI";
    			if ($MEDLEMGRAD == 7) $MEDLEMGRAD = "VII";
    			if ($MEDLEMGRAD == 8) $MEDLEMGRAD = "VIII";
    			if ($MEDLEMGRAD == 9) $MEDLEMGRAD = "IX";
    			if ($MEDLEMGRAD == 10) $MEDLEMGRAD = "X";
    			if ($MEDLEMGRAD == 11) $MEDLEMGRAD = "XI";
    
    			// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
    			$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";
    
    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
    
    			// Läs in logens namn från databasen arbetsenheter.
    			while ($row = mysqli_fetch_array($Logeresult)) {
    				$MEDLEMLOGE = $row["ENHETNAMN"];
    			}
    		}
        	$BIBLIOTEKARIE = "$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE";
           	echo "<div class='Rubrik'><b>$BIBLIOTEKNAMN</b></div><div class='Text'>Tj&auml;nstg&ouml;rande: $BIBLIOTEKARIE</div>";
    	}
    }
    else echo "<br /><div class='Rubrik'><b>Administration</b></div>";
    
    // Visa administrationsmenyn.
   	echo "<table width='600 px' align='center' cellpadding='5' border='1' class='Text'><tr>";
   	echo "<td width='33%'>Titlar: $Antaltitlar<br />B&ouml;cker: $Antalbocker<br />Saldo: ".($Antalbocker-$Antalaktivatotalt)."</td>";
    echo "<td width='33%'>Bes&ouml;k idag: ".($Antalbesok-1)."<br />Utl&aring;n idag: $Antaltotallokal<br />Returer idag: $Antalreturer</td><td width='34%'>P&aring;g&aring;ende l&aring;n: $VisaAntalaktiva$Visaantalsena$Antalspecial</td>";
   	echo "</tr></table>";
	echo "<div class='Text'>$Medlemsuppdatering uppdaterades medlemsregistrets $Antalbroder br&ouml;der.";
    echo "$Arsavgift</div><br />";
    
   	echo "<table width='600 px' align='center' cellpadding='5' border='1' class='Text'>";
    $pdficon = "<img src='../design/pdf_icon.png' align='bottom' height='18' />";
    
    // Visa inte de vanliga knapparna om det är administratören som är inloggad.
    if ($_SERVER["PHP_AUTH_USER"] <> "9999") {
        
        // Sektion ett med knappar.
        echo "<tr align='left'><td valign='top'>";
        echo "<button type='button' onClick='parent.location=\"../fm.php\"' class='Text' style='width:145px; height:40px'>Huvudsidan</button></td></tr>";
        
        // Sektion två med knappar.
       	echo "<tr align='left'><td valign='top'>";
       	echo "<button type='button' onClick='parent.location=\"../medlem.php?Steg=RFID&Typ=Special\"' class='Text' style='width:145px; height:40px'>Speciall&aring;n</button>";
       	echo "<button type='button' onClick='parent.location=\"../medlem.php?Steg=RFID\"' class='Text' style='width:145px; height:40px'>Receptionsl&aring;n</button>";
        echo "</td></tr>";
        
        // Sektion tre med knappar.
        echo "<tr align='left'><td valign='top'>";
       	echo "<button type='button' onClick='parent.location=\"bok_hantering.php?Steg=Kontroll\"' class='Text' style='width:145px; height:40px'>Kontrollera bok</button>";
       	echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Sok&Sok=Titel\"' class='Text' style='width:145px; height:40px'>S&ouml;k titel</button>";
		echo "<button type='button' onClick='parent.location=\"kategori_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Visa kategorier</button>";
		echo "<button type='button' onClick='parent.location=\"sprak_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Visa spr&aring;k</button>";
       	echo "</td></tr>";

        // Sektion fyra med knappar.
        echo "<tr align='left'><td valign='top'>";
       	echo "<button type='button' onClick='parent.location=\"rapport_hantering.php?Steg=Dagslista\"' class='Text' style='width:145px; height:40px'>Rapport $pdficon</button>";
    	echo "<button type='button' onClick='window.close();' class='Text' class='Text' style='width:145px; height:40px'>Avsluta</button>";
       	echo "</td></tr>";
                    
        // Tom rad.
    	echo "<tr height='20px'></tr>";

    	// Kolla om det är läge att skriva ut en Administrationsknapp eller inte.
    	if ($_SESSION['Admin'] <> 1) {
    		echo "<tr align='left' bgcolor='#F1F1F1'><td valign='top'>";
    		echo "<button type='button' onClick='parent.location=\"administrera.php?Admin=1\"' class='Text' style='width:145px; height:40px'>Fler funktioner</button>";
    		echo "</td></tr>";
    	}
    }
            		
	// Kolla om det är läge att skriva ut de andra knapparna eller inte.
	if ($_SESSION['Admin'] == 1) {
		if ($_SERVER["PHP_AUTH_USER"] <> "9999") {
            echo "<tr align='left' bgcolor='#F1F1F1'><td valign='top'>";
    		echo "<button type='button' onClick='parent.location=\"administrera.php?Admin=0\"' class='Text' style='width:145px; height:40px'>F&auml;rre funktioner</button>";
    		echo "</td></tr>";

            // Sektionen med bokhanteringsknappar.
    		echo "<tr align='left'><td valign='top'>";
    		echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Sok\"' class='Text' style='width:145px; height:40px'>L&auml;gg till ny bok</button>";
    		echo "<button type='button' onClick='parent.location=\"bok_hantering.php?Steg=RedigeraBok\"' class='Text' style='width:145px; height:40px'>Redigera bok</button>";
    		echo "<button type='button' onClick='parent.location=\"bok_hantering.php?Steg=Listabocker\"' class='Text' style='width:145px; height:40px'>Lista b&ouml;cker</button>";
    		echo "<button type='button' onClick='parent.location=\"bok_hantering.php?Steg=Radera\"' class='Text' style='width:145px; height:40px'>Makulera bok</button>";
    		echo "</td></tr>";

        }
        
        // Sektion med titelhanteringsknappar.
   		echo "<tr align='left'><td valign='top'>";
		echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel2&amp;Sparasom=Ny\"' class='Text' style='width:145px; height:40px'>L&auml;gg till ny titel</button>";
#		echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel\"' class='Text' style='width:145px; height:40px'>Redigera titel</button>";
		echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=ListaTitlar\"' class='Text' style='width:145px; height:40px'>Lista titlar</button>";
		if ($_SERVER['PHP_AUTH_USER'] <> "9999") echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Radera\"' class='Text' style='width:145px; height:40px'>Radera titel</button>";
       	if ($_SERVER['PHP_AUTH_USER'] == "9999") echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Sok&Sok=Titel\"' class='Text' style='width:145px; height:40px'>S&ouml;k titel</button>";
   		if ($_SERVER['PHP_AUTH_USER'] == "9999") echo "<button type='button' onClick='parent.location=\"kategori_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Visa kategorier</button>";
       	if ($_SERVER['PHP_AUTH_USER'] == "9999") echo "<button type='button' onClick='parent.location=\"sprak_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Visa spr&aring;k</button>";
       	if ($_SERVER['PHP_AUTH_USER'] == "9999") echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Nollor\"' class='Text' style='width:145px; height:40px'>!! Ta bort nollor</button>";
       	if ($_SERVER['PHP_AUTH_USER'] <> "9999") echo "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Topplistan\"' class='Text' style='width:145px; height:40px'>Topplistan</button>";
		echo "</td></tr>";

        // Tom rad.
		echo "<tr height='20px'></tr>";

        // Sektion med lånehanteringsknappar.
		echo "<tr align='left'><td valign='top'>";
		if ($_SERVER['PHP_AUTH_USER'] <> "9999") echo "<button type='button' onClick='parent.location=\"inventering_hantering.php\"' class='Text' style='width:145px; height:40px'>Inventera $pdficon</button>";
		echo "<button type='button' onClick='parent.location=\"rapport_hantering.php?Steg=Special\"' class='Text' style='width:145px; height:40px'>Speciall&aring;n $pdficon</button>";
		if ($_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"rapport_medlem.php?Steg=Arsdatum\"' class='Text' style='width:145px; height:40px'>&Aring;rsrapport $pdficon</button>";
        if ($_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"bibliotek_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Lista bibliotek</button>";
#		if ($_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"rapport_hantering.php?Steg=Lokal\"' class='Text' style='width:145px; height:40px'>Utl&aring;n $pdficon</button>";
#		echo "<button type='button' onClick='parent.location=\"rapport_hantering.php?Steg=Lan\"' class='Text' style='width:145px; height:40px'>Utl&aring;n SFMO $pdficon</button>";
#      	if ($Antalsena >= 1 && $_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Senstatistik\"' class='Text' style='width:145px; height:40px'>Sena returer</button>";
#		if ($Antalaktiva >=1 && $_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Utlanade\"' class='Text' style='width:145px; height:40px'>Utl&aring;nade b&ouml;cker</button>";
#		if ($Antalsena >=1 && $_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Sena\"' class='Text' style='width:145px; height:40px'>Sena returer</button>";

		if ($_SERVER["PHP_AUTH_USER"] == "9999") {
    		echo "<button type='button' onClick='parent.location=\"bibliotek_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Lista bibliotek</button>";
            echo "<button type='button' onClick='parent.location=\"bibliotek_hantering.php?Steg=Laggtill\"' class='Text' style='width:145px; height:40px'>L&auml;gg till enhet</button>";
            echo "<button type='button' onClick='parent.location=\"bibliotek_hantering.php?Steg=Redigera\"' class='Text' style='width:145px; height:40px'>Redigera enhet</button>";
        }
		echo "</td></tr>";

		echo "<tr align='left'><td valign='top'>";
			if ($Antalaktiva >=1 && $Antalsena >=1) echo "";
#			if ($Antalsena >=1 && $_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Senaloge\"' class='Text' style='width:145px; height:40px'>Sena returer per loge</button>";
			if ($_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"rapport_hantering.php?Steg=Arkiv\"' class='Text' style='width:145px; height:40px'>Bes&ouml;kslista $pdficon</button>";
            if ($_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"rapport_medlem.php?Steg=Datum\"' class='Text' style='width:145px; height:40px'>Bes&ouml;kslista 2</button>";
#			echo "<button type='button' onClick='parent.location=\"svartalistan_hantering.php?Steg=Visa\"' class='Text' style='width:145px; height:40px'>\"Svarta listan\"</button>";
			if ($_SERVER["PHP_AUTH_USER"] <> "9999") echo "<button type='button' onClick='parent.location=\"streckkod_hantering.php?Steg=Generera\"' class='Text' style='width:145px; height:40px'>Streckkoder $pdficon</button>";
			echo "<button type='button' onClick='parent.location=\"rapport_hantering.php?Steg=Makulerade\"' class='Text' style='width:145px; height:40px'>Makulerade $pdficon</button>";
#			echo "<button type='button' onClick='parent.location=\"rapport_medlem.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Besökslista 2</button>";
			echo "</td></tr>";
#            }

            
		if ($_SERVER["PHP_AUTH_USER"] == "9999") {
    		echo "<tr height='20px'></tr>";

    		echo "<tr align='left'><td valign='top'>";
			if ($Antalsena >=1 ) echo "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Maila\"' class='Text' style='width:145px; height:40px'>Skicka p&aring;minnelser</button>";
			echo "<button type='button' onClick='parent.location=\"../konfiguration/medlem.php?Steg=Start\"' class='Text' style='width:145px; height:40px'>Uppdatera medlemslistan</button>";
    		echo "<button type='button' onClick='parent.location=\"../konfiguration/litteratur_export.php\"' class='Text' style='width:145px; height:40px'>Exportera litteraturlista</button>";
    		echo "<button type='button' onClick='parent.location=\"../konfiguration/litteratur_import.php\"' class='Text' style='width:145px; height:40px'>Uppdatera litteraturlista</button>";
    		echo "<button type='button' onClick='parent.location=\"help_hantering.php?Steg=Lista\"' class='Text' style='width:145px; height:40px'>Hj&auml;lpavsnitt</button>";
    		echo "<button type='button' onClick='parent.location=\"dubletter.php?Steg=Sok\"' class='Text' style='width:145px; height:40px'>Dubletter</button>";
    		echo "</td></tr>";
        }
        
        // Sektion med informationsknappar.
        echo "<tr align='left'><td valign='top'>";
        echo "<button type='button' onClick='parent.location=\"information.php\"' class='Text' style='width:145px; height:40px'>Information</button>";
        echo "</td></tr>";

		}
		echo "</table><br />";
		
		// Gömda knappar.
//		echo "<button type='button' onClick='parent.location=\"medlem_hantering.php?Steg=Sok\"' class='Text'>S&ouml;k broder</button>";
//		echo "<button type='button' onClick='parent.location=\"svartalistan_hantering.php?Steg=Laggtill\"' class='Text'>Svartlista</button>";
//    	echo "<button type='button' onClick='parent.location=\"../index.php?Steg=Kopiera\"' class='Text'>S&auml;kerhetskopiera</button>";
?>
</div>
</body>
</html>
