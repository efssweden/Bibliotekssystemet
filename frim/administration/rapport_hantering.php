<?php
session_start();

    // H�mta pdf-funktionen.
    require('pdf/fpdf.php');

	// Etablera kontakt med databasen.
	require ("../konfiguration/konfig.php");
	$db = mysqli_connect($server, $db_user, $db_pass);
	mysqli_select_db($db,$database);
	
    // Kolla upp vilket steg det �r som ska tas.
    $Steg = $_GET['Steg'];

    // Kolla upp dagens datum.
	$Serverar		= date("Y");
	$Servermanad 	= date("m");
	$Serverdag 		= date("d");

    // Om det handlar om en �rsrapport s� �ndra till �ret man vill rapportera.    
    if ($Steg == "Arsrapport") {
        $Serverar = $_GET["Year"];
    }

	$Serveridag		= mktime(0,0,0,$Servermanad,$Serverdag,$Serverar);
	$Idag 			= date("Y-m-d",$Serveridag);

	// H�mta information om vilket bibliotek som �r ig�ng.
	$BIBLIOTEKNR = $_SESSION['Bibliotek'];

	// R�kna efter hur m�nga bes�k det varit i biblioteket idag men r�kna bort tj�nstg�rande bibliotekarie.
	$Raknabesok	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM = '$Serveridag'";
	$Besokresult = mysqli_query($db,$Raknabesok);
	$Antalbesok	= mysqli_num_rows($Besokresult)-1;

	// R�kna efter hur m�nga utl�n det varit i biblioteket idag.
	$Raknalan	= "SELECT * FROM aktiva WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM = '$Serveridag'";
	$Lanresult = mysqli_query($db,$Raknalan);
	$Antallan	= mysqli_num_rows($Lanresult);
		
	// R�kna efter hur m�nga speciall�n som gjorts i biblioteket idag.
	$Raknalan	= "SELECT * FROM special WHERE SBIBLIOTEK = $BIBLIOTEKNR AND SDATUM = '$Serveridag'";
	$Lanresult = mysqli_query($db,$Raknalan);
	$Antallan	= ($Antallan+mysqli_num_rows($Lanresult));
		
	// R�kna efter hur m�nga b�cker som l�mnats tillbaka i biblioteket idag.
	$Raknareturer	= "SELECT * FROM returer WHERE RETUREAN LIKE '$BIBLIOTEKNR%' AND RETURDATUM = '$Serveridag'";
	$Returerresult = mysqli_query($db,$Raknareturer);
	$Antalreturer	= (mysqli_num_rows($Returerresult));
		
	// L�s in namnet p� det valda biblioteket.
	$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEKNR";
			
	// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r galet.
	if (!$result = mysqli_query($db,$Visabibliotek)) {
		die('Invalid query: ' . mysqli_error($db));
	}
		
	while ($row = mysqli_fetch_array($result)) {
		$BIBLIOTEKNAMN	= utf8_decode($row["BIBLIOTEKNAMN"]);
	}
		
	if ($Steg == "Dagslista") {
 
    	// H�mta information om kv�llens tj�nstg�rande bibliotekarie.
	   	$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";
            
        // Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$resultbesok = mysqli_query($db,$Hamtabibliotekarie)) {
			die('Invalid query: ' . mysqli_error($db));
		}
			
		// L�s medlemsnummer fr�n databasen besokslistan.
		while ($row = mysqli_fetch_array($resultbesok)) {
            $Besoksid       = $row["BESOKSID"];
			$Besoksmedlem	= substr($row["MEDLEM"],2);
                							
			// H�mta information fr�n databasen medlem.
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";

			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// L�s data om brodern i databasen medlem.
			while ($row = mysqli_fetch_array($resultmedlem)) {
                $MEDLEM			= $row["MEDLEM"];
				$MEDLEMNAMN		= $row["MEDLEMNAMN"];
				$MEDLEMFNAMN	= $row["MEDLEMFNAMN"];
				$MEDLEMGRAD		= $row["MEDLEMGRAD"];
				$MEDLEMLOGE		= $row["MEDLEMLOGE"];

				// Skriv ut medlemsgraden i klartext.
				$Gradiklartext = array("I","II","III","IV-V","","VI","VII","VIII","IX","X");
                $MEDLEMGRAD = $Gradiklartext[$MEDLEMGRAD];

				// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
				$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";

				// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
				if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// L�s in logens namn fr�n databasen arbetsenheter.
				while ($row = mysqli_fetch_array($Logeresult)) {
					$MEDLEMLOGE = $row["ENHETNAMN"];
				}
            }
        }
		
        // Tj�nstg�rande bibliotekarie f�r en egen str�ng.
        $BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
		$Tjanstgorande = $MEDLEM;
    }
    
	elseif ($Steg == "Arkiv") {
	
        // Kolla om det finns n�gra registrerade bes�k.
		$Kollabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR ORDER BY DATUM DESC";
		$Sparadatum = "1";
			
		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om bes�kslistan �r tom s� ber�tta det.
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Det finns inga bes�k registrerade',1,2,'C');
			
		else {
		
            // L�s datum fr�n databasen besokslistan.
			while ($row = mysqli_fetch_array($result)) {
				$Besoksdatum	= $row["DATUM"];
				
				// Visa varje datum bara en g�ng.
				if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
					
					// H�mta information om kv�llens tj�nstg�rande bibliotekarie.
					$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = $BIBLIOTEKNR AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";

					// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
					if (!$resultbibliotekarie = mysqli_query($db,$Hamtabibliotekarie)) {
						die('Invalid query: ' . mysqli_error($db));
					}

					// L�s medlemsnummer fr�n databasen besokslistan.
					while ($row = mysqli_fetch_array($resultbibliotekarie)) {
						$Besoksmedlem	= substr($row["MEDLEM"],2);
									
						// H�mta information fr�n databasen medlem.
						$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";

						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
						if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
							die('Invalid query: ' . mysqli_error($db));
						}

						// L�s data om brodern i databasen medlem.
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

							// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
							$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";

							// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
							if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
								die('Invalid query: ' . mysqli_error($db));
							}

							// L�s in logens namn fr�n databasen arbetsenheter.
							while ($row = mysqli_fetch_array($Logeresult)) {
								$MEDLEMLOGE = $row["ENHETNAMN"];
							}
                        }
                    }
						
                    // Tj�nstg�rande bibliotekarie f�r en egen str�ng.
                    $BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
					$Tjanstgorande = $Besoksmedlem;
                }
            }
        }
    }
    
	elseif ($Steg == "Arkivbror") {
	
        // Kolla om det finns n�gra registrerade bes�k.
		$Kollabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR ORDER BY DATUM DESC";
		$Sparadatum = "1";
			
		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om bes�kslistan �r tom s� ber�tta det.
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Det finns inga bes�k registrerade',1,2,'C');
			
		else {
		
            // L�s datum fr�n databasen besokslistan.
			while ($row = mysqli_fetch_array($result)) {
				$Besoksdatum	= $row["DATUM"];
				
				// Visa varje datum bara en g�ng.
				if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
					
					// H�mta information om kv�llens tj�nstg�rande bibliotekarie.
					$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = $BIBLIOTEKNR AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";

					// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
					if (!$resultbibliotekarie = mysqli_query($db,$Hamtabibliotekarie)) {
						die('Invalid query: ' . mysqli_error($db));
					}

					// L�s medlemsnummer fr�n databasen besokslistan.
					while ($row = mysqli_fetch_array($resultbibliotekarie)) {
						$Besoksmedlem	= substr($row["MEDLEM"],2);
									
						// H�mta information fr�n databasen medlem.
						$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";

						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
						if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
							die('Invalid query: ' . mysqli_error($db));
						}

						// L�s data om brodern i databasen medlem.
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

							// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
							$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";

							// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
							if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
								die('Invalid query: ' . mysqli_error($db));
							}

							// L�s in logens namn fr�n databasen arbetsenheter.
							while ($row = mysqli_fetch_array($Logeresult)) {
								$MEDLEMLOGE = $row["ENHETNAMN"];
							}
                        }
                    }
						
                    // Tj�nstg�rande bibliotekarie f�r en egen str�ng.
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
		  
			//Bibliotekets namn och den tj�nstg�rande bibliotekarien och dagens datum.
			global $BIBLIOTEKNAMN;
			global $BIBLIOTEKARIE;
			global $Idag;
			global $Steg;

            // Kontrollera om det finns n�got Exlibris till biblioteket.			
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
			
            // Skriv ut dokumentets titel p� �versta raden.
			if($Steg == "Dagslista") $this->Cell(150,10,$BIBLIOTEKNAMN.' '.$Idag,1,2,'C');
			elseif($Steg == "Lan") $this->Cell(150,10,'Svenska Frimurare Orden',1,2,'C');
			else $this->Cell(150,10,$BIBLIOTEKNAMN,1,2,'C');
            
            // Skriv ut dokumentets andra titel p� raden under.
			$this->SetFont('Arial','B',10);
			if($Steg == "Dagslista") $this->Cell(150,10,'Tj�nstg�rande: '.$BIBLIOTEKARIE,1,0,'C');
			elseif($Steg == "Arkiv") $this->Cell(150,10,'Bes�kslista f�r �ret '.date('Y').'',1,0,'C');
			elseif($Steg == "Arsrapport") $this->Cell(150,10,'�rsrapport '.$Serverar.'',1,0,'C');
			elseif($Steg == "Lan") $this->Cell(150,10,'Utl�nade titlar f�r �ret '.date('Y').'',1,0,'C');
            elseif($Steg == "Lokal") $this->Cell(150,10,'Utl�nade titlar f�r �ret '.date('Y').'',1,0,'C');
			elseif($Steg == "Kvitto") $this->Cell(150,10,'L�nelapp '.date('Y-m-d').'',1,0,'C');
			elseif($Steg == "Makulerade") $this->Cell(150,10,'Makulerade b�cker '.date('Y-m-d').'',1,0,'C');
			elseif($Steg == "Special" && $_SERVER['REMOTE_USER'] <> "9999") $this->Cell(150,10,'P�g�ende speciall�n ur biblioteket',1,0,'C');
			elseif($Steg == "Special" && $_SERVER['REMOTE_USER'] == "9999") $this->Cell(150,10,'P�g�ende speciall�n i systemet',1,0,'C');

			// Avsluta sidhuvudet.
			$this->Ln(20);
			$this->SetDrawColor(0);
			$this->Line(10,38,200,38);
		}

		// Definiera sidfoten.
		function Footer(){
		  
			// Kolla upp dagens datum.
			global $Idag;
            
			// Positionera sidfoten 1,5 cm fr�n botten.
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
		// Kolla om det finns n�gra registrerade bes�k.
		$Kollabesok = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']."";
		$Sparadatum = "1";
		
		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om bes�kslistan �r tom s� ber�tta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga bes�k registrerade idag.',0,1);
		else {
		  
            // Skriv ut dagens bes�ks- och utl�ningsantal.
            $pdf->SetFont('Arial','B',10);
    		$pdf->Cell (0,0,'Bes�k idag: '.$Antalbesok.' - Utl�n idag: '.$Antallan.'',0,1,'C');
    		$pdf->Ln(3);
 
             // L�s datum fr�n databasen besokslistan.
            while ($row = mysqli_fetch_array($result)) {
            $Besoksdatum	= $row["DATUM"];
                       			
    			// Visa varje datum bara en g�ng.
    			if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
    			
    				// L�s bes�kare i databasen besokslistan fr�n detta datum.
    				$Hamtabesokare = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND BESOKSID != $Besoksid";
    				
    				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
    				if (!$resultbesok = mysqli_query($db,$Hamtabesokare)) {
    					die('Invalid query: ' . mysqli_error($db));
    				}
    					
    				// L�s medlemsnummer fr�n databasen besokslistan.
    				while ($row = mysqli_fetch_array($resultbesok)) {
    					$Besoksdatum	= $row["DATUM"];
    					$Besoksmedlem	= $row["MEDLEM"];
                        if (substr($Besoksmedlem,0,2) == "tj") $Besoksmedlem = substr($Besoksmedlem,2);
    					$Sparadatum = $Besoksdatum;
    					
    					// H�mta information fr�n databasen medlem.
    					$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";
    
    					// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    					if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    						die('Invalid query: ' . mysqli_error($db));
    					}
    
    					// L�s data om brodern i databasen medlem.
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
    
    						// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
    						$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";
    
    						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    						if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
    							die('Invalid query: ' . mysqli_error($db));
    						}
    
    						// L�s in logens namn fr�n databasen arbetsenheter.
    						while ($row = mysqli_fetch_array($Logeresult)) {
    							$MEDLEMLOGE = $row["ENHETNAMN"];
    						}
    
    						$LANADEBOCKER = "";
    						
    						// Kontrollera om l�ntagaren l�nat n�got idag.
    						$Hamtalan = "SELECT * FROM aktiva WHERE MEDLEM = $MEDLEMNR AND DATUM = $Besoksdatum";
    
    						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    						if (!$Lanresult = mysqli_query($db,$Hamtalan)) {
    							die('Invalid query: ' . mysqli_error($db));
    						}
    
    						// L�s in bokens namn fr�n databasen aktiva.
    						while ($row = mysqli_fetch_array($Lanresult)) {
    							$LANADBOK = $row["TITELID"];
    							$LANADID = $row["BOKID"];
    						
    							$HamtaEAN = "SELECT * FROM bocker WHERE BOKID = $LANADID";
    							// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
    							if (!$eanresult = mysqli_query($db,$HamtaEAN)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    							
    							// L�s information fr�n databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($eanresult)) {
    								$EAN		= $row["EAN"];
    							}
    							
    							$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $LANADBOK";
    							
    							// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
    							if (!$titelresult = mysqli_query($db,$Listatitlar)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    			
    							// L�s information fr�n databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($titelresult)) {
    								$TITELID		= $row["TITELID"];
    								$KOD			= $row["KOD"];
    								$TITEL			= $row["TITEL"];
    								$FORFATTARE		= $row["FORFATTARE"];
    
    								// Kontrollera om data saknas och hantera det i s� fall.
    								if (empty($TITEL)) $TITEL = "**Titel saknas**";
    								if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
    								
                                    // Kolla om KOD inneh�ller n�gra inledande nollor och plocka bort dem i s� fall.
                                    if (!empty($KOD)) {
                                        
                                        $KOD = str_replace(":00",":",$KOD);
                                        $KOD = str_replace(":0",":",$KOD);
                                        $KOD = str_replace(" 00"," ",$KOD);
                                        $KOD = str_replace(" 0"," ",$KOD);
                                    }
								
    								$LANADEBOCKER = "$LANADEBOCKER$KOD \"$TITEL\" *$EAN*\n";
    							}
    						}
    						
    						// Kontrollera om l�ntagaren speciall�nat n�got idag.
    						$Hamtalan = "SELECT * FROM special WHERE SMEDLEM = $MEDLEMNR AND SDATUM = $Besoksdatum";
    
    						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    						if (!$Lanresult = mysqli_query($db,$Hamtalan)) {
    							die('Invalid query: ' . mysqli_error($db));
    						}
    
    						// L�s in bokens namn fr�n databasen special.
    						while ($row = mysqli_fetch_array($Lanresult)) {
    							$LANADBOK = $row["STITELID"];
    							$LANADID = $row["SBOKID"];
    						
    							$HamtaEAN = "SELECT * FROM bocker WHERE BOKID = $LANADID";
    							// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
    							if (!$eanresult = mysqli_query($db,$HamtaEAN)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    							
    							// L�s information fr�n databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($eanresult)) {
    								$EAN		= $row["EAN"];
    							}
    							
    							$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $LANADBOK";
    							
    							// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
    							if (!$titelresult = mysqli_query($db,$Listatitlar)) {
    								die('Invalid query: ' . mysqli_error($db));
    							}
    			
    							// L�s information fr�n databasen litteratur om utvald titel.
    							while ($row = mysqli_fetch_array($titelresult)) {
    								$TITELID		= $row["TITELID"];
    								$KOD			= $row["KOD"];
    								$TITEL			= $row["TITEL"];
    								$FORFATTARE		= $row["FORFATTARE"];
    
    								// Kontrollera om data saknas och hantera det i s� fall.
    								if (empty($TITEL)) $TITEL = "**Titel saknas**";
    								if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";

                                    // Kolla om KOD inneh�ller n�gra inledande nollor och plocka bort dem i s� fall.
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
    					
    					// Skriv ut en rad med bes�kande broderns medlemsnummer, namn, grad och loge.
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
                
                // Skriv ut en lista p� dagens returer.
                $Listareturer = "SELECT * FROM returer WHERE RETUREAN LIKE '$BIBLIOTEKNR%' AND RETURDATUM = '$Serveridag'";

        		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
        		if (!$resultreturer = mysqli_query($db,$Listareturer)) {
        			die('Invalid query: ' . mysqli_error($db));
        		} 

        		// Om listan �r tom s� ber�tta det.
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
                            
                    // L�s information om dagens returer.
                    while ($row = mysqli_fetch_array($resultreturer)) {
                        $RETURTITELID = $row["RETURTITELID"];
                        $RETUREAN = $row["RETUREAN"];

						$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = $RETURTITELID";
                        		
						// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
						if (!$titelresult = mysqli_query($db,$Listatitlar)) {
							die('Invalid query: ' . mysqli_error($db));
						}
    			
						// L�s information fr�n databasen litteratur om utvald titel.
						while ($row = mysqli_fetch_array($titelresult)) {
    						$KOD			= utf8_decode($row["KOD"]);
    						$TITEL			= utf8_decode($row["TITEL"]);
        
    						// Kontrollera om data saknas och hantera det i s� fall.
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
        // Visa lite l�mplig statistik f�r �ret.
		$Datumett	= mktime(0,0,0,1,1,$Serverar);
		$Datumtva	= mktime(0,0,0,12,31,$Serverar);

    	// R�kna efter hur m�nga bes�k det varit i biblioteket i �r.
    	$Raknabesok	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM >= '$Datumett' AND DATUM <= '$Datumtva'";
    	$Besokresult = mysqli_query($db,$Raknabesok);
    	$Antalbesok	= mysqli_num_rows($Besokresult);

        // R�kna efter hur m�nga bes�kare av respektive grad som varit i biblioteket i �r.
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
        
		// R�kna efter hur m�nga l�n som gjorts i biblioteket i �r.
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
                		
    	// R�kna efter hur m�nga b�cker som l�mnats tillbaka i biblioteket under �ret.
    	$Raknareturer	= "SELECT * FROM returer WHERE RETURBIBLIOTEK = $BIBLIOTEKNR AND RETURDATUM >= '$Datumett' AND RETURDATUM <= '$Datumtva'";
    	$Returerresult = mysqli_query($db,$Raknareturer);
    	$Antalreturer	= (mysqli_num_rows($Returerresult));

        $pdf->SetFont('Arial','',13);
		$pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,'Under �ret '.$Serverar.' har f�ljande h�ndelser registrerats i biblioteket:',0,1);
        $pdf->Ln(8);
        $pdf->SetFont('Arial','',16);
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,''.$Antalbesok.' bes�k av '.$Antalbesoktotalt.' br�der enligt f�ljande:',0,1);
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
        $pdf->Cell(0,0,'Observera att ovanst�ende g�ller br�dernas grad idag - inte n�r bes�ken gjordes.',0,1);
        $pdf->Ln(8);   
        $pdf->SetFont('Arial','',16);        

        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,''.$Totalutlan.' utl�nade b�cker.',0,1);
        $pdf->Ln(8);           
        
        $pdf->Cell(7,5,'',0,0);
        $pdf->Cell(0,0,''.$Antalreturer.' �terl�mnade b�cker.',0,1);
        $pdf->Ln(8);           
	}
    
    elseif ($Steg == "Makulerade") {
        // Kolla om det finns n�gra makulerade b�cker.
        $Kollamakulerade = "SELECT * FROM makulerade WHERE MAKEAN LIKE '$BIBLIOTEKNR%' ORDER BY MAKDATUM";
        if ($_SERVER['REMOTE_USER'] == "9999") $Kollamakulerade = "SELECT * FROM makulerade ORDER BY MAKDATUM";

		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Kollamakulerade)) {
			die('Invalid query: ' . mysqli_error($db));
		} 

		// Om listan �r tom s� ber�tta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga makulerade b�cker registrerade.',0,1);
        else {
            
            $pdf->SetFont('Arial','I',9);
		    $pdf->Cell(7,5,'',0,0);
        	$pdf->Cell(20,0,'Datum',0,0);
        	$pdf->Cell(20,0,'Streckkod',0,0);
	        $pdf->Cell(0,0,'Titel',0,1);
        	$pdf->Ln(3);

            // L�s information om makulerade titlar.
            while ($row = mysqli_fetch_array($result)) {
                $Makdatum = $row["MAKDATUM"];
                $Maktitelnr = $row["MAKTITEL"];
                $Makean = $row["MAKEAN"];
                
                $Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = $Maktitelnr";
   				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
       			if (!$resulttitel = mysqli_query($db,$Hamtatitel)) {
	      			die('Invalid query: ' . mysqli_error($db));
		     	}
                
                // L�s data om titeln i databasen litteratur.
					while ($row = mysqli_fetch_array($resulttitel)) {
						$TITEL			= $row["TITEL"];
                        $KOD            = $row["KOD"];

                        // Kolla om KOD inneh�ller n�gra inledande nollor och plocka bort dem i s� fall.
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
        // Kolla om det finns n�gra makulerade b�cker.
        $Kollaspecial = "SELECT * FROM special WHERE SBIBLIOTEK = $BIBLIOTEKNR ORDER BY SDATUM ASC";
        if ($_SERVER['REMOTE_USER'] == "9999") $Kollaspecial = "SELECT * FROM special ORDER BY SDATUM ASC";

		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Kollaspecial)) {
			die('Invalid query: ' . mysqli_error($db));
		} 

		// Om listan �r tom s� ber�tta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga speciall�n registrerade.',0,1);
        else {
            // Skriv ut rubriker.
            $pdf->SetFont('Arial','I',9);
		          		$pdf->Cell(7,5,'',0,0);
        				$pdf->Cell(20,0,'Datum',0,0);
        				$pdf->Cell(20,0,'Streckkod',0,0);
        				$pdf->Cell(30,0,'L�ntagare',0,0);
	           			$pdf->Cell(0,0,'Titel',0,1);
        				$pdf->Ln(3);
                        
            // L�s information om speciall�nade titlar.
            while ($row = mysqli_fetch_array($result)) {
                $Datum = $row["SDATUM"];
                $Stitel = $row["STITELID"];
                $Slantagare = $row["SMEDLEM"];
                $Ansvarig = $row["SANSVARIG"];
                $Sbokid = $row["SBOKID"];
                
                // R�tta till medlemsnummer.
                $Ansvarig = substr($Ansvarig,0,4)."-".substr($Ansvarig,4);
#                $Lantagare = substr($Slantagare,0,4)."-".substr($Slantagare,4);

                // H�mta information fr�n databasen medlem.
    			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Slantagare";
    
    			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    			if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
    
    			// L�s data om brodern i databasen medlem.
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
    
    				// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
    				$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";
    
    				// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    				if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
    					die('Invalid query: ' . mysqli_error($db));
    				}
    
    				// L�s in logens namn fr�n databasen arbetsenheter.
    				while ($row = mysqli_fetch_array($Logeresult)) {
    					$MEDLEMLOGE = $row["ENHETNAMN"];
    				}
                    
      				// H�mta bokens streckkod fr�n databasen bocker.
    				$Hamtaean = "SELECT * FROM bocker WHERE BOKID = $Sbokid";
    
    				// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
    				if (!$Eanresult = mysqli_query($db,$Hamtaean)) {
    					die('Invalid query: ' . mysqli_error($db));
    				}
    
    				// L�s in logens namn fr�n databasen arbetsenheter.
    				while ($row = mysqli_fetch_array($Eanresult)) {
    					$EAN = $row["EAN"];
    				}
                }
                
                // Skapa en str�ng av l�ntagarens info.
                $Lantagare = utf8_decode($MEDLEMFNAMN." ".$MEDLEMNAMN);
                
                $Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = $Stitel";
   				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
       			if (!$resulttitel = mysqli_query($db,$Hamtatitel)) {
	      			die('Invalid query: ' . mysqli_error($db));
		     	}
                
                // L�s data om titeln i databasen litteratur.
					while ($row = mysqli_fetch_array($resulttitel)) {
						$TITEL			= $row["TITEL"];
                        $KOD            = $row["KOD"];

                        // Kolla om KOD inneh�ller n�gra inledande nollor och plocka bort dem i s� fall.
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
		// Kolla om det finns n�gra registrerade bes�k i �r.
		$Datumett	= mktime(0,0,0,1,1,date("Y"));
		$Datumtva	= mktime(0,0,0,12,31,date("Y"));
		$Kollabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM > $Datumett AND DATUM < $Datumtva ORDER BY DATUM ASC";
		$Sparadatum = 1;
		$Rader = 1;
		
		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Kollabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		// Om bes�kslistan �r tom s� ber�tta det.
		if (!mysqli_num_rows($result)) $pdf->Cell(0,10,'Det finns inga bes�k registrerade idag.',0,1);
		else {
			// L�s datum fr�n databasen besokslistan.
			while ($row = mysqli_fetch_array($result)) {
				$Besoksdatum	= $row["DATUM"];
				
				// Visa varje datum bara en g�ng.
				if ($Sparadatum == 1 || $Sparadatum <> $Besoksdatum) {
				
				// H�mta information om kv�llens tj�nstg�rande bibliotekarie.
				$Hamtabibliotekarie = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = $BIBLIOTEKNR AND MEDLEM LIKE 'tj%' ORDER BY BESOKSID DESC LIMIT 1";

				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$resultbibliotekarie = mysqli_query($db,$Hamtabibliotekarie)) {
					die('Invalid query: ' . mysqli_error($db));
				}

				// L�s medlemsnummer fr�n databasen besokslistan.
				while ($row = mysqli_fetch_array($resultbibliotekarie)) {
					$Besoksmedlem	= substr($row["MEDLEM"],2);
                    $Besoksid       = $row["BESOKSID"];
								
					// H�mta information fr�n databasen medlem.
					$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";

					// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
					if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
						die('Invalid query: ' . mysqli_error($db));
					}

					// L�s data om brodern i databasen medlem.
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

						// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
						$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";

						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
						if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
							die('Invalid query: ' . mysqli_error($db));
						}

						// L�s in logens namn fr�n databasen arbetsenheter.
						while ($row = mysqli_fetch_array($Logeresult)) {
							$MEDLEMLOGE = $row["ENHETNAMN"];
						}
					}
				}
				$BIBLIOTEKARIE = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN, $MEDLEMGRAD $MEDLEMLOGE");
				$Tjanstgorande = $Besoksmedlem;
				
				// L�s bes�kare i databasen besokslistan fr�n detta datum.
				$Hamtabesokare = "SELECT * FROM besokslistan WHERE DATUM = $Besoksdatum AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND BESOKSID != '$Besoksid'";
					
				// Skriv ut rubrik.
				if ($Rader >= 33) {
					$Rader = 1;
					$pdf->AddPage();
				}
				$pdf->SetFont('Arial','B',10);
				$pdf->Cell(7,5,'',0,0);
				$pdf->Cell(20,0,date('Y-m-d',$Besoksdatum),0,0);
				$pdf->Cell(0,0,'Tj�nstg�rande: '.$BIBLIOTEKARIE.'',0,1);
				$pdf->Ln(3);
				$Rader = $Rader + 2;
				
				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$resultbesok = mysqli_query($db,$Hamtabesokare)) {
					die('Invalid query: ' . mysqli_error($db));
				}
						
				// L�s medlemsnummer fr�n databasen besokslistan.
				while ($row = mysqli_fetch_array($resultbesok)) {
					$Besoksdatum	= $row["DATUM"];
					$Besoksmedlem	= $row["MEDLEM"];
                    if (substr($Besoksmedlem,0,2) == "tj") $Besoksmedlem = substr($Besoksmedlem,2);
					$Sparadatum = $Besoksdatum;
						
					// H�mta information fr�n databasen medlem.
					$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";

					// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
					if (!$resultmedlem = mysqli_query($db,$Hamtamedlem)) {
						die('Invalid query: ' . mysqli_error($db));
					}

					// L�s data om brodern i databasen medlem.
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

						// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
						$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";

						// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
						if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
							die('Invalid query: ' . mysqli_error($db));
						}

						// L�s in logens namn fr�n databasen arbetsenheter.
						while ($row = mysqli_fetch_array($Logeresult)) {
							$MEDLEMLOGE = $row["ENHETNAMN"];
						}
							
						$Besoksnamn = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN");
						$Besoksgrad = utf8_decode("$MEDLEMGRAD");
						$Besoksloge = utf8_decode("$MEDLEMLOGE");
					}				
						
					// Skriv ut en rad med bes�kande broderns medlemsnummer, namn, grad och loge.
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
		// H�mta utl�nade titlar fr�n databasen litteratur sorterat efter grad.
