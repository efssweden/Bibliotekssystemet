<?php
    session_start();

    // Starta dokumentet.
    require("subrutiner/head.php");
    echo "<body><div id='ALLT'>";
    
    // Skriv ut bibliotekets exlibris på sidan.
    require("subrutiner/top_exlibris.php");
    
    // Skriv ut en lista över dagens tillbakalämnade böcker.
    $Listadagens = "SELECT * FROM returer WHERE RETUREAN LIKE '".$_SESSION['Bibliotek']."%' AND RETURDATUM = $Serveridag ORDER BY RETURID DESC LIMIT 5";

   	// Skicka begäran till servern och stoppa om det blir något fel.
   	if (!$resultidag = mysqli_query($db,$Listadagens)) {
   		die('Invalid query: ' . mysqli_error($db));
   	}

	// Spara uppgifter om eventuella tillbakalämningar och spara uppgifterna i en sträng.
//	if (!mysqli_num_rows($resultidag)) $Tillbakaidag = "$Tillbakaidag<br /><div class='Rubrik'>Inga registrerade returer idag.</div><br />";
//	else {

        // Räkna antalet returer idag.
        $Antalreturer = (mysqli_num_rows($resultidag));
        
        // Börja räkna rader.
        $Rad = 1;
        
   		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($resultidag)) {
            $RETURTITELID		= $row["RETURTITELID"];
            $RETUREAN			= $row["RETUREAN"];
            
            // Läs titeln som är kopplad till boken.
			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$RETURTITELID'";

			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
			if (!$titelresult = mysqli_query($db,$Lastitel)) {
				die('Invalid query: ' . mysqli_error($db));
			} 

			// Läs information från databasen litteratur om aktuell titel.
			while ($row = mysqli_fetch_array($titelresult)) {
				$TITELID		= $row["TITELID"];
				$KOD			= $row["KOD"];
				$TITEL			= $row["TITEL"];
				$FORFATTARE		= $row["FORFATTARE"];
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];

				// Kontrollera om data saknas och hantera det i så fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
				if (empty($UTGIVNINGSAR)) $UTGIVNINGSAR = "";
				else $UTGIVNINGSAR = " ($UTGIVNINGSAR)";
				if (empty($REVIDERAD)) $REVIDERAD = "";
				else $UTGIVNINGSAR = "$UTGIVNINGSAR Reviderad $REVIDERAD";
            
                // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                if (!empty($KOD)) {
                    
                    $KOD = str_replace(":00",":",$KOD);
                    $KOD = str_replace(":0",":",$KOD);
                    $KOD = str_replace(" 00"," ",$KOD);
                    $KOD = str_replace(" 0"," ",$KOD);
                }
                if (!empty($KOD)) $KOD = "$KOD ";

            }
            // Spara information om returen i en sträng.
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
	
    // Starta ett formulär.
    echo "<form action='tillbaka.php' name='lana' method='post' enctype='application/form-data'>";
	echo "<input name='EAN' type='text' class='Text' size='50'><br />";
	
    // Skriv ut knappar och avsluta formuläret.
    echo "<input type='submit' value='L&auml;mna tillbaka' class='Text' ><input type='button' name='Cancel' value='Avsluta' onclick='window.location.href = \"fm.php\" ' class='Text' /></form><br />";

    // Skriv ut en hjälpknapp.
    $HELPID = 5;
    require ("subrutiner/help.php");
	
    // Sätt fokus på inmatningsrutan så att markören alltid är där.
    echo "<script type='text/javascript'>document.lana.EAN.focus()</script>";		

	// Hantera den tillbakalämnade boken om den är inscannad.
	if (isset($_POST['EAN'])) {
	
		// Hämta streckkoden från föregående steg.
		$EAN		= $_POST["EAN"];
		
		// Kontrollera om streckkoden är numerisk och larma om så inte är fallet.
		if (!is_numeric($EAN)) {
			header("location:varning.php?Varning=830");
		}
			
		// Hämta information om boken i databasen bocker.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = $EAN";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
		// Varna om boken inte tillhör systemet.
		if (!mysqli_num_rows($result)) header("location:varning.php?Varning=190");	
		else {
	
			// Kolla om boken tillhör det aktuella biblioteket.
			if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) header("location:varning.php?Varning=180");
			
			// Läs information från databasen bocker om aktuell titel.
			while ($row = mysqli_fetch_array($result)) {
				$BOKID			= $row["BOKID"];
			}
			
			// Hämta upp aktuell utlåning från databasen aktiva.
			$Hamtalan = "SELECT * FROM aktiva WHERE BOKID = $BOKID";

			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$resultaktiva = mysqli_query($db,$Hamtalan)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// Hämta upp aktuell utlåning från databasen special.
			$Hamtaspecial = "SELECT * FROM special WHERE SBOKID = $BOKID";
			
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$resultspecial = mysqli_query($db,$Hamtaspecial)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
            // Kontrollera i vilken kategori boken är registrerad som utlånad.
            if (mysqli_num_rows($resultaktiva) == 1) $Kategori = "aktiva";
            if (mysqli_num_rows($resultspecial) == 1) $Kategori = "special";
            
            // Varna om boken inte är registrerad som utlånad.
            if ($Kategori == "") {
                header("location:varning.php?Varning=190");						
			}

			if ($Kategori == "aktiva") {
                
                // Läs information från databasen aktiva om aktuell utlåning.
                while ($row = mysqli_fetch_array($resultaktiva)) {
                    $LANID			= $row["LANID"];
                    $TITELID        = $row["TITELID"];
                    $MEDLEM         = $row["MEDLEM"];
                    $DATUM          = $row["DATUM"];
                    $BIBLIOTEK      = $row["BIBLIOTEK"];
                }

    			// Radera posten med utlåningen i databasen aktiva.
    			$Raderalan = "DELETE FROM aktiva WHERE LANID = $LANID";
    			if (!$resultaktiva = mysqli_query($db,$Raderalan)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
                
                // Lägg till den återlämnade boken i databasen returer.
                $Spararetur = "INSERT INTO returer (RETURTITELID,RETURDATUM,RETURBIBLIOTEK,RETUREAN) VALUES ('$TITELID','$Serveridag','".$_SESSION['Bibliotek']."','$EAN')";

                // Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
                if (!$resultretur = mysqli_query($db,$Spararetur)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
                // Lägg till det återlämnade boken i databasen gamlalan.
                $Sparagammal = "INSERT INTO gamlalan (TITELID,MEDLEM,BIBLIOTEK,DATUM) VALUES ('$TITELID','$MEDLEM','$DATUM','$BIBLIOTEK')";
                
                // Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
                if (!$resultgammal = mysqli_query($db,$Sparagammal)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
                // Hoppa vidare.
                header("location:tillbaka.php");
            }
            
			if ($Kategori == "special") {
                
                // Läs information från databasen special om aktuell utlåning.
                while ($row = mysqli_fetch_array($resultspecial)) {
                    $LANID			= $row["SLANID"];
                    $TITELID        = $row["STITELID"];
                    $MEDLEM         = $row["SMEDLEM"];
                    $DATUM          = $row["SDATUM"];
                    $BIBLIOTEK      = $row["SBIBLIOTEK"];
                }

    			// Radera posten med utlåningen i databasen aktiva.
    			$Raderalan = "DELETE FROM special WHERE SLANID = $LANID";
    			if (!$resultaktiva = mysqli_query($db,$Raderalan)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
                
                // Lägg till den återlämnade boken i databasen returer.
                $Spararetur = "INSERT INTO returer (RETURTITELID,RETURDATUM,RETURBIBLIOTEK,RETUREAN) VALUES ('$TITELID','$Serveridag','".$_SESSION['Bibliotek']."','$EAN')";

                // Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
                if (!$resultretur = mysqli_query($db,$Spararetur)) {
                    die('Invalid query: ' . mysqli_error($db));
                }

                // Lägg till det återlämnade boken i databasen gamlalan.
                $Sparagammal = "INSERT INTO gamlalan (TITELID,MEDLEM,BIBLIOTEK,DATUM) VALUES ('$TITELID','$MEDLEM','$DATUM','$BIBLIOTEK')";
                
                // Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
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