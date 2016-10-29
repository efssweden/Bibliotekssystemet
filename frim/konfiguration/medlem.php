<?php

    // Starta dokumentet.
    require ("../subrutiner/head.php");
        
    	// Berätta för systemet vad medlemsfilen heter.
    	$xml = simplexml_load_file('medlemmar.xml');
    
        // Börja med att tömma databasen medlemmar på innehåll.
    	mysqli_query($db,"TRUNCATE medlem");
        
        // Definiera funktionen unserialize_xml.
    	function unserialize_xml($input, $callback = null, $recurse = false){
    		global $db;
    
        	// Get input, loading an xml string with simplexml if its the top level of recursion
        	$data = ((!$recurse) && is_string($input))? simplexml_load_file($input): $input;
    
        	// Convert SimpleXMLElements to array
        	if ($data instanceof SimpleXMLElement) $data = (array) $data;
    
            // Recurse into arrays
        	if (is_array($data)) {
            	foreach ($data as &$item) $item = unserialize_xml($item, $callback, true);
                
            	// Om medlemsnumret inte finns så ersätt det med ett slumpmässigt valt nummer, annars ta bort minustecknet i medlemsnumret.
            	if (!($data['medlemsnummer'])) $MEDLEM = rand(1, 998);
            	else $MEDLEM	= str_replace("-","",$data['medlemsnummer']);
    
                // Läs in broderns för- och efternamn och ta bort eventuella specialtecken.
            	$MEDLEMFNAMN	= mysqli_real_escape_string($db,$data['fornamn']);
            	$MEDLEMNAMN		= mysqli_real_escape_string($db,$data['efternamn']);
    
                // Läs in broderns grad och sätt den som 4 om den råkat bli 5.
                $MEDLEMGRAD		= $data['grad'];
                if ($MEDLEMGRAD == "5") $MEDLEMGRAD = "4";
    
                // Läs in broderns logetillhörighet.
            	$MEDLEMLOGE		= $data['enhetsid'];
    
                // Läs in broderns bostadsort och ta bort eventuella specialtecken.
            	$MEDLEMORT		= mysqli_real_escape_string($db,$data['ort']);
    
                // Läs in broderns kortnummer.
            	$MEDLEMKORT		= $data['kortnummer'];
    
        		// Skriv in brodern i databasen medlem.
        		$Skrivdata = "INSERT INTO medlem (MEDLEM, MEDLEMNAMN, MEDLEMFNAMN, MEDLEMGRAD, MEDLEMLOGE, MEDLEMKORT,MEDLEMORT) VALUES ('$MEDLEM','$MEDLEMNAMN','$MEDLEMFNAMN','$MEDLEMGRAD','$MEDLEMLOGE','$MEDLEMKORT','$MEDLEMORT')";
                echo "<tr><td>$MEDLEM</td><td>$MEDLEMNAMN, $MEDLEMFNAMN</td><td>$MEDLEMGRAD</td><td>$MEDLEMLOGE</td><td>$MEDLEMKORT</td><td>$MEDLEMORT</td></tr>";
    
                // Kontrollera så att det inte blir något fel i kontakten med servern.
        		if (!$result = mysqli_query($db,$Skrivdata)) {
        			die('Invalid query: ' . mysqli_error($db));
        		}
            }
                
        	// Run callback and return
        	return (!is_array($data) && is_callable($callback))? call_user_func($callback, $data): $data;
    
        }
    
        // Kör funktionen unserialize_xml.
        echo "<table>";
        unserialize_xml($xml);
        echo "</table>";
    
        // Radera bröder med ogiltiga medlemsnummer från databasen medlem.
        $Raderamedlem = "DELETE FROM medlem WHERE MEDLEM < 10000";
    
        // Skicka kommandot till servern och visa felet om det dyker upp något.
        if (!$result = mysqli_query($db,$Raderamedlem)) {
        	die('Invalid query: ' . mysqli_error($db));
        }
    
        // Komplettera med epostadresser från filen medlem.csv som sekreteraren kan bistå med.
        if (($handle = fopen("medlem.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    
                // Läs data från csv-filen och placera dem i respektive sträng.
            	$MEDLEM       = str_replace("-","",$data[3]);
                $MEDLEMMAIL   = UTF8_encode($data[17]);
    
            	// Uppdatera databasen medlem med broderns epostadress.
            	$Uppdatera = "UPDATE medlem SET MEDLEMMAIL='$MEDLEMMAIL' WHERE MEDLEM = $MEDLEM";
    
            	// Skicka kommandot till servern och visa felet om det blir fel.
                if (!$result = mysqli_query($db,$Uppdatera)) {
                    die('Invalid query: ' . mysqli_error($db));
            	}
            }
    
            // Stäng filen.
            fclose($handle);
        }
    
        // Hoppa tillbaka till administrationssidan.
//        echo "<script language='javascript' type='text/javascript'>window.location.href = \"../administration/administrera.php\"</script>";
        echo "<br /><a href= \"../administration/administrera.php\">Tillbaka</a>";
        exit;
?>