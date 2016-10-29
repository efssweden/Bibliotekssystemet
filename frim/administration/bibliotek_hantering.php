<?php
    session_start();
    
    // Starta dokumentet.
    require("../subrutiner/head.php");
	echo "<body><div id='ALLT'><br />";
    
    // Kontrollera vilket steg som ska tas på sidan.
    $Steg = $_GET['Steg'];
	
	// Visa en lista över alla bibliotek.
	if ($Steg == "Lista") {
	
		echo "<div class='Rubrik'>Bibliotek i systemet ".date(Y)."</div>";
		echo "<table border='1' align='center' cellpadding='5'>";
		
        $Listabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR != '9999' ORDER BY BIBLIOTEKNR ASC";

		// Skicka en begäran till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listabibliotek)) {
			die('Invalid query: ' . mysqli_error($db));
		} 
        
        $Fordelning = 0;

		// Läs information från databasen bibliotek.
		while ($row = mysqli_fetch_array($result)) {
			$BIBLIOTEKID	= $row["BIBLIOTEKID"];
			$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];
			$BIBLIOTEKNR	= $row["BIBLIOTEKNR"];
            $BETALT         = $row["BETALT"];
            $ORT            = $row["ORT"];

			// Kontrollera om det finns några böcker kopplade till biblioteket.
			$Listabocker = "SELECT * FROM bocker WHERE EAN LIKE '$BIBLIOTEKNR%'";
			$Bokresult = mysqli_query($db,$Listabocker);
			$Raknabocker = mysqli_num_rows($Bokresult);
            
            // Om det finns böcker i biblioteket så tillverka en länk så att man kan se dem.
#            if ($Raknabocker >= 1 && $_SERVER['REMOTE_USER'] == "9999") $Raknabocker = "<a href='bok_hantering.php?Steg=Listabocker&Bib=$BIBLIOTEKNR'>$Raknabocker</a>";
            if ($Raknabocker >= 1) $Raknabocker = "<a href='bok_hantering.php?Steg=Listabocker&Bib=$BIBLIOTEKNR'>$Raknabocker</a>";
			
			// Kolla upp det här årets första och sista dag.
			$Datumett = mktime(0,0,0,1,1,date("Y"));
			$Datumtva = mktime(23,59,59,12,31,date("Y"));
			
			// Räkna efter hur många besök det varit i biblioteket i år.
            $Antalbesok = 0;
			$Raknabesok	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR AND DATUM > $Datumett AND DATUM < $Datumtva";
			$Besokresult = mysqli_query($db,$Raknabesok);
			$Antalbesok	= mysqli_num_rows($Besokresult);

			// Kolla upp senaste besöket i biblioteket.
			$Raknabesokigen	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR ORDER BY DATUM DESC LIMIT 1";
			$Besokresultigen = mysqli_query($db,$Raknabesokigen);
            $Raknabesokigennum = mysqli_num_rows($Besokresultigen);
            
			while ($row = mysqli_fetch_array($Besokresultigen)) {
				$DATUM = $row["DATUM"];
			}
			if ($Raknabesokigennum == 0) $Senastebesok = "Inga bes&ouml;k registrerade.";
            else $Senastebesok = "Senaste bes&ouml;k: ".date('Y-m-d',$DATUM);
            
            // Kolla upp det första besöket i biblioteket.
            $Raknabesokigen	= "SELECT * FROM besokslistan WHERE BIBLIOTEK = $BIBLIOTEKNR ORDER BY DATUM ASC LIMIT 1";
			$Besokresultigen = mysqli_query($db,$Raknabesokigen);
            $Raknabesokigennum = mysqli_num_rows($Besokresultigen);
            
			while ($row = mysqli_fetch_array($Besokresultigen)) {
				$DATUM = $row["DATUM"];
			}
			if ($Raknabesokigennum == 0) $Forstabesok = "";
            else $Forstabesok = "F&ouml;rsta bes&ouml;k:  ".date('Y-m-d',$DATUM)."<br />";
            
            // Räkna hur många returer man haft under året.
            $Returer = 0;
            $Raknareturer = "SELECT * FROM returer WHERE RETURBIBLIOTEK=$BIBLIOTEKNR AND RETURDATUM > $Datumett AND RETURDATUM < $Datumtva";
            $Returerresult = mysqli_query($db,$Raknareturer);
            $Antalreturer = mysqli_num_rows($Returerresult);
            
            // Räkna efter hur många lån som gjorts i biblioteket i år.
            $Utlan = 0;
            
            // Börja med alla gamla lån...
            $Raknagamlalan = "SELECT * FROM gamlalan WHERE DATUM=$BIBLIOTEKNR AND BIBLIOTEK > $Datumett AND BIBLIOTEK < $Datumtva";
            $Gamlalanresult = mysqli_query($db,$Raknagamlalan);
            $Antalgamlalan = mysqli_num_rows($Gamlalanresult);
            
            // Fortsätt med alla pågående lån.
            $Raknalan = "SELECT * FROM aktiva WHERE BIBLIOTEK=$BIBLIOTEKNR AND DATUM > $Datumett AND DATUM < $Datumtva";
            $Lanresult = mysqli_query($db,$Raknalan);
            $Antallan = mysqli_num_rows($Lanresult);
            
            // Lägg ihop siffrorna.
            $Utlan = $Antalgamlalan+$Antallan;
            
            // Räkna efter hur många titlar biblioteket registrerat.
            $Raknatitlar = "SELECT * FROM litteratur WHERE REGISTRATOR = $BIBLIOTEKNR";
            $Titlarresult = mysqli_query($db,$Raknatitlar);
            $Antaltitlar = mysqli_num_rows($Titlarresult);
            
            // Om det finns titlar registrerade av biblioteket så tillverka en länk så att man kan se dem.
