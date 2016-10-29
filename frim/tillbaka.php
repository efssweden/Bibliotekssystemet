<?php
    session_start();

    // Starta dokumentet.
    require("subrutiner/head.php");
    echo "<body><div id='ALLT'>";
    
    // Skriv ut bibliotekets exlibris p� sidan.
    require("subrutiner/top_exlibris.php");
    
    // Skriv ut en lista �ver dagens tillbakal�mnade b�cker.
    $Listadagens = "SELECT * FROM returer WHERE RETUREAN LIKE '".$_SESSION['Bibliotek']."%' AND RETURDATUM = $Serveridag ORDER BY RETURID DESC LIMIT 5";

   	// Skicka beg�ran till servern och stoppa om det blir n�got fel.
   	if (!$resultidag = mysqli_query($db,$Listadagens)) {
   		die('Invalid query: ' . mysqli_error($db));
   	}

	// Spara uppgifter om eventuella tillbakal�mningar och spara uppgifterna i en str�ng.
//	if (!mysqli_num_rows($resultidag)) $Tillbakaidag = "$Tillbakaidag<br /><div class='Rubrik'>Inga registrerade returer idag.</div><br />";
//	else {

        // R�kna antalet returer idag.
        $Antalreturer = (mysqli_num_rows($resultidag));
        
        // B�rja r�kna rader.
        $Rad = 1;
        
   		// L�s data om utl�nade b�cker i databasen aktiva.
		while ($row = mysqli_fetch_array($resultidag)) {
            $RETURTITELID		= $row["RETURTITELID"];
            $RETUREAN			= $row["RETUREAN"];
            
            // L�s titeln som �r kopplad till boken.
			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$RETURTITELID'";

			// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
			if (!$titelresult = mysqli_query($db,$Lastitel)) {
				die('Invalid query: ' . mysqli_error($db));
			} 

			// L�s information fr�n databasen litteratur om aktuell titel.
			while ($row = mysqli_fetch_array($titelresult)) {
				$TITELID		= $row["TITELID"];
				$KOD			= $row["KOD"];
				$TITEL			= $row["TITEL"];
				$FORFATTARE		= $row["FORFATTARE"];
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];

				// Kontrollera om data saknas och hantera det i s� fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
				if (empty($UTGIVNINGSAR)) $UTGIVNINGSAR = "";
				else $UTGIVNINGSAR = " ($UTGIVNINGSAR)";
				if (empty($REVIDERAD)) $REVIDERAD = "";
				else $UTGIVNINGSAR = "$UTGIVNINGSAR Reviderad $REVIDERAD";
            
                // Kolla om KOD inneh�ller n�gra inledande nollor och plocka bort dem i s� fall.
                if (!empty($KOD)) {
                    
                    $KOD = str_replace(":00",":",$KOD);
                    $KOD = str_replace(":0",":",$KOD);
                    $KOD = str_replace(" 00"," ",$KOD);
                    $KOD = str_replace(" 0"," ",$KOD);
                }
                if (!empty($KOD)) $KOD = "$KOD ";

            }
            // Spara information om returen i en str�ng.
            $Tillbakarad[$Rad] = "<tr><td align='left' class='Text'>$RETUREAN</td><td align='left' class='Text'><b>$KOD&ldquo;$TITEL&rdquo;</b></td></tr>";
            $Rad = $Rad + 1;
        }
        $Rad = $Rad -1;
        
        if ($Rad >= 6) {
            $Maxantal = 5;
            $Titelrubrik = "Titel: (Observera att endast de 5 senaste returerna visas)";
        }
        else {
            $Maxantal = $Rad;
            $Titelrubrik = "Titel:";
        }
        
        for ($i=1;$i<=$Maxantal;$i++) {
            $Tillbakarader = $Tillbakarader.$Tillbakarad[$i];
        }        
        
        // Skriv ut en rubrik.
//        $Tillbakaidag = "$Tillbakaidag<br /><div class='Rubrik'>Returer idag: $Antalreturer</div><table cellpadding='5' align='center' border='1'>";
        $Tillbakaidag = "$Tillbakaidag<br /><div class='Rubrik'>Returer idag: </div><table cellpadding='5' align='center' border='1'>";
        $Tillbakaidag = "$Tillbakaidag<tr bgcolor='#f1f1f1'><td valign='top' align='left' class='Text'>Streckkod:</td><td valign='top' align='left' class='Text'>$Titelrubrik</td></tr>";

        // Avsluta tabellen.
        $Tillbakaidag = "$Tillbakaidag$Tillbakarader</table>"; 
      
