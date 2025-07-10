<?PHP
// se inserir, deve remover o button row do get
$dados  = "'" . $formulario['tabela'] . "', \n";
$dados .= pg_escape_string($buttonrow_key);


$queryGetIgieUser  = "SELECT statconfig.\"Usar este usuário ao salvar processos do IGIE:\" as igieUser ";
$queryGetIgieUser .= " from statconfig where codigo = 4";
$result = pg_exec($conn, $queryGetIgieUser);
if ($result) {
    $fetched = pg_fetch_all($result);
    $igieUser = $fetched[0]['igieuser'];
} else {
    $igieUser = "igie73.2";
}

$query_status = "SELECT materia_prima as materiaprima, encode(\"modelo_cad_path\", 'base64') as raw from \"squsinagem\" where \"codigo\" = " . intval($buttonrow_key);
//echo $query_status;
$result = pg_exec($conn, $query_status);
if ($result) {
    $peca = pg_fetch_assoc($result, 0);
    $fileArray = formsDecodeFile(base64_decode($peca['raw']));
    $materiaPrima = $peca['materiaprima'];
}

if ($useSessions)
    $workPath = "../session_files/";
if (!file_exists($workPath . "simulation" . $PHPSESSID)) {
    mkdir("./" . $workPath . "simulation" . $PHPSESSID, 0777);
}
if (!file_exists($workPath . "simulation" . $PHPSESSID . "/occ/")) {
    mkdir("./" . $workPath . "simulation" . $PHPSESSID . "/occ/", 0777);
}

$step_filename = "./" . $workPath . "simulation" . $PHPSESSID . "/occ/" . fixField($fileArray['name']);
$step_file = fopen($step_filename, "w");
fputs($step_file, $fileArray['contents']);
fclose($step_file);

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sha1 do PHP : " . sha1($fileArray['contents']) . "<BR>\n";

//echo "<PRE>";
$copia =  `cp -vf $step_filename /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/uploaded`;
//echo "</PRE>";

//$query_material = "select nome from materias_primas_yuri where codigo = (select \"materia_prima\" from \"squsinagem\" where codigo = " . intval($buttonrow_key) . ")";

//$nome_projeto = "Projeto: " . $fileArray['name'];

/*//select "nome"
$query_squsinagem = "select nome, materia_prima, quantidade, data_criacao from squsinagem where codigo = " . intval($buttonrow_key);
select nome,
(select nome from materias_primas_yuri mp where mp.codigo = sq.materia_prima) as "Materia Prima",
(select valor from materias_primas_yuri mp where mp.codigo = sq.materia_prima) as "Custo/Kg",
quantidade,
data_criacao
from squsinagem sq;
*/
/*
$query_squsinagem = "SELECT nome, materia_prima, quantidade, data_criacao FROM squsinagem WHERE codigo = " . intval($buttonrow_key);
$result = pg_exec($conn, $query_squsinagem);

if ($result) {
    $data = pg_fetch_assoc($result);

    $array_info = [
        'nome' => $data['nome'],
        'materia_prima' => $data['materia_prima'],
        'quantidade' => $data['quantidade'],
        'data_criacao' => $data['data_criacao']
    ];

} else {
    echo "Erro ao executar a query.";
}
*/

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sha1 do PHP : " . sha1($fileArray['contents']) . "<BR>\n";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<PRE>" . $queryCheckPecas . "</PRE>";


$query_material  = "select nome from materiais where ";
$query_material .= " codigo = (select (select  \"Material de referência para parâmetros de corte\" ";
$query_material .= " from materias_primas_yuri where codigo = \"materia_prima\") ";
$query_material .= " from \"squsinagem\" where codigo = " . intval($buttonrow_key) . ")";
$result = pg_exec($conn, $query_material);
$material = pg_fetch_assoc($result, 0);

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
//  foreach ($occ_string as $key => $val){
//  	if ($key != 'boundingbox')
//      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .			$key . " = " . number_format($val, 2, ",", ".") . "<BR>";
//    else
//      foreach ($val as $innerkey => $innerval){
//        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
//  			 echo "boundingbox[" . $innerkey . "] = " . number_format($innerval, 2, ",", ".") . "<BR>";
//  		}
//  }
// echo "<PRE>" . print_r($occ_string, true) . "</PRE>";

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . "Materia prima: " . $materiaPrima . "<BR>";
$query_pega_prop  = "select \"Massa específica (g/cm³)\" from materias_primas_yuri";
$query_pega_prop .= " where codigo = " . $materiaPrima;
$resultProp = pg_exec($conn, $query_pega_prop);
if($resultProp){
  $row = pg_fetch_row($resultProp, 0);
  $massa_especifica = $row[0];
}

