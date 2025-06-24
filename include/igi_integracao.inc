<?PHP
// se inserir, deve remover o button row do get
$dados  = "'" . $formulario['tabela'] . "', \n";
$dados .= pg_escape_string($buttonrow_key);

$queryGetIgieUser  = "SELECT statconfig.\"Usar este usuário ao salvar processos do IGIE\" as igieUser ";
$queryGetIgieUser .= " from statconfig where codigo = 4";
$result = pg_exec($conn, $queryGetIgieUser);
if ($result){
	$fetched = pg_fetch_all($result);
	$igieUser = $fetched[0]['igieuser'];
 }
else
  $igieUser = "igie73.2";

$query_status = "SELECT encode(\"Modelo CAD (STEP)\", 'base64') as raw from \"Peças\" where \"codigo\" = " . intval($buttonrow_key);

$result = pg_exec($conn, $query_status);
if ($result){
	$peca = pg_fetch_assoc($result, 0);
  $fileArray = formsDecodeFile(base64_decode($peca['raw']));
 }

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

			// atualiza o sha1 do arquivo original
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;nome do arquivo: " . $fileArray['name'] . "<BR>\n";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sha1 do PHP : " . sha1($fileArray['contents']) . "<BR>\n";
			$updateSha1  = "UPDATE \"Peças\" set sha1_hash = '" . sha1($fileArray['contents']);
      $updateSha1 .= "' where codigo = " . intval($buttonrow_key) . "";
      $result = pg_exec($conn, $updateSha1);
      $command = "sha1sum '" . $step_filename ."'";
      echo "<PRE>" . $udpateSha1 . "</PRE>";
      echo pg_last_error() . "<BR>\n";
      //echo "Sha1 do linux: " . `$command` . "<BR>\n";

			//echo "</PRE>";
			$command  = $path_to_python . " ../occ/fix_z.py ";
			$command .= "'" . $step_filename . "' 2>&1";
			$result = `$command`;
      //echo "<PRE>" . print_r($result, true) . "</PRE>";
			$fixed_filename = $step_filename . "_fixed";
			if (file_exists( $fixed_filename)){
				$command = "sha1sum '" . $step_filename . "_fixed'";
				$sha1_z_fixed = `$command`;
				//echo "Sha1 do linux Z fixed: " . $sha1_z_fixed . "";
				$fileZfixed = file_get_contents($step_filename . "_fixed", true);
				// atualiza o sha1 do arquivo original
				//echo "Sha1 PHP: " . sha1($fileZfixed)	. "\n";
				//echo "tamanho = " . strlen($sha1_z_fixed) . "\n";
				$updateSha1  = "UPDATE \"Peças\" set sha1_hash_z_fixed = '" . sha1($fileZfixed);
				$updateSha1 .= "' where codigo = " . $peca['codigo'];
				$result = pg_exec($conn, $updateSha1); 
			}			


//echo "<PRE>";
$copia =  `cp -vf $step_filename /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/uploaded`;
//echo "</PRE>";

$query_material = "select nome from materiais where codigo = (select (select  \"Material de referência para parâmetros de corte\" from materias_primas_yuri where codigo = \"Matéria prima\") from \"Peças\" where codigo = " . intval($buttonrow_key) . ")";

$result = pg_exec($conn, $query_material);
$material = pg_fetch_assoc($result, 0);

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . $material['nome'] . "<BR>\n";

$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie_material.sh ./uploaded/" . fixField($fileArray['name']) . " \"" . utf8_decode($material['nome']) ."\"' 2>/dev/null";

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;comando: " . $command . "<BR>\n";

$resultado = `$command`;
$resultado = str_replace('[34m', '<font color=blue>', $resultado);
$resultado = str_replace('[33m', '<font color=yellow>', $resultado);
$resultado = str_replace('[32m', '<font color=green>', $resultado);
$resultado = str_replace('[31m', '<font color=red>', $resultado);
$resultado = str_replace('[0m', '</font>', $resultado);

 if ($isdeveloper){
   togglePoint(md5($resultado), "Exibir log de comunicação com o IGIE", 1, false, NULL, NULL);
   echo "<PRE>";
   echo $resultado;
   echo "</PRE>";
	 echo "</div>";
 }

$resultado = explode("---- terminei o recognize and volume -------------", $resultado);
$resultado = explode("Batch mode finished successfully", $resultado[1]);
$cutting_data = json_decode($resultado[0], true);


