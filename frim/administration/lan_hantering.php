<?php
    session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";

    // Visa bibliotekets logotyp.
    require ("../subrutiner/top_exlibris.php");
    
    // Kolla upp vilket steg på sidan som ska utföras.
	$Steg = $_GET['Steg'];

	// Lista över böcker som är utlånade just nu.
	if ($Steg == "Utlanade") {
	
		// Hämta utlånade böcker från databasen aktiva.
		$Listautlan = "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC";
        if ($_SERVER["PHP_AUTH_USER"] == "9999") $Listautlan = "SELECT * FROM aktiva ORDER BY DATUM ASC";
        
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
        if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

        // Kolla efter om det finns några registrerade lån först.
		if (!mysqli_num_rows($result)) $Registreradelan = "<br /><div class='Rubrik'>Det finns inga p&aring;g&aring;ende l&aring;n just nu.</div>";
		else {
    		// Skriv ut en Tillbaka-knapp.
    		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
            
            // Bestäm vad rubriken ska innehålla.
    		if (mysqli_num_rows($result) == 1) $Utlanrubrik = "Denna bok &auml;r just nu utl&aring;nad:";
    		else $Utlanrubrik = "Dessa b&ouml;cker &auml;r just nu utl&aring;nade:";

            // Skriv ut rubriken och starta en tabell för att visa pågående lån.            
            echo "<br /><div class='Rubrik'>$Utlanrubrik</div><table align='center' cellpadding='5' border='1'>";
            echo "<tr bgcolor='#f1f1f1'><td valign='top' align='center' class='Text'>Enhets-ID:</td><td valign='top' align='left' class='Text'>Titel och l&aring;tagare:</td></tr>";
		
    		// Läs data om utlånade böcker i databasen aktiva.
    		while ($row = mysqli_fetch_array($result)) {
    			$LANID			= $row["LANID"];
    			$BOKID			= $row["BOKID"];
    			$TITELID		= $row["TITELID"];
    			$DATUM			= $row["DATUM"];
    			$MEDLEM			= $row["MEDLEM"];
			
    			// Hämta information om låntagaren.
    			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";

    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    				die('Invalid query: ' . mysqli_error($db));
				}
	
    			// Läs data om brodern i databasen medlem.
    			while ($row = mysqli_fetch_array($resultmedlem)) {
    				$Lantagaremedlem= $row["MEDLEM"];
    				$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
    				$Lantagaregrad	= $row["MEDLEMGRAD"];
    				$Lantagareloge	= $row["MEDLEMLOGE"];
				}

                // Översätt broderns grad till romerska siffror.
    			if ($Lantagaregrad == 1) $Grad = ", I";
    			if ($Lantagaregrad == 2) $Grad = ", II";
    			if ($Lantagaregrad == 3) $Grad = ", III";
    			if ($Lantagaregrad == 4) $Grad = ", IV-V";
    			if ($Lantagaregrad == 6) $Grad = ", VI";
    			if ($Lantagaregrad == 7) $Grad = ", VII";
    			if ($Lantagaregrad == 8) $Grad = ", VIII";
    			if ($Lantagaregrad == 9) $Grad = ", IX";
    			if ($Lantagaregrad == 10) $Grad = ", X";
    			if ($Lantagaregrad == 11) $Grad = ", XI";
                
                $MATRIKEL = "(".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." <a href='https://www.frimurarorden.se/medlemsmatrikel/' target='_Blank'>Matrikel</a>)";

    			// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
    			$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = '$Lantagareloge'";

    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}

    			// Läs in logens namn från databasen arbetsenheter.
    			while ($row = mysqli_fetch_array($logeresult)) {
    				$Lantagareloge = $row["ENHETNAMN"];
    			}

    			$MEDLEM = "<b>$Lantagarenamn</b>$Grad i $Lantagareloge $MATRIKEL";

    			// Läs titeln som är kopplad till boken.
    			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";
			
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
        
    				// Läs streckkoden som är kopplad till boken.
    				$Lasstreckkod = "SELECT * FROM bocker WHERE BOKID = '$BOKID'";
				
    				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    				if (!$eanresult = mysqli_query($db,$Lasstreckkod)) {
    					die('Invalid query: ' . mysqli_error($db));
					}
					
    				// Läs information från databasen bocker om aktuell titel.
    				while ($row = mysqli_fetch_array($eanresult)) {
    					$EAN			= $row["EAN"];
					}
					
    				$Registreradelan = "<tr><td align='left' valign='top' class='Text'>*$EAN*</td><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b><br /><br />L&aring;ntagare: $MEDLEM<br />Utl&aring;nad ".date('Y-m-d',$DATUM).".";

    				// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
    				$Tillbakadatum = $DATUM+2592000;
    				if ($Tillbakadatum >= $Serveridag) echo $Registreradelan." <span class='green'><b>Ska vara tillbakal&auml;mnad: ".date('Y-m-d', $Tillbakadatum)."</b></span></td></tr>";
    				else {
    					$Registreradelan = $Registreradelan." <span class='red'><b>Skulle varit tillbakal&auml;nad: ".date('Y-m-d', $Tillbakadatum)."</b></span></td></tr>";
       				}
                }
            }

            // Avsluta tabellen.
    		echo "</table>";
        }

		// Skriv ut en Tillbaka-knapp längst ner på sidan också.
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
	}
	
	// Lista över böcker som är specialutlånade just nu.
	if ($Steg == "Utlanadespecial") {
	
		// Hämta utlånade böcker från databasen aktiva.
		$Listautlan = "SELECT * FROM special WHERE SBIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY SDATUM ASC";
        if ($_SERVER["PHP_AUTH_USER"] == "9999") $Listautlan = "SELECT * FROM special ORDER BY SDATUM ASC";
        
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
        if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

        // Kolla efter om det finns några registrerade lån först.
		if (!mysqli_num_rows($result)) $Registreradelan = "<br /><div class='Rubrik'>Det finns inga p&aring;g&aring;ende speciall&aring;n just nu.</div>";
		else {
    		// Skriv ut en Tillbaka-knapp.
    		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
            
            // Bestäm vad rubriken ska innehålla.
    		if (mysqli_num_rows($result) == 1) $Utlanrubrik = "Denna bok &auml;r just nu specialutl&aring;nad:";
    		else $Utlanrubrik = "Dessa b&ouml;cker &auml;r just nu specialutl&aring;nade:";

            // Skriv ut rubriken och starta en tabell för att visa pågående lån.            
            echo "<br /><div class='Rubrik'>$Utlanrubrik</div><table align='center' cellpadding='5' border='1'>";
            echo "<tr bgcolor='#f1f1f1'><td valign='top' align='center' class='Text'>Enhets-ID:</td><td valign='top' align='left' class='Text'>Titel och l&aring;tagare:</td></tr>";
		
    		// Läs data om utlånade böcker i databasen special.
    		while ($row = mysqli_fetch_array($result)) {
    			$LANID			= $row["SLANID"];
    			$BOKID			= $row["SBOKID"];
    			$TITELID		= $row["STITELID"];
    			$DATUM			= $row["SDATUM"];
    			$MEDLEM			= $row["SMEDLEM"];
                $ANSVARIG       = $row["SANSVARIG"];
			
    			// Hämta information om låntagaren.
    			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";

    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    				die('Invalid query: ' . mysqli_error($db));
				}
	
    			// Läs data om brodern i databasen medlem.
    			while ($row = mysqli_fetch_array($resultmedlem)) {
    				$Lantagaremedlem= $row["MEDLEM"];
    				$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
    				$Lantagaregrad	= $row["MEDLEMGRAD"];
    				$Lantagareloge	= $row["MEDLEMLOGE"];
				}

                // Översätt broderns grad till romerska siffror.
    			if ($Lantagaregrad == 1) $Grad = ", I";
    			if ($Lantagaregrad == 2) $Grad = ", II";
    			if ($Lantagaregrad == 3) $Grad = ", III";
    			if ($Lantagaregrad == 4) $Grad = ", IV-V";
    			if ($Lantagaregrad == 6) $Grad = ", VI";
    			if ($Lantagaregrad == 7) $Grad = ", VII";
    			if ($Lantagaregrad == 8) $Grad = ", VIII";
    			if ($Lantagaregrad == 9) $Grad = ", IX";
    			if ($Lantagaregrad == 10) $Grad = ", X";
    			if ($Lantagaregrad == 11) $Grad = ", XI";
                
                $MATRIKEL = "(".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." <a href='https://www.frimurarorden.se/medlemsmatrikel/' target='_Blank'>Matrikel</a>)";

    			// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
    			$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = '$Lantagareloge'";

    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}

    			// Läs in logens namn från databasen arbetsenheter.
    			while ($row = mysqli_fetch_array($logeresult)) {
    				$Lantagareloge = $row["ENHETNAMN"];
    			}

    			$LANTAGARE = "<b>$Lantagarenamn</b>$Grad i $Lantagareloge $MATRIKEL";

    			// Hämta information om ansvarig utlånare.
    			$Hamtaansvarig = "SELECT * FROM medlem WHERE MEDLEM = $ANSVARIG";

    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$resultansvarig = mysqli_query($db,$Hamtaansvarig)) {
    				die('Invalid query: ' . mysqli_error($db));
				}
	
    			// Läs data om brodern i databasen medlem.
    			while ($row = mysqli_fetch_array($resultansvarig)) {
    				$Ansvarignamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
    				$Ansvariggrad	= $row["MEDLEMGRAD"];
    				$Ansvarigloge	= $row["MEDLEMLOGE"];
				}

                // Översätt broderns grad till romerska siffror.
    			if ($Ansvariggrad == 1) $Ansvariggrad = ", I";
    			if ($Ansvariggrad == 2) $Ansvariggrad = ", II";
    			if ($Ansvariggrad == 3) $Ansvariggrad = ", III";
    			if ($Ansvariggrad == 4) $Ansvariggrad = ", IV-V";
    			if ($Ansvariggrad == 6) $Ansvariggrad = ", VI";
    			if ($Ansvariggrad == 7) $Ansvariggrad = ", VII";
    			if ($Ansvariggrad == 8) $Ansvariggrad = ", VIII";
    			if ($Ansvariggrad == 9) $Ansvariggrad = ", IX";
    			if ($Ansvariggrad == 10) $Ansvariggrad = ", X";
    			if ($Ansvariggrad == 11) $Ansvariggrad = ", XI";
                
                $ANSVARIGMATRIKEL = "(".substr($ANSVARIG,0,4)."-".substr($ANSVARIG,4)." <a href='https://www.frimurarorden.se/medlemsmatrikel/' target='_Blank'>Matrikel</a>)";

    			// Hämta ansvarigs loge från databasen arbetsenheter och skriv ut den i klartext.
    			$Hamtaansvarigloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = '$Ansvarigloge'";

    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$ansvariglogeresult = mysqli_query($db,$Hamtaansvarigloge)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}

    			// Läs in logens namn från databasen arbetsenheter.
    			while ($row = mysqli_fetch_array($ansvariglogeresult)) {
    				$Ansvarigloge = $row["ENHETNAMN"];
    			}
                
                if ($Ansvarignamn == "") $ANSVARIG = "<span class='red'><b>Ansvarig utl&aring;nare kunde inte hittas!</b> SFMO-ID: ".substr($ANSVARIG,0,4)."-".substr($ANSVARIG,4)."</span>";
                else $ANSVARIG = "<b>$Ansvarignamn</b>$Ansvariggrad i $Ansvarigloge $ANSVARIGMATRIKEL";

    			// Läs titeln som är kopplad till boken.
    			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";
			
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
        
    				// Läs streckkoden som är kopplad till boken.
    				$Lasstreckkod = "SELECT * FROM bocker WHERE BOKID = '$BOKID'";
				
    				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    				if (!$eanresult = mysqli_query($db,$Lasstreckkod)) {
    					die('Invalid query: ' . mysqli_error($db));
					}
					
    				// Läs information från databasen bocker om aktuell titel.
    				while ($row = mysqli_fetch_array($eanresult)) {
    					$EAN			= $row["EAN"];
					}
					
    				$Registreradelan = "<tr><td align='left' valign='top' class='Text'>*$EAN*</td><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b><br /><br />L&aring;ntagare: $LANTAGARE<br />Utl&aring;nad ".date('Y-m-d',$DATUM).".";
                    echo $Registreradelan."<br />Ansvarig: $ANSVARIG";
                }
            }

            // Avsluta tabellen.
    		echo "</table>";
        }

		// Skriv ut en Tillbaka-knapp längst ner på sidan också.
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
	}
	
	// Lista över böcker som är försenade just nu.
	if ($Steg == "Sena") {
	
		// Hämta utlånade böcker från databasen aktiva.
		$Listautlan = "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC";
		
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
			}

		// Skapa en tabell över försenade tillbakalämningar.
		$Utlanrubrik = "F&ouml;rsenade tillbakal&auml;mningar:";
		if (!mysqli_num_rows($result)) {
			echo "<br /><div class='Rubrik'>Inga f&ouml;rsenade b&ouml;cker...</div><br />";
			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
			exit();
		}
		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
		$Registreradelan = "<br /><div class='Rubrik'>$Utlanrubrik</div><table align='center' cellpadding='5' border='1'>";
		
		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["LANID"];
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
			$DATUM			= $row["DATUM"];
			$MEDLEM			= $row["MEDLEM"];
			
			// Hämta information om låntagaren.
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
				die('Invalid query: ' . mysqli_error($db));
				}
                
                $Lantagaremail = "";
	
			// Läs data om brodern i databasen medlem.
			while ($row = mysqli_fetch_array($resultmedlem)) {
				$Lantagaremedlem= $row["MEDLEM"];
				$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
				$Lantagaregrad	= $row["MEDLEMGRAD"];
				$Lantagareloge	= $row["MEDLEMLOGE"];
				$Lantagaremail	= $row["MEDLEMMAIL"];
				}
			if ($Lantagaregrad == 1) $Grad = ", I";
			if ($Lantagaregrad == 2) $Grad = ", II";
			if ($Lantagaregrad == 3) $Grad = ", III";
			if ($Lantagaregrad == 4) $Grad = ", IV-V";
			if ($Lantagaregrad == 6) $Grad = ", VI";
			if ($Lantagaregrad == 7) $Grad = ", VII";
			if ($Lantagaregrad == 8) $Grad = ", VIII";
			if ($Lantagaregrad == 9) $Grad = ", IX";
			if ($Lantagaregrad == 10) $Grad = ", X";
			if ($Lantagaregrad == 11) $Grad = ", XI";

			// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
			$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = '$Lantagareloge'";

			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Läs in logens namn från databasen arbetsenheter.
			while ($row = mysqli_fetch_array($logeresult)) {
				$Lantagareloge = $row["ENHETNAMN"];
			}

            $MATRIKEL = "(".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." <a href='https://www.frimurarorden.se/medlemsmatrikel/' target='_Blank'>Matrikel</a>)";

			$MEDLEM = "<b>$Lantagarenamn</b>$Grad i $Lantagareloge $MATRIKEL";

			// Läs titeln som är kopplad till boken.
			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";
			
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
                    $KOD = $KOD." ";
                }
        
    			// Läs streckkoden som är kopplad till boken.
    			$Lasstreckkod = "SELECT * FROM bocker WHERE BOKID = '$BOKID'";
			
    			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    			if (!$eanresult = mysqli_query($db,$Lasstreckkod)) {
    				die('Invalid query: ' . mysqli_error($db));
				}
				
    			// Läs information från databasen bocker om aktuell titel.
    			while ($row = mysqli_fetch_array($eanresult)) {
    				$EAN			= $row["EAN"];
				}
				
        		$Registreradelan = "$Registreradelan";
        
        		// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
        		$Tillbakadatum = $DATUM+2592000;
        		if ($Tillbakadatum >= $Serveridag) $Registreradelan = $Registreradelan;
