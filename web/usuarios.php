<?PHP
  /**
   * Pagina de listagem de ementas [TESTE]
   * $Id: usuarios.php,v 1.44 2019/01/03 02:23:11 filipi Exp $
   */
$headerTitle = "Administra&ccedil;&atilde;o > Equipe > Usu&aacute;rios";
$useSessions = 1;
$ehXML = 0;

$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";

require("class.phpmailer.php");
require_once('class.html2text.inc'); 

//$_debug = 2;
unset($clearGroups);
$clearGroups[] = 41;  //Administradores
checkClearence($conn, $clearGroups);

echo "<BR>\n";
if ($_debug) {
  echo "<B>Conection handle=" . $conn . "</B><BR>\n";
 }
$query  = "SELECT codigo FROM albuns WHERE \"Álbum\" = 'Perfil'";
$result = pg_query($conn, $query);
$album  = pg_fetch_row($result);
$album = intval(trim($album[0]));
if(!$album){
  $query = "INSERT INTO albuns(\"Álbum\") VALUES('Perfil')";
  $result = pg_query($conn, $query);
  if($result){
    $query  = "SELECT codigo FROM albuns WHERE \"Álbum\" = 'Perfil'";
    $result = pg_query($conn, $query);
    $album  = pg_fetch_row($result);
    $album = intval(trim($album[0]));
  } else {
    warning("Erro ao inserir álbum Perfil! ".pg_last_error($conn));
  }
 }

if ($_POST['envia'] == 'Inserir' || $_POST['salvar']) {
  if (htmlentities(trim($_POST['senha'])) != htmlentities(trim($_POST['confirma']))) {
    $_POST['buttonrow'][$_POST['login_novo']] = "Detalhes...";
    $mensagem = "Senhas n&atilde;o conferem.<BR><BR>\n";
    $mensagem .= "As informa&ccedil;&otilde;es ser&atilde;o atualizadas<BR>\n";
    $mensagem .= "por&eacute;m a senha ser&aacute; mantida a mesma.<BR>\n";
    warning($mensagem);
  }
 }

if ($_POST['envia'] == 'Inserir') {
  if (!htmlentities(trim($_POST['senha'])) || !htmlentities(trim($_POST['confirma']))) {
    $_POST['buttonrow'][$_POST['login_novo']] = "Detalhes...";
    //$mensagem = "Senha n&atilde;o informada.<BR><BR>\n";
    $mensagem = "\n";
    $_POST['senha'] = randomPassword();
    $mensagem .= "Senha setada automaticamente para <B><TT>" . $_POST['senha'] . "</TT></B>.<BR>\n";
    warning($mensagem);
  }
}

if ($_debug > 1) {
  echo "<PRE>";
  var_dump($_POST['buttonrow']);
  echo "</PRE>";
}

if (isset($_POST['genero'])) {
  $_SESSION['genero'] = $_POST['genero'];
}


############################################--Versão antiga--############################################################
/*
 *
 * if ($_FILES['userfile']['name']){
 *   if ($_debug){
 *     echo "<B>VARDUMP(FILES)</B>:<BR>\n<PRE>\n";
 *     var_dump($_FILES);
 *     echo "</PRE>\n";
 *   }
 *
 * //$code = round(time() / 10 * rand(1,10));
 * //$filename = "oct" . $code;
 * if ($_debug) echo "\n<BR><B>MD5:</B>\n";
 *
 * $md5 = md5_file($_FILES['userfile']['tmp_name']); ////////////////Assinatura MD5
 * if ($_debug) echo $md5 . "<BR>\n";
 * //if (!(move_uploaded_file($_FILES['userfile']['tmp_name'], $path_to_temp_dir . $filename . ".dat")))
 * $type = explode("/",  $_FILES['userfile']['type']); ///////////////////Pega tipo do arquivo [0] => tipo [1] =>extensão
 * //print_r($type);
 * if ($type[0]=="image"){
 *
 *   $serverImageFile = "../session_files/image_" . $md5 . "." . $type[1];
 *   if (!(move_uploaded_file($_FILES['userfile']['tmp_name'], $serverImageFile))){
 *     echo "<DIV class=\"schedulled\">";
 *     echo "Erro ao carregar arquivo.</DIV";
 *   }
 */
  #########################################################################################################################
      
  ###############################################--Inserir imagem no banco de dados--######################################
  /**
   * Insere a imagem de perfil do usuário no banco de dados
   * Cria thumbnail e versão reduzida
   * @author: Gustavo Leal;
   *  
   */

