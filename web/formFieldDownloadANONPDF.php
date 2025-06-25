<?PHP
//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
///////////////////////////////////////////////////////////////// Tratando POST
/////////////////////////////////////////////// GET passado para links (action)
if (isset($_GET['keyIsQuoted']))
  $keyIsQuoted = intval(trim($_GET['keyIsQuoted']));
else
  $keyIsQuoted = false; 
if (isset($_GET['keyField']))
  $keyField    = pg_escape_string(trim($_GET['keyField']));
else
  $keyField    = 'codigo';

$field       = pg_escape_string(trim($_GET['field']));
if ($keyIsQuoted)
  $keyValue  = pg_escape_string(trim($_GET['keyValue']));
else
  $keyValue  = intval(trim($_GET['keyValue']));
$table       = pg_escape_string(trim($_GET['table']));
///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 1; $ehXML = 1;
//$headerTitle = "Página de gabarito";
//echo "passei";
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include('light_header.inc');
//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////// Monta formulario

$query  = "SELECT encode(\"" . $field . "\", 'base64') AS field  \n";
$query .= "  FROM \"" . $table . "\"\n";
$query .= "  WHERE \"" . $keyField . "\" = " . ($keyIsQuoted ? "'" : '') . $keyValue . ($keyIsQuoted ? "'" : '');

  //echo $query;
  $res = pg_query($conn, $query);
  $raw = pg_fetch_result($res, 'field');
  ////////////////////////////////////////////
  //pg_close($conn);
  $fileArray = formsDecodeFile(base64_decode($raw));

$pdfContent = $fileArray['contents'];

// Caminho para o script Python
$pythonScriptPath = "../scripts/anonipipe.py";

// Configura os descritores de arquivo para o processo
$descriptorspec = [
    0 => ["pipe", "r"], // stdin (entrada para o script Python)
    1 => ["pipe", "w"], // stdout (saída do script Python)
    2 => ["pipe", "w"]  // stderr (erros do script Python)
];

// Inicia o processo
$process = proc_open($path_to_python . " ". escapeshellarg($pythonScriptPath), $descriptorspec, $pipes);

if (is_resource($process)) {
    // Envia o conteúdo do PDF para o stdin do script Python
    fwrite($pipes[0], $pdfContent);
    fclose($pipes[0]);

    // Lê a saída do stdout do script Python (PDF modificado)
    $modifiedPdfContent = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Lê a saída do stderr (mensagens de erro, se houver)
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // Fecha o processo
    $returnValue = proc_close($process);

    if ($returnValue === 0) {
        // Sucesso: o PDF modificado está em $modifiedPdfContent
        echo "PDF modificado gerado com sucesso!";
        // Salva o PDF modificado em um arquivo (opcional)
    } else {
        // Erro: exibe a mensagem de erro
        echo "Erro ao processar o PDF: " . $errors;
    }
} else {
    echo "Falha ao iniciar o processo do script Python.";
}

  //var_dump($fileArray);
  header("Content-Type: ". $fileArray['type']);
  header('Content-Disposition: attachment; filename="' . $fileArray['name'] . '"');
  echo $modifiedPdfContent;
  //echo `pwd`;
  //$command = "echo  \"" . $fileArray['contents'] . "\" | " . $path_to_python . " ../scripts/anonipipe.py 2>&1";
  //$command = $path_to_python . " ../scripts/anonipipe.py";
  //echo `pwd`;
  //echo `ls -l ../scripts/anonipipe.py`;
  //$command = "echo oi | /home/indusmart/anaconda3/bin/python ../scripts/anonipipe.py 2>&1";
  //echo $command;
  //$resultado = `$command`;
  //echo $resultado;
  //echo $command;
  //eecho $fileArray['contents'];


/* create table file_form_download_log( */
/* 				    codigo serial primary key, */
/* 				    user_login char(8) not null references usuarios(login), */
/* 				    success boolean not null, */
/* 				    table_name varchar(100) not null, */
/* 				    field varchar(100) not null, */
/* 				    key_field varchar(100) not null, */
/* 				    key_value varchar(100) not null, */
/* 				    key_is_quoted boolean not null, */
/* 				    log_timestamp timestamp not null default current_timestamp, */
/* 				    ip char(15) not null */
/* 				    ) */

$queryLog  = "INSERT INTO file_form_download_log (user_login, success, table_name, field,";
$queryLog .= " key_field, key_value, key_is_quoted, ip) VALUES (";
$queryLog .= "'" . $_SESSION['matricula'] . "', ";
$queryLog .= "'" . ($res ? "t" : "f") . "', ";
$queryLog .= "'" . $table . "', ";
$queryLog .= "'" . $field . "', ";
$queryLog .= "'" . $keyField . "', ";
$queryLog .= "'" . $keyValue . "', ";
$queryLog .= "'" . ($keyIsQuoted ? "t" : "f") . "', ";
$queryLog .= "'" . $_SESSION['ip'] . "') ";

$res = pg_query($conn, $queryLog);

/**
 * verificar se o campo é bytea
 * Esse script só pode ser chamado por um form
 * verificar se a tabela passada é a tabela do form
 * verificar se usuario tem permissao no form
 */

?>

<?PHP
include "page_footer.inc";
?>
