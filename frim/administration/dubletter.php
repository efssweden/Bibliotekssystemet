<?php
    session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";
    
    // Kolla om det finns något Exlibris till det aktuella biblioteket.
   	require ("../subrutiner/top_exlibris.php");
    
    // Ta reda på vilket steg av sidan som ska visas.
	$Steg = $_GET['Steg'];
 	if (isset($_GET['Sok'])) $Sok = $_GET['Sok'];
    
	// Börja med att skriva in titeln som ska bort.
	if ($Steg == "Sok") {

		// Öppna ett formulär för sökningen.
		echo "<form action='dubletter.php?Steg=Sokt' name='dubletter' method='post' enctype='application/form-data'>";
		
		// Skriv ut en rubrik och öppna tabellen.
		echo "<div class='Rubrik'>Ta bort dublett, steg 1</div>";
		echo "<div class='Text'>Skriv in ID p&aring; den titel som ska tas bort:</div>";
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		
		// Skriv ut raderna i tabellen.
		echo "<tr><td valign='top' align='left'><input name='IDett' type='Text' class='Text' size='50' ></td></tr>";
	
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "</table><br /><input type='submit' value='K&ouml;r' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></form><br />";	

		// Sätt fokus på inmatningsrutan så att markören alltid står där från början.
		echo "<script type='text/javascript'>document.dubletter.IDett.focus()</script>";		
    }
    
	// Visa sökt titel.
	if ($Steg == "Sokt") {
	
		// Hämta upp ID på titeln som ska tas bort.
   		$Hitta = $_POST['IDett'];
        $Sok = $_GET['Sok'];
        if (empty($Hitta)) {
            header("location:dubletter.php?Steg=Sok&Sok=$Sok");
            exit();
        }
        
        $Hitta = mysqli_real_escape_string($db,$Hitta);
        
		// Skriv ut en rubrik.
		echo "<div class='Rubrik'>Ta bort dublett, steg 2</div><br />";
				
		// Leta efter söksträngen i databasen litteratur.
		$Hittatitel = "SELECT * FROM litteratur WHERE TITELID = $Hitta";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resulttitel = mysqli_query($db,$Hittatitel)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Hämta information om titeln.
        while ($row = mysqli_fetch_array($resulttitel)) {
            $TITEL      = $row["TITEL"];
        }
        
        // Kolla hur många böcker som finns kopplade till den aktuella titeln.
        $Raknabocker = "SELECT * FROM bocker WHERE TITELID = $Hitta";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultrakna = mysqli_query($db,$Raknabocker)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        $Antalbocker = mysqli_num_rows($resultrakna);
        
        // Skriv ut information om titeln som skall tas bort.
        echo "<b>$Antalbocker</b> b&ouml;cker flyttas &ouml;ver fr&aring;n titeln <b>$TITEL</b><br /><br />";

		// Öppna ett formulär för sökningen.
		echo "<form action='dubletter.php?Steg=Verifiera&Hitta=$Hitta' name='dubletter' method='post' enctype='application/form-data'>";
		
		// Skriv ut tabellen.
		echo "<div class='Text'>Skriv in ID p&aring; den titel som ska ers&auml;tta ovanst&aring;ende:</div>";
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		
		// Skriv ut raderna i tabellen.
		echo "<tr><td valign='top' align='left'><input name='IDtva' type='Text' class='Text' size='50' ></td></tr>";
	
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "</table><br /><input type='submit' value='K&ouml;r' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></form><br />";	

		// Sätt fokus på inmatningsrutan så att markören alltid står där från början.
		echo "<script type='text/javascript'>document.dubletter.IDtva.focus()</script>";		
                        			
	}
    
	// Verifiera.
	if ($Steg == "Verifiera") {
	
		// Hämta upp ID på titeln som ska tas bort.
   		$Ersatt = $_POST['IDtva'];
   		$Hitta = $_GET['Hitta'];
        
        if (empty($Hitta)) {
            header("location:dubletter.php?Steg=Sok&Sok=$Sok");
            exit();
        }
        
		// Skriv ut en rubrik.
		echo "<div class='Rubrik'>Ta bort dublett, steg 3</div><br />";
				
		// Leta efter söksträngen i databasen litteratur.
		$Hittatitel = "SELECT * FROM litteratur WHERE TITELID = $Hitta";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resulttitel = mysqli_query($db,$Hittatitel)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Hämta information om titeln.
        while ($row = mysqli_fetch_array($resulttitel)) {
            $TITEL      = $row["TITEL"];
        }
        
        // Kolla hur många böcker som finns kopplade till den aktuella titeln.
        $Raknabocker = "SELECT * FROM bocker WHERE TITELID = $Hitta";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultrakna = mysqli_query($db,$Raknabocker)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        $Antalbocker = mysqli_num_rows($resultrakna);
        
		// Leta efter söksträngen i databasen litteratur.
		$Hittaersatt = "SELECT * FROM litteratur WHERE TITELID = $Ersatt";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultersatt = mysqli_query($db,$Hittaersatt)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Hämta information om titeln.
        while ($row = mysqli_fetch_array($resultersatt)) {
            $NYTITEL      = $row["TITEL"];
        }
        
        // Kolla hur många böcker som finns kopplade till den aktuella titeln.
        $Raknaersatt = "SELECT * FROM bocker WHERE TITELID = $Ersatt";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultraknaersatt = mysqli_query($db,$Raknaersatt)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        $NYAntalbocker = mysqli_num_rows($resultraknaersatt);
        
        // Skriv ut information om titeln som skall tas bort.
        echo "<b>$Antalbocker</b> b&ouml;cker flyttas nu &ouml;ver fr&aring;n titeln <b>$TITEL</b> (ID: $Hitta)<br /><br />";
        echo "till <b>$NYTITEL</b> (ID: $Ersatt) som redan har <b>$NYAntalbocker</b> b&ouml;cker registrerade.<br /><br />";

		// Skriv ut "Fortsätt"- och "Tillbaka"-knappar.
		echo "<br /><button type='button' onClick='parent.location=\"dubletter.php?Steg=Exec&Hitta=$Hitta&Ersatt=$Ersatt\"' class='Text'>Forts&auml;tt</button> <button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";	

	}
    
    // Börja kötta!
    if ($Steg == "Exec") {
        
        $Hitta = $_GET['Hitta'];
        $Ersatt = $_GET['Ersatt'];
        
        // Börja med att byta ut alla referenser i pågående lån.
        $Aktiva = "UPDATE aktiva SET TITELID = $Ersatt WHERE TITELID = $Hitta";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultett = mysqli_query($db,$Aktiva)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Fortsätt med att byta ut alla referenser i gamla lån.
        $Gamlalan = "UPDATE gamlalan SET TITELID = $Ersatt WHERE TITELID = $Hitta";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resulttva = mysqli_query($db,$Gamlalan)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Fortsätt med att byta ut alla referenser i pågående speciallån.
        $Special = "UPDATE special SET STITELID = $Ersatt WHERE STITELID = $Hitta";
        
        // Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resulttre = mysqli_query($db,$Special)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Fortsätt med att byta ut alla referenser bland returnerade lån.
        $Returer = "UPDATE returer SET RETURTITELID = $Ersatt WHERE RETURTITELID = $Hitta";

        // Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultfyra = mysqli_query($db,$Returer)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Fortsätt med att byta ut alla referenser bland makulerade böcker.
        $Makulerade = "UPDATE makulerade SET MAKTITEL = $Ersatt WHERE MAKTITEL = $Hitta";

        // Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    	if (!$resultfem = mysqli_query($db,$Makulerade)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Fortsätte med att byta ut alla referenser bland registerade böcker.
        $Bocker = "UPDATE bocker SET TITELID = $Ersatt WHERE TITELID = $Hitta";

        // Skicka begäran till databasen. Stoppa och visa felet om något går snett.
    	if (!$resultsex = mysqli_query($db,$Bocker)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        // Avsluta med att slänga den gamla titeln.
        $Kasta = "DELETE FROM litteratur WHERE TITELID = $Hitta";

        // Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resultsex = mysqli_query($db,$Kasta)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
		// Skriv ut en rubrik.
		echo "<div class='Rubrik'>Ta bort dublett, steg 4</div><br /><br />";
        echo "1. $Aktiva<br />";
        echo "2. $Gamlalan<br />";
        echo "3. $Special<br />";
        echo "4. $Returer<br />";
        echo "5. $Makulerade<br />";
        echo "6. $Bocker<br />";
        echo "7. $Kasta<br />";

		// Skriv ut "Tillbaka"-knapp.
		echo "<br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";	
				
        
    }
	
