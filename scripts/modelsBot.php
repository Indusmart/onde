#!/usr/bin/php
<?PHP
// Coloca em modo de debug apenas o laço de demandas
$localDebug['demandas']['debug'] = 0;
// Nao executa a atualizacao de demandas (pula este laço)
$localDebug['demandas']['jump'] = false;
//$localDebug['demandas']['jump'] = true;

declare(ticks = 1);
$useSessions = 0; $ehXML = 1;
$oldInclude = ini_get("include_path");
$printCount = 0;

ini_set("include_path", "..:../include:../../include:include:" . $oldInclude);
include "page_header.inc";
include "plot.inc";
require("class.phpmailer.php");
require_once('class.html2text.inc'); 
require('class.iCalReader.php');

ini_set ( "error_reporting", "E_ALL" );

// Fazer o error reporting aumentar de acordo com o nivel de debug...
//$_debug = 2;
if ($_debug){
  echo "[" . date("Y-m-d H:i:s", time());
  echo "] PostgreSQL Conection handle = \"" . $conn . "\" ";
  echo "(Debug mode ON)\n";
}

// signal handler function
function sig_handler($signo){
  global $useSessions;
  global $ehXML;
  global $conn;

  switch ($signo) {
  case SIGTERM:
    // handle shutdown tasks
    include "page_footer.inc";
    $timestamp = date("YYYY", time());
    echo "[" . date("Y-m-d H:i:s", time());
    echo "] Robo de demandas terminado!\n";
    echo "=================================================\n\n";
    exit;
    break;

  case SIGHUP:
    $timestamp = date("YYYY", time());
    echo "[" . date("Y-m-d H:i:s", time());
    echo "] Encerrando conexao com o banco de dados!\n";
    // handle restart tasks
    include "page_footer.inc";
    $timestamp = date("YYYY", time());
    echo "[" . date("Y-m-d H:i:s", time());
    echo "] Robo de demandas terminado!\n";
    include "page_header.inc";
    $timestamp = date("YYYY", time());
    echo "[" . date("Y-m-d H:i:s", time());
    echo "] Robo de demandas iniciado!\n";
    break;

  case SIGUSR1:
    echo "Caught SIGUSR1...\n";
    break;
  default:
    // handle all other signals
    echo "[" . date("Y-m-d H:i:s", time());
    echo "] --- \n";
  }

}

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

/////////////////////////////////////////////////////////////////////////////////////////////////
include("botconf.inc");

//echo "passei\n\n";
//var_dump($botControl);
if (strtoupper($botControl['demandasBot'])=='STOP') exit(0);
//echo "passei\n\n";
/////////////////////////////////////////////////////////////////////////////////////////////////

$timestamp = date("YYYY", time());
echo "[" . date("Y-m-d H:i:s", time());
echo "] Robo de demandas iniciado!\n";


if (!$localDebug['demandas']['jump']){
  $demandas_path = "demandas";

  if (!file_exists($demandas_path))
    if (!mkdir("./" . $demandas_path, 0777)){
      echo "Can't create temp dir\n";
      exit(1);
    }
  $query = "SELECT codigogtit FROM solicitacoes ORDER BY codigogtit DESC";
  $result = pg_exec ($conn, $query);
  if (pg_num_rows($result))
  $demandasNovas = pg_fetch_all ($result);
  foreach($demandasNovas as $demandaNova)
    $demandasGrandeIdeia[] = $demandaNova['codigogtit'];
 }
