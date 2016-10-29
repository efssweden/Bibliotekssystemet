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
			$BIBLIOTEKNAMN	= utf8_decode("Tillhör ".$row["BIBLIOTEKNAMN"]);
		}

		class PDF extends FPDF {
		}
		
		// Starta sidan.

		// Hämta streckkoden och antalet ark som ska skrivas ut.
		$EAN	= $_GET["EAN"];
		$Ark	= $_GET["Ark"];
        $Size   = $_GET["Size"];

		$pdf=new PDF();
		$pdf->SetMargins(0,4,0);
		$pdf->SetAutoPageBreak(0);
		$pdf->AddFont('code39','','code39.php');
        $Height = $Size-12;
		
		for ($a = 1; $a <= $Ark; $a++){			
			$pdf->AddPage();
			$pdf->SetFont('Times','BI',4);
			$pdf->Link(0,0,209,290,'administrera.php');
            
            // Aktivera den här raden om man skriver ut på 70x36mm-etiketter för att skriva ut en första marginal.
            if ($Size == "36") $pdf->Cell(0,5,"",0,1,'C',0);
            if ($Size == "37") $pdf->Cell(0,0,"",0,1,'C',0);
			
			$Kolumn = 0;
			for ($i = 0; $i <= 23; $i++){
				for ($Kolumn = 0; $Kolumn <= 2; $Kolumn++) {
					$NyEAN = $EAN+$i+$Kolumn;
					$Streckkod = "*".$NyEAN."*";
					$pdf->SetFont('code39','',65);
					if ($Kolumn == 2){
						$pdf->Cell(70,$Height,$Streckkod,0,1,'C');
					}
					else {
						$pdf->Cell(70,$Height,$Streckkod,0,0,'C');
					}
				}
				for ($Namnskylt = 0; $Namnskylt <= 2; $Namnskylt++) {
					$pdf->SetFont('Times','BI',9);
					if ($Namnskylt == 2){
						$pdf->Cell(70,6,$BIBLIOTEKNAMN,0,1,'C');
					}
					else {
						$pdf->Cell(70,6,$BIBLIOTEKNAMN,0,0,'C');
					}
				}
				if ($Size == 36 )$pdf->Cell(70,6,'',0,1,'C');
				if ($Size == 37 )$pdf->Cell(70,6,'',0,1,'C');
				$EAN = $NyEAN-$i;
			}
			$EAN = $EAN-24;
		}		
	$pdf->Output();
?>