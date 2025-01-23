<?PHP
/**
 * Arquivo de cabecalho padrao do sistema.
 * Abre uma conexao com o banco e inicializa o cabecalho do HTML
 * $Id: page_header.inc,v 1.81 2019/01/18 19:35:29 filipi Exp $
 */

//if (stripos("_" . $_theme, "frameless"))
include("startup.inc"); // Inicia a sessao.
include "start_sessao.inc";

if (isset($_POST['dark'])){
  if ($_POST['dark'] == 'on')
    $_SESSION['dark'] = 't';
}
else
  if (basename($_SERVER['PHP_SELF']) == 'perfil.php' && $_POST['salvar'] )
    $_SESSION['dark'] = 'f';

if ( $_SESSION['dark'] == 't'  && stripos("_" . $_theme, "frameless"))
  $_theme = "framelessTron";

if (basename($_SERVER['PHP_SELF']) != 'forms.php' &&
    basename($_SERVER['PHP_SELF']) != 'query.php'
) include_once("masterFormStartup.inc");

if (!isset($_startup))
  include_once("startup.inc");

/**
 * Caso a variavel ehXML venha ZERADA, inclui o trecho do cabecalho HTML
 */
if (!$ehXML){
  header('Content-Type: text/html; charset=' . $encoding);
  //<!DOCTYPE html>
   ?>
<!--[if IE 6]>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<![endif]-->
<!--[if !IE 6]>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<![endif]-->
<head profile="http://www.w3.org/2005/10/profile">
<link rel="apple-touch-icon" href="images/icone_iPhone.png"/>
<!-- IE -->
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
<!-- other browsers -->
<link rel="icon" type="image/x-icon" href="favicon.ico" />
  <TITLE><?PHP
  if (!$headerTitle) 
    echo $SYSTEM_NAME . " - " . $SYSTEM_VERSION;
  //echo "UNTITLED";
  else 
    echo $headerTitle; ?></TITLE>
    <meta name="viewport" content="width=device-width, user-scalable=no">			     
    <meta name="format-detection" content="telephone=no">
    <meta charset="<?PHP echo $encoding; ?>">
    <meta name="google" content="notranslate">
    <meta http-equiv="Content-Language" content="pt-br">

    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<?PHP echo $encoding; ?>">
<?PHP
if (stripos("_" . $_theme, "bootstrap"))
  echo "    <link rel=\"stylesheet\" type=\"text/css\" href=\"themeAssets/" . $_theme . "/css/bootstrap.css\" media=\"screen\">\n";
if (stripos("_" . $_theme, "frameless"))
  echo "    <link rel=\"stylesheet\" type=\"text/css\" href=\"themeAssets/" . $_theme . "/css/style.css\" media=\"screen\">\n";
?>
    <link href="dependencies/onde/chat.css" rel="stylesheet" type="text/css"/>
    <link href="dependencies/jquery/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <link href="dependencies/codemirror/lib/codemirror.css" rel="stylesheet" type="text/css"/>
    <link href="dependencies/codemirror/theme/night.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="dependencies/codemirror/addon/hint/show-hint.css" />
    <link href="dependencies/onde/onde.css" rel="stylesheet" type="text/css"/>

    <link rel="stylesheet" href="dependencies/chosen-js/chosen.css" type="text/css"/>

    <LINK REL="stylesheet" HREF="<?PHP
  if (!$_remoteAssets) if ($_theme) echo "themeAssets/" . $_theme;
    else echo "themeAssets/framelessPlain";
  else  echo "http://www.pucrs.br";?>/css/pucrs.css" TYPE="text/css">
    <LINK REL="stylesheet" HREF="<?PHP
  if (!$_remoteAssets) if ($_theme) echo "themeAssets/" . $_theme;
    else echo "themeAssets/framelessPlain";    
  else echo "http://www.pucrs.br";?>/css/tabela.css" TYPE="text/css">
    <LINK REL="stylesheet" HREF="<?PHP
  if (stripos("_" . $_theme, 'fancy') )//|| stripos("_" . $_theme, 'tron'))
    if (file_exists("./css/fancy.css"))
      echo "css/fancy.css";
    else{
      if (file_exists("../css/fancy.css"))
        echo "../css/fancy.css";
      else if (file_exists("../../dependencies/onde/css/fancy.css"))
          echo "../../dependencies/onde/css/fancy.css";
    }
  else if (file_exists("./themeAssets/" . $_theme . "/css/gati.css")) echo "themeAssets/" . $_theme . "/css/gati.css";
    else{
      if (file_exists("../themeAssets/" . $_theme . "/css/gati.css"))
        echo "../themeAssets/" . $_theme . "/css/gati.css";
      else if (file_exists("../../themeAssets/" . $_theme . "/css/gati.css"))
          echo "../../themeAssets/" . $_theme . "/css/gati.css";
     }?>" TYPE="text/css"> <?PHP /*http://stackoverflow.com/questions/4861224/how-to-use-css-to-surround-a-number-with-a-circle*/ ?>

    <STYLE type="text/css">
    .numberCircle {
      display: inline-block;
      border-radius: 50%;

      width: 36px;
      height: 36px;
      padding: 8px;
      
      background: #FF0000;
      border: 2px solid #FF0000;
      color: #FFFFFF;
      text-align: center;

      font: 32px Arial, sans-serif;
    }
    td.calendar {
      background-color : <?PHP if (stripos("_" . $_theme, 'fancy') || stripos("_" . $_theme, 'tron'))
                                 echo "black"; else echo "white"; ?>;
    }
    body {
      background-color : <?PHP if (stripos("_" . $_theme, 'fancy') || stripos("_" . $_theme, 'tron'))
                                 echo "black"; else echo "white"; ?>;
    }
    </STYLE>
    <link rel='stylesheet' type='text/css' href='dependencies/fullcalendar-1.4.11/fullcalendar/fullcalendar.css' />
    <!-- calendar stylesheet -->
    <link rel="stylesheet" type="text/css" media="all" href="dependencies/calendar/css/calendar-blue.css" title="winter" />
    <!-- main calendar program -->
	<script type="text/javascript" src="dependencies/calendar/js/calendar.js"></script>
    <!-- language for the calendar -->
    <script type="text/javascript" src="dependencies/calendar/lang/calendar-pt.js"></script>

    <!-- the following script defines the Calendar.setup helper function, which makes
         adding a calendar a matter of 1 or 2 lines of code. -->
    <script type="text/javascript" src="dependencies/calendar/js/calendar-setup.js"></script>
<?PHP/*
    <script type="text/javascript" src="dependencies/MathJax/MathJax.js?config=TeX-AMS_HTML-full"></script>

<script type="text/x-mathjax-config">
  MathJax.Hub.Config({
      //tex2jax: {inlineMath: [["$","$"],["\\(","\\)"]]}
    tex2jax: {inlineMath: [["\\(","\\)"]]}
  });
</script>
		 */?>


<?PHP
/*
 Para utilizar o editor de gantt, eh necessaria uma versao mais recente da framework jquery.
 Entretanto ainda há um problema de incompatibilidade com o menulateral quando em utilizando
 um tema "frameless" no internet explorer 6, pois o menu frameless no IE6 utiliza um redimensionamento
 por javascript.
 */
//if (stripos("_" . $_SERVER['PHP_SELF'], "gantt") || stripos("_" . $_theme, "bootstrap") ){
if (stripos("_" . $_SERVER['PHP_SELF'], "gantt") ){
  echo "  <link rel=stylesheet href=\"dependencies/jquerygantt/platform.css\" type=\"text/css\">\n";
  echo "  <link rel=stylesheet href=\"dependencies/jquerygantt/libs/dateField/jquery.dateField.css\" type=\"text/css\">\n";
  echo "  <link rel=stylesheet href=\"dependencies/jquerygantt/gantt.css\" type=\"text/css\">\n";
  echo "  <link rel=stylesheet href=\"dependencies/jquerygantt/ganttPrint.css\" type=\"text/css\" media=\"print\">\n";
  //echo "  <script type=\"text/javascript\" src=\"dependencies/jquery/jquery-1.8.min.js\"></script>\n";
  //echo "  <script type=\"text/javascript\" src=\"dependencies/jquery/jquery-ui-1.8.min.js\"></script>\n";
  echo "<script type=\"text/javascript\" src=\"dependencies/jquery-3.3.1.js\"></script>\n";
}
else{
  if (stripos("_" . $_SERVER['PHP_SELF'], "gantt") || stripos("_" . $_theme, "bootstrap") ){
    //echo "  <script type=\"text/javascript\" src=\"dependencies/jquery/jquery-1.8.min.js\"></script>\n";
    //echo "  <script type=\"text/javascript\" src=\"dependencies/jquery/jquery-ui-1.8.min.js\"></script>\n";    
    echo "<script src=\"dependencies/jquery-3.3.1.js\"></script>\n";
  }
  else{
    if (stripos("_" . $_SERVER['PHP_SELF'], "uploadArquivos")){
      echo "<script src=\"dependencies/jquery-3.3.1.js\"></script>\n";
    }
    else{
      echo "    <script type=\"text/javascript\" src=\"dependencies/jquery/jquery.min.js\"></script>\n";
      echo "    <script type=\"text/javascript\" src=\"dependencies/jquery/jquery-ui.min.js\"></script>\n";
    }
  }
}

echo "    <script type=\"text/javascript\" src=\"dependencies/onde/onde.js\"></script>\n";
if (stripos($_SERVER['PHP_SELF'], "shiftzoom"))
  echo "    <script type=\"text/javascript\" src=\"shiftzoom/shiftzoom.js\"></script>\n";
if (stripos($_SERVER['PHP_SELF'], "mapacampus")){
  echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
  echo "    <link rel=\"stylesheet\" href=\"maps/dist/leaflet.css\" />\n";
  echo "    <!--[if lte IE 8]><link rel=\"stylesheet\" href=\"dist/leaflet.ie.css\" /><![endif]-->\n";
}
// chosen
echo "<script type=\"text/javascript\" src=\"dependencies/chosen-order/dist/chosen.order.jquery.min.js\"></script>\n";
echo "<script src=\"dependencies/chosen-js/chosen.jquery.js\" type=\"text/javascript\"></script>\n";
echo "  </HEAD>\n";

if (stripos("_" . $_theme, 'fancy') || stripos("_" . $_theme, 'tron'))
  echo "  <BODY TEXT=\"lightblue\" BGCOLOR=\"black\">\n";
else
  echo "  <BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\">\n";

if ( stripos("_" . $_theme, "frameless") && !$ehXML){
  echo "<div id=\"wrapper\"><!--root element for IE6-->  \n";
}
 } // fim do condicionarl !$eh_XML

  if (isset($_manutencao) && $_manutencao){  
    if (!$ehXML)
      echo "<CENTER>MANUTENÇÃO</CENTER><BR>\n";
    else
      echo "[" . date("Y-m-d H:i:s", time()) . "] MANUTENÇÃO\n";
    include "page_footer.inc";
    exit;
  }


