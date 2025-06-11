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

//atualizar minha tabela para ter hash?
/*
    // atualiza o sha1 do arquivo original
    echo "nome do arquivo: " . $fileArray['name'] . "<BR>\n";
    echo "Sha1 do PHP : " . sha1($fileArray['contents']) . "<BR>\n";
    $updateSha1  = "UPDATE \"Peças\" set sha1_hash = '" . sha1($fileArray['contents']);
    $updateSha1 .= "' where codigo = " . intval($buttonrow_key) . "";
    $result = pg_exec($conn, $updateSha1);
    $command = "sha1sum '" . $step_filename ."'";
    echo "<PRE>" . $udpateSha1 . "</PRE>";
    echo pg_last_error() . "<BR>\n";
    echo "Sha1 do linux: " . `$command` . "<BR>\n";

    //echo "</PRE>";
    $command  = $path_to_python . " ../occ/fix_z.py ";
    $command .= "'" . $step_filename . "' 2>&1";
    $result = `$command`;
    echo "<PRE>" . print_r($result, true) . "</PRE>";
    $fixed_filename = $step_filename . "_fixed";
    if (file_exists($fixed_filename)) {
        $command = "sha1sum '" . $step_filename . "_fixed'";
        $sha1_z_fixed = `$command`;
        echo "Sha1 do linux Z fixed: " . $sha1_z_fixed . "";
        $fileZfixed = file_get_contents($step_filename . "_fixed", true);
        // atualiza o sha1 do arquivo original
        echo "Sha1 PHP: " . sha1($fileZfixed) . "\n";
        //echo "tamanho = " . strlen($sha1_z_fixed) . "\n";
        $updateSha1  = "UPDATE \"Peças\" set sha1_hash_z_fixed = '" . sha1($fileZfixed);
        $updateSha1 .= "' where codigo = " . $peca['codigo'];
        $result = pg_exec($conn, $updateSha1);
    }
*/


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

$query_sql = "
    SELECT
        sq.nome,
        mp.nome AS nome_materia_prima,
        mp.valor AS custo_kg,
        sq.quantidade,
        sq.data_criacao
    FROM
        squsinagem sq
    LEFT JOIN
        materias_primas_yuri mp ON sq.materia_prima = mp.codigo
    WHERE
        sq.codigo = $1
";

$result = pg_query_params($conn, $query_sql, [intval($buttonrow_key)]);

$dados_usinagem = [];

if ($result) {
    $dados_usinagem = pg_fetch_assoc($result);

} else {
    echo "Erro na execução da query: " . pg_last_error($conn);
}

//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//Inicio do código de exibição

if (!empty($dados_usinagem)) {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Nome do Projeto:</b> " . htmlspecialchars($dados_usinagem['nome']) . "<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Matéria Prima:</b> " . htmlspecialchars($dados_usinagem['nome_materia_prima']) . "<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Valor/Kg:</b> R$ " . number_format($dados_usinagem['custo_kg'], 2, ',', '.') . "<BR>";
    if (isset($dados_usinagem['quantidade'])) {
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Quantidade:</b> " . htmlspecialchars($dados_usinagem['quantidade']) . "<BR>";
    }
    echo "<BR>";
} else {
    echo "Nenhum registro encontrado para o código " . intval($buttonrow_key);
}

//calculo volume bounding box
if (isset($cutting_data['part']['bounding_box']['dimensions'])) {
    $dims = $cutting_data['part']['bounding_box']['dimensions'];
    $x = isset($dims['x']) ? floatval($dims['x']) : 0;
    $y = isset($dims['y']) ? floatval($dims['y']) : 0;
    $z = isset($dims['z']) ? floatval($dims['z']) : 0;
    $volume = ($x * $y * $z)/1000;
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Volume do blank ideal (?):</b> " . number_format($volume, 2, ',', '.') . " cm³<br>";
}


$valor_por_hora = 100; // Defina aqui o valor por hora desejado
$total_active_machining_time = isset($cutting_data['part']['total_active_machining_time']) ? floatval($cutting_data['part']['total_active_machining_time']) : 0;
$total_active_machining_time_horas = $total_active_machining_time / 60; // converte minutos para horas
$custo_total_maquina = $valor_por_hora * $total_active_machining_time_horas;

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo unitário final:</b> R$" . number_format($custo_total_maquina, 2, ',', '.') . "<br>";

if (isset($dados_usinagem['quantidade'])) {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo total:</b> R$" . number_format($custo_total_maquina * $dados_usinagem['quantidade'], 2, ',', '.') . "<br><br>";
}
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="https://dev.services.indusmart.com.br/web/forms.php?&toggle[]=M215&form=383" style="font-size: 18px; color: green;">Atualizar Custos</a></b><br><br><br>';

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>CUSTO MATERIAL / UN.:</b> R$ 10,00<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Volume blank ideal (cm³):</b> 117,29<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Material:</b> Aço inox<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Densidade:</b> 0.00<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Peso do blank (kg):</b> 0.00<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Valor / kg:</b> R$ 12,75<br><br>";


