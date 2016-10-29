<?php
	session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";

    // Visa bibliotekets exlibris.
    require ("../subrutiner/top_exlibris.php");
    
    // Kolla upp vilket steg som ska tas.
	$Steg = $_GET['Steg'];

	// Sök medlem.
	if ($Steg == "Sok") {
	
		// Öppna ett formulär för sökningen.
		echo "<form action='medlem_hantering.php?Steg=Sokt' name='medlem_hantering' method='post' enctype='application/form-data'>";
		
		// Skriv ut en rubrik och öppna tabellen.
		echo "<div class='Rubrik'>Skriv in efternamnet p&aring; den du s&ouml;ker:</div>";
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		
		// Skriv ut raderna i tabellen.
		echo "<tr><td valign='top' align='left'><input name='NAMN' type='Text' class='Text' size='50' ></td></tr>";
	
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "</table><br /><input type='submit' value='S&ouml;k' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"../medlem.php?Steg=RFID\" '  class='Text'/></form><br />";	

        // Skriv ut en hjälpknapp.
        $HELPID = 4;
        require ("../subrutiner/help.php");

		// Sätt fokus på inmatningsrutan så att markören alltid står där från början.
		echo "<script type='text/javascript'>document.medlem_hantering.NAMN.focus()</script>";		
			
	}
	
	// Visa sökt medlem.
	if ($Steg == "Sokt") {
	
		// Hämta upp söksträngen.
		$Hitta = $_POST['NAMN'];
		
		// Leta efter söksträngen i databasen medlem.
		$Hittamedlem = "SELECT * FROM medlem WHERE MEDLEMNAMN LIKE '%$Hitta%' ORDER BY MEDLEMFNAMN,MEDLEMNAMN ASC";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultmedlem = mysqli_query($db,$Hittamedlem)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		$Antaltraffar	= mysqli_num_rows($resultmedlem);

		// Skriv ut en rubrik och öppna tabellen.
		echo "<div class='Rubrik'>$Antaltraffar resultat f&ouml;r \"$Hitta\":</div>";
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";

		echo "<tr bgcolor='#f1f1f1'><td align='left' valign='top' class='Text'>Namn:</td><td align='left' valign='top' class='Text'>Grad:</td><td align='left' valign='top' class='Text'>Loge:</td><td align='left' valign='top' class='Text'>Ort:</td><td align='left' valign='top' class='Text'>Registrera:</td></tr>";
		// Läs data om brodern i databasen medlem.
		while ($row = mysqli_fetch_array($resultmedlem)) {
			$MEDLEM			= $row["MEDLEM"];
			$MEDLEMNAMN		= $row["MEDLEMNAMN"];
			$MEDLEMFNAMN	= $row["MEDLEMFNAMN"];
			$MEDLEMGRAD		= $row["MEDLEMGRAD"];
			$MEDLEMLOGE		= $row["MEDLEMLOGE"];
			$MEDLEMKORT		= $row["MEDLEMKORT"];
			$MEDLEMORT		= $row["MEDLEMORT"];

			// Konvertera tillbaka medlemsnumret enligt SFMO.
			$MEDLEM = substr($MEDLEM,0,4)."-".substr($MEDLEM,4);
		
			// Skriv ut medlemsgraden i klartext.
            $Romerskgrad = array("","I","III","III","IV-V","","VI","VII","VIII","IX","X");
			$MEDLEMGRAD = $Romerskgrad[$MEDLEMGRAD];

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
			
			// Tillverka en knapp för att registrera en broder som glömt sitt medlemskort.
			$REGISTRERA = "<button type='button' onClick='parent.location=\"../medlem.php?Steg=RFID&amp;Inlogg=$MEDLEMKORT\"' class='Text'>L&aring;na ut bok</button>";
			
			echo "<tr><td align='left' valign='top' class='Text'><b>$MEDLEMFNAMN $MEDLEMNAMN</b></td><td align='left' valign='top' class='Text'>$MEDLEMGRAD</td><td align='left' valign='top' class='Text'>$MEDLEMLOGE</td><td align='left' valign='top' class='Text'>$MEDLEMORT</td>$SVARTLISTA<td align='left' valign='top' class='Text'>$REGISTRERA</td></tr>";
		}				
						
		// Stäng tabellen och skriv ut "Tillbaka"-knapp.
		echo "</table><br /><button type='button' onClick='parent.location=\"../fm.php\"' class='Text'>Tillbaka</button><br /><br />";	
			
	}
	
    // Avsluta dokumentet.
    echo "</div></body></html>";
?>