while(1){
  //if ($_debug>1) echo "\n\n\n";
  include("botconf.inc");
  if (isset($_botdebug))
    if ($_botdebug) var_dump($botControl);
  if ($botControl['demandasBot']){ // Se tem um valor no controle do bot
    switch (strtoupper($botControl['demandasBot'])){
      case 'RESTART':
        /* Salva as configuracoes */
        $arquivo_de_configuracao = fopen("../include/botconf.inc", "w");
        if (!$arquivo_de_configuracao){
	  printTimeStamp(NULL);
          echo "Impossível abrir arquivo de configuração ";
          echo "para escrita!\n                      Verifique as permissões e o caminho.";
          exit(1);
        }
        $confStr  = "<?PHP\n";
        foreach ($botControl as $key => $value){
          if ($key=="demandasBot") $value = "";
          $confStr .= "\$botControl['" . $key . "'] = \"" . $value . "\";\n";
        }
        $confStr  .= "?>\n";
        fputs($arquivo_de_configuracao, $confStr);
        if (fclose($arquivo_de_configuracao)){
	  printTimeStamp(NULL);
          echo "Configurações salvas com sucesso\n";
  	include("botconf.inc");
        }
        else{
	  printTimeStamp(NULL);
          echo "Erro gravando configurações do bot!";
	}
      case 'STOP':
        printTimeStamp(NULL);	
        echo "Parando pelo botconf.inc\n";
        exit(0);
      break;
    }
  }

  if (!$localDebug['demandas']['jump']){
    //echo "ANTES DO HANDSHAKE\n";  
    if (!timeOutCheck($URL_sistemaIdeia)){
      //echo"ANTES DO HANDSHAKE, sem timeout\n";
      $erro  = sistemaIdeiaHandShake($demandas_path);
      //echo "SAIU DO HANDSHAKE\n";  
      if ($erro['error']){
        botConfSave("stop");
        //echoMessage($erro['message']);
        exit(1);
      }
    }
  }


  ////////////////////////////////////////////////////////////////////// Demandas no Sistema Ideia
  if ($localDebug['demandas']['debug']){
    $localDebug['demandas']['lastState'] = $_debug;
    $_debug = $localDebug['demandas']['debug'];
  }

  if (!$localDebug['demandas']['jump']){
    // NOTA: Salvar no banco notificacoes sobre erros de login
    $timeOut = timeOutCheck($URL_sistemaIdeia);
    if (!$timeOut){
      $sistemaIdeiaOffline = 0;

      if ($_debug>1) echo "Baixando lista de usuarios\n";
      baixaUsuarios($demandas_path);
      if ($_debug>1) echo "Atualizando usuarios\n";
      atualizaUsuarios($conn, $demandas_path);

      baixaMicroscopia($demandas_path);

      if ($_debug>1) echo "Baixando servicos de microscopia e caracterizacoes\n";
      $combos = carregaCombos($demandas_path, NULL);
      atualizaServicos($combos['servicos'], $conn);
      $tipoServicos = carregaTipoServicos($conn);

      //var_dump($tipoServicos);
    
      if ($_debug>1) echo "Baixando lista de equipamentos compartilhados\n";
      //carregaEquipamentos($demandas_path, NULL);
      //exit(1);

      if ($_debug>1) echo "Verificando demandas...\n";
      $demandas = carregaDemandas($demandas_path, false, false);
      if ($demandas)
	//while (list($key, $demanda) = each($demandas)){
	foreach($demandas as $key => $demanda){
	  if (!in_array($key, $demandasGrandeIdeia)){
	    $demandasGrandeIdeia[] = $key;
	    printTimeStamp(NULL);
	    echo "Detectada nova demanda! (" . trim($demanda['codigogtit']) . ")\n";
	    baixaDetalhe($demandas_path, $demanda, false);
	    $demanda['situacao'] = 3; // Situacao nao definida
	    $incluida = atualizaDemanda($conn, $demandas_path, $demanda);
	    if (isset($incluida['codigo'])) {	  
	      printTimeStamp(NULL);
	      echo "Incluída com sucesso (f-main.php?demanda=" . trim($incluida['codigo']) . ")! Agora manda e-mail\n";
	      //exit(1);
	  
	      $query  = "SELECT p.nome AS pesquisador_nome, t.nome AS tipodesolicitacao, \n";
	      $query .= "       t.apelido AS tipo_apelido, u.nome as nome_unidade, s.* \n";

	      $query .= ",'UBEA '||  \n";
	      $query .= "		 substring(substring(to_char(to_number(s.patrimonio, '000000000'), '000000000') from 0 for length(to_char(to_number(s.patrimonio, '000000000'), '000000000'))-3) from length(substring(to_char(to_number(s.patrimonio, '000000000'), '000000000') from 0 for length(to_char(to_number(s.patrimonio, '000000000'), '000000000'))-3))-2)||'.'||  \n";
	      $query .= "		 substring(substring(to_char(to_number(s.patrimonio, '000000000'), '000000000') from 0 for length(to_char(to_number(s.patrimonio, '000000000'), '000000000'))) from length(substring(to_char(to_number(s.patrimonio, '000000000'), '000000000') from 0 for length(to_char(to_number(s.patrimonio, '000000000'), '000000000'))))-2)||'-'||  \n";
	      $query .= "		 substring(to_char(to_number(s.patrimonio, '000000000'), '000000000') from length(to_char(to_number(s.patrimonio, '000000000'), '000000000')))||'.00' as ubea  \n";

	      $query .= "  FROM solicitacoes AS s, pesquisadores AS p, tiposdesolicitacoes AS t, unidades AS u\n";
	      $query .= "  WHERE s.codigo = " . intval($incluida['codigo']);
	      $query .= "  AND s.tipo=t.codigo AND s.pesquisador=p.codigo AND s.unidade = u.apelido";

              //echo $query . "\n";

	      $result = pg_exec ($conn, $query);
	      $detalhes = pg_fetch_assoc ($result, 0);

	      //Seleciona equipe (Verifica se ativo e se tem e-mail)
	      // manda mensagem
        
	      // Remove responsaveis da solicitacao para fins de teste.
	      //$query = "DELETE from responsaveis WHERE solicitacao = " . $incluida['codigo']; 
	      //$result = pg_Exec($conn, $query); // Executa a consulta 

	      $query =  "SELECT u.nome, u.email\n";
	      $query .= "  FROM usuarios as u, responsaveis as r\n";
	      $query .= "  WHERE u.ativo = 't' AND r.tecnico=u.login AND r.solicitacao=" . $incluida['codigo'];    
	      //echo $query . "\n";
	      $result = pg_Exec($conn, $query); // Executa a consulta 
	      $linhas=0;
	      // Verifica se demanda tem responsaveis associados
	      //exit();
	      if (pg_num_rows($result)){
		//Monta mensagem
		$html  = "<P>\n";
		$html .= "  Olá,<BR>\n";
		$html .= "  Est&aacute; dispon&iacute;vel para voc&ecirc; uma nova demanda\n";
		$html .= "  de " . $detalhes['tipodesolicitacao'];
                if ($detalhes['ubea'])
		  $html .= " do equipamento " . $detalhes['ubea'];
                $html .= ",<BR>\n";
		$html .= "  enviada por <I>" . $detalhes['pesquisador_nome'];
		$html .= "</I><BR>da unidade " . $detalhes['nome_unidade'] . " (" . $detalhes['unidade'] . "), solicitando ";
		if ($detalhes['tipo'] == 3 || $detalhes['tipo']==5) 
		  $html .= "<B>" . intval($detalhes['horas']) . " horas</B> de uso do seu laborat&oacute;rio.<BR>\n";
		else{
		  $html .= "<BLOCKQUOTE>\n";
		  $html .= "\"" . $detalhes['nome'] . "\"";
		  $html .= "</BLOCKQUOTE>\n";
		}
		$html .= "  <BR>\n";
		$html .= "  <a HREF=" . $URL . "/f-main.php?demanda=";
		$html .= $incluida['codigo'] . ">Acesse o Sistema Grande IDEIA para maiores detalhes</A>.\n";
		$html .=  $htmlFooter;
		$html = str_replace("\\", "", $html);
		$h2t = new \Html2Text\Html2Text($html);
		$text = $h2t->gettext(); 

		while ($linhas<pg_num_rows($result)){
		  $row = pg_fetch_array ($result, $linhas);
		  $mail = new PHPMailer();
		  $mail->From     = "ideia@pucrs.br";
		  $mail->FromName = "IDEIA – Centro de Apoio ao Desenvolvimento Científico e Tecnológico ";
		  $mail->Host     = "smart.pucrs.br";
		  $mail->Mailer   = "smtp";
		  $mail->CharSet = $encoding;
		  //$mail->ConfirmReadingTo = "filipi@pucrs.br";
		  $assunto  = "[IDEIA-" . $detalhes['tipo_apelido'];
                  //if ($detalhes['ubea'])
		  //  $assunto .= "/" . $detalhes['ubea'];
                  $assunto .= "] Demanda ";
		  $assunto .= $incluida['codigogtit'] . " - " . $incluida['unidade'] . " importada do Sistema IDEIA.";
		  $mail->Subject  = stripAccents($assunto);
		  // Plain text body (for mail clients that cannot read HTML)
		  $mail->Body    = str_replace(" src=\"logo.png\"", " src=\"cid:1272542224.13304.4.camel@brainstorm\"", $html);
		  $mail->AltBody = $text;
		  if ($_debug)
		    $mail->AddAddress("filipi@pucrs.br", "Filipi Vianna");
		  else
		    $mail->AddAddress($row['email'], $row['nome']);
		  if (file_exists("logo.png"))
		    $mail->AddEmbeddedImage('logo.png', '1272542224.13304.4.camel@brainstorm', 'logo.png');
		  else
		    $mail->AddEmbeddedImage('/var/www/scripts/logo.png', '1272542224.13304.4.camel@brainstorm', 'logo.png');

		  printTimeStamp(NULL);
		  
		  if(!$mail->Send())
		    echo "ERRO - Erro ao enviar messagem para " . $row['nome'] . "<" . $row['email'] . ">\n";
		  else 
		    echo $row['nome'] . "<" . $row['email'] . "> OK!\n";
		  //exit(1);
		  $linhas++;
		  // Clear all addresses and attachments for next loop
		  $mail->ClearAddresses();
		  $mail->ClearAttachments();
		}
	      }
	      else{
		printTimeStamp(NULL);	    
		echo "Alerta! Não foram detectados os responsáveis\n";

		// Query para pegar o nome e o email dos usuarios desenvolvedores.
		$query  = "select distinct usuarios.nome, usuarios.email \n";
		$query .= "  from usuarios\n";
		$query .= "  where usuarios.login in (";
		for ($i = 0; $i<count($developer)-1; $i++)
		  $query .= "'" . $developer[$i] . "',";
		$query .= "'" . $developer[count($developer)-1] . "')";

		$result = pg_Exec($conn, $query); // Executa a consulta 
		$linhas=0;
		// Verifica se demanda tem responsáveis associados
		if (pg_num_rows($result)){
		  printTimeStamp(NULL);
		  echo "Enviando e-mail para desenvolvedores:\n";
		  // Manda mensagem avisando desenvolvedores.
		  //Monta mensagem
		  $html  = "<P>\n";
		  $html .= "  <B><FONT COLOR=\"#FF0000\">ATENÇÃO</B></FONT><BR>\n";

		  //$html .= "  \n";

		  $html .= "  Você está recebendo esta mensagem por que seu usuário do \n";
		  $html .= "  Grande IDEIA está listado como desenvolvedor no arquivo de \n";
		  $html .= "  configurações do sistema e foi detectada uma anomalia no \n";
		  $html .= "  funcionamento do mesmo.<BR><BR>\n";

		  $html .= "  <B>Não foram detectados os responsáveis para a demanda.</B><BR><BR>\n";
		  $html .= "  \n";
		  $html .= "  Demanda de " . $detalhes['tipodesolicitacao'] . ",\n";

		  $html .= "  enviada por <I>" . $detalhes['pesquisador_nome'] . "</I><BR>da unidade ";
		  $html .= $detalhes['nome_unidade'] . " (" . $detalhes['unidade'] . "), solicitando ";
		  if ($detalhes['tipo'] == 3 || $detalhes['tipo']==5) 
		    $html .= "<B>" . intval($detalhes['horas']) . " horas</B>.<BR>\n";
		  else{
		    $html .= "<BLOCKQUOTE>\n";
		    $html .= "\"" . $detalhes['nome'] . "\"";
		    $html .= "</BLOCKQUOTE>\n";
		  }
		  $html .= "  <BR>\n";
		  $html .= "  <a HREF=" . $URL . "/f-main.php?demanda=" . $incluida['codigo'];
		  $html .= ">Acesse o Sistema Grande IDEIA para maiores detalhes</A>.\n";
		  $html .=  $htmlFooter;
		  $html = str_replace("\\", "", $html);
		  $h2t = new \Html2Text\Html2Text($html); 
		  $text = $h2t->gettext(); 

		  while ($linhas<pg_num_rows($result)){
		    $row = pg_fetch_array ($result, $linhas);
		    $mail = new PHPMailer();
		    $mail->From     = "ideia@pucrs.br";
		    $mail->FromName = "IDEIA – Centro de Apoio ao Desenvolvimento Científico e Tecnológico ";
		    $mail->Host     = "smart.pucrs.br";
		    $mail->Mailer   = "smtp";
		    $mail->CharSet = $encoding;
		    //$mail->ConfirmReadingTo = "filipi@pucrs.br";
		    $mail->Subject  = "[IDEIA-ALERTA] Responsáveis não detectados para a demanda " . $incluida['codiggtit'] . ".";
		    // Plain text body (for mail clients that cannot read HTML)
		    $mail->Body    = str_replace(" src=\"logo.png\"", " src=\"cid:1272542224.13304.4.camel@brainstorm\"", $html);
		    $mail->AltBody = $text;
		    if ($_debug)
		      $mail->AddAddress("filipi@pucrs.br", "Filipi Vianna");
		    else
		      $mail->AddAddress($row['email'], $row['nome']);
		    if (file_exists("logo.png"))
		      $mail->AddEmbeddedImage('logo.png', '1272542224.13304.4.camel@brainstorm', 'logo.png');
		    else
		      $mail->AddEmbeddedImage('/var/www/scripts/logo.png', '1272542224.13304.4.camel@brainstorm', 'logo.png');
		    printTimeStamp(NULL);
		    if(!$mail->Send())
		      echo "[ERRO] Erro ao enviar messagem para " . $row['nome'] . "<" . $row['email'] . ">\n";
		    else 
		      echo $row['nome'] . "<" . $row['email'] . ">  OK!\n";
		    //exit(1);
		    $linhas++;
		    // Clear all addresses and attachments for next loop
		    $mail->ClearAddresses();
		    $mail->ClearAttachments();
		  }
		}
		else{
		  printTimeStamp(NULL);
		  echo " - Alerta - Não foram detectados os desenvolvedores e/ou seus respectivos e-mails.\n";
		}
	      }	  
	    }
	    // Demanda incluida, email enviado, agora atualiza o grafico no site
	    if (isset($incluida['codigo'])) {
	      if ($_integracaoSiteLigada && $usuario_siteIdeia && $host_siteIdeia){
		printTimeStamp(NULL);	    
		echo "Verificando se servidor destino está on-line...";
		if (!timeOutCheck("http://" . $host_siteIdeia . "/")){

		  //$ano = 2015;
		  $ano = date("Y", time());
		  $orientation = 0;
		  $plotType = 'png';
		  $background = "#F5F5F5";

		  /*
		   ve se arquivo mudou
		   Verifica se o servidor estah UP
		   copia
		   apaga arquivo
		  */

		  $geometry = "295x244";
		  $png = plot($ano, $orientation, $plotType, $background, $geometry);
		  echo " [ OK ]\n";
		  printTimeStamp(NULL);
		  echo "Copiando grafico (miniatura)...\n";
		  echo $filename . "\n";
		  $command = "cp " . $filename . ".png grafico.png";
		  `$command`;
		  $command = "sftp -o KexAlgorithms=+diffie-hellman-group1-sha1 -o HostKeyAlgorithms=+ssh-dss " . $usuario_siteIdeia . "@" . $host_siteIdeia . " < inputs";
		  `$command`;
		  echo "ok\n";

		  $geometry = "800x662";
		  $png = plot($ano, $orientation, $plotType, $background, $geometry);	      
		  echo " [ OK ]\n";
		  printTimeStamp(NULL);
		  echo "Copiando grafico...\n";
		  echo $filename . "\n";
		  $command = "cp " . $filename . ".png grafico_zoom.png";
		  `$command`;
		  $command = "sftp -o KexAlgorithms=+diffie-hellman-group1-sha1 -o HostKeyAlgorithms=+ssh-dss " . $usuario_siteIdeia . "@" . $host_siteIdeia . " < inputs_zoom";
		  `$command`;
		  echo "ok\n";
		}
		else{
		  printTimeStamp(NULL);
		  echo "Servidor destino off-line\n";
		}
		cleanPlot();
		rmdir("simulation/plot");
		rmdir("simulation");
	      }
	      else{
		printTimeStamp(NULL); 
		echo "O gráfico de demandas não foi copiado para o site!\n";
	      }
	    }
	  }      
	}
      if ($_debug) echo ".";
    }
    else{  
      if (($sistemaIdeiaOffline % 100000000)==0  || !$sistemaIdeiaOffline){
	if ($printCount){
	  printTimeStamp(true);
	  echo "Sistema IDEIA offline (" . $timeOut . ") " . ($sistemaIdeiaOffline ? $sistemaIdeiaOffline . " vezes" : "") . "\n";
	}
      }
      $sistemaIdeiaOffline++;
    }
  }
  else{
    if ($_debug){
      printTimeStamp(NULL);
      echo "Pulando verificacao de novas demandas\n";
    }    
  }
  if ($localDebug['demandas']['debug']){
    $_debug = $localDebug['demandas']['lastState'];
  }
  ////////////////////////////////////////////////////////////////////// Demandas no Sistema Ideia
  
}// While(1) (tem que incluir o page_footer, por que pode ter breaks nesse while(1))

include "page_footer.inc";
?>