$queryCheckPecas  = "select codigo from \"Peças\" ";
$queryCheckPecas .= "  where sha1_hash = '" . sha1($fileArray['contents']) . "'";
$queryCheckPecas .= " and \"Modelo CAD (STEP)\" is null";
$result = pg_exec($conn, $queryCheckPecas);
if ($result){
  $pecas = pg_fetch_row($result, 0);
  //echo "<PRE>PECAS"; var_dump($pecas); echo  "</PRE>";
	if ($pecas){ // sha1 encontrado e peca náo (atualiza com peca e dados do OCC)
    //echo "<PRE>PECAS" . print_r($pecas, true) . "</PRE>";
    //echo "<PRE>PECAS"; var_dump($pecas); echo  "</PRE>";
	  //update
    $query_atualiza_occ  = "UPDATE \"Peças\" set\n";
    $query_atualiza_occ .= "  \"Modelo CAD (STEP)\" = (select modelo_cad_path from squsinagem where codigo = " . intval($buttonrow_key) . "),\n";
    $query_atualiza_occ .= " \"Peso da peça (kg)\" = " . floatval((($occ_string['volume_peca']/1000) * $massa_especifica)/1000) . ",\n";
    $query_atualiza_occ .= " \"Volume da peça (mm³)\" = " . floatval($occ_string['volume_peca']) .  ",\n";
    $query_atualiza_occ .= " \"Área de superfície da peça (mm²)\" = " . floatval($occ_string['superficie']) .  ",\n";
    $query_atualiza_occ .= " \"Comprimento da bounding box em x (mm)\" = " . floatval($occ_string['boundingbox'][0]) . ",\n";
    $query_atualiza_occ .= " \"Comprimento da bounding box em y (mm)\" = " . floatval($occ_string['boundingbox'][1]) . ",\n";
    $query_atualiza_occ .= " \"Comprimento da bounding box em z (mm)\" = " . floatval($occ_string['boundingbox'][2]) . "\n";
    $query_atualiza_occ .= " WHERE codigo = " . $pecas[0];
    //echo "<PRE>"; var_dump($query_atualiza_occ); echo "</PRE>";
    $result = pg_exec($conn, $query_atualiza_occ);					
	}else{
	 //echo "passei";
    $queryCheckPecas  = "select codigo from \"Peças\" ";
    $queryCheckPecas .= "  where sha1_hash = '" . sha1($fileArray['contents']) . "'";
    $queryCheckPecas .= " and \"Modelo CAD (STEP)\" is not null";
    $result = pg_exec($conn, $queryCheckPecas);	
    $pecas = pg_fetch_row($result, 0);
    if ($result){
			//echo "tem resultado";
  	  if (!$pecas){
			// 	echo "tem peca";
      //   echo "<PRE>PECAS" . print_r($pecas, true) . "</PRE>";
      //   echo "<PRE>PECAS"; var_dump($pecas); echo  "</PRE>";
			// 	echo "nao faz nada";
			// }
			// else{
			 	//echo "tem que inserir";
			 	//echo "<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Button row key: " . intval($buttonrow_key)  . "<BR>";
				$queryInsertIntoPecas  = "INSERT INTO \"Peças\" (orcamento, usuario,sha1_hash) values (112, '";
				$queryInsertIntoPecas	.= $_SESSION['matricula'] . "', '";
				$queryInsertIntoPecas	.= sha1($fileArray['contents']) . "')";
				//echo "<PRE>" . $queryInsertIntoPecas . "</PRE><BR>";
        $result = pg_exec($conn, $queryInsertIntoPecas);
        $queryCheckPecas  = "select codigo from \"Peças\" ";
        $queryCheckPecas .= "  where sha1_hash = '" . sha1($fileArray['contents']) . "'";
        $queryCheckPecas .= " and \"Modelo CAD (STEP)\" is null";
				//echo $queryCheckPecas;
        $result = pg_exec($conn, $queryCheckPecas);
        if ($result){
          $pecas = pg_fetch_row($result, 0);
          $query_atualiza_occ  = "UPDATE \"Peças\" set\n";
          $query_atualiza_occ .= "  \"Modelo CAD (STEP)\" = (select modelo_cad_path from squsinagem where codigo = " . intval($buttonrow_key) . "),\n";
          $query_atualiza_occ .= " \"Peso da peça (kg)\" = " . floatval((($occ_string['volume_peca']/1000) * $massa_especifica)/1000) . ",\n";
          $query_atualiza_occ .= " \"Volume da peça (mm³)\" = " . floatval($occ_string['volume_peca']) .  ",\n";
          $query_atualiza_occ .= " \"Área de superfície da peça (mm²)\" = " . floatval($occ_string['superficie']) .  ",\n";
          $query_atualiza_occ .= " \"Comprimento da bounding box em x (mm)\" = " . floatval($occ_string['boundingbox'][0]) . ",\n";
          $query_atualiza_occ .= " \"Comprimento da bounding box em y (mm)\" = " . floatval($occ_string['boundingbox'][1]) . ",\n";
          $query_atualiza_occ .= " \"Comprimento da bounding box em z (mm)\" = " . floatval($occ_string['boundingbox'][2]) . "\n";
          $query_atualiza_occ	.= " WHERE codigo = " . $pecas[0];
          $result = pg_exec($conn, $query_atualiza_occ);					
          //echo "<PRE>"; var_dump($query_atualiza_occ); echo "</PRE>";
				}
			}
		}
		
	}
 }
