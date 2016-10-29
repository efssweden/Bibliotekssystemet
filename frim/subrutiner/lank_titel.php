<?php

    // Starta en loop från grad 0 till grad 10 och skriv ut ankarlänkar till respektikve grad.
	for ($Gradnummer = 1; $Gradnummer <= 10; $Gradnummer +=1){
		$Lank = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=ListaTitlar#$Gradnummer\"' class='Text' >";
	
        // Lägg till kategorin om det är vad som visas.
        if (isset($_GET['Kat'])) $Lank = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=ListaTitlar&Kat=".$_GET['Kat']."#$Gradnummer\"' class='Text' >";
        
        // Lägg till biblioteket om det är vad som visas.
        if (isset($_GET['Bib'])) $Lank = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=ListaTitlar&Bib=".$_GET['Bib']."#$Gradnummer\"' class='Text' >";
        
        // Lägg till språket om det är vad som visas.
        if (isset($_GET['Sprak'])) $Lank = "<button type='button' onClick='parent.location=\"titel_hantering.php?Steg=ListaTitlar&Sprak=".$_GET['Sprak']."#$Gradnummer\"' class='Text' >";
        
    	// Bestäm knappens innehåll baserat på graden.
        if ($Gradnummer == 1) $Gradlank = "$Lank I</button>";
		if ($Gradnummer == 2) $Gradlank = "$Lank II</button>";
		if ($Gradnummer == 3) $Gradlank = "$Lank III</button> - ";
		if ($Gradnummer == 4) $Gradlank = "$Lank IV-V</button>";
		if ($Gradnummer == 6) $Gradlank = "$Lank VI</button> - ";
		if ($Gradnummer == 7) $Gradlank = "$Lank VII</button>";
		if ($Gradnummer == 8) $Gradlank = "$Lank VIII</button>";
		if ($Gradnummer == 9) $Gradlank = "$Lank IX</button>";
		if ($Gradnummer == 10) $Gradlank = "$Lank X</button>";

		// Om gradnumret är 5 så gör ingenting, annars skriv ut en lista på gradens titlar.
		if ($Gradnummer == 5) echo "";
		else {
			echo "$Gradlank";
		}
    }
	echo "<br /><br />";
?>