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

//echo "<PRE>";
$copia =  `cp -vf $step_filename /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/uploaded`;
//echo "</PRE>";

//$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie.sh ./repaired/FACE_repaired_repaired.step' 2>/dev/null";
$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie.sh ./uploaded/" . fixField($fileArray['name']) . "' 2>/dev/null";

$resultado = `$command`;
// echo "<PRE>";
// echo $resultado;
// echo "</PRE>";

$resultado = explode("---- terminei o recognize and volume -------------", $resultado);
$resultado = explode("Batch mode finished successfully", $resultado[1]);
$cutting_data = json_decode($resultado[0], true);
//echo $resultado[0];

// echo "<PRE>";
//echo print_r($cutting_data['part']['features'], true);
$query_igieintegracao = "delete from processos_peca where usuario = 'igie65' and \"Peça\" = " . intval($buttonrow_key);
$result = pg_exec($conn, $query_igieintegracao);

foreach($cutting_data['part']['features'] as $feature){
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste: " . $feature['roughing_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de acabamento: " . $feature['finishing_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR>";
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo do processo (horas)\",  \"Tempo passivo - NOP (horas)\", \"Observações\") values ('igie65', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . " (desbaste)',"; // nome
	$query_igieintegracao	.= "'" . floatval($feature['roughing_time']) . " min'::interval, "; // tempo
	$query_igieintegracao .= "'" . floatval($feature['nop_time']) . " min'::interval, "; // tempo
	$query_igieintegracao .= "'Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "')";
  //echo $query_igieintegracao;
	//echo "\n";
  $result = pg_exec($conn, $query_igieintegracao);
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo do processo (horas)\", \"Observações\") values ('igie65', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . " (acabamento)',"; // nome	
	$query_igieintegracao .= "'" . floatval($feature['finishing_time']) . " min'::interval, "; // tempo
	$query_igieintegracao .= "'Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "')";
  $result = pg_exec($conn, $query_igieintegracao);
  //echo $query_igieintegracao;
	//echo "\n";
}

//echo "</PRE>";

// foreach($cutting_data as $feature => $feature_data){
// 	echo "<H1>" . $feature . "</H1>";
//   foreach($feature_data as $detail_key => $feature_details){
// 		if (!is_array($feature_details))
// 	    echo "<B>" . $feature . "[" . $detail_key . "]</B> = " . $feature_details . "<BR>";
// 		else{
// 		  echo "<H2>" . $feature . "[" . $detail_key . "]</H2>";			
//       foreach($feature_details as $key => $value){
//   	    echo "<B>" . $key . "</B>"; 
//   		  if (!is_array($value)) echo " = " . $value . "<BR>";  
//     		  else{
//             echo "<PRE>";
// 	          echo print_r($value, true);
//           echo "</PRE>";
// 		    }
// 		  }
// 		}
// 	}
// }

?>
