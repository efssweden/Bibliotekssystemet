<?php

header('Content-disposition: attachment; filename=litteratur.txt');
header('Content-type: text/txt');

	// Kontakta databasen.
	require ('konfig.php');
	$db = mysqli_connect($server, $db_user, $db_pass);
	mysqli_select_db($db,$database);

    echo "TITELID\tKOD\tKODNUM\tTITEL\tFORFATTARE\tUTGIVNINGSAR\tGRAD\tSTUDIEPLAN\tBESKRIVNING\tREVIDERAD\tKATNR\tDATUM\tREGISTRATOR\tSIDOR\tSPRAK\tANTAL\n";
    
	// Förbered hämtningen av alla titlar.
	$Hamtatitel = "SELECT * FROM litteratur ORDER BY TITELID ASC";
    
	// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
	if (!$result = mysqli_query($db,$Hamtatitel)) {
		die('Invalid query: ' . mysqli_error($db));
	} 
	
    // Läs data om utvald titel i databasen litteratur.
	while ($row = mysqli_fetch_array($result)) {
		$TITELID		= $row["TITELID"];
		$KOD			= utf8_decode($row["KOD"]);
		$KODNUM			= utf8_decode($row["KODNUM"]);
		$TITEL			= utf8_decode($row["TITEL"]);
		$FORFATTARE		= utf8_decode($row["FORFATTARE"]);
		$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
		$GRAD			= $row["GRAD"];
		$STUDIEPLAN		= $row["STUDIEPLAN"];
		$BESKRIVNING	= utf8_decode($row["BESKRIVNING"]);
		$REVIDERAD		= $row["REVIDERAD"];
		$KATNR			= $row["KATNR"];
		$DATUM			= $row["DATUM"];
		$REGISTRATOR	= $row["REGISTRATOR"];
		$SIDOR      	= $row["SIDOR"];
		$SPRAK      	= $row["SPRAK"];
        
        $BESKRIVNING = str_replace("'","\'",$BESKRIVNING);
        $BESKRIVNING = str_replace('"','\"',$BESKRIVNING);
        $BESKRIVNING = nl2br($BESKRIVNING);
        $BESKRIVNING = str_replace('<br />','. ',$BESKRIVNING);
        $BESKRIVNING = preg_replace("/[\n\r]/",". ",$BESKRIVNING);
        $BESKRIVNING = trim($BESKRIVNING);
        
        $TITEL = str_replace("\"","",$TITEL);
        
        $Raknabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID";
        $Bockerresult = mysqli_query($db,$Raknabocker);
        $Antalbocker = mysqli_num_rows($Bockerresult);

    // Lägg till data i filen.
    echo "$TITELID\t\"$KOD\"\t\"$KODNUM\"\t\"$TITEL\"\t\"$FORFATTARE\"\t$UTGIVNINGSAR\t$GRAD\t$STUDIEPLAN\t\"$BESKRIVNING\"\t$REVIDERAD\t$KATNR\t$DATUM\t$REGISTRATOR\t$SIDOR\t$SPRAK\t$Antalbocker\n";
	}
?>