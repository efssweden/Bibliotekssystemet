<?php
session_start();

    // Hämta pdf-funktionen.
    require('pdf/fpdf.php');

	// Etablera kontakt med databasen.
	require ("../konfiguration/konfig.php");
	$db = mysqli_connect($server, $db_user, $db_pass);
	mysqli_select_db($db,$database);
	
    // Kolla upp vilket steg det är som ska tas.
    $Steg = $_GET['Steg'];

    // Kolla upp dagens datum.
	$Serverar		= date("Y");
	$Servermanad 	= date("m");
	$Serverdag 		= date("d");

    // Om det handlar om en årsrapport så ändra till året man vill rapportera.    
    if ($Steg == "Arsrapport") {
        $Serverar = $_GET["Year"];
    }

	$Serveridag		= mktime(0,0,0,$Servermanad,$Serverdag,$Serverar);
	$Idag 			= date("Y-m-d",$Serveridag);

	// Hämta information om vilket bibliotek som är igång.
	$BIBLIOTEKNR = $_SESSION['Bibliotek'];

	// Räkna efter hur många besök det varit i biblioteket idag men räkna bort tjänstgörande bibliotekarie.
	$Raknabesok	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM = '$Serveridag'";
	$Besokresult = mysqli_query($db,$Raknabesok);
	$Antalbesok	= mysqli_num_rows($Besokresult)-1;

	// Räkna efter hur många utlån det varit i biblioteket idag.
	$Raknalan	= "SELECT * FROM aktiva WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM = '$Serveridag'";
	$Lanresult = mysqli_query($db,$Raknalan);
	$Antallan	= mysqli_num_rows($Lanresult);
		
	// Räkna efter hur många speciallån som gjorts i biblioteket idag.
	$Raknalan	= "SELECT * FROM special WHERE SBIBLIOTEK = $BIBLIOTEKNR AND SDATUM = '$Serveridag'";
	$Lanresult = mysqli_query($db,$Raknalan);
	$Antallan	= ($Antallan+mysqli_num_rows($Lanresult));
		
	// Räkna efter hur många böcker som lämnats tillbaka i biblioteket idag.
	$Raknareturer	= "SELECT * FROM returer WHERE RETUREAN LIKE '$BIBLIOTEKNR%' AND RETURDATUM = '$Serveridag'";
	$Returerresult = mysqli_query($db,$Raknareturer);
	$Antalreturer	= (mysqli_num_rows($Returerresult));
		
	// Läs in namnet på det valda biblioteket.
	$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEKNR";
			
	// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
	if (!$result = mysqli_query($db,$Visabibliotek)) {
		die('Invalid query: ' . mysqli_error($db));
	}
		
	while ($row = mysqli_fetch_array($result)) {
		$BIBLIOTEKNAMN	= utf8_decode($row["BIBLIOTEKNAMN"]);
	}
		
	if ($Steg == "Dagslista") {
 
    	// Hämta information om kvällens tjänstgörande bibliotekarie.
	   	$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";
            
        // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$resultbesok = mysqli_query($db,$Hamtabibliotekarie)) {
			die('Invalid query: ' . mysqli_error($db));
		}
			
		// Läs medlemsnummer från databasen besokslistan.
		while ($row = mysqli_fetch_array($resultbesok)) {
            $Besoksid       = $row["BESOKSID"];
			$Besoksmedlem	= substr($row["MEDLEM"],2);
                							
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
				$Gradiklartext = array("I","II","III","IV-V","","VI","VII","VIII","IX","X");
                $MEDLEMGRAD = $Gradiklartext[$MEDLEMGRAD];

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
        }
		
        // Tjänstgörande bibliotekarie får en egen sträng.
        $BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
		$Tjanstgorande = $MEDLEM;
    }
    
	elseif ($Steg == "Arkiv") {
	
        // Kolla om det finns några registrerade besök.
		$Kollabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR ORDER BY DATUM DESC";
		$Sparadatum = "1";
			
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om besökslistan är tom så berätta det.
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Det finns inga besök registrerade',1,2,'C');
			
		else {
		
            // Läs datum från databasen besokslistan.
			while ($row = mysqli_fetch_array($result)) {
				$Besoksdatum	= $row["DATUM"];
				
				// Visa varje datum bara en gång.
				if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
					
					// Hämta information om kvällens tjänstgörande bibliotekarie.
					$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = $BIBLIOTEKNR AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";

					// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
					if (!$resultbibliotekarie = mysqli_query($db,$Hamtabibliotekarie)) {
						die('Invalid query: ' . mysqli_error($db));
					}

					// Läs medlemsnummer från databasen besokslistan.
					while ($row = mysqli_fetch_array($resultbibliotekarie)) {
						$Besoksmedlem	= substr($row["MEDLEM"],2);
									
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
                    }
						
                    // Tjänstgörande bibliotekarie får en egen sträng.
                    $BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
					$Tjanstgorande = $Besoksmedlem;
                }
            }
        }
    }
    
	elseif ($Steg == "Arkivbror") {
	
        // Kolla om det finns några registrerade besök.
		$Kollabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR ORDER BY DATUM DESC";
		$Sparadatum = "1";
			
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om besökslistan är tom så berätta det.
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Det finns inga besök registrerade',1,2,'C');
			
		else {
		
            // Läs datum från databasen besokslistan.
			while ($row = mysqli_fetch_array($result)) {
				$Besoksdatum	= $row["DATUM"];
				
				// Visa varje datum bara en gång.
				if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
					
					// Hämta information om kvällens tjänstgörande bibliotekarie.
					$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = $BIBLIOTEKNR AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";

					// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
					if (!$resultbibliotekarie = mysqli_query($db,$Hamtabibliotekarie)) {
						die('Invalid query: ' . mysqli_error($db));
					}

					// Läs medlemsnummer från databasen besokslistan.
					while ($row = mysqli_fetch_array($resultbibliotekarie)) {
						$Besoksmedlem	= substr($row["MEDLEM"],2);
									
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
                    }
						
                    // Tjänstgörande bibliotekarie får en egen sträng.
                    $BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
					$Tjanstgorande = $Besoksmedlem;
                }
            }
        }
    }
	
    // Definiera lite grejer till pdf-funktionen.
	class PDF extends FPDF {
	   
		// Definiera dokumentets sidhuvud.
		function Header() {
		  
			//Bibliotekets namn och den tjänstgörande bibliotekarien och dagens datum.
			global $BIBLIOTEKNAMN;
			global $BIBLIOTEKARIE;
			global $Idag;
			global $Steg;

            // Kontrollera om det finns något Exlibris till biblioteket.			
			$Filnamn = "../design/bibliotek/".$_SESSION['Bibliotek'].".jpg";
			if (file_exists($Filnamn)) $Exlibris = "../design/bibliotek/".$_SESSION['Bibliotek'].".jpg";
			else $Exlibris = "../design/bibliotek/Exlibris.jpg";

			// Logo och sidhuvud om man inte ska skriva ut streckkoder.
			if ($Steg == "Lan")	$this->Image('../design/bibliotek/Exlibris.jpg',10,8,33,0,'','administrera.php');
			else $this->Image($Exlibris,10,8,33,0,'','administrera.php');
            
			// Arial bold 15
			$this->SetFont('Arial','B',15);
			
            // Move to the right
			$this->Ln(1);
			$this->Cell(38);
			
            // Skriv ut dokumentets titel på översta raden.
			if($Steg == "Dagslista") $this->Cell(150,10,$BIBLIOTEKNAMN.' '.$Idag,1,2,'C');
			elseif($Steg == "Lan") $this->Cell(150,10,'Svenska Frimurare Orden',1,2,'C');
			else $this->Cell(150,10,$BIBLIOTEKNAMN,1,2,'C');
            
            // Skriv ut dokumentets andra titel på raden under.
			$this->SetFont('Arial','B',10);
			if($Steg == "Dagslista") $this->Cell(150,10,'Tjänstgörande: '.$BIBLIOTEKARIE,1,0,'C');
			elseif($Steg == "Arkiv") $this->Cell(150,10,'Besökslista för året '.date('Y').'',1,0,'C');
			elseif($Steg == "Arsrapport") $this->Cell(150,10,'Årsrapport '.$Serverar.'',1,0,'C');
			elseif($Steg == "Lan") $this->Cell(150,10,'Utlånade titlar för året '.date('Y').'',1,0,'C');
            elseif($Steg == "Lokal") $this->Cell(150,10,'Utlånade titlar för året '.date('Y').'',1,0,'C');
			elseif($Steg == "Kvitto") $this->Cell(150,10,'Lånelapp '.date('Y-m-d').'',1,0,'C');
			elseif($Steg == "Makulerade") $this->Cell(150,10,'Makulerade böcker '.date('Y-m-d').'',1,0,'C');
			elseif($Steg == "Special" && $_SERVER['REMOTE_USER'] <> "9999") $this->Cell(150,10,'Pågående speciallån ur biblioteket',1,0,'C');
			elseif($Steg == "Special" && $_SERVER['REMOTE_USER'] == "9999") $this->Cell(150,10,'Pågående speciallån i systemet',1,0,'C');

			// Avsluta sidhuvudet.
			$this->Ln(20);
			$this->SetDrawColor(0);
			$this->Line(10,38,200,38);
		}

		// Definiera sidfoten.
		function Footer(){
		  
			// Kolla upp dagens datum.
			global $Idag;
            
			// Positionera sidfoten 1,5 cm från botten.
			$this->SetY(-15);
            
			// Skriv ut sidfoten med dagens datum, sidnummer och antal sidor.
			$this->SetFont('Arial','',8);
			$this->Cell(0,10,$Idag.' Sida '.$this->PageNo().' av {nb}',0,0,'C');
		}
	}

	//Instanciation of inherited class
	$pdf=new PDF();
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont('Times','',10);
	if ($Steg == "Kvitto") $pdf->Link(0,0,209,290,'../lana.php');
	else $pdf->Link(0,0,209,290,'administrera.php');
	
	if ($Steg == "Dagslista") {
		// Kolla om det finns några registrerade besök.
		$Kollabesok = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']."";
		$Sparadatum = "1";
		
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om besökslistan är tom så berätta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga besök registrerade idag.',0,1);
		else {
		  
            // Skriv ut dagens besöks- och utlåningsantal.
            $pdf->SetFont('Arial','B',10);
    		$pdf->Cell (0,0,'Besök idag: '.$Antalbesok.' - Utlån idag: '.$Antallan.'',0,1,'C');
    		$pdf->Ln(3);
 
             // Läs datum från databasen besokslistan.
            while ($row = mysqli_fetch_array($result)) {
            $Besoksdatum	= $row["DATUM"];
                       			
    			// Visa varje datum bara en gång.
    			if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
    			
    				// Läs besökare i databasen besokslistan från detta datum.
    				$Hamtabesokare = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND BESOKSID != $Besoksid";
    				
    				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    				if (!$resultbesok = mysqli_query($db,$Hamtabesokare)) {
    					die('Invalid query: ' . mysqli_error($db));
    				}
    					
    				// Läs medlemsnummer från databasen besokslistan.
    				while ($row = mysqli_fetch_array($resultbesok)) {
    					$Besoksdatum	= $row["DATUM"];
    					$Besoksmedlem	= $row["MEDLEM"];
                        if (substr($Besoksmedlem,0,2) == "tj") $Besoksmedlem = substr($Besoksmedlem,2);
    					$Sparadatum = $Besoksdatum;
    					
    					// Hämta information från databasen medlem.
    					$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";
    
    					// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    					if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    						die('Invalid query: ' . mysqli_error($db));
    					}
    
    					// Läs data om brodern i databasen medlem.
    					while ($row = mysqli_fetch_array($resultmedlem)) {
    						$MEDLEM			= $row["MEDLEM"];
    						$MEDLEMNR		= $row["MEDLEM"];
    						$MEDLEMNAMN		= $row["MEDLEMNAMN"];
    						$MEDLEMFNAMN	= $row["MEDLEMFNAMN"];
    						$MEDLEMGRAD		= $row["MEDLEMGRAD"];
    						$MEDLEMLOGE		= $row["MEDLEMLOGE"];
    						$MEDLEMORT		= $row["MEDLEMORT"];
    
    						// Konvertera tillbaka medlemsnumret enligt SFMO.
    						$MEDLEM = substr($MEDLEM,0,4)."-".substr($MEDLEM,4);
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
    
    						$LANADEBOCKER = "";
    						
    						// Kontrollera om låntagaren lånat något idag.
    						$Hamtalan = "SELECT * FROM aktiva WHERE MEDLEM = $MEDLEMNR AND DATUM = $Besoksdatum";
    
    						// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    						if (!$Lanresult = mysqli_query($db,$Hamtalan)) {
    							die('Invalid query: ' . mysqli_error($db));
    						}
    
    						// Läs in bokens namn från databasen aktiva.
    						while ($row = mysqli_fetch_array($Lanresult)) {
    							$LANADBOK = $row["TITELID"];
    							$LANADID = $row["BOKID"];
    						
    							$HamtaEAN = "SELECT * FROM bocker WHERE BOKID = $LANADID";
    							// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    							if (!$eanresult = mysqli_query($db,$HamtaEAN)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    							
    							// Läs information från databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($eanresult)) {
    								$EAN		= $row["EAN"];
    							}
    							
    							$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $LANADBOK";
    							
    							// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    							if (!$titelresult = mysqli_query($db,$Listatitlar)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    			
    							// Läs information från databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($titelresult)) {
    								$TITELID		= $row["TITELID"];
    								$KOD			= $row["KOD"];
    								$TITEL			= $row["TITEL"];
    								$FORFATTARE		= $row["FORFATTARE"];
    
    								// Kontrollera om data saknas och hantera det i så fall.
    								if (empty($TITEL)) $TITEL = "**Titel saknas**";
    								if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
    								
                                    // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                                    if (!empty($KOD)) {
                                        
                                        $KOD = str_replace(":00",":",$KOD);
                                        $KOD = str_replace(":0",":",$KOD);
                                        $KOD = str_replace(" 00"," ",$KOD);
                                        $KOD = str_replace(" 0"," ",$KOD);
                                    }
								
    								$LANADEBOCKER = "$LANADEBOCKER$KOD \"$TITEL\" *$EAN*\n";
    							}
    						}
    						
    						// Kontrollera om låntagaren speciallånat något idag.
    						$Hamtalan = "SELECT * FROM special WHERE SMEDLEM = $MEDLEMNR AND SDATUM = $Besoksdatum";
    
    						// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    						if (!$Lanresult = mysqli_query($db,$Hamtalan)) {
    							die('Invalid query: ' . mysqli_error($db));
    						}
    
    						// Läs in bokens namn från databasen special.
    						while ($row = mysqli_fetch_array($Lanresult)) {
    							$LANADBOK = $row["STITELID"];
    							$LANADID = $row["SBOKID"];
    						
    							$HamtaEAN = "SELECT * FROM bocker WHERE BOKID = $LANADID";
    							// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    							if (!$eanresult = mysqli_query($db,$HamtaEAN)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    							
    							// Läs information från databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($eanresult)) {
    								$EAN		= $row["EAN"];
    							}
    							
    							$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $LANADBOK";
    							
    							// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    							if (!$titelresult = mysqli_query($db,$Listatitlar)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    			
    							// Läs information från databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($titelresult)) {
    								$TITELID		= $row["TITELID"];
    								$KOD			= $row["KOD"];
    								$TITEL			= $row["TITEL"];
    								$FORFATTARE		= $row["FORFATTARE"];
    
    								// Kontrollera om data saknas och hantera det i så fall.
    								if (empty($TITEL)) $TITEL = "**Titel saknas**";
    								if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";

                                    // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                                    if (!empty($KOD)) {
                                        
                                        $KOD = str_replace(":00",":",$KOD);
                                        $KOD = str_replace(":0",":",$KOD);
                                        $KOD = str_replace(" 00"," ",$KOD);
                                        $KOD = str_replace(" 0"," ",$KOD);
                                    }
    								$LANADEBOCKER = "$LANADEBOCKER$KOD \"$TITEL\" *$EAN* (Special)\n";
    							}
    						}
    						
    						$Besoksnamn = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN");
    						$Besoksgrad = utf8_decode("$MEDLEMGRAD");
    						$Besoksloge = utf8_decode("$MEDLEMLOGE");
    						$Besokslan  = utf8_decode("$LANADEBOCKER");
    						}				
    					
    					// Skriv ut en rad med besökande broderns medlemsnummer, namn, grad och loge.
    					$pdf->SetFont('Arial','',8);
    					$pdf->Cell(7,5,'',0,0);
    					$pdf->Cell(35,5,$Besoksnamn,1,0);
    					$pdf->Cell(10,5,$Besoksgrad,1,0,'C');
    					$pdf->Cell(50,5,$Besoksloge,1,0);
    					$pdf->MultiCell(0,5,$Besokslan,1,1);
    					$Rader = $Rader + 1;
    					}
    				}
                }
                
                // Skriv ut en lista på dagens returer.
                $Listareturer = "SELECT * FROM returer WHERE RETUREAN LIKE '$BIBLIOTEKNR%' AND RETURDATUM = '$Serveridag'";

        		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
        		if (!$resultreturer = mysqli_query($db,$Listareturer)) {
        			die('Invalid query: ' . mysqli_error($db));
        		} 

        		// Om listan är tom så berätta det.
        		if (!mysqli_num_rows($resultreturer)) {
                    $pdf->Ln(5);
                    $pdf->SetFont('Arial','B',10);
            		$pdf->Cell (0,0,'Det finns inga registrerade returer idag.',0,1,'C');
                }
                else {

                    // Skriv ut en rubrik.
                    $pdf->Ln(5);
                    $pdf->SetFont('Arial','B',10);
            		$pdf->Cell (0,0,'Returer idag: '.$Antalreturer.'',0,1,'C');
            		$pdf->Ln(3);
                            
                    // Läs information om dagens returer.
                    while ($row = mysqli_fetch_array($resultreturer)) {
                        $RETURTITELID = $row["RETURTITELID"];
                        $RETUREAN = $row["RETUREAN"];

						$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $RETURTITELID";
                        		
						// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
						if (!$titelresult = mysqli_query($db,$Listatitlar)) {
							die('Invalid query: ' . mysqli_error($db));
						}
    			
						// Läs information från databasen litteratur om utvald titel.
						while ($row = mysqli_fetch_array($titelresult)) {
    						$KOD			= utf8_decode($row["KOD"]);
    						$TITEL			= utf8_decode($row["TITEL"]);
        
    						// Kontrollera om data saknas och hantera det i så fall.
    						if (empty($TITEL)) $TITEL = "**Titel saknas**";
                            if (!empty($KOD)) $KOD = "$KOD ";
        																
    						$Returrad = "*$RETUREAN* $KOD\"$TITEL\"";
     
                            // Skriv ut raden.
                            $pdf->SetFont('Arial','',8);
        					$pdf->Cell(7,5,'',0,0);
        					$pdf->MultiCell(0,5,$Returrad,1,1);
                            $Rader = $Rader + 1; 
						}
                    }
                }		
            }
	   }
	elseif ($Steg == "Arsrapport") {
        // Visa lite lämplig statistik för året.
		$Datumett	= mktime(0,0,0,1,1,$Serverar);
		$Datumtva	= mktime(0,0,0,12,31,$Serverar);

    	// Räkna efter hur många besök det varit i biblioteket i år.
    	$Raknabesok	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM >= '$Datumett' AND DATUM <= '$Datumtva'";
    	$Besokresult = mysqli_query($db,$Raknabesok);
    	$Antalbesok	= mysqli_num_rows($Besokresult);

        // Räkna efter hur många besökare av respektive grad som varit i biblioteket i år.
        while ($row = mysqli_fetch_array($Besokresult)) {
            $MEDLEM = $row["MEDLEM"];
            $Gradkoll = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEM";
            $Gradkollresult = mysqli_query($db,$Gradkoll);
            if ($Gradkollresult <> "") {
            while ($row = mysqli_fetch_array($Gradkollresult)) {
                $MEDLEMGRAD = $row["MEDLEMGRAD"];
            }
            if ($MEDLEMGRAD == 1) $SJL = $SJL+1;
            if ($MEDLEMGRAD == 2) $SJMB = $SJMB+1;
            if ($MEDLEMGRAD == 3) $SJM = $SJM+1;
            if ($MEDLEMGRAD == 4) $SAMB = $SAMB+1;
            if ($MEDLEMGRAD == 6) $SAM = $SAM+1;
            if ($MEDLEMGRAD == 7) $KL = $KL+1;
            if ($MEDLEMGRAD == 8) $KMB = $KMB+1;
            if ($MEDLEMGRAD == 9) $KM = $KM+1;
            if ($MEDLEMGRAD == 10) $KMM = $KMM+1;
            }
        }   
        $Antalbesoktotalt = $SJL+$SJMB+$SJM+$SAMB+$SAM+$KL+$KMB+$KM+$KMM;
        
		// Räkna efter hur många lån som gjorts i biblioteket i år.
		$Utlan = 0;
		$Raknalan = "SELECT * FROM litteratur";
		$Lanresult = mysqli_query($db,$Raknalan);
		while ($row = mysqli_fetch_array($Lanresult)) {
			$LANARRAY		= $row["LANARRAY"];
			$LANARRAY = unserialize($LANARRAY);
			$LANLOKALT = $LANARRAY[$BIBLIOTEKNR.$Serverar];
			$Utlan = $Utlan + $LANLOKALT;
		}
        $Totalutlan = $Totalutlan+$Utlan;
                		
    	// Räkna efter hur många böcker som lämnats tillbaka i biblioteket under året.
    	$Raknareturer	= "SELECT * FROM returer WHERE RETURBIBLIOTEK = $BIBLIOTEKNR AND RETURDATUM >= '$Datumett' AND RETURDATUM <= '$Datumtva'";
    	$Returerresult = mysqli_query($db,$Raknareturer);
    	$Antalreturer	= (mysqli_num_rows($Returerresult));

        $pdf->SetFont('Arial','',13);
		$pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,'Under året '.$Serverar.' har följande händelser registrerats i biblioteket:',0,1);
        $pdf->Ln(8);
        $pdf->SetFont('Arial','',16);
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,''.$Antalbesok.' besök av '.$Antalbesoktotalt.' bröder enligt följande:',0,1);
        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,'Grad I: '.$SJL.' Grad II: '.$SJMB.' Grad III: '.$SJM.'',0,1);
        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,'Grad IV-V: '.$SAMB.' Grad VI: '.$SAM.'',0,1);
        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,'Grad VII: '.$KL.' Grad VIII: '.$KMB.' Grad IX: '.$KM.' Grad X: '.$KMM.'',0,1);
        $pdf->Ln(8);           

        $pdf->SetFont('Arial','',10);           
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,'Observera att ovanstående gäller brödernas grad idag - inte när besöken gjordes.',0,1);
        $pdf->Ln(8);   
        $pdf->SetFont('Arial','',16);        

        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,''.$Totalutlan.' utlånade böcker.',0,1);
        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,''.$Antalreturer.' återlämnade böcker.',0,1);
        $pdf->Ln(8);           
	}
    
    elseif ($Steg == "Makulerade") {
        // Kolla om det finns några makulerade böcker.
        $Kollamakulerade = "SELECT * FROM makulerade WHERE MAKEAN LIKE '$BIBLIOTEKNR%' ORDER BY MAKDATUM";
        if ($_SERVER['REMOTE_USER'] == "9999") $Kollamakulerade = "SELECT * FROM makulerade ORDER BY MAKDATUM";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Kollamakulerade)) {
			die('Invalid query: ' . mysqli_error($db));
		} 

		// Om listan är tom så berätta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga makulerade böcker registrerade.',0,1);
        else {
            
            $pdf->SetFont('Arial','I',9);
		    $pdf->Cell(7,5,'',0,0);
        	$pdf->Cell(20,0,'Datum',0,0);
        	$pdf->Cell(20,0,'Streckkod',0,0);
	        $pdf->Cell(0,0,'Titel',0,1);
        	$pdf->Ln(3);

            // Läs information om makulerade titlar.
            while ($row = mysqli_fetch_array($result)) {
                $Makdatum = $row["MAKDATUM"];
                $Maktitelnr = $row["MAKTITEL"];
                $Makean = $row["MAKEAN"];
                
                $Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = $Maktitelnr";
   				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
       			if (!$resulttitel = mysqli_query($db,$Hamtatitel)) {
	      			die('Invalid query: ' . mysqli_error($db));
		     	}
                
                // Läs data om titeln i databasen litteratur.
					while ($row = mysqli_fetch_array($resulttitel)) {
						$TITEL			= $row["TITEL"];
                        $KOD            = $row["KOD"];

                        // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                        if (!empty($KOD)) {
                            
                            $KOD = str_replace(":00",":",$KOD);
                            $KOD = str_replace(":0",":",$KOD);
                            $KOD = str_replace(" 00"," ",$KOD);
                            $KOD = str_replace(" 0"," ",$KOD);
                        }
        
                        if ($KOD == "") $Maktitel = utf8_decode("$TITEL");
                        else $Maktitel = utf8_decode("$KOD - $TITEL");
                        if (strlen($Maktitel) >= 100) $Maktitel = substr($Maktitel,0,100)."...";
                            
                     	// Skriv ut rubrik.
                        if ($Rader >= 33) {
					       $Rader = 1;
					       $pdf->AddPage();
				        }
        				$pdf->SetFont('Arial','',9);
		          		$pdf->Cell(7,5,'',0,0);
        				$pdf->Cell(20,0,date('Y-m-d',$Makdatum),0,0);
        				$pdf->Cell(20,0,''.$Makean.'',0,0);
	           			$pdf->Cell(0,0,''.$Maktitel.'',0,1);
        				$pdf->Ln(3);
        				$Rader = $Rader + 2;
				    }
            }
        }
    }
    
    elseif ($Steg == "Special") {
        // Kolla om det finns några makulerade böcker.
        $Kollaspecial = "SELECT * FROM special WHERE SBIBLIOTEK = $BIBLIOTEKNR ORDER BY SDATUM ASC";
        if ($_SERVER['REMOTE_USER'] == "9999") $Kollaspecial = "SELECT * FROM special ORDER BY SDATUM ASC";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Kollaspecial)) {
			die('Invalid query: ' . mysqli_error($db));
		} 

		// Om listan är tom så berätta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga speciallån registrerade.',0,1);
        else {
            // Skriv ut rubriker.
            $pdf->SetFont('Arial','I',9);
		          		$pdf->Cell(7,5,'',0,0);
        				$pdf->Cell(20,0,'Datum',0,0);
        				$pdf->Cell(20,0,'Streckkod',0,0);
        				$pdf->Cell(30,0,'Låntagare',0,0);
	           			$pdf->Cell(0,0,'Titel',0,1);
        				$pdf->Ln(3);
                        
            // Läs information om speciallånade titlar.
            while ($row = mysqli_fetch_array($result)) {
                $Datum = $row["SDATUM"];
                $Stitel = $row["STITELID"];
                $Slantagare = $row["SMEDLEM"];
                $Ansvarig = $row["SANSVARIG"];
                $Sbokid = $row["SBOKID"];
                
                // Rätta till medlemsnummer.
                $Ansvarig = substr($Ansvarig,0,4)."-".substr($Ansvarig,4);
#                $Lantagare = substr($Slantagare,0,4)."-".substr($Slantagare,4);

                // Hämta information från databasen medlem.
    			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Slantagare";
    
    			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
    
    			// Läs data om brodern i databasen medlem.
    			while ($row = mysqli_fetch_array($resultmedlem)) {
    				$MEDLEMNR		= $row["MEDLEM"];
    				$MEDLEMNAMN		= $row["MEDLEMNAMN"];
    				$MEDLEMFNAMN	= $row["MEDLEMFNAMN"];
    				$MEDLEMGRAD		= $row["MEDLEMGRAD"];
    				$MEDLEMLOGE		= $row["MEDLEMLOGE"];
    				$MEDLEMORT		= $row["MEDLEMORT"];
    
    				// Konvertera tillbaka medlemsnumret enligt SFMO.
    				$MEDLEM = substr($MEDLEM,0,4)."-".substr($MEDLEM,4);
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
                    
      				// Hämta bokens streckkod från databasen bocker.
    				$Hamtaean = "SELECT * FROM bocker WHERE BOKID = $Sbokid";
    
    				// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    				if (!$Eanresult = mysqli_query($db,$Hamtaean)) {
    					die('Invalid query: ' . mysqli_error($db));
    				}
    
    				// Läs in logens namn från databasen arbetsenheter.
    				while ($row = mysqli_fetch_array($Eanresult)) {
    					$EAN = $row["EAN"];
    				}
                }
                
                // Skapa en sträng av låntagarens info.
                $Lantagare = utf8_decode($MEDLEMFNAMN." ".$MEDLEMNAMN);
                
                $Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = $Stitel";
   				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
       			if (!$resulttitel = mysqli_query($db,$Hamtatitel)) {
	      			die('Invalid query: ' . mysqli_error($db));
		     	}
                
                // Läs data om titeln i databasen litteratur.
					while ($row = mysqli_fetch_array($resulttitel)) {
						$TITEL			= $row["TITEL"];
                        $KOD            = $row["KOD"];

                        // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                        if (!empty($KOD)) {
                            
                            $KOD = str_replace(":00",":",$KOD);
                            $KOD = str_replace(":0",":",$KOD);
                            $KOD = str_replace(" 00"," ",$KOD);
                            $KOD = str_replace(" 0"," ",$KOD);
                        }
        
                        if ($KOD == "") $Specialtitel = utf8_decode("$TITEL");
                        else $Specialtitel = utf8_decode("$KOD - $TITEL");
                        if (strlen($Specialtitel) >= 70) $Specialtitel = substr($Specialtitel,0,70)."...";
                            
                     	// Skriv ut rubrik.
                        if ($Rader >= 33) {
					       $Rader = 2;
					       $pdf->AddPage();
				        }
        				$pdf->SetFont('Arial','',9);
		          		$pdf->Cell(7,5,'',0,0);
        				$pdf->Cell(20,0,date('Y-m-d',$Datum),0,0);
        				$pdf->Cell(20,0,''.$EAN.'',0,0);
        				$pdf->Cell(30,0,''.$Lantagare.'',0,0);
	           			$pdf->Cell(0,0,''.$Specialtitel.'',0,1);
        				$pdf->Ln(3);
        				$Rader = $Rader + 2;
				    }
            }
        }
    }
    
	elseif ($Steg == "Arkiv") {
		// Kolla om det finns några registrerade besök i år.
		$Datumett	= mktime(0,0,0,1,1,date("Y"));
		$Datumtva	= mktime(0,0,0,12,31,date("Y"));
		$Kollabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM > $Datumett AND DATUM < $Datumtva ORDER BY DATUM ASC";
		$Sparadatum = 1;
		$Rader = 1;
		
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om besökslistan är tom så berätta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga besök registrerade idag.',0,1);
		else {
			// Läs datum från databasen besokslistan.
			while ($row = mysqli_fetch_array($result)) {
				$Besoksdatum	= $row["DATUM"];
				
				// Visa varje datum bara en gång.
				if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
				
				// Hämta information om kvällens tjänstgörande bibliotekarie.
				$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = $BIBLIOTEKNR AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";

				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$resultbibliotekarie = mysqli_query($db,$Hamtabibliotekarie)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// Läs medlemsnummer från databasen besokslistan.
				while ($row = mysqli_fetch_array($resultbibliotekarie)) {
					$Besoksmedlem	= substr($row["MEDLEM"],2);
                    $Besoksid       = $row["BESOKSID"];
								
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
				}
				$BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
				$Tjanstgorande = $Besoksmedlem;
				
				// Läs besökare i databasen besokslistan från detta datum.
				$Hamtabesokare = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND BESOKSID != '$Besoksid'";
					
				// Skriv ut rubrik.
				if ($Rader >= 33) {
					$Rader = 1;
					$pdf->AddPage();
				}
				$pdf->SetFont('Arial','B',10);
				$pdf->Cell(7,5,'',0,0);
				$pdf->Cell(20,0,date('Y-m-d',$Besoksdatum),0,0);
				$pdf->Cell(0,0,'Tjänstgörande: '.$BIBLIOTEKARIE.'',0,1);
				$pdf->Ln(3);
				$Rader = $Rader + 2;
				
				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$resultbesok = mysqli_query($db,$Hamtabesokare)) {
					die('Invalid query: ' . mysqli_error($db));
				}
						
				// Läs medlemsnummer från databasen besokslistan.
				while ($row = mysqli_fetch_array($resultbesok)) {
					$Besoksdatum	= $row["DATUM"];
					$Besoksmedlem	= $row["MEDLEM"];
                    if (substr($Besoksmedlem,0,2) == "tj") $Besoksmedlem = substr($Besoksmedlem,2);
					$Sparadatum = $Besoksdatum;
						
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
						$MEDLEMORT		= $row["MEDLEMORT"];

						// Konvertera tillbaka medlemsnumret enligt SFMO.
						$MEDLEM = substr($MEDLEM,0,4)."-".substr($MEDLEM,4);
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
							
						$Besoksnamn = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN");
						$Besoksgrad = utf8_decode("$MEDLEMGRAD");
						$Besoksloge = utf8_decode("$MEDLEMLOGE");
					}				
						
					// Skriv ut en rad med besökande broderns medlemsnummer, namn, grad och loge.
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(7,5,'',0,0);
					$pdf->Cell(50,5,$Besoksnamn,1,0);
					$pdf->Cell(10,5,$Besoksgrad,1,0,'C');
					$pdf->Cell(0,5,$Besoksloge,1,1);
					$Rader = $Rader + 1;
				}	
				$pdf->Ln(5);
				}
			}
		}
	}
	
	elseif ($Steg == "Lan") {
		// Hämta utlånade titlar från databasen litteratur sorterat efter grad.
#		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".$_SESSION['Bibliotek'].date("Y")."%'";
		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".date("Y")."%'";
		$Rader = 1;
		
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Inga utlånade böcker under året',0,0);
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
				$LANARRAY		= $row["LAN"];

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
				if ($GRAD == "0") $GRAD = "Öppen";
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
				if ($STUDIEPLAN == "B") $STUDIEPLAN = "Studieplan: Bas";
				if ($STUDIEPLAN == "K") $STUDIEPLAN = "Studieplan: Komplettering";
				if ($STUDIEPLAN == "F") $STUDIEPLAN = "Studieplan: Fördjupning";		

				// Kontrollera om det finns några böcker kopplade till titeln.
				$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$bokresult = mysqli_query($db,$Listabocker)) {
					die('Invalid query: ' . mysqli_error($db));
				} 
				$LANARRAY = unserialize($LANARRAY);
				$LANLOKALT = $LANARRAY[date("Y")];
				if(!empty($KOD)) $KOD = $KOD." ";
				// Läs information från databasen bocker om aktuell titel.
				$Raknabocker = mysqli_num_rows($bokresult);
				$Lanerad = utf8_decode($KOD)."\"".utf8_decode($TITEL)."\" ".$UTGIVNINGSAR."\nFörfattare: ".utf8_decode($FORFATTARE);
				if (!empty($STUDIEPLAN)) $Lanerad = $Lanerad."\n$STUDIEPLAN";
				$Lokallan[$i] = array(LaneID => $i, Lanerad => "$Lanerad", GRAD => "$GRAD", LANLOKALT => $LANLOKALT);
				$i = $i+1;
			}
			// Dra bort ett från $i för att kompensera den sista ettan som lagts till.
			$i = $i-1;

			// Funktion för att underlätta sorteringen av de lokala lånen.
			function compare($x, $y){
				if ( $x['LANLOKALT'] == $y['LANLOKALT'] ) return 0; 
				elseif ( $x['LANLOKALT'] > $y['LANLOKALT'] )  return -1; 
				else return 1;
			}
			// Sortera de lokala lånen.
			usort($Lokallan, "compare");
				
			for ($n=0; $n<=$i; $n++) {
				// Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
				if ($Rader >= 21) {
					$Rader = 1;
					$pdf->AddPage();
				}
				$LANLOKALT = $Lokallan[$n]['LANLOKALT'];
				$GRAD = $Lokallan[$n]['GRAD'];
				$LaneID = $Lokallan[$n]['LaneID'];
				$Lanerad = $Lokallan[$n]['Lanerad'];
				
				$pdf->SetFont('Arial','',8);
				$pdf->Cell(7,5,'',0,0);
				$pdf->Cell(10,5,$LANLOKALT,1,0,'C');
				$pdf->Cell(10,5,$GRAD,1,0,'C');
				$pdf->MultiCell(0,4,$Lanerad,1,1);
				$Rader = $Rader + 1;
			}
		}
	}
	
	elseif ($Steg == "Lokal") {
		// Hämta utlånade titlar från databasen litteratur.
		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".$_SESSION['Bibliotek'].date("Y")."%'";
		$Rader = 1;
		
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Inga utlånade böcker under året',0,0);
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
				if ($GRAD == "0") $GRAD = "Öppen";
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
				if ($STUDIEPLAN == "B") $STUDIEPLAN = "Studieplan: Bas";
				if ($STUDIEPLAN == "K") $STUDIEPLAN = "Studieplan: Komplettering";
				if ($STUDIEPLAN == "F") $STUDIEPLAN = "Studieplan: Fördjupning";		

				// Kontrollera om det finns några böcker kopplade till titeln.
				$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$bokresult = mysqli_query($db,$Listabocker)) {
					die('Invalid query: ' . mysqli_error($db));
				} 
				$LANARRAY = unserialize($LANARRAY);
				$LANLOKALT = $LANARRAY[$_SESSION['Bibliotek'].date("Y")];
				if(!empty($KOD)) $KOD = $KOD." ";
				// Läs information från databasen bocker om aktuell titel.
				$Raknabocker = mysqli_num_rows($bokresult);
				$Lanerad = utf8_decode($KOD)."\"".utf8_decode($TITEL)."\" ".$UTGIVNINGSAR."\nFörfattare: ".utf8_decode($FORFATTARE);
				if (!empty($STUDIEPLAN)) $Lanerad = $Lanerad."\n$STUDIEPLAN";
				$Lokallan[$i] = array(LaneID => $i, Lanerad => "$Lanerad", GRAD => "$GRAD", LANLOKALT => $LANLOKALT);
				$i = $i+1;
			}
			// Dra bort ett från $i för att kompensera den sista ettan som lagts till.
			$i = $i-1;

			// Funktion för att underlätta sorteringen av de lokala lånen.
			function compare($x, $y){
				if ( $x['LANLOKALT'] == $y['LANLOKALT'] ) return 0; 
				elseif ( $x['LANLOKALT'] > $y['LANLOKALT'] )  return -1; 
				else return 1;
			}
			// Sortera de lokala lånen.
			usort($Lokallan, "compare");
				
			for ($n=0; $n<=$i; $n++) {
				// Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
				if ($Rader >= 20) {
					$Rader = 1;
					$pdf->AddPage();
				}
				$LANLOKALT = $Lokallan[$n]['LANLOKALT'];
				$GRAD = $Lokallan[$n]['GRAD'];
				$LaneID = $Lokallan[$n]['LaneID'];
				$Lanerad = $Lokallan[$n]['Lanerad'];
				
				$pdf->SetFont('Arial','',8);
				$pdf->Cell(7,5,'',0,0);
				$pdf->Cell(10,5,$LANLOKALT,1,0,'C');
				$pdf->Cell(10,5,$GRAD,1,0,'C');
				$pdf->MultiCell(0,4,$Lanerad,1,1);
				$Rader = $Rader + 1;
			}
		}	
	}

	
	elseif ($Steg == "Kvitto") {
		// Starta en ny sida.
		$pdf->SetFont('Arial','',12);
		
		// Läs in informationen om låntagaren.
		$Hamtalantagare = "SELECT * FROM medlem WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultmedlem = mysqli_query($db,$Hamtalantagare)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Läs data om brodern i databasen medlem.
		while ($row = mysqli_fetch_array($resultmedlem)) {
			$MEDLEM			= $row["MEDLEM"];
			$MEDLEMNR		= $row["MEDLEM"];
			$MEDLEMNAMN		= $row["MEDLEMNAMN"];
			$MEDLEMFNAMN	= $row["MEDLEMFNAMN"];
			$MEDLEMGRAD		= $row["MEDLEMGRAD"];
			$MEDLEMLOGE		= $row["MEDLEMLOGE"];
			$MEDLEMORT		= $row["MEDLEMORT"];
		}
		
		// Konvertera tillbaka medlemsnumret enligt SFMO.
		$MEDLEM = substr($MEDLEM,0,4)."-".substr($MEDLEM,4);
		// Skriv ut medlemsgraden i klartext.
		if ($MEDLEMGRAD == 1) $MEDLEMGRAD = ", I";
		if ($MEDLEMGRAD == 2) $MEDLEMGRAD = ", II";
		if ($MEDLEMGRAD == 3) $MEDLEMGRAD = ", III";
		if ($MEDLEMGRAD == 4) $MEDLEMGRAD = ", IV-V";
		if ($MEDLEMGRAD == 6) $MEDLEMGRAD = ", VI";
		if ($MEDLEMGRAD == 7) $MEDLEMGRAD = ", VII";
		if ($MEDLEMGRAD == 8) $MEDLEMGRAD = ", VIII";
		if ($MEDLEMGRAD == 9) $MEDLEMGRAD = ", IX";
		if ($MEDLEMGRAD == 10) $MEDLEMGRAD = ", X";
		if ($MEDLEMGRAD == 11) $MEDLEMGRAD = ", XI";

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

		$Besoksnamn = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN");
		$Besoksgrad = utf8_decode("$MEDLEMGRAD");
		$Besoksloge = utf8_decode("$MEDLEMLOGE");
		$Medlemsrad = "$MEDLEM $Besoksnamn$Besoksgrad\n$Besoksloge\n\n";
		
		// Skriv ut låntagarens medlemsnummer, namn, grad och loge.
		$pdf->Cell(7,5,'',0,0);
		$pdf->MultiCell(0,4,$Medlemsrad,0,1);
			
		// Visa den aktuelle låntagarens pågående lån.
		$Visalan = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Visalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["LANID"];
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
			$DATUM			= $row["DATUM"];
			
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
				$FORFATTARE		= utf8_decode($row["FORFATTARE"]);
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];

				// Kontrollera om data saknas och hantera det i så fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**Författare saknas**";
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
				
				// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
				$Tillbakadatum = $DATUM+2592000;
				
				// Skriv ut raden med information.
				$Lanerad = "\n$KOD \"$TITEL\"$UTGIVNINGSAR\nFörfattare: $FORFATTARE\nUtlånad ".date('Y-m-d',$DATUM).".\nTillbakalämnas senast ".date('Y-m-d',$Tillbakadatum).".\n\n";
				
				$pdf->Cell(7,5,'',0,0);				
				$pdf->MultiCell(0,4,$Lanerad,1,1);
				$pdf->ln();				

			}
		}
		// Visa den aktuelle låntagarens pågående speciallån.
		$Visalan = "SELECT * FROM special WHERE SMEDLEM = ".$_SESSION['Lantagaremedlem']."";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Visalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Läs data om utlånade böcker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["SLANID"];
			$BOKID			= $row["SBOKID"];
			$TITELID		= $row["STITELID"];
			$DATUM			= $row["SDATUM"];
			
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
				$FORFATTARE		= utf8_decode($row["FORFATTARE"]);
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];

				// Kontrollera om data saknas och hantera det i så fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**Författare saknas**";
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
				
				// Kontrollera återlämningsdatum för boken = utlämningsdag + 30 dagar (2592000 sekunder).
				$Tillbakadatum = $DATUM+2592000;
				
				// Skriv ut raden med information.
				$Lanerad = "\n$KOD \"$TITEL\"$UTGIVNINGSAR\nFörfattare: $FORFATTARE\nUtlånad ".date('Y-m-d',$DATUM).".\nSpeciallån.\n\n";
				
				$pdf->Cell(7,5,'',0,0);				
				$pdf->MultiCell(0,4,$Lanerad,1,1);
				$pdf->ln();				

			}
		}
	}
	$pdf->Output();
?>