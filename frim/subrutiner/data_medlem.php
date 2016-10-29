<?php

    // Kolla upp vilken sida som anropar rutinen och rensa filnamnet från pre- och suffix.
    $Sidnamn = basename($_SERVER['PHP_SELF'], '.php');
    
    // Läs in information om en broder. (Förutsätter att $Hamtamedlem redan är definierad.)
	while ($row = mysqli_fetch_array($result)) {
		$Lantagaremedlem= $row["MEDLEM"];
		$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
		$Lantagaregrad	= $row["MEDLEMGRAD"];
		$Lantagareloge	= $row["MEDLEMLOGE"];
		$Lantagaremail	= $row["MEDLEMMAIL"];
		$Lantagareort	= $row["MEDLEMORT"];
	}

	// Skriv ut låntagarens grad i klartext.
    $Gradiklartext = array("",", I",", II",", III",", IV-V","",", VI",", VII",", VIII",", IX",", X");
    $Grad = $Gradiklartext[$Lantagaregrad];

	// Hämta låntagarens loge från databasen arbetsenheter och skriv ut den i klartext.
	$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $Lantagareloge";

	// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
	if (!$result = mysqli_query($db,$Hamtaloge)) {
		die('Invalid query: ' . mysqli_error($db));
	}

	// Läs in logens namn från databasen arbetsenheter.
	while ($row = mysqli_fetch_array($result)) {
		$Lantagareloge = $row["ENHETNAMN"];
	}

	// Kolla om det handlar om att förbereda ett utlån och hantera det i så fall.
    if ($Sidnamn == "medlem") {
        
        // Spara informationen om låntagaren i SESSIONs.
    	$_SESSION['Lantagarenamn'] = $Lantagarenamn;
    	$_SESSION['Lantagaregrad'] = $Lantagaregrad;
    	$_SESSION['Lantagareloge'] = $Lantagareloge;
    	$_SESSION['Lantagaremedlem'] = $Lantagaremedlem;
    	$_SESSION['Lantagareort'] = $Lantagareort;
    	$_SESSION['Lantagaremail'] = $Lantagaremail;
    	$_SESSION['Grad'] = $Grad;

		// Kontrollera om besökarens medlemsnummer redan finns registrerat i besökslistan.
		$Kollabesokare = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND MEDLEM = $Lantagaremedlem";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Kollabesokare)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Om brodern inte står i besökslistan så skriv in honom.
		if (!mysqli_num_rows($result)) {

			// Spara besökarens medlemsnummer i besökslistan.
			$Skrivbesok = "INSERT INTO besokslistan (DATUM,MEDLEM,BIBLIOTEK) VALUES ('$Serveridag','$Lantagaremedlem','".$_SESSION['Bibliotek']."')";		

			// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
			if (!$result = mysqli_query($db,$Skrivbesok)) {
				die('Invalid query: ' . mysqli_error($db));
			}		
		}		
    }
?>