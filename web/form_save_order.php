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
