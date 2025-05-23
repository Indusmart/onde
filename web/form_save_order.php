<?PHP
//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
///////////////////////////////////////////////////////////////// Tratando POST
/////////////////////////////////////////////// GET passado para links (action)
///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 1; $ehXML = 1;
$headerTitle = "";
include "iniset.php";
include "page_header.inc";
//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////// Finaliza solicitacao
//////////////////////////////////////////////////////////// remove solicitacao
////////////////////////////////////////////////// Carrega solicitacao desejada
////////////////////////////////////////////////////////////// Monta formulario

$formulario = intval($_POST['codigo']);
$query = "select tabela from forms where codigo = " . $formulario;
$result = pg_exec($conn, $query);
if ($result){
  $row = pg_fetch_row($result, 0);
  $tabela = $row[0];
  $dados_log = $tabela . "\n";
}
$comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
$log = `$comandos`;

$innerQuery  = "SELECT a.attname, tt.typname, a.atttypmod, a.atthasdef, a.attnum\n";
$innerQuery .= "  FROM pg_tables as t, pg_class as c, pg_attribute as a, pg_type as tt\n";
$innerQuery .= "  WHERE\n";
$innerQuery .= "    (tableowner <> 'postgres' OR t.tablename in ('pg_class', 'pg_constraint', 'pg_attribute') )AND\n";
$innerQuery .= "    c.relname = t.tablename AND\n";
$innerQuery .= "    attrelid = c.oid AND\n";
$innerQuery .= "    attstattarget<>0 AND \n";
$innerQuery .= "    tt.oid=a.atttypid AND\n";
$innerQuery .= "    t.tablename='" . $tabela . "'\n";
$innerResult = pg_exec($conn, $innerQuery);
if ($innerResult){
  $colunas = pg_fetch_all($innerResult);
  $dados_log = print_r($colunas, true);
  $comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
  $log = `$comandos`;
  foreach($colunas as $indice => $coluna){
    $dados_log = "indice: " . $indice . " nome: " . $coluna['attname']  . " id: onde_div_" . fixField($coluna['attname']) . "\n";
    $comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
    $log = `$comandos`;
    $indices_das_colunas['onde_div_' . fixField($coluna['attname'])] = $indice;
  }
}

$innerTotal = count($indices_das_colunas);
$dados_log = "total de colunas = " . $innerTotal . "\n";
$comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
$log = `$comandos`;


  //Tenta descobrir relacoes N:N
  // 1. Lista todas as tabelas para as quais a chave primaria desta tabela eh chave estrangeira
  $queryNN  = "SELECT tc.table_name, kcu.column_name, ccu.table_name\n";
  $queryNN .= "AS foreign_table_name, ccu.column_name AS foreign_column_name\n";
  $queryNN .= "  FROM information_schema.table_constraints tc\n";
  $queryNN .= "  JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name\n";
  $queryNN .= "  JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name\n";
  $queryNN .= "  WHERE constraint_type = 'FOREIGN KEY'\n";
  $queryNN .= "  AND ccu.table_name='" . $tabela . "'\n";

  $queryNN .= "    AND ccu.column_name = 'codigo'\n";
  $queryNN .= "      AND (SELECT COUNT(*) \n";
  $queryNN .= "         FROM information_schema.columns \n";
  $queryNN .= "  	 WHERE table_name = tc.table_name) < 4\n";

  $queryNN .= "      AND (SELECT COUNT(*) \n";
  $queryNN .= "         FROM information_schema.columns \n";
  $queryNN .= "  	 WHERE table_name = tc.table_name) > 1 \n";

  $resultNN = pg_Exec($conn, $queryNN);
  $NNtables = pg_fetch_all($resultNN);

