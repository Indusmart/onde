#!/usr/bin/php
<?PHP
	//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
	///////////////////////////////////////////////////////////////// Tratando POST
	/////////////////////////////////////////////// GET passado para links (action)
	///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 0; $ehXML = 1;
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";
//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////
$queryGetIgieUser  = "SELECT statconfig.\"Usar este usuário ao salvar processos do IGIE\" as igieUser ";
$queryGetIgieUser .= " from statconfig where codigo = 4";
$result = pg_exec($conn, $queryGetIgieUser);
if ($result){
	$fetched = pg_fetch_all($result);
	$igieUser = $fetched[0]['igieuser'];
}
 else{
	 echo "Erro ao detectar igie user\n";
	 echo $queryGetIgieUser . "\n";
	 exit();
 }

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors','On');

$workPath = "";
$query = "select codigo, encode(\"Modelo CAD (STEP)\", 'base64') as raw from \"Peças\"";
$query  = "select codigo, encode(\"Modelo CAD (STEP)\", 'base64') as raw ";
$query .= " from \"Peças\" ";
//$query .= " where codigo = 154";
$result = pg_exec($conn, $query);
if ($result){
	$pecas = pg_fetch_all($result);
	foreach($pecas as $peca){
		echo "Peça " . $peca['codigo'] . "\n";
    if (isset($peca['raw'])){
      //Pega conteúdo do arquivo da peça
			$fileArray = formsDecodeFile(base64_decode($peca['raw']));

			// cria o diretório temporário para salvar a peça
			if (!file_exists( $workPath . "steps")) mkdir("./" . $workPath . "steps", 0777);  

			// salva a peça no arquivo
			$step_filename = "./" . $workPath . "steps/" . fixField($fileArray['name']);
			$step_file = fopen($step_filename, "w");
			echo "nome do arquivo: " . $fileArray['name'] . "\n";
			echo "Sha1 do PHP : " . sha1($fileArray['contents']) . "\n";
			fputs($step_file, $fileArray['contents']);
			fclose($step_file);

			// atualiza o sha1 do arquivo original
			$updateSha1  = "UPDATE \"Peças\" set sha1_hash = '" . sha1($fileArray['contents']);
      $updateSha1 .= "' where codigo = " . $peca['codigo'];
      $result = pg_exec($conn, $updateSha1); 
      $command = "sha1sum './steps/" . fixField($fileArray['name']) . "'";
      echo "Sha1 do linux: " . `$command` . "";

			//echo "</PRE>";
			$command  = $path_to_python . " ../occ/fix_z.py ";
			$command .= $step_filename . " 2>&1";
			$result = `$command`;
			
			$fixed_filename = $step_filename . "_fixed";
			if (file_exists( $fixed_filename)){
				$command = "sha1sum '" . $step_filename . "_fixed'";
				$sha1_z_fixed = `$command`;
				echo "Sha1 do linux Z fixed: " . $sha1_z_fixed . "";
				$fileZfixed = file_get_contents($step_filename . "_fixed", true);
				// atualiza o sha1 do arquivo original
				echo "Sha1 PHP: " . sha1($fileZfixed)	. "\n";
				echo "tamanho = " . strlen($sha1_z_fixed) . "\n";
				$updateSha1  = "UPDATE \"Peças\" set sha1_hash_z_fixed = '" . sha1($fileZfixed);
				$updateSha1 .= "' where codigo = " . $peca['codigo'];
				$result = pg_exec($conn, $updateSha1); 
			}			
      //exit();
			// $command  = "echo 'Qw121314!' | su - indusmart -c ";
			// $command .= "'export DISPLAY=:0.0; cd /home/indusmart/faceShot/; ./allViews.sh ./uploaded/";
			// $command .= fixField($fileArray['name']) . "'  2>&1";
			
			$command  = "echo 'Qw121314!' | su - indusmart -c ";
			$command .= "'export DISPLAY=:0.0; cd /home/indusmart/faceShot/; ";
			$command .= "./allViews.sh ./uploaded/" . fixField($fileArray['name']) . "'  2>&1";
			
			//$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./partView.sh ./uploaded/" . fixField($fileArray['name']) . "' 2>/dev/null";
			$resultado = `$command`;
      //echo $resultado;
			if ($fileArray['contents']){
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
        unset($gray);
				
				$wireframe_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_wireframe.png";
				$wireframe['name'] = $fileArray['name'] . "_wireframe.png";
				$wireframe['type'] = "image/png";
				$wireframe['contents'] = file_get_contents($wireframe_file_name);
				$wireframeData = formsEncodeFile($wireframe);
        unset($wireframe);
				
  			// $update_isometric = "UPDATE \"Peças\" set preview = '" . $isometricData . "' where codigo = " . $peca['codigo'];
        // $result = pg_exec($conn, $update_isometric); 
  			$update_gray = "UPDATE \"Peças\" set preview_transparent = '" . $grayData . "' where codigo = " . $peca['codigo'];
        $result = pg_exec($conn, $update_gray); 
	  		$update_wireframe = "UPDATE \"Peças\" set preview_wireframe = '" . $wireframeData . "' where codigo = " . $peca['codigo'];
        $result = pg_exec($conn, $update_wireframe);
      }
 		  ////////////////////////////// Integracao com o igie
      $copia =  `cp -vf $step_filename /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/uploaded`;		


			$query_material = "select nome from materiais where codigo = (select (select  \"Material de referência para parâmetros de corte\" from materias_primas_yuri where codigo = \"Matéria prima\") from \"Peças\" where codigo = " . $peca['codigo'] . ")";

			$result = pg_exec($conn, $query_material);
			$material = pg_fetch_assoc($result, 0);
			//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . print_r($query_material, true) . "<BR>\n";
			//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . $material['nome'] . "<BR>\n";
			//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;MATERIAL: " . utf8_decode($material['nome']) . "<BR>\n";

			//$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie.sh ./repaired/FACE_repaired_repaired.step' 2>/dev/null";
			$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie_material.sh ./uploaded/" . fixField($fileArray['name']) . " \"" . utf8_decode($material['nome']) ."\"' 2>/dev/null";

			//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;comando: " . $command . "<BR>\n";

			$resultado = `$command`;
			$resultado = explode("---- terminei o recognize and volume -------------", $resultado);
			$resultado = explode("Batch mode finished successfully", $resultado[1]);
			$cutting_data = json_decode($resultado[0], true);
			//echo $resultado[0];

			// echo "<PRE>";
			//echo print_r($cutting_data['part']['features'], true);
			$query_igieintegracao = "delete from processos_peca where usuario = '" . $igieUser . "' and \"Peça\" = " . $peca['codigo'];
			$result = pg_exec($conn, $query_igieintegracao);

			foreach($cutting_data['part']['features'] as $feature){
				// Desbaste com NOP
				$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo do processo (horas)\",  \"Tempo passivo - NOP (horas)\", \"Observações\") values ('" . $igieUser . "', " . $peca['codigo'] . ", 2, ";
				$query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . " (desbaste)',"; // nome
				$query_igieintegracao	.= "((to_char((" . floatval($feature['roughing_time']) . ")::double precision, '99999999999999.999999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
				$query_igieintegracao .= "((to_char((" . floatval($feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
				$query_igieintegracao .= "'Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "')";
				//echo $query_igieintegracao;
				//echo "\n";
				$result = pg_exec($conn, $query_igieintegracao);

				// Acabemento
				$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo do processo (horas)\", \"Observações\") values ('" . $igieUser . "', " . $peca['codigo'] . ", 2, ";
				$query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . " (acabamento)',"; // nome	
				$query_igieintegracao .= " ((to_char((" . floatval($feature['finishing_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
				$query_igieintegracao .= "'Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "')";
				$result = pg_exec($conn, $query_igieintegracao);
				//echo $query_igieintegracao;
				//echo "\n";
			}
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

			// Tempo de setup de ferramneta
			$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\") values ('" . $igieUser . "', " . $peca['codigo'] . ", 2, ";
			$query_igieintegracao .= "'Tempo de setup (ferramenta)',"; // nome
			$query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time_tool']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ('00:00:00')::interval)"; // tempo
			//echo $query_igieintegracao;
			//echo "\n";
			$result = pg_exec($conn, $query_igieintegracao);

			// NOP total
			$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\") values ('" . $igieUser . "', " . $peca['codigo'] . ", 2, ";
			$query_igieintegracao .= "'Tempo não produtivo (NOP)',"; // nome
			$query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['setup_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ('00:00:00')::interval)"; // tempo
			//echo $query_igieintegracao;
			//echo "\n";
			$result = pg_exec($conn, $query_igieintegracao);

			// percentual detectado
			$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Observações\", \"Tempo do processo (horas)\") values ('" . $igieUser . "', " . $peca['codigo'] . ", 2, ";
			$query_igieintegracao .= "'percentual reconhecido',"; // nome
			$query_igieintegracao .= "'" . $cutting_data['part']['percentage_recognized'] . "', ('00:00:00')::interval)";
			$result = pg_exec($conn, $query_igieintegracao);

			
		}
	}
 }
echo "Terminei!!!\n";
include "page_footer.inc";
?>