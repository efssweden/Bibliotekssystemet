<?php
    session_start();

    // Starta dokumentet.
    require("subrutiner/head.php");
    echo "<body><div id='ALLT'>";

    // Skriv ut bibliotekets exlibris på sidan.
    require("subrutiner/top_exlibris.php");

	// Börja med att välja grad.
	if (!isset($_POST['GRADSELECT'])) {

		// Öppna ett formulär för att välja grad.
		echo "<form action='studieplan.php' name='Visa' method='post' enctype='application/form-data'>";

		// Skriv ut en rubrik och öppna en tabell.
        echo "<div class='Rubrik'>V&auml;lj grad:</div>";
		echo "<table align='center' class='Text' border='1' cellpadding='5' >";

		// Visa de olika graderna med en radioknapp för respektive grad.
		echo "<tr><td valign='top' align='left'>";
        $Grader = array("","S:t Johannes Grad I","S:t Johannes Grad II","S:t Johannes Grad III","S:t Andreas Grad IV-V","","S:t Andreas Grad VI","Kapitel Grad VII","Kapitel Grad VIII","Kapitel Grad IX","Kapitel Grad X");
        for ($i=1; $i<=10; $i++) {
            
            // Om graden inte är 5 så skriv ut en rad.
            if ($i <> 5) echo "<input type='radio' name='GRADSELECT' value='$i' class='Text' ";
            if ($i == 1) echo "checked";
            if ($i <> 5) echo "/> ".$Grader[$i]."<br />";
        }
        echo "</td></tr>";
		
		// Skriv ut "Spara"- och "Tillbaka"-knappar och stäng tabellen.
		echo "<tr><td valign='top' align='left'><input type='submit' value='Visa studieplan' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"fm.php\" ' class='Text' /></td></tr></table></form><br />";	

        // Skriv ut en hjälpknapp.
        $HELPID = 3;
        require ("subrutiner/help.php");
	}
	
	// Lista alla titlar i litteratur-databasen i den valda graden som finns i biblioteket.
	if (isset($_POST['GRADSELECT'])) {

		// Hämta vald grad.
		$Gradnummer = $_POST['GRADSELECT'];
		
		// Kolla upp vilket bibliotek vi befinner oss i.
		$BIBLIOTEK = $_SESSION['Bibliotek'];

		// Starta en loop som tar oss genom de tre studieplanerna. (Ändra "$i <= 2" till 3 för att även visa övrig litteratur.)
		for ($i = 0; $i <= 3; $i++) {
			
            // Hämta titlar i vald grad i respektive studieplan.
            $Plan = array("B","K","F","");
            $Visa = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND STUDIEPLAN = '".$Plan[$i]."' ORDER BY KOD,TITEL ASC";

			// Bestäm gradrubriken baserat på graden.
            $Grader = array("","S:t Johannes Grad I","S:t Johannes Grad II","S:t Johannes Grad III","S:t Andreas Grad IV-V","","S:t Andreas Grad VI","Kapitel Grad VII","Kapitel Grad VIII","Kapitel Grad IX","Kapitel Grad X");
            $Gradrubrik = $Grader[$Gradnummer];

			// Visa vilken studieplan som är aktuell.
			if ($i == 0) $Studieplan = "Studieplan Bas";
			if ($i == 1) $Studieplan = "Studieplan Komplettering";
			if ($i == 2) $Studieplan = "Studieplan F&ouml;rdjupning";
			if ($i == 3) $Studieplan = "&Ouml;vrig litteratur";
			
			// Skriv ut rubriken och öppna en tabell.
			echo "<div class='Rubrik'>$Studieplan i $Gradrubrik</div>";
			echo "<table border='1' cellpadding='5' align='center' width='70%'>";

			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Visa)) {
				die('Invalid query: ' . mysqli_error($db));
			} 
            
			// Läs information från databasen litteratur om utvald titel.
			while ($row = mysqli_fetch_array($result)) {
				$TITELID		= $row["TITELID"];
				$KOD			= $row["KOD"];
				$TITEL			= $row["TITEL"];
				$FORFATTARE		= $row["FORFATTARE"];
				$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
				$BESKRIVNING	= $row["BESKRIVNING"];
				$REVIDERAD		= $row["REVIDERAD"];
				$LAN			= $row["LAN"];

				// Kontrollera om data saknas och hantera det i så fall.
				if (empty($TITEL)) $TITEL = "**Titel saknas**";
				if (empty($FORFATTARE)) $FORFATTARE = "**F&ouml;rfattare saknas**";
				if (empty($UTGIVNINGSAR)) $UTGIVNINGSAR = "";
				else $UTGIVNINGSAR = " ($UTGIVNINGSAR)";
				if (empty($REVIDERAD)) $REVIDERAD = "";
				else $UTGIVNINGSAR = "$UTGIVNINGSAR Reviderad $REVIDERAD";
                if (!empty($KOD)) {
                    $KOD = str_replace(":00",":",$KOD);
                    $KOD = str_replace(":0",":",$KOD);
                    $KOD = str_replace(" 00"," ",$KOD);
                    $KOD = str_replace(" 0"," ",$KOD);
                }
                if (!empty($KOD)) $KOD="$KOD ";
                if (!empty($BESKRIVNING) && substr($BESKRIVNING,-1,1) <> ".") $BESKRIVNING = $BESKRIVNING.".";
                if ($i == 3) $BESKRIVNING = "";
				
				// Kontrollera om det finns några böcker kopplade till titeln.
				$Listabocker = "SELECT * FROM bocker WHERE EAN LIKE '$BIBLIOTEK%' AND TITELID = $TITELID ORDER BY EAN ASC";

				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$bokresult = mysqli_query($db,$Listabocker)) {
					die('Invalid query: ' . mysqli_error($db));
				} 

				// Räkna antalet böcker med denna titel.
				$Raknabocker = mysqli_num_rows($bokresult);
                
                $Total = $Total + $Raknabocker;
				
				// Kontrollera om det finns några aktiva utlån kopplade till titeln.
				$Listalan = "SELECT * FROM aktiva WHERE TITELID = $TITELID AND BIBLIOTEK = $BIBLIOTEK";

				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$lanresult = mysqli_query($db,$Listalan)) {
					die('Invalid query: ' . mysqli_error($db));
				} 

                $Raknalan = mysqli_num_rows($lanresult);
                
				// Kontrollera om det finns några speciallån kopplade till titeln.
				$Listalan = "SELECT * FROM special WHERE STITELID = $TITELID AND SBIBLIOTEK = $BIBLIOTEK";

				// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
				if (!$lanresult = mysqli_query($db,$Listalan)) {
					die('Invalid query: ' . mysqli_error($db));
				} 

				// Räkna antalet utlånade böcker med denna titel.
				$Raknaspecial = mysqli_num_rows($lanresult);
                $Raknalan = $Raknalan-$Raknaspecial;
                
				// Räkna efter hur många böcker som finns inne just nu.
				$Bocker = $Raknabocker-$Raknalan;
				
				// Beroende på antalet böcker som finns tillgängliga, visa grönt eller rött.
				if ($Bocker == 0 ) {
				    $Bocker = "<font color='red'> <b>$Raknabocker</b> exemplar finns ";
                    if ($Raknabocker == 1) $Bocker = $Bocker."registrerat i biblioteket men det &auml;r utl&aring;nat just nu.</font>";
                    else $Bocker = $Bocker."registrerade i biblioteket men alla &auml;r utl&aring;nade just nu.</font>";
                    $Nyrad = "<tr bgcolor='#FFF7F7'>";
                }
				if ($Bocker >= 1) {
				    $Bocker = "<font color='green'> <b>$Bocker</b> exemplar finns just nu i biblioteket.</font>";
                    $Nyrad = "<tr bgcolor='#F7FFF7'>";
                }

                // Samla alla streckkoder i en sträng.
                $Streckkoder = "";
                if ($Raknabocker <> 0) {
                    
                    $Streckkodslista = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN ASC";
                    $Streckkodslistaresult	= mysqli_query($db,$Streckkodslista);
                    while ($row = mysqli_fetch_array($Streckkodslistaresult)) {
                        $Streckkoder = $Streckkoder.substr($row["EAN"],4).", ";
                    }
                    
                    $Streckkoder = "<br /><br />ID-nr: ".substr($Streckkoder,0,strlen($Streckkoder)-2);
                    
                }     
        
				// Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
				if ($Raknabocker >= 1) echo "$Nyrad<td valign='top' align='left' class='Text'>$KOD<b>&ldquo;$TITEL&rdquo;</b>$UTGIVNINGSAR<br />F&ouml;rfattare: $FORFATTARE<br /><br />$BESKRIVNING$Bocker$Streckkoder</td></tr>";
			}
            if ($Total == 0) echo "<td valign='top' align='center' class='Text'>Det finns inga b&ouml;cker registrerade i denna grads studieplan &auml;nnu...</td></tr>";
			
			// Stäng tabellen.
			echo "</table><br />";
		}
        
		// Skriv ut en Tillbaka-knapp.
		echo "<button type='button' onClick='parent.location=\"fm.php\"' class='Text' >Tillbaka</button><br /><br />";
	}

    // Avsluta dokumentet.
    echo "</div></body></html>";
?>