//$connectionString  = "dbname =   " . $banco . "\n";
//$connectionString .= "user =     " . $usuario_banco . "\n";
//$connectionString .= "password = " . $senha_banco;
if (basename($_SERVER['PHP_SELF']) != 'forms.php' &&
    basename($_SERVER['PHP_SELF']) != 'query.php')   $conn = pg_connect($connectionString);
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
  if (basename($_SERVER['PHP_SELF']) != 'forms.php' &&
      basename($_SERVER['PHP_SELF']) != 'query.php') include_once("sistemaIdeia.inc");

$isdeveloper = 0;
reset($developer);
foreach($developer as $dev){
  if ($useSessions)
    if ($dev == $_SESSION['matricula'])
      $isdeveloper=1;
}
$matricula = trim($_SESSION['matricula']);
$senha =  trim($_SESSION['senha']);
$ip = $_SERVER['REMOTE_ADDR'];

// $authlog_query  = "INSERT INTO authlog (" . (intval($matricula) ? "matricula, " : '') . "IP, page, get)\n";
// $authlog_query .= "  VALUES (" . (intval($matricula) ? "'" . $matricula . "', " : '') . "\n";
// $authlog_query .= "          '" . $ip . "', '";
// $authlog_query .= basename($_SERVER["PHP_SELF"]) . "',\n '{";
// foreach ($_GET as $key => $value){
//   $authlog_query .=  "(" . pg_escape_string($key) . " = " . pg_escape_string($value) . ")"; 
// }
// $authlog_query .= "}')\n";
// //echo "<PRE>". $authlog_query . "</PRE>";
// $authlog_exe = pg_exec($conn,$authlog_query);

