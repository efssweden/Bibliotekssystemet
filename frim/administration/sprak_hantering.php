<?php
    session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
    echo "<body><div id='ALLT'><br />";
    
    // Släng in en logotyp på sidan.
    require ("../subrutiner/top_exlibris.php");
    
	// Kontrollera vilket steg som ska tas på sidan.
    $Steg = $_GET['Steg'];
		
	// Lista språk.
	if ($Steg == "Lista") {

		// Hämta språk från databasen sprak.
		$Listasprak = "SELECT * FROM sprak ORDER BY SPRAKLANG ASC";
		$result = mysqli_query($db,$Listasprak);

		if (!mysqli_num_rows($result)) $Output = "<br /><div class='Rubrik'>Det finns inga registrerade spr&aring;k.</div>";
		else {
		  
			// Skapa en tabell över registrerade språk.
			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br />";
			$Sprakrubrik = "Dessa spr&aring;k &auml;r just nu registrerade:";
			echo "<br /><div class='Rubrik'>$Sprakrubrik</div><table align='center' cellpadding='5' border='1' width='400'>";
			echo "<tr><td align='left' valign='top' class='Text' >Titlar:</td><td align='left' valign='top' class='Text' width='95%'>Spr&aring;k:</td></tr>";
		
			// Läs data om språk i databasen sprak.
			while ($row = mysqli_fetch_array($result)) {
                $SPRAKKORT = $row["SPRAKKORT"];
                $SPRAKLANG = $row["SPRAKLANG"];
				
				// Räkna efter hur många titlar som finns i aktuellt språk.
				$Raknatitlar    = "SELECT * FROM litteratur WHERE SPRAK = '$SPRAKKORT'";
				$Titlarresult	= mysqli_query($db,$Raknatitlar);
				$Antaltitlar	= mysqli_num_rows($Titlarresult);
                
                // Om det finns titlar i kategorin så skapa en länk.
                if ($Antaltitlar >= 1) $Antaltitlar = "<a href='titel_hantering.php?Steg=ListaTitlar&Sprak=$SPRAKKORT'>$Antaltitlar</a>";
				
				echo "<tr><td align='center' valign='top' class='Text'>$Antaltitlar</td><td align='left' valign='top' class='Text'><b>$SPRAKLANG</b></td></tr>";
			}
			echo "</table>";
			
			// Skriv ut Tillbaka-knappen.
			echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
            
            // Skriv ut en hjälpknapp.
            $HELPID = 14;
            require ("../subrutiner/help.php");

		}
	}
    // Avsluta dokumentet.
    echo "</div></body></html>";
?>