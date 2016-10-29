<?php
    // Kolla upp vilken mapp sidan ligger i och flagga om sidan ligger i en undermapp.
    $Sidmapp = dirname($_SERVER['PHP_SELF']);
    if ($Sidmapp == "/frim") {
        $Grundmapp = "";
        $Sidnamn = "Frimurare Biblioteket";
    }
    else {
        $Grundmapp = "../";
        $Sidnamn = "Frimurare Biblioteket Administration";
    }

    // Skriv ut sidans <HTML> och <HEAD> taggar.
    echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
    echo "<html xmlns='http://www.w3.org/1999/xhtml'>";
	echo "<head>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
    echo "<title>$Sidnamn</title>";
    echo "<link href='".$Grundmapp."design/design.css' rel='stylesheet' type='text/css' />";
    
    // Etablera javascriptet jumpMenuGo
    ?>
    <script type="text/javascript">
		<!--
		function MM_jumpMenuGo(objId,targ,restore){ //v9.0
		  var selObj = null;  with (document) { 
		  if (getElementById) selObj = getElementById(objId);
		  if (selObj) eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
		  if (restore) selObj.selectedIndex=0; }
		}
		//-->
		</script>
    <?php
    echo "<script type=\"text/javascript\" src=\"".$Grundmapp."subrutiner/java_popup.js\"></script>";
    echo "</head>";
    
	// Etablera kontakt med databasen och kolla upp dagens datum.
	require ($Grundmapp."konfiguration/konfig.php");
	$db = mysqli_connect($server, $db_user, $db_pass);
	mysqli_select_db($db,$database);
		
	$Serverar		= date("Y");
	$Servermanad 	= date("m");
	$Serverdag 		= date("d");
	$Serveridag		= mktime(0,0,0,$Servermanad,$Serverdag,$Serverar);
    
    // Spara information i kaka.
    $BIBLIOTEKNR = $_SERVER['PHP_AUTH_USER'];
    $_SESSION['Bibliotek'] = $BIBLIOTEKNR;
?>
