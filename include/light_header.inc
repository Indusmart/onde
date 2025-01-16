<?PHP
  include_once("escapeConfVars.inc");
  include("conf.inc");

  $banco =  trim($banco);
  $banco_cadastro =  trim($banco_cadastro);
  $usuario_banco =  trim($usuario_banco);
  $senha_banco =  trim($senha_banco);

  $connectionString  = "dbname =   " . $banco . "\n";
  $connectionString .= "user =     " . $usuario_banco . "\n";
  $connectionString .= "password = " . $senha_banco;
  $conn = pg_connect($connectionString);

  escapeConfVars($conn);
  include_once("lib.inc");
  if($useSessions)
  include "start_sessao.inc";

  if (!$conn){
    if (!$ehXML){
    Warning("Banco de dados temporariamente indispon&iacute;vel.<BR>
             Tente novamente em alguns minutos.");
    }
    else{
      echo " [/!\\]  Banco de dados temporariamente indisponivel.\n";
      echo "        Tente novamente em alguns minutos.\n";
    }
    include "page_footer.inc";
    exit(1);
  }
  pg_set_client_encoding($conn, $encoding);
?>