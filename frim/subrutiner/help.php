<?php
	// Hämta det valda avsnittet från databasen help.
	$Hamtahelp = "SELECT * FROM help WHERE HELPID = '$HELPID'";
	$result = mysqli_query($db,$Hamtahelp);
	while ($row = mysqli_fetch_array($result)) {
        $HELPRUBRIK = $row["HELPRUBRIK"];
        $HELPTEXT = $row["HELPTEXT"];
    }
    // Kolla upp vilken mapp sidan ligger i och flagga om sidan ligger i en undermapp.
    $Sidmapp = dirname($_SERVER['PHP_SELF']);
    if ($Sidmapp == "/frim") {
        $Grundmapp = "";
    }
    else {
        $Grundmapp = "../";
    }
    echo "<div id='blanket' style='display:none;'></div>";
    echo "<div id='popUpDiv' style='display:none;'><div class='Rubrik' align='center'>$HELPRUBRIK</div><span class='Text'>$HELPTEXT</span><br /><br /><div align='center'><button type='button' href='#' onclick='popup(\"popUpDiv\")' class='Text'>St&auml;ng hj&auml;lprutan <img src='".$Grundmapp."design/close_icon.png' align='center'></button></div></div>";
    echo "<button type='button' href='#' onclick='popup(\"popUpDiv\")' class='Text' >Visa hj&auml;lp <img src='".$Grundmapp."design/help_icon.png' align='center'></button>";
?>