<?PHP
$useSessions = 1; $ehXML = 1;
include "iniset.php";
include "light_header.inc";

//echo "passei<BR>\n";

if (isset($_GET['table'])) $stepTable = pg_escape_string($conn, trim($_GET['table'])); else exit();
if (isset($_GET['field'])) $stepField = pg_escape_string($conn, trim($_GET['field'])); else exit();
if (isset($_GET['keyField'])) $stepKey = pg_escape_string($conn, trim($_GET['keyField'])); else exit();
if (isset($_GET['keyIsQuoted'])) $quoted = intval($_GET['keyIsQuoted']); else exit();
if (isset($_GET['keyValue'])) $keyValue = ($quoted?pg_escape_string($conn, $_GET['keyValue']):intval($_GET['keyValue'])); else exit();

$query = "SELECT encode(\"" .  $stepField . "\", 'base64') as raw from \"" . $stepTable  . "\" where \"" . $stepKey . "\" = " . $keyValue;
//echo "<PRE>" . $query . "</PRE>";

$result = pg_exec($conn, $query);
if ($result){
	$peca = pg_fetch_assoc($result, 0);
  $fileArray = formsDecodeFile(base64_decode($peca['raw']));
 }
//echo "<PRE>";
//var_dump($fileArray);
//echo "</PRE>";

if ($useSessions)
	$workPath = "../session_files/";
if (!file_exists( $workPath . "simulation" .  $PHPSESSID)){
  mkdir("./" . $workPath . "simulation" .  $PHPSESSID, 0777);  
}
if (!file_exists($workPath . "simulation" .  $PHPSESSID . "/occ/")){
  mkdir("./" . $workPath . "simulation" .  $PHPSESSID . "/occ/", 0777);  
}

$step_filename = "./" . $workPath . "simulation" .  $PHPSESSID . "/occ/" . fixField($fileArray['name']);
$stl_filename = "./" . $workPath . "simulation" .  $PHPSESSID . "/occ/'" . fixField($fileArray['name']) . ".stl'";
$step_file = fopen($step_filename, "w");
fputs($step_file, $fileArray['contents']);
fclose($step_file);
//echo $path_to_python;
$command  = $path_to_python . " ../occ/Step2STL.py ";
$command .= $step_filename . " " . $stl_filename . " 2>&1";
$result = `$command`;
//echo $command;
$stl_file = fopen($step_filename . ".stl", "r");
if ($stl_file){
  $stl = "";
  while (!feof($stl_file))
    $stl .= fgets($stl_file);
  fclose($stl_file);
}

header("Content-Type: ". $fileArray['type']);
header('Content-Disposition: attachment; filename="' . $fileArray['name'] . '.stl"');
echo $stl;

include "page_footer.inc";
?>