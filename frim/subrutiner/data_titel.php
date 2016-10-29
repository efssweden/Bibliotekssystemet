<?php

	// Bestäm gradrubriken baserat på graden.
    $ROMERSKGRADRUBRIK = array("&Ouml;ppen litteratur","S:t Johannes Grad I","S:t Johannes Grad II","S:t Johannes Grad III","S:t Andreas Grad IV-V","","S:t Andreas Grad VI","Kapitel Grad VII","Kapitel Grad VIII","Kapitel Grad IX","Kapitel Grad X");
    $Gradrubrik = $ROMERSKGRADRUBRIK[$Gradnummer];

    // Lägg till kategorin i rubriken om det är kategorin man visar.
    if (isset($_GET['Kat'])) {
        
        // Läs kategorin från databasen kategorier.
        $Listakategorier = "SELECT * FROM kategorier WHERE KATNR = '".$_GET['Kat']."'";
        $Resultkat = mysqli_query($db,$Listakategorier);
        while ($row = mysqli_fetch_array($Resultkat)) {
			$KATEGORI = utf8_encode($row["KATEGORI"]);
        }
        $Gradrubrik = "&ldquo;$KATEGORI&rdquo; i $Gradrubrik";
    }

    // Lägg till biblioteket i rubriken om det är registrerade titlar från ett bibliotek man visar.
    if (isset($_GET['Bib'])) {
        
        // Läs namnet på biblioteket från databasen bibliotek.
        $Listabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR = '".$_GET['Bib']."'";
        $Resultbib = mysqli_query($db,$Listabibliotek);
        while ($row = mysqli_fetch_array($Resultbib)) {
            $BIBLIOTEKNAMN = $row["BIBLIOTEKNAMN"];
        }
        $Gradrubrik = "Registrerat av $BIBLIOTEKNAMN i $Gradrubrik";
    }

    // Lägg till språket i rubriken om det är titlar på ett visst språk man visar.
    if (isset($_GET['Sprak'])) {
        
        // Läs namnet på språket från databasen sprak.
        $Listasprak = "SELECT * FROM sprak WHERE SPRAKKORT = '".$_GET['Sprak']."'";
        $Resultsprak = mysqli_query($db,$Listasprak);
        while ($row = mysqli_fetch_array($Resultsprak)) {
            $SPRAKLANG = strtolower($row["SPRAKLANG"]);
        }
        $Gradrubrik = "Titlar p&aring; $SPRAKLANG i $Gradrubrik";
    }

    if ($Steg == "ListaTitlar") {
        
   			echo "<a name='$Gradnummer'></a> <div class='Rubrik'>$Gradrubrik ($Antaltitlar titlar)</div>";	
			echo "<table border='1' width='100%' cellpadding='5'>";	
			echo "<tr bgcolor='#f1f1f1'><td valign='top' align='left' class='Text' width='10'>Antal:</td><td valign='top' align='left' class='Text' >Titel och f&ouml;rfattare:</td></tr>";

    }

    // Berätta som det är om det inte finns några titlar att visa.    