// if (!$authlog_exe){
//   $authlog_query  = "INSERT INTO authlog(error) VALUES('" . pg_last_error();
//   $authlog_query .= "\nmatricula:" . $matricula;
//   //$authlog_query .= "\nsenha:" . $senha . "')";
//   $authlog_exe = pg_exec($conn,$authlog_query);
// }

/*
echo basename($_SERVER['PHP_SELF']) . "\n";
echo "<PRE>\n";
var_dump($withoutMenu);
echo "</PRE>\n";
*/
if ( stripos("_" . $_theme, "frameless") && !$ehXML && !$erro_sessao){
  if (!in_array(basename($_SERVER['PHP_SELF']), $withoutMenu)){
    $_menu = "PASSEI";
    /*
    <div id="header">
        <div id="innerhead">
            <h1>Cross Browser "Fixed Header-Footer-Sidebar"</h1> 
            <h3>Tested in IE6-8, FF-2, Opera-9.5, Win/Safari-3.1</h3>
        </div>
    </div>
    */
?>
<div id="innerwrap">
    <div id="main"> 
        <div id="right-wrap">
            <div id="right-inner">
	    <?PHP
if ( stripos("_" . $_theme, "bootstrap") && !$ehXML && !$erro_sessao)
   include("testMenu.inc");
 else
   if($_menu_from_db){
     include("database_menu.inc");
   }
   else
     include("menu.inc");
?>
            </div><!--end right-inner-->
        </div><!--end right-wrap-->
<?PHP
		
  }
?>
        <div id="content">
<?PHP
if (isset($withoutMenu) && isset($isMobile) && is_array($withoutMenu) && $isMobile && (!in_array(basename($_SERVER['PHP_SELF']), $withoutMenu)) && !$ehXML){
  ?>
<SCRIPT LANGUAGE='javascript'>
   var menu_mouse;
</SCRIPT>
<A STYLE="display:block;"
  onclick="document.getElementById('right-wrap').style.display = 'block';
           document.getElementById('right-inner').style.width=jQuery(window).width();"
  onmouseover="menu_mouse = document.body.style.cursor; document.body.style.cursor = 'pointer'; "
  onmouseout="document.body.style.cursor =  menu_mouse;"
  style="text-decoration: none; cursor: pointer; margin: 0px; padding: 0px; border: 0px;  width: 100%; display:block;"
  border="0"
  ><img src="images/Ic_menu_48px<?PHP if(stripos("_" . $_theme, 'fancy') || stripos("_" . $_theme, 'tron')) echo "Glow";?>.png"></A>
<?PHP
}

	   
 }
  if (file_exists("./images/titulo.gif"))
    echo "<DIV ID=titulo><IMG SRC=./images/titulo.gif></DIV>\n<BR>\n";
    else{
      if (file_exists("../images/titulo.gif"))
        echo "<DIV ID=titulo><IMG SRC=../images/titulo.gif></DIV>\n<BR>\n";
      else
        if (file_exists("../../images/titulo.gif"))
          echo "<DIV ID=titulo><IMG SRC=../../images/titulo.gif></DIV>\n<BR>\n";
    }
  if ($headerTitle && $useSessions)
    echo "    <DIV ID=\"o_titulo\" CLASS=\"titulo\">" . $headerTitle ."</DIV>";