#        		else $Registreradelan = $Registreradelan."<tr><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b><br /><br />L&aring;ntagare: $MEDLEM<br />*$EAN* utl&aring;nad ".date('j M, Y',$DATUM).".</td><td align='center' bgcolor='#FF0000' valign='top' class='Text'><b>".date('Y-m-d', $Tillbakadatum)."</b></td></tr>";
				else $Registreradelan = $Registreradelan."<tr><td align='left' valign='top' class='Text'>*$EAN*</td><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b><br /><br />L&aring;ntagare: $MEDLEM<br />Utl&aring;nad ".date('Y-m-d',$DATUM).". <font color='red'><b>Skulle varit tillbaka ".date('Y-m-d', $Tillbakadatum).".</b></font></td></tr>";			
            }
		}
		$Registreradelan = "$Registreradelan</table>";

		// Skriv ut listan.
		echo "$Registreradelan";
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
	}
	
	// Lista sena returer per loge.
	if ($Steg == "Senaloge") {
	
		// Hämta utlånade böcker från databasen aktiva.
		$Listautlan = "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC";
		
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Skapa en tabell över försenade tillbakalämningar.
		$Utlanrubrik = "F&ouml;rsenade tillbakal&auml;mningar:";
		if (!mysqli_num_rows($result)) {
			echo "<br /><div class='Rubrik'>Inga f&ouml;rsenade b&ouml;cker...</div><br />";
			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
			exit();
		}
		$Registreradelan = "<br /><div class='Rubrik'>$Utlanrubrik</div><table align='center' cellpadding='5' border='1'>";

		// Välj loge.
		$Listaloger = "SELECT * FROM arbetsenheter ORDER BY ENHETNAMN ASC";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listaloger)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Skriv ut rubrik.
		echo "<div class='Rubrik'>V&auml;lj vilken loge som ska visas:</div>";
		
		// Öppna ett formulär.
		echo "<br /><form name='form' id='form'><select name='jumpMenu' id='jumpMenu'>";
		
		// Läs data från respektive loge.
		while ($row = mysqli_fetch_array($result)) {
			$ENHETNAMN	= $row["ENHETNAMN"];
			$ENHETNR	= $row["ENHETNR"];

			// Skriv ut menyraden och fortsätt till nästa rad.
			if (!strpos($ENHETNAMN, 'ening')) echo "<option value='lan_hantering.php?Steg=Senalogevald&amp;Loge=$ENHETNR' >$ENHETNAMN</option>";
		}
			
		// Stäng menyn och skriv ut en "Välj"-knapp.
		echo "</select> <input type='button' name='go_button' id= 'go_button' class='Text' value='V&auml;lj!' onclick='MM_jumpMenuGo(\"jumpMenu\",\"parent\",0)' /></form>";

		// Skriv ut lite mer text.
		echo "<br /><div class='Text'>V&auml;lj loge i listan ovan och tryck sedan \"V&auml;lj!\"</div>";		
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
		
	}

	// Visa statistik över försenade tillbakalämningar.
	if ($Steg == "Senstatistik") {
	
		$Utlanrubrik = "F&ouml;rsenade tillbakal&auml;mningar per loge:";
		$Registreradelan = "<br /><div class='Rubrik'>$Utlanrubrik</div><br /><table align='center' cellpadding='5' border='1' width='600'>";
		$Registreradelan = $Registreradelan."<tr bgcolor='#f1f1f1'><td align='left' valign='top' class='Text' width='10%'>Antal:</td><td align='left' valign='top' class='Text'>Loge:</td></tr>";

		// Hämta utlånade böcker från databasen aktiva.
		$Listautlan = "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC";
        if ($_SERVER["PHP_AUTH_USER"] == "9999") $Listautlan = "SELECT * FROM aktiva ORDER BY DATUM ASC";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

        // Kontrollera om det finns några försenade lån och säg till om så är fallet.
        $Sistadag = $Serveridag-2592000;
        $Raknasena = "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." AND DATUM <= ".$Sistadag."";
        if ($_SERVER["PHP_AUTH_USER"] == "9999") $Raknasena = "SELECT * FROM aktiva WHERE DATUM <= ".$Sistadag."";
        $Senaresult = mysqli_query($db,$Raknasena);
        $Antalsena = mysqli_num_rows($Senaresult);
    	
    	if ($Antalsena == 0) {
            echo "<div class='Rubrik'>Det finns inga sena l&aring;n i det h&auml;r biblioteket.</div><br />";
    		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";		
            exit();
    	}

		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($Senaresult)) {
			$DATUM			= $row["DATUM"];
			$MEDLEM			= $row["MEDLEM"];
			
			// Hämta information om låntagaren.
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";
            
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
				die('Invalid query: ' . mysqli_error($db));
			}
	
			// Läs data om brodern i databasen medlem.
			while ($row = mysqli_fetch_array($resultmedlem)) {
				$Lantagareloge	= $row["MEDLEMLOGE"];
			}

			$Registreradelan = "$Registreradelan";

			// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
			$Tillbakadatum = $DATUM+2592000;
			if ($Tillbakadatum >= $Serveridag) $Registreradelan = $Registreradelan;
			else $Loge[$Lantagareloge] = $Loge[$Lantagareloge]+1;
		}
		
		// Sortera logerna efter hur många försenade tillbakalämningar de har och räkna antalet loger.
		asort($Loge);
		$Antalloger = count($Loge);
		$Nycklar = array_keys($Loge);
		
		// Skriv ut en tabell.
		for ($i = 0; $i <= $Antalloger-1; $i++) {
			$Nyckel = $Nycklar[$i];
			$Registreradelan = $Registreradelan."<tr><td align='center' valign='top' class='Text'>$Loge[$Nyckel]</td>";
			
			// Hämta aktuell loge från databasen arbetsenheter och skriv ut den i klartext.
			$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $Nyckel";

			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Läs in logens namn från databasen arbetsenheter.
			while ($row = mysqli_fetch_array($logeresult)) {
				$Logenamn = $row["ENHETNAMN"];
			}

			$Registreradelan = $Registreradelan."<td align='left' valign='top' class='Text'><a href='lan_hantering.php?Steg=Senalogevald&amp;Loge=$Nyckel'>$Logenamn</a></td></tr>";
		}
		
		$Registreradelan = "$Registreradelan</table>";

		// Skriv ut listan.
		echo "$Registreradelan";
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";		
	}
	
	// Visa försenade lån i vald loge.
	if ($Steg == "Senalogevald") {
	
		// Hämta vald loges nummer.
		$Valdloge = $_GET['Loge'];
		
		// Hämta låntagarnas loge från databasen arbetsenheter och skriv ut den i klartext.
		$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $Valdloge ";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Läs in logens namn från databasen arbetsenheter.
		while ($row = mysqli_fetch_array($logeresult)) {
			$Valdlogenamn = $row["ENHETNAMN"];
		}
		
		$Utlanrubrik = "F&ouml;rsenade tillbakal&auml;mningar i $Valdlogenamn:";
		$Registreradelan = "<br /><div class='Rubrik'>$Utlanrubrik</div><table align='center' cellpadding='5' border='1'>";
        $Registreradelan = "$Registreradelan<tr bgcolor='#f1f1f1'><td valign='top' align='center' class='Text'>Enhets-ID:</td><td valign='top' align='left' class='Text'>Titel och l&aring;tagare:</td></tr>";

		// Hämta utlånade böcker från databasen aktiva.
		$Listautlan = "SELECT * FROM aktiva WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC";
        if ($_SERVER["PHP_AUTH_USER"] == "9999") $Listautlan = "SELECT * FROM aktiva ORDER BY DATUM ASC";
        

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["LANID"];
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
			$DATUM			= $row["DATUM"];
			$MEDLEM			= $row["MEDLEM"];
			
			// Hämta information om låntagaren.
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";
            
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
				die('Invalid query: ' . mysqli_error($db));
			}
            $Lantagareemail	= "";

			// Läs data om brodern i databasen medlem.
			while ($row = mysqli_fetch_array($resultmedlem)) {
				$Lantagaremedlem= $row["MEDLEM"];
				$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
				$Lantagaregrad	= $row["MEDLEMGRAD"];
				$Lantagareloge	= $row["MEDLEMLOGE"];
				$Lantagarelogen	= $row["MEDLEMLOGE"];
			}
                
			if ($Lantagaregrad == 1) $Grad = ", I";
			if ($Lantagaregrad == 2) $Grad = ", II";
			if ($Lantagaregrad == 3) $Grad = ", III";
			if ($Lantagaregrad == 4) $Grad = ", IV-V";
			if ($Lantagaregrad == 6) $Grad = ", VI";
			if ($Lantagaregrad == 7) $Grad = ", VII";
			if ($Lantagaregrad == 8) $Grad = ", VIII";
			if ($Lantagaregrad == 9) $Grad = ", IX";
			if ($Lantagaregrad == 10) $Grad = ", X";
			if ($Lantagaregrad == 11) $Grad = ", XI";
            
            $MATRIKEL = "(".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." <a href='https://www.frimurarorden.se/medlemsmatrikel/' target='_Blank'>Matrikel</a>)";

			$MEDLEM = "<b>$Lantagarenamn</b>$Grad i $Valdlogenamn $MATRIKEL";

			// Kontrollera om brodern är medlem i vald loge.
			if ($Lantagarelogen <> $Valdloge) echo "";
			else {
			
				// Läs titeln som är kopplad till boken.
				$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";
				
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
        
					// Läs streckkoden som är kopplad till boken.
					$Lasstreckkod = "SELECT * FROM bocker WHERE BOKID = '$BOKID'";
					
					// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
					if (!$eanresult = mysqli_query($db,$Lasstreckkod)) {
						die('Invalid query: ' . mysqli_error($db));
						}
						
					// Läs information från databasen bocker om aktuell titel.
					while ($row = mysqli_fetch_array($eanresult)) {
						$EAN			= $row["EAN"];
						}
						
					$Registreradelan = "$Registreradelan";

					// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
					$Tillbakadatum = $DATUM+2592000;
					if ($Tillbakadatum >= $Serveridag) $Registreradelan = $Registreradelan;
					else $Registreradelan = $Registreradelan."<tr><td align='left' valign='top' class='Text'>*$EAN*</td><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b><br /><br />L&aring;ntagare: $MEDLEM<br />Utl&aring;nad ".date('Y-m-d',$DATUM).". <font color='red'><b>Skulle varit tillbaka ".date('Y-m-d', $Tillbakadatum).".</b></font></td></tr>";
				}
			}
		}
		$Registreradelan = "$Registreradelan</table>";

		// Skriv ut listan.
		echo "$Registreradelan";
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
	}
	
	// Topplistan över utlånade titlar.
	if ($Steg == "Topplistan") {

        // Bestäm vilket år som ska visas.
        $Aret = date("Y");
        
		// Hämta utlånade titlar från databasen litteratur.
#		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".$_SESSION['Bibliotek'].date("Y")."%'";
		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%$Aret%'";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		if (!mysqli_num_rows($result)) echo "<br /><div class='Rubrik'>Topplistan &auml;r tom...</div><br />";
		else {
		
#			echo "<div class='Rubrik'>Topplistan &ouml;ver de mest utl&aring;nade titlarna ".date("Y")."</div>";
			echo "<div class='Rubrik'>Topplistan &ouml;ver de mest utl&aring;nade titlarna $Aret</div>";

			// Starta en loop från grad 0 till grad 10 och skriv ut ankarlänkar till respektikve grad.
			for ($Gradnummer = 0; $Gradnummer <= 10; $Gradnummer +=1){
				$Lank = "<button type='button' onClick='parent.location=\"lan_hantering.php?Steg=Topplistan#$Gradnummer\"' class='Text' >";
				// Bestäm gradrubriken baserat på graden.
				if ($Gradnummer == 1) $Gradlank = "$Lank I</button>";
				if ($Gradnummer == 2) $Gradlank = "$Lank II</button>";
				if ($Gradnummer == 3) $Gradlank = "$Lank III</button> - ";
				if ($Gradnummer == 4) $Gradlank = "$Lank IV-V</button>";
				if ($Gradnummer == 6) $Gradlank = "$Lank VI</button> - ";
				if ($Gradnummer == 7) $Gradlank = "$Lank VII</button>";
				if ($Gradnummer == 8) $Gradlank = "$Lank VIII</button>";
				if ($Gradnummer == 9) $Gradlank = "$Lank IX</button>";
				if ($Gradnummer == 10) $Gradlank = "$Lank X</button>";

				// Om gradnumret är 0 eller 5 så gör ingenting, annars skriv ut en lista på gradens titlar.
				if ($Gradnummer == 0 || $Gradnummer == 5) echo "";
				else {
					echo "$Gradlank";
				}
			}
			echo "<br /><br />";
			echo "<table border='1' align='center' cellpadding='5'>";

			// Funktion för att underlätta sorteringen av de lokala lånen.
			function compare($x, $y){
				if ( $x['LANLOKALT'] == $y['LANLOKALT'] ) return 0; 
				elseif ( $x['LANLOKALT'] > $y['LANLOKALT'] )  return -1; 
				else return 1;
			}
					
			// Starta en loop från grad 0 till grad 10 och hämta respektive grads titlar från databasen.
			for ($Gradnummer = 1; $Gradnummer <= 10; $Gradnummer +=1){
			
				// Bestäm gradrubriken baserat på graden.
				if ($Gradnummer == 1) $Gradrubrik = "S:t Johannes Grad I";
				if ($Gradnummer == 2) $Gradrubrik = "S:t Johannes Grad II";
				if ($Gradnummer == 3) $Gradrubrik = "S:t Johannes Grad III";
				if ($Gradnummer == 4) $Gradrubrik = "S:t Andreas Grad IV-V";
				if ($Gradnummer == 6) $Gradrubrik = "S:t Andreas Grad VI";
				if ($Gradnummer == 7) $Gradrubrik = "Kapitel Grad VII";
				if ($Gradnummer == 8) $Gradrubrik = "Kapitel Grad VIII";
				if ($Gradnummer == 9) $Gradrubrik = "Kapitel Grad IX";
				if ($Gradnummer == 10) $Gradrubrik = "Kapitel Grad X";		

				if ($Gradnummer == 5) $Gradnummer = 5;
				else {
					// Hämta utlånade titlar från databasen litteratur.
#					$Samlatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND LANARRAY LIKE '%".$_SESSION['Bibliotek'].date("Y")."%'";
#					$Samlatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND LANARRAY LIKE '%".date("Y")."%'";
					$Samlatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND LANARRAY NOT LIKE ''";

					// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
					if (!$result = mysqli_query($db,$Samlatitlar)) {
						die('Invalid query: ' . mysqli_error($db));
					}
				
					echo "<a name='$Gradnummer'> </a><tr><td valign='top' align='center' class='Rubrik' bgcolor='#F1F1F1'>$Gradrubrik</td></tr>";
                    
                    // Kontrollera så att det finns utlånade böcker i den aktuella graden.
                    if (!mysqli_num_rows($result)) echo "<tr><td class='Text'>Det finns inga utl&aring;nade titlar i denna grad &auml;nnu.</td></tr>";
                    else {

    					// Läs information från databasen litteratur om utvald titel.
    					$i = 1;
    					while ($row = mysqli_fetch_array($result)) {
    						$TITELID		= $row["TITELID"];
    						$KOD			= $row["KOD"];
    						$TITEL			= $row["TITEL"];
    						$FORFATTARE		= $row["FORFATTARE"];
    						$GRAD			= $row["GRAD"];
    						$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
    						$STUDIEPLAN		= $row["STUDIEPLAN"];
    						$REVIDERAD		= $row["REVIDERAD"];
    						$LAN			= $row["LAN"];
    						$LANARRAY		= $row["LANARRAY"];
    
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
        
    						// Skriv ut graden i klartext.
    						if ($GRAD == "0") $GRAD = "&Ouml;ppen";
    						elseif ($GRAD == "1") $GRAD = "I";
    						elseif ($GRAD == "2") $GRAD = "II";
    						elseif ($GRAD == "3") $GRAD = "III";
    						elseif ($GRAD == "4") $GRAD = "IV-V";
    						elseif ($GRAD == "6") $GRAD = "VI";
    						elseif ($GRAD == "7") $GRAD = "VII";
    						elseif ($GRAD == "8") $GRAD = "VIII";
    						elseif ($GRAD == "9") $GRAD = "IX";
    						elseif ($GRAD == "10") $GRAD = "X";
    
    						// Kontrollera om titeln tillhör studieplanen och skriv i så fall ut det i klartext.
    						if ($STUDIEPLAN == "B") $STUDIEPLAN = "Bas";
    						if ($STUDIEPLAN == "K") $STUDIEPLAN = "Komplettering";
    						if ($STUDIEPLAN == "F") $STUDIEPLAN = "F&ouml;rdjupning";		
    
    						// Kontrollera om det finns några böcker kopplade till titeln.
    						$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
    						// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    						if (!$bokresult = mysqli_query($db,$Listabocker)) {
    							die('Invalid query: ' . mysqli_error($db));
    						}
    						$LANARRAY = unserialize($LANARRAY);
    						$LANLOKALT = $LANARRAY[$_SESSION['Bibliotek'].$Aret];
    						
    						// Läs information från databasen bocker om aktuell titel.
    						$Raknabocker = mysqli_num_rows($bokresult);
    						$Lanerad = $KOD." <b>&ldquo;$TITEL&rdquo;</b> ".$UTGIVNINGSAR."<br />F&ouml;rfattare: ".$FORFATTARE;
    						if (!empty($STUDIEPLAN)) $Lanerad = $Lanerad."<br /><span class='blue'>Studieplan: $STUDIEPLAN</span>";
                            if ($_SERVER['REMOTE_USER'] <> "9999" && !empty($LANLOKALT)) $Lanerad = $Lanerad."<br /><br />$LANLOKALT utl&aring;n i biblioteket under $Aret.";
                            if ($_SERVER['REMOTE_USER'] <> "9999" && empty($LANLOKALT))  $Lanerad = $Lanerad."<br /><br />Inga utl&aring;n i biblioteket under $Aret.";
    						$Lokallan[$i] = array(LaneID => $i, Lanerad => "$Lanerad", GRAD => "$GRAD", LANLOKALT => $LANLOKALT);
    						$i = $i+1;
    					
    					}
    
    					// Dra bort ett från $i för att kompensera den sista ettan som lagts till.
    					$i = $i-1;
    
    					// Sortera de lokala lånen om listan inte är tom.
    					if (!$Lokallan == "") usort($Lokallan, "compare");
    
    					for ($n=0; $n<=9; $n++) {
    						// Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
    						$LANLOKALT = $Lokallan[$n]['LANLOKALT'];
    						$GRAD = $Lokallan[$n]['GRAD'];
    						$LaneID = $Lokallan[$n]['LaneID'];
    						$Lanerad = $Lokallan[$n]['Lanerad'];
    						echo "<tr><td valign='top' align='left' class='Text'>$Lanerad</td></tr>";
    					}
				    }
                }
				echo "<tr height='10px'></tr>";
				$Lokallan = "";
			}				
			// Stäng tabellen.
			echo "</table><br />";
		}
		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button><br /><br />";
	}
	
	// Skicka ett mail om försenade böcker.
	if ($Steg == "Maila") {
	
		// Hämta utlånade böcker från databasen aktiva.
#		$Listautlan = "SELECT * FROM aktiva WHERE BIBLIOTEK = $BIBLIOTEK";
		$Listautlan = "SELECT * FROM aktiva";
		
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listautlan)) {
			die('Invalid query: ' . mysqli_error($db));
		}
	
		// Kontrollera om det finns några utlånade böcker och avsluta om det inte finns några.
		if (!mysqli_num_rows($result)) {
			echo "<br /><div class='Rubrik'>Inga f&ouml;rsenade b&ouml;cker...</div><br />";
			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
			exit();
		}
		
		$Skickade = 0;
		
		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["LANID"];
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
			$DATUM			= $row["DATUM"];
			$MEDLEM			= $row["MEDLEM"];
			$BIBLIOTEK		= $row["BIBLIOTEK"];

			// Läs data om det valda biblioteket.
			$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEK";
					
			// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
			if (!$bibliotekresult = mysqli_query($db,$Visabibliotek)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			while ($row = mysqli_fetch_array($bibliotekresult)) {
				$BIBLIOTEKNAMN	= utf8_decode($row["BIBLIOTEKNAMN"]);
			}
			
			// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
			$Tillbakadatum = $DATUM+2592000;
			if ($Tillbakadatum <= $Serveridag) {
			
				// Hämta information om låntagaren.
				$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";

				// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
				if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
					die('Invalid query: ' . mysqli_error($db));
				}
                
                $Lantagaremail = "";
	
				// Läs data om brodern i databasen medlem.
				while ($row = mysqli_fetch_array($resultmedlem)) {
					$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
					$Lantagaremail	= $row["MEDLEMMAIL"];
				}

				// Kontrollera om låntagarens epostadress är registrerad.
				if ($Lantagaremail <> "") {

					// Etablera kontakt med databasen igen för säkerhets skull.
					require ("../konfiguration/konfig.php");
					$db = mysqli_connect($server, $db_user, $db_pass);
					mysqli_select_db($db,$database);

					// Läs titeln som är kopplad till boken.
					$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";
					
					// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
					if (!$titelresult = mysqli_query($db,$Lastitel)) {
						die('Invalid query: ' . mysqli_error($db));
					} 

					// Läs information från databasen litteratur om aktuell titel.
					while ($row = mysqli_fetch_array($titelresult)) {
						$TITELID		= $row["TITELID"];
						$KOD			= $row["KOD"];
						$TITEL			= utf8_decode($row["TITEL"]);
					}
					
                    // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                    if (!empty($KOD)) {
                        
                        $KOD = str_replace(":00",":",$KOD);
                        $KOD = str_replace(":0",":",$KOD);
                        $KOD = str_replace(" 00"," ",$KOD);
                        $KOD = str_replace(" 0"," ",$KOD);
                        $KOD = "$KOD ";
                    }
        
					$Mottagare = $Lantagaremail;
#					$Mottagare = "kontakt@electricfantasticsound.info";
					echo "$Lantagarenamn - $Mottagare<br />";
					$Rubrik = "Påminnelse från Frimurarebiblioteket";
					$Extra = "From: no-reply@frimurarna.se"."\r\nX-Mailer: PHP/".phpversion()."\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n";
					$Meddelande = "Broder $Lantagarenamn,<br />Den ".date('j/n Y',$DATUM)." l&aring;nade du boken \"$TITEL\". Boken skulle varit tillbaka i biblioteket den ".date('j/n Y', $Tillbakadatum)." men &auml;r &auml;nnu inte &aring;terl&auml;mnad.<br /><br />Biblioteket vill naturligtvis ha tillbaka boken. V&auml;nligen &aring;terl&auml;mna den snarast.<br /><br />Skulle biblioteket vara st&auml;ngt kan du l&auml;gga boken i bibliotekets tydligt uppm&auml;rkta l&aring;da. Du kan ocks&aring; &aring;terl&auml;mna boken genom ombud eller s&aring; kan du i f&ouml;rsluten f&ouml;rs&auml;ndelse skicka boken till biblioteket.<br /><br />Observera att du inte kan svara p&aring; detta mail.<br /><br />Med de b&auml;sta f&ouml;rhoppningar!<br />Biblioteket, $BIBLIOTEKNAMN";			
					mail($Mottagare, $Rubrik, $Meddelande, $Extra);
#					sleep(5);
					$Skickade = $Skickade+1;
				}
			}
		}

		// Skriv ut en avslutande text.
		echo "<br /><div class='Rubrik'>$Skickade p&aring;minnelsemail skickade.</div><br />";
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
		
	}
	
?>
</div>
</body>
</html>