#            if ($Antaltitlar >= 1 && $_SERVER['REMOTE_USER'] == "9999") $Antaltitlar = "<a href='titel_hantering.php?Steg=ListaTitlar&Bib=$BIBLIOTEKNR'>$Antaltitlar</a>";
            if ($Antaltitlar >= 1) $Antaltitlar = "<a href='titel_hantering.php?Steg=ListaTitlar&Bib=$BIBLIOTEKNR'>$Antaltitlar</a>";
						
			// Kontrollera om det finns något Exlibris inlagt.
			$Filnamn = "../design/bibliotek/$BIBLIOTEKNR.jpg";
			if (file_exists($Filnamn)) $Bildstatus = "<img src='../design/bibliotek/$BIBLIOTEKNR.jpg' height='50px'/>";
			else $Bildstatus = "Saknas";
            
            // Kontrollera om årsavgiften är betald.
            if ($BETALT == 1) $COLOR = "<font color='green'><b>&deg; </b></font>";
            else $COLOR = "<font color='red'><b>&deg; </b></font>";
            
            // Kolla vilken fördelning biblioteket tillhör.
            $Fordelningsnummer = substr($BIBLIOTEKNR,0,1);
            $Fordelningsnamn = array(1=>"F&ouml;rsta f&ouml;rdelningen", 2=>"Andra f&ouml;rdelningen", 3=>"Tredje f&ouml;rdelningen", 4=>"Fj&auml;rde f&ouml;rdelningen", 5=>"Femte f&ouml;rdelningen", 6=>"Sj&auml;tte f&ouml;rdelningen", 7=>"Sjunde f&ouml;rdelningen", 8=>"&Aring;ttonde f&ouml;rdelningen");

            // Kolla om det är samma fördelning fortfarande.
            if ($Fordelningsnummer<>$Fordelning){
                echo "<tr><td style='background-color:#f1f1f1' valign='top' align='left' class='Text' >Nr:</td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text' ><b>$Fordelningsnamn[$Fordelningsnummer]:</b></td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text'>B&ouml;cker:</td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text'>Bes&ouml;k:</td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text'>Utl&aring;n:</td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text'>Retur:</td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text'>Titlar:</td><td style='background-color:#f1f1f1' valign='top' align='left' class='Text'>Exlibris:</td></tr>";
            }

			// Skriv ut biblioteket på en rad i tabellen och fortsätt sen till nästa.
			echo "<tr height='63px'><td valign='top' align='left' class='Text' >$BIBLIOTEKNR</td><td valign='top' align='left' class='Text' >$COLOR<b>$BIBLIOTEKNAMN</b> ($ORT)<br />$Forstabesok$Senastebesok</td><td valign='top' align='left' class='Text'>$Raknabocker</td><td valign='top' align='left' class='Text'>$Antalbesok</td><td valign='top' align='left' class='Text'>$Utlan</td><td valign='top' align='left' class='Text'>$Antalreturer</td><td valign='top' align='left' class='Text'>$Antaltitlar</td><td valign='top' align='left' class='Text'>$Bildstatus</td></tr>";
            $Antaltitlar = "";
            $Fordelning = $Fordelningsnummer;
        }
        // Räkna ut totalen på utvalda kolumner.
		$Listabocker = "SELECT * FROM bocker";
		$Bokresult = mysqli_query($db,$Listabocker);
		$Raknabocker = mysqli_num_rows($Bokresult);
         
        $Antalbesok = 0;
    	$Raknabesok	= "SELECT * FROM besokslistan WHERE DATUM > $Datumett AND DATUM < $Datumtva";
		$Besokresult = mysqli_query($db,$Raknabesok);
		$Antalbesok	= mysqli_num_rows($Besokresult);
        
        $Raknareturer = "SELECT * FROM returer WHERE RETURDATUM > $Datumett AND RETURDATUM < $Datumtva";
        $Returerresult = mysqli_query($db,$Raknareturer);
        $Antalreturer = mysqli_num_rows($Returerresult);
        
        $Utlan = 0;
        $Raknagamlalan = "SELECT * FROM gamlalan WHERE BIBLIOTEK > $Datumett AND BIBLIOTEK < $Datumtva";
        $Gamlalanresult = mysqli_query($db,$Raknagamlalan);
        $Antalgamlalan = mysqli_num_rows($Gamlalanresult);
            
        $Raknalan = "SELECT * FROM aktiva WHERE DATUM > $Datumett AND DATUM < $Datumtva";
        $Lanresult = mysqli_query($db,$Raknalan);
        $Antallan = mysqli_num_rows($Lanresult);
            
        $Utlan = $Antalgamlalan+$Antallan;
        
        $Raknatitlar = "SELECT * FROM litteratur";
        $Titlarresult = mysqli_query($db,$Raknatitlar);
        $Antaltitlar = mysqli_num_rows($Titlarresult);
        
        echo "<tr><td></td><td valign='top' align='left' class='Text' ><b>Totalt:</b></td><td valign='top' align='left' class='Text'>$Raknabocker</td><td valign='top' align='left' class='Text'>$Antalbesok</td><td valign='top' align='left' class='Text'>$Utlan</td><td valign='top' align='left' class='Text'>$Antalreturer</td><td valign='top' align='left' class='Text'>$Antaltitlar</td><td></td></tr>";
        
        
		echo "</table><br /><input type='button' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/>";
	}
	
	// Lägg till nytt bibliotek.
	if ($Steg == "Laggtill") {
	
		$Sparasom = $_GET['Sparasom'];
		
		if ($Sparasom == "Redigera") {
		  
			// Förbered hämtningen av utvalt bibliotek.
			$BIBLIOTEKNR = $_GET['BIBLIOTEKNR'];
			$Redigerabibliotek = "SELECT * FROM bibliotek WHERE BIBLIOTEKNR = '$BIBLIOTEKNR'";

			// Skicka begäran till databasen. Stoppa och visa felet om något går snett.
			if (!$result = mysqli_query($db,$Redigerabibliotek)) {
				die('Invalid query: ' . mysqli_error($db));
				} 

			// Läs data om utvalt bibliotek i databasen bibliotek.
			while ($row = mysqli_fetch_array($result)) {
				$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];
				$BIBLIOTEKNR	= $row["BIBLIOTEKNR"];
                $ORT            = $row["ORT"];
			}

			// Öppna ett formulär för att redigera valt bibliotek.
			echo "<form action='bibliotek_hantering.php?Steg=Spara&amp;BIBLIOTEKNR=$BIBLIOTEKNR&amp;Sparasom=Uppdatera' method='post' enctype='application/form-data'>";
		}
		else {
			// Öppna ett formulär för att skapa en nytt bibliotek.
			echo "<form action='bibliotek_hantering.php?Steg=Spara&amp;Sparasom=Ny' method='post' enctype='application/form-data'>";
		}
        
		// Öppna en tabell.
		echo "<table cellpadding='5' align='center' class='Text' border='1'>";

		// Skriv ut raderna i tabellen.
		echo "<tr><td valign='top' align='left' class='Text'>Frimurarsamh&auml;lle:</td><td valign='top' align='left' class='Text'><input name='BIBLIOTEKNAMN' type='Text' class='Text' size='100' value='$BIBLIOTEKNAMN' ></td></tr>";
		echo "<tr><td valign='top' align='left' class='Text'>Nummer:</td><td valign='top' align='left' class='Text'><input name='BIBLIOTEKNR' type='Text' class='Text' size='4' value='$BIBLIOTEKNR' ></td></tr>";
		echo "<tr><td valign='top' align='left' class='Text'>Ort:</td><td valign='top' align='left' class='Text'><input name='ORT' type='Text' class='Text' size='100' value='$ORT' ></td></tr>";

		// Skriv ut "Spara"- och "Återgå"-knappar och stäng tabellen.
		echo "<tr><td></td><td valign='top' align='left' class='Text'><input type='submit' value='Spara' class='Text'><input type='button' name='Cancel' value='Tillbaka' onclick='window.location = \"administrera.php\" '  class='Text'/></td></tr></table></form>";		
	}
	
	// Spara det nya eller uppdaterade biblioteket.
	if ($Steg == "Spara") {

		$Sparasom = $_GET['Sparasom'];
		if (isset($_GET['BIBLIOTEKNR'])) $BIBLIOTEKNR = $_GET['BIBLIOTEKNR'];
		
		// Läs in bibliotekets namn från föregående sida.
		$BIBLIOTEKNAMN	= $_POST["BIBLIOTEKNAMN"];
		$BIBLIOTEKNR	= $_POST["BIBLIOTEKNR"];
        $ORT            = $_POST["ORT"];

		// Kontrollera om det är ett befintligt bibliotek som ska redigeras och gör det i så fall.
		if ($Sparasom == "Uppdatera") {

			// Sätt ihop strängarna till en uppdateringssträng för databasen.
			$Skrivbibliotek = "UPDATE bibliotek SET BIBLIOTEKNAMN='$BIBLIOTEKNAMN', ORT='$ORT' WHERE BIBLIOTEKNR='$BIBLIOTEKNR'";
			}

		// Kontrollera om det är ett nytt bibliotek som ska läggas till och gör det i så fall.
		elseif ($Sparasom == "Ny") {
		  
			// Sätt ihop strängarna för att skicka dem till databasen.
			$Skrivbibliotek = "INSERT INTO bibliotek (BIBLIOTEKNAMN,BIBLIOTEKNR, ORT) VALUES ('$BIBLIOTEKNAMN','$BIBLIOTEKNR','$ORT')";
			}

		// Skicka skrivsträngen till databasen, stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Skrivbibliotek)) {
			die('Invalid query: ' . mysqli_error($db));
			}
		
		// Hoppa tillbaka till administrationssidan.
		header("location:administrera.php");
	}

	// Välj vilket bibliotek som ska redigeras.
	if ($Steg == "Redigera") {

		// Öppna ett formulär.
		echo "<form name='form' id='form'><select class='Text' name='jumpMenu' id='jumpMenu'>";
		
        // Hämta innehållet i databasen bibliotek för att göra en meny.
		$Listabibliotek = "SELECT * FROM bibliotek ORDER BY BIBLIOTEKNR ASC";
		
        // Skicka begäran till databasen. Stoppa och visa felet om något går galet.
		if (!$result = mysqli_query($db,$Listabibliotek)) {
			die('Invalid query: ' . mysqli_error($db));
			}
		
		// Läs data från respektive titel.
		while ($row = mysqli_fetch_array($result)) {
			$BIBLIOTEKNAMN	= $row["BIBLIOTEKNAMN"];
			$BIBLIOTEKNR	= $row["BIBLIOTEKNR"];
			
			// Skriv ut menyraden och fortsätt till nästa rad.
			echo "<option value='bibliotek_hantering.php?Steg=Laggtill&amp;BIBLIOTEKNR=$BIBLIOTEKNR&amp;Sparasom=Redigera'>$BIBLIOTEKNR - $BIBLIOTEKNAMN</option>";
			}
		// Stäng menyn och skriv ut en "Redigera"-knapp.
		echo "</select> <input type='button' name='go_button' id= 'go_button' class='Text' value='Redigera' onclick='MM_jumpMenuGo(\"jumpMenu\",\"parent\",0)' /></form>";
	}
	echo "</div></body></html>";
?>