if ($_FILES['userfile']['name']) {              
  $type = explode("/", $_FILES['userfile']['type']);                    //Pega tipo do arquivo [0] => tipo [1] =>extensão
  //echo "<PRE>"; var_dump($_FILES); echo "</PRE>";
  if ($type[0] == "image") {
    $file_name = $_FILES['userfile']['name'];
    $fileArray['name'] = $_FILES['userfile']['name'];
    $fileArray['type'] = $_FILES['userfile']['type'];
    /**
     * Se não existir o diretório simulation para a sessão do usuário, ele é criado.
     * Se não existir o diretório para arquivos temporários, ele é criado.
     * @author: Filipi Viana e Gustavo Leal;
     */
    if ($type[1] == 'jpeg' || $type[1] == 'jpg' || $type[1] == 'tiff'){  //EXIF da imagem
      $exif = exif_read_data($_FILES['userfile']['tmp_name']);
      $exif= json_encode($exif);
      if(!$exif){     // Erro ao ler informações EXIF.
	//echo json_encode('exif');
      }
    } else {
      $exif = 0;
    }
    
    if($_debug > 1){
      $return['exif'] = array($exif);
    }

    $simulation = "simulation" . $PHPSESSID;
    $uploadDir = "/upload/";
    if (!file_exists(($useSessions ? "../session_files/" : "") . $simulation)) {
      mkdir("./" . ($useSessions ? "../session_files/" : "") . $simulation, 0777);
    }
    if (!file_exists(($useSessions ? "../session_files/" : "") . $simulation . $uploadDir)) {
      mkdir("./" . ($useSessions ? "../session_files/" : "") . $simulation . $uploadDir, 0777);
    }

    /**
     * Gera string com o diretório para armazenar as imagens temporariamente;
     * Move o arquivo temporário para o diretório gerado;
     */
    $abspath = realpath(dirname(__FILE__));
    $uploadPath = $abspath . "/../session_files/" . $simulation . $uploadDir;
    $file_tmp = $_FILES['userfile']['tmp_name']; //// Nome do arquivo temporário salvo no computador para processamento;
    if ($_debug){
      echo "<PRE>\n";
      var_dump($_FILES);
      echo "</PRE>\n";
    }
    $file = $uploadPath . $file_name;
    if ($_debug) echo "\$file: " . $file . "<BR>\n";
    if (move_uploaded_file($file_tmp, $file)){
      if ($_debug) echo "SUCESSO ... movido<BR>\n";
    }
    
    if ($_debug) { ///////////////////////////////DEBUG
      echo "<PRE>";
      print_r($file);
      echo "</PRE>";
    }
            
    if ($_debug) ///////////////////////////////// DEBUG
      echo "\n<BR><B>MD5:</B>\n";

    $md5 = md5_file($file); ////////////////////// Assinatura MD5 da imagem original
    if ($_debug) ///////////////////////////////// DEBUG
      echo $md5 . "<BR>\n";
        
    $filedata = file_get_contents($file); //////// Lê o conteúdo da imagem principal
    $fileArray['contents'] = $filedata;
    $filedata = formsEncodeFile($fileArray);
    if ($_debug) ///////////////////////////////// DEBUG
      echo "<pre>" . $filedata . "</pre>";

    $query  = "SELECT codigo FROM albuns WHERE \"Álbum\" = 'Perfil'";
    $result = pg_query($conn, $query);
    $album  = pg_fetch_row($result);
    $album = intval(trim($album[0]));
    if(!$album){
      $query = "INSERT INTO albuns(\"Álbum\", sistema) VALUES('Perfil', 't')";
      $result = pg_query($conn, $query);
      if($result){
	$query  = "SELECT codigo FROM albuns WHERE \"Álbum\" = 'Perfil'";
	$result = pg_query($conn, $query);
	$album  = pg_fetch_row($result);
	$album = intval(trim($album[0]));
      } else {
	warning("Erro ao inserir álbum Perfil! ".pg_last_error($conn));
      }
    }
        
        
    if ($_debug) ///////////////////////////////// DEBUG
      echo "album = ".$album;

    
    $mainImage = insertFile($file_name, $filedata, $type, $exif, $md5, $album, null, null, null, $_SESSION['matricula']); // Insere a imagem principal no BD
    if(!$mainImage){
      $query = "SELECT codigo FROM arquivos WHERE \"Assinatura MD5\" = '".$md5."'";
      $query.= "AND \"Conteúdo\" IS NOT NULL;";
      $result = pg_query($conn, $query);
      $codigoArq = pg_fetch_row($result);
      //$codigoArq = $codigoArq[0]; 
    }else{
      //echo $mainImage;
      echo $mainImage.": ".uploadErrors($mainImage);
      exit(0);
    }

    /**
     * Gera o nome para versão reduzida da imagem;
     * Gera versão reduzida da imagem e a salva no diretório temporário;
     *      $smallProp => proporção para a imagem reduzida;
     * 
     * Insere imagem reduzida no BD.
     * Exclui a imagem do diretório.
     */
    
    $smallProp = 512;
    if ($_debug) echo "Reduzindo arquivo....";
    $smallName = $md5 . "_pequeno";
    $smallFile = resizeImage($file, $type, $smallProp, $smallName, $uploadPath);
    if ($smallFile){
      if ($_debug) echo "[  OK  ]\n";
    }
    else{
      if ($_debug) echo "[ FAIL ]\n";      
    }
    $md5Small = md5_file($smallFile); //////////////// Assinatura MD5
    $filedata = file_get_contents($smallFile);
    $fileArray['contents'] = $filedata;
    $filedata = formsEncodeFile($fileArray);
    $smallImage = insertFile($file_name, $filedata, $type, null, $md5Small, $album, $codigoArq[0], 1, null, $_SESSION['matricula']);
    unlink($smallFile);
    if($smallImage){
      echo $smallImage.": ".uploadErrors($smallImage);
      exit(0);
    }
    /**
     * Gera o nome para a thumbnail da imagem;
     * Gera thumbnail da imagem e a salva no diretório temporário;
     *      $thumbProp => proporção para o thumbnail;
     * 
     * Insere a thumbnail no BD.
     * Exclui a thumbnail do diretório.
     */
      
    $thumbProp = 80;
    if ($_debug) echo "Gerando thumbnail....";
    $thumbName = $md5 . "_thumb";
    $thumbFile = resizeImage($file, $type, $thumbProp, $thumbName, $uploadPath);
    if ($thumbFile){
      if ($_debug) echo "[  OK  ]\n";
    }
    else{
      if ($_debug) echo "[ FAIL ]\n";      
    }
    $md5Thumb = md5_file($thumbFile); //////////////// Assinatura MD5
    $filedata = file_get_contents($thumbFile);
    //$filedata = bin2hex($filedata); ////////////// Converte conteúdo para hexadecimal
    $fileArray['contents'] = $filedata;
    $filedata = formsEncodeFile($fileArray);
    $thumbImage = insertFile($file_name, $filedata, $type, null, $md5Thumb, $album, $codigoArq[0], null, 1, $_SESSION['matricula']);
    unlink($thumbFile);
    if($thumbImage){
      echo $thumbImage.": ".uploadErrors($thumbImage);
      echo json_encode($return, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);;
      exit(0);
    }
    unlink($file);
    unlink($smallName);
    unlink($thumbName);
        
  } else {
    echo "<DIV class=\"schedulled\">";
    echo "Arquivo carregado n&atilde;o foi reconhecido como imagem v&aacute;lida!</DIV>";
  }
 }
