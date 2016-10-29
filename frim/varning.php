<?php

	// Hämta varningskod.
	$Varning = "".$_GET['Varning']."";
	
    // Definiera hur texten ser ut.
    echo "<STYLE TYPE='text/css'>.Varning {color:white; font-size:large; font-family:Georgia, Times New Roman, Times, serif; font-style: italic; text-align: center; font-weight: bold}";
    echo ".Knapp {color:black; font-size:large; font-family:Georgia, Times New Roman, Times, serif; text-align: center; font-weight: bold}</STYLE>";
    
    // Definiera retursträngen.
    $Felretur ["0"] = "fm_inlogg.php";
    $Felretur ["1"] = "fm.php";
    $Felretur ["2"] = "medlem.php?Steg=RFID&Typ=Vanligt";
    $Felretur ["3"] = "medlem.php?Steg=RFID&Typ=Reception";
    $Felretur ["4"] = "medlem.php?Steg=RFID&Typ=Special";
    $Felretur ["5"] = "lana.php?Typ=Vanligt";
    $Felretur ["6"] = "lana.php?Typ=Reception";
    $Felretur ["7"] = "lana.php?Typ=Special";
    $Felretur ["8"] = "tillbaka.php";
    $Felretur ["9"] = "administration/administrera.php";
    $Felretur ["a"] = "administration/bok_hantering.php?Steg=Nybok";
    
    // Definiera felmeddelandet.
    $Felmeddelande ["0"] = "Detta medlemskort finns inte registrerat i systemet!";
    $Felmeddelande ["1"] = "Detta medlemsnummer finns inte registrerat i systemet!";
    $Felmeddelande ["2"] = "L&aring;ntagaren har inte l&auml;mnat tillbaka redan l&aring;nade b&ouml;cker!";
    $Felmeddelande ["3"] = "Streckkoden &auml;r felaktig. Prova igen!";
    $Felmeddelande ["4"] = "Boken finns inte registrerad i systemet!";
    $Felmeddelande ["5"] = "Boken &auml;r redan registrerad som utl&aring;nad!";
    $Felmeddelande ["6"] = "L&aring;ntagaren har inte tillr&auml;ckligt h&ouml;g grad!";
    $Felmeddelande ["7"] = "Titeln &auml;r tillbakadragen och f&aring;r inte l&aring;nas ut!";
    $Felmeddelande ["8"] = "Boken &auml;r inte registrerad eller tillh&ouml;r ett annat bibliotek!";
    $Felmeddelande ["9"] = "Boken &auml;r inte registrerad som utlånad!";
    $Felmeddelande ["a"] = "Boken &auml;r registrerad som tillbakal&auml;mnad. Tack!";
    $Felmeddelande ["b"] = "Det blev fel n&auml;r streckkoden l&auml;stes in. Prova igen!";
    $Felmeddelande ["c"] = "Streckkoden tillh&ouml;r ett annat bibliotek!";
    $Felmeddelande ["d"] = "Den h&auml;r streckkoden finns redan registrerad i systemet!";
    $Felmeddelande ["e"] = "Boken &auml;r nu registrerad i systemet. Tack!";
    $Felmeddelande ["f"] = "Boken &auml;r nu registrerad som makulerad.";
    
    // Definiera bakgrundsfärgen.
    $Felbakgrund ["0"] = "Red";
    $Felbakgrund ["1"] = "Green";
    $Felbakgrund ["2"] = "Blue";
    $Felbakgrund ["3"] = "Grey";
    
    // Etablera de tre strängarna som behövs för att skriva ut felmeddelandet.
    $Retur = $Felretur[substr($Varning,0,1)];
    $Meddelande = $Felmeddelande[substr($Varning,1,1)];
    $Bakgrund = $Felbakgrund[substr($Varning,2,1)];
    
    // Skriv ut bakgrundsfärg, meddelande och returlänk.
    echo "<body bgcolor='$Bakgrund'>";
    echo "<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><div class='Varning'>$Meddelande</div><br /><br />";
    echo "<div align='center'><button type='button' onClick='parent.location=\"$Retur\"' class='Knapp' >OK</button></div><br /><br />";

?>