<?php

    // Kolla upp vilken sida som anropar rutinen och rensa filnamnet från pre- och suffix.
    $Sidnamn = basename($_SERVER['PHP_SELF'], '.php');
    
    // Skriv ut radbrytningar ovanför logon.
    if ($Sidnamn == "fm") echo "<br /><br /><br /><br /><br />";
    if ($Sidnamn == "fm_inlogg") echo "<br /><br /><br /><br /><br /><br /><br />";
    
    // Kolla upp vilken mapp sidan ligger i och flagga om sidan ligger i en undermapp.
    $Sidmapp = dirname($_SERVER['PHP_SELF']);
    if ($Sidmapp == "/frim") {
        $Grundmapp = "design/bibliotek/";
        $Returlink = "<a href='fm.php'>";
    }
    else {
        $Grundmapp = "../design/bibliotek/";
        $Returlink = "<a href='administrera.php'>";
    }
    
    // Kontrollera om det finns något Exlibris för biblioteket inlagt och om inte använd den generiska logon.
    $Exlibris = $Grundmapp.$_SESSION['Bibliotek'].".jpg";
    if (file_exists($Exlibris)) $Exlibris = $Grundmapp.$_SESSION['Bibliotek'].".jpg";
    else $Exlibris = $Grundmapp."Exlibris.jpg";
    
    // Skriv ut Exlibris med en länk tillbaka till huvudsidan.
    if ($Sidnamn <> "fm_inlogg") echo $Returlink."<img src='$Exlibris' title='Klicka h&auml;r f&ouml;r att &aring;terg&aring; till menyn!' border='0' /></a>";
    else echo "<img src='$Exlibris' title='Logo' border='0' />";
    
    // Skriv ut avslutande radbrytningar.
    echo "<br /><br />";

?>