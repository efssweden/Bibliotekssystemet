<?php
session_start();

    // Starta dokumentets sidhuvud.
    require("subrutiner/head.php");

	// Starta <body> och <div>.
	echo "<body><div id='ALLT'>";
    
    // Skriv ut logon p� sidan.
    require("subrutiner/top_exlibris.php");

    // Kolla upp vad det �r f�r typ av l�n som ska g�ras.
    if (!isset($_GET['Typ'])) $Typ = "Vanligt";
    else $Typ = $_GET['Typ'];

    // "Vanligt" = vanligt medlemsl�n. "Reception" = receptionsl�n. "Special" = speciall�n.

    // Om man l�nar vanligt eller nyrecipierad - kolla om det finns n�gra speciall�n.
    if ($Typ == "Vanligt" || $Typ == "Reception") {
        
    	// F�rbered kontrollen av antalet speciall�nade b�cker hos l�ntagaren.
    	$Raknaspecial = "SELECT * FROM special WHERE SMEDLEM = ".$_SESSION['Lantagaremedlem']."";
    
    	// Skicka beg�ran till servern och stoppa om det blir n�got fel.
    	if (!$resultspecial = mysqli_query($db,$Raknaspecial)) {
    		die('Invalid query: ' . mysqli_error($db));
    	}
    	
    	// R�kna efter hur m�nga speciall�n som finns registrerade.
    	$Antalspecial = mysqli_num_rows($resultspecial);
    	
        // Skriv ut en rad med information om det finns registrerade speciall�n.
        if ($Antalspecial >= 1){
            $Registreradelan = "<br /><table cellpadding='5' width='400' bgcolor='yellow' align='center' border='1' class='Rubrik'><tr><td><a href='lana.php?Typ=Special'>Visa $Antalspecial speciall&aring;n</a></td></tr></table>";
        }
    }
    
    // Om man speciall�nar - kolla om det finns n�gra vanliga l�n registrerade.
    if ($Typ == "Special") {
        
    	// F�rbered kontrollen av antalet l�nade b�cker hos l�ntagaren.
    	$Raknalan = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";
    
    	// Skicka beg�ran till servern och stoppa om det blir n�got fel.
    	if (!$resultlan = mysqli_query($db,$Raknalan)) {
    		die('Invalid query: ' . mysqli_error($db));
    	}
    	
    	// R�kna efter hur m�nga l�n som finns registrerade.
    	$Antallan = mysqli_num_rows($resultlan);
    	
        // Skriv ut en rad med information om det finns registrerade l�n.
        if ($Antallan >= 1){
            $Registreradelan = "<br /><table cellpadding='5' width='400' bgcolor='yellow' align='center' border='1' class='Rubrik'><tr><td><a href='lana.php'>Visa $Antallan vanliga l&aring;n</a></td></tr></table>";
        }
    }
    
    
    
	// Kontrollera f�rst om l�ntagaren har registrerade l�n.
	if ($Typ <> "Special") $Kontrolleratidigare = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']." ORDER BY DATUM ASC";
	if ($Typ == "Special") $Kontrolleratidigare = "SELECT * FROM special WHERE SMEDLEM = ".$_SESSION['Lantagaremedlem']." ORDER BY SDATUM ASC";

	// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
	if (!$result = mysqli_query($db,$Kontrolleratidigare)) {
		die('Invalid query: ' . mysqli_error($db));
	}

	// Spara uppgifter om eventuella l�n och spara den uppgiften i en str�ng.
	if (!mysqli_num_rows($result)) $Registreradelan = "$Registreradelan<br /><div class='Rubrik'>Inga registrerade l&aring;n.</div>";
	else {
		
		// Skapa en tabell i en str�ng �ver registrerade utl�n.
		if ($Typ <> "Special") $Registreradelan = "$Registreradelan<br /><div class='Rubrik'>Registrerade l&aring;n:</div><table cellpadding='5' align='center' border='1'>";
		if ($Typ == "Special") $Registreradelan = "$Registreradelan<br /><div class='Rubrik'>Registrerade speciall&aring;n:</div><table cellpadding='5' align='center' border='1'>";
        if ($Typ <> "Special") $Registreradelan = $Registreradelan."<tr bgcolor='#f1f1f1'><td align='center' valign='top' class='Text'>Enhets-ID:</td><td align='left' valign='top' class='Text'>Titel och f&ouml;rfattare:</td></tr>";
        if ($Typ == "Special") $Registreradelan = $Registreradelan."<tr bgcolor='#f1f1f1'><td align='center' valign='top' class='Text'>Enhets-ID:</td><td align='left' valign='top' class='Text'>Titel och f&ouml;rfattare:</td><td align='center' valign='top' class='Text'>Bibliotek:</td></tr>";

		// L�s data om utl�nade b�cker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {

            // H�mta information om vanliga l�n.
            if ($Typ <> "Special") {
    			$LANID			= $row["LANID"];
    			$BOKID			= $row["BOKID"];
    			$TITELID		= $row["TITELID"];
    			$DATUM			= $row["DATUM"];
            }
            
            // H�mta information om speciall�n.
            if ($Typ == "Special") {
    			$LANID			= $row["SLANID"];
    			$BOKID			= $row["SBOKID"];
    			$TITELID		= $row["STITELID"];
    			$DATUM			= $row["SDATUM"];
    			$BIBLIOTEK		= $row["SBIBLIOTEK"];
    			$SANSVARIG		= $row["SANSVARIG"];

                // L�s data om det utl�nande biblioteket.
                $Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEK";
        			
                // Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r galet.
                if (!$resultbibliotek = mysqli_query($db,$Visabibliotek)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
            	while ($row = mysqli_fetch_array($resultbibliotek)) {
            		$BIBLIOTEK	= $row["BIBLIOTEKNAMN"];
        	    }
    
                // L�gg till ett bindestreck i ansvarig bibliotekaries medlemsnummer.
                $ANSVARIG = substr($SANSVARIG,0,4)."-".substr($SANSVARIG,4);
			}
            
            // L�s streckkoden f�r den aktuella boken.
            $Visaean = "SELECT * FROM bocker WHERE BOKID=$BOKID";
        			
            // Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r galet.
            if (!$resultean = mysqli_query($db,$Visaean)) {
                die('Invalid query: ' . mysqli_error($db));
            }
                
            while ($row = mysqli_fetch_array($resultean)) {
            	$EAN	= $row["EAN"];
        	}
            
            // Kontrollera om den utl�nade boken tillh�r det aktuella biblioteket.
            if (substr($EAN,0,4) == $_SERVER["PHP_AUTH_USER"]) {
                $Vilketbibliotek = ".";
            }
            else {
                
                // L�s data om det utl�nande biblioteket.
                $Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=".substr($EAN,0,4)."";
        			
                // Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r galet.
                if (!$resultbibliotek = mysqli_query($db,$Visabibliotek)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
                
            	while ($row = mysqli_fetch_array($resultbibliotek)) {
            		$BIBLIOTEK	= $row["BIBLIOTEKNAMN"];
        	    }
                $Vilketbibliotek = " i $BIBLIOTEK.";                
            }
			
            // L�s titeln som �r kopplad till boken.
			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";

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
                        
                // R�kna ut hur m�nga dagar brodern haft boken.
                if ($Typ == "Special") {
                    $Antaldagar = round(($Serveridag-$DATUM)/86400);
                    if ($Antaldagar == 1) $Antaldagar = " <span class='green'>$Antaldagar dag sedan.</span>";
                    elseif ($Antaldagar >= 2) $Antaldagar = " <span class='green'>$Antaldagar dagar sedan.</span>";
                    else $Antaldagar = " <span class='green'>L&aring;nades ut idag.</span>";
                }
				
				// Spara uppgiften/uppgifterna i en str�ng.
				$Registreradelan = "$Registreradelan<tr><td align='center' valign='top' class='Text'>*$EAN*</td><td align='left' valign='top' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b>$UTGIVNINGSAR<br />F&ouml;rfattare: $FORFATTARE<br />Utl&aring;nad: ".date('Y-m-d',$DATUM)."$Vilketbibliotek";

                // L�gg till mer information om det �r ett speciall�n.
				if ($Typ == "Special") $Registreradelan = $Registreradelan."$Antaldagar</td><td align='center' valign='top' class='Text'>$BIBLIOTEK<br />$ANSVARIG</td></tr>";
		
				// Kontrollera �terl�mningsdatum f�r boken = utl�mningsdag + 30 dagar (2592000 sekunder) om det inte �r ett speciall�n.
                if ($Typ <> "Special") {
    				$Tillbakadatum = $DATUM+2592000;
    				if ($Tillbakadatum >= $Serveridag) $Registreradelan = $Registreradelan." <span class='green'>Ska vara tillbakal&auml;mnad: <b>".date('Y-m-d', $Tillbakadatum)."</b>.</span></td></tr>";
    				else $Registreradelan = $Registreradelan." <span class='red'><b>Skulle varit tillbakal&auml;mnad: ".date('Y-m-d', $Tillbakadatum)."</b>.</span></td></tr>";
                }
            }
		}
		
		// St�ng tabellen i str�ngen.
		$Registreradelan = "$Registreradelan</table>";
	}	
	
	// Kontrollera om l�ntagarens epostadress finns registrerad.
	// if ($_SESSION['Lantagaremail'] <> "") $Mail = "<img src='design/Email.png' title='Epostadress finns registrerad.' align='bottom' />";
	// else $Mail = "";
    
    $Mail = "";
	
	// Skriv ut namn, grad och logetillh�righet f�r l�ntagaren, eventuella l�n samt instruktioner.
	echo "<div class='Text'>L&aring;ntagare: <b>".$_SESSION['Lantagarenamn']."</b>".$_SESSION['Grad'].", ".$_SESSION['Lantagareloge'].". $Mail<br />".$Registreradelan."<br /></div>";

	// F�rbered kontrollen av antalet redan l�nade b�cker hos l�ntagaren.
	$Raknalan = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";

	// Skicka beg�ran till servern och stoppa om det blir n�got fel.
	if (!$resultrakna = mysqli_query($db,$Raknalan)) {
		die('Invalid query: ' . mysqli_error($db));
	}
	
	// R�kna efter hur m�nga l�n som finns registrerade.
	$Antallan = mysqli_num_rows($resultrakna);

