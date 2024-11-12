<?PHP
$useSessions = 1; $ehXML = 1;
include "iniset.php";
include "light_header.inc";

echo "passei";

if (isset($_GET['t'])) $stepTable = pg_escape_string($conn, $_GET['t']); else exit();
//echo $stepTable . "<BR>\n";
if (isset($_GET['f'])) $stepField = pg_escape_string($conn, $_GET['f']); else exit();
echo $stepField .. "<BR>\n";
//if (isset($_GET['k'])) $stepKey = pg_escape_string($conn, $_GET['k']); else exit();
//echo $stepKey . "<BR>\n";
if (isset($_GET['v'])) $keyValue = intval($_GET['v']); else exit();
//echo $keyValue . "<BR>\n";

//$query = 'SELECT "Modelo CAD (STEP)" from "Peças" where codigo = 1';

if ($useSessions)
	$workPath = "../sessions_files/simulation/occ/";
 else
	$workPath = "";

if (!file_exists( $workPath . "simulation" .  $PHPSESSID)){
  mkdir("./" . $workPath . "simulation" .  $PHPSESSID, 0777);  
}
if (!file_exists($workPath . "simulation" .  $PHPSESSID . "/occ/")){
  mkdir("./" . $workPath . "simulation" .  $PHPSESSID . "/occ/", 0777);  
}

$query  = "SELECT encode(\"" . $stepField . "\", 'base64') AS field  \n";
$query .= "  FROM \"" . $stepTable . "\"\n";
$query .= "  WHERE \"" . $keyField . "\" = " . ($keyIsQuoted ? "'" : '') . $keyValue . ($keyIsQuoted ? "'" : '');



//echo $path_to_python;
//$command  = $path_to_python . " ../occ/Step2STL.py ";
//$command .= "/var/www/onde-indusmart/occ/flangenova.stp /tmp/flange.stl";

include "page_footer.inc";
?>