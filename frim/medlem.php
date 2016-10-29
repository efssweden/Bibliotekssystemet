<?php
session_start();

    // Starta dokumentet.
    require("subrutiner/head.php");
    echo "<body><div id='ALLT'>";
	
    // Skriv ut bibliotekets exlibris p� sidan.
    require("subrutiner/top_exlibris.php");
	
    // Ta reda p� vilket steg av sidan som ska tas.
	$Steg = $_GET['Steg'];
    if (!isset($_GET['Typ'])) $Typ = "Vanligt";
    else $Typ = $_GET['Typ'];
    
    // "Vanligt" = vanligt medlemsl�n. "Reception" = receptionsl�n. "Special" = speciall�n.

	// T�m SESSION p� gammal data f�r att ingen gammal l�ntagare ska ligga kvar och slaska men spara biblioteket.
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
	
	// �ppna ett formul�r f�r kortnumret.
	echo "<form action='medlem.php?Steg=".$Steg."&Typ=".$Typ."' name='medlem' method='post' enctype='application/form-data'>";
	
	// Kontrollera om man loggar in via "S�k broder" och h�mta upp kortnumret i s� fall.
	if(isset($_GET['Inlogg'])) {
		$Inlogg = $_GET['Inlogg'];
	}
	else $Inlogg = "";
	
	// Skriv ut en inmatningsruta f�r kortnumret och fyll i kortnumret om man kommer fr�n "S�k broder"-funktionen.
	echo "<input name='nummer' type='text' class='Text' size='50' value='$Inlogg'><br />";
	
	// Skriv ut knappar f�r att "Registrera" och "Tillbaka".
	echo "<input type='submit' value='Registrera' class='Text'>";
    if ($Typ == "Vanligt") echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"fm.php\" ' class='Text' />";
    if ($Typ <> "Vanligt") echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location.href = \"administration/administrera.php\" ' class='Text' />";

	// Skriv ut en knapp f�r att s�ka efter en broder som inte har sitt kort med sig.
	if ($Typ == "Vanligt") echo "<button type='button' onClick='parent.location=\"administration/medlem_hantering.php?Steg=Sok\"' class='Text'>S&ouml;k broder</button>";
    
    // Avsluta formul�ret.
    echo "</form><br />";

	// Skriv ut en rad om s�kningen.
	if ($Typ == "Vanligt") {
        // Skriv ut en hj�lpknapp.
        $HELPID = 6;
        require ("subrutiner/help.php");
	}
		
	if ($Typ == "Special") {
        // Skriv ut en hj�lpknapp.
        $HELPID = 9;
        require ("subrutiner/help.php");
	}
		
	if ($Typ == "Reception") {
        // Skriv ut en hj�lpknapp.
        $HELPID = 10;
        require ("subrutiner/help.php");
	}
		
	// Skriv ut information om special- och receptionsl�n.
