<?PHP
//echo intval($queryarguments[0]['value']);
// se inserir, deve remover o button row do get
$dados  = "'" . $formulario['tabela'] . "', \n";
$dados .= pg_escape_string($buttonrow_key);

$query_status = "SELECT encode(\"Modelo CAD (STEP)\", 'base64') as raw from \"Peças\" where \"codigo\" = " . intval($buttonrow_key);
//echo "<PRE>" . $query_status . "</PRE>";

$result = pg_exec($conn, $query_status);
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

$step_filename = "./" . $workPath . "simulation" .  $PHPSESSID . "/occ/" . $fileArray['name'];
$step_file = fopen($step_filename, "w");
fputs($step_file, $fileArray['contents']);
fclose($step_file);
//echo $path_to_python;
$command  = $path_to_python . " ../occ/getPropos.py ";
$command .= $step_filename . " " . $step_filename . ".stl 2>&1";
$result = `$command`;

// echo "<PRE>";
// echo $result;
// echo "</PRE>";
$occ_string = json_decode($result, true);
foreach ($occ_string as $key => $val){
	if ($key != 'boundingbox')
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .			$key . " = " . number_format($val, 2, ",", ".") . "<BR>";
  else
    foreach ($val as $innerkey => $innerval){
      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			echo "boundingbox[" . $innerkey . "] = " . number_format($innerval, 2, ",", ".") . "<BR>";
		}
}
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . "Materia prima: " . intval($_POST['Materia_prima']) . "<BR>";
$query_pega_prop  = "select \"Massa específica (g/cm³)\" from materias_primas_yuri";
$query_pega_prop .= " where codigo = " . intval($_POST['Materia_prima']);
$resultProp = pg_exec($conn, $query_pega_prop);
if($resultProp){
  $row = pg_fetch_row($resultProp, 0);
  $massa_especifica = $row[0];
}
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . "Massa específica: " . number_format($massa_especifica, 2, ",", ".") . " (g/cm³)<BR>";
//var_dump($occ_string);
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<B>Massa da peça pronta: ";
echo number_format((($occ_string['volume_peca']/1000) * $massa_especifica)/1000, 2, ",", ".") . " kg</B><BR>";
?>