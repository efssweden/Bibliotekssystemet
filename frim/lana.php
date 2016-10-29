<?php
session_start();

    // Starta dokumentets sidhuvud.
    require("subrutiner/head.php");

	// Starta <body> och <div>.
	echo "<body><div id='ALLT'>";
    
    // Skriv ut logon på sidan.
    require("subrutiner/top_exlibris.php");

    // Kolla upp vad det är för typ av lån som ska göras.
    if (!isset($_GET['Typ'])) $Typ = "Vanligt";
    else $Typ = $_GET['Typ'];

    // "Vanligt" = vanligt medlemslån. "Reception" = receptionslån. "Special" = speciallån.

    // Om man lånar vanligt eller nyrecipierad - kolla om det finns några speciallån.
    if ($Typ == "Vanligt" || $Typ == "Reception") {
        
    	// Förbered kontrollen av antalet speciallånade böcker hos låntagaren.
    	$Raknaspecial = "SELECT * FROM special WHERE SMEDLEM = ".$_SESSION['Lantagaremedlem']."";
    
    	// Skicka begäran till servern och stoppa om det blir något fel.
    	if (!$resultspecial = mysqli_query($db,$Raknaspecial)) {
    		die('Invalid query: ' . mysqli_error($db));
    	}
    	
    	// Räkna efter hur många speciallån som finns registrerade.
    	$Antalspecial = mysqli_num_rows($resultspecial);
    	
        // Skriv ut en rad med information om det finns registrerade speciallån.
        if ($Antalspecial >= 1){
            $Registreradelan = "<br /><table cellpadding='5' width='400' bgcolor='yellow' align='center' border='1' class='Rubrik'><tr><td><a href='lana.php?Typ=Special'>Visa $Antalspecial speciall&aring;n</a></td></tr></table>";
        }
    }
    
    // Om man speciallånar - kolla om det finns några vanliga lån registrerade.
    if ($Typ == "Special") {
        
    	// Förbered kontrollen av antalet lånade böcker hos låntagaren.
    	$Raknalan = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";
    
    	// Skicka begäran till servern och stoppa om det blir något fel.
    	if (!$resultlan = mysqli_query($db,$Raknalan)) {
    		die('Invalid query: ' . mysqli_error($db));
    	}
    	
    	// Räkna efter hur många lån som finns registrerade.
    	$Antallan = mysqli_num_rows($resultlan);
    	
        // Skriv ut en rad med information om det finns registrerade lån.
        if ($Antallan >= 1){
            $Registreradelan = "<br /><table cellpadding='5' width='400' bgcolor='yellow' align='center' border='1' class='Rubrik'><tr><td><a href='lana.php'>Visa $Antallan vanliga l&aring;n</a></td></tr></table>";
        }
    }
    
    
    
	// Kontrollera först om låntagaren har registrerade lån.
	if ($Typ <> "Special") $Kontrolleratidigare = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']." ORDER BY DATUM ASC";
	if ($Typ == "Special") $Kontrolleratidigare = "SELECT * FROM special WHERE SMEDLEM = ".$_SESSION['Lantagaremedlem']." ORDER BY SDATUM ASC";

	// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
	if (!$result = mysqli_query($db,$Kontrolleratidigare)) {
		die('Invalid query: ' . mysqli_error($db));
	}

	// Spara uppgifter om eventuella lån och spara den uppgiften i en sträng.
	if (!mysqli_num_rows($result)) $Registreradelan = "$Registreradelan<br /><div class='Rubrik'>Inga registrerade l&aring;n.</div>";
	else {
		
		// Skapa en tabell i en sträng över registrerade utlån.
		if ($Typ <> "Special") $Registreradelan = "$Registreradelan<br /><div class='Rubrik'>Registrerade l&aring;n:</div><table cellpadding='5' align='center' border='1'>";
		if ($Typ == "Special") $Registreradelan = "$Registreradelan<br /><div class='Rubrik'>Registrerade speciall&aring;n:</div><table cellpadding='5' align='center' border='1'>";
        if ($Typ <> "Special") $Registreradelan = $Registreradelan."<tr bgcolor='#f1f1f1'><td align='center' valign='top' class='Text'>Enhets-ID:</td><td align='left' valign='top' class='Text'>Titel och f&ouml;rfattare:</td></tr>";
        if ($Typ == "Special") $Registreradelan = $Registreradelan."<tr bgcolor='#f1f1f1'><td align='center' valign='top' class='Text'>Enhets-ID:</td><td align='left' valign='top' class='Text'>Titel och f&ouml;rfattare:</td><td align='center' valign='top' class='Text'>Bibliotek:</td></tr>";

		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {

            // Hämta information om vanliga lån.
            if ($Typ <> "Special") {
    			$LANID			= $row["LANID"];
    			$BOKID			= $row["BOKID"];
    			$TITELID		= $row["TITELID"];
    			$DATUM			= $row["DATUM"];
            }
            
            // Hämta information om speciallån.
            if ($Typ == "Special") {
    			$LANID			= $row["SLANID"];
    			$BOKID			= $row["SBOKID"];
    			$TITELID		= $row["STITELID"];
    			$DATUM			= $row["SDATUM"];
    			$BIBLIOTEK		= $row["SBIBLIOTEK"];
    			$SANSVARIG		= $row["SANSVARIG"];

                // Läs data om det utlånande biblioteket.
                $Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEK";
        			
                // Skicka begäran till databasen. Stoppa och visa felet om något går galet.
                if (!$resultbibliotek = mysqli_query($db,$Visabibliotek)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
            	while ($row = mysqli_fetch_array($resultbibliotek)) {
            		$BIBLIOTEK	= $row["BIBLIOTEKNAMN"];
        	    }
    
                // Lägg till ett bindestreck i ansvarig bibliotekaries medlemsnummer.
                $ANSVARIG = substr($SANSVARIG,0,4)."-".substr($SANSVARIG,4);
			}
            
            // Läs streckkoden för den aktuella boken.
            $Visaean = "SELECT * FROM bocker WHERE BOKID=$BOKID";
        			
            // Skicka begäran till databasen. Stoppa och visa felet om något går galet.
            if (!$resultean = mysqli_query($db,$Visaean)) {
                die('Invalid query: ' . mysqli_error($db));
            }
                
            while ($row = mysqli_fetch_array($resultean)) {
            	$EAN	= $row["EAN"];
        	}
            
            // Kontrollera om den utlånade boken tillhör det aktuella biblioteket.
            if (substr($EAN,0,4) == $_SERVER["PHP_AUTH_USER"]) {
                $Vilketbibliotek = ".";
            }
            else {
                
                // Läs data om det utlånande biblioteket.
                $Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=".substr($EAN,0,4)."";
        			
                // Skicka begäran till databasen. Stoppa och visa felet om något går galet.
                if (!$resultbibliotek = mysqli_query($db,$Visabibliotek)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
            	while ($row = mysqli_fetch_array($resultbibliotek)) {
            		$BIBLIOTEK	= $row["BIBLIOTEKNAMN"];
        	    }
                $Vilketbibliotek = " i $BIBLIOTEK.";                
            }
			
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
                        
                // Räkna ut hur många dagar brodern haft boken.
                if ($Typ == "Special") {
                    $Antaldagar = round(($Serveridag-$DATUM)/86400);
                    if ($Antaldagar == 1) $Antaldagar = " <span class='green'>$Antaldagar dag sedan.</span>";
                    elseif ($Antaldagar >= 2) $Antaldagar = " <span class='green'>$Antaldagar dagar sedan.</span>";
                    else $Antaldagar = " <span class='green'>L&aring;nades ut idag.</span>";
                }
				
				// Spara uppgiften/uppgifterna i en sträng.
				$Registreradelan = "$Registreradelan<tr><td align='center' valign='top' class='Text'>*$EAN*</td><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b>$UTGIVNINGSAR<br />F&ouml;rfattare: $FORFATTARE<br />Utl&aring;nad: ".date('Y-m-d',$DATUM)."$Vilketbibliotek";

                // Lägg till mer information om det är ett speciallån.
				if ($Typ == "Special") $Registreradelan = $Registreradelan."$Antaldagar</td><td align='center' valign='top' class='Text'>$BIBLIOTEK<br />$ANSVARIG</td></tr>";
		
				// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder) om det inte är ett speciallån.
                if ($Typ <> "Special") {
    				$Tillbakadatum = $DATUM+2592000;
    				if ($Tillbakadatum >= $Serveridag) $Registreradelan = $Registreradelan." <span class='green'>Ska vara tillbakal&auml;mnad: <b>".date('Y-m-d', $Tillbakadatum)."</b>.</span></td></tr>";
    				else $Registreradelan = $Registreradelan." <span class='red'><b>Skulle varit tillbakal&auml;mnad: ".date('Y-m-d', $Tillbakadatum)."</b>.</span></td></tr>";
                }
            }
		}
		
		// Stäng tabellen i strängen.
		$Registreradelan = "$Registreradelan</table>";
	}	
	
	// Kontrollera om låntagarens epostadress finns registrerad.
	// if ($_SESSION['Lantagaremail'] <> "") $Mail = "<img src='design/Email.png' title='Epostadress finns registrerad.' align='bottom' />";
	// else $Mail = "";
    
    $Mail = "";
	
	// Skriv ut namn, grad och logetillhörighet för låntagaren, eventuella lån samt instruktioner.
	echo "<div class='Text'>L&aring;ntagare: <b>".$_SESSION['Lantagarenamn']."</b>".$_SESSION['Grad'].", ".$_SESSION['Lantagareloge'].". $Mail<br />".$Registreradelan."<br /></div>";

	// Förbered kontrollen av antalet redan lånade böcker hos låntagaren.
	$Raknalan = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";

	// Skicka begäran till servern och stoppa om det blir något fel.
	if (!$resultrakna = mysqli_query($db,$Raknalan)) {
		die('Invalid query: ' . mysqli_error($db));
	}
	
	// Räkna efter hur många lån som finns registrerade.
	$Antallan = mysqli_num_rows($resultrakna);

# BORTPLOCKAD RUTIN FÖR ATT BEGRÄNSA ANTALET TILLÅTNA LÅN 2013-08-20	
	// Om antalet är tre och det inte är ett speciallån så visa inte inmatningsrutan längre.
#	if ($Typ <> "Special" && $Antallan == 3) {
#		echo "<div class='Text'><b>Du har l&aring;nat maximalt till&aring;tet antal b&ouml;cker.</b></div><br />";
#		echo "<input type='button' name='Kvitto' value='L&aring;nelapp' onclick='window.location.href = \"administration/rapport_hantering.php?Steg=Kvitto\" ' class='Text' />";
#		echo "<input type='button' name='Cancel' value='Avsluta' onclick='window.location.href = \"fm.php\" ' class='Text' /><br />";

        // Skriv ut en hjälpknapp.
#        $HELPID = 7;
#        if (!isset($_POST['EAN'])) require ("subrutiner/help.php");
#	}
#

	// Om antalet lånade böcker är färre än tre så visa inmatningsrutan.
#	if ($Typ == "Special" || $Antallan <= 2) {
	if ($Typ == "Special" || $Antallan <= 9999) {
	
		// Skriv ut instruktioner.
		echo "<div class='Text'>Scanna av eller skriv in streckkoden p&aring; boken som ska l&aring;nas eller tryck Avsluta.<br /><br /></div>";
		
		// Öppna ett formulär för streckkoden.
		echo "<form action='lana.php?Steg=Scannad&Typ=$Typ' name='lana' method='post' enctype='application/form-data'>";
		
		// Skriv ut en inmatningsruta för streckkoden.
		echo "<input name='EAN' type='text' class='Text' size='50'><br />";
		
		// Skriv ut knappar för att låna och avsluta.
		echo "<input type='submit' value='L&aring;na' class='Text' ><input type='button' name='Kvitto' value='L&aring;nelapp' onclick='window.location.href = \"administration/rapport_hantering.php?Steg=Kvitto\" ' class='Text' /><input type='button' name='Cancel' value='Avsluta' onclick='window.location.href = \"fm.php\" ' class='Text' /></form><br />";
		
        // Skriv ut en hjälpknapp.
        if ($Typ <> "Special") $HELPID = 7;
        if ($Typ == "Special") $HELPID = 8;
        if (!isset($_POST['EAN'])) require ("subrutiner/help.php");

		// Sätt fokus på inmatningsrutan så att markören alltid befinner sig där från början.
		echo "<script type='text/javascript'>document.lana.EAN.focus()</script>";
	}

	// Kontrollera om streckkoden är ifylld och spara i så fall uppgifterna om lånet.
	if (isset($_POST['EAN'])) {

        $Typ            = $_GET['Typ'];
		$EAN			= $_POST["EAN"];
		$Lantagarenamn	= $_SESSION['Lantagarenamn'];
		$Lantagaregrad	= $_SESSION['Lantagaregrad'];
		$Lantagareloge	= $_SESSION['Lantagareloge'];
		$Lantagaremail	= $_SESSION['Lantagaremail'];
		$Lantagaremedlem= $_SESSION['Lantagaremedlem'];
		$Grad			= $_SESSION['Grad'];
		
		// Kontrollera om streckkoden är numerisk och larma om så inte är fallet.
		if (!is_numeric($EAN)) {
			header("location:varning.php?Varning=530");
            exit ();
		}

		// Kolla om boken tillhör det aktuella biblioteket.
		if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) {
            header("location:varning.php?Varning=580");
            exit();
        }
	
		// Förbered hämtningen av den utvalda boken från databasen bocker.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = $EAN";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Kontrollera om boken tillhör biblioteket och varna om så inte är fallet.
		if (!mysqli_num_rows($result)) {
			header("location:varning.php?Varning=580");
            exit ();
		}
		else {

			// Läs data om utvald titel i databasen bocker.
			while ($row = mysqli_fetch_array($result)) {
				$BOKID			= $row["BOKID"];
				$TITELID		= $row["TITELID"];
				$EAN			= $row["EAN"];
			}

			// Kontrollera om boken redan är utlånad.
			$Hamtalan = "SELECT * FROM aktiva WHERE BOKID = $BOKID";
			if (!$resultlan = mysqli_query($db,$Hamtalan)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Visa en varning om boken redan är registrerad som utlånad.
			if (mysqli_num_rows($resultlan)) {
				header("location:varning.php?Varning=550");
				exit();
			}
			
			// Kontrollera om boken redan är specialutlånad.
			$Hamtaspecial = "SELECT * FROM special WHERE SBOKID = $BOKID";
			if (!$resultspecial = mysqli_query($db,$Hamtaspecial)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Visa en varning om boken redan är registrerad som utlånad.
			if (mysqli_num_rows($resultspecial)) {
                header("location:varning.php?Varning=550");
				exit();
			}
			
			// Läs titeln som är kopplad till boken.
			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = $TITELID";
			
			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
			if (!$titelresult = mysqli_query($db,$Lastitel)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// Läs information från databasen litteratur om aktuell titel.
			while ($row = mysqli_fetch_array($titelresult)) {
				$TITELID		= $row["TITELID"];
				$KOD			= $row["KOD"];
				$GRAD			= $row["GRAD"];
				$TITEL			= $row["TITEL"];
				$FORFATTARE		= $row["FORFATTARE"];
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];
				$STUDIEPLAN		= $row["STUDIEPLAN"];
				$LAN			= $row["LAN"];
				$LANARRAY		= $row["LANARRAY"];

				// Kontrollera om data saknas och hantera det i så fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
				if (empty($UTGIVNINGSAR)) $UTGIVNINGSAR = "";
				else $UTGIVNINGSAR = " ($UTGIVNINGSAR)";
				if (empty($REVIDERAD)) $REVIDERAD = "";
				else $UTGIVNINGSAR = "$UTGIVNINGSAR Reviderad $REVIDERAD";
			}

# BORTPLOCKAD RUTIN FÖR ATT KONTROLLERA LÅNTAGARENS NUVARANDE GRAD 2013-08-20
#
			// Kontrollera om låntagarens grad är lägre än titelns grad om det inte är ett receptionslån och varna och avsluta i så fall.
#			if ($Typ <> "Reception") {
#                if ($GRAD >= ($Lantagaregrad + 1)) {
#    			header("location:varning.php?Varning=562");
#    				exit();
#    			}
#            }
#
			// Kontrollera om titeln är tillbakadragen och varna och avsluta i så fall om man inte gör ett speciallån.
			if ($STUDIEPLAN == "X" && $Typ <> "Special") {
			header("location:varning.php?Varning=570");
				exit();
			}
			
			// Lägg till en utlåning till titelns totala utlåningsstatistik.
			$LAN = unserialize($LAN);
			$LANTOTALT = $LAN[date("Y")];
			$LANTOTALT = $LANTOTALT + 1;
			$LAN[date("Y")] = $LANTOTALT;
			$LAN = serialize($LAN);
			
			// Lägg till en utlåning till titelns lokala utlåningsstatistik.
			$LANARRAY = unserialize($LANARRAY);
			$LANLOKALT = $LANARRAY[$_SESSION['Bibliotek'].date("Y")];
			$LANLOKALT = $LANLOKALT + 1;
			$LANARRAY[$_SESSION['Bibliotek'].date("Y")] = $LANLOKALT;
		
			$LANARRAY = serialize($LANARRAY);
			
			// Sätt ihop strängen för att skicka dem till databasen.
			$Skrivlan = "INSERT INTO aktiva (BOKID,TITELID,MEDLEM,DATUM,BIBLIOTEK) VALUES ('$BOKID','$TITELID','$Lantagaremedlem','$Serveridag','".$_SESSION['Bibliotek']."')";
			
            // Anpassa strängen om det är ett speciallån.
			if ($Typ == "Special") {

            	// Hämta information om kvällens tjänstgörande bibliotekarie.
            	$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";
        
                // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
                if (!$resultbesok = mysqli_query($db,$Hamtabibliotekarie)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
        
            	// Läs medlemsnummer från databasen medlem.
            	while ($row = mysqli_fetch_array($resultbesok)) {
                    $SANSVARIG	= $row["MEDLEM"];
                    $SANSVARIG   = substr($SANSVARIG,2);
                }
            
                $Skrivlan = "INSERT INTO special (SBOKID,STITELID,SMEDLEM,SDATUM,SBIBLIOTEK,SANSVARIG) VALUES ('$BOKID','$TITELID','$Lantagaremedlem','$Serveridag','".$_SESSION['Bibliotek']."','$SANSVARIG')";
            }
            
			// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Skrivlan)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// Uppdatera databasen litteratur om att en utlåning har skett.
			$Uppdateratitel = "UPDATE litteratur SET LAN='$LAN',LANARRAY='$LANARRAY' WHERE TITELID=$TITELID";
			
			// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Uppdateratitel)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// Om låntagarens epostadress finns registrerad så skicka ett automatiskt mail om lånet.
			if ($_SESSION['Lantagaremail'] <> "BLOCK") {
			
				// Läs data om det valda biblioteket.
				$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=".$_SESSION['Bibliotek']."";
					
				// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
				if (!$bibliotekresult = mysqli_query($db,$Visabibliotek)) {
					die('Invalid query: ' . mysqli_error($db));
				}
				while ($row = mysqli_fetch_array($bibliotekresult)) {
					$BIBLIOTEKNAMN	= utf8_decode($row["BIBLIOTEKNAMN"]);
				}
				
				$Mottagare = $_SESSION['Lantagaremail'];
				$Tillbakadatum = $Serveridag+2592000;
				$Rubrik = "Meddelande från Frimurarebiblioteket";
				$Extra = "From: no-reply@frimurarna.se"."\r\nX-Mailer: PHP/".phpversion()."\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n";
				$Meddelande = "Broder!<br />Du har idag den ".date('j/n Y',$Serveridag)." l&aring;nat boken $KOD \"".utf8_decode($TITEL)."\".<br />";
                if ($Typ <> "Special") $Meddelande = "$Meddelande<br />Boken ska vara tillbaka i biblioteket senast den ".date('j/n Y', $Tillbakadatum).".<br />";
                $Meddelande = "$Meddelande<br />Observera att du inte kan svara p&aring; detta mail.<br /><br />Biblioteket, $BIBLIOTEKNAMN";			
				mail($Mottagare, $Rubrik, $Meddelande, $Extra);			
			}
			
			// Hoppa tillbaka till lånesidan.
			header("location:lana.php?Typ=$Typ");
		}
	}

	// Avsluta HTML-taggarna och stäng sidan.
	echo "</div></body></html>";
?>