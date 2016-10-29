<?php

    // Starta dokumentet.
    require ("../subrutiner/head.php");
        
    	// Ber�tta f�r systemet vad medlemsfilen heter.
    	$xml = simplexml_load_file('medlemmar.xml');
    
        // B�rja med att t�mma databasen medlemmar p� inneh�ll.
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
                
            	// Om medlemsnumret inte finns s� ers�tt det med ett slumpm�ssigt valt nummer, annars ta bort minustecknet i medlemsnumret.
            	if (!($data['medlemsnummer'])) $MEDLEM = rand(1, 998);
            	else $MEDLEM	= str_replace("-","",$data['medlemsnummer']);
    
                // L�s in broderns f�r- och efternamn och ta bort eventuella specialtecken.
            	$MEDLEMFNAMN	= mysqli_real_escape_string($db,$data['fornamn']);
            	$MEDLEMNAMN		= mysqli_real_escape_string($db,$data['efternamn']);
    
                // L�s in broderns grad och s�tt den som 4 om den r�kat bli 5.
                $MEDLEMGRAD		= $data['grad'];
                if ($MEDLEMGRAD == "5") $MEDLEMGRAD = "4";
    
                // L�s in broderns logetillh�righet.
            	$MEDLEMLOGE		= $data['enhetsid'];
    
                // L�s in broderns bostadsort och ta bort eventuella specialtecken.
            	$MEDLEMORT		= mysqli_real_escape_string($db,$data['ort']);
    
                // L�s in broderns kortnummer.
            	$MEDLEMKORT		= $data['kortnummer'];
    
        		// Skriv in brodern i databasen medlem.
        		$Skrivdata = "INSERT INTO medlem (MEDLEM, MEDLEMNAMN, MEDLEMFNAMN, MEDLEMGRAD, MEDLEMLOGE, MEDLEMKORT,MEDLEMORT) VALUES ('$MEDLEM','$MEDLEMNAMN','$MEDLEMFNAMN','$MEDLEMGRAD','$MEDLEMLOGE','$MEDLEMKORT','$MEDLEMORT')";
                echo "<tr><td>$MEDLEM</td><td>$MEDLEMNAMN, $MEDLEMFNAMN</td><td>$MEDLEMGRAD</td><td>$MEDLEMLOGE</td><td>$MEDLEMKORT</td><td>$MEDLEMORT</td></tr>";
    
                // Kontrollera s� att det inte blir n�got fel i kontakten med servern.
        		if (!$result = mysqli_query($db,$Skrivdata)) {
        			die('Invalid query: ' . mysqli_error($db));
        		}
            }
                
        	// Run callback and return
        	return (!is_array($data) && is_callable($callback))? call_user_func($callback, $data): $data;
    
        }
    
        // K�r funktionen unserialize_xml.
        echo "<table>";
        unserialize_xml($xml);
        echo "</table>";
    
        // Radera br�der med ogiltiga medlemsnummer fr�n databasen medlem.
        $Raderamedlem = "DELETE FROM medlem WHERE MEDLEM < 10000";
    
        // Skicka kommandot till servern och visa felet om det dyker upp n�got.
        if (!$result = mysqli_query($db,$Raderamedlem)) {
        	die('Invalid query: ' . mysqli_error($db));
        }
    
        // Komplettera med epostadresser fr�n filen medlem.csv som sekreteraren kan bist� med.
        if (($handle = fopen("medlem.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    
                // L�s data fr�n csv-filen och placera dem i respektive str�ng.
            	$MEDLEM       = str_replace("-","",$data[3]);
                $MEDLEMMAIL   = UTF8_encode($data[17]);
    
            	// Uppdatera databasen medlem med broderns epostadress.
            	$Uppdatera = "UPDATE medlem SET MEDLEMMAIL='$MEDLEMMAIL' WHERE MEDLEM = $MEDLEM";
    
            	// Skicka kommandot till servern och visa felet om det blir fel.
                if (!$result = mysqli_query($db,$Uppdatera)) {
                    die('Invalid query: ' . mysqli_error($db));
            	}
            }
    
            // St�ng filen.
            fclose($handle);
        }
    
        // Hoppa tillbaka till administrationssidan.
//        echo "<script language='javascript' type='text/javascript'>window.location.href = \"../administration/administrera.php\"</script>";
        echo "<br /><a href= \"../administration/administrera.php\">Tillbaka</a>";
        exit;
?>