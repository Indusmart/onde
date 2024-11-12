<?PHP
$useSessions = 1; $ehXML = 1;
include "iniset.php";
include "light_header.inc";

echo "passei<BR>\n";

if (isset($_GET['t'])) $stepTable = pg_escape_string($conn, trim($_GET['t'])); else exit();
echo $stepTable . "<BR>\n";
if (isset($_GET['f'])) $stepField = pg_escape_string($conn, trim($_GET['f'])); else exit();
echo $stepField . "<BR>\n";
if (isset($_GET['k'])) $stepKey = pg_escape_string($conn, trim($_GET['k'])); else exit();
echo $stepKey . "<BR>\n";
if (isset($_GET['v'])) $keyValue = intval($_GET['v']); else exit();
echo $keyValue . "<BR>\n";

$query = "SELECT encode(\"" .  $stepField . "\", 'base64') as raw from \"" . $stepTable  . "\" where \"" . $stepKey . "\" = " . $keyValue;
echo "<PRE>" . $query . "</PRE>";

$result = pg_exec($conn, $query);
if ($result){
	$peca = pg_fetch_assoc($result, 0);
  $fileArray = formsDecodeFile(base64_decode($peca['raw']));
 }
echo "<PRE>";
var_dump($fileArray);
echo "</PRE>";

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


//echo $path_to_python;
//$command  = $path_to_python . " ../occ/Step2STL.py ";
//$command .= "/var/www/onde-indusmart/occ/flangenova.stp /tmp/flange.stl";

include "page_footer.inc";
?>