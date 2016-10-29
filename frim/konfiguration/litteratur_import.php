<?php

	// Set up HTML.
	echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
	echo "<html xmlns='http://www.w3.org/1999/xhtml'>";

	echo "<head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8' /><title>Importera data fr&aring;n CSV-fil</title>";
	echo "</head>";
	echo "<body><html>";
	
	// Make connection to database and empty the litteratur database.
	require ('konfig.php');
		$db = mysqli_connect($server, $db_user, $db_pass);
		mysqli_select_db($db,$database);
#		mysqli_query($db,"TRUNCATE litteratur");

	// Get todays date from the server and set up a string for today.
	$ServerYear		= date("Y");
	$ServerMonth 	= date("m");
	$ServerDay 		= date("d");
	$ServerToday	= mktime(0,0,0,$ServerMonth,$ServerDay,$ServerYear);
		
	// Import data from CSV-file and update litteratur database.
	if (($handle = fopen("litteratur.csv", "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	
            // Töm gammal data först.
            $KOD = "";
            $TITEL = "";
            $FORFATTARE = "";
            $UTGIVNINGSAR = "";
            $DATUM = "";
            $GRAD = "";
            $STUDIEPLAN = "";
            $BESKRIVNING = "";
            $REVIDERAD = "";
            $KATNR = "";
            $SAMLINGSID = "";
            $REGISTRATOR = "";
            $SIDOR = "";
            $SPRAK = "";
            
    		// Get data from CSV-file and assign it to the various strings.
            $TITELID        = $data[0];
            $KOD            = $data[1];
            $TITEL          = $data[2];
            $FORFATTARE     = $data[3];
            $UTGIVNINGSAR   = $data[4];
            $GRAD           = $data[5];
            $STUDIEPLAN     = $data[6];
            $BESKRIVNING    = $data[7];
            $REVIDERAD      = $data[8];
            $KATNR          = $data[9];
            $DATUM          = $data[10];
            $REGISTRATOR    = $data[11];
            $SIDOR          = $data[12];
            $SPRAK          = $data[13];
            $SAMLINGSID     = $data[14];
            
#            if (empty($DATUM)) $DATUM = "1293840000";
            
    		// Output strings to the litteratur database.
    		$Uppdateradata = "UPDATE litteratur SET KOD='$KOD',TITEL='$TITEL',FORFATTARE='$FORFATTARE',UTGIVNINGSAR='$UTGIVNINGSAR',DATUM='$DATUM',GRAD='$GRAD',STUDIEPLAN='$STUDIEPLAN',BESKRIVNING='$BESKRIVNING',REVIDERAD='$REVIDERAD',KATNR='$KATNR',REGISTRATOR='$REGISTRATOR',SIDOR='$SIDOR',SPRAK='$SPRAK' WHERE TITELID='$TITELID'";

    		if (!$result = mysqli_query($db,Uppdateradata)) {
    			die('Invalid query: ' . mysqli_error($db));
    		}
    	}
	
    // Close CSV-file.
	fclose($handle);
	}
						
	// Close HTML.
	echo "</body></html>";

	// Return to fm.php.
	header("Location: ../administration/administrera.php"); 	
	exit;
?>