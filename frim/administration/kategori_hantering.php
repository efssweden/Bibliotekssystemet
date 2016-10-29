<?php
    session_start();

    // Starta dokumentet.
    require ("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";

    // Visa bibliotekets exlibris.
    require ("../subrutiner/top_exlibris.php");

	$Steg = $_GET['Steg'];
	$BIBLIOTEK = $_SESSION['Bibliotek'];
		
		
		
	// Lista kategorier.
	if ($Steg == "Lista") {

		// Hämta utlånade kategorier från databasen kategorier.
		$Listakategorier = "SELECT * FROM kategorier ORDER BY KATEGORI ASC";
		
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Listakategorier)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		if (!mysqli_num_rows($result)) $Output = "<br /><div class='Rubrik'>Det finns inga registrerade kategorier.</div>";
		else {
			// Skapa en tabell över registrerade kategorier.
			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
			$Utlanrubrik = "Dessa kategorier &auml;r just nu registrerade:";
			echo "<br /><div class='Rubrik'>$Utlanrubrik</div><table align='center' cellpadding='5' border='1'>";
			echo "<tr bgcolor='#f1f1f1'><td align='left' valign='top' class='Text'>Titlar:</td><td align='left' valign='top' class='Text'>Nummer:</td><td align='left' valign='top' class='Text'>Kategori:</td></tr>";
		
			// Läs data om kategorier i databasen kategorier.
			while ($row = mysqli_fetch_array($result)) {
				$KATNR			= $row["KATNR"];
				$KATEGORI		= utf8_encode($row["KATEGORI"]);
				
				// Räkna efter hur många titlar som finns i aktuell kategori.
				if ($KATNR == "0") $Raknatitlar = "SELECT * FROM litteratur WHERE KATNR = '$KATNR' OR KATNR = ''";
				else $Raknatitlar = "SELECT * FROM litteratur WHERE KATNR = '$KATNR'";
				
				// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
				if (!$Titlarresult = mysqli_query($db,$Raknatitlar)) {
					die('Invalid query: ' . mysqli_error($db));
				}
				$Titlarresult	= mysqli_query($db,$Raknatitlar);
				$Antaltitlar	= mysqli_num_rows($Titlarresult);
                
                // Om det finns titlar i kategorin så skapa en länk.
                if ($Antaltitlar >= 1) $Visaantaltitlar = "<a href='titel_hantering.php?Steg=ListaTitlar&Kat=$KATNR'>$Antaltitlar</a>";
				
				if ($KATEGORI == "") $KATEGORI = "** Oregistrerad **";
				
				if ($Antaltitlar >= 1) echo "<tr><td align='center' valign='top' class='Text'>$Visaantaltitlar</td><td align='left' valign='top' class='Text'>$KATNR</td><td align='left' valign='top' class='Text'><b>&ldquo;$KATEGORI&rdquo;</b></td></tr>";
			}
			echo "</table>";
			
			// Skriv ut Tillbaka-knappen.
			echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";

            // Skriv ut en hjälpknapp.
            $HELPID = 13;
            require ("../subrutiner/help.php");
        

		}
	}
    
    // Avsluta dokumentet.
    echo "</div></body></html>";
?>