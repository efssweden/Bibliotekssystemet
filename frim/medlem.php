<?php
session_start();

    // Starta dokumentet.
    require("subrutiner/head.php");
    echo "<body><div id='ALLT'>";
	
    // Skriv ut bibliotekets exlibris på sidan.
    require("subrutiner/top_exlibris.php");
	
    // Ta reda på vilket steg av sidan som ska tas.
	$Steg = $_GET['Steg'];
    if (!isset($_GET['Typ'])) $Typ = "Vanligt";
    else $Typ = $_GET['Typ'];
    
    // "Vanligt" = vanligt medlemslån. "Reception" = receptionslån. "Special" = speciallån.

	// Töm SESSION på gammal data för att ingen gammal låntagare ska ligga kvar och slaska men spara biblioteket.
	$Bibliotek = $_SESSION['Bibliotek'];
	unset($_SESSION['Lantagarenamn']);
	unset($_SESSION['Lantagaregrad']);
	unset($_SESSION['Lantagareloge']);
	unset($_SESSION['Lantagaremedlem']);
	unset($_SESSION['Lantagareort']);
	unset($_SESSION['Lantagaremail']);
	$_SESSION['Bibliotek'] = "$Bibliotek";
	 
	// Skriv ut instruktioner.
    if ($Typ == "Reception") {    
    	if ($Steg == "RFID") echo "<div class='Text'>Anv&auml;nd den h&auml;r funktionen f&ouml;r att l&aring;na ut en bok till en <b>nyrecepierad broder i grad II</b> eller h&ouml;gre.<br /><br /></div>";
    	if ($Steg == "Temp") echo "<div class='Text'>Anv&auml;nd den h&auml;r funktionen f&ouml;r att l&aring;na ut en bok till en <b>nyrecepierad broder i grad II</b> eller h&ouml;gre.<br /><br /></div>";
	}
	
    if ($Typ == "Special") {    
    	if ($Steg == "RFID") echo "<div class='Text'>Anv&auml;nd den h&auml;r funktionen f&ouml;r att <b>speciall&aring;na</b> ut b&ouml;cker.<br /><br /></div>";
    	if ($Steg == "Temp") echo "<div class='Text'>Anv&auml;nd den h&auml;r funktionen f&ouml;r att <b>speciall&aring;na</b> ut b&ouml;cker.<br /><br /></div>";
	}

	if ($Steg == "RFID") echo "<div class='Text'>Scanna av l&aring;ntagarens medlemskort eller tryck Tillbaka.<br /><br /></div>";
	else echo "<div class='Text'>Skriv in l&aring;ntagarens medlemsnummer eller tryck Tillbaka.<br /><br /></div>";
	
	// Öppna ett formulär för kortnumret.
	echo "<form action='medlem.php?Steg=".$Steg."&Typ=".$Typ."' name='medlem' method='post' enctype='application/form-data'>";
	
	// Kontrollera om man loggar in via "Sök broder" och hämta upp kortnumret i så fall.
	if(isset($_GET['Inlogg'])) {
		$Inlogg = $_GET['Inlogg'];
	}
	else $Inlogg = "";
	
	// Skriv ut en inmatningsruta för kortnumret och fyll i kortnumret om man kommer från "Sök broder"-funktionen.
	echo "<input name='nummer' type='text' class='Text' size='50' value='$Inlogg'><br />";
	
	// Skriv ut knappar för att "Registrera" och "Tillbaka".
	echo "<input type='submit' value='Registrera' class='Text'>";
    if ($Typ == "Vanligt") echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"fm.php\" ' class='Text' />";
    if ($Typ <> "Vanligt") echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"administration/administrera.php\" ' class='Text' />";

	// Skriv ut en knapp för att söka efter en broder som inte har sitt kort med sig.
	if ($Typ == "Vanligt") echo "<button type='button' onClick='parent.location=\"administration/medlem_hantering.php?Steg=Sok\"' class='Text'>S&ouml;k broder</button>";
    
    // Avsluta formuläret.
    echo "</form><br />";

	// Skriv ut en rad om sökningen.
	if ($Typ == "Vanligt") {
        // Skriv ut en hjälpknapp.
        $HELPID = 6;
        require ("subrutiner/help.php");
	}
		
	if ($Typ == "Special") {
        // Skriv ut en hjälpknapp.
        $HELPID = 9;
        require ("subrutiner/help.php");
	}
		
	if ($Typ == "Reception") {
        // Skriv ut en hjälpknapp.
        $HELPID = 10;
        require ("subrutiner/help.php");
	}
		
	// Skriv ut information om special- och receptionslån.
