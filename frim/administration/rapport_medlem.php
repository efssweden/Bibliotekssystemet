<?php
	session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";

    // Visa bibliotekets exlibris.
    require ("../subrutiner/top_exlibris.php");  
    
    // Kolla upp vilket steg som ska tas.
	$Steg = $_GET['Steg'];
    
    // Välj vilket år som ska visas i årsrapporten.
    if ($Steg == "Arsdatum") {

        // Skriv ut rubriken.
        echo "<span class='Rubrik'>V&auml;lj vilket &aring;r som ska visas:</span><br /><br />";
        
        // Kolla upp det första året som är aktuellt för biblioteket.
        $Firstvisit = "SELECT * FROM besokslistan WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC LIMIT 1";
        
        // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$resultfirst = mysqli_query($db,$Firstvisit)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        while ($row = mysqli_fetch_array($resultfirst)) {
				$Firstyear	= date("Y",$row["DATUM"]);
        }
        
		// Skriv ut en tabell.
		echo "<table align='center' class='Text' border='1' cellpadding='5' >";
        
        // Gör en loop och skriv ut länkar till respektive år.
        for ($i=date(Y); $i >=$Firstyear; $i--) {
            
            echo "<tr><td valign='top' align='left'>$i - <a href='rapport_hantering.php?Steg=Arsrapport&Year=$i'>Klicka h&auml;r</a></td></tr>";
        }

		// Skriv ut en "Tillbaka"-knapp och stäng tabellen.
		echo "<tr><td valign='top' align='center'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" ' class='Text' /></td></tr></table><br />";	

    }
    
    // Välj vilket år som ska visas.
    if ($Steg == "Datum") {

        // Skriv ut rubriken.
        echo "<span class='Rubrik'>V&auml;lj vilket &aring;r som ska visas:</span><br /><br />";
        
        // Kolla upp det första året som är aktuellt för biblioteket.
        $Firstvisit = "SELECT * FROM besokslistan WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." ORDER BY DATUM ASC LIMIT 1";
        
        // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$resultfirst = mysqli_query($db,$Firstvisit)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
        while ($row = mysqli_fetch_array($resultfirst)) {
				$Firstyear	= date("Y",$row["DATUM"]);
        }
        
		// Skriv ut en tabell.
		echo "<table align='center' class='Text' border='1' cellpadding='5' >";
        
        // Gör en loop och skriv ut länkar till respektive år.
        for ($i=date(Y); $i >=$Firstyear; $i--) {
            
            echo "<tr><td valign='top' align='left'>$i - <a href='rapport_medlem.php?Steg=Lista&Datum=$i&Sort=Namn'>Klicka h&auml;r</a></td></tr>";
        }

		// Skriv ut en "Tillbaka"-knapp och stäng tabellen.
		echo "<tr><td valign='top' align='center'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" ' class='Text' /></td></tr></table><br />";	

    }
    
    // Lista flitiga besökare.
	if ($Steg == "Lista") {
	   
        // Ta reda på vilket år som ska visas.
        $Ar = $_GET["Datum"];

		// Sätt första och sista datum i år.
		$Datumett	= mktime(0,0,0,1,1,$Ar);
		$Datumtva	= mktime(0,0,0,12,31,$Ar);

		$Listabesok = "SELECT * FROM besokslistan WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." AND DATUM > $Datumett AND DATUM < $Datumtva";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listabesok)) {
			die('Invalid query: ' . mysqli_error($db));
		} 

		// Om besökslistan är tom så berätta det.
		if (!mysqli_num_rows($result)) echo "Det finns inga bes&ouml;k registrerade i &aring;r.";
		else {

            // Skriv ut rubriken.
            echo "<span class='Rubrik'>Bes&ouml;k i biblioteket under &aring;r $Ar</span><br />";
            
            // Skriv ut sorteringsalternativen.
            $Sortering = $_GET["Sort"];
            if ($Sortering == "Namn") $Sorteringslank = "<a href='rapport_medlem.php?Steg=Lista&Datum=$Ar&Sort=Loge'>Sortera efter logetillh&ouml;righet</a>";
            if ($Sortering == "Loge") $Sorteringslank = "<a href='rapport_medlem.php?Steg=Lista&Datum=$Ar&Sort=Namn'>Sortera efter namn</a>";
            echo "<span class='Text'>$Sorteringslank</br>";
            
			// Läs besökare i databasen besokslistan från detta år.
			$Hamtabesokare = "SELECT * FROM besokslistan WHERE BIBLIOTEK = ".$_SESSION['Bibliotek']." AND DATUM > $Datumett AND DATUM < $Datumtva GROUP BY MEDLEM";

			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
			if (!$resultbesok = mysqli_query($db,$Hamtabesokare)) {
				die('Invalid query: ' . mysqli_error($db));
			}
            
            // Börja räkna.
            $i = 1;

			// Läs medlemsnummer från databasen besokslistan.
			while ($row = mysqli_fetch_array($resultbesok)) {
				$Besoksmedlem	= $row["MEDLEM"];
                
                // Kontrollera om det är en tjänstgörande bibliotekarie som registrerats.
                if (substr($Besoksmedlem,0,2) == "tj") $Bibliotekarie = $Bibliotekarie;
                
                // Om så inte är fallet - fortsätt.
                else {
						
				// Hämta information från databasen medlem.
				$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $Besoksmedlem";

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

					// Skriv ut medlemsgraden i klartext.
					if ($MEDLEMGRAD == 1) $MEDLEMGRAD = "I";
					if ($MEDLEMGRAD == 2) $MEDLEMGRAD = "II";
					if ($MEDLEMGRAD == 3) $MEDLEMGRAD = "III";
					if ($MEDLEMGRAD == 4) $MEDLEMGRAD = "IV-V";
					if ($MEDLEMGRAD == 6) $MEDLEMGRAD = "VI";
					if ($MEDLEMGRAD == 7) $MEDLEMGRAD = "VII";
					if ($MEDLEMGRAD == 8) $MEDLEMGRAD = "VIII";
					if ($MEDLEMGRAD == 9) $MEDLEMGRAD = "IX";
					if ($MEDLEMGRAD == 10) $MEDLEMGRAD = "X";
					if ($MEDLEMGRAD == 11) $MEDLEMGRAD = "XI";

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
                    
                    // Räkna antalet besök medlemmen gjort.
                    $Raknabesok = "SELECT * FROM besokslistan WHERE MEDLEM = $MEDLEM AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND DATUM > $Datumett AND DATUM < $Datumtva";
                   	$Besokresult = mysqli_query($db,$Raknabesok);
                    $Antalbesok	= mysqli_num_rows($Besokresult);

                    // Räkna antalet lån medlemmen gjort.
                    $Raknalan = "SELECT * FROM gamlalan WHERE MEDLEM = $MEDLEM AND DATUM = ".$_SESSION['Bibliotek']." AND BIBLIOTEK > $Datumett AND BIBLIOTEK < $Datumtva";
                   	$Lanresult = mysqli_query($db,$Raknalan);
                    $Antallan	= mysqli_num_rows($Lanresult);
                    
                    // Räkna antalet pågående lån.
                    $Raknanu = "SELECT * FROM aktiva WHERE MEDLEM = $MEDLEM AND BIBLIOTEK = ".$_SESSION['Bibliotek']."";
                    $Nuresult = mysqli_query($db,$Raknanu);
                    $Antallan = $Antallan+mysqli_num_rows($Nuresult);

#                    echo "$MEDLEMFNAMN $MEDLEMNAMN (".substr($MEDLEM,0,4)."-".substr($MEDLEM,4)."), $MEDLEMGRAD i $MEDLEMLOGE $Antalbesok bes&ouml;k - $Antallan l&aring;n.<br />";
                    
                    $Medlemarray[$i] = array(ID => $i, MEDLEM => $MEDLEM, MEDLEMNAMN => "$MEDLEMNAMN, $MEDLEMFNAMN", GRAD => $MEDLEMGRAD, LOGE => $MEDLEMLOGE, BESOK => $Antalbesok, LAN => $Antallan);
                    $i = $i+1;
                }
            }
        }

		// Funktion för att underlätta sorteringen.
		function compare($x, $y){
            // Sortera efter brödernas efternamn.
    		if ( $x['MEDLEMNAMN'] == $y['MEDLEMNAMN'] ) return 0; 
			elseif ( $x['MEDLEMNAMN'] < $y['MEDLEMNAMN'] )  return -1;
			else return 1;
		}

		function compare2($x, $y){
            // Sortera efter brödernas logetillhörighet. Inlagt på begäran av Stockholm 2016-10-25.
    		if ( $x['LOGE'] == $y['LOGE'] ) return 0; 
			elseif ( $x['LOGE'] < $y['LOGE'] )  return -1;
			else return 1;
		}

        if ($Sortering == "Namn") usort($Medlemarray, "compare");
        if ($Sortering == "Loge") usort($Medlemarray, "compare2");
        
        echo "<table border='1' align='center' cellpadding='5'>";
        echo "<tr bgcolor='#F1F1F1'><td align='Left' class='Text'>Namn</td><td align='Left' class='Text'>Grad</td><td align='Left' class='Text'>Loge</td><td class='Text'>Bes&ouml;k</td><td class='Text'>L&aring;n</td></tr>";
    	for ($n=0; $n<=($i-2); $n++) {    	
        
            // Skriv ut medlemmen på en rad i tabellen och fortsätt till nästa.
    		$MEDLEM = $Medlemarray[$n]['MEDLEM'];
    		$MEDLEMNAMN = $Medlemarray[$n]['MEDLEMNAMN'];
    		$BESOK = $Medlemarray[$n]['BESOK'];
    		$LAN = $Medlemarray[$n]['LAN'];
    		$LOGE = $Medlemarray[$n]['LOGE'];
    		$GRAD = $Medlemarray[$n]['GRAD'];
            
    		echo "<tr><td align='Left' class='Text'>$MEDLEMNAMN</td><td align='Left' class='Text'>$GRAD</td><td align='Left' class='Text'>$LOGE</td><td class='Text'>$BESOK</td><td class='Text'>$LAN</td></tr>";
   		}

        // Stäng tabellen.
		echo "</table><br />";
        echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button><br /><br />";
    }
}
			
	
	
    // Avsluta dokumentet.
    echo "</div></body></html>";
?>