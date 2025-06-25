<?PHP
//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
///////////////////////////////////////////////////////////////// Tratando POST
///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 1; $ehXML = 1;
//$headerTitle = "Página de gabarito";
include "iniset.php";
include "light_header.inc";
//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// GET passado para links (action)
if (isset($_GET['keyIsQuoted']))
  $keyIsQuoted = intval(trim($_GET['keyIsQuoted']));
else
  $keyIsQuoted = false; 
if (isset($_GET['keyField']))
  $keyField    = pg_escape_string($conn, trim($_GET['keyField']));
else
  $keyField    = 'codigo';

$field       = pg_escape_string($conn, trim($_GET['field']));
if ($keyIsQuoted)
  $keyValue  = pg_escape_string($conn, trim($_GET['keyValue']));
else
  $keyValue  = intval(trim($_GET['keyValue']));
$table       = pg_escape_string($conn, trim($_GET['table']));
////////////////////////////////////////////////////////// Finaliza solicitacao
//////////////////////////////////////////////////////////// remove solicitacao
////////////////////////////////////////////////// Carrega solicitacao desejada
////////////////////////////////////////////////////////////// Monta formulario

if (isset($_SERVER['HTTP_REFERER'])) {
  $previous = $_SERVER['HTTP_REFERER'];
  $command = "echo 'PREVIOUS: " . $previous . "' >> " . $path_to_temp_dir . "/loadFiles.log";
  `$command`;

  preg_match('/form=(\d+)\D/', $previous, $matches, PREG_OFFSET_CAPTURE, 3);

  $referer = print_r($matches, true);
  //$command = "echo 'REFERER: " . $referer . "' >> " . $path_to_temp_dir . "/loadFiles.log";
  //`$command`;

  $refererForm = $matches[1][0];

  $command = "echo 'refererForm: " . $refererForm . "' >> " . $path_to_temp_dir . "/loadFiles.log";
  `$command`;

  $command = "echo 'usuario: " . $_SESSION['matricula'] . "' >> " . $path_to_temp_dir . "/loadFiles.log";
  `$command`;

}

$query  = "SELECT encode(\"" . $field . "\", 'base64') AS field  \n";
$query .= "  FROM \"" . $table . "\"\n";
$query .= "  WHERE \"" . $keyField . "\" = " . ($keyIsQuoted ? "'" : '') . $keyValue . ($keyIsQuoted ? "'" : '');

  //echo $query;
  $res = pg_query($conn, $query);
  $raw = pg_fetch_result($res, 'field');
  ////////////////////////////////////////////
  //pg_close($conn);
  $fileArray = formsDecodeFile(base64_decode($raw));
  //var_dump($fileArray);

  $videoData = $fileArray['contents'];
  $size = strlen($videoData);
  $start = 0;
  $end = $size - 1;
  $length = $size;
while (ob_get_level()) ob_end_clean();

//header("HTTP/1.1 206 Partial Content");
//header("Content-Type: video/ogv");
header("Content-Type: ". $fileArray['type']);
//header("Accept-Ranges: bytes");
//header("Content-Range: bytes $start-$end/$size");
//header("Content-Length: $length");

//  header("Accept-Ranges: bytes");
if ($fileArray['type'] == 'application/pdf' || strpos("__" . $fileArray['type'], "image") )
    $disposition = "inline";
  else
    $disposition = "attachment";  
  header('Content-Disposition: ' . $disposition . '; filename="' . $fileArray['name'] . '"');

  if (isset($_SERVER['HTTP_RANGE'])) {
      if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
          $start = $matches[1] !== '' ? intval($matches[1]) : $start;
          $end = $matches[2] !== '' ? intval($matches[2]) : $end;
          $length = $end - $start + 1;

		  header("HTTP/1.1 206 Partial Content");
          header("Content-Range: bytes $start-$end/$size");
          header("Content-Length: $length");
          //header("Access-Control-Allow-Origin: *");
          //header("Access-Control-Expose-Headers: Content-Range, Accept-Ranges");		  
      }
  } else {
      header("Content-Length: $size");
  }
  error_log("Range: $start - $end / $size, length: $length, strlen: " . strlen(substr($videoData, $start, $length)));
  $partialContent = substr($videoData, $start, $length);
  if (strlen($partialContent) < $length) {
      error_log("ERRO: Conteúdo parcial menor que o esperado: ".strlen($partialContent)." < $length");
  }
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Expose-Headers: Content-Range, Accept-Ranges");

  echo $partialContent;
  //echo substr($videoData, $start, $length);
  flush();
  //http_response_code(206);
  //exit();
  //echo $fileArray['contents'];
  //http://localhost/GrandeIdeia/1.14.1/web/formFieldDownload.php?table=solicitacoes_lpe&keyField=codigo&field=Arquivo%20STL&keyValue=282
//http://localhost/GrandeIdeia/1.14.1/web/formFieldDownload.php?table=solicitacoes_lpe&keyField=codigo&field=Envie%20seu%20arquivo%20Aqui&keyValue=282

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
include "page_footer.inc";
?>