//else{ // sha_1 e peca encontrado (OK não precisa fazer nada)
//}


//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Material:</b> " . $material['nome'] . "<BR>\n";
$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie_material.sh ./uploaded/" . fixField($fileArray['name']) . " \"" . utf8_decode($material['nome']) ."\"' 2>/dev/null";

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;comando: " . $command . "<BR>\n";

$resultado = `$command`;
$resultado = str_replace('[34m', '<font color=blue>', $resultado);
$resultado = str_replace('[33m', '<font color=yellow>', $resultado);
$resultado = str_replace('[32m', '<font color=green>', $resultado);
$resultado = str_replace('[31m', '<font color=red>', $resultado);
$resultado = str_replace('[0m', '</font>', $resultado);

$resultado_raw = $resultado;

$resultado = explode("---- terminei o recognize and volume -------------", $resultado);
$resultado = explode("Batch mode finished successfully", $resultado[1]);
$cutting_data = json_decode($resultado[0], true);

$query_sq = '
SELECT
    sq.nome,
    mpy.nome AS nome_materia_prima,
    msq.custo AS custo_kg,
    sq.quantidade,
    sq.data_criacao,
    mpy."Massa específica (g/cm³)" AS massa_especifica
FROM
    squsinagem sq
LEFT JOIN
    materias_primas_yuri mpy ON sq.materia_prima = mpy.codigo
LEFT JOIN
    materiais_squsinagem msq ON sq.materia_prima = msq.id_materia_prima AND msq.usuario = $2
WHERE
    sq.codigo = $1
';

$result = pg_query_params($conn, $query_sq, [intval($buttonrow_key), $_SESSION['matricula']]);

$dados_usinagem = [];

if ($result) {
    $dados_usinagem = pg_fetch_assoc($result);

} else {
    echo "Erro na execução da query: " . pg_last_error($conn);
}

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
//Adicionar $onde_user em WHERE para pegar apenas os dados do usuário logado
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
$query_ferramentas = "
SELECT
    id_tipo_ferramenta,
    custo,
    data_de_vigencia
FROM
    ferramentas_squsinagem
WHERE
    usuario = $1
ORDER BY
    id_tipo_ferramenta,
    data_de_vigencia DESC;
";
$result_ferramentas = pg_query_params($conn, $query_ferramentas, [$_SESSION['matricula']]);

