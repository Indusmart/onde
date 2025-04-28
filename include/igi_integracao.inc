<?PHP
//echo intval($queryarguments[0]['value']);
// se inserir, deve remover o button row do get
$dados  = "'" . $formulario['tabela'] . "', \n";
$dados .= pg_escape_string($buttonrow_key);

$queryGetIgieUser  = "SELECT statconfig.\"Usar este usuário ao salvar processos do IGIE:\" as igieUser ";
$queryGetIgieUser .= " from statconfig where codigo = 4";
$result = pg_exec($conn, $queryGetIgieUser);
if ($result){
	$fetched = pg_fetch_all($result);
	$igieUser = $fetched[0]['igieuser'];
 }
else
  $igieUser = "igie73.2";

//echo "USUARIO: " . $igieUser . "<BR>";
//echo "<PRE>" . $queryGetIgieUser . "</PRE>";
//exit();

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

$query_material = "select nome from materiais where codigo = (select (select  \"Material de referência para parâmetros de corte\" from materias_primas_yuri where codigo = \"Matéria prima\") from \"Peças\" where codigo = " . intval($buttonrow_key) . ")";

$result = pg_exec($conn, $query_material);
$material = pg_fetch_assoc($result, 0);

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . print_r($query_material, true) . "<BR>\n";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . $material['nome'] . "<BR>\n";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . utf8_decode($material['nome']) . "<BR>\n";

//$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie.sh ./repaired/FACE_repaired_repaired.step' 2>/dev/null";
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
//echo $resultado[0];

// echo "<PRE>";
//echo print_r($cutting_data['part']['features'], true);
$query_igieintegracao = "delete from processos_peca where usuario = '" . $igie . "' and \"Peça\" = " . intval($buttonrow_key);
$result = pg_exec($conn, $query_igieintegracao);

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup:</B> " . $cutting_data['machine']['set_up_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup (ferramenta):</B> " . $cutting_data['machine']['set_up_time_tool'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo não produtivo (NOP):</B> " . $cutting_data['part']['nop_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Percentual reconhecido:</B> " . $cutting_data['part']['percentage_recognized'] . "%<BR>";

foreach($cutting_data['part']['features'] as $feature){
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste: " . $feature['roughing_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de acabamento: " . $feature['finishing_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR>";

  //echo "USUARIO: " . $igieUser . "<BR>";

	// Desbaste com NOP
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo de desbaste (horas)\",  \"Tempo passivo - NOP (horas)\", \"Tempo de acabamento (horas)\", \"Tempo do processo (horas)\", \"Observações\") values ('" . $igieUser . "', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . " (desbaste)',"; // nome

	$query_igieintegracao	.= "((to_char((" . floatval($feature['roughing_time']) . ")::double precision, '99999999999999.999999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
	$query_igieintegracao .= "((to_char((" . floatval($feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
	$query_igieintegracao .= " ((to_char((" . floatval($feature['finishing_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
	$query_igieintegracao .= " ((to_char((" . floatval($feature['roughing_time']+$feature['finishing_time']+$feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo

$query_igieintegracao .= "'Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "')";

//echo $query_igieintegracao;
//echo "<BR>\n";
//echo "<BR>\n";
  $result = pg_exec($conn, $query_igieintegracao);

  //echo $query_igieintegracao;
	//echo "\n";
}
  // Tempo de setup
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\") values ('" . $igie . "', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'Tempo de setup (máquina)',"; // nome
	$query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ('00:00:00')::interval)"; // tempo
  //echo $query_igieintegracao;
	//echo "\n";
  $result = pg_exec($conn, $query_igieintegracao);

// // Tempo de setup de ferramneta
//   $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\") values ('" . $igie . "', " . intval($buttonrow_key) . ", 2, ";
// 	$query_igieintegracao .= "'Tempo de setup (ferramenta)',"; // nome
// 	$query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time_tool']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ('00:00:00')::interval)"; // tempo
//   //echo $query_igieintegracao;
// 	//echo "\n";


			// Tempo de setup e tempo de esquadro
			$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\", \"Observações\") values ('" . $igieUser . "', " . $peca['codigo'] . ", 2, ";
			$query_igieintegracao .= "'Tempo de setup (máquina)',"; // nome
			$query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
			$query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
      $query_igieintegracao .= "'tempo de esquadro e tempo de setup')";
			//('00:00:00')::interval)"; // tempo
			//echo $query_igieintegracao;
			//echo "\n";
												 
  $result = pg_exec($conn, $query_igieintegracao);

// NOP total
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\") values ('" . $igie . "', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'Tempo não produtivo (NOP)',"; // nome
	$query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ('00:00:00')::interval)"; // tempo
  //echo $query_igieintegracao;
	//echo "\n";
  $result = pg_exec($conn, $query_igieintegracao);

// percentual detectado
  $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Observações\", \"Tempo do processo (horas)\") values ('" . $igie . "', " . intval($buttonrow_key) . ", 2, ";
	$query_igieintegracao .= "'percentual reconhecido',"; // nome
	$query_igieintegracao .= "'" . $cutting_data['part']['percentage_recognized'] . "', ('00:00:00')::interval)";
  $result = pg_exec($conn, $query_igieintegracao);

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
