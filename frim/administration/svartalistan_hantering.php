<?php
    session_start();
    
    // Starta dokumentet.
    require("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";

    // Visa bibliotekets exlibris.
    require("../subrutiner/top_exlibris.php");
    
    // Ta reda på vilket steg som ska utföras.    
	$Steg = $_GET['Steg'];

	// Visa bröderna på "svarta listan".
	if ($Steg == "Visa") {
		
		// Funktion för att underlätta sorteringen av de svartlistade bröderna.
		function compare($x, $y){
			if ( $x['Sortera'] == $y['Sortera'] ) return 0; 
			elseif ( $x['Sortera'] < $y['Sortera'] )  return -1; 
			else return 1;
		}
            
		$Svartlistade = "SELECT * FROM svartalistan ORDER BY MEDLEM ASC";
		
		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Svartlistade)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Om "svarta listan" är tom så berätta det.
		if (!mysqli_num_rows($result)) {
			echo "<br /><div class='Rubrik'>Det finns inga svartlistade br&ouml;der registrerade.</div><br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button>";
			exit();
		}

		// Skriv ut rubrik.
		echo "<div class='Rubrik'>Br&ouml;der p&aring; \"Svarta listan\":</div>";
		echo "<table align='center' cellpadding='5' border='1'>";
		echo "<tr><td align='left' valign='top' class='Text'>Namn:</td><td align='left' valign='top' class='Text'>Grad:</td><td align='left' valign='top' class='Text'>Nummer:</td><td align='left' valign='top' class='Text'>Tillhör:</td><td align='left' valign='top' ></td></tr>";
		
        $i = 0;
        
        // Läs in syndande bröder från databasen svartalistan.
		while ($row = mysqli_fetch_array($result)) {
			$Svartmedlem	= $row["MEDLEM"];
			
			// Hämta information från databasen medlem.
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Svartmedlem";

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
				$MEDLEMMAIL		= $row["MEDLEMMAIL"];

				// Konvertera tillbaka medlemsnumret enligt SFMO.
				$MEDLEM = substr($MEDLEM,0,4)."-".substr($MEDLEM,4);

				// Skriv ut medlemsgraden i klartext.
                $Romerskagrader = array("","I","III","III","IV-V","","VI","VII","VIII","IX","X");
                $MEDLEMGRAD = $Romerskagrader[$MEDLEMGRAD];
				
				// Kontrollera om låntagarens epostadress är registrerad.
				if ($MEDLEMMAIL <> "") $MEDLEMMAIL = " <a href='mailto:$MEDLEMMAIL'>Epost</a>";
                else $MEDLEMMAIL = "";

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
						
				$Besoksrad = "<tr><td align='left' valign='top' class='Text'><b>$MEDLEMNAMN, $MEDLEMFNAMN</b>$MEDLEMMAIL</td><td align='left' valign='top' class='Text'>$MEDLEMGRAD</td><td align='left' valign='top' class='Text'>$MEDLEM</td><td align='left' valign='top' class='Text'>$MEDLEMLOGE</td><td align='left' valign='top' ><button type='button' onClick='parent.location=\"svartalistan_hantering.php?Steg=Radera&amp;MEDLEM=$Svartmedlem\"' class='Text'>Ta bort</button></td></tr>";
                $SORTERINGSNAMN[$i] = array(SortID => $i, Sortrad => "$Besoksrad", Sortera => "$MEDLEMNAMN");
            }
            
			// Skriv ut en rad med besökande broderns medlemsnummer, namn, grad och loge.
            
			#echo $Besoksrad;
            $i = $i+1;
		}
    		// Dra bort ett från $i för att kompensera den sista ettan som lagts till.
    		$i = $i-1;

    		// Sortera de lokala lånen om listan inte är tom.
    		if (!$SORTERINGSNAMN == "") usort($SORTERINGSNAMN, "compare");

            for ($n=0; $n<=$i; $n++) {
                
                // Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
    			echo $SORTERINGSNAMN[$n]['Sortrad'];
            }                
					
		// Stäng tabellen och släng dit en Tillbaka-knapp.
		echo "</table><br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
		
	}
	
	// Förlåt broder och ta bort honom från "svarta listan".
	if ($Steg == "Radera") {
	
		// Hämta ID-nummer på vald post på listan och ta bort den.
		$MEDLEM = $_GET["MEDLEM"];
		$Radera = "DELETE FROM svartalistan WHERE MEDLEM = $MEDLEM";
		
		// Skicka begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Radera)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
		
		echo "<div class='Rubrik'>Brodern &auml;r nu bortplockad fr&aring;n den \"svarta listan\".<br /><br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button>";
		
	}
		
	// Spara utvald broder i "svarta listan".
	if ($Steg == "Spara") {
	
		$MEDLEM = $_GET["MEDLEM"];

		// Skicka brodern till databasen svartalistan.
		$Skrivbroder = "INSERT INTO svartalistan (MEDLEM) VALUES ('$MEDLEM')";

		// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Skrivbroder)) {
			die('Invalid query: ' . mysqli_error($db));
		}		
		
		echo "<div class='Rubrik'>Brodern &auml;r nu inlagd p&aring; den \"svarta listan\".<br /><br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button>";
	}
?>
</div>
</body>
</html>