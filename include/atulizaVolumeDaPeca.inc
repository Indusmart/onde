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

$step_filename = "./" . $workPath . "simulation" .  $PHPSESSID . "/occ/" . fixField($fileArray['name']);
$step_file = fopen($step_filename, "w");
fputs($step_file, $fileArray['contents']);
fclose($step_file);

//echo $path_to_python;
$command  = $path_to_python . " ../occ/getPropos.py ";
//$command .= $step_filename . " " . $step_filename . ".stl 2>/dev/null";
$command .= $step_filename . " " . $step_filename . ".stl 2>&1";
$string = `$command`;
// echo "<PRE>";
// echo $command;
// echo $string;
// echo "</PRE>";
$string = preg_replace("/(\r|\n)/u", "", $string);
$pattern = '/.*?({.*)/i';
$replacement = '$1';
$result = preg_replace($pattern, $replacement, $string);

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
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . "Massa específica: " . number_format($massa_especifica, 2, ",", ".") . " (g/cm³)<BR>";
//var_dump($occ_string);
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
//echo "<B>Massa da peça pronta: ";
//echo number_format((($occ_string['volume_peca']/1000) * $massa_especifica)/1000, 2, ",", ".") . " kg</B><BR>";

$query_atualiza_occ  = "UPDATE \"Peças\" set\n";
$query_atualiza_occ .= " \"Peso da peça (kg)\" = " . floatval((($occ_string['volume_peca']/1000) * $massa_especifica)/1000) . ",\n";
$query_atualiza_occ .= " \"Volume da peça (mm³)\" = " . floatval($occ_string['volume_peca']) .  ",\n";
$query_atualiza_occ .= " \"Área de superfície da peça (mm²)\" = " . floatval($occ_string['superficie']) .  ",\n";
$query_atualiza_occ .= " \"Comprimento da bounding box em x (mm)\" = " . floatval($occ_string['boundingbox'][0]) . ",\n";
$query_atualiza_occ .= " \"Comprimento da bounding box em y (mm)\" = " . floatval($occ_string['boundingbox'][1]) . ",\n";
$query_atualiza_occ .= " \"Comprimento da bounding box em z (mm)\" = " . floatval($occ_string['boundingbox'][2]) . "\n";
$query_atualiza_occ .= " WHERE codigo = " . intval($buttonrow_key);

//echo "<PRE>" . $query_atualiza_occ . "</PRE>";
$result_atualiza_occ = pg_exec($conn, $query_atualiza_occ);


$command  = "echo 'Qw121314!' | su - indusmart -c ";
$command .= "'export DISPLAY=:0.0; cd /home/indusmart/faceShot/; pwd;";
$command .= "cp /home/filipi/onde/session_files/simulation" .  $PHPSESSID . "/occ/" . fixField($fileArray['name']) . " /home/indusmart/faceShot/uploaded/; ";
$command .= "./allViews.sh ./uploaded/" . fixField($fileArray['name']) . "'  2>&1";

//echo "<PRE>";
//echo $command;
$resultado = `$command`;
//echo print_r($resultado, true);
//echo "</PRE>";

			if ($fileArray['name']){
				// $isometric_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_isometric.png";
				// $isometric['name'] = $fileArray['name'] . "_isometric.png";
				// $isometric['type'] = "image/png";
				// $isometric['contents'] = file_get_contents($isometric_file_name);
				// $isometricData = formsEncodeFile($isometric);
        // unset($isometric);
				
        //echo "PASSEI\n";
				$gray_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_gray.png";
				$gray['name'] = $fileArray['name'] . "_gray.png";
				$gray['type'] = "image/png";
				$gray['contents'] = file_get_contents($gray_file_name);
				$grayData = formsEncodeFile($gray);
				//echo "<PRE>" . print_r($gray, true) . "</PRE>";
        unset($gray);
				
				$wireframe_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_wireframe.png";
				$wireframe['name'] = $fileArray['name'] . "_wireframe.png";
				$wireframe['type'] = "image/png";
				$wireframe['contents'] = file_get_contents($wireframe_file_name);
				$wireframeData = formsEncodeFile($wireframe);
        unset($wireframe);
				
  			// $update_isometric = "UPDATE \"Peças\" set preview = '" . $isometricData . "' where codigo = " . $peca['codigo'];
        // $result = pg_exec($conn, $update_isometric); 
  			$update_gray = "UPDATE \"Peças\" set preview_transparent = '" . $grayData . "' where codigo = " . intval($buttonrow_key);
        $result = pg_exec($conn, $update_gray);
				//echo "<PRE>" . print_r($result, true) . "</PRE>";
				//echo pg_last_error();
	  		$update_wireframe = "UPDATE \"Peças\" set preview_wireframe = '" . $wireframeData . "' where codigo = " . intval($buttonrow_key);
        $result = pg_exec($conn, $update_wireframe);
      }

?>