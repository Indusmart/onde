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

$query_status = "SELECT encode(\"modelo_cad_path\", 'base64') as raw from \"squsinagem\" where \"codigo\" = " . intval($buttonrow_key);

$result = pg_exec($conn, $query_status);
if ($result) {
    $peca = pg_fetch_assoc($result, 0);
    $fileArray = formsDecodeFile(base64_decode($peca['raw']));
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

$query_material = "select nome from materiais where codigo = (select (select  \"Material de referência para parâmetros de corte\" from materias_primas_yuri where codigo = \"materia_prima\") from \"squsinagem\" where codigo = " . intval($buttonrow_key) . ")";
$result = pg_exec($conn, $query_material);
$material = pg_fetch_assoc($result, 0);
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Material:</b> " . $material['nome'] . "<BR>\n";


$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./igie_material.sh ./uploaded/" . fixField($fileArray['name']) . " \"" . utf8_decode($material['nome']) ."\"' 2>/dev/null";

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;comando: " . $command . "<BR>\n";

$resultado = `$command`;
$resultado = str_replace('[34m', '<font color=blue>', $resultado);
$resultado = str_replace('[33m', '<font color=yellow>', $resultado);
$resultado = str_replace('[32m', '<font color=green>', $resultado);
$resultado = str_replace('[31m', '<font color=red>', $resultado);
$resultado = str_replace('[0m', '</font>', $resultado);

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

$result = pg_query_params($conn, $query_sq, [intval($buttonrow_key), $onde_user]);

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
    nome,
    custo,
    data_de_vigencia
FROM
    ferramentas_squsinagem
WHERE
    usuario = $1
ORDER BY
    nome,
    data_de_vigencia DESC;
";

$result_ferramentas = pg_query_params($conn, $query_ferramentas, [$onde_user]);

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
            'nome' => $row['nome'],
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

echo "<div id='onde_div_header'>";
echo "<div style='font-size: 2em; font-weight: bold;'>";
//somar o valor de material com o custo total da máquina
echo "<span style='font-size: 10pt;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b>CUSTO UNITÁRIO FINAL:</b> R$" . number_format($custo_final, 2, ',', '.') . "<br><br>";
echo "</div>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Valor total: R$</b> " . number_format($custo_final * $dados_usinagem['quantidade'], 2, ',', '.') . "<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>CUSTO MATERIAL / UN.:</b> R$" . number_format($custo_material, 2, ',', '.') . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Material:</b> " . htmlspecialchars($dados_usinagem['nome_materia_prima']) . "<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Peso do blank:</b> " . number_format($peso/1000, 2, ',', '.') . " kg<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Valor/Kg:</b> R$ " . number_format($dados_usinagem['custo_kg'], 2, ',', '.') . "<br>";
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="https://services.indusmart.com.br/web/forms.php?&toggle[]=M215&form=391" style="font-size: 18px; color: dodgerblue;">Atualizar custos de materiais</a></b><br><br>';

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>CUSTO OPERAÇÃO / UN.: R$" . number_format($custo_total_maquina, 2, ',', '.') . "</b><br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo de desbaste: </b>" . ceil($cutting_data['part']['roughing_time']) . " min<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo de acabamento: </b>" . ceil($cutting_data['part']['total_active_machining_time'] - $cutting_data['part']['roughing_time']) . " min<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo de setup (NOP): </b>" . ceil($cutting_data['part']['total_time'] - $cutting_data['part']['total_active_machining_time']) . " min<br><br>";

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (acabamento fresa):</b> R$" . $ferramentas[0]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (desbaste fresa):</b> R$" . $ferramentas[1]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (acabamento torno):</b> R$" . $ferramentas[3]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (desbaste torno):</b> R$" . $ferramentas[4]['custo'] . "<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (nop): </b> R$" . $ferramentas[2]['custo'] . "<br>";
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="https://services.indusmart.com.br/web/forms.php?&toggle[]=M215&form=392" style="font-size: 18px; color: dodgerblue;">Atualizar custos de ferramentas</a></b><br><br>';
echo "</div>";

if ($isdeveloper) {
    togglePoint(md5($resultado), "Exibir log de comunicação com o IGIE", 1, false, NULL, NULL);
    echo "<PRE>";
    echo $resultado;
    echo "</PRE>";
    echo "</div>";
}

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