if (!isset($conn) || !$conn) {
   die("Erro: Conexão com o banco de dados não estabelecida ou inválida.");
   console.log("Erro: " . pg_last_error($conn));
} else {
    console.log("Conexão com o banco de dados estabelecida com sucesso.<br>");
}

//$result_ferramentas = pg_query_params($conn, $query_ferramentas, array($onde_user));
$ferramentas = [];

if ($result_ferramentas) {
    while ($row = pg_fetch_assoc($result_ferramentas)) {
        $ferramentas[] = [
            'id_tipo' => $row['id_tipo_ferramenta'],
            'custo' => $row['custo'],
            'data_de_vigencia' => $row['data_de_vigencia']
        ];
    }
} else {
    console.log("Erro na execução da query de ferramentas: " . pg_last_error($conn));
}
/*
echo "<PRE>";
var_dump($ferramentas);
echo "</PRE>";
*/
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//Inicio do código de exibição
/*
if (!empty($dados_usinagem)) {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Nome do Projeto:</b> " . htmlspecialchars($dados_usinagem['nome']) . "<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Matéria Prima:</b> " . htmlspecialchars($dados_usinagem['nome_materia_prima']) . "<BR>";
    if (isset($dados_usinagem['quantidade'])) {
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Quantidade:</b> " . htmlspecialchars($dados_usinagem['quantidade']) . "<BR>";
    }
    echo "<BR>";
} else {
    echo "Nenhum registro encontrado para o código " . intval($buttonrow_key);
}
*/

/*
Fresa Acabamento
Fresa Desbaste
NOP - No Operation Time
Torno Acabamento
Torno Desbaste
*/

$custo_acabamento = 0;
$custo_desbaste = 0;
$custo_nop = 0;

if($cutting_data['operation'] == 'fresamento'){
    $custo_acabamento = $ferramentas[0]['custo'] ?? 0;
    $custo_desbaste = $ferramentas[1]['custo'] ?? 0;
} else {
    $custo_acabamento = $ferramentas[3]['custo'] ?? 0;
    $custo_desbaste = $ferramentas[4]['custo'] ?? 0;
}

//calculo volume bounding box
if (isset($cutting_data['part']['bounding_box']['dimensions'])) {
    $dims = $cutting_data['part']['bounding_box']['dimensions'];
    $x = isset($dims['x']) ? floatval($dims['x']) : 0;
    $y = isset($dims['y']) ? floatval($dims['y']) : 0;
    $z = isset($dims['z']) ? floatval($dims['z']) : 0;
    $volume = ($x * $y * $z) / 1000; // mm³ to cm³
}

$massa_especifica = floatval($dados_usinagem['massa_especifica']);
$peso = floatval($volume * $massa_especifica); 
$custo_material = ($peso / 1000) * $dados_usinagem['custo_kg'];

$custo_total_desbaste = $cutting_data['part']['roughing_time']/60 * ($custo_desbaste);
$custo_total_acabamento = ($cutting_data['part']['total_active_machining_time'] - $cutting_data['part']['roughing_time'])/60 * ($custo_acabamento);
$custo_nop = ($cutting_data['part']['total_time'] - $cutting_data['part']['total_active_machining_time'])/60 * ($ferramentas[2]['custo'] ?? 0);
$custo_total_maquina = $custo_total_desbaste + $custo_total_acabamento + $custo_nop;

$custo_final = $custo_material + $custo_total_maquina;

//if (isset($dados_usinagem['quantidade'])) {
//    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo total:</b> R$" . number_format($custo_total_maquina * $dados_usinagem['quantidade'], 2, ',', '.') . "<br><br>";
//}


//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Volume do blank ideal:</b> " . number_format($volume, 2, ',', '.') . " mm³<br>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Material:</b> Aço inox<br>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Densidade:</b> 0.00<br>";

/*
colocar os echos e essas funções dentro de uma div com id onde_div_codigo
<script>  
  $("#onde_div_materia_prima b:first").text("Matéria prima");
  
  $("#onde_div_custo_materia_prima b:first").text("Custo da matéria prima");
  
  $("#onde_div_modelo_cad_path b:first").text("Modelo CAD");
  
  $("#onde_div_data_criacao").hide();

  $("#onde_div_preco_alvo b:first").text("Preço alvo");
</script>
*/