#    if ($Sok == "Titel" && $Antaltitlar == 0) echo "<tr><td></td><td></td><td valign='top' align='left' class='Text' ><b>Det finns inga titlar att visa.</b></td><td valign='top' align='left' width='100' class='Text'></td>";
#    if ($Sok <> "Titel" && $Antaltitlar == 0) echo "<tr><td valign='top' align='left' class='Text' width='10'></td><td valign='top' align='left' class='Text' ><b>Det finns inga titlar att visa.</b></td>";
    if ($Sok == "Laggtill" && $Antaltraffar == 0) echo "<tr><td valign='top' align='left' class='Text' width='10'></td><td valign='top' align='left' class='Text' width='10'></td><td valign='top' align='left' class='Text' ><b>Det finns inga titlar att visa.</b></td>";
    if ($Antaltitlar <= 0 && $Sok <> "Laggtill" && $Sok <> "Titel") echo "<tr><td></td><td valign='top' align='left' class='Text' ><b>Det finns inga titlar att visa i denna grad.</b></td>";

	// Läs data om titeln i databasen litteratur.
	while ($row = mysqli_fetch_array($resulttitel)) {
		$TITELID		= $row["TITELID"];
		$KOD			= $row["KOD"];
		$TITEL			= $row["TITEL"];
		$FORFATTARE		= $row["FORFATTARE"];
		$UTGIVNINGSAR	= $row["UTGIVNINGSAR"];
		$GRAD			= $row["GRAD"];
		$STUDIEPLAN		= $row["STUDIEPLAN"];
		$BESKRIVNING	= $row["BESKRIVNING"];
		$REVIDERAD		= $row["REVIDERAD"];
		$KATNR			= $row["KATNR"];
		$LAN			= $row["LAN"];
        $DATUM          = $row["DATUM"];
        $REGISTRATOR    = $row["REGISTRATOR"];
        $SIDOR          = $row["SIDOR"];
        $SPRAK          = $row["SPRAK"];
        $SAMLING        = $row["SAMLING"];
        $SAMLINGSID     = $row["SAMLINGSID"];
        
        // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
        if (!empty($KOD)) {
            
            $KOD = str_replace(":00",":",$KOD);
            $KOD = str_replace(":0",":",$KOD);
            $KOD = str_replace(" 00"," ",$KOD);
            $KOD = str_replace(" 0"," ",$KOD);
        }
    
		// Hämta kategorin om det finns någon registrerad.
		if (empty($KATNR)) $KATEGORI = "";
		else {
		
			// Hämta kategorin från databasen kategorier.
            $Hamtakategori = "SELECT * FROM kategorier WHERE KATNR = '$KATNR'";
			$Kategoriresult = mysqli_query($db,$Hamtakategori);
    		while ($row = mysqli_fetch_array($Kategoriresult)) {
				$KATEGORI		= utf8_encode($row["KATEGORI"]);
			}
        }
        
		// Skriv ut graden i klartext.
        $ROMERSKAGRADER = array("&Ouml;ppen","I","II","III","IV-V","","VI","VII","VIII","IX","X");
        $GRAD = $ROMERSKAGRADER[$GRAD];
        
		// Kontrollera om titeln saknas och hantera det i så fall.
		if (empty($TITEL)) $TITEL = "<b><span class='red'>**Titel saknas**</span></b>";

		// Kontrollera om författaren saknas och hantera det i så fall.
		if (empty($FORFATTARE)) $FORFATTARE = "<b><span class='red'>**F&ouml;rfattare saknas**</span></b>";

		// Kontrollera om utgivningsåret saknas och/eller hantera det.
		if (empty($UTGIVNINGSAR)) $UTGIVNINGSAR = "";
		else $UTGIVNINGSAR = " ($UTGIVNINGSAR)";
		if (empty($REVIDERAD)) $REVIDERAD = "";
		else $UTGIVNINGSAR = "$UTGIVNINGSAR Reviderad $REVIDERAD";
        if ($_SERVER['PHP_AUTH_USER'] == "9999") $UTGIVNINGSAR = "$UTGIVNINGSAR #$TITELID";

		// Kontrollera om beskrivningen saknas och hantera det i så fall.
		if (empty($BESKRIVNING)) $BESKRIVNING = "<br />";
		else {
            $BESKRIVNING = "<br /><br />$BESKRIVNING";
            // Lägg till en punkt på slutet om det inte redan finns.
            if (substr($BESKRIVNING, -1) <> ".") $BESKRIVNING = $BESKRIVNING.".";
        }
		
   		// Kontrollera om kategorin saknas och hantera det i så fall.
        if (empty($KATNR) || isset($_GET['Kat'])) $KATEGORI = "";
		else $KATEGORI = "<br />Kategori: $KATEGORI";

        // Kontrollera om antalet sidor registrerats.
        if (empty($SIDOR)) $SIDOR = "<br />";
        else $SIDOR = "<br />$SIDOR sidor.";
        
        // Kontrollera om titelns språk har registrerats.
        if (empty($SPRAK) || isset($_GET['Sprak'])) $SPRAK = "";
        else {
            
            // Hämta språket från databasen sprak.
            $Hamtasprak = "SELECT * FROM sprak WHERE SPRAKKORT = '$SPRAK'";
            $Sprakresult = mysqli_query($db,$Hamtasprak);
            while ($row = mysqli_fetch_array($Sprakresult)) {
                $SPRAKLANG = utf8_encode($row["SPRAKLANG"]);
            }
            $SPRAK = "Spr&aring;k: $SPRAKLANG. ";
        }
        
        // Kontrollera om det rör sig om en samlingsvolym.
        if ($SAMLING == 1 && $Steg == "Sokt") $Samlingsknapp = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel2&SAMLINGSID=$TITELID&Sparasom=Ny\"' class='Text' >+Inneh&aring;ll</button> ";
        else $Samlingsknapp = "";

        if ($SAMLING == 1) $Visasamling = "";
        else $Visasamling = "";

		// Kontrollera om titeln tillhör studieplanen och skriv i så fall ut det i klartext.
		if ($STUDIEPLAN == "B") $STUDIEPLAN = "<br /><span class='blue'>Studieplan: Bas</span>";
		if ($STUDIEPLAN == "K") $STUDIEPLAN = "<br /><span class='blue'>Studieplan: Komplettering</span>";
		if ($STUDIEPLAN == "F") $STUDIEPLAN = "<br /><span class='blue'>Studieplan: F&ouml;rdjupning</span>";		
		if ($STUDIEPLAN == "X") $STUDIEPLAN = " - <b><span class='red'>Titeln &auml;r tillbakadragen</span></b>";		

        // Visa vem som registrerat titeln om uppgiften är registrerad.
        $REGISTRERING = "";
        if ($REGISTRATOR <> 0 && $_SERVER['PHP_AUTH_USER'] == "9999") {

            // Läs information från databasen bibliotek.
            $Hamtabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR = $REGISTRATOR";
            $Resultbibliotek = mysqli_query($db,$Hamtabibliotek);
            while ($row = mysqli_fetch_array($Resultbibliotek)) {
       			$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];      
            }
                            
            $REGISTRERING = "<br />Registrerad av $BIBLIOTEKNAMN ".date('Y-m-d',$DATUM)."";
            if (isset($_GET['Bib'])) $REGISTRERING = "<br />Registrerad ".date('Y-m-d',$DATUM)."";
            if ($REGISTRATOR == "9999") $REGISTRERING = "";
        }
        
        // Kontrollera om det är en sökning som sker och markera i så fall sökordet där det dyker upp.
        if ($Sok == "Titel" || $Sok == "Laggtill") {
            
            $Sokordlangd = strlen($Hitta);
            $TITELstart = stripos($TITEL,$Hitta);
            $FORFATTAREstart = stripos($FORFATTARE,$Hitta);
            $BESKRIVNINGstart = stripos($BESKRIVNING,$Hitta);
            $KODstart = stripos($KOD,$Hitta);
            
            if ($TITELstart === false) {}
            else {
                $TITELpre = substr($TITEL,0,$TITELstart);
                $Sokordet = "<span class='Sokresultat'>".substr($TITEL,$TITELstart,$Sokordlangd)."</span>";
                $TITELpost = substr($TITEL,$TITELstart+$Sokordlangd);
                $TITEL = "$TITELpre$Sokordet$TITELpost";
            }
            if ($FORFATTAREstart === false) {}
            else {
                $FORFATTAREpre = substr($FORFATTARE,0,$FORFATTAREstart);
                $Sokordet = "<span class='Sokresultat'>".substr($FORFATTARE,$FORFATTAREstart,$Sokordlangd)."</span>";
                $FORFATTAREpost = substr($FORFATTARE,$FORFATTAREstart+$Sokordlangd);
                $FORFATTARE = "$FORFATTAREpre$Sokordet$FORFATTAREpost";
            }
            if ($BESKRIVNINGstart === false) {}
            else {
                $BESKRIVNINGpre = substr($BESKRIVNING,0,$BESKRIVNINGstart);
                $Sokordet = "<span class='Sokresultat'>".substr($BESKRIVNING,$BESKRIVNINGstart,$Sokordlangd)."</span>";
                $BESKRIVNINGpost = substr($BESKRIVNING,$BESKRIVNINGstart+$Sokordlangd);
                $BESKRIVNING = "$BESKRIVNINGpre$Sokordet$BESKRIVNINGpost";
            }
            if ($KODstart === false) {}
            else {
                $KODpre = substr($KOD,0,$KODstart);
                $Sokordet = "<span class='Sokresultat'>".substr($KOD,$KODstart,$Sokordlangd)."</span>";
                $KODpost = substr($KOD,$KODstart+$Sokordlangd);
                $KOD = "$KODpre$Sokordet$KODpost";
            }
            
        }

		// Kontrollera hur många böcker som finns kopplade till titeln.
		$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN ASC";
		if ($_SERVER["PHP_AUTH_USER"] == "9999") $Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
        
		// Räkna antalet böcker.
        $Bokresult	= mysqli_query($db,$Listabocker);
		$Raknabocker = mysqli_num_rows($Bokresult);
		
        // Om det inte finns några böcker registrerade till titeln så visa en Radera-knapp om administratören tittar.
        if ($Raknabocker == 0 && $_SERVER['PHP_AUTH_USER'] == "9999") {
            $RADERA = " <button type='button' style='background-color: red' onClick='parent.location=\"titel_hantering.php?Steg=RaderaTitel&TITELID=$TITELID\"' class='Text' >-</button> ";
        }
        else $RADERA = "";
        $Visaandrasbocker = "";
        
        // Om det inte finns några böcker i just detta bibliotek så kolla om det finns någon annanstans.
        if ($Raknabocker == 0 || $_SERVER['PHP_AUTH_USER'] == "9999") {
            
            $Andrasbocker = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN NOT LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN ASC";
            if ($_SERVER['PHP_AUTH_USER'] == "9999") $Andrasbocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
            $Andrasresult	= mysqli_query($db,$Andrasbocker);
            $Raknaandrasbocker = mysqli_num_rows($Andrasresult);
            
            if ($Raknaandrasbocker == 0) $Visaandrasbocker = " <span class='red'>Det finns inga exemplar registrerade i n&aring;got bibliotek. </span>";
            
            if ($Raknaandrasbocker >= 1) {
                                
                // Kolla vilket bibliotek som har titeln registrerad.
                $Andrabibliotek = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN NOT LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN ASC";
                $Resultandra = mysqli_query($db,$Andrabibliotek);
                while ($row = mysqli_fetch_array($Resultandra)) {
                    $Bibliotek = substr($row["EAN"],0,4);
                
                    // Läs namnet på biblioteket från databasen bibliotek.
                    $Listabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR = '$Bibliotek'";
                    $Resultbib = mysqli_query($db,$Listabibliotek);
                    while ($row = mysqli_fetch_array($Resultbib)) {
                        $BIBLIOTEKNAMN = $row["BIBLIOTEKNAMN"];
                    }
                    
                    // Kontrollera så att ett bibliotek inte redan står med i listan och lägg till det om så inte är fallet.
                    if (strlen(strstr($Bibliotekandra,$BIBLIOTEKNAMN))>0) $Bibliotekandra = $Bibliotekandra;
                    else $Bibliotekandra = $Bibliotekandra."$BIBLIOTEKNAMN, ";
                    
                }
                // Ta bort det sista kommat i listan.
                if (substr($Bibliotekandra,-2,2) == ", ") $Bibliotekandra = substr($Bibliotekandra,0,strlen($Bibliotekandra)-2);

                
                if ($Raknaandrasbocker == 1) $Visaandrasbocker = " <span class='green'>Det finns $Raknaandrasbocker exemplar registrerat i $Bibliotekandra. </span>";
                else $Visaandrasbocker = " <span class='green'>Det finns totalt $Raknaandrasbocker exemplar registrerade i $Bibliotekandra. </span>";
                $Bibliotekandra = "";
            }
        }

        // Kontrollera om det handlar om ett innehåll i en samlingsvolym.
        if ($SAMLINGSID <> 0) {
            $Hamtasamling = "SELECT * FROM litteratur WHERE TITELID = $SAMLINGSID";
            $Samlingsresult = mysqli_query($db,$Hamtasamling);
            while ($row = mysqli_fetch_array($Samlingsresult)) {
                $Samlingsid = $row["TITELID"];
                $Samlingstitel = $row["TITEL"];
            }
            $Raknabocker = "";
            $Visaandrasbocker = "";
            $Visasamling = " (Del av <b>&ldquo;<a href = 'titel_hantering.php?Steg=Sokt&Visaid=$Samlingsid'>$Samlingstitel</a>&rdquo;</b>)";
        }
        
        // Kontrollera om det handlar om att visa en samlingsvolym.
        if ($SAMLING == 1) {
            $Hamtasamling = "SELECT * FROM litteratur WHERE SAMLINGSID = $TITELID ORDER BY TITEL ASC";
            $Samlingsresult = mysqli_query($db,$Hamtasamling);
            $FORFATTARE = "Samlingsvolym";
            $BESKRIVNING = $BESKRIVNING."<br />Samlingsvolym som inneh&aring;ller:";
            while ($row = mysqli_fetch_array($Samlingsresult)) {
                $Kapiteltitel = $row["TITEL"];
                $Kapitelforfattare = $row["FORFATTARE"];
                $Kapitelid = $row["TITELID"];
                
                $BESKRIVNING = $BESKRIVNING."<br /><b>&ldquo;<a href='titel_hantering.php?Steg=Sokt&Visaid=$Kapitelid'>$Kapiteltitel</a>&rdquo;</b> av $Kapitelforfattare.";
            }
        }
        
   		// Kontrollera om det är läge att skriva ut en Redigera-knapp. (Spärren bortplockad 2015-10-20 på begäran av GPL)
        $REDIGERA = "";
