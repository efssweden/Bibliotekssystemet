<?php
session_start();
require('pdf/fpdf.php');

	// Etablera kontakt med databasen och kolla upp dagens datum.
	require ("../konfiguration/konfig.php");
	$db = mysqli_connect($server, $db_user, $db_pass);
	mysqli_select_db($db,$database);
	
	$Steg 			= $_GET['Steg'];
	$Serverar		= date("Y");
	$Servermanad 	= date("m");
	$Serverdag 		= date("d");
	$Serveridag		= mktime(0,0,0,$Servermanad,$Serverdag,$Serverar);
	$Idag 			= date("Y-m-d",$Serveridag);

	// Hämta information om vilket bibliotek som är igång.
	$BIBLIOTEKNR = $_SESSION['Bibliotek'];
        
	// Läs data om det valda biblioteket.
	$Visabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEKNR";
			
	// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
	if (!$result = mysqli_query($db,$Visabibliotek)) {
		die('Invalid query: ' . mysqli_error($db));
	}
		
	while ($row = mysqli_fetch_array($result)) {
		$BIBLIOTEKNAMN	= utf8_decode($row["BIBLIOTEKNAMN"]);
	}

    // Generera rutiner till FPDF-funktionen.
	class PDF extends FPDF {

		// Generera en funktion för sidhuvudet.
		function Header() {
		  
			// Sätt bibliotekets namn och dagens datum.
			global $BIBLIOTEKNAMN;
			global $Idag;
			
            // Sätt bibliotekes logotyp.
			$Filnamn = "../design/bibliotek/".$_SESSION['Bibliotek'].".jpg";
			if (file_exists($Filnamn)) $Exlibris = "../design/bibliotek/".$_SESSION['Bibliotek'].".jpg";
			else $Exlibris = "../design/bibliotek/Exlibris.jpg";

			// Skriv ut logotypen.
			$this->Image($Exlibris,10,8,33,0,'','administrera.php');
			
            // Bestäm typsnittet till Arial bold 15.
			$this->SetFont('Arial','B',15);
			
            // Flytta till höger.
			$this->Ln(1);
			$this->Cell(38);
			
            // Skriv ut texterna.
			$this->Cell(150,10,$BIBLIOTEKNAMN.' '.$Idag,1,2,'C');
			$this->SetFont('Arial','B',10);
			$this->Cell(150,10,'Inventeringsunderlag',1,0,'C');

			// Avsluta sidhuvudet.
			$this->Ln(20);
			$this->SetDrawColor(0);
			$this->Line(10,38,200,38);
		}

		// Generera en funktion för sidfoten.
		function Footer(){
		  
			// Hämta dagens datum.
			global $Idag;
            
			// Placera sidfoten 1.5 cm från sidans botten.
			$this->SetY(-15);
            
			// Bestäm typsnittet till Arial 8.
			$this->SetFont('Arial','',8);
            
			// Skriv ut sidnumret och antalet sidor.
			$this->Cell(0,10,'Utskriven '.$Idag.'. Sida '.$this->PageNo().' av {nb}',0,0,'C');
		}
	}
    
	// Påbörja PDF-genereringen.
	$pdf=new PDF();
	$pdf->AliasNbPages();

    // Starta en loop som kör igenom alla graderna och ger dem olika rubriker.
    for ($Grad=0; $Grad<=10; $Grad++) {

    	// Visa alla grader utom nummer 5.
        if ($Grad <> "5") {
        $pdf->AddPage();
    	$pdf->Link(0,0,209,290,'administrera.php');
    
    	// Skriv ut graden i klartext.
        $GRADRUBRIK = array("Obunden","I","II","III","IV-V","","VI","VII","VIII","IX","X");
        if ($Grad == 0 )$GRADRUBRIK = $GRADRUBRIK[$Grad]." litteratur i biblioteket";
        else $GRADRUBRIK = "Litteratur i grad ".$GRADRUBRIK[$Grad]." i biblioteket";
        
        // Skriv ut gradrubriken.
    	$pdf->SetFont('Arial','',12);
    	$pdf->Cell(7,5,'',0,0);
    	$pdf->Cell(153,6,$GRADRUBRIK,0,1,'L');    
    	$pdf->SetFont('Arial','',6);
    	$pdf->Cell(7,5,'',0,0);
    	$pdf->Cell(164,4,'Titel',1,0,'L');
    	$pdf->Cell(7,4,'',1,0,'C');
    	$pdf->Cell(10,4,'Sign',1,1,'C');
        
    	// Börja räkna antalet rader på sidan för att veta när det är dags att börja på en ny sida.
        $Rader = 1;
    
        // Hämta information från databasen litteratur.
    	$Hamtatitlar = "SELECT * FROM litteratur WHERE GRAD = $Grad ORDER BY KOD, TITEL ASC";
    
    	// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    	if (!$result = mysqli_query($db,$Hamtatitlar)) {
    		die('Invalid query: ' . mysqli_error($db));
    	} 
    
        // Skriv ut en text om det inte finns några titlar registrerade.
    	if (!mysqli_num_rows($result)) $this->Cell (0,0,'Det finns inga registrerade titlar i systemet.',0,0);

        else {

            // Läs information från databasen litteratur.
    		while ($row = mysqli_fetch_array($result)) {
        		$TITELID		= $row["TITELID"];
        		$TITEL     		= utf8_decode($row["TITEL"]);
        		$KOD     		= utf8_decode($row["KOD"]);
        		$FORFATTARE		= utf8_decode($row["FORFATTARE"]);
                $GRAD           = $row["GRAD"];
        
        		// Kontrollera om data saknas och hantera det i så fall.
        		if (empty($TITEL)) $TITEL = "**Titel saknas**";
                if (strlen($TITEL) >= 85) $TITEL = substr($TITEL,0,95)."...";
                if (!empty($KOD)) {
                    $KOD = str_replace(":00",":",$KOD);
                    $KOD = str_replace(":0",":",$KOD);
                    $KOD = str_replace(" 00"," ",$KOD);
                    $KOD = str_replace(" 0"," ",$KOD);
                }
                if (!empty($KOD)) $KOD = "$KOD ";
                if (empty($FORFATTARE)) $FORFATTARE = "***";
                
                $TITELRAD = $KOD.$TITEL." - ".$FORFATTARE;
                if (strlen($TITELRAD) >= 110) $TITELRAD = substr($TITELRAD,0,110)."...";

                // Kontrollera om titeln finns i biblioteket.
        		$Kontrollerabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN LIKE '".$_SESSION['Bibliotek']."%'";
    
                // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
        		if (!$kontrollresult = mysqli_query($db,$Kontrollerabocker)) {
        			die('Invalid query: ' . mysqli_error($db));
        		}
                
                $Raknakontrolleradebocker = mysqli_num_rows($kontrollresult);
                if ($Raknakontrolleradebocker == 0) $Raknakontrolleradebocker = 0;
                else {
                    
               		// Kontrollera om det är dags att byta sida och om så skapa en ny sida och skriv ut sidhuvudet igen.
           			if ($Rader >= 56) {
           				$Rader = 1;
           				$pdf->AddPage();
                      	$pdf->Link(0,0,209,290,'administrera.php');
                       	$pdf->SetFont('Arial','',12);
                    	$pdf->Cell(7,5,'',0,0);
                    	$pdf->Cell(153,6,$GRADRUBRIK,0,1,'L');    
                    	$pdf->SetFont('Arial','',6);
                    	$pdf->Cell(7,5,'',0,0);
                    	$pdf->Cell(164,4,'Titel',1,0,'L');
                    	$pdf->Cell(7,4,'',1,0,'C');
                    	$pdf->Cell(10,4,'Sign',1,1,'C');
           			}
                
                    // Skriv först ut en rad med titeln.
                    $pdf->SetFont('Arial','B',8);
               		$pdf->Cell(7,5,'',0,0);
               		$pdf->Cell(164,4,$TITELRAD,1,0,'L');
               		$pdf->Cell(7,4,$Raknakontrolleradebocker.' st',1,0,'C');
               		$pdf->Cell(10,4,'',1,1,'C');
               		$Rader = $Rader + 1;
                
                    // Visa alla böcker med denna titel.
            		$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN LIKE '".$_SESSION['Bibliotek']."%'";
        
                    // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
            		if (!$bokresult = mysqli_query($db,$Listabocker)) {
            			die('Invalid query: ' . mysqli_error($db));
            		}
                
                    // Läs in streckkoden och skriv ut raden.
                    while ($row = mysqli_fetch_array($bokresult)) {
                        $EAN = $row["EAN"];
                        $BOKID = $row["BOKID"];
                        
                        // Kontrollera om boken är utlånad som vanligt lån.
                        $Listalan = "SELECT * FROM aktiva WHERE BOKID = '$BOKID' ";
                        
                        // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
                		if (!$lanresult = mysqli_query($db,$Listalan)) {
                			die('Invalid query: ' . mysqli_error($db));
                		}
                        
                        $Raknalan = mysqli_num_rows($lanresult);
                        $BOKMEDLEM = "";
                        
                        // Om boken är utlånad så säg det och berätta vem som lånat boken och när.
                        if ($Raknalan == 1) {
                            $Status = "U";
                            
                            while ($row = mysqli_fetch_array($lanresult)) {
                                $MEDLEM = $row["MEDLEM"];
                                $DATUM  = $row["DATUM"];
                            }
                            
                            $BOKMEDLEM = " utlånad till medlem ".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." den ".date("Y-m-d",$DATUM)."";
                            $BOKMEDLEM = utf8_decode($BOKMEDLEM);
                            
                        }
                        
                        // Kontrollera om det finns några speciallån av denna titel.
                        $Listaspecial = "SELECT * FROM special WHERE SBOKID = '$BOKID' ";
        
                        // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
                        if (!$specialresult = mysqli_query($db,$Listaspecial)) {
                            die('Invalid query: ' . mysqli_error($db));
                        }
                            
                        $Raknaspecial = mysqli_num_rows($specialresult);
                        $BOKMEDLEM = "";
                            
                        // Om boken är specialutlånad så säg det.
                        if ($Raknaspecial == 1) {
                            $Status = "S";
                               
                        while ($row = mysqli_fetch_array($specialresult)) {
                            $MEDLEM = $row["SMEDLEM"];
                            $DATUM  = $row["SDATUM"];
                        }
                            
                        $BOKMEDLEM = " specialutlånad till medlem ".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)." den ".date("Y-m-d",$DATUM)."";
                        $BOKMEDLEM = utf8_decode($BOKMEDLEM);            
                        }
                    
                        // Om boken inte alls är utlånad så töm alla sådana strängar.
                        if ($Raknalan == 0 && $Raknaspecial == 0) {
                            $Inne = "X";
                            $Status = "";
                            $BOKMEDLEM = "";
                        }
    
                   		// Kontrollera om det är dags att byta sida och om så skapa en ny sida och skriv ut sidhuvudet igen.
               			if ($Rader >= 57) {
               				$Rader = 1;
               				$pdf->AddPage();
                          	$pdf->Link(0,0,209,290,'administrera.php');
                           	$pdf->SetFont('Arial','',12);
                        	$pdf->Cell(7,5,'',0,0);
                        	$pdf->Cell(153,6,$GRADRUBRIK,0,1,'L');    
                        	$pdf->SetFont('Arial','',6);
                        	$pdf->Cell(7,5,'',0,0);
                        	$pdf->Cell(164,4,'Titel',1,0,'L');
                        	$pdf->Cell(7,4,'',1,0,'C');
                        	$pdf->Cell(10,4,'Sign',1,1,'C');
               			}
                         
               			// Skriv ut raden.	
               			$pdf->SetFont('Arial','',8);
               			$pdf->Cell(7,5,'',0,0);
               			$pdf->Cell(164,4,' *'.$EAN.'*'.$BOKMEDLEM,1,0,'L');
               			$pdf->Cell(7,4,$Status,1,0,'C');
               			$pdf->Cell(10,4,'',1,1,'C');
               			$Rader = $Rader + 1;
                    }
                }
            }
        }
    }
}	
	
$pdf->Output();
			
?>