#	if ($Typ <> "Vanligt") echo "<br /><div class='Text'>Skriv in broderns medlemsnummer om han gl&ouml;mt sitt medlemskort f&ouml;r <b>special- och receptionsl&aring;n</b>.";
    
	// Sätt fokus på inmatningsrutan så att markören alltid står där från början.
	echo "<script type='text/javascript'>document.medlem.nummer.focus()</script>";		

    // Avsluta dokumentet.
    echo "</div></body></html>";

	// Kontrollera kortnumret och hitta medlemmen i databasen om numret är okej.
	if (isset($_POST['nummer'])) {

		// Hämta information om medlemmen från databasen medlem.
		$MEDLEMKORT	 = $_POST['nummer'];
        
        // Kontrollera så att strängen inte är tom.
        if (!empty($MEDLEMSKORT)) {
            header("location:medlem.php?Steg=$Steg&Typ=$Typ");
            exit();
        }
        
		// Undersök strängen för att avgöra om det inscannats ett kortnummer eller skrivits in ett medlemsnummer.
		if (strlen($MEDLEMKORT) == 12) $Steg = "RFID";
        if (substr($MEDLEMKORT,4,1) == "-") $Steg = "Temp";

        // Fyll på med nollor i början kortnumret om det saknas.
        if ($Steg <> "Temp" && strlen($MEDLEMKORT) <> 12) {
            $Stranglangd = strlen($MEDLEMKORT);
            if ($Stranglangd == 9) $MEDLEMKORT = "000$MEDLEMKORT";
            if ($Stranglangd == 10) $MEDLEMKORT = "00$MEDLEMKORT";
            if ($Stranglangd == 11) $MEDLEMKORT = "0$MEDLEMKORT";
            $Steg = "RFID";
		}
		
		if ($Steg == "RFID") $Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEMKORT = $MEDLEMKORT";
		if ($Steg == "Temp" || $Steg == "") {
			$MEDLEMKORT = str_replace("-", "", $MEDLEMKORT);
			$Hamtamedlem = "SELECT * FROM medlem WHERE MEDLEM = $MEDLEMKORT";
		}
		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$result = mysqli_query($db,$Hamtamedlem)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Varna om man skrivit in ett medlemsnummer som inte finns registrerat.
		if (!mysqli_num_rows($result)) {
			if ($Steg == "RFID") header("location:varning.php?Varning=200");
			if ($Steg == "Temp") header("location:varning.php?Varning=200");
		}
		else {

			// Läs data om låntagaren i databasen medlem.
			while ($row = mysqli_fetch_array($result)) {
				$Lantagaremedlem= $row["MEDLEM"];
				$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
				$Lantagaregrad	= $row["MEDLEMGRAD"];
				$Lantagareloge	= $row["MEDLEMLOGE"];
				$Lantagaremail	= $row["MEDLEMMAIL"];
				$Lantagareort	= $row["MEDLEMORT"];
			}

			// Skriv ut låntagarens grad i klartext.
			if ($Lantagaregrad == 1) $Grad = ", I";
			if ($Lantagaregrad == 2) $Grad = ", II";
			if ($Lantagaregrad == 3) $Grad = ", III";
			if ($Lantagaregrad == 4) $Grad = ", IV-V";
			if ($Lantagaregrad == 6) $Grad = ", VI";
			if ($Lantagaregrad == 7) $Grad = ", VII";
			if ($Lantagaregrad == 8) $Grad = ", VIII";
			if ($Lantagaregrad == 9) $Grad = ", IX";
			if ($Lantagaregrad == 10) $Grad = ", X";
			if ($Lantagaregrad == 11) $Grad = ", XI";
 
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

			// Kontrollera om låntagaren står med på "Svarta listan".
#			$Kollasvartlistan = "SELECT * FROM svartalistan WHERE MEDLEM = $Lantagaremedlem";
			
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
#			if (!$result = mysqli_query($db,$Kollasvartlistan)) {
#				die('Invalid query: ' . mysqli_error($db));
#			}
			
            // Om brodern är svartlistad så varna om detta.
#			if (mysqli_num_rows($result)) {
#				header("location:varning.php?Varning=123");
#			}
			
			// Hoppa till lånesidan för att fortsätta låneprocessen.
			echo "<script language='javascript' type='text/javascript'>window.location.href = \"lana.php?Typ=$Typ\"</script>";			
		}
	}
?>