#	if ($Typ <> "Vanligt") echo "<br /><div class='Text'>Skriv in broderns medlemsnummer om han gl&ouml;mt sitt medlemskort f&ouml;r <b>special- och receptionsl&aring;n</b>.";
    
	// S�tt fokus p� inmatningsrutan s� att mark�ren alltid st�r d�r fr�n b�rjan.
	echo "<script type='text/javascript'>document.medlem.nummer.focus()</script>";		

    // Avsluta dokumentet.
    echo "</div></body></html>";

	// Kontrollera kortnumret och hitta medlemmen i databasen om numret �r okej.
	if (isset($_POST['nummer'])) {

		// H�mta information om medlemmen fr�n databasen medlem.
		$MEDLEMKORT	 = $_POST['nummer'];
        
        // Kontrollera s� att str�ngen inte �r tom.
        if (!empty($MEDLEMSKORT)) {
            header("location:medlem.php?Steg=$Steg&Typ=$Typ");
            exit();
        }
        
		// Unders�k str�ngen f�r att avg�ra om det inscannats ett kortnummer eller skrivits in ett medlemsnummer.
		if (strlen($MEDLEMKORT) == 12) $Steg = "RFID";
        if (substr($MEDLEMKORT,4,1) == "-") $Steg = "Temp";

        // Fyll p� med nollor i b�rjan kortnumret om det saknas.
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
		// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
		if (!$result = mysqli_query($db,$Hamtamedlem)) {
			die('Invalid query: ' . mysqli_error($db));
		}

		// Varna om man skrivit in ett medlemsnummer som inte finns registrerat.
		if (!mysqli_num_rows($result)) {
			if ($Steg == "RFID") header("location:varning.php?Varning=200");
			if ($Steg == "Temp") header("location:varning.php?Varning=200");
		}
		else {

			// L�s data om l�ntagaren i databasen medlem.
			while ($row = mysqli_fetch_array($result)) {
				$Lantagaremedlem= $row["MEDLEM"];
				$Lantagarenamn	= $row["MEDLEMFNAMN"]." ".$row["MEDLEMNAMN"];
				$Lantagaregrad	= $row["MEDLEMGRAD"];
				$Lantagareloge	= $row["MEDLEMLOGE"];
				$Lantagaremail	= $row["MEDLEMMAIL"];
				$Lantagareort	= $row["MEDLEMORT"];
			}

			// Skriv ut l�ntagarens grad i klartext.
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
 
			// H�mta l�ntagarens loge fr�n databasen arbetsenheter och skriv ut den i klartext.
			$Hamtaloge = "SELECT * FROM arbetsenheter WHERE ENHETNR = $Lantagareloge";

			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
			if (!$result = mysqli_query($db,$Hamtaloge)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// L�s in logens namn fr�n databasen arbetsenheter.
			while ($row = mysqli_fetch_array($result)) {
				$Lantagareloge = $row["ENHETNAMN"];
			}

			// Spara informationen om l�ntagaren i SESSIONs.
			$_SESSION['Lantagarenamn'] = $Lantagarenamn;
			$_SESSION['Lantagaregrad'] = $Lantagaregrad;
			$_SESSION['Lantagareloge'] = $Lantagareloge;
			$_SESSION['Lantagaremedlem'] = $Lantagaremedlem;
			$_SESSION['Lantagareort'] = $Lantagareort;
			$_SESSION['Lantagaremail'] = $Lantagaremail;
			$_SESSION['Grad'] = $Grad;
			
			// Kontrollera om bes�karens medlemsnummer redan finns registrerat i bes�kslistan.
			$Kollabesokare = "SELECT * FROM besokslistan WHERE DATUM = $Serveridag AND MEDLEM = $Lantagaremedlem";

			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
			if (!$result = mysqli_query($db,$Kollabesokare)) {
				die('Invalid query: ' . mysqli_error($db));
			}

			// Om brodern inte st�r i bes�kslistan s� skriv in honom.
			if (!mysqli_num_rows($result)) {

				// Spara bes�karens medlemsnummer i bes�kslistan.
				$Skrivbesok = "INSERT INTO besokslistan (DATUM,MEDLEM,BIBLIOTEK) VALUES ('$Serveridag','$Lantagaremedlem','".$_SESSION['Bibliotek']."')";		

				// Skicka skrivstr�ngen till databasen, stoppa och visa felet om n�got g�r galet.
				if (!$result = mysqli_query($db,$Skrivbesok)) {
					die('Invalid query: ' . mysqli_error($db));
				}		
			}		

			// Kontrollera om l�ntagaren st�r med p� "Svarta listan".
#			$Kollasvartlistan = "SELECT * FROM svartalistan WHERE MEDLEM = $Lantagaremedlem";
			
			// Skicka beg�ran till databasen. Stoppa och visa felet om n�got g�r snett.
#			if (!$result = mysqli_query($db,$Kollasvartlistan)) {
#				die('Invalid query: ' . mysqli_error($db));
#			}
			
            // Om brodern �r svartlistad s� varna om detta.
#			if (mysqli_num_rows($result)) {
#				header("location:varning.php?Varning=123");
#			}
			
			// Hoppa till l�nesidan f�r att forts�tta l�neprocessen.
			echo "<script language='javascript' type='text/javascript'>window.location.href = \"lana.php?Typ=$Typ\"</script>";			
		}
	}
?>