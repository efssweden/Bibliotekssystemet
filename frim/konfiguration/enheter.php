<?php 
session_start();

	// Den här rutinen används för att importera en lista över arbetsenheter i Svenska Frimurare Orden.
	// Listan hämtas från datorn med närvaroprogrammet (Sekreteraren vet vilken) och ligger vanligtvis här:
	// C:/Program/NärvaroRegistrering/arbetsenheter.xml
	// Denna fil måste göras om till en ny fil som heter arbetsenheter.csv och den måste ligga i samma mapp
	// som denna rutin. Titta först på den befintliga arbetsenheter.csv och lägg märke till hur den ser ut.
	// OBServera att den här listan bara behöver uppdateras om någon ny loge eller arbetsenhet skapats!
	// 2011-01-17 Johan Billing, Malmö
			 
	// Make connection to database and empty the arbetsenheter database.
	require ('konfig.php');
		$db = mysqli_connect($server, $db_user, $db_pass);
		mysqli_select_db($db,$database);
		mysqli_query($db,"TRUNCATE arbetsenheter");

	// Import data from CSV-file and update arbetsenheter database.
	if (($handle = fopen("arbetsenheter.csv", "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	
		// Get data from CSV-file and assign it to the various strings.
		$ENHETNR		= $data[0];					// The unit's number as stated by the SFMO.
		$ENHETNAMN		= $data[1];					// The unit's name.
				
		// Output strings to the arbetsenheter database.
		$WriteData = "INSERT INTO arbetsenheter (ENHETNR, ENHETNAMN) VALUES ('$ENHETNR','$ENHETNAMN')";
		if (!$result = mysqli_query($db,$WriteData)) {
			die('Invalid query: ' . mysqli_error($db));
			}
		}
	// Close CSV-file.
	fclose($handle);
	}
	
	// Return to fm.php.
	header("Location: ../administation/administrera.php"); 	
	exit;
?>
