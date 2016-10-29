<?php
	session_start();
    // Kontrollera om det är administratorn som loggat in och skicka honom vidare i så fall.
    if ($_SERVER["PHP_AUTH_USER"] == "9999") {
        echo "<script language='javascript' type='text/javascript'>window.location.href = \"administration/administrera.php\"</script>";
        exit();
    }
        
    // Starta dokumentet.
    require("subrutiner/head.php");
	echo "<body><div id='ALLT'>";

    // Kontrollera om strängen Steg är satt och hantera den i så fall.
	if (isset($_GET['Steg'])) $Steg = $_GET['Steg'];
	
	// Scanna in bibliotekarien.
	if (!isset($Steg)) {

        // Kolla upp vilket bibliotek det är som loggat in och lagra detta i en cookie.
        $BIBLIOTEKNR = $_SERVER['PHP_AUTH_USER'];
		$_SESSION['Bibliotek'] = $BIBLIOTEKNR;

        // Skriv ut bibliotekets exlibris på sidan.
        require("subrutiner/top_exlibris.php");
    
		// Läs data om det valda biblioteket.
		$Hamtabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR=$BIBLIOTEKNR";
	
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Hamtabibliotek)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
        while ($row = mysqli_fetch_array($result)) {
			$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];
		}

		// Skriv ut det valda biblioteket och dagens datum på en rad.
		echo "<div class='Rubrik'>$BIBLIOTEKNAMN ($BIBLIOTEKNR) - ".date('Y-m-d',$Serveridag)."</div>";

		// Skriv ut instruktioner.
		echo "<br /><div class='Text'>Tj&auml;nstg&ouml;rande bibliotekarie, scanna av ditt medlemskort eller skriv in ditt medlemsnummer!<br /><br /></div>";
			
		// Starta ett formulär.
		echo "<form action='fm_inlogg.php?Steg=Klar' name='nummer' method='post' enctype='application/form-data'>";
	
		// Skriv ut en inmatningsruta.
		echo "<input name='nummer' type='text' class='Text' size='50' ><br />";
			
		// Skriv ut en knapp för att starta.
		echo "<input type='submit' value='Starta' class='Text'></form><br />";
        
        // Skriv ut en hjälpknapp.
        $HELPID = 2;
        require ("subrutiner/help.php");
			
		// Sätt fokus på inmatningsrutan så att markören alltid står där från början.
		echo "<script type='text/javascript'>document.nummer.nummer.focus()</script>";
        
        // Avsluta dokumentet.
        echo "</div></body></html>";
    }

    // Hämta information om bibliotekarien om inmatningsrutan fyllts i.
	if ($Steg == "Klar") {

		// Kolla om man scannat in kortnummer eller skrivit in medlemsnummer.
		$MEDLEMKORT	= $_POST['nummer'];
           
        if (strlen($MEDLEMKORT) == 12) $Scan = "RFID";
		if (substr($MEDLEMKORT,4,1) == "-") $Scan = "Medlem";
			
        // Fyll på med nollor i början kortnumret om det saknas.
        if ($Scan <> "Medlem" && strlen($MEDLEMKORT) <> 12) {
            $Stranglangd = strlen($MEDLEMKORT);
            if ($Stranglangd == 9) $MEDLEMKORT = "000$MEDLEMKORT";
            if ($Stranglangd == 10) $MEDLEMKORT = "00$MEDLEMKORT";
            if ($Stranglangd == 11) $MEDLEMKORT = "0$MEDLEMKORT";
            $Scan = "RFID";
		}
            
		// Förbered hämntning av information om medlemmen från databasen medlem beroende på om man skannat kortet eller skrivit in medlemsnumret för hand.
		if ($Scan == "RFID") $Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEMKORT = $MEDLEMKORT";
		if ($Scan == "Medlem") {
			$MEDLEMKORT = str_replace("-", "", $MEDLEMKORT);
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEMKORT";
		}		
			
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Hamtamedlem)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Varna om man skrivit in ett medlemsnummer som inte finns registrerat, annars gå vidare i inläsningen...
		if (!mysqli_num_rows($result)) {
			if ($Scan == "RFID") header("location:varning.php?Varning=000");
			if ($Scan == "Medlem") header("location:varning.php?Varning=010");
		}
		else {

            // Läs in tjänstgörande bibliotekaries medlemsnummer från databasen medlem.
			while ($row = mysqli_fetch_array($result)) {
				$Bibliotekariemedlem= $row["MEDLEM"]; 
			}
				
            // Kontrollera om tjänstgörande redan loggat in idag.
            $Kollatjanstgorande = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND BIBLIOTEK = ".$_SESSION['Bibliotek']." AND MEDLEM = 'tj".$Bibliotekariemedlem."' ";

           	// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
            if (!$resulttjanstgorande = mysqli_query($db,$Kollatjanstgorande)) {
                die('Invalid query: ' . mysqli_error($db));
            }
                
            // Om tjänstgörande inte är registrerad så lägg till honom i besökslistan.
            $Tjanstgorande = mysqli_num_rows($resulttjanstgorande);
                
            if ($Tjanstgorande == 1) echo "";
            else {
                $Bibliotekariemedlem = "tj".$Bibliotekariemedlem;
                $Skrivbesok = "INSERT INTO besokslistan (DATUM,MEDLEM,BIBLIOTEK) VALUES ('$Serveridag','$Bibliotekariemedlem','".$_SESSION['Bibliotek']."')";		

				// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
				if (!$result = mysqli_query($db,$Skrivbesok)) {
				    die('Invalid query: ' . mysqli_error($db));
                }				
            }
        }
		
        // Hoppa till startsidan.
		echo "<script language='javascript' type='text/javascript'>window.location.href = \"fm.php\"</script>";			
    }
?>