// $dados_log = print_r($NNtables, true);
// $comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
// $log = `$comandos`;

 // 2. Para cada uma destas tabelas, lista todas as chaves estrangeiras
  foreach($NNtables as $NNkey => $NNtable){
    $campo_chave =  $NNtable['foreign_column_name'];
    $queryNN  = "SELECT\n";
    $queryNN .= "    tc.constraint_name, tc.table_name, kcu.column_name, \n";
    $queryNN .= "    ccu.table_name AS foreign_table_name,\n";
    $queryNN .= "    ccu.column_name AS foreign_column_name \n";
    $queryNN .= "FROM \n";
    $queryNN .= "    information_schema.table_constraints AS tc \n";
    $queryNN .= "    JOIN information_schema.key_column_usage AS kcu\n";
    $queryNN .= "      ON tc.constraint_name = kcu.constraint_name\n";
    $queryNN .= "    JOIN information_schema.constraint_column_usage AS ccu\n";
    $queryNN .= "      ON ccu.constraint_name = tc.constraint_name\n";
    $queryNN .= "WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name='" . $NNtable['table_name'] . "';\n";

    if ($isdeveloper) echo "</CENTER>query: <PRE>" . print_r($queryNN, true) . "</PRE><CENTER>";
    $resultNN = pg_Exec($conn, $queryNN);
    $NNrelations = pg_fetch_all($resultNN);
    $NNtables[$NNkey]['relations'] = $NNrelations;	
	//}
    $dados_log = print_r("tabela:  " . $tabela, true);
    $comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
    $log = `$comandos`;
	
    if ( $NNtables[$NNkey]['relations'][1]['foreign_table_name'] == $tabela){
      $NNbridge[0] = $NNtables[$NNkey]['relations'][1];
      $NNbridge[1] = $NNtables[$NNkey]['relations'][0];
    }
    else{
      $NNbridge[0] = $NNtables[$NNkey]['relations'][0];
      $NNbridge[1] = $NNtables[$NNkey]['relations'][1];
    }
    $NNtables[$NNkey]['relations'][0] = $NNbridge[0];
    $NNtables[$NNkey]['relations'][1] = $NNbridge[1];
    $NNrelations[0]=$NNbridge[0];
    $NNrelations[1]=$NNbridge[1];

    //echo "</CENTER><PRE>"; var_dump($NNtables); echo "</PRE><CENTER>";
    $NNtables[$NNkey]['lines'] = pg_numrows($resultNN);

  $dados_log = print_r($NNtables, true);
  $comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
  $log = `$comandos`;

    if ($_debug > 1){
      echo "<H1>" . $NNtables[$NNkey]['lines'] . "</H1>\n";
      echo "TABELA: " . $NNtable['table_name'] . "<BR>\n";
      show_query($queryNN, $conn);
    }
    $query_tamanho  = "SELECT count(a.attname)\n";
    $query_tamanho .= "  FROM pg_attribute as a, pg_type as t, pg_class as c\n";
    $query_tamanho .= "  WHERE a.attrelid = c.oid AND\n";
    $query_tamanho .= "        a.attstattarget<>0 AND \n";
    $query_tamanho .= "        t.oid=a.atttypid AND\n";
    $query_tamanho .= "        c.relname='" . $NNtable['table_name'] . "'\n";
    if ($_debug > 1) show_query($query_tamanho, $conn);
    $tamanho = pg_exec($conn, $query_tamanho);
    $tamanho_linhas = pg_fetch_row($tamanho, 0);
    //if ($isdeveloper) {
    //  echo "TAMANHO DE " . $NNtable['table_name'] . " = " . $tamanho_linhas[0] . "<BR>\n";
    //}
    if ($_debug > 1) echo "TAMANHO DE " . $NNtable['table_name'] . " = " . $tamanho_linhas[0] . "<BR>\n";
    $NNtables[$NNkey]['size'] = $tamanho_linhas[0];

    //>>>>>>>>>>>>>>>>>>>>>>>      3. A tabela que tiver apenas 2 chaves estrangeiras, eh uma relacao N:N
    // [NN_ORDER] Tentando incluir ordem para campos N:N
    // [NN_ORDER] aqui o lines pode ser >= 2
    
    //           chaves estrangeiras                 campos
   if ( ($NNtables[$NNkey]['lines'] == 2 && $NNtables[$NNkey]['size'] == 2 ) ||
        ($NNtables[$NNkey]['lines'] == 2 && $NNtables[$NNkey]['size'] == 3)
      ){
       $NNtables[$NNkey]['sortable'] = 0;
	   if ($NNtables[$NNkey]['lines'] == 2 && $NNtables[$NNkey]['size'] == 3){
         $NNtables[$NNkey]['sortable'] = 1;
         //echo "Tem que descobrir qual é a coluna que guarda a ordenação. ";
         //echo "E tem que descobrir se ela aceita nulos e se tem um valor default. ";
	   }
	  
   }
 }


  foreach($NNtables as $NNkey => $NNtable){
    $dados_log = "indice: " . $indice . " nome: " . $coluna['attname']  . " id: onde_div_" . fixField($coluna['attname']) . "\n";
    $comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
    $log = `$comandos`;
    $indices_das_colunas['onde_div_' . fixField($NNtable['relations'][1]['foreign_table_name'])] = $innerTotal + $NNkey;
  }


$dados_log = print_r($indices_das_colunas, true);
$comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
$log = `$comandos`;


$dados_log = print_r($_POST, true);
$comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
$log = `$comandos`;

$ordem = "";
foreach($_POST['sorted'] as $sort){
  if (strpos("_" . $sort, "onde_div_"))
    $ordem .= intval($indices_das_colunas[$sort]) . ",";
}

$dados_log = "ordem = " . $ordem . "\n";
$comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
$log = `$comandos`;

$queryUpdate  = "update forms set \"Listar campos na ordem que devem ser exibidos, incluir os N:N\" = '";
$queryUpdate .= pg_escape_string($ordem);
$queryUpdate .= "' where codigo = " . intval($formulario);
pg_exec($conn, $queryUpdate);
$dados_log = "query = " . print_r($queryUpdate, true) . "\n";
$comandos = "echo \"[" . $_SERVER['REMOTE_ADDR'] . "] \n" . $dados_log . "\" >> /tmp/reorder.log";
$log = `$comandos`;


$retorno = "sucesso";
echo json_encode($retorno);
include "page_footer.inc";
?>