echo "<div id='onde_div_codigo'>";
echo "<div style='font-size: 2em; font-weight: bold;'>";
echo "<span style='font-size: 10pt;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b>CUSTO UNITÁRIO FINAL:</b> R$" . number_format($custo_final, 2, ',', '.') . "<br><br>";
echo "</div>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Valor total: R$</b> " . number_format($custo_final * $dados_usinagem['quantidade'], 2, ',', '.') . "<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo de material / Un.:</b> R$" . number_format($custo_material, 2, ',', '.') . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Material:</b> " . htmlspecialchars($dados_usinagem['nome_materia_prima']) . "<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Peso do blank:</b> " . number_format($peso/1000, 2, ',', '.') . " kg<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Valor/Kg:</b> R$ " . number_format($dados_usinagem['custo_kg'], 2, ',', '.') . "<br>";
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="https://services.indusmart.com.br/web/forms.php?&form=391" style="font-size: 18px; color: dodgerblue;">Atualizar custos de materiais</a></b><br><br>';

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo de operação / Un.: R$" . number_format($custo_total_maquina, 2, ',', '.') . "</b><br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo de desbaste: </b>" . ceil($cutting_data['part']['roughing_time']) . " min<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo de acabamento: </b>" . ceil($cutting_data['part']['total_active_machining_time'] - $cutting_data['part']['roughing_time']) . " min<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo de setup (NOP): </b>" . ceil($cutting_data['part']['total_time'] - $cutting_data['part']['total_active_machining_time']) . " min<br><br>";

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (acabamento fresa):</b> R$" . $ferramentas[0]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (desbaste fresa):</b> R$" . $ferramentas[1]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (acabamento torno):</b> R$" . $ferramentas[3]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (desbaste torno):</b> R$" . $ferramentas[4]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (nop): </b> R$" . $ferramentas[2]['custo'] . "<br>";
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="https://services.indusmart.com.br/web/forms.php?&form=392" style="font-size: 18px; color: dodgerblue;">Atualizar custos de ferramentas</a></b><br><br>';
echo "</div>";

if ($isdeveloper) {
    togglePoint(md5($resultado_raw), "Exibir log de comunicação com o IGIE", 1, false, NULL, NULL);
    echo "<PRE>";
    echo $resultado_raw;
    echo "</PRE>";
    echo "</div>";
}

