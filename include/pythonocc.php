<?PHP
function cleanOCC(){
  global $_debug, $filename;

  if (!$_debug){
    unlink($filename . ".stp");
    unlink($filename . ".stl");
  }
}

function getPart($partCode, $conn){
	if (!intval($partCode)) return 0;
  $query = "select \"Modelo CAD (STEP)\" where codigo = " . intval($partCode);
  $result = pg_exec($conn, $query);
	if ($result){
		$partData = pg_fetch_array($result, 0);
	}
	
}

// First of all, check dependencies.
$deps = array("python");

foreach ($deps as $dep){
  $dep = trim(str_replace("-", "_", $dep));  
  $variable = "path_to_" . $dep;
  global $$variable;
}

if (!depsOK($deps)){
  if ($_debug)
    echo "Faltam dependencias!!!\n\n";
  include "page_footer.inc";
  exit(1);
}

if (!file_exists( ($useSessions?"../session_files/":"") . "simulation" .  $PHPSESSID)){
  mkdir("./" . ($useSessions?"../session_files/":"") . "simulation" .  $PHPSESSID, 0777);  
}
if (!file_exists(($useSessions?"../session_files/":"") . "simulation" .  $PHPSESSID . "/occ/")){
  mkdir("./" . ($useSessions?"../session_files/":"") . "simulation" .  $PHPSESSID . "/occ/", 0777);  
}
$filename = round(time() / 10 * rand(1,10));
$filename =  ($useSessions?".." . $slashPath . "session_files" . $slashPath:"") . "simulation" . $PHPSESSID . $slashPath . "occ" . $slashPath . $filename;

  if (!$total){
    if ($_debug>1){
      echo "Ano: " . $ano . "\n";
      echo $query . "\n";
      echo "Linhas resultantes: " . intval($total) . "\n";
    }
    include "page_footer.inc";
    exit(0);
  }

//  passthru($command);

?>