#        if ($_SESSION['Bibliotek'] == $REGISTRATOR) $REDIGERA = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel2&TITELID=$TITELID&Sparasom=Redigera\"' class='Text' >Redigera</button> ";
        $REDIGERA = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel2&TITELID=$TITELID&Sparasom=Redigera\"' class='Text' >Redigera</button> ";
        if ($_SERVER['PHP_AUTH_USER'] == "9999" || $_SERVER['PHP_AUTH_USER'] == "4011") $REDIGERA = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel2&TITELID=$TITELID&Sparasom=Redigera\"' class='Text' >Redigera</button> ";

        // Samla alla streckkoder i en sträng.
        $Streckkoder = "";
        if ($Raknabocker <> 0) {
            
            $Streckkodslista = "SELECT * FROM bocker WHERE TITELID = $TITELID AND EAN LIKE '".$_SESSION['Bibliotek']."%' ORDER BY EAN ASC";
            $Streckkodslistaresult	= mysqli_query($db,$Streckkodslista);
            while ($row = mysqli_fetch_array($Streckkodslistaresult)) {
                $Streckkoder = $Streckkoder.substr($row["EAN"],4).", ";
            }
            
            $Streckkoder = "<br />ID-nr: ".substr($Streckkoder,0,strlen($Streckkoder)-2);
            
        }        
        
		// Skriv ut titeln på en rad i tabellen och fortsätt till nästa titel.
        echo "<tr><td valign='top' align='center' class='Text' >$Raknabocker$RADERA</td>";
        if ($Sok == "Titel" || $Sok == "Laggtill") echo "<td valign='top' align='center' class='Text' >$GRAD</td>";
        echo "<td valign='top' align='left' class='Text'>$REDIGERA$Samlingsknapp$KOD <b>&ldquo;$TITEL&rdquo;</b>$Visasamling$UTGIVNINGSAR<br />F&ouml;rfattare: $FORFATTARE$KATEGORI$STUDIEPLAN$BESKRIVNING$SIDOR $SPRAK$Visaandrasbocker$Streckkoder$REGISTRERING</td>";
        if ($Sok == "Laggtill") echo "<td valign='top' align='center' class='Text' ><button type='button' onClick='parent.location=\"bok_hantering.php?Steg=Nybok2&TITELIDSELECT=$TITELID\"' class='Text' >L&auml;gg till bok</button></td>";
        echo "</tr>";

    }
?>