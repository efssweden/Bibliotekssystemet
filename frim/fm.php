<?php
    session_start();
    
    // Släng tillbaka administratören om han råkat hamna här.
    if ($_SERVER["PHP_AUTH_USER"] == "9999") header("Location: fm_inlogg.php");
    
    // Starta dokumentet.
    require("subrutiner/head.php");
    echo "<body><div id='ALLT'>";

	$_SESSION['Steg'] = "Startalan";
	
    // Skriv ut logon på sidan.
    require("subrutiner/top_exlibris.php");
	
    // Skriv ut knapparna på sidan.
    echo "<button type='button' onClick='parent.location=\"studieplan.php?Steg=Grad\"' class='Text' style='width:200px; height:40px'>Visa studieplan</button><br /><br />";
    echo "<button type='button' onClick='parent.location=\"medlem.php?Steg=RFID\"' class='Text' style='width:200px; height:40px'>L&aring;na ut bok</button><br /><br />";
	echo "<button type='button' onClick='parent.location=\"tillbaka.php\"' class='Text' style='width:200px; height:40px'>L&auml;mna tillbaka bok</button><br /><br />";
	echo "<button type='button' onClick='parent.location=\"administration/administrera.php\"' class='Text' style='width:200px; height:40px'>Administrera</button><br /><br />";
			
	echo "<br /><br /><img src='design/LOGO_sfmo.jpg' title='Svenska Frimurare Orden' />";

	// Avsluta dokumentet.
    echo "</div></body></html>";
    
?>