//fazer query para pegar os custos de cada ferramenta

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>CUSTO OPERAÇÃO / UN.:</b> R$ 1,50<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo desbaste (min):</b> 117,29<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo acabamento (min):</b> 520<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Tempo setup (NOP) (min):</b> 15<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (desbaste torno):</b> 150<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (acabamento torno):</b> 170<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (desbaste fresa):</b> 190<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (acabamento fresa):</b> 210<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Custo / hora (nop):</b> 50<br>";
//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>OPERAÇÕES RECONHECIDAS (IA):</b> 95%<br><br>";




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


echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup:</B> " . $cutting_data['machine']['set_up_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo de setup (ferramenta):</B> " . $cutting_data['machine']['set_up_time_tool'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Tempo não produtivo (NOP):</B> " . $cutting_data['part']['nop_time'] . "min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Percentual reconhecido:</B> " . $cutting_data['part']['percentage_recognized'] . "%<BR>";

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
    $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\", \"Tempo de desbaste (horas)\", \"Observações\") values ('" . $igieUser . "', " . intval($buttonrow_key) . ", 2, ";
    $query_igieintegracao .= "'Setup no NOP e esquadro no desbaste', "; // nome
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']+$cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "'tempo de esquadro e tempo de setup')";
    //echo "<PRE>" . $query_igieintegracao . "</PRE>";
    $result = pg_exec($conn, $query_igieintegracao);
    

//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de setup: " . $cutting_data['machine']['set_up_time'] . " min<BR>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste para esquadro: " . $cutting_data['part']['esquadro'] . " min<BR><BR>";

$query_user_avancado = "select distinct usuario from usuarios_grupos where grupo = 49";
$result_user_avancado = pg_exec($conn, $query_user_avancado);
$usuarios_avancados = pg_fetch_all($result_user_avancado);

//Criar lógica para apenas usuários avançados entrarem nesse loop
If (/*$isdeveloper || */in_array($onde_user, $usuarios_avancados)) {
foreach ($cutting_data['part']['features'] as $feature) {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste: " . $feature['roughing_time'] . " min<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de acabamento: " . $feature['finishing_time'] . " min<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR><BR>";
    }

    //echo "intval($features_igie[$features['featureType']]) . "<BR>";
    
        // Desbaste, acabamento e NOP
        $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo de desbaste (horas)\",  \"Tempo passivo - NOP (horas)\", \"Tempo de acabamento (horas)\", \"Tempo do processo (horas)\", \"Superfícies\", feature, \"Operação\") values ('" . $igieUser . "', " . intval($buttonrow_key) . ", 2, ";
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
    $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo passivo - NOP (horas)\", \"Tempo do processo (horas)\", \"Tempo de desbaste (horas)\", \"Observações\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
    $query_igieintegracao .= "'Setup no NOP e esquadro no desbaste', "; // nome
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']+$cutting_data['machine']['set_up_time']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "((to_char((" . floatval($cutting_data['part']['esquadro']) . ")::double precision, '99999999999999.99999999999999999999999999999999999999999999999999'))||' min')::interval, ";
    $query_igieintegracao .= "'tempo de esquadro e tempo de setup')";
    //echo "<PRE>" . $query_igieintegracao . "</PRE>";
    $result = pg_exec($conn, $query_igieintegracao);

    //echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de setup: " . $cutting_data['machine']['set_up_time'] . " min<BR>";
    //echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste para esquadro: " . $cutting_data['part']['esquadro'] . " min<BR>";

    foreach ($cutting_data['part']['features'] as $feature) {
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>" . $feature['featureType'] . "</B><BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Operação: " . $feature['operation'] . "<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de desbaste: " . $feature['roughing_time'] . " min<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo de acabamento: " . $feature['finishing_time'] . " min<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo passivo (NOP): " . $feature['nop_time'] . " min<BR>";
        // echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faces: " . implode(",", $feature['faceIds'][0]['face_ids']) . "<BR>";

        //echo "intval($features_igie[$features['featureType']]) . "<BR>";


        
        // Desbaste, acabamento e NOP
        $query_igieintegracao  = "INSERT INTO processos_peca (usuario, \"Peça\", processo, nome, \"Tempo de desbaste (horas)\",  \"Tempo passivo - NOP (horas)\", \"Tempo de acabamento (horas)\", \"Tempo do processo (horas)\", \"Superfícies\", feature, \"Operação\") values ('" . $orcamentista . "', " . intval($buttonrow_key) . ", 2, ";
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