// echo 'This request contained ' . count( $_POST ) . ' POST vars, ' . count( $_GET ) . ' GET vars, and ' . count( $_COOKIE ) . ' Cookies.'; 
// echo "<PRE>";
// var_dump(get_defined_vars());
// echo "</PRE>";
/*
if (!$ehXML){
  echo "\$_POST['toggle']: " . count($_POST['toggle']) . "<BR>\n";
  echo "\$_GET['toggle']: " . count($_GET['toggle']) . "<BR>\n";
  echo "\$toggle: " . count($toggle) . "<BR>\n";
}
*/

  if (isset($isMobile) && $isMobile && isset($withoutMenu) && is_array($withoutMenu) && (!in_array(basename($_SERVER['PHP_SELF']), $withoutMenu)) && !$ehXML ){
  echo "<SCRIPT>document.getElementById('right-wrap').style.display = 'none';\n";
  echo "        document.getElementById(\"main\").style.paddingLeft = \"0px\";\n";
  echo "        document.getElementById('right-inner').style.width=jQuery(window).width();\n";
  echo "</SCRIPT>";
}

/* echo "\$_POST['dark']: " . $_POST['dark'] . "<BR>\n"; */
/* echo "\$_SESSION['dark']: " . $_SESSION['dark'] . "<BR>\n"; */
/* echo "\$_theme: " . $_theme; */
/* echo "<PRE>"; */
/* var_dump($_SESSION); */
/* echo "</PRE>"; */

$htmlFooter = '
</P>
Atenciosamente,
<TABLE CELLSPACING="0" CELLPADDING="0" WIDTH="100%">
<TR>
<TD>
<TABLE>
<TR>
<TD WIDTH="79" ALIGN="left" VALIGN="top">
<PRE>
<IMG width="96" src="logo.png" ALIGN="bottom" BORDER="0">
</PRE>
</TD>
<TD WIDTH="100%" ALIGN="left">
&nbsp;Indusmart Prototype<BR>
&nbsp;Indusmart - Manufatura Inteligente<BR>
&nbsp;Telefone: (51) 3557-7404<BR>
&nbsp;E-mail: suporte@indusmart.com.br www.indusmart.com.br <BR>
</TD>
</TR>
</TABLE>
</TD>
</TR>
</TABLE>';

?>