/*************************************************************************************************************************/

if ( ($_POST['envia'] == 'Inserir' && (
    // previne que cadastre matricula em branco.
      intval(pg_escape_string(
			      (trim($_POST['login_novo']) ? trim($_POST['login_novo']) : trim($_POST['login'])
											      )//ternario
			       )//pg_escape_string
	     )//intval
      ||
      strlen(pg_escape_string(
			      (trim($_POST['login_novo']) ? trim($_POST['login_novo']) : trim($_POST['login'])
											      )//ternario
			       )//pg_escape_string
	     )//intval

	   ) //||
      )// &&
   ) {
  $query = "INSERT INTO usuarios (login, username, nome, senha, ativo";

  if (isset($_POST['genero'])) {
    $query .= ", masculino";
  }

  if (isset($_POST['email'])) {
    $query .= ", email";
  }

  if (isset($_POST['ramal'])) {
    $query .= ", ramal";
  }

  if (isset($_POST['celular'])) {
    $query .= ", celular";
  }
  if (isset($_POST['endereco'])) {
    $query .= ", endereco";
  }
  if (isset($_POST['cep'])) {
    $query .= ", cep";
  }
  if (isset($_POST['cidade'])) {
    $query .= ", cidade";
  }
 
  if (trim($_POST['aniversario'])) {
    $query .= ", aniversario";
  }

  if (isset($_POST['horas'])) {
    $query .= ", horas";
  }
  //echo $imagemPerfil . "<BR>\n";
  if ($codigoArq[0] ) {
    $query .= ", foto";
  }

  $query .= ")\n  VALUES (";
  $query .= "  '" . pg_escape_string( (trim($_POST['login_novo']) ? trim($_POST['login_novo']) : trim($_POST['login'])) )    . "',";
  $query .= "  '" . pg_escape_string($_POST['username']) . "',";
  $query .= "  '" . pg_escape_string($_POST['nome']) . "',";
  $query .= "  '" . crypt(trim($_POST['senha']), '$1$') . "'";
  if (!$_POST['ativo']) {
    $query .= "  , 'false'";
  } else {
    $query .= "  , 'true'";
  }

  if (isset($_POST['genero'])) {
    if ($_POST['genero'] == "masculino") {
      $query .= ", 'true'";
    } else
      if ($_POST['genero'] == "feminino") {
	$query .= ", 'false'";
      }

  }

  if (isset($_POST['email'])) {
    $query .= ", '" . pg_escape_string($_POST['email']);
    //if (pg_escape_string(trim($_POST['login_novo'])) != pg_escape_string(trim($_POST['login'])))
    //	$query .= "(novo)";
    $query .= "'";
  }

  if (isset($_POST['ramal'])) {
    $query .= ", '" . pg_escape_string($_POST['ramal']) . "'";
  }
  if (isset($_POST['celular'])) {
    $query .= ", '" . pg_escape_string($_POST['celular']) . "'";
  }

  if (isset($_POST['endereco'])) {
    $query .= ", '" . pg_escape_string($_POST['endereco']) . "'";
  }

  if (isset($_POST['cep'])) {
    $query .= ", '" . pg_escape_string($_POST['cep']) . "'";
  }

  if (isset($_POST['cidade'])) {
    $query .= ", '" . pg_escape_string($_POST['cidade']) . "'";
  }


  if (trim($_POST['aniversario'])) {
    $diaMes = explode("/", $_POST['aniversario']);
    $query .= "  , '2009-" . $diaMes[1] . "-" . $diaMes[0] . "'\n";
  }
  if (isset($_POST['horas'])) {
    $query .= ", " . intval($_POST['horas']);
  }

  if ($imagemPerfil || $codigoArq[0]) {
    $query .= "  , '" . ($codigoArq[0] ? $codigoArq[0] : $imagemPerfil) . "'\n";
  }

  $query .= ")\n";
  $result = pg_exec($conn, $query);
  if ($_debug) {
    echo "<PRE>" . $query . "</PRE><BR>\n";
  }
  //echo "<PRE>" . $query . "</PRE><BR>\n";

  if (!$result) {
    $erro++;
    warning("Erro inserindo usuario!<BR>\n<PRE>" . pg_last_error($conn) . "</PRE>");
  }
}
else
  if ($_POST['salvar']) {
    // Se for alterado o login (matricula) do usuario:
    // localizar todas as ocorrencias desse usuario em todas as tabelas que tem chave estrangeira
    // apontando para a tabela usuarios
    //SELECT conname, pg_catalog.pg_get_constraintdef(r.oid, true) as condef FROM pg_catalog.pg_constraint r WHERE r.confrelid = 'usuarios'::regclass;
    // Inserir o novo usuario
    // dar o update em todas as tableas filhas
    // apagar o usuario anterior
    
    
    $imagemPerfil = codigoImagem($_POST['login'], $album, $md5);
    //$imagemPerfil = getUserProfilePictureCode($_POST['login']);
    
    $query = "UPDATE usuarios\n";
    $query .= "  SET nome = '" . pg_escape_string($_POST['nome']) . "',\n";
    $query .= "  username = " . ((isset($_POST['username']) && trim($_POST['username'])) ? ("'" . pg_escape_string($_POST['username']) . "'") : "NULL");

    if (!$_POST['ativo']) {
      $query .= ",\n      ativo = false";
    } else {
      $query .= ",\n      ativo = true";
  }

  if (!$_POST['troca']) {
    $query .= ",\n      first = false";
  } else {
    $query .= ",\n      first = true";
  }

  if (isset($_POST['genero'])) {
    if ($_POST['genero'] == "masculino") {
      $query .= ",\n      masculino = true";
    }
    else
      if ($_POST['genero'] == "feminino") {
	$query .= ",\n      masculino = false";
      }

  }

  if (intval(trim($_POST['cargo'])))
      $query .= ",\n      cargo = " . intval(trim($_POST['cargo']));

  
  if (trim($_POST['aniversario'])) {
    $diaMes = explode("/", $_POST['aniversario']);
    $query .= ",\n      aniversario = '2009-" . intval($diaMes[1]) . "-" . intval($diaMes[0]) . "'";
  }
  if (isset($_POST['email'])) {
    $query .= ",\n      email = '" . pg_escape_string($_POST['email']) . "'";
  }

  if (isset($_POST['ramal'])) {
    $query .= ",\n      ramal = '" . pg_escape_string($_POST['ramal']) . "'";
  }

  if (isset($_POST['celular'])) {
    $query .= ",\n      celular = '" . pg_escape_string($_POST['celular']) . "'";
  }

  if (isset($_POST['endereco'])) {
    $query .= ",\n      endereco = '" . pg_escape_string($_POST['endereco']) . "'";
  }

  if (isset($_POST['cep'])) {
    $query .= ",\n      cep = '" . pg_escape_string($_POST['cep']) . "'";
  }

  if (isset($_POST['cidade'])) {
    $query .= ",\n      cidade = '" . pg_escape_string($_POST['cidade']) . "'";
    }
  
  //if (!$_POST['buttonrow'] && !trim($_POST['senha']))
  if (trim($_POST['senha']) && trim($_POST['confirma'])) {
    $query .= ",\n      senha = '" . crypt(trim(pg_escape_string($_POST['senha'])), '$1$') . "'";
  }

  if ($_POST['horas']) {
    $query .= ",\n      horas = " . intval($_POST['horas']);
  }
  if ($codigoArq[0] ) {
    $query .= ",\n      avatar = NULL";    
    $query .= ",\n      foto = " . $codigoArq[0];
  }
  /*if ($serverImageFile)
   $query .= ",\n      avatar = '" . $serverImageFile . "'"; */
  $query .= "\n  WHERE login = '" . pg_escape_string($_POST['login']) . "'";
  $result = pg_exec($conn, $query);
  if ($_debug) {
    echo "<PRE>" . $query . "</PRE><BR>\n";
  }
  //echo "<PRE>" . $query . "</PRE><BR>\n";

 }
