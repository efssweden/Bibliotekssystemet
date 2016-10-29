<?php
    session_start();
    
    // Starta dokumentet.
    require("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";
    
    // Skriv ut bibliotektes exlibris.
    require ("../subrutiner/top_exlibris.php");

    // Kolla upp vilket steg som ska tas.
	$Steg = $_GET['Steg'];


	if ($Steg == "Generera") {
		// Öppna ett formulär för att skapa nya streckkodsetiketter.
		echo "<form action='streckkod_hantering.php?Steg=Kontrollera' method='post' enctype='application/form-data'>";

		//Kolla vilken som är den senaste registrerade streckkoden.
		$LasEAN = "SELECT * FROM bocker WHERE EAN LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN DESC LIMIT 1";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$LasEAN)) {
			die('Invalid query: ' . mysqli_error($db));
		}
			
		while ($row = mysqli_fetch_array($result)) {
			$EAN	= $row["EAN"]+1;
		}
		
		// Skapa första streckkoden om det inte finns någon.
		if ($EAN == "") $EAN = $_SESSION['Bibliotek']."000001";
		
        // Skriv ut rubrik.
        echo "<table cellpadding='5' width='400' bgcolor='yellow' align='center' border='1' class='Rubrik'><tr><td>T&auml;nk p&aring; att anv&auml;nda A4-ark med 3x8 etiketter!</td></tr></table><br />";
		
        // Öppna en tabell och sätt upp en sträng med återkommande instruktioner för att spara plats.			
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		$CELL = "<td valign='top' align='left'>";

		// Skriv ut raderna i tabellen.
		echo "<tr>".$CELL."F&ouml;rsta streckkoden:</td>$CELL<input name='EAN' type='Text' class='Text' size='12' value='$EAN' > = F&ouml;rsta oregistrerade streckkoden.</td></tr>";
		echo "<tr>".$CELL."Antal A4-ark:</td>$CELL<input name='Ark' type='Text' class='Text' size='4' value='1' > Varje ark rymmer 24 etiketter.</td></tr>";
		echo "<tr>".$CELL."Etikettstorlek:</td>$CELL<input name='Size' type='Radio' class='Text' value='37' checked> 70 x 37 mm<br /><input name='Size' type='Radio' class='Text' value='36'> 70 x 36 mm</td></tr>";

		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td>$CELL<input type='submit' value='Generera' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></td></tr></table></form>";	
	}
	
	// Kontrollera att allt verkar stämma innan man ger sig på att generera streckkoderna.
	if ($Steg == "Kontrollera") {

		// Läs in de respektive strängarnas värde från föregående sida.
		$EAN	= $_POST["EAN"];
		$Ark	= $_POST["Ark"];
        $Size   = $_POST["Size"];

		// Kontrollera om streckkoden är numerisk och larma om så inte är fallet.
		if (!is_numeric($EAN)) {
			echo "<div class='Text'>Streckkoden inneh&aring;ller otill&aring;tna tecken. Den f&aring;r bara inneh&aring;lla siffror!</div><br />";
			echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"streckkod_hantering.php?Steg=Generera\" '  class='Text'/>";
			exit();
		}

		// Kolla så att streckkoden har tillräckligt många siffror och larma om så inte är fallet.
		if (strlen($EAN) <> 10) {
			echo "<div class='Text'>Streckkoden inneh&aring;ller felaktigt antal tecken. Den m&aring;ste best&aring; av 10 siffror!</div><br />";
			echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"streckkod_hantering.php?Steg=Generera\" '  class='Text'/>";
			exit();
		}

		// Kolla om boken tillhör det aktuella biblioteket och larma om så inte är fallet.
		if (substr($EAN,0,4) <> $_SESSION['Bibliotek']) {
			echo "<div class='Text'>Streckkoden tillh&ouml;r inte detta bibliotek. Den m&aring;ste b&ouml;rja på ".$_SESSION['Bibliotek']."!</div><br />";
			echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"streckkod_hantering.php?Steg=Generera\" '  class='Text'/>";
			exit();
		}		
		
		// Hoppa till streckkodsgenereringen.
		header("location:streckkod.php?EAN=$EAN&Ark=$Ark&Size=$Size");
	}

?>
</div>
</body>
</html>