//    }

    // Skriv ut information om dagens returer.
    echo "$Tillbakaidag<br />";
    
	// Skriv ut instruktioner.
	echo "<div class='Text'>Scanna av eller skriv in streckkoden p&aring; boken som ska &aring;terl&auml;mnas eller tryck Avsluta.<br /><br /></div>";
	
    // Starta ett formul�r.
    echo "<form action='tillbaka.php' name='lana' method='post' enctype='application/form-data'>";
	echo "<input name='EAN' type='text' class='Text' size='50'><br />";
	
    // Skriv ut knappar och avsluta formul�ret.
    echo "<input type='submit' value='L&auml;mna tillbaka' class='Text' ><input type='button' name='Cancel' value='Avsluta' onclick='window.location.href = \"fm.php\" ' class='Text' /></form><br />";

    // Skriv ut en hj�lpknapp.
    $HELPID = 5;
    require ("subrutiner/help.php");
	
    // S�tt fokus p� inmatningsrutan s� att mark�ren alltid �r d�r.
    echo "<script type='text/javascript'>document.lana.EAN.focus()</script>";		

	// Hantera den tillbakal�mnade boken om den �r inscannad.
	if (isset($_POST['EAN'])) {
	
		// H�mta streckkoden fr�n f�reg�ende steg.
		$EAN		= $_POST["EAN"];
		
		// Kontrollera om streckkoden �r numerisk och larma om s� inte �r fallet.
		if (!is_numeric($EAN)) {
			header("location:varning.php?Varning=830");
		}
			
		// H�mta information om boken i databasen bocker.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = $EAN";

		// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
		// Varna om boken inte tillh�r systemet.
		if (!mysqli_num_rows($result)) header("location:varning.php?Varning=190");	
		else {
	
			// Kolla om boken tillh�r det aktuella biblioteket.
			if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) header("location:varning.php?Varning=180");
			
			// L�s information fr�n databasen bocker om aktuell titel.
			while ($row = mysqli_fetch_array($result)) {
				$BOKID			= $row["BOKID"];
			}
			
			// H�mta upp aktuell utl�ning fr�n databasen aktiva.
			$Hamtalan = "SELECT * FROM aktiva WHERE BOKID = $BOKID";

			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
			if (!$resultaktiva = mysqli_query($db,$Hamtalan)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// H�mta upp aktuell utl�ning fr�n databasen special.
			$Hamtaspecial = "SELECT * FROM special WHERE SBOKID = $BOKID";
			
			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
			if (!$resultspecial = mysqli_query($db,$Hamtaspecial)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
            // Kontrollera i vilken kategori boken �r registrerad som utl�nad.
            if (mysqli_num_rows($resultaktiva) == 1) $Kategori = "aktiva";
            if (mysqli_num_rows($resultspecial) == 1) $Kategori = "special";
            
            // Varna om boken inte �r registrerad som utl�nad.
            if ($Kategori == "") {
                header("location:varning.php?Varning=190");						
			}

			if ($Kategori == "aktiva") {
                
                // L�s information fr�n databasen aktiva om aktuell utl�ning.
                while ($row = mysqli_fetch_array($resultaktiva)) {
                    $LANID			= $row["LANID"];
                    $TITELID        = $row["TITELID"];
                    $MEDLEM         = $row["MEDLEM"];
                    $DATUM          = $row["DATUM"];
                    $BIBLIOTEK      = $row["BIBLIOTEK"];
                }

    			// Radera posten med utl�ningen i databasen aktiva.
    			$Raderalan = "DELETE FROM aktiva WHERE LANID = $LANID";
    			if (!$resultaktiva = mysqli_query($db,$Raderalan)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
                
                // L�gg till den �terl�mnade boken i databasen returer.
                $Spararetur = "INSERT INTO returer (RETURTITELID,RETURDATUM,RETURBIBLIOTEK,RETUREAN) VALUES ('$TITELID','$Serveridag','".$_SESSION['Bibliotek']."','$EAN')";

                // Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
                if (!$resultretur = mysqli_query($db,$Spararetur)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
                // L�gg till det �terl�mnade boken i databasen gamlalan.
                $Sparagammal = "INSERT INTO gamlalan (TITELID,MEDLEM,BIBLIOTEK,DATUM) VALUES ('$TITELID','$MEDLEM','$DATUM','$BIBLIOTEK')";
                
                // Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
                if (!$resultgammal = mysqli_query($db,$Sparagammal)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
                // Hoppa vidare.
                header("location:tillbaka.php");
            }
            
			if ($Kategori == "special") {
                
                // L�s information fr�n databasen special om aktuell utl�ning.
                while ($row = mysqli_fetch_array($resultspecial)) {
                    $LANID			= $row["SLANID"];
                    $TITELID        = $row["STITELID"];
                    $MEDLEM         = $row["SMEDLEM"];
                    $DATUM          = $row["SDATUM"];
                    $BIBLIOTEK      = $row["SBIBLIOTEK"];
                }

    			// Radera posten med utl�ningen i databasen aktiva.
    			$Raderalan = "DELETE FROM special WHERE SLANID = $LANID";
    			if (!$resultaktiva = mysqli_query($db,$Raderalan)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
                
                // L�gg till den �terl�mnade boken i databasen returer.
                $Spararetur = "INSERT INTO returer (RETURTITELID,RETURDATUM,RETURBIBLIOTEK,RETUREAN) VALUES ('$TITELID','$Serveridag','".$_SESSION['Bibliotek']."','$EAN')";

                // Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
                if (!$resultretur = mysqli_query($db,$Spararetur)) {
                    die('Invalid query: ' . mysqli_error($db));
                }

                // L�gg till det �terl�mnade boken i databasen gamlalan.
                $Sparagammal = "INSERT INTO gamlalan (TITELID,MEDLEM,BIBLIOTEK,DATUM) VALUES ('$TITELID','$MEDLEM','$DATUM','$BIBLIOTEK')";
                
                // Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
                if (!$resultgammal = mysqli_query($db,$Sparagammal)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                			
                // Hoppa vidare.
                header("location:tillbaka.php");
            }
        }
	}
    echo "</div></body></html>";
?>