if ($_POST['envia'] == 'Inserir' || $_POST['salvar']) {
  $result = pg_exec($conn, "BEGIN");
  $query = "DELETE FROM usuarios_grupos WHERE ";
  $query .= "usuario = '" . pg_escape_string($_POST['login']) . "'";
  $result = pg_exec($conn, $query);
  $erro = 0;
  if (!$result) {
    $result = pg_exec($conn, "ROLLBACK");
    $erro++;
    warning("Erro deletando grupos do usuario!<BR>\nOpera&ccedil;&atilde;o desfeita!");
    //break;
  }
  if ($_debug) {
    echo "<B>\$POST['grupos']</B><BR>\n";
    echo "<PRE>\n";
    var_dump($_POST['grupos']);
    //var_dump($_POST['grupos']);
    echo "</PRE>\n";
  }
  
  //while (list($key, $val) = each($_POST['grupos'])) {
  foreach($_POST['grupos'] as $key => $val){
    $query = "INSERT INTO usuarios_grupos(usuario, grupo) VALUES (";
    $query .= "'" . ($_POST['login_novo'] ? pg_escape_string($_POST['login_novo']) : pg_escape_string($_POST['login'])) . "', ";
    $query .= pg_escape_string($_POST['grupos'][$key]) . ")";
    $result = pg_exec($conn, $query);
    if ($_debug) {
    }
    //      echo "<PRE>" . $query . "</PRE>\n";

    if (!$result) {
      echo "<BR><PRE>ERRO:" . pg_last_error($conn) . "</PRE>";
      $result = pg_exec($conn, "ROLLBACK");
      $erro++;
      warning("Erro atualizando grupos do usuario!<BR>\nOpera&ccedil;&atilde;o desfeita!");  
      break;
    }
  }
  if ($erro) {
    $result = pg_exec($conn, "ROLLBACK");
    warning("Erro atualizando grupos do usuario!<BR>\nOpera&ccedil;&atilde;o desfeita!");
  } else {
    $result = pg_exec($conn, "COMMIT");
    echo "      <BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
    echo "      <DIV CLASS=\"message\">Usu&aacute;rio";
    echo "      salvo com sucesso!</DIV><BR>\n";
    /////////////////////////////////////////////////////////////////////////////////  ENVIA E-MAIL

    $saudacao = "Saudações";  // Trocar por prezado ou prezada conforme genero preenchido.
    if ($_POST['genero'] == "masculino") $saudacao = "Prezado";
    if ($_POST['genero'] == "feminido") $saudacao = "Prezada";

    $emailArray['content']  = "<P>" . $saudacao . ", " . pg_escape_string($conn, $_POST['nome']) . "</P>";
    $emailArray['content'] .= "<P>Um usuário foi criado para que você possa acessar o ";
    $emailArray['content'] .= $SYSTEM_NAME . "</P>";
    $emailArray['content'] .= "<P>Para acessar, utilize o endereço";
    $emailArray['content'] .= " <a href=\"" . $URL . "\">" . $URL ."</a>";
    $emailArray['content'] .= "</P>";

    $emailArray['content'] .= "<P>Para efetuar o login você pode utilizar o seu login:";
    $emailArray['content'] .= " <TT>" . pg_escape_string( (trim($_POST['login_novo']) ? trim($_POST['login_novo']) : trim($_POST['login'])) ) . "</TT>  ou o seu e-mail: ";
    $emailArray['content'] .= "<TT>" . pg_escape_string($_POST['email']) . "</TT>";
    $emailArray['content'] .= "</P>";

    $emailArray['content'] .= "<P>";
    $emailArray['content'] .= $mensagem;
    $emailArray['content'] .= "</P>";

    $emailArray['content'] .= $htmlFooter;

 $emailArray['recipients'][0]['nome'] = "Filipi Vianna";
 $emailArray['recipients'][0]['email'] = "filipi@pucrs.br";
 $emailArray['recipients'][1]['nome'] = pg_escape_string($conn, trim($_POST['nome']));
 $emailArray['recipients'][1]['email'] = pg_escape_string($conn, trim($_POST['email']));
 //echo "<B>Envia: " . $_POST['envia'] . "<BR><PRE>";
 //var_dump($emailArray['recipients']);
 //echo "</PRE>";
 if (is_array($emailArray['recipients']) && $emailArray['recipients'] != NULL
     && $_POST['envia'] == "Inserir"){

    $emailArray['content']  = str_replace("\\", "", $emailArray['content']);
    $h2t = new \Html2Text\Html2Text($emailArray['content']);
    $emailArray['text'] = $h2t->get_text(); 
    $idxRecipients = count($emailArray['recipients']);
    foreach($emailArray['recipients'] as $recipient){
      $mail = new PHPMailer();
      $mail->From     = $system_mail_from;
      $mail->Sender   = $system_mail_from;
      $mail->FromName = $system_mail_from_name;
      $mail->HostName = $system_mail_host;
      $mail->Host     = $system_mail_host;
			$mail->Port     = $system_mail_port;
      $mail->Mailer   = $system_mail_mailer;
			if (isset($system_mail_user) && $system_mail_user && isset($system_mail_password)){
			  $mail->SMTPAuth = true;
			  $mail->Username  = $system_mail_user;
			  $mail->Password  = $system_mail_password;
			}
      $mail->CharSet = $encoding;
      $assunto  = "[INDUSMART] Usuário cadastrado no ONDE";
      $mail->Subject  = stripAccents($assunto);
      $mail->Body    = str_replace(" src=\"logo.png\"", " src=\"cid:1272542224.13304.4.camel@brainstorm\"", $emailArray['content']);
      $mail->AltBody = $emailArray['text'];
      $mail->AddAddress($recipient['email'], $recipient['nome']);
      if (file_exists("logo.png"))
        $mail->AddEmbeddedImage('logo.png', '1272542224.13304.4.camel@brainstorm', 'logo.png');
      else
        $mail->AddEmbeddedImage('/var/www/scripts/logo.png', '1272542224.13304.4.camel@brainstorm', 'logo.png');
      $mail->do_debug = 2;
      if(!$mail->Send()){
        echo "ERRO - Erro ao enviar mensagem para " . $recipient['nome'] . "(" . $recipient['email'] . ")\n";
			  echo "ERRO: <PRE>" . print_r($mail, true) . "</PRE>";
			}
      else{
	echo "      <DIV CLASS=\"message\">E-mail enviado";
	echo "      com sucesso para " . $recipient['nome'] . "&lt;" . $recipient['email'] . "&gt;</DIV><BR>\n";
      }

    // Clear all addresses and attachments for next loop
    $mail->ClearAddresses();
    $mail->ClearAttachments();
  }
 }
    ///////////////////////////////////////////////////////////////////////////////////////////////
  }


  if (!$erro && strpos($_SESSION['grupos'], 'root') && $_POST['salvar'] && ($_POST['login'] != $_POST['login_novo']) && ($_POST['envia'] != 'Inserir')){
    $erro = 0;
    pg_exec($conn, "BEGIN");

    $query_duplica_usuario_com_nome_novo  = "insert into usuarios ";
    $query_duplica_usuario_com_nome_novo .= " (login,senha,nome,email,last_login,first,ativo,aniversario,tema,masculino,senha2,senha3,avatar,ramal,horas,endereco,cep,cidade,celular,quando,foto)\n";
    $query_duplica_usuario_com_nome_novo .= "    SELECT '" . trim(pg_escape_string($conn, $_POST['login_novo'])) . "'";
    $query_duplica_usuario_com_nome_novo .= ",senha,nome,email,last_login,first,ativo,aniversario,tema,masculino,senha2,senha3,avatar,ramal,horas,endereco,cep,cidade,celular,quando,foto from usuarios\n";
    $query_duplica_usuario_com_nome_novo .= "where login = '" . trim(pg_escape_string($conn, $_POST['login'])) . "'\n";
    $resultado = pg_exec($conn, $query_duplica_usuario_com_nome_novo);
    if ($_debug) echo "<PRE>" . $query_duplica_usuario_com_nome_novo . "</PRE>";    
    if (!$resultado){
      $erro++;
      $erro_array[] = pg_last_error($conn);
      $result = pg_exec($conn, "ROLLBACK");
      warning("Erro renomeando usuário!<BR>\nOpera&ccedil;&atilde;o desfeita!<BR>\n(" . pg_last_error($conn) . ") [" . $erro . "]");
      include "page_footer.inc";
      exit(1);
    }
    $query_tabelas_a_alterar  = "select R.TABLE_NAME, R.COLUMN_NAME\n";
    $query_tabelas_a_alterar .= "from INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE u\n";
    $query_tabelas_a_alterar .= "inner join INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS FK\n";
    $query_tabelas_a_alterar .= "    on U.CONSTRAINT_CATALOG = FK.UNIQUE_CONSTRAINT_CATALOG\n";
    $query_tabelas_a_alterar .= "    and U.CONSTRAINT_SCHEMA = FK.UNIQUE_CONSTRAINT_SCHEMA\n";
    $query_tabelas_a_alterar .= "    and U.CONSTRAINT_NAME = FK.UNIQUE_CONSTRAINT_NAME\n";
    $query_tabelas_a_alterar .= "inner join INFORMATION_SCHEMA.KEY_COLUMN_USAGE R\n";
    $query_tabelas_a_alterar .= "    ON R.CONSTRAINT_CATALOG = FK.CONSTRAINT_CATALOG\n";
    $query_tabelas_a_alterar .= "    AND R.CONSTRAINT_SCHEMA = FK.CONSTRAINT_SCHEMA\n";
    $query_tabelas_a_alterar .= "    AND R.CONSTRAINT_NAME = FK.CONSTRAINT_NAME\n";
    $query_tabelas_a_alterar .= "WHERE U.COLUMN_NAME = 'login'\n";
    $query_tabelas_a_alterar .= "  AND U.TABLE_NAME = 'usuarios'\n";
    show_query($query_tabelas_a_alterar, $conn);
    $resultado = pg_exec($conn, $query_tabelas_a_alterar);
    $tabelas_a_alterar = pg_fetch_all($resultado);
    echo "<PRE>"; var_dump($tabelas_a_alterar); echo "</PRE>";
    foreach ($tabelas_a_alterar as $tb){
      $query_update_rename  = "UPDATE \"" . $tb['table_name'] . "\" SET ";
      $query_update_rename .= "\"" . $tb['column_name'] . "\" = '" . trim(pg_escape_string($conn, $_POST['login_novo'])) . "' where ";
      $query_update_rename .= "\"" . $tb['column_name'] . "\" = '" . trim(pg_escape_string($conn, $_POST['login'])) . "'";
      if ($_debug) echo $query_update_rename . "<BR>\n";
      $resultado = pg_exec($conn, $query_update_rename);
      if (!$resultado){
        $erro++;
        $erro_array[] = pg_last_error($conn);
        $result = pg_exec($conn, "ROLLBACK");
        warning("Erro renomeando usuário!<BR>\nOpera&ccedil;&atilde;o desfeita!<BR>\n(" . pg_last_error($conn) . ") [" . $erro . "]");
        break;
      }    
    }
    if (!$erro){
      $query_delete_old_name = "delete from usuarios where login = '" . trim(pg_escape_string($conn, $_POST['login'])) . "'";
      $resultado = pg_exec($conn, $query_delete_old_name);
      if (!$resultado){
        $erro++;
        $erro_array[] = pg_last_error($conn);
        $result = pg_exec($conn, "ROLLBACK");
        warning("Erro renomeando usuário!<BR>\nOpera&ccedil;&atilde;o desfeita!<BR>\n(" . pg_last_error($conn) . ") [" . $erro . "]");
        $result = pg_exec($conn, "ROLLBACK");
      }
      if (!$erro){
        $result = pg_exec($conn, "COMMIT");
      }
    }    
  }
}