/*
// Verificar se foi solicitada regeneração das visualizações 3D
if (isset($_GET['regen_3d']) && intval($_GET['regen_3d']) == intval($buttonrow_key)) {
    // Remover visualizações existentes
    $query_delete_models = "DELETE FROM \"Modelos 3D\" WHERE \"Peça\" = " . intval($buttonrow_key);
    $result_delete = pg_exec($conn, $query_delete_models);
    
    echo "<div style='margin: 20px 0; padding: 10px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;'>";
    echo "<p style='margin: 0; color: #856404;'><strong>Regenerando visualizações 3D...</strong></p>";
    echo "</div>";
    
    // Forçar a regeneração definindo model_exists como false
    $model_exists = false;
} else {
    // Integração do Visualizador 3D - Verificar e gerar modelos 3D se necessário
    $query_check_models = "SELECT COUNT(*) as count FROM \"Modelos 3D\" WHERE \"Peça\" = " . intval($buttonrow_key);
    $result_check = pg_exec($conn, $query_check_models);
    $model_exists = false;

    if ($result_check) {
        $check_data = pg_fetch_assoc($result_check);
        $model_exists = intval($check_data['count']) > 0;
    }
}

// Se não existem modelos 3D para esta peça, gerar eles usando o script SaveFaces
if (!$model_exists) {
    echo "<div style='margin: 20px 0; padding: 10px; background-color: #f0f8ff; border: 1px solid #0066cc; border-radius: 5px;'>";
    echo "<p style='margin: 0; color: #0066cc;'><strong>Gerando visualizações 3D...</strong></p>";
    echo "<p style='margin: 5px 0 0 0; font-size: 12px; color: #666;'>Esta operação pode levar alguns minutos na primeira vez.</p>";
    echo "</div>";
    
    // Flush do buffer para mostrar a mensagem imediatamente
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
    
    // Executar o script SaveFaces para esta peça específica
    $scripts_path = dirname(__FILE__) . "/../scripts";
    $save_faces_command = "cd " . escapeshellarg($scripts_path) . " && php saveFacesSqusinagem.php " . intval($buttonrow_key) . " 2>&1";
    $save_faces_result = shell_exec($save_faces_command);
    
    // Verificar novamente se os modelos foram criados
    $query_check_models_after = "SELECT COUNT(*) as count FROM \"Modelos 3D\" WHERE \"Peça\" = " . intval($buttonrow_key);
    $result_check_after = pg_exec($conn, $query_check_models_after);
    if ($result_check_after) {
        $check_data_after = pg_fetch_assoc($result_check_after);
        $model_exists = intval($check_data_after['count']) > 0;
    }
    
    // Mostrar resultado da geração
    if ($model_exists) {
        echo "<div style='margin: 20px 0; padding: 10px; background-color: #d4edda; border: 1px solid #28a745; border-radius: 5px;'>";
        echo "<p style='margin: 0; color: #155724;'><strong>✓ Visualizações 3D geradas com sucesso!</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='margin: 20px 0; padding: 10px; background-color: #f8d7da; border: 1px solid #dc3545; border-radius: 5px;'>";
        echo "<p style='margin: 0; color: #721c24;'><strong>⚠ Erro ao gerar visualizações 3D</strong></p>";
        echo "</div>";
    }
}

// Exibir o visualizador 3D
echo "<div style='margin: 20px 0; border: 1px solid #ccc; border-radius: 5px;'>";
echo "<h3 style='margin: 10px; color: #333;'>Visualizador 3D da Peça</h3>";

if ($model_exists) {
    // Buscar o primeiro modelo 3D disponível para esta peça
    $query_get_model = "SELECT codigo FROM \"Modelos 3D\" WHERE \"Peça\" = " . intval($buttonrow_key) . " ORDER BY codigo LIMIT 1";
    $result_model = pg_exec($conn, $query_get_model);
    
    if ($result_model && pg_num_rows($result_model) > 0) {
        $model_data = pg_fetch_assoc($result_model);
        $model_codigo = $model_data['codigo'];
        
        echo "<iframe src='../web/objViewer.php?table=Modelos%203D&keyField=codigo&keyValue=" . intval($model_codigo) . "' ";
        echo "width='100%' height='600px' frameborder='0' style='border-radius: 0 0 5px 5px;'>";
        echo "Seu navegador não suporta iframes. ";
        echo "<a href='../web/objViewer.php?table=Modelos%203D&keyField=codigo&keyValue=" . intval($model_codigo) . "' target='_blank'>Clique aqui para abrir o visualizador 3D</a>";
        echo "</iframe>";
        
        // Adicionar links para todas as visualizações disponíveis
        $query_all_models = "SELECT codigo, nome, faces FROM \"Modelos 3D\" WHERE \"Peça\" = " . intval($buttonrow_key) . " ORDER BY codigo";
        $result_all_models = pg_exec($conn, $query_all_models);
        
        if ($result_all_models && pg_num_rows($result_all_models) > 1) {
            echo "<div style='margin: 10px; padding: 10px; background-color: #f9f9f9; border-radius: 3px;'>";
            echo "<strong>Outras visualizações disponíveis:</strong><br><br>";
            $count = 0;
            while ($model_row = pg_fetch_assoc($result_all_models)) {
                $count++;
                echo "<a href='../web/objViewer.php?table=Modelos%203D&keyField=codigo&keyValue=" . intval($model_row['codigo']) . "' target='_blank' ";
                echo "style='display: inline-block; margin: 5px 10px 5px 0; padding: 5px 10px; background-color: #007bff; color: white; text-decoration: none; border-radius: 3px; font-size: 12px;'>";
                echo "Visualização " . $count;
                if (!empty($model_row['faces'])) {
                    echo " (Faces: " . htmlspecialchars(substr($model_row['faces'], 0, 20)) . (strlen($model_row['faces']) > 20 ? '...' : '') . ")";
                }
                echo "</a>";
            }
            echo "</div>";
        }
        
        // Adicionar botão para regenerar visualizações
        echo "<div style='margin: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 3px;'>";
        echo "<a href='?regen_3d=" . intval($buttonrow_key) . "' ";
        echo "style='display: inline-block; padding: 8px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 3px; font-size: 12px;' ";
        echo "onclick='return confirm(\"Deseja regenerar as visualizações 3D? Esta operação pode levar alguns minutos.\");'>";
        echo "🔄 Regenerar Visualizações 3D</a>";
        echo "</div>";
        
    } else {
        echo "<p style='margin: 10px; color: #ff6600;'>Erro ao carregar o modelo 3D.</p>";
    }
} else {
    echo "<p style='margin: 10px; color: #ff6600;'>Não foi possível gerar as visualizações 3D para esta peça. Verifique se o arquivo STEP está válido.</p>";
    if (isset($save_faces_result) && !empty(trim($save_faces_result))) {
        echo "<details style='margin: 10px;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>Ver detalhes do erro</summary>";
        echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 3px; font-size: 11px; margin-top: 10px; white-space: pre-wrap;'>" . htmlspecialchars($save_faces_result) . "</pre>";
        echo "</details>";
    }
    
    // Botão para tentar novamente
    echo "<div style='margin: 10px;'>";
    echo "<a href='?' style='display: inline-block; padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 3px; font-size: 12px;'>";
    echo "🔄 Tentar Novamente</a>";
    echo "</div>";
}

*/