#		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".$_SESSION['Bibliotek'].date("Y")."%'";
		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".date("Y")."%'";
		$Rader = 1;
		
		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Inga utl�nade b�cker under �ret',0,0);
		else {
			// L�s information fr�n databasen litteratur om utvald titel.
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
        
				// Skriv ut graden i klartext.
				if ($GRAD == "0") $GRAD = "�ppen";
				elseif ($GRAD == "1") $GRAD = "I";
				elseif ($GRAD == "2") $GRAD = "II";
				elseif ($GRAD == "3") $GRAD = "III";
				elseif ($GRAD == "4") $GRAD = "IV-V";
				elseif ($GRAD == "6") $GRAD = "VI";
				elseif ($GRAD == "7") $GRAD = "VII";
				elseif ($GRAD == "8") $GRAD = "VIII";
				elseif ($GRAD == "9") $GRAD = "IX";
				elseif ($GRAD == "10") $GRAD = "X";

				// Kontrollera om titeln tillh�r studieplanen och skriv i s� fall ut det i klartext.
				if ($STUDIEPLAN == "B") $STUDIEPLAN = "Studieplan: Bas";
				if ($STUDIEPLAN == "K") $STUDIEPLAN = "Studieplan: Komplettering";
				if ($STUDIEPLAN == "F") $STUDIEPLAN = "Studieplan: F�rdjupning";		

				// Kontrollera om det finns n�gra b�cker kopplade till titeln.
				$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$bokresult = mysqli_query($db,$Listabocker)) {
					die('Invalid query: ' . mysqli_error($db));
				} 
				$LANARRAY = unserialize($LANARRAY);
				$LANLOKALT = $LANARRAY[date("Y")];
				if(!empty($KOD)) $KOD = $KOD." ";
				// L�s information fr�n databasen bocker om aktuell titel.
				$Raknabocker = mysqli_num_rows($bokresult);
				$Lanerad = utf8_decode($KOD)."\"".utf8_decode($TITEL)."\" ".$UTGIVNINGSAR."\nF�rfattare: ".utf8_decode($FORFATTARE);
				if (!empty($STUDIEPLAN)) $Lanerad = $Lanerad."\n$STUDIEPLAN";
				$Lokallan[$i] = array(LaneID => $i, Lanerad => "$Lanerad", GRAD => "$GRAD", LANLOKALT => $LANLOKALT);
				$i = $i+1;
			}
			// Dra bort ett fr�n $i f�r att kompensera den sista ettan som lagts till.
			$i = $i-1;

			// Funktion f�r att underl�tta sorteringen av de lokala l�nen.
			function compare($x, $y){
				if ( $x['LANLOKALT'] == $y['LANLOKALT'] ) return 0; 
				elseif ( $x['LANLOKALT'] > $y['LANLOKALT'] )  return -1; 
				else return 1;
			}
			// Sortera de lokala l�nen.
			usort($Lokallan, "compare");
				
			for ($n=0; $n<=$i; $n++) {
				// Skriv ut titeln p� en rad i tabellen och forts�tt till n�sta titel.
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
		// H�mta utl�nade titlar fr�n databasen litteratur.
		$Listatitlar = "SELECT * FROM litteratur WHERE LANARRAY LIKE '%".$_SESSION['Bibliotek'].date("Y")."%'";
		$Rader = 1;
		
		// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		if (!mysqli_num_rows($result)) $this->Cell (0,0,'Inga utl�nade b�cker under �ret',0,0);
		else {
			// L�s information fr�n databasen litteratur om utvald titel.
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
				
				// Skriv ut graden i klartext.
				if ($GRAD == "0") $GRAD = "�ppen";
				elseif ($GRAD == "1") $GRAD = "I";
				elseif ($GRAD == "2") $GRAD = "II";
				elseif ($GRAD == "3") $GRAD = "III";
				elseif ($GRAD == "4") $GRAD = "IV-V";
				elseif ($GRAD == "6") $GRAD = "VI";
				elseif ($GRAD == "7") $GRAD = "VII";
				elseif ($GRAD == "8") $GRAD = "VIII";
				elseif ($GRAD == "9") $GRAD = "IX";
				elseif ($GRAD == "10") $GRAD = "X";

				// Kontrollera om titeln tillh�r studieplanen och skriv i s� fall ut det i klartext.
				if ($STUDIEPLAN == "B") $STUDIEPLAN = "Studieplan: Bas";
				if ($STUDIEPLAN == "K") $STUDIEPLAN = "Studieplan: Komplettering";
				if ($STUDIEPLAN == "F") $STUDIEPLAN = "Studieplan: F�rdjupning";		

				// Kontrollera om det finns n�gra b�cker kopplade till titeln.
				$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$bokresult = mysqli_query($db,$Listabocker)) {
					die('Invalid query: ' . mysqli_error($db));
				} 
				$LANARRAY = unserialize($LANARRAY);
				$LANLOKALT = $LANARRAY[$_SESSION['Bibliotek'].date("Y")];
				if(!empty($KOD)) $KOD = $KOD." ";
				// L�s information fr�n databasen bocker om aktuell titel.
				$Raknabocker = mysqli_num_rows($bokresult);
				$Lanerad = utf8_decode($KOD)."\"".utf8_decode($TITEL)."\" ".$UTGIVNINGSAR."\nF�rfattare: ".utf8_decode($FORFATTARE);
				if (!empty($STUDIEPLAN)) $Lanerad = $Lanerad."\n$STUDIEPLAN";
				$Lokallan[$i] = array(LaneID => $i, Lanerad => "$Lanerad", GRAD => "$GRAD", LANLOKALT => $LANLOKALT);
				$i = $i+1;
			}
			// Dra bort ett fr�n $i f�r att kompensera den sista ettan som lagts till.
			$i = $i-1;

			// Funktion f�r att underl�tta sorteringen av de lokala l�nen.
			function compare($x, $y){
				if ( $x['LANLOKALT'] == $y['LANLOKALT'] ) return 0; 
				elseif ( $x['LANLOKALT'] > $y['LANLOKALT'] )  return -1; 
				else return 1;
			}
			// Sortera de lokala l�nen.
			usort($Lokallan, "compare");
				
			for ($n=0; $n<=$i; $n++) {
				// Skriv ut titeln p� en rad i tabellen och forts�tt till n�sta titel.
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
		
		// L�s in informationen om l�ntagaren.
		$Hamtalantagare = "SELECT * FROM medlem WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";

		// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
		if (!$resultmedlem = mysqli_query($db,$Hamtalantagare)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// L�s data om brodern i databasen medlem.
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

		// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
		$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $MEDLEMLOGE";

		// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
		if (!$Logeresult = mysqli_query($db,$Hamtaloge)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// L�s in logens namn fr�n databasen arbetsenheter.
		while ($row = mysqli_fetch_array($Logeresult)) {
			$MEDLEMLOGE = $row["ENHETNAMN"];
		}		

		$Besoksnamn = utf8_decode("$MEDLEMFNAMN $MEDLEMNAMN");
		$Besoksgrad = utf8_decode("$MEDLEMGRAD");
		$Besoksloge = utf8_decode("$MEDLEMLOGE");
		$Medlemsrad = "$MEDLEM $Besoksnamn$Besoksgrad\n$Besoksloge\n\n";
		
		// Skriv ut l�ntagarens medlemsnummer, namn, grad och loge.
		$pdf->Cell(7,5,'',0,0);
		$pdf->MultiCell(0,4,$Medlemsrad,0,1);
			
		// Visa den aktuelle l�ntagarens p�g�ende l�n.
		$Visalan = "SELECT * FROM aktiva WHERE MEDLEM = ".$_SESSION['Lantagaremedlem']."";

		// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
		if (!$result = mysqli_query($db,$Visalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// L�s data om utl�nade b�cker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["LANID"];
			$BOKID			= $row["BOKID"];
			$TITELID		= $row["TITELID"];
			$DATUM			= $row["DATUM"];
			
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
				$TITEL			= utf8_decode($row["TITEL"]);
				$FORFATTARE		= utf8_decode($row["FORFATTARE"]);
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];

				// Kontrollera om data saknas och hantera det i s� fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F�rfattare saknas**";
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

				// L�s streckkoden som �r kopplad till boken.
				$Lasstreckkod = "SELECT * FROM bocker WHERE BOKID = '$BOKID'";
			
				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$eanresult = mysqli_query($db,$Lasstreckkod)) {
					die('Invalid query: ' . mysqli_error($db));
				}
				
				// L�s information fr�n databasen bocker om aktuell titel.
				while ($row = mysqli_fetch_array($eanresult)) {
					$EAN			= $row["EAN"];
				}
				
				// Kontrollera �terl�mningsdatum f�r boken = utl�mningsdag + 30 dagar (2592000 sekunder).
				$Tillbakadatum = $DATUM+2592000;
				
				// Skriv ut raden med information.
				$Lanerad = "\n$KOD \"$TITEL\"$UTGIVNINGSAR\nF�rfattare: $FORFATTARE\nUtl�nad ".date('Y-m-d',$DATUM).".\nTillbakal�mnas senast ".date('Y-m-d',$Tillbakadatum).".\n\n";
				
				$pdf->Cell(7,5,'',0,0);				
				$pdf->MultiCell(0,4,$Lanerad,1,1);
				$pdf->ln();				

			}
		}
		// Visa den aktuelle l�ntagarens p�g�ende speciall�n.
		$Visalan = "SELECT * FROM special WHERE SMEDLEM = ".$_SESSION['Lantagaremedlem']."";

		// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
		if (!$result = mysqli_query($db,$Visalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// L�s data om utl�nade b�cker i databasen aktiva.
		while ($row = mysqli_fetch_array($result)) {
			$LANID			= $row["SLANID"];
			$BOKID			= $row["SBOKID"];
			$TITELID		= $row["STITELID"];
			$DATUM			= $row["SDATUM"];
			
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
				$TITEL			= utf8_decode($row["TITEL"]);
				$FORFATTARE		= utf8_decode($row["FORFATTARE"]);
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$REVIDERAD		= $row["REVIDERAD"];

				// Kontrollera om data saknas och hantera det i s� fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F�rfattare saknas**";
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

				// L�s streckkoden som �r kopplad till boken.
				$Lasstreckkod = "SELECT * FROM bocker WHERE BOKID = '$BOKID'";
			
				// Skicka en beg�ran till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$eanresult = mysqli_query($db,$Lasstreckkod)) {
					die('Invalid query: ' . mysqli_error($db));
				}
				
				// L�s information fr�n databasen bocker om aktuell titel.
				while ($row = mysqli_fetch_array($eanresult)) {
					$EAN			= $row["EAN"];
				}
				
				// Kontrollera �terl�mningsdatum f�r boken = utl�mningsdag + 30 dagar (2592000 sekunder).
				$Tillbakadatum = $DATUM+2592000;
				
				// Skriv ut raden med information.
				$Lanerad = "\n$KOD \"$TITEL\"$UTGIVNINGSAR\nF�rfattare: $FORFATTARE\nUtl�nad ".date('Y-m-d',$DATUM).".\nSpeciall�n.\n\n";
				
				$pdf->Cell(7,5,'',0,0);				
				$pdf->MultiCell(0,4,$Lanerad,1,1);
				$pdf->ln();				

			}
		}
	}
	$pdf->Output();
?>