############################################--Versão antiga--############################################################

if ($_debug) {
  echo "<BR><B>BOTAO</B><BR>\n<PRE>";
  echo substr(trim($_POST['botao']), 0, 7) . "|\n";
  echo substr(trim($_POST['botao']), 0, 4) . "|\n";
  echo $_POST['envia'] . "|\n";

  echo "</PRE><BR>\n";
  echo "<BR><B>DeleteCheckBox</B><BR>\n<PRE>";
  echo var_dump($_POST['DeleteCheckBox']);
  echo "</PRE><BR>\n";
 }

//while (list($key, $val) = each($_POST['grupos'])) {
foreach ($POST['grupos'] as $key => $val){
  if ($_debug) {
    echo "<BR><B>Grupos</B><BR>\n<PRE>";
    echo $_POST['grupos'][$key];
    echo "</PRE><BR>\n";
  }

 }

if (isset($_POST['DeleteCheckBox']) &&
    substr(trim($_POST['botao']), 0, 7) == "Remover") {
  $delete = $_POST['DeleteCheckBox'];
  pg_Exec($conn, "BEGIN"); // Inicia a transacao
  if ($_debug) {
    echo "<PRE>\n";
  }

  //while (list($key, $val) = each($delete)) {
  foreach($delete as $key => $val){
    if ($_debug) {
      echo $key . " = " . $delete[$key] . "\n";
    }

    if ($delete[$key]) {
      $query_liga = "DELETE FROM usuarios\n";
      $query_liga .= "  WHERE login = '" . $key . "'\n";
    }
    if ($_debug) {
      echo $query_liga;
    }

    $result = pg_Exec($conn, $query_liga);
    if (!$result) {
      echo "FALHOU, executando roll back\n";
      pg_Exec($conn, "ROLLBACK");
      break;
    }
  }
  $result_delete = pg_Exec($conn, "COMMIT");
  if ($_debug) {
    echo "</PRE>\n";
  }
  if ($result_delete){
    echo "<div class=\"message\">Usuários excluídos com sucesso.</DIV>";
  }
  else{
    echo "<div class=\"busy\">Erro excluindo usuários.</DIV>";    
  }  
 }