//$query_igieintegracao = "delete from processos_peca where usuario = '" . $igieUser . "' and \"Peça\" = " . intval($buttonrow_key);
//$result = pg_exec($conn, $query_igieintegracao);

//Print para ver o retorno do igie

/*
echo "<pre>";
print_r($cutting_data);
echo "</pre>";
*/


//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup:</B> " . $cutting_data['machine']['set_up_time'] . "min<BR>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup (ferramenta):</B> " . $cutting_data['machine']['set_up_time_tool'] . "min<BR>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo não produtivo (NOP):</B> " . $cutting_data['part']['nop_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Percentual reconhecido:</B> " . $cutting_data['part']['percentage_recognized'] . "%<BR><BR>";

$getFeatureCodesQuery = "select * from features_igie";
$getFeatureCodeResult = pg_exec($conn, $getFeatureCodesQuery);
$featuresFetch = pg_fetch_all($getFeatureCodeResult);
foreach ($featuresFetch as $feature_igie) {
    $features_igie[$feature_igie['nome']] = $feature_igie['codigo'];
}
// echo "<PRE>";
// var_dump($features_igie);
// echo "</PRE>";

$getOperationsQuery = "select * from \"Operações\"";
$getOperationsResult = pg_exec($conn, $getOperationsQuery);
$operationsFetch = pg_fetch_all($getOperationsResult);
foreach ($operationsFetch as $operation) {
    $operations[$operation['nome']] = $operation['codigo'];
}
// echo "<PRE>";
// var_dump($operations);
// echo "</PRE>";

//apenas insere na tabela processos_peca
    
    // Tempo de setup e tempo de esquadro
    $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\", \"Tempo de custo_desbaste (horas)\", \"Observações\") values ('" . $igieUser . "', " . intval($buttonrow_key) . ", 2, ";
    $query_igieintegracao .= "'Setup no NOP e esquadro no custo_desbaste', "; // nome
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']+$cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "'tempo de esquadro e tempo de setup')";
    //echo "<PRE>" . $query_igieintegracao . "</PRE>";
    $result = pg_exec($conn, $query_igieintegracao);
    

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de setup: " . $cutting_data['machine']['set_up_time'] . " min<BR>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de custo_desbaste para esquadro: " . $cutting_data['part']['esquadro'] . " min<BR><BR>";

$query_user_avancado = "select distinct usuario from usuarios_grupos where grupo = 49";
$result_user_avancado = pg_exec($conn, $query_user_avancado);
$usuarios_avancados = pg_fetch_all($result_user_avancado);

