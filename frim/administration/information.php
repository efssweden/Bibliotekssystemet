<?php
	session_start();
    
    // Starta dokumentet.
    require("../subrutiner/head.php");

	// Starta <body> och <div>.
	echo "<body><div id='ALLT'><br />";
    
    // Skriv ut rubriken.
    echo "<br /><div class='Rubrik'><b>Kortfattad information om Bibliotekssystemet.se</b></div><br />";
    
    // Generera en lista över alla orter som har ett bibliotekssystem aktivt.
    $Listabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR != '9999' ORDER BY ORT ASC";
    
    // Skicka en begäran till databasen, stoppa och visa felet om något går galet.
	if (!$result = mysqli_query($db,$Listabibliotek)) {
		die('Invalid query: ' . mysqli_error($db));
	}
    
    // Läs information från databasen bibliotek.
    $i = 0;
    while ($row = mysqli_fetch_array($result)) {
        $ORT            = $row["ORT"];
        $Bibliotek[$i] = $ORT;
        $i++;
    }
    
    // Sortera biblioteken.
    sort($Bibliotek);
    
    // Skapa en sträng av alla bibliotek.
    for($a=0; $a<=$i; $a++ ){
        $Biblioteken = "$Biblioteken$Bibliotek[$a], ";
    }
    $Biblioteken = substr($Biblioteken,0,strlen($Biblioteken)-4);
    
    echo "<div class='text'><b>Systemet finns idag i ".($i)." frimurarbibliotek, n&auml;mligen:</b><br />$Biblioteken.<br /><br /></div>";
    
    // Räkna titlar och annat av intresse.
    $Raknatitlar = "SELECT * FROM litteratur";
    
    if (!$result = mysqli_query($db,$Raknatitlar)) {
		die('Invalid query: ' . mysqli_error($db));
	}
    
    $Antaltitlar = mysqli_num_rows($result);
    
    $Raknabocker = "SELECT * FROM bocker";
    
    if (!$result = mysqli_query($db,$Raknabocker)) {
		die('Invalid query: ' . mysqli_error($db));
	}
    
    $Antalbocker = mysqli_num_rows($result);
    
    $Raknaaktiva = "SELECT * FROM aktiva";
    
    if (!$result = mysqli_query($db,$Raknaaktiva)) {
		die('Invalid query: ' . mysqli_error($db));
	}
    
    $Antalaktiva = mysqli_num_rows($result);
    
    $Raknaspecial = "SELECT * FROM special";
    
    if (!$result = mysqli_query($db,$Raknaspecial)) {
		die('Invalid query: ' . mysqli_error($db));
	}
    
    $Antalspecial = mysqli_num_rows($result);
    
    // Skriv ut informationen.
    echo "<div class='text'><b>Totalt finns i hela systemet idag:</b><br />";
    echo "$Antaltitlar titlar registrerade<br />";
    echo "$Antalbocker b&ouml;cker registrerade<br />";
    echo "$Antalaktiva p&aring;g&aring;ende l&aring;n registrerade<br />";
    echo "$Antalspecial p&aring;g&aring;ende speciall&aring;n registrerade<br />";
    
    // Skriv ut en Tillbaka-knapp.
    echo "<br /><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";
    
    // Avsluta sidan.
    echo "</div></body></html>";
?>