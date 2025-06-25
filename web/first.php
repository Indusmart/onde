<?PHP
$useSessions = 1; $ehXML = 0;
$withoutMenu[] = "first.php";
if (isset($_GET['demanda']))  $demanda = intval($_GET['demanda']);
if (isset($_GET['form'])) $form = intval($_GET['form']);
include "iniset.php";
include "start_sessao.inc";
include "page_header.inc";
//$_debug = 1;
//echo "<PRE>"; var_dump($_SESSION); echo "</PRE>";

echo "<DIV CLASS='titulo'>";
    if ($_SESSION['genero']){
      echo "Bem vind";
      if ($_SESSION['genero']=="masculino")
        echo "o";
      else
        if ($_SESSION['genero']=="feminino")
          echo "a";
        else
          echo "o(a)";
    }
    else echo "Saudações";
$nomes = explode(" ", trim($_SESSION['nome']));
$primeiro_nome = ucfirst($nomes[0]);
echo ", <B>" . $primeiro_nome . "</B>!</DIV>\n";
//echo " <B>" . $_SESSION['nome'] . "</B>!</DIV>\n";
?>

<BR>
<BR>
<?PHP 
if ($_POST['SEND'] == 't'){
  $SENHA_OLD = trim($_POST['SENHA_OLD']);
  $SENHA_NEW_CAD = trim($_POST['SENHA_NEW_CAD']);
  $SENHA_NEW_REP = trim($_POST['SENHA_NEW_REP']);
  if($debug == 1) echo $_POST['SENHA_NEW_CAD'] . $_POST['SENHA_NEW_REP'] . $_POST['SENHA_OLD'] .  $_SESSION['matricula'] ;
  $ERR = '';
  //echo "SENHA ANTIGA: " . $_SESSION['senha'] . "<BR><BR>\n";
  //exit(1);
  //if ($SENHA_OLD != $_SESSION['senha']) $ERR = $ERR.'1';
  if ($SENHA_NEW_CAD != $SENHA_NEW_REP) $ERR = $ERR.'2';
  if (strlen($SENHA_NEW_CAD) < 6 || strlen($SENHA_NEW_CAD) > 12) $ERR = $ERR.'3';
  if ($ERR == '')  {
    $query = "UPDATE usuarios SET senha='" . crypt(trim($SENHA_NEW_CAD) ,'$1$') . "',first='f' WHERE login='" . $_SESSION['matricula'] . "'";
    $exec = pg_query($conn,$query);
    if ($exec) {
      $_SESSION['senha_crypt'] = crypt(trim($SENHA_NEW_CAD), '$1$');
      $_SESSION['senha'] = $SENHA_NEW_CAD;
      $_SESSION['first'] = 'f';
	?> 
    <META HTTP-EQUIV='Refresh' CONTENT='
    <?PHP if ($_debug) echo "10"; else echo "1";?>;
    URL=./f-main.php?PHPSESSID=<?PHP echo $PHPSESSID; if ($demanda) echo "&demanda=" . $demanda; else if ($form) echo "&form=" . $form; ?>' TARGET='_self'><?PHP echo "\n";
    }
    else {
      $ERR = $ERR.'4';
      ?> <meta HTTP-EQUIV='Refresh' CONTENT='0; URL=./first.php?PHPSESSID=<?PHP echo $PHPSESSID . "&ERR=" . $ERR ; if ($demanda) echo "&demanda=" . $demanda; else if ($form) echo "&form=" . $form; ?> ' TARGET='_self'> <?PHP
    }
  }
  else {
    ?> <meta HTTP-EQUIV='Refresh' CONTENT='0; URL=./first.php?PHPSESSID=<?PHP echo $PHPSESSID."&ERR=".$ERR ; if ($demanda) echo "&demanda=" . $demanda; else if ($form) echo "&form=" . $form; ?>' TARGET='_self'> <?PHP
  }
  ?>
  <div class=coment>
  <center>
  <b>Processando sua solicitação.</b><br>
  Por favor, aguarde...
  </center>
  </div>
  <?PHP
}
else{
  ?>
  <center>
  <table width=400>
   <tr>
    <th>Troca de senha</th>
   </tr>
   <tr>
    <td>
    <div class=coment>
    <center>
    <b>ATENÇÃO!</b><br>
    <br>
    Por motivo de segurança, sua senha deve ser trocada.<br>
    Nos campos abaixo informe sua senha atual e cadastre uma nova senha.<!-- que contenha no <b>mínimo 6</b> e no <b>máximo 12</b> caracteres--><br>
    <form method=POST action=first.php?PHPSESSID=<?PHP 
    echo $PHPSESSID;
    if ($demanda) echo "&demanda=" . $demanda; 
    ?>>
    <input type='hidden' name='PHPSESSID' value='<?PHP echo $PHPSESSID; ?>'>
    <input type='hidden' name='SEND' value='t'>
    <table style='border-width: 0px;'>
     <tr>
      <td style='border-width: 0px;'> 
      <div class=coment> 
       <b>Senha atual:</b><br>
      <input type='password' name='SENHA_OLD' size='20' <?PHP /*maxlength='12' */?>
      class "ui-input ui-widget ui-corner-all"
      STYLE="height: 28px; width: 300px"       
      value='<?PHP echo $_SESSION['senha']; ?>'><br>
      <b>Nova senha:</b><br>
      <input type='password' name='SENHA_NEW_CAD' size='20' 
      class "ui-input ui-widget ui-corner-all"
      STYLE="height: 28px; width: 300px"       
      <?PHP /*maxlength='12' */?> ><br>
      <b>Repita a nova senha:</b><br>
      <input type='password' name='SENHA_NEW_REP' size='20'
      class "ui-input ui-widget ui-corner-all"
      STYLE="height: 28px; width: 300px"       
       <?PHP /*maxlength='12' */?> ><br>
      <br>
      <center>
      <input type='submit' value='Trocar Senha'
      CLASS="ui-button ui-widget ui-corner-all">
      </center>
      </div>
      </td>
     </tr>
    </table>
    </form>
    </center>
    </div>
    </td>
   </tr>
  </table>
  </center>
  <?PHP
}
if($debug == 1) echo $_SESSION['matricula'] ;
//echo "<PRE>" . print_r($_GET, true) . "</PRE>";
if ($_GET['ERR'] != ''){
  $ERR = $_GET['ERR'];
  ?>
  <div class=coment>
  <font color='#FF0000'>
  <center>
  <b>ATENÇÃO!</b><br>
  <br>
  Ocorreu um ERRO no processamento de sua solicitação.<br>
  Leia atentamente as messagens abaixo:<br>
  <br>
  <?PHP
  if (strstr($ERR,'1')){
    ?>
    A senha antiga não confere com a previamente cadastrada.
    <br>
    <?PHP
  }
  if (strstr($ERR,'2')){
    ?>
    Os campos \"<b>Nova Senha</b>\" e \"<b>Repita a Nova Senha</b>\" devem ser iguais.
    <br>
    <?PHP
  }
  if (strstr($ERR,'3')) {
    ?>
    A senha deve conter no <b>mínimo 6</b> caracteres.
    <br>
    <?PHP
  }
  if (strstr($ERR,'4')){

    ?>
    <font color='#FFCC00'>
    <br>
    Ocorreu um erro no Banco de Dados.
    <br>
    Por favor, tente novamente mais tarde.
    <br>
    <b>Se persistir o problema entre em contato com urgência com o Webmaster.</b>
    </font>
    <br>
    <?PHP
  }
  ?>
  <br>
  Por favor, repita a operação.
  </center>
  </font>
  </div>
  <?PHP
}

include "page_footer.inc";

?>