if ($_POST['buttonrow'] ||
    substr(trim($_POST['botao']), 0, 4) == "Novo") {

  //while (list($key, $val) = each($_POST['buttonrow'])) {
  foreach($_POST['buttonrow'] as $key => $val){
    if ($_debug) {
      echo "<B>login: " . $key;
      echo " - " . $_POST['buttonrow'][$key] . "</B><BR>\n";
    }
    $query = "select * from usuarios where login='" . $key . "'";
    if ($_debug) {
      echo "<PRE>" . $query . "</PRE><BR>\n";
    }

    $result = pg_exec($conn, $query);
    $disciplina = pg_fetch_array($result, 0);
    if ($_debug) {
      echo "<B>login: " . $disciplina['login'] . "</B><BR>\n";
    }

    if ($_debug) {
      echo "<B>nome: " . $disciplina['nome'] . "</B><BR>\n";
    }

  }
  /*######################################################VERSÃO ANTERIOR####################################################
  /* if ($disciplina['avatar'] && !$serverImageFile) {
   $serverImageFile = $disciplina['avatar'];
   }

   if ($serverImageFile) {
   $size = getimagesize($serverImageFile);
   //     echo "<PRE>\n";
   //     var_dump($size);
   //     echo "</PRE>\n";
   echo "<DIV class=\"codigoDeDemanda\">\n";
   echo "<IMG src=\"" . $serverImageFile . "\" ALT=\"ideia_image\" ";
   if ($size[0] > 255) {
   echo " width=255 ";
   }

   echo "ALIGN=\"bottom\" BORDER=\"0\">\n";
   echo "</DIV>\n";
   } */

  ##########################################################################################################################*/
  ################################################--Buscar imagem no banco de dados--######################################
  /** 
   *
   *  Seleciona as imagens do banco de dados para apresentar na imagem de perfil;
   *  
   *  @author: Gustavo Leal
   */

  //$imagemPerfil = codigoImagem($disciplina['login'], $album);
  $imagemPerfil = getUserProfilePictureCode($disciplina['login']);
  
  if($imagemPerfil){
 
    echo "<DIV class=\"codigoDeDemanda\">\n";
    echo "<img src=\"loadFiles.php?id=".$imagemPerfil."\" height = 220 \n";
    if ($size[0] > 255) {
      echo " width=220 ";
    }
    echo "ALIGN=\"bottom\" BORDER=\"0\">\n";
    echo "</DIV>\n"; 
  }
    
  /*************************************************************************************************************************/

  //echo "  <FORM ACTION=\"" . basename( $_SERVER['PHP_SELF']) . "\" ";
  //echo "  <FORM autocomplete=\"off\" ACTION=\"\" ";
  echo "  <FORM ACTION=\"\" ";
  echo "METHOD=\"POST\" ENCTYPE=\"multipart/form-data\">\n";
  if ($_POST['login']) $disciplina['login'] = pg_escape_string($conn, trim($_POST['login']));
  if (trim($_POST['login_novo'])) $disciplina['login'] = pg_escape_string($conn, trim($_POST['login_novo']));
  echo "    <INPUT TYPE=\"HIDDEN\" NAME=\"salvar\" VALUE=1\>\n";
  //echo "    <INPUT TYPE=\"HIDDEN\" NAME=\"autocomplete\" VALUE=\"off\" \>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  if (substr(trim($_POST['botao']), 0, 4) == "Novo" || strpos($_SESSION['grupos'], 'root') ) {
    echo "    <B>login: </B><BR>\n";
    echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
    echo "    <INPUT TYPE=\"";
    echo "TEXT";
    echo "\" NAME=\"login";
    if (strpos($_SESSION['grupos'], 'root') ) {   
      echo "_novo";
    }    
    echo "\" SIZE=\"8\" MAXLENGTH=\"8\" CLASS=\"TEXT\"";
    //echo "  onKeypress = 'if(event.keyCode < 48 || event.keyCode > 57)\n";
    echo "  event.returnValue = false;'\n";
    echo "VALUE=\"" . $disciplina['login'] . "\"><BR>\n";
    if (strpos($_SESSION['grupos'], 'root') ) {   
      echo "    <INPUT TYPE=\"";
      echo "HIDDEN";
      echo "\" NAME=\"login\" ";
      echo "VALUE=\"" . $disciplina['login'] . "\">\n";
    }
  } else {
    echo "    <B>login: </B>" . $disciplina['login'] . "<BR><BR>\n";
    echo "    <INPUT TYPE=\"";
    echo "HIDDEN";
    echo "\" NAME=\"login\" ";
    echo "VALUE=\"" . $disciplina['login'] . "\">\n";
  }
  echo "<BR>";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Nome:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"nome\" SIZE=\"40\"\n";
  echo "    MAXLENGTH=\"100\"";
  echo "     VALUE=\"" . $disciplina['nome'] . "\"><BR><BR>\n";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Usuário de rede:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"username\"  autocomplete=\"off\" SIZE=\"40\"\n";
  echo "    MAXLENGTH=\"100\"";
  echo "     VALUE=\"" . $disciplina['username'] . "\"><BR><BR>\n";

  
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>G&ecirc;nero:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"RADIO\" NAME=\"genero\" ";
  if ($disciplina['masculino'] == "f") {
    echo " CHECKED ";
  }

  echo "VALUE=\"feminino\">feminino<BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"RADIO\" NAME=\"genero\" ";
  if ($disciplina['masculino'] == "t") {
    echo " CHECKED ";
  }

  echo "VALUE=\"masculino\">masculino<BR><BR>\n";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>e-mail:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"email\" SIZE=\"40\"\n";
  echo "    MAXLENGTH=\"200\"";
  echo "     VALUE=\"" . $disciplina['email'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Ramal:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"ramal\" SIZE=\"40\"\n";
  echo "    MAXLENGTH=\"200\"";
  echo "     VALUE=\"" . $disciplina['ramal'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Celular:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"celular\" SIZE=\"20\"\n";
  echo "    MAXLENGTH=\"20\"";
  echo "     VALUE=\"" . $disciplina['celular'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Endereço:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"endereco\" SIZE=\"50\"\n";
  echo "    MAXLENGTH=\"200\"";
  echo "     VALUE=\"" . $disciplina['endereco'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>CEP:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"cep\" SIZE=\"10\"\n";
  echo "    MAXLENGTH=\"9\"";
  echo "     VALUE=\"" . $disciplina['cep'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Cidade:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"cidade\" SIZE=\"50\"\n";
  echo "    MAXLENGTH=\"200\"";
  echo "     VALUE=\"" . $disciplina['cidade'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Cargo ou função</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  dbcombo("cargos", "codigo", "nome", $conn, "cargo",
          10, $disciplina['cargo'], $onChange, NULL, NULL, NULL, NULL);
  //          10, "Selecione um tipo", $onChange, NULL, NULL, NULL);
    echo "<BR><BR>\n";

  
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Grupos:</B><BR>\n";
  dbCheckList("grupos", "codigo", "nome", $conn, "grupos",
	      "grouphaveuser(codigo, '" . $disciplina['login'] . "')", NULL);
  echo "<BR><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Senha:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"PASSWORD\"  autocomplete=\"off\" CLASS=\"PASSWORD\" NAME=\"senha\" SIZE=\"40\"\n";
  echo "    MAXLENGTH=\"200\"";
  $disciplina['senha'] = "";
  echo "     VALUE=\"" . $disciplina['senha'] . "\"><BR><BR>";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Confirma senha:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"PASSWORD\" CLASS=\"PASSWORD\" NAME=\"confirma\" SIZE=\"40\"\n";
  echo "    MAXLENGTH=\"200\"";
  echo "     VALUE=\"" . $disciplina['confirma'] . "\"><BR><BR>";
  if (!substr(trim($_POST['botao']), 0, 4) == "Novo") {
    echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
    echo "    <B>Solicitar troca de senha no login:</B>\n";
    echo "    <INPUT TYPE=\"checkbox\" name=\"troca\" ";
    if ($disciplina['first'] == 't') {
      echo "CHECKED";
    }

    echo "><BR><BR>\n";
  }

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>N&uacute;mero de horas semanais:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <INPUT TYPE=\"TEXT\" CLASS=\"TEXT\" NAME=\"horas\" SIZE=\"6\"\n";
  echo "    MAXLENGTH=\"10\"";
  echo "    onKeypress=\"if(event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;\"";
  echo "     VALUE=\"" . $disciplina['horas'] . "\"><BR><BR>";

  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Data de anivers&aacute;rio:</B><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  if ($disciplina['aniversario']) {
    $aniversario = date("d/m", strtotime($disciplina['aniversario']));
  } else {
    $aniversario = "";
  }

  ?>
    <input type="text" name="aniversario" id="f_date_b" value="<?php echo $aniversario; ?>"><button type="reset" id="f_trigger_b">...</button>
       <script type="text/javascript">
       Calendar.setup({
	 inputField     :    "f_date_b",      // id of the input field
	     ifFormat       :    "%d/%m",       // format of the input field
	     showsTime      :    false,            // will display a time selector
	     button         :    "f_trigger_b",   // trigger for the calendar (button ID)
	     singleClick    :    false,           // double-click mode
	     step           :    1                // show all years in drop-down boxes (instead of every other year as default)
	     });
  </script><BR><BR>

      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <input type="hidden" name="MAX_FILE_SIZE" value="100000000000000">
     <B>Upload de imagens: </B><BR>
     &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <INPUT NAME="userfile" TYPE="file">
     <BR>

     <?PHP
     echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  echo "    <B>Este usu&aacute;rio est&aacute; ativo?:</B>\n";
  echo "    <INPUT TYPE=\"checkbox\" name=\"ativo\" ";
  if ($disciplina['ativo'] == 't' || substr(trim($_POST['botao']), 0, 4) == "Novo") {
    echo "CHECKED";
  }

  echo "><BR><BR>\n";
  echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  if ((substr(trim($_POST['botao']), 0, 4) == "Novo") || $_POST['envia'] == "Inserir") {
    echo "    <INPUT TYPE=\"SUBMIT\" CLASS=\"SUBMIT\" VALUE=\"Inserir\" NAME=\"envia\">\n";
  } else {
    echo "    <INPUT TYPE=\"SUBMIT\" CLASS=\"SUBMIT\" VALUE=\"Salvar\" NAME=\"envia\">\n";
  }
  echo "  </FORM>\n";
 }

$query = "SELECT login, nome, email, (select nome from cargos where codigo = cargo) as cargo, ativo, (case when moodle_id is not null then 't' else 'f' end)::boolean as \"<center><img src=images/moodle.gif></center>\"";
$query .= "  FROM usuarios\n";

echo "    <CENTER>\n";
if (!(isset($_GET['orderby'])) &&
    (isset($_POST['orderby']))) {
  $_GET['orderby'] = $_POST['orderby'];
 }

$references[0] = "";
$references[1] = "";
$references[2] = "";
$references[3] = "";
$boolean[4][0] = "<center><img src=\"images/busy.png\"></center>";
$boolean[4][1] = "<center><img src=\"images/accept.png\"></center>";

$boolean[5][0] = "<center></center>";
$boolean[5][1] = "<center><img src=\"images/moodle.gif\"></center>";

$formata[1] = 30;
$formata[3] = 30;

$form['field'] = "login";
//$form['action']=basename( $_SERVER['PHP_SELF']);
$form['action'] = "";
$form['name'] = "detalhes";
if (strpos($_SESSION['grupos'], 'root')) {
  $form['delete'] = 1;
 }

// echo "GRUPOS: " . $_SESSION['grupos'] . "<BR>\n";
// echo "matricula: " . $_SESSION['matricula'] . "<BR>\n";

//echo "    <FORM ACTION=\""  . $_SERVER['PHP_SELF'] . "\"";
echo "    <FORM ACTION=\"\"";
echo " METHOD=\"POST\">\n";
if (!(isset($_GET['orderby'])) && (isset($_POST['orderby']))) {
  $_GET['orderby'] = $_POST['orderby'];
 }

echo "      <INPUT TYPE=\"SUBMIT\" CLASS=\"SUBMIT\" VALUE=\"Novo Usu&aacute;rio...\" NAME=\"botao\">\n";
if (strpos($_SESSION['grupos'], 'root')) {
  echo "      <INPUT TYPE=\"SUBMIT\" CLASS=\"SUBMIT\" VALUE=\"Remover usu&aacute;rios marcados\"\n";
  echo "       onClick=\"return confirmSubmit()\" NAME=\"botao\">\n";
 }

if (!(isset($_GET['orderby'])) && (isset($_POST['orderby']))) {
  $_GET['orderby'] = $_POST['orderby'];
 }

echo "      <BR>\n      <BR>\n";

if (isset($_GET['orderby'])) {
  show_query($query, $conn,
	     pg_escape_string($conn, $_GET['orderby']),
	     (intval($_GET['desc']) || (intval($_POST['desc'])) != null ),
	     $formata, $references, $form, $boolean,
	     "");
 } else {
  show_query($query, $conn, "nome", "", $formata, $references,
	     $form, $boolean, "");
 }

echo "      <INPUT TYPE=\"SUBMIT\" CLASS=\"SUBMIT\" VALUE=\"Novo Usu&aacute;rio...\" NAME=\"botao\">\n";
if (strpos($_SESSION['grupos'], 'root')) {
  echo "      <INPUT TYPE=\"SUBMIT\" CLASS=\"SUBMIT\" VALUE=\"Remover usu&aacute;rios marcados\"\n";
  echo "       onClick=\"return confirmSubmit()\" NAME=\"botao\">\n";
 }
echo "    </FORM>\n";
echo "    </CENTER>\n";

echo "<BR>\n";
include "page_footer.inc";
?>