$query_igieintegracao = "delete from processos_peca where usuario = '" . $igieUser . "' and \"Peça\" = " . intval($buttonrow_key);
$result = pg_exec($conn, $query_igieintegracao);

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup:</B> " . $cutting_data['machine']['set_up_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup (ferramenta):</B> " . $cutting_data['machine']['set_up_time_tool'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo não produtivo (NOP):</B> " . $cutting_data['part']['nop_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Percentual reconhecido:</B> " . $cutting_data['part']['percentage_recognized'] . "%<BR>";

$getFeatureCodesQuery = "select * from features_igie";
$getFeatureCodeResult = pg_exec($conn, $getFeatureCodesQuery);
$featuresFetch = pg_fetch_all($getFeatureCodeResult);
foreach($featuresFetch as $feature_igie){
	$features_igie[$feature_igie['nome']] = $feature_igie['codigo'];
  $featuresArray[] = $feature_igie['nome'];
}
// echo "<PRE>";
// var_dump($features_igie);
// echo "</PRE>";

$getOperationsQuery = "select * from \"Operações\"";
$getOperationsResult = pg_exec($conn, $getOperationsQuery);
$operationsFetch = pg_fetch_all($getOperationsResult);
foreach($operationsFetch as $operation){
	$operations[$operation['nome']] = $operation['codigo'];
}

$orcamentistas[] = $igieUser;
$orcamentistas[] = 'maicon';
$orcamentistas[] = 'yuri';
$orcamentistas[] = '10056942';

foreach ($orcamentistas as $orcamentista){

	// tem qur ver se tem registros antes de inserir (nao inserir se tiver registros)

	// Tempo de setup e tempo de esquadro
	$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\", \"Tempo de desbaste (horas)\", \"Observações\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'Setup no NOP e esquadro no desbaste', "; // nome
	$query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
	$query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']+$cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
	$query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
	$query_igieintegracao .= "'tempo de esquadro e tempo de setup')";
	//echo "<PRE>" . $query_igieintegracao . "</PRE>";
	$result = pg_exec($conn, $query_igieintegracao);

  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de setup: " . $cutting_data['machine']['set_up_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste para esquadro: " . $cutting_data['part']['esquadro'] . " min<BR>";

	foreach($cutting_data['part']['features'] as $feature){
		// echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
		// echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
		// echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste: " . $feature['roughing_time'] . " min<BR>";
		// echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de acabamento: " . $feature['finishing_time'] . " min<BR>";
		// echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
		// echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR>";

		//echo "intval($features_igie[$features['featureType']]) . "<BR>";
    if(!in_array($feature['featureType'], $featuresArray)){
      //echo "Não encontrei a fearures: " . $feature['featureType'] . "<BR>";
      pg_exec($conn, "INSERT INTO features_igie (nome) values ('" . $feature['featureType'] . "')");
      $getFeatureCodesQuery = "select * from features_igie where nome = '" . $feature['featureType'] . "'";
      $getFeatureCodeResult = pg_exec($conn, $getFeatureCodesQuery);
      $novaFeaturesFetch = pg_fetch_assoc($getFeatureCodeResult, 0);
			//echo "<PRE>" . print_r($novaFeaturesFetch, true) . "</PRE>";
			$features_igie[$novaFeaturesFetch['nome']] = $novaFeaturesFetch['codigo'];
			$featuresArray[] = $novaFeaturesFetch['nome'];
		}
		// Desbaste, acabamento e NOP
		$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo de desbaste (horas)\",  \"Tempo passivo - NOP (horas)\", \"Tempo de acabamento (horas)\", \"Tempo do processo (horas)\", \"Superfícies\", feature, \"Operação\", \"Volume da feature (mm³)\", \"Volume de desbaste (mm³)\", \"Área de acabamento (mm²)\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
		$query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . "',"; // nome

		$query_igieintegracao	.= "((to_char((" . floatval($feature['roughing_time']) . ")::double precision, '99999999999999.999999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
		$query_igieintegracao .= "((to_char((" . floatval($feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
		$query_igieintegracao .= " ((to_char((" . floatval($feature['finishing_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
		$query_igieintegracao .= " ((to_char((" . floatval($feature['roughing_time']+$feature['finishing_time']+$feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
		$query_igieintegracao .= "'" . implode(",", $feature['faceIds'][0]['face_ids']) . "',";
		$query_igieintegracao .= " " . intval($features_igie[$feature['featureType']]) . ", ";
		$query_igieintegracao .= " " . (strpos(strtoupper($feature['featureType']), 'HOLE')?4:intval($operations[trim($feature['operation'])])) . ", ";
    $query_igieintegracao .= " " . floatval($feature['volume']) . ", ";
		$query_igieintegracao .= " " . floatval($feature['roughing_volume']) . ", ";
		$query_igieintegracao .= " " . floatval($feature['finishing_area']) . "  ";
    $query_igieintegracao .= ") ";
		//echo "<PRE>" . $query_igieintegracao . "</PRE>";
		$result = pg_exec($conn, $query_igieintegracao);
	}
  // percentual detectado
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Observações\", \"Tempo do processo (horas)\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
  $query_igieintegracao .= "'percentual reconhecido',"; // nome
  $query_igieintegracao .= "'" . $cutting_data['part']['percentage_recognized'] . "', ('00:00:00')::interval)";
  $result = pg_exec($conn, $query_igieintegracao);
 
}

$command = "cd ../scripts; ./saveViews.php " . intval($buttonrow_key) . "&";
$resultado1 = `$command`;
//echo "<PRE>" . $resultado1 . "</PRE>";
$command = "cd ../scripts; ./saveViewsZFixed.php " . intval($buttonrow_key) . "&";
$resultado2 = `$command`;
//echo "<PRE>" . $resultado2 . "</PRE>";
$command = "cd ../scripts; ./saveFaces.php " . intval($buttonrow_key) . "&";
$resultado3 = `$command`;
//echo "<PRE>" . $resultado3 . "</PRE>";
?>