# BORTPLOCKAD RUTIN F�R ATT BEGR�NSA ANTALET TILL�TNA L�N 2013-08-20	
	// Om antalet �r tre och det inte �r ett speciall�n s� visa inte inmatningsrutan l�ngre.
#	if ($Typ <> "Special" && $Antallan == 3) {
#		echo "<div class='Text'><b>Du har l&aring;nat maximalt till&aring;tet antal b&ouml;cker.</b></div><br />";
#		echo "<input type='button' name='Kvitto' value='L&aring;nelapp' onclick='window.location.href = \"administration/rapport_hantering.php?Steg=Kvitto\" ' class='Text' />";
#		echo "<input type='button' name='Cancel' value='Avsluta' onclick='window.location.href = \"fm.php\" ' class='Text' /><br />";

        // Skriv ut en hj�lpknapp.
#        $HELPID = 7;
#        if (!isset($_POST['EAN'])) require ("subrutiner/help.php");
#	}
#

	// Om antalet l�nade b�cker �r f�rre �n tre s� visa inmatningsrutan.
#	if ($Typ == "Special" || $Antallan <= 2) {
	if ($Typ == "Special" || $Antallan <= 9999) {
	
		// Skriv ut instruktioner.
		echo "<div class='Text'>Scanna av eller skriv in streckkoden p&aring; boken som ska l&aring;nas eller tryck Avsluta.<br /><br /></div>";
		
		// �ppna ett formul�r f�r streckkoden.
		echo "<form action='lana.php?Steg=Scannad&Typ=$Typ' name='lana' method='post' enctype='application/form-data'>";
		
		// Skriv ut en inmatningsruta f�r streckkoden.
		echo "<input name='EAN' type='text' class='Text' size='50'><br />";
		
		// Skriv ut knappar f�r att l�na och avsluta.
		echo "<input type='submit' value='L&aring;na' class='Text' ><input type='button' name='Kvitto' value='L&aring;nelapp' onclick='window.location.href = \"administration/rapport_hantering.php?Steg=Kvitto\" ' class='Text' /><input type='button' name='Cancel' value='Avsluta' onclick='window.location.href = \"fm.php\" ' class='Text' /></form><br />";
		
        // Skriv ut en hj�lpknapp.
        if ($Typ <> "Special") $HELPID = 7;
        if ($Typ == "Special") $HELPID = 8;
        if (!isset($_POST['EAN'])) require ("subrutiner/help.php");

		// S�tt fokus p� inmatningsrutan s� att mark�ren alltid befinner sig d�r fr�n b�rjan.
		echo "<script type='text/javascript'>document.lana.EAN.focus()</script>";
	}

	// Kontrollera om streckkoden �r ifylld och spara i s� fall uppgifterna om l�net.
	if (isset($_POST['EAN'])) {

        $Typ            = $_GET['Typ'];
		$EAN			= $_POST["EAN"];
		$Lantagarenamn	= $_SESSION['Lantagarenamn'];
		$Lantagaregrad	= $_SESSION['Lantagaregrad'];
		$Lantagareloge	= $_SESSION['Lantagareloge'];
		$Lantagaremail	= $_SESSION['Lantagaremail'];
		$Lantagaremedlem= $_SESSION['Lantagaremedlem'];
		$Grad			= $_SESSION['Grad'];
		
		// Kontrollera om streckkoden �r numerisk och larma om s� inte �r fallet.
		if (!is_numeric($EAN)) {
			header("location:varning.php?Varning=530");
            exit ();
		}

		// Kolla om boken tillh�r det aktuella biblioteket.
		if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) {
            header("location:varning.php?Varning=580");
            exit();
        }
	
		// F�rbered h�mtningen av den utvalda boken fr�n databasen bocker.
		$Hamtabok = "SELECT * FROM bocker WHERE EAN = $EAN";

		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Hamtabok)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Kontrollera om boken tillh�r biblioteket och varna om s� inte �r fallet.
		if (!mysqli_num_rows($result)) {
			header("location:varning.php?Varning=580");
            exit ();
		}
		else {

			// L�s data om utvald titel i databasen bocker.
			while ($row = mysqli_fetch_array($result)) {
				$BOKID			= $row["BOKID"];
				$TITELID		= $row["TITELID"];
				$EAN			= $row["EAN"];
			}

			// Kontrollera om boken redan �r utl�nad.
			$Hamtalan = "SELECT * FROM aktiva WHERE BOKID = $BOKID";
			if (!$resultlan = mysqli_query($db,$Hamtalan)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Visa en varning om boken redan �r registrerad som utl�nad.
			if (mysqli_num_rows($resultlan)) {
				header("location:varning.php?Varning=550");
				exit();
			}
			
			// Kontrollera om boken redan �r specialutl�nad.
			$Hamtaspecial = "SELECT * FROM special WHERE SBOKID = $BOKID";
			if (!$resultspecial = mysqli_query($db,$Hamtaspecial)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Visa en varning om boken redan �r registrerad som utl�nad.
			if (mysqli_num_rows($resultspecial)) {
                header("location:varning.php?Varning=550");
				exit();
			}
			
			// L�s titeln som �r kopplad till boken.
			$Lastitel = "SELECT * FROM litteratur WHERE TITELID = $TITELID";
			
			// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
			if (!$titelresult = mysqli_query($db,$Lastitel)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// L�s information fr�n databasen litteratur om aktuell titel.
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

				// Kontrollera om data saknas och hantera det i s� fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
				if (empty($UTGIVNINGSAR)) $UTGIVNINGSAR = "";
				else $UTGIVNINGSAR = " ($UTGIVNINGSAR)";
				if (empty($REVIDERAD)) $REVIDERAD = "";
				else $UTGIVNINGSAR = "$UTGIVNINGSAR Reviderad $REVIDERAD";
			}

# BORTPLOCKAD RUTIN F�R ATT KONTROLLERA L�NTAGARENS NUVARANDE GRAD 2013-08-20
#
			// Kontrollera om l�ntagarens grad �r l�gre �n titelns grad om det inte �r ett receptionsl�n och varna och avsluta i s� fall.
#			if ($Typ <> "Reception") {
#                if ($GRAD >= ($Lantagaregrad + 1)) {
#    			header("location:varning.php?Varning=562");
#    				exit();
#    			}
#            }
#
			// Kontrollera om titeln �r tillbakadragen och varna och avsluta i s� fall om man inte g�r ett speciall�n.
			if ($STUDIEPLAN == "X" && $Typ <> "Special") {
			header("location:varning.php?Varning=570");
				exit();
			}
			
			// L�gg till en utl�ning till titelns totala utl�ningsstatistik.
			$LAN = unserialize($LAN);
			$LANTOTALT = $LAN[date("Y")];
			$LANTOTALT = $LANTOTALT + 1;
			$LAN[date("Y")] = $LANTOTALT;
			$LAN = serialize($LAN);
			
			// L�gg till en utl�ning till titelns lokala utl�ningsstatistik.
			$LANARRAY = unserialize($LANARRAY);
			$LANLOKALT = $LANARRAY[$_SESSION['Bibliotek'].date("Y")];
			$LANLOKALT = $LANLOKALT + 1;
			$LANARRAY[$_SESSION['Bibliotek'].date("Y")] = $LANLOKALT;
		
			$LANARRAY = serialize($LANARRAY);
			
			// S�tt ihop str�ngen f�r att skicka dem till databasen.
			$Skrivlan = "INSERT INTO aktiva (BOKID,TITELID,MEDLEM,DATUM,BIBLIOTEK) VALUES ('$BOKID','$TITELID','$Lantagaremedlem','$Serveridag','".$_SESSION['Bibliotek']."')";
			
            // Anpassa str�ngen om det �r ett speciall�n.
			if ($Typ == "Special") {

            	// H�mta information om kv�llens tj�nstg�rande bibliotekarie.
            	$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";
        
                // Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
                if (!$resultbesok = mysqli_query($db,$Hamtabibliotekarie)) {
                    die('Invalid query: ' . mysqli_error($db));
                }
        
            	// L�s medlemsnummer fr�n databasen medlem.
            	while ($row = mysqli_fetch_array($resultbesok)) {
                    $SANSVARIG	= $row["MEDLEM"];
                    $SANSVARIG   = substr($SANSVARIG,2);
                }
            
                $Skrivlan = "INSERT INTO special (SBOKID,STITELID,SMEDLEM,SDATUM,SBIBLIOTEK,SANSVARIG) VALUES ('$BOKID','$TITELID','$Lantagaremedlem','$Serveridag','".$_SESSION['Bibliotek']."','$SANSVARIG')";
            }
            
			// Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
			if (!$result = mysqli_query($db,$Skrivlan)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// Uppdatera databasen litteratur om att en utl�ning har skett.
			$Uppdateratitel = "UPDATE litteratur SET LAN='$LAN',LANARRAY='$LANARRAY' WHERE TITELID=$TITELID";
			
			// Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
			if (!$result = mysqli_query($db,$Uppdateratitel)) {
				die('Invalid query: ' . mysqli_error($db));
			}
			
			// Om l�ntagarens epostadress finns registrerad s� skicka ett automatiskt mail om l�net.
			if ($_SESSION['Lantagaremail'] <> "BLOCK") {
			
				// L�s data om det valda biblioteket.
				$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=".$_SESSION['Bibliotek']."";
					
				// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r galet.
				if (!$bibliotekresult = mysqli_query($db,$Visabibliotek)) {
					die('Invalid query: ' . mysqli_error($db));
				}
				while ($row = mysqli_fetch_array($bibliotekresult)) {
					$BIBLIOTEKNAMN	= utf8_decode($row["BIBLIOTEKNAMN"]);
				}
				
				$Mottagare = $_SESSION['Lantagaremail'];
				$Tillbakadatum = $Serveridag+2592000;
				$Rubrik = "Meddelande fr�n Frimurarebiblioteket";
				$Extra = "From: no-reply@frimurarna.se"."\r\nX-Mailer: PHP/".phpversion()."\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n";
				$Meddelande = "Broder!<br />Du har idag den ".date('j/n Y',$Serveridag)." l&aring;nat boken $KOD \"".utf8_decode($TITEL)."\".<br />";
                if ($Typ <> "Special") $Meddelande = "$Meddelande<br />Boken ska vara tillbaka i biblioteket senast den ".date('j/n Y', $Tillbakadatum).".<br />";
                $Meddelande = "$Meddelande<br />Observera att du inte kan svara p&aring; detta mail.<br /><br />Biblioteket, $BIBLIOTEKNAMN";			
				mail($Mottagare, $Rubrik, $Meddelande, $Extra);			
			}
			
			// Hoppa tillbaka till l�nesidan.
			header("location:lana.php?Typ=$Typ");
		}
	}

	// Avsluta HTML-taggarna och st�ng sidan.
	echo "</div></body></html>";
?>