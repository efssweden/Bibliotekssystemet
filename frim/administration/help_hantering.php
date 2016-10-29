<?php
    session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
    echo "<body><div id='ALLT'><br />";
    
	// Kontrollera vilket steg som ska tas på sidan.
    $Steg = $_GET['Steg'];

	// Lista hjälpavsnitt.
	if ($Steg == "Lista") {

		// Hämta avsnittet från databasen help.
		$Listahelp = "SELECT * FROM help ORDER BY HELPID ASC";
		$result = mysqli_query($db,$Listahelp);

		if (!mysqli_num_rows($result)) echo "<br /><div class='Rubrik'>Det finns inga registrerade hj&auml;lpavsnitt.</div>";
		else {
		  
			// Skapa en tabell över registrerade hjälpavsnitt.
			echo "<br /><div class='Rubrik'>Dessa hj&auml;lpavsnitt &auml;r just nu registrerade:</div>";
			echo "<table align='center' cellpadding='5' border='1' width='400'>";
			echo "<tr bgcolor='#f1f1f1'><td align='left' valign='top' class='Text' >ID:</td><td align='left' valign='top' class='Text' width='95%'>Rubrik:</td></tr>";
		
			// Läs data om avsnittet i databasen help.
			while ($row = mysqli_fetch_array($result)) {
                $HELPID = $row["HELPID"];
                $HELPRUBRIK = $row["HELPRUBRIK"];
                $HELPTEXT = $row["HELPTEXT"];
								
				echo "<tr><td align='center' valign='center' class='Text'>$HELPID</td><td align='left' valign='top' class='Text'><button type='button' onClick='parent.location=\"help_hantering.php?Steg=Redigera&Sparasom=Uppdatera&HELPID=$HELPID\"' class='Text'>Redigera</button> <b>$HELPRUBRIK</b></td></tr>";
			}
			echo "</table>";
		}	
		// Skriv ut Tillbaka-knappen.
		echo "<br /><button type='button' onClick='parent.location=\"help_hantering.php?Steg=Redigera&Sparasom=Ny\"' class='Text'>L&auml;gg till</button><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
		
	}
    
    // Redigera hjälpavsnitt.
    if ($Steg == "Redigera") {
        
        // Vilket avsnitt ska redigeras?
        $Sparasom = $_GET['Sparasom'];
        $HELPID = $_GET['HELPID'];

		// Hämta det valda avsnittet från databasen help.
		$Hamtahelp = "SELECT * FROM help WHERE HELPID = '$HELPID'";
		$result = mysqli_query($db,$Hamtahelp);

		while ($row = mysqli_fetch_array($result)) {
            $HELPID = $row["HELPID"];
            $HELPRUBRIK = $row["HELPRUBRIK"];
            $HELPTEXT = $row["HELPTEXT"];
        }
        
        // Kolla om det är ett befintligt avsnitt som ska redigeras.
		if ($Sparasom == "Uppdatera") {
            echo "<br /><div class='Rubrik'>Redigera ett hj&auml;lpavsnitt</div>";
            echo "<form action='help_hantering.php?Steg=Spara&amp;HELPID=$HELPID&amp;Sparasom=Uppdatera' method='post' enctype='application/form-data'>";
        }

        // Kolla om det är ett befintligt avsnitt som ska redigeras.
		if ($Sparasom == "Ny") {
            echo "<br /><div class='Rubrik'>L&auml;gg till ett nytt hj&auml;lpavsnitt</div>";
            echo "<form action='help_hantering.php?Steg=Spara&amp;&amp;Sparasom=Ny' method='post' enctype='application/form-data'>";
        }

        // Sätt upp en tabell.
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		if ($Sparasom == "Uppdatera") echo "<tr><td valign='top' align='left' class='Text'>ID-nr:</td><td valign='top' align='left' class='Text'>$HELPID</td></tr>";
		echo "<tr><td valign='top' align='left' class='Text'>Rubrik:</td><td valign='top' align='left' class='Text'><input name='HELPRUBRIK' type='Text' class='Text' size='100' value='$HELPRUBRIK' ></td></tr>";
		echo "<tr><td valign='top' align='left' class='Text'>Text:</td><td valign='top' align='left' class='Text'><textarea name='HELPTEXT' cols='100' rows='4' class='Text'>$HELPTEXT</textarea></td></tr>";

		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td><td><input type='submit' value='Spara' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></td></tr></table></form>";
   }

	// Spara den nya eller uppdaterade titeln i databasen litteratur.
	if ($Steg == "Spara") {

		$Sparasom = $_GET['Sparasom'];
		if (isset($_GET['HELPID'])) $HELPID = $_GET['HELPID'];
		
		// Läs in de respektive strängarnas värde från föregående sida.
		$HELPRUBRIK		= $_POST["HELPRUBRIK"];
        $HELPTEXT       = nl2br($_POST["HELPTEXT"]);
        
		// Kontrollera om det är ett befintligt avsnitt som ska redigeras och gör det i så fall.
		if ($Sparasom == "Uppdatera") {
			// Sätt ihop strängarna till en uppdateringssträng för databasen.
			$Skrivhelp = "UPDATE help SET HELPRUBRIK='$HELPRUBRIK', HELPTEXT='$HELPTEXT' WHERE HELPID='$HELPID'";
		}

		// Kontrollera om det är ett nytt avsnitt som ska läggas till och gör det i så fall.
		elseif ($Sparasom == "Ny") {
			// Sätt ihop strängarna för att skicka dem till databasen.
			$Skrivhelp = "INSERT INTO help (HELPRUBRIK,HELPTEXT) VALUES ('$HELPRUBRIK','$HELPTEXT')";
		}
        
		// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Skrivhelp)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Hoppa tillbaka till administrationssidan.
		header("location:administrera.php");
    }

    // Avsluta dokumentet.
    echo "</div></body></html>";
?>