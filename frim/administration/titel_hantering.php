<?php
    session_start();
    
    // Starta dokumentet.
    require ("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";
    
    // Kolla om det finns något Exlibris till det aktuella biblioteket.
   	require ("../subrutiner/top_exlibris.php");
    
    // Ta reda på vilket steg av sidan som ska visas.
	$Steg = $_GET['Steg'];
 	if (isset($_GET['Sok'])) $Sok = $_GET['Sok'];
   
	// Sök titel.
	if ($Steg == "Sok") {
	
		// Öppna ett formulär för sökningen.
		if ($Sok == "Titel") echo "<form action='titel_hantering.php?Steg=Sokt&Sok=Titel' name='titel_hantering' method='post' enctype='application/form-data'>";
		else echo "<form action='titel_hantering.php?Steg=Sokt&Sok=Laggtill' name='titel_hantering' method='post' enctype='application/form-data'>";
		
		// Skriv ut en rubrik och öppna tabellen.
		echo "<div class='Rubrik'>Skriv in ett s&ouml;kord:</div>";
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		
		// Skriv ut raderna i tabellen.
		echo "<tr><td valign='top' align='left'><input name='SOKORD' type='Text' class='Text' size='50' ></td></tr>";
	
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "</table><br /><input type='submit' value='S&ouml;k' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></form><br />";	

        // Skriv ut en hjälpknapp.
        $HELPID = 12;
        require ("../subrutiner/help.php");
        
		// Sätt fokus på inmatningsrutan så att markören alltid står där från början.
		echo "<script type='text/javascript'>document.titel_hantering.SOKORD.focus()</script>";		
			
	}
	
	// Visa sökt titel.
	if ($Steg == "Sokt") {
	
		// Hämta upp söksträngen.
        if (isset($_GET["Visaid"])) {
            $ID = $_GET["Visaid"];
            $Sok = "Titel";
        }
        else {
    		$Hitta = $_POST['SOKORD'];
            $Sok = $_GET['Sok'];
            if (empty($Hitta)) {
                header("location:titel_hantering.php?Steg=Sok&Sok=$Sok");
                exit();
            }
        }
        $Hitta = mysqli_real_escape_string($db,$Hitta);

        
		// Leta efter söksträngen i databasen litteratur.
		$Hittatitel = "SELECT * FROM litteratur WHERE KOD LIKE '%$Hitta%' OR TITEL LIKE '%$Hitta%' OR FORFATTARE LIKE '%$Hitta%' OR KATNR LIKE '%$Hitta%' OR BESKRIVNING LIKE '%$Hitta%' ORDER BY GRAD, KOD, TITEL ASC";
        if (isset($_GET["Visaid"])) $Hittatitel = "SELECT * FROM litteratur WHERE TITELID = ".$_GET["Visaid"]."";

		// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
		if (!$resulttitel = mysqli_query($db,$Hittatitel)) {
			die('Invalid query: ' . mysqli_error($db));
		}
        
		// Räkna antalet träffar.
		$Antaltraffar	= mysqli_num_rows($resulttitel);

        // Om sökordet kammar noll så berätta det.
		if ($Antaltraffar == 0) {
			echo "<div class='Rubrik'>Inga tr&auml;ffar f&ouml;r s&ouml;kordet \"$Hitta\"</div><br />";
			if ($Sok == "Titel") echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Sok&Sok=Titel\"' class='Text'>S&ouml;k igen</button>";
			if ($Sok == "Laggtill") echo "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=Sok&Sok=Laggtill\"' class='Text'>S&ouml;k igen</button>";
			echo " <button type='button' onClick='parent.location=\"titel_hantering.php?Steg=RedigeraTitel2&amp;Sparasom=Ny\"' class='Text'>L&auml;gg till ny titel</button><br /><br />";
			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";
			exit ();
		}
		
		// Skriv ut en rubrik och öppna tabellen.
		if (!isset($_GET["Visaid"])) echo "<div class='Rubrik'>$Antaltraffar resultat f&ouml;r \"$Hitta\":</div>";
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		if ($Sok == "Titel") echo "<tr bgcolor='#f1f1f1'><td valign='top' align='left' class='Text' width='10'>Antal:</td><td valign='top' align='left' class='Text' >Grad:</td><td valign='top' align='left' class='Text' >Titel och f&ouml;rfattare:</td></tr>";
		else echo "<tr bgcolor='#f1f1f1'><td valign='top' align='left' class='Text' width='10'>Antal:</td><td valign='top' align='left' class='Text' >Grad:</td><td valign='top' align='left' class='Text' >Titel och f&ouml;rfattare:</td><td valign='top' align='left' class='Text' ></td></tr>";
				
        // Visa resultatet.
        require ("../subrutiner/data_titel.php");
						
		// Stäng tabellen och skriv ut "Tillbaka"-knapp.
		echo "</table><br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text'>Tillbaka</button><br /><br />";	
			
	}
	
	// Lista alla titlar i litteratur-databasen, sorterade efter grad.
	if ($Steg == "ListaTitlar") {

    	// Börja med att välja grad.
    	if (!isset($_POST['GRADSELECT']) && !isset($_GET['Kat']) && !isset($_GET['Bib']) && !isset($_GET['Sprak'])) {
    
    		// Öppna ett formulär för att välja grad.
    		echo "<form action='titel_hantering.php?Steg=ListaTitlar' name='Visa' method='post' enctype='application/form-data'>";
    
    		// Skriv ut en rubrik och öppna en tabell.
            echo "<div class='Rubrik'>V&auml;lj grad:</div>";
    		echo "<table align='center' class='Text' border='1' cellpadding='5' >";
            
            // Ta reda på hur många titlar som finns i respektive grad och spara informationen i en array.
            for ($a=0; $a<=10; $a++) {
                $Raknatitlar = "SELECT * FROM litteratur WHERE GRAD = $a";
                $result = mysqli_query($db,$Raknatitlar);
                $Antaltitlar = mysqli_num_rows($result);
                $Antalarray[$a] = $Antaltitlar;
            }
    
    		// Visa de olika graderna med en radioknapp för respektive grad.
    		echo "<tr><td valign='top' align='left'>";
            $Grader = array("&Ouml;ppen litteratur","S:t Johannes Grad I","S:t Johannes Grad II","S:t Johannes Grad III","S:t Andreas Grad IV-V","","S:t Andreas Grad VI","Kapitel Grad VII","Kapitel Grad VIII","Kapitel Grad IX","Kapitel Grad X");
            for ($i=0; $i<=10; $i++) {
                
                // Om graden inte är 5 så skriv ut en rad.
                if ($i <> 5) echo "<input type='radio' name='GRADSELECT' value='$i' class='Text' ";
                if ($i == 1) echo "checked";
                if ($i <> 5) echo "/> ".$Grader[$i]." (".$Antalarray[$i]." titlar)<br />";
            }
            echo "</td></tr>";
    		
    		// Skriv ut "Visa"- och "Tillbaka"-knappar och stäng tabellen.
    		echo "<tr><td valign='top' align='center'><input type='submit' value='Visa litteratur' class='Text' ><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" ' class='Text' /></td></tr></table></form><br />";	

            // Skriv ut en hjälpknapp.
            $HELPID = 17;
            require ("../subrutiner/help.php");
       	}
        
        // Visa litteraturen i den valda graden.
        if (isset($_POST['GRADSELECT']) && !isset($_GET['Kat']) && !isset($_GET['Bib']) && !isset($_GET['Sprak'])) {
            
            $Gradnummer = $_POST['GRADSELECT'];

    		// Skriv ut en lite förklarande text.
    		echo "<span class='Text'>Det h&auml;r &auml;r en lista &ouml;ver alla registrerade titlar i systemet.<br />Den f&ouml;rsta kolumnen (\"Antal\") visar hur m&aring;nga exemplar av titeln som finns registrerade i biblioteket.</span><br /><br />";
    		  		
    		// Hämta vald grads titlar från databasen.
       		$Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer ORDER BY KOD, TITEL ASC";
                
            // Kontrollera om man vill visa titlar i en speciell kategori.
            if (isset($_GET['Kat'])) $Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND KATNR = '".$_GET['Kat']."' ORDER BY KOD, TITEL ASC";
        
            // Kontrollera om man vill visa titlar från ett speciellt bibliotek.
            if (isset($_GET['Bib'])) $Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND REGISTRATOR = '".$_GET['Bib']."' ORDER BY KOD, TITEL ASC";
        
            // Kontrollera om man vill visa titlar på ett speciellt språk.
            if (isset($_GET['Sprak'])) $Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND SPRAK = '".$_GET['Sprak']."' ORDER BY KOD, TITEL ASC";
        
        	$Titlarresult	= mysqli_query($db,$Listatitlar);
        	$Antaltitlar	= mysqli_num_rows($Titlarresult);
            if ($Antaltitlar == 0) $Tomgrad = 1;
        
        	// Om gradnumret är 5 så gör ingenting, annars skriv ut en lista på gradens titlar.
        	if ($Gradnummer == 5) $Gradnummer = 5;
        	else {
        
                // Läs in titeln från databasen litteratur.
        		$resulttitel = mysqli_query($db,$Listatitlar); 
        
                // Visa titlarna.
                require ("../subrutiner/data_titel.php");
        			
        		// Stäng tabellen och fortsätt till nästa grad.
        		echo "</table>";
        		echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button><br /><br />";
      		}
        }
        
        // Om det är läge att visa kategorier eller likande...
        if (isset($_GET['Kat']) || isset($_GET['Bib']) || isset($_GET['Sprak'])) {

            // Visa snabblänkar till de olika graderna.
            require ("../subrutiner/lank_titel.php");
              
            // Starta en loop från grad 0 till grad 10 och hämta respektive grads titlar från databasen.
    		for ($Gradnummer = 0; $Gradnummer <= 10; $Gradnummer +=1){

                // Kontrollera om man vill visa titlar i en speciell kategori.
                if (isset($_GET['Kat'])) $Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND KATNR = '".$_GET['Kat']."' ORDER BY KOD, TITEL ASC";
        
                // Kontrollera om man vill visa titlar från ett speciellt bibliotek.
                if (isset($_GET['Bib'])) $Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND REGISTRATOR = '".$_GET['Bib']."' ORDER BY KOD, TITEL ASC";
        
                // Kontrollera om man vill visa titlar på ett speciellt språk.
                if (isset($_GET['Sprak'])) $Listatitlar = "SELECT * FROM litteratur WHERE GRAD = $Gradnummer AND SPRAK = '".$_GET['Sprak']."' ORDER BY KOD, TITEL ASC";

        		$Titlarresult	= mysqli_query($db,$Listatitlar);
        		$Antaltitlar	= mysqli_num_rows($Titlarresult);

        		// Om gradnumret är 5 så gör ingenting, annars skriv ut en lista på gradens titlar.
        		if ($Gradnummer == 5) $Gradnummer = 5;
        		else {

                    // Läs in titeln från databasen litteratur.
        			$resulttitel = mysqli_query($db,$Listatitlar); 
        
                    // Visa titlarna.
                    require ("../subrutiner/data_titel.php");
        			
        			// Stäng tabellen och fortsätt till nästa grad.
        			echo "</table>";
        			echo "<button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button><br /><br />";
                }
            }
        }
	}

	// Välj vilken titel som ska redigeras.
	if ($Steg == "RedigeraTitel") {

		// Hämta innehållet i databasen litteratur för att göra en meny.
		if ($_SERVER["REMOTE_USER"] <> "9999") $Listatitlar = "SELECT * FROM litteratur WHERE REGISTRATOR = ".$_SESSION["Bibliotek"]." ORDER BY GRAD, KOD, TITEL ASC";
        else $Listatitlar = "SELECT * FROM litteratur WHERE REGISTRATOR = ".$_SESSION["Bibliotek"]." ORDER BY GRAD, KOD, TITEL ASC";
        
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
			}
		
        // Om bibliotektet inte registrerat några titlar så meddela det.
        if (!mysqli_num_rows($result)) echo "<div class='Text'>Det finns inga titlar som registrerats av detta bibliotek</div><br /><button type='button' onClick='parent.location=\"administrera.php\"' class='Text' >Tillbaka</button>";
        
        // Om biblioteket registrerat titlar så visa dem i en lista.
        if (mysqli_num_rows($result) >= 1) {
    	
        	// Öppna ett formulär.
    		echo "<form name='form' id='form'><select class='Text' name='jumpMenu' id='jumpMenu'>";
            
    		// Läs data från respektive titel.
    		while ($row = mysqli_fetch_array($result)) {
    			$TITELID		= $row["TITELID"];
    			$KOD			= $row["KOD"];
    			$TITEL			= $row["TITEL"];
    			$GRAD			= $row["GRAD"];
    
    			// Behandla data som behöver behandlas.
    			if (empty($TITEL)) $TITEL = "**Titel saknas**";
    			if (strlen($TITEL) >= 40) $TITEL = substr($TITEL,0,40)."...";
    			if ($GRAD == "0") $GRAD = "&Ouml;ppen";
    			elseif ($GRAD == "1") $GRAD = "Grad I";
    			elseif ($GRAD == "2") $GRAD = "Grad II";
    			elseif ($GRAD == "3") $GRAD = "Grad III";
    			elseif ($GRAD == "4") $GRAD = "Grad IV-V";
    			elseif ($GRAD == "6") $GRAD = "Grad VI";
    			elseif ($GRAD == "7") $GRAD = "Grad VII";
    			elseif ($GRAD == "8") $GRAD = "Grad VIII";
    			elseif ($GRAD == "9") $GRAD = "Grad IX";
    			elseif ($GRAD == "10") $GRAD = "Grad X";
    
    			if (!empty($KOD)) $KOD = "$KOD - ";
    			
    			// Skriv ut menyraden och fortsätt till nästa rad.
    			echo "<option value='titel_hantering.php?Steg=RedigeraTitel2&amp;TITELID=$TITELID&amp;Sparasom=Redigera'>$KOD$GRAD - $TITEL</option>";
    			}
    		// Stäng menyn och skriv ut en "Redigera"-knapp.
    		echo "</select> <input type='button' name='go_button' id= 'go_button' class='Text' value='Redigera' onclick='MM_jumpMenuGo(\"jumpMenu\",\"parent\",0)' /></form>";
	   }
    }

	// Redigera vald titel eller skapa en ny titel.
	if ($Steg == "RedigeraTitel2") {
	
		$Sparasom = $_GET['Sparasom'];
		
		if ($Sparasom == "Redigera") {
		
			$TITELID = $_GET['TITELID'];
			// Förbered hämtningen av utvald titel.
			$Redigeratitel = "SELECT * FROM litteratur WHERE TITELID = $TITELID";
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$result = mysqli_query($db,$Redigeratitel)) {
				die('Invalid query: ' . mysqli_error($db));
				} 
			// Läs data om utvald titel i databasen litteratur.
			while ($row = mysqli_fetch_array($result)) {
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
				$SAMLING		= $row["SAMLING"];
				$SAMLINGSID		= $row["SAMLINGSID"];
				$DATUM			= $row["DATUM"];
				$REGISTRATOR	= $row["REGISTRATOR"];
				$SIDOR      	= $row["SIDOR"];
				$SPRAK      	= $row["SPRAK"];
			}
		
			// Öppna ett formulär för att redigera vald titel.
			echo "<form action='titel_hantering.php?Steg=Spara&amp;TITELID=$TITELID&amp;Sparasom=Uppdatera' method='post' enctype='application/form-data'>";
		}
		else {
			// Öppna ett formulär för att skapa en ny titel.
			echo "<form action='titel_hantering.php?Steg=Spara&amp;Sparasom=Ny' method='post' enctype='application/form-data'>";
			$GRAD = 0;
			$STUDIEPLAN = "";
			$KOD = "";
			$TITEL = "";
			$FORFATTARE = "";
			$UTGIVNINGSAR = "";
			$BESKRIVNING = "";
			$REVIDERAD = "";
			$KATNR = "";
            $SAMLING = "";
            $SAMLINGSID = "";
			echo "<div class='Rubrik'>L&auml;gg till en ny titel!</div>";
			}
        if (isset($_GET["SAMLINGSID"])) $SAMLINGSID = $_GET["SAMLINGSID"];
			
		// Öppna en tabell och sätt upp en sträng med återkommande instruktioner för att spara plats.			
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";
		$CELL = "<td valign='top' align='left'>";

        // Kontrollera om det är ett subinnehåll som ska registreras.
        if (isset($_GET["SAMLINGSID"])) {
            
            // Hissa en flagga.
            $Samling = 1;

			// Förbered hämtningen av samlingsvolymen.
			$Hamtatitel = "SELECT * FROM litteratur WHERE TITELID = ".$_GET['SAMLINGSID']."";
			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$Samlingsresult = mysqli_query($db,$Hamtatitel)) {
				die('Invalid query: ' . mysqli_error($db));
			} 
			// Läs data om utvald titel i databasen litteratur.
			while ($row = mysqli_fetch_array($Samlingsresult)) {
				$SAMLINGSKOD	= $row["KOD"];
				$SAMLINGSTITEL	= $row["TITEL"];
                $SAMLINGSAR     = $row["UTGIVNINGSAR"];
                $SAMLINGSREV    = $row["REVIDERAD"];
                $GRAD           = $row["GRAD"];
                $KATNR          = $row["KATNR"];
                
                // Kolla om KOD innehåller några inledande nollor och plocka bort dem i så fall.
                if (!empty($SAMLINGSKOD)) {
                    
                    $SAMLINGSKOD = str_replace(":00",":",$SAMLINGSKOD);
                    $SAMLINGSKOD = str_replace(":0",":",$SAMLINGSKOD);
                    $SAMLINGSKOD = str_replace(" 00"," ",$SAMLINGSKOD);
                    $SAMLINGSKOD = str_replace(" 0"," ",$SAMLINGSKOD);
                    $SAMLINGSKOD = "$SAMLINGSKOD ";
                }
			}
            echo "<tr>".$CELL."Samlingsvolym:</td>$CELL$SAMLINGSKOD<b>$SAMLINGSTITEL</b><input type='hidden' name='SAMLINGSID' value='$SAMLINGSID'</td></tr>";            
        }
		
		// Konfigurera radioknapparna för grad och studieplan.
		if ($GRAD == 0) $GRAD0 = "<input type='radio' name='GRAD' value='0' checked /> &Ouml;ppen / Utl&auml;ndsk litteratur<br />";
		else $GRAD0 = "<input type='radio' name='GRAD' value='0' /> &Ouml;ppen / Utl&auml;ndsk litteratur<br />";
		if ($GRAD == 1) $GRAD1 = "<input type='radio' name='GRAD' value='1' checked /> S:t Johannes Grad I<br />";
		else $GRAD1 = "<input type='radio' name='GRAD' value='1' /> S:t Johannes Grad I<br />";
		if ($GRAD == 2) $GRAD2 = "<input type='radio' name='GRAD' value='2' checked /> S:t Johannes Grad II<br />";
		else $GRAD2 = "<input type='radio' name='GRAD' value='2' /> S:t Johannes Grad II<br />";
		if ($GRAD == 3) $GRAD3 = "<input type='radio' name='GRAD' value='3' checked /> S:t Johannes Grad III<br />";
		else $GRAD3 = "<input type='radio' name='GRAD' value='3' /> S:t Johannes Grad III<br />";
		if ($GRAD == 4) $GRAD4 = "<input type='radio' name='GRAD' value='4' checked /> S:t Andreas Grad IV-V<br />";
		else $GRAD4 = "<input type='radio' name='GRAD' value='4' /> S:t Andreas Grad IV-V<br />";
		if ($GRAD == 6) $GRAD6 = "<input type='radio' name='GRAD' value='6' checked /> S:t Andreas Grad VI<br />";
		else $GRAD6 = "<input type='radio' name='GRAD' value='6' /> S:t Andreas Grad VI<br />";
		if ($GRAD == 7) $GRAD7 = "<input type='radio' name='GRAD' value='7' checked /> Kapitel Grad VII<br />";
		else $GRAD7 = "<input type='radio' name='GRAD' value='7' /> Kapitel Grad VII<br />";
		if ($GRAD == 8) $GRAD8 = "<input type='radio' name='GRAD' value='8' checked /> Kapitel Grad VIII<br />";
		else $GRAD8 = "<input type='radio' name='GRAD' value='8' /> Kapitel Grad VIII<br />";
		if ($GRAD == 9) $GRAD9 = "<input type='radio' name='GRAD' value='9' checked /> Kapitel Grad IX<br />";
		else $GRAD9 = "<input type='radio' name='GRAD' value='9' /> Kapitel Grad IX<br />";
		if ($GRAD == 10) $GRAD10 = "<input type='radio' name='GRAD' value='10' checked /> Kapitel Grad X<br />";
		else $GRAD10 = "<input type='radio' name='GRAD' value='10' /> Kapitel Grad X<br />";
		$GRADRADIO = $GRAD0.$GRAD1.$GRAD2.$GRAD3.$GRAD4.$GRAD6.$GRAD7.$GRAD8.$GRAD9.$GRAD10;

		if ($STUDIEPLAN == "B") $STUDIEPLANB = "<input type='radio' name='STUDIEPLAN' value='B' checked /> Bas<br />";
		else $STUDIEPLANB = "<input type='radio' name='STUDIEPLAN' value='B' /> Bas<br />";
		if ($STUDIEPLAN == "F") $STUDIEPLANF = "<input type='radio' name='STUDIEPLAN' value='F' checked /> F&ouml;rdjupning<br />";
		else $STUDIEPLANF = "<input type='radio' name='STUDIEPLAN' value='F' /> F&ouml;rdjupning<br />";
		if ($STUDIEPLAN == "K") $STUDIEPLANK = "<input type='radio' name='STUDIEPLAN' value='K' checked /> Komplettering<br />";
		else $STUDIEPLANK = "<input type='radio' name='STUDIEPLAN' value='K' /> Komplettering<br />";
		if ($STUDIEPLAN == "X") $STUDIEPLANX = "<input type='radio' name='STUDIEPLAN' value='X' checked /> ** Tillbakadragen titel **<br />";
		else $STUDIEPLANX = "<input type='radio' name='STUDIEPLAN' value='X' /> ** Tillbakadragen titel **<br />";
		if ($STUDIEPLAN == "") $STUDIEPLANT = "<input type='radio' name='STUDIEPLAN' value='' checked /> ** Tillh&ouml;r inte studieplanen **<br />";
		else $STUDIEPLANT = "<input type='radio' name='STUDIEPLAN' value='' /> ** Tillh&ouml;r inte studieplanen **<br />";
		$STUDIEPLANRADIO = $STUDIEPLANB.$STUDIEPLANK.$STUDIEPLANF.$STUDIEPLANT."<br />".$STUDIEPLANX;
		
		if ($REVIDERAD == 0) $REVIDERAD = "";
		
		// Skriv ut raderna i tabellen.
		if ($Sparasom <> "Ny") echo "<tr>".$CELL."ID-nummer:</td>$CELL$TITELID. (Senast redigerad ".date('Y.m.d', $DATUM)." av $REGISTRATOR)</td></tr>";
		echo "<tr>".$CELL."Kod/Serie:</td>$CELL<input name='KOD' type='Text' class='Text' size='30' value='$KOD' > (Ex. A I:012 eller VLN 139. Fyll ut tal under 100 med nollor f&ouml;re.)</td></tr>";
		echo "<tr>".$CELL."Titel:</td>$CELL<input name='TITEL' type='Text' class='Text' size='100' value='$TITEL' ></td></tr>";
		echo "<tr>".$CELL."F&ouml;rfattare:</td>$CELL<input name='FORFATTARE' type='Text' class='Text' size='100' value='$FORFATTARE' ></td></tr>";

		// Hämta kategorier från databasen kategorier.
		$Listakategorier = "SELECT * FROM kategorier ORDER BY KATEGORI ASC";
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$Kategoriresult = mysqli_query($db,$Listakategorier)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		echo "<tr>".$CELL."Kategori:</td>$CELL<select name='KATNRSELECT' class='Text'>";

		// Läs data från respektive titel.
		while ($row = mysqli_fetch_array($Kategoriresult)) {
			$KATLISTNR	= $row["KATNR"];
			$KATEGORI	= utf8_encode($row["KATEGORI"]);
		
			if ($KATNR == $KATLISTNR) echo "<option selected='selected' value='$KATLISTNR'>$KATLISTNR - $KATEGORI</option>";
			else echo "<option value='$KATLISTNR'>$KATLISTNR - $KATEGORI</option>";
		}
		echo "</td></tr>";
		
		if ($Samling <> 1) {
            echo "<tr>".$CELL."Utgivnings&aring;r:</td>$CELL<input name='UTGIVNINGSAR' type='Text' class='Text' size='30' value='$UTGIVNINGSAR' > (Fullt &aring;rtal, ex. 2010 eller 1924)</td></tr>";
            echo "<tr>".$CELL."Reviderad:</td>$CELL<input name='REVIDERAD' type='Text' class='Text' size='30' value='$REVIDERAD' > (Fullt &aring;rtal, ex. 2010 eller 1924)</td></tr>";
        }
        else {
            echo "<tr>".$CELL."Utgivnings&aring;r:</td>$CELL<input name='UTGIVNINGSAR' type='Text' class='Text' size='30' value='$SAMLINGSAR' > (Fullt &aring;rtal, ex. 2010 eller 1924)</td></tr>";
            echo "<tr>".$CELL."Reviderad:</td>$CELL<input name='REVIDERAD' type='Text' class='Text' size='30' value='$SAMLINGSREV' > (Fullt &aring;rtal, ex. 2010 eller 1924)</td></tr>";
        }
		echo "<tr>".$CELL."Grad:</td>$CELL<b>Observera att endast titlar tillh&ouml;rande det svenska systemet f&aring;r graderas.</b><br /><br />$GRADRADIO</td></tr>";
        
        // Översätt studieplanen till klartext.
        if ($STUDIEPLAN == "B") $Studieplan = "Bas.";
        if ($STUDIEPLAN == "F") $Studieplan = "F&ouml;rdjupning.";
        if ($STUDIEPLAN == "K") $Studieplan = "Komplettering.";
        if ($STUDIEPLAN == "X") $Studieplan = "Tillbakadragen titel.";
        if ($STUDIEPLAN == "") $Studieplan = "Tillh&ouml;r inte SFMOs studieplan.";

        // Skriv ut studieplansvalen om det är administratorn som loggat in.
        if ($_SERVER['REMOTE_USER'] == "9999") echo "<tr>".$CELL."Studieplan:</td>$CELL$STUDIEPLANRADIO</td></tr>";
        
		echo "<tr>".$CELL."Beskrivning:</td>$CELL<textarea name='BESKRIVNING' cols='100' rows='4' class='Text'>$BESKRIVNING</textarea></td></tr>";
		echo "<tr>".$CELL."Sidor:</td>$CELL<input name='SIDOR' type='Text' class='Text' size='4' value='$SIDOR' > sidor.</td></tr>";
        
        // Hämta språk från databasen sprak.
        $Listasprak = "SELECT * FROM sprak ORDER BY SPRAKLANG ASC";
        
		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$Sprakresult = mysqli_query($db,$Listasprak)) {
			die('Invalid query: ' . mysqli_error($db));
        }
        
		echo "<tr>".$CELL."Språk:</td>$CELL<select name='SPRAK' class='Text'>";

 		// Läs data från respektive språk.
		while ($row = mysqli_fetch_array($Sprakresult)) {
			$SPRAKKORT	= $row["SPRAKKORT"];
			$SPRAKLANG	= $row["SPRAKLANG"];
            
            if (empty($SPRAK)) $SPRAK = "SE";
		
			if ($SPRAK == $SPRAKKORT) echo "<option selected='selected' value='$SPRAKKORT'>$SPRAKLANG</option>";
			else echo "<option value='$SPRAKKORT'>$SPRAKLANG</option>";
		}
        echo "</td></tr>";

        // Markera om det är en samlingsvolym eller inte.
        if ($Samling <> 1 && $SAMLINGSID == 0) {
            echo "<tr>".$CELL."Samlingsvolym:</td>$CELL<input type='checkbox' name='SAMLING' value='1' ";
            if ($SAMLING == 1) echo "checked";
            echo "/> (Kryssa i om titeln &auml;r en samlingsvolym.)</td></tr>";
        }
        if ($SAMLINGSID <> 0) {
            echo "<tr>".$CELL."Samlingsvolym:</td>$CELL<input type='hidden' name='SAMLINGSID' value='$SAMLINGSID'><b>Titeln &auml;r en del av en samlingsvolym.</b></td></tr>";
        }
        
		if ($Sparasom == "Redigera") {
		  
			// Kontrollera om det finns några böcker kopplade till titeln.
			$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
		
        	// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
			if (!$bokresult = mysqli_query($db,$Listabocker)) {
				die('Invalid query: ' . mysqli_error($db));
			}
		
			// Räkna efter i databasen bocker hur många exemplar som finns av aktuell titel.
			$Raknabocker = mysqli_num_rows($bokresult);

			// Läs information från databasen bocker.
			while ($row = mysqli_fetch_array($bokresult)) {
				$BOKID			= $row["BOKID"];
				$TITELID		= $row["TITELID"];
				$EAN			= $row["EAN"];
			}
			
            // Förbered information om hur många böcker som finns registrerade till titeln.
            if ($Raknabocker == 0) $Raknabocker = "Inga b&ouml;cker registrerade med denna titel &auml;nnu.";
            else $Raknabocker = "$Raknabocker st.";
            
			// Skriv ut informationen på en rad.
            if ($SAMLINGSID == 0) {
    			echo "<tr>".$CELL."Antal b&ouml;cker:</td>$CELL$Raknabocker";
    			echo "</td></tr>";
            }
		}
		
		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td>$CELL<input type='submit' value='Spara' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></td></tr></table></form>";
	}

	// Spara den nya eller uppdaterade titeln i databasen litteratur.
	if ($Steg == "Spara") {

		$Sparasom = $_GET['Sparasom'];
		$TITELID = $_GET['TITELID'];
		
		// Läs in de respektive strängarnas värde från föregående sida.
		$KOD			= $_POST["KOD"];								// Titelns kod.
		$TITEL			= $_POST["TITEL"];								// Titelns titel.
		$FORFATTARE		= ucwords($_POST["FORFATTARE"]);				// Titelns författare, med stora begynnelsebokstäver.
		$UTGIVNINGSAR	= $_POST["UTGIVNINGSAR"];						// Titelns utgivningsår.
		$GRAD			= $_POST["GRAD"];								// Titelns grad 1-10. 0 om det är öppen litteratur.
		$STUDIEPLAN		= $_POST["STUDIEPLAN"];							// Om titeln tillhör studieplanen flaggas den "B", "K" eller "F".
		$BESKRIVNING	= $_POST["BESKRIVNING"];						// Titelns beskrivning.
		$REVIDERAD		= $_POST["REVIDERAD"];							// Om titeln är reviderad anges revideringens årtal här.
		$KATNR			= $_POST["KATNRSELECT"];						// Titelns kategori.
		$SIDOR			= $_POST["SIDOR"];	          					// Titelns kategori.
		$SPRAK			= $_POST["SPRAK"];       						// Titelns kategori.
		$DATUM			= mktime(0,0,0,$Servermanad,$Serverdag,$Serverar);
        $REGISTRATOR    = $_SESSION["Bibliotek"];                       // Titelns registrator.
        $SAMLING        = $_POST["SAMLING"];
        $SAMLINGSID     = $_POST["SAMLINGSID"];

		// Kontrollera om det är en befintlig titel som ska redigeras och gör det i så fall.
		if ($Sparasom == "Uppdatera") {
			// Sätt ihop strängarna till en uppdateringssträng för databasen.
			$Skrivtitel = "UPDATE litteratur SET KOD='$KOD', TITEL='$TITEL', FORFATTARE='$FORFATTARE', UTGIVNINGSAR='$UTGIVNINGSAR', GRAD='$GRAD', KATNR='$KATNR', STUDIEPLAN='$STUDIEPLAN', BESKRIVNING='$BESKRIVNING', REVIDERAD='$REVIDERAD', DATUM='$Serveridag', SPRAK='$SPRAK', SIDOR='$SIDOR', SAMLING='$SAMLING', SAMLINGSID='$SAMLINGSID' WHERE TITELID='$TITELID'";
		}

		// Kontrollera om det är en ny titel som ska läggas till och gör det i så fall.
		elseif ($Sparasom == "Ny") {
			// Sätt ihop strängarna för att skicka dem till databasen.
			$Skrivtitel = "INSERT INTO litteratur (KOD,TITEL,FORFATTARE,UTGIVNINGSAR,GRAD,STUDIEPLAN,BESKRIVNING,REVIDERAD,KATNR,DATUM,REGISTRATOR,SIDOR,SPRAK,SAMLING,SAMLINGSID) VALUES ('$KOD','$TITEL','$FORFATTARE','$UTGIVNINGSAR','$GRAD','$STUDIEPLAN','$BESKRIVNING','$REVIDERAD','$KATNR','$Serveridag','$REGISTRATOR', '$SIDOR', '$SPRAK', '$SAMLING', '$SAMLINGSID')";
			}

		// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Skrivtitel)) {
			die('Invalid query: ' . mysqli_error($db));
			}
		
		// Hoppa tillbaka till administrationssidan.
		header("location:administrera.php");
	}

	// Radera en titel - men bara om den inte har några böcker kopplade till sig!
	if ($Steg == "Radera") {
	
		// Hämta innehållet i databasen litteratur för att göra en meny.
		$Listatitlar = "SELECT * FROM litteratur WHERE REGISTRATOR = ".$_SESSION["Bibliotek"]." ORDER BY GRAD, KOD, TITEL ASC";
        if ($_SERVER["REMOTE_USER"] == "9999") $Listatitlar = "SELECT * FROM litteratur ORDER BY GRAD, KOD, TITEL ASC";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
        // Kontrollera så att det finns något att radera.
        $Raknatitlar = mysqli_num_rows($result);
        if ($Raknatitlar == 0) echo "<div class='Text'>Det finns inga titlar att radera.</div><br /><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";
        else {
    		// Öppna ett formulär.
    		echo "<div class='Rubrik'>Radera titel</div>";
    		echo "<div class='Text'>V&auml;lj vilken titel som ska raderas. Observera att endast titlar utan n&aring;gra b&ouml;cker visas.<br />";
    		echo "Innan den valda titeln raderas m&aring;ste du bekr&auml;fta ditt val.</div><br />";
    		echo "<form name='form' id='form'><select class='Text' name='jumpMenu' id='jumpMenu'>";
    
    		// Läs data från respektive titel.
    		while ($row = mysqli_fetch_array($result)) {
    			$TITELID		= $row["TITELID"];
    			$KOD			= $row["KOD"];
                $KODNUM         = $row["KODNUM"];
    			$TITEL			= $row["TITEL"];
    			$GRAD			= $row["GRAD"];
    
    			// Behandla data som behöver behandlas.
    			if (strlen($TITEL) >= 60) $TITEL = substr($TITEL,0,60)."...";
    			if ($GRAD == "0") $GRAD = "&Ouml;ppen";
    			elseif ($GRAD == "1") $GRAD = "Grad I";
    			elseif ($GRAD == "2") $GRAD = "Grad II";
    			elseif ($GRAD == "3") $GRAD = "Grad III";
    			elseif ($GRAD == "4") $GRAD = "Grad IV-V";
    			elseif ($GRAD == "6") $GRAD = "Grad VI";
    			elseif ($GRAD == "7") $GRAD = "Grad VII";
    			elseif ($GRAD == "8") $GRAD = "Grad VIII";
    			elseif ($GRAD == "9") $GRAD = "Grad IX";
    			elseif ($GRAD == "10") $GRAD = "Grad X";
    
    			if (!empty($KOD)) $KOD = "$KOD $KODNUM - ";
    
    			// Kontrollera om det finns några böcker kopplade till titeln.
    			$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID ORDER BY EAN ASC";
    			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    			if (!$bokresult = mysqli_query($db,$Listabocker)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
    		
    			// Räkna efter i databasen bocker hur många exemplar som finns av aktuell titel.
    			$Raknabocker = mysqli_num_rows($bokresult);
    			
    			// Skriv ut menyraden och fortsätt till nästa rad.
    			if ($Raknabocker == 0) echo "<option value='titel_hantering.php?Steg=RaderaTitel&amp;TITELID=$TITELID'>$KOD$GRAD - $TITEL</option>";
            }
        
    		// Stäng menyn och skriv ut en "Radera"-knapp.
    		echo "</select><br /><input type='button' name='go_button' id= 'go_button' class='Text' value='Radera' onclick='MM_jumpMenuGo(\"jumpMenu\",\"parent\",0)' /><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></form><br />";	
        }

        // Skriv ut en hjälpknapp.
        $HELPID = 18;
        require ("../subrutiner/help.php");
    }
	
	// Verifiera att man verkligen vill radera den valda titeln.
	if ($Steg == "RaderaTitel") {
	
		// Hämta titelns ID från föregående sida.
		$TITELID = $_GET['TITELID'];
		
		// Hämta innehållet i databasen litteratur för att visa den en sista gång.
		$Listatitlar = "SELECT * FROM litteratur WHERE TITELID = '$TITELID'";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Läs data från titeln.
		while ($row = mysqli_fetch_array($result)) {
			$TITELID		= $row["TITELID"];
			$KOD			= $row["KOD"];
            $KODNUM         = $row["KODNUM"];
			$TITEL			= $row["TITEL"];
		}

		// Behandla data som behöver behandlas.
		if (!empty($KOD)) $KOD = "$KOD $KODNUM - ";
			
		echo "<div class='Text'>Radera titeln $KOD<b>$TITEL</b>?</div>";
		echo "<br /><input type='button' value='Radera' onclick='window.location = \"titel_hantering.php?Steg=RaderaTitel2&amp;TITELID=$TITELID\" '  class='Text'/><input type='button' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";
	}
	
	// Radera den utvalda och verifierade titeln.
	if ($Steg == "RaderaTitel2") {
	
		// Hämta titelns ID från föregående sida.
		$TITELID = $_GET['TITELID'];
		
		// Sätt upp en slängsträng.
		$Raderatiteln = "DELETE FROM litteratur WHERE TITELID = '$TITELID'";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Raderatiteln)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
		// Hoppa tillbaka till administrationssidan.
		header("location:administrera.php");
		
	}
    
    // Låt administratören ta bort titlar som inte har några böcker knutna till sig.
    if ($Steg == "Nollor") {
        
        // Räkna antalet böcker kopplade till respektive titel.
        $Listatitlar = "SELECT * FROM litteratur";

		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listatitlar)) {
			die('Invalid query: ' . mysqli_error($db));
		}
		
        // Kontrollera hur många titlar som finns registrerade.
        $Raknatitlar = mysqli_num_rows($result);
        echo "$Raknatitlar titlar finns registrerade.<br />";
        $tombok = 0;
            
            // Läs data från respektive titel.
    		while ($row = mysqli_fetch_array($result)) {
    			$TITELID		= $row["TITELID"];
        
    			// Kontrollera om det finns några böcker kopplade till titeln.
    			$Listabocker = "SELECT * FROM bocker WHERE TITELID = $TITELID";
    			// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
    			if (!$bokresult = mysqli_query($db,$Listabocker)) {
    				die('Invalid query: ' . mysqli_error($db));
    			}
                if (!mysqli_num_rows($bokresult)) {
                    $tombok = $tombok+1;
            		// Sätt upp en slängsträng.
            		$Raderatiteln = "DELETE FROM litteratur WHERE TITELID = '$TITELID'";
            
            		// Skicka begäran till databasen. Stoppa och visa felet om något går galet.
            		if (!$raderaresult = mysqli_query($db,$Raderatiteln)) {
            			die('Invalid query: ' . mysqli_error($db));
            		}
                }
    		
            }
            echo "$tombok titlar utan registrerade exemplar raderade.<br /><br />";

    		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
	       	echo "<input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";

    }
    echo "</div></body></html>";
?>
