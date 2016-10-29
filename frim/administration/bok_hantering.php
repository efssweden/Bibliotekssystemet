<?php
    session_start();
    
    // Starta dokumentet.
    require("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";

    // Skriv ut bibliotekets exlibris.
    require("../subrutiner/top_exlibris.php");

		$Steg = $_GET['Steg'];
		$BIBLIOTEK = $_SESSION['Bibliotek'];
		

	// Scanna en boks streckkod för att kontrollera aktuell status.
	if ($Steg == "Kontroll") {
	
		// Skriv ut instruktioner.
		echo "<div class='Text'>Scanna av eller skriv in streckkoden p&aring; boken som ska kontrolleras eller tryck Tillbaka.<br /><br /></div>";

		// Öppna ett formulär för streckkoden.
		echo "<form action='bok_hantering.php?Steg=Scannad' name='kontrollera' method='post' enctype='application/form-data'>";
		
		// Skriv ut en inmatningsruta för streckkoden.
		echo "<input name='EAN' type='text' class='Text' size='50'><br />";
		
		// Skriv ut knappar för att låna och avsluta.
		echo "<input type='submit' value='Kontrollera' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"administrera.php\" ' class='Text' /></form><br />";

        // Skriv ut en hjälpknapp.
        $HELPID = 11;
        require ("../subrutiner/help.php");
		
		// Sätt fokus på inmatningsrutan så att markören alltid befinner sig där från början.
		echo "<script type='text/javascript'>document.kontrollera.EAN.focus()</script>";

	}

	// Generera resultat av kontrollscanning.
	if ($Steg == "Scannad") {
	
		$EAN			= $_POST["EAN"];

		// Kontrollera om streckkoden är numerisk och larma om så inte är fallet.
		if (!is_numeric($EAN)) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=1");
			exit();
		}

		// Kolla om boken tillhör det aktuella biblioteket.
		if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=2");
			exit();
		}
		
		// Förbered hämtningen av den utvalda boken från databasen bocker.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = $EAN";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Kontrollera om boken finns registrerad i biblioteket och varna om så inte är fallet.
		if (!mysqli_num_rows($result)) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=3");
			exit();
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
				header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=4");
				exit();
			}
            
            $Hamtaspeciallan = "SELECT * FROM special WHERE SBOKID = $BOKID";
			if (!$resultspeciallan = mysqli_query($db,$Hamtaspeciallan)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Visa en varning om boken redan är registrerad som specialutlånad.
			if (mysqli_num_rows($resultspeciallan)) {
				header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=6");
				exit();
			}

			// Hoppa tillbaka till administrationssidan.
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=5");			
		}
	}
	
	// Visa resultatet för den scannade streckkoden.
	if ($Steg == "Kontrollresultat") {
	
		$EAN		= $_GET['EAN'];
		$Meddelande = $_GET['Meddelande'];
	
		if ($Meddelande == 1) $Kontrollresultat = "Streckkoden &auml;r felaktig eller n&aring;got gick snett vid inscanningen.";
		if ($Meddelande == 2) $Kontrollresultat = "Streckkoden tillh&ouml;r inte det h&auml;r biblioteket.";
		if ($Meddelande == 3) $Kontrollresultat = "Streckkoden &auml;r inte registrerad.";
		if ($Meddelande == 4) $Kontrollresultat = "Boken &auml;r registrerad som utl&aring;nad.";
		if ($Meddelande == 5) $Kontrollresultat = "Boken &auml;r registrerad som i biblioteket.";
		if ($Meddelande == 6) $Kontrollresultat = "Boken &auml;r registrerad som specialutl&aring;nad.";
		if ($Meddelande == 7) $Kontrollresultat = "Boken &auml;r registrerad som utl&aring;nad. Avregistrera l&aring;net f&ouml;rst!";

		// Skriv ut rubriken.
		echo "<br /><div class='Rubrik'>$Kontrollresultat<br /></span><span class='Text'>Streckkod: $EAN<br /><br /></div><table cellpadding='5' align='center' border='1'>";

		if ($Meddelande == 4 || $Meddelande == 5 || $Meddelande == 6) {
		
			$Listabocker = "SELECT * FROM bocker WHERE EAN = $EAN";
			// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Listabocker)) {
				die('Invalid query: ' . mysqli_error($db));
				}
		
			// Läs data från respektive titel.
			while ($row = mysqli_fetch_array($result)) {
				$BOKID			= $row["BOKID"];
				$TITELID		= $row["TITELID"];
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
				$LAN			= $row["LAN"];
                $KATNR          = $row["KATNR"];

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
                    $KOD = "$KOD ";
                }
			}
            
            // Kolla om det finns någon kategori registrerad.
            if ($KATNR==0||$KATNR=="") {
                $Kategori = "";
            }
            else {
    			// Hämta titelns kategori och skriv ut den i klartext.
    			$Hamtakategori = "SELECT * FROM kategorier WHERE KATNR = '$KATNR'";
    
    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$kategoriresult = mysqli_query($db,$Hamtakategori)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
    
    			// Läs in titelns kategori från databasen kategorier.
    			while ($row = mysqli_fetch_array($kategoriresult)) {
    				$Kategori = utf8_encode($row["KATEGORI"]);
    			}
                
                $Kategori = "<br />Kategori: ".$Kategori;
            }
			
			// Skriv ut uppgifterna.
			echo "<tr><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b>$UTGIVNINGSAR<br />F&ouml;rfattare: $FORFATTARE$Kategori";
		
			if ($Meddelande == 4) {
			
				// Hämta information om lånet.
				$Kontrolleratidigare = "SELECT * FROM aktiva WHERE BOKID = $BOKID";

				// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
				if (!$result = mysqli_query($db,$Kontrolleratidigare)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// Läs data om utlånade böcker i databasen aktiva.
				while ($row = mysqli_fetch_array($result)) {
					$LANID			= $row["LANID"];
					$BOKID			= $row["BOKID"];
					$TITELID		= $row["TITELID"];
					$DATUM			= $row["DATUM"];
					$MEDLEM			= $row["MEDLEM"];
				}

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
					$Lantagaremail	= $row["MEDLEMMAIL"];
					}
				if ($Lantagaregrad == 1) $Grad = ", I";
				if ($Lantagaregrad == 2) $Grad = ", II";
				if ($Lantagaregrad == 3) $Grad = ", III";
				if ($Lantagaregrad == 4) $Grad = ", IV/V";
				if ($Lantagaregrad == 6) $Grad = ", VI";
				if ($Lantagaregrad == 7) $Grad = ", VII";
				if ($Lantagaregrad == 8) $Grad = ", VIII";
				if ($Lantagaregrad == 9) $Grad = ", IX";
				if ($Lantagaregrad == 10) $Grad = ", X";
				if ($Lantagaregrad == 11) $Grad = ", XI";
				
				// Kontrollera om låntagarens epostadress är registrerad.
				if ($Lantagaremail <> "") $Lantagaremail = " <a href='mailto:$Lantagaremail'>Epost</a>";
				else $Lantagaremail = " (".substr($Lantagaremedlem,0,4)."-".substr($Lantagaremedlem,4).")";

				// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
				$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $Lantagareloge";

				// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
				if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// Läs in logens namn från databasen arbetsenheter.
				while ($row = mysqli_fetch_array($logeresult)) {
					$Lantagareloge = $row["ENHETNAMN"];
				}

				$MEDLEM = "<b>$Lantagarenamn</b>$Grad i $Lantagareloge $Lantagaremail";
				
				// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
				$Tillbakadatum = $DATUM+2592000;
				echo "<br /><br />Utl&aring;nad ".date('Y-m-d', $DATUM)." till $MEDLEM.";
				if ($Tillbakadatum >= $Serveridag) echo "<br /><span class='green'>Ska vara tillbaka: <b>".date('Y-m-d', $Tillbakadatum)."</b>.</span>";
				else echo "<br /><span class='red'>Skulle varit tillbakal&auml;mnad: <b>".date('Y-m-d', $Tillbakadatum)."</b>.</span>";
                echo "</td></tr>";
			}
            
			if ($Meddelande == 6) {
			
				// Hämta information om lånet.
				$Kontrolleratidigare = "SELECT * FROM special WHERE SBOKID = $BOKID";

				// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
				if (!$result = mysqli_query($db,$Kontrolleratidigare)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// Läs data om utlånade böcker i databasen aktiva.
				while ($row = mysqli_fetch_array($result)) {
					$LANID			= $row["SLANID"];
					$BOKID			= $row["SBOKID"];
					$TITELID		= $row["STITELID"];
					$DATUM			= $row["SDATUM"];
					$MEDLEM			= $row["SMEDLEM"];
                    $ANSVARIG       = $row["SANSVARIG"];
				}

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
					$Lantagaremail	= $row["MEDLEMMAIL"];
					}
				if ($Lantagaregrad == 1) $Grad = ", I";
				if ($Lantagaregrad == 2) $Grad = ", II";
				if ($Lantagaregrad == 3) $Grad = ", III";
				if ($Lantagaregrad == 4) $Grad = ", IV/V";
				if ($Lantagaregrad == 6) $Grad = ", VI";
				if ($Lantagaregrad == 7) $Grad = ", VII";
				if ($Lantagaregrad == 8) $Grad = ", VIII";
				if ($Lantagaregrad == 9) $Grad = ", IX";
				if ($Lantagaregrad == 10) $Grad = ", X";
				if ($Lantagaregrad == 11) $Grad = ", XI";
				
				// Kontrollera om låntagarens epostadress är registrerad.
				if ($Lantagaremail <> "") $Lantagaremail = " <a href='mailto:$Lantagaremail'>Epost</a>";
				else $Lantagaremail = " (".substr($Lantagaremedlem,0,4)."-".substr($Lantagaremedlem,4).")";
                $MATRIKEL = "(".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." <a href='https://www.frimurarorden.se/medlemsmatrikel/' target='_Blank'>Matrikel</a>)";

				// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
				$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $Lantagareloge";

				// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
				if (!$logeresult = mysqli_query($db,$Hamtaloge)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// Läs in logens namn från databasen arbetsenheter.
				while ($row = mysqli_fetch_array($logeresult)) {
					$Lantagareloge = $row["ENHETNAMN"];
				}

				$MEDLEM = "<b>$Lantagarenamn</b>$Grad i $Lantagareloge $MATRIKEL";
                
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
                
				// Skriv ut informationen.
				echo "<br /><br />Utl&aring;nad ".date('Y-m-d', $DATUM)." till $MEDLEM<br />";
                echo "Ansvarig: $ANSVARIG</td></tr>";
			}            
            

			else echo "</tr>";
			echo "</table>";
		}
		echo "<br /><input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"administrera.php\" ' class='Text' />";
	}
	
	// Välj vilken bok som ska avregistreras.
	if ($Steg == "RedigeraBok") {

		// Skriv ut instruktioner.
        echo "<div class='Rubrik'>Redigera bok</div>";
        echo "<div class='Text'>Om du r&aring;kat registrera en bok med fel titel kan du avregistrera streckkoden h&auml;r, sedan registrerar du om boken med r&auml;tt titel.</div>";
		echo "<div class='Text'>Scanna av eller skriv in streckkoden p&aring; boken som ska avregistreras eller tryck Tillbaka.<br /><br /></div>";

		// Öppna ett formulär för streckkoden.
		echo "<form action='bok_hantering.php?Steg=RedigeraBok3' name='avregistrera' method='post' enctype='application/form-data'>";
		
		// Skriv ut en inmatningsruta för streckkoden.
		echo "<input name='EAN' type='text' class='Text' size='50'><br />";
		
		// Skriv ut knappar för att låna och avsluta.
		echo "<input type='submit' value='Avregistrera' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"administrera.php\" ' class='Text' /></form><br />";

        // Skriv ut en hjälpknapp.
        $HELPID = 15;
        require ("../subrutiner/help.php");
        		
		// Sätt fokus på inmatningsrutan så att markören alltid befinner sig där från början.
		echo "<script type='text/javascript'>document.avregistrera.EAN.focus()</script>";
        
	}

	// Verifiera att man verkligen vill avregistrera den valda boken.
	if ($Steg == "RedigeraBok3") {
 
		// Hämta titelns ID från föregående sida.
		$EAN = $_POST['EAN'];
        if (empty($EAN)) header("location:bok_hantering.php?Steg=RedigeraBok");
		
		// Hämta innehållet i databasen bocker för att visa den en sista gång.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = '$EAN'";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Läs data från boken.
		while ($row = mysqli_fetch_array($result)) {
            $BOKID          = $row["BOKID"];
			$TITELID		= $row["TITELID"];
		}

		// Läs titeln som är kopplad till boken.
		$Lastitel = "SELECT * FROM litteratur WHERE TITELID = $TITELID";
				
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$titelresult = mysqli_query($db,$Lastitel)) {
			die('Invalid query: ' . mysqli_error($db));
		}
				
		// Läs information från databasen litteratur om aktuell titel.
		while ($row = mysqli_fetch_array($titelresult)) {
			$KOD			= $row["KOD"];
			$GRAD			= $row["GRAD"];
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
		}
        
        // Kontrollera så att boken inte är utlånad.
        $Kollalan = "SELECT * FROM aktiva WHERE BOKID='$BOKID'";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$aktivaresult = mysqli_query($db,$Kollalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        if (mysqli_num_rows($aktivaresult)) $Utlanad = 1;
        
        $Kollalan = "SELECT * FROM special WHERE SBOKID='$BOKID'";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$specialresult = mysqli_query($db,$Kollalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        if (mysqli_num_rows($specialresult)) $Utlanad = 1;
        
        if ($Utlanad == 1) header("location:../varning.php?Varning=950");
        else {
            echo "<div class='Text'>Avregistrera boken $EAN som &auml;r kopplad till titeln $KOD<b>$TITEL</b>?</div>";
            echo "<br /><input type='button' value='Avregistrera' onclick='window.location = \"bok_hantering.php?Steg=AvregistreraBok2&amp;BOKID=$BOKID\" '  class='Text'/><input type='button' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";
        }
	}
	
	// Radera den utvalda och verifierade titeln.
	if ($Steg == "AvregistreraBok2") {
	
		// Hämta titelns ID från föregående sida.
		$BOKID = $_GET['BOKID'];
		
		// Sätt upp en slängsträng.
		$Raderaboken = "DELETE FROM bocker WHERE BOKID = '$BOKID'";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Raderaboken)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Hoppa tillbaka till administrationssidan.
		header("location:administrera.php");
		
	}

	// Redigera vald bok eller skapa en ny bok.
	if ($Steg == "RedigeraBok2") {
	
		$Sparasom = $_GET['Sparasom'];

		if ($Sparasom == "Redigera") {
			$BOKID = $_GET['BOKID'];
			// Förbered hämtningen av utvald bok.
			$Sparasombok = "SELECT * FROM bocker WHERE BOKID = $BOKID";
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$result = mysqli_query($db,$Sparasombok)) {
				die('Invalid query: ' . mysqli_error($db));
				} 
			// Läs data om utvald titel i databasen bocker.
			while ($row = mysqli_fetch_array($result)) {
				$BOKID			= $row["BOKID"];
				$TITELID		= $row["TITELID"];
				$EAN			= $row["EAN"];
				$DATUM			= $row["DATUM"];
				}
			}
		if ($Sparasom == "Redigera") {
			// Öppna ett formulär för att redigera vald bok.
			echo "<form action='bok_hantering.php?Steg=SparaBok&amp;BOKID=$BOKID&amp;Sparasom=Uppdatera' method='post' enctype='application/form-data'>";
			}
		if ($Sparasom <> "Redigera") {
			// Öppna ett formulär för att skapa en ny bok.
			echo "<form action='bok_hantering.php?Steg=SparaBok&Sparasom=Ny' method='post' enctype='application/form-data'>";
			}
			
		// Öppna en tabell och sätt upp en sträng med återkommande instruktioner för att spara plats.			
		echo "<table class='Text' align='center' border='1'>";
		$CELL = "<td valign='top' align='left'>";
		
		// Skriv ut raderna i tabellen.
		if ($Sparasom == "Redigera") {
			echo "<tr>".$CELL."ID-nummer:</td>$CELL$BOKID. (Senast redigerad ".date('Y.m.d', $DATUM).")<input type='hidden' name='BOKID' value='$BOKID'></td></tr>";
		
			// Hämta titeln från databasen litteratur.
			$Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = $TITELID";
			// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Hamtatitel)) {
				die('Invalid query: ' . mysqli_error($db));
				}
			
			// Läs data från respektive titel.
			while ($row = mysqli_fetch_array($result)) {
				$TITELID		= $row["TITELID"];
				$TITELIDCHECK	= $row["TITELID"];
				$FORFATTARE		= $row["FORFATTARE"];
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$KOD			= $row["KOD"];
				$TITEL			= $row["TITEL"];
				$GRAD			= $row["GRAD"];

				// Behandla data som behöver behandlas.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (strlen($TITEL) >= 60) $TITEL = substr($TITEL,0,60)."...";
				if ($GRAD == "0") $GRAD = "&Ouml;ppen";
				elseif ($GRAD == "1") $GRAD = "Grad I";
				elseif ($GRAD == "2") $GRAD = "Grad II";
				elseif ($GRAD == "3") $GRAD = "Grad III";
				elseif ($GRAD == "4") $GRAD = "Grad IV/V";
				elseif ($GRAD == "6") $GRAD = "Grad VI";
				elseif ($GRAD == "7") $GRAD = "Grad VII";
				elseif ($GRAD == "8") $GRAD = "Grad VIII";
				elseif ($GRAD == "9") $GRAD = "Grad IX";
				elseif ($GRAD == "10") $GRAD = "Grad X";		

				echo "<tr>".$CELL."Registrerad titel:&nbsp;</td>$CELL$KOD - <b>$TITEL</b> - $GRAD<br />F&ouml;rfattare: $FORFATTARE ($UTGIVNINGSAR)</td></tr>";		
				}
			}
			
		// Hämta titlar från databasen litteratur.
		$Listatitlar = "SELECT * FROM litteratur ORDER BY KOD, TITEL ASC";
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
			}
			
		if ($Sparasom == "Redigera") echo "<tr>".$CELL."Ny titel:</td>$CELL<select name='TITELIDSELECT' class='Text'>";		
		else echo "<tr>".$CELL."Titel:</td>$CELL<select name='TITELIDSELECT' class='Text'>";		
		// Läs data från respektive titel.
		while ($row = mysqli_fetch_array($result)) {
			$TITELID		= $row["TITELID"];
			$KOD			= $row["KOD"];
			$TITEL			= $row["TITEL"];
			$GRAD			= $row["GRAD"];

			// Behandla data som behöver behandlas.
			if (empty($TITEL)) $TITEL = "**Titel saknas**";
			if (strlen($TITEL) >= 60) $TITEL = substr($TITEL,0,60)."...";
			if ($GRAD == "0") $GRAD = "&Ouml;ppen";
			elseif ($GRAD == "1") $GRAD = "Grad I";
			elseif ($GRAD == "2") $GRAD = "Grad II";
			elseif ($GRAD == "3") $GRAD = "Grad III";
			elseif ($GRAD == "4") $GRAD = "Grad IV/V";
			elseif ($GRAD == "6") $GRAD = "Grad VI";
			elseif ($GRAD == "7") $GRAD = "Grad VII";
			elseif ($GRAD == "8") $GRAD = "Grad VIII";
			elseif ($GRAD == "9") $GRAD = "Grad IX";
			elseif ($GRAD == "10") $GRAD = "Grad X";		
			
			if (!empty($KOD)) $KOD = "$KOD - ";
			
			if ($TITELID == $TITELIDCHECK) echo "<option selected='selected' value='$TITELID'>$KOD$GRAD - $TITEL</option>";
			else echo "<option value='$TITELID'>$KOD$GRAD - $TITEL</option>";
			}
		echo "</td></tr>";
		
		if ($Sparasom <> "Redigera") echo "<tr>".$CELL."Streckkod:</td>$CELL<input name='EAN' type='Text' class='Text' size='135' ></td></tr>";
		elseif ($Sparasom == "Redigera") {
			echo "<input name='EAN' type='Hidden' value='$EAN' >";
			echo "<tr>".$CELL."Streckkod:</td>$CELL<img src='http://barcode.tec-it.com/barcode.ashx?code=Code93&modulewidth=fit&data=$EAN&dpi=96&imagetype=png&rotation=0' alt='Barcode generated by TEC-IT'/></td></tr>";
		}
		
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td>$CELL<input type='submit' class='Text' value='Spara'><input type='button' name='Cancel' class='Text' value='Tillbaka' onclick='window.location = \"administrera.php\" ' /></td></tr></table></form>";
	}	
	
	// Välj vilken grad en ny bok ska hamna i.
	if ($Steg == "Nybok") {

		// Kontrollera så att det finns titlar att koppla till böckerna.
		$Raknatitlar	= "SELECT * FROM litteratur";
		$Titlarresult	= mysqli_query($db,$Raknatitlar);

		if (!mysqli_num_rows($Titlarresult)) {
			echo "<div class='Rubrik'>Det finns inga titlar registrerade!</div>";
			echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button>";
			exit();
		}
		
		// Öppna ett formulär för att välja grad.
		echo "<form action='bok_hantering.php?Steg=Nybokvald' name='Titel' method='post' enctype='application/form-data'>";

		// Öppna en tabell och sätt upp en sträng med återkommande instruktioner för att spara plats.			
		echo "<table align='center' class='Text' border='1' cellpadding='5' >";
		$CELL = "<td valign='top' align='left'>";	

		// Skriv ut respektive rad i listan.
		echo "<tr>".$CELL."Grad:</td>$CELL<select name='GRADSELECT' class='Text'>";		
		echo "<option value='0'>&Ouml;ppen</option>";
		echo "<option value='1'>Grad I</option>";
		echo "<option value='2'>Grad II</option>";
		echo "<option value='3'>Grad III</option>";
		echo "<option value='4'>Grad IV/V</option>";
		echo "<option value='6'>Grad VI</option>";
		echo "<option value='7'>Grad VII</option>";
		echo "<option value='8'>Grad VIII</option>";
		echo "<option value='9'>Grad IX</option>";
		echo "<option value='10'>Grad X</option>";
		echo "</td></tr>";
		
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td>$CELL<input type='submit' value='V&auml;lj grad' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" ' class='Text' /></td></tr></table></form>";	
		
	}

	// Lägg till en ny bok.
	if ($Steg == "Nybokvald") {
	
		// Hämta upp vald grad.
		$Grad = $_POST['GRADSELECT'];
#		$Grad = 4;

		// Kontrollera så att det finns titlar i vald grad att koppla till böckerna.
		$Raknatitlar	= "SELECT * FROM litteratur WHERE GRAD = $Grad";
		$Titlarresult	= mysqli_query($db,$Raknatitlar);

		if (!mysqli_num_rows($Titlarresult)) {
			echo "<div class='Rubrik'>Det finns inga titlar registrerade i denna grad!</div>";
			echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button>";
			exit();
		}
		
		// Öppna ett formulär för att skapa en ny bok.
		echo "<form action='bok_hantering.php?Steg=Nybok2' name='Titel' method='post' enctype='application/form-data'>";
			
		// Öppna en tabell och sätt upp en sträng med återkommande instruktioner för att spara plats.			
		echo "<table align='center' class='Text' border='1'>";
		$CELL = "<td valign='top' align='left'>";	
	
		// Hämta titlar från databasen litteratur.
		$Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Grad ORDER BY KOD ASC";
		
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
			}
		
		// Skriv ut respektive rad i listan.
		echo "<tr>".$CELL."Titel:</td>$CELL<select name='TITELIDSELECT' class='Text'>";		
		
		// Läs data från respektive titel.
		while ($row = mysqli_fetch_array($result)) {
			$TITELID		= $row["TITELID"];
			$KOD			= $row["KOD"];
			$TITEL			= $row["TITEL"];
			$GRAD			= $row["GRAD"];

			// Behandla data som behöver behandlas.
			if (empty($TITEL)) $TITEL = "**Titel saknas**";
			if (strlen($TITEL) >= 40) $TITEL = substr($TITEL,0,40)."...";
			if ($GRAD == "0") $GRAD = "&Ouml;ppen";
			elseif ($GRAD == "1") $GRAD = "Grad I";
			elseif ($GRAD == "2") $GRAD = "Grad II";
			elseif ($GRAD == "3") $GRAD = "Grad III";
			elseif ($GRAD == "4") $GRAD = "Grad IV/V";
			elseif ($GRAD == "6") $GRAD = "Grad VI";
			elseif ($GRAD == "7") $GRAD = "Grad VII";
			elseif ($GRAD == "8") $GRAD = "Grad VIII";
			elseif ($GRAD == "9") $GRAD = "Grad IX";
			elseif ($GRAD == "10") $GRAD = "Grad X";		
			
			echo "<option value='$TITELID'>$KOD - $GRAD - $TITEL</option>";
			}
		echo "</td></tr>";
		
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td>$CELL<input type='submit' value='V&auml;lj titel' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" ' class='Text' /></td></tr></table></form>";	
	}
	
	// Lägg till en streckkod till den nya boken.
	if ($Steg == "Nybok2") {
	
		if (isset($_GET['TITELIDSELECT'])) $TITELIDSELECT = $_GET['TITELIDSELECT'];
		else $TITELIDSELECT = $_POST['TITELIDSELECT'];
			
		// Öppna ett formulär för att lägga till streckkoden och skapa en ny bok.
		echo "<form action='bok_hantering.php?Steg=SparaBok&Sparasom=Ny' name='administrera' method='post' enctype='application/form-data'>";
			
		// Öppna en tabell och sätt upp en sträng med återkommande instruktioner för att spara plats.			
		echo "<table align='center' class='Text' border='1'>";
		$CELL = "<td valign='top' align='left' class='Text'>";

		// Hämta titeln från databasen litteratur.
		$Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = $TITELIDSELECT";
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtatitel)) {
			die('Invalid query: ' . mysqli_error($db));
		}
			
		// Läs data från respektive titel.
		while ($row = mysqli_fetch_array($result)) {
			$TITELID		= $row["TITELID"];
			$KOD			= $row["KOD"];
			$TITEL			= $row["TITEL"];
			$GRAD			= $row["GRAD"];
			$FORFATTARE		= $row["FORFATTARE"];

			// Behandla data som behöver behandlas.
			if (empty($TITEL)) $TITEL = "**Titel saknas**";
            if (!empty($KOD)) {
                $KOD = str_replace(":00",":",$KOD);
                $KOD = str_replace(":0",":",$KOD);
                $KOD = str_replace(" 00"," ",$KOD);
                $KOD = str_replace(" 0"," ",$KOD);
            }
            if (!empty($KOD)) $KOD = "$KOD ";

		}
			
		// Skriv ut information om titeln.
		echo "<div class='Rubrik'>L&auml;gg till ny bok</div><br />";
		echo "<div class='Text'>Registrera streckkoden f&ouml;r boken $KOD&ldquo;<b>$TITEL</b>&rdquo; av $FORFATTARE:</div><br />";

		// Skriv ut streckkodsrutan.
		echo "<tr>".$CELL."Streckkod:</td>$CELL<input name='EAN' type='Text' class='Text' size='100' ></td></tr>";
		echo "<input name='TITELIDSELECT' type='hidden' value='$TITELIDSELECT'>";
		
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td>$CELL<input type='submit' value='Spara' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" ' class='Text'/></td></tr></table></form>";
		echo "<script type='text/javascript'>document.administrera.EAN.focus()</script>";
	}
	
	// Spara den nya eller uppdaterade boken i databasen bocker.
	if ($Steg == "SparaBok") {

		// Läs in de respektive strängarnas värde från föregående sida.
		if (isset($_POST["BOKID"])) $BOKID= $_POST["BOKID"];			// Bokens unika ID-nummer.
		$TITELID		= $_POST["TITELIDSELECT"];						// Titeln som boken pekar på.
		$EAN			= $_POST["EAN"];								// Bokens streckkod i Code-93.
		$DATUM			= mktime(0,0,0,$Servermanad,$Serverdag,$Serverar);

		// Kontrollera om streckkoden verkar blivit felaktigt inscannad - dvs den är för kort eller för lång.
		if (strlen($EAN) <> 10) {
			header("location: ../varning.php?Varning=9b0");
			exit();
		}

		// Kontrollera om streckkoden tillhör det här biblioteket och avbryt om så inte är fallet.
		if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) {
			header("location: ../varning.php?Varning=9c0");
			exit();
		}
		
		$Sparasom = $_GET['Sparasom'];
		
		// Kontrollera om det är en befintlig bok som ska redigeras och gör det i så fall.
		if ($Sparasom == "Uppdatera") {
			// Sätt ihop strängarna till en uppdateringssträng för databasen.
			$Skrivbok = "UPDATE bocker SET TITELID='$TITELID', DATUM='$DATUM' WHERE BOKID='$BOKID'";

			// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Skrivbok)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Hoppa tillbaka till administrationssidan.
			header("location:administrera.php");			
		}

		// Kontrollera om det är en ny bok som ska läggas till och gör det i så fall.
		elseif ($Sparasom == "Ny") {
			
			// Kontrollera så att streckkoden inte redan finns registrerad.
			$Kollastreck = "SELECT * FROM bocker WHERE EAN = $EAN";

			// Skicka strängen till databasen, stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Kollastreck)) {
				die('Invalid query: ' . mysqli_error($db));
			}
				
			// Varna om streckkoden redan finns registrerad i systemet.
			if (mysqli_num_rows($result)) {
				header("location:../varning.php?Varning=9d0");
				exit();
			}

			// Om streckkoden är oanvänd så fortsätt, annars varna!
			if (!mysqli_num_rows($result)) {

				// Sätt ihop strängarna för att skicka dem till databasen.
				$Skrivbok = "INSERT INTO bocker (TITELID,EAN,DATUM) VALUES ('$TITELID','$EAN','$DATUM')";

				// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
				if (!$result = mysqli_query($db,$Skrivbok)) {
					die('Invalid query: ' . mysqli_error($db));
				}			
			
				// Visa bekräftelse på att boken är registrerad.
				header("location:../varning.php?Varning=9e1");
			}
		}
	}
	
	// Lista alla böcker i bocker-databasen, sorterade efter streckkod.
	if ($Steg == "Listabocker") {
		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button><br /><br />";
		
        // Lista böckerna sorterade efter streckkoden.
		$Listabocker = "SELECT * FROM bocker WHERE EAN LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN ASC";
        
        // Om man kommer från bibliotekslistan så sätt upp en ny databassträng.
        if (isset($_GET['Bib'])) $Listabocker = "SELECT * FROM bocker WHERE EAN LIKE '".$_GET['Bib']."%' ORDER BY EAN ASC";
        
        // Sätt upp rubriker och sånt.
        $Rubrik = "<div class='Rubrik'>Registrerade b&ouml;cker i detta bibliotek:</div>";
        if (isset($_GET['Bib'])) {
            $Listabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR = '".$_GET['Bib']."'";

    		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    		if (!$resultbib = mysqli_query($db,$Listabibliotek)) {
    			die('Invalid query: ' . mysqli_error($db));
    		} 

    		// Läs information från databasen bibliotek.
    		while ($row = mysqli_fetch_array($resultbib)) {
    			$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];
            }
            $Rubrik = "<div class='Rubrik'>Registrerade b&ouml;cker hos $BIBLIOTEKNAMN:</div>";
        }

		echo $Rubrik;
		echo "<table border='1' align='center' width='70%'>";
        echo "<tr bgcolor='#F1F1F1'><td valign='top' align='center' class='Text' >Enhets-ID:</td><td valign='top' align='center' class='Text' width='100'>Reg. datum:</td><td valign='top' align='center' class='Text' >Grad:</td><td valign='top' align='left' class='Text'>Titel & f&ouml;rfattare:</td></tr>";
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listabocker)) {
			die('Invalid query: ' . mysqli_error($db));
		} 

		// Läs information från databasen bocker.
		while ($row = mysqli_fetch_array($result)) {
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
			$EAN			= $row["EAN"];
			$DATUM			= $row["DATUM"];

			// Läs titeln som är kopplad till boken.
			$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $TITELID";
				
			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
			if (!$titelresult = mysqli_query($db,$Listatitlar)) {
				die('Invalid query: ' . mysqli_error($db));
			} 
			
			// Läs information från databasen litteratur om aktuell titel.
			while ($row = mysqli_fetch_array($titelresult)) {
				$TITELID		= $row["TITELID"];
				$KOD			= $row["KOD"];
				$TITEL			= $row["TITEL"];
				$FORFATTARE		= $row["FORFATTARE"];
				$GRAD			= $row["GRAD"];
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];
					
				// Kontrollera om data saknas och hantera det i så fall.
				if (empty($TITEL)) $TITEL = "<span class='red'>**Titel saknas**</span>";
				if (empty($FORFATTARE)) $FORFATTARE = "<span class='red'>**F&ouml;rfattare saknas**</span>";
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

				// Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
				echo "<tr><td valign='center' align='center' class='Text' >*$EAN*</td><td valign='center' align='center' class='Text' >".date('Y-m-d',$DATUM)."</td><td valign='center' align='center' class='Text' >$GRAD</td><td valign='top' align='left' class='Text'>$KOD <b>&ldquo;$TITEL&rdquo;</b>$UTGIVNINGSAR<br />F&ouml;rfattare: $FORFATTARE</td></tr>";
			}
		}
		// Stäng tabellen.
		echo "</table><br />";
		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button><br /><br />";
	}
	
	// Radera en bok från databasen.
	if ($Steg == "Radera") {
	
		// Skriv ut en rubrik.
		echo "<div class='Rubrik'>Makulera bok</div>";

		// Skriv ut instruktioner.
		echo "<div class='Text'>Scanna av eller skriv in streckkoden p&aring; boken som ska makuleras eller tryck Tillbaka.<br /><br /></div>";

		// Öppna ett formulär för streckkoden.
		echo "<form action='bok_hantering.php?Steg=Makulera' name='lana' method='post' enctype='application/form-data'>";
		
		// Skriv ut en inmatningsruta för streckkoden.
		echo "<input name='EANmakulera' type='text' class='Text' size='50'><br />";
		
		// Skriv ut knappar för att låna och avsluta.
		echo "<input type='submit' value='Makulera' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"administrera.php\" ' class='Text' /></form><br />";

        // Skriv ut en hjälpknapp.
        $HELPID = 16;
        require ("../subrutiner/help.php");
        		
		// Sätt fokus på inmatningsrutan så att markören alltid befinner sig där från början.
		echo "<script type='text/javascript'>document.lana.EANmakulera.focus()</script>";
	
	}
	
	// Verifiera makuleringen.
	if ($Steg == "Makulera") {
	
		$EAN			= $_POST["EANmakulera"];

		// Kontrollera om streckkoden är numerisk och larma om så inte är fallet.
		if (!is_numeric($EAN)) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=1");
			exit();
		}

		// Kolla om boken tillhör det aktuella biblioteket.
		if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=2");
			exit();
		}
		
		// Förbered hämtningen av den utvalda boken från databasen bocker.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = $EAN";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Kontrollera om boken finns registrerad i biblioteket och varna om så inte är fallet.
		if (!mysqli_num_rows($result)) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=3");
			exit();
		}	
		
		// Läs data om utvald titel i databasen bocker.
		while ($row = mysqli_fetch_array($result)) {
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
		}
        
        // Kontrollera om boken är utlånad.
        $Hamtaaktiv = "SELECT * FROM aktiva WHERE BOKID = $BOKID";
        
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$aktivresult = mysqli_query($db,$Hamtaaktiv)) {
			die('Invalid query: ' . mysqli_error($db));
		}
				
        if (mysqli_num_rows($aktivresult)) {
			header("location:bok_hantering.php?Steg=Kontrollresultat&EAN=$EAN&Meddelande=7");
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
			$TITEL			= $row["TITEL"];

			// Kontrollera om data saknas och hantera det i så fall.
			if (!empty($KOD)) $KOD = "$KOD - ";
		}

		// Skriv ut en förfrågan om boken verkligen ska raderas.
		echo "<div class='Text'>Makulera boken <b>$TITEL</b> ($BOKID med streckkod $EAN)?</div>";
		echo "<br /><input type='button' value='Makulera' onclick='window.location = \"bok_hantering.php?Steg=Makulera2&amp;BOKID=$BOKID\" '  class='Text'/><input type='button' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";		
	}
	
	// Radera den utvalda och verifierade titeln.
	if ($Steg == "Makulera2") {
	
		// Hämta titelns ID från föregående sida.
		$BOKID = $_GET['BOKID'];
        
        // Hämta information om boken och titeln för att spara i listan över makulerade böcker.
        $Hamtabok = "SELECT * FROM bocker WHERE BOKID = $BOKID";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
        // Läs data om utvald titel i databasen bocker.
		while ($row = mysqli_fetch_array($result)) {
			$TITELID		= $row["TITELID"];
			$EAN     		= $row["EAN"];
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
		}

        // Sätt ihop en sträng till databasen makulerade och spara den i databasen.
        $MAKTITEL   = $TITELID;
        $MAKEAN     = $EAN;
        $MAKDATUM   = $Serveridag;
        
        // Sätt ihop strängarna för att skicka dem till databasen.
		$Makulerabok = "INSERT INTO makulerade (MAKTITEL,MAKEAN,MAKDATUM) VALUES ('$MAKTITEL','$MAKEAN','$MAKDATUM')";

		// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Makulerabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}			
			
		// Sätt upp en slängsträng.
		$Raderaboken = "DELETE FROM bocker WHERE BOKID = '$BOKID'";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Raderaboken)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Visa bekräftelse på att boken är makulerad.
		header("location:../varning.php?Varning=9f1");

	}
?>
</div>
</body>
</html>