//Criar lógica para apenas usuários avançados entrarem nesse loop
If (/*$isdeveloper || */in_array($onde_user, $usuarios_avancados)) {
foreach ($cutting_data['part']['features'] as $feature) {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de custo_desbaste: " . $feature['roughing_time'] . " min<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de custo_acabamento: " . $feature['finishing_time'] . " min<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR><BR>";
    }

    //echo "intval($features_igie[$features['featureType']]) . "<BR>";
    
        // custo_desbaste, custo_acabamento e NOP
        $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo de custo_desbaste (horas)\",  \"Tempo passivo - NOP (horas)\", \"Tempo de custo_acabamento (horas)\", \"Tempo do processo (horas)\", \"Superfícies\", feature, \"Operação\") values ('" . $igieUser . "', " . intval($buttonrow_key) . ", 2, ";
        $query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . "',"; // nome

        $query_igieintegracao .= "((to_char((" . floatval($feature['roughing_time']) . ")::double precision, '99999999999999.999999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= "((to_char((" . floatval($feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= " ((to_char((" . floatval($feature['finishing_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= " ((to_char((" . floatval($feature['roughing_time']+$feature['finishing_time']+$feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= "'" . implode(",", $feature['faceIds'][0]['face_ids']) . "',";
        $query_igieintegracao .= " " . intval($features_igie[$feature['featureType']]) . ", ";
        $query_igieintegracao .= " " . (strpos(strtoupper($feature['featureType']), 'HOLE')?4:intval($operations[trim($feature['operation'])])) . ") ";
        //echo "<PRE>" . $query_igieintegracao . "</PRE>";
        $result = pg_exec($conn, $query_igieintegracao);
    
}


// percentual detectado
$query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Observações\", \"Tempo do processo (horas)\") values ('" . $igieUser . "', " . intval($buttonrow_key) . ", 2, ";
$query_igieintegracao .= "'percentual reconhecido',"; // nome
$query_igieintegracao .= "'" . $cutting_data['part']['percentage_recognized'] . "', ('00:00:00')::interval)";
$result = pg_exec($conn, $query_igieintegracao);


$orcamentistas[] = 'maicon';
$orcamentistas[] = 'yuri';
//$orcamentistas[] = '10056942';

foreach ($orcamentistas as $orcamentista) {

    // tem qur ver se tem registros antes de inserir (nao inserir se tiver registros)

    // Tempo de setup e tempo de esquadro
    $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\", \"Tempo de custo_desbaste (horas)\", \"Observações\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
    $query_igieintegracao .= "'Setup no NOP e esquadro no custo_desbaste', "; // nome
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']+$cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "'tempo de esquadro e tempo de setup')";
    //echo "<PRE>" . $query_igieintegracao . "</PRE>";
    $result = pg_exec($conn, $query_igieintegracao);

    //echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de setup: " . $cutting_data['machine']['set_up_time'] . " min<BR>";
    //echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de custo_desbaste para esquadro: " . $cutting_data['part']['esquadro'] . " min<BR>";

    foreach ($cutting_data['part']['features'] as $feature) {
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de custo_desbaste: " . $feature['roughing_time'] . " min<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de custo_acabamento: " . $feature['finishing_time'] . " min<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR>";

        //echo "intval($features_igie[$features['featureType']]) . "<BR>";


        
        // custo_desbaste, custo_acabamento e NOP
        $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo de custo_desbaste (horas)\",  \"Tempo passivo - NOP (horas)\", \"Tempo de custo_acabamento (horas)\", \"Tempo do processo (horas)\", \"Superfícies\", feature, \"Operação\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
        $query_igieintegracao .= "'" . $feature['featureType']  . " " . $feature['operation'] . "',"; // nome

        $query_igieintegracao .= "((to_char((" . floatval($feature['roughing_time']) . ")::double precision, '99999999999999.999999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= "((to_char((" . floatval($feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= " ((to_char((" . floatval($feature['finishing_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= " ((to_char((" . floatval($feature['roughing_time']+$feature['finishing_time']+$feature['nop_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, "; // tempo
        $query_igieintegracao .= "'" . implode(",", $feature['faceIds'][0]['face_ids']) . "',";
        $query_igieintegracao .= " " . intval($features_igie[$feature['featureType']]) . ", ";
        $query_igieintegracao .= " " . (strpos(strtoupper($feature['featureType']), 'HOLE')?4:intval($operations[trim($feature['operation'])])) . ") ";
        //echo "<PRE>" . $query_igieintegracao . "</PRE>";
        $result = pg_exec($conn, $query_igieintegracao);
        
    }
        
}

?>