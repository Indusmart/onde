<?PHP
$headerTitle = "Error - logging on!";
$useSessions = 0; $ehXML=0;
include "page_header.inc";

echo "<CENTER>\n";
if (stripos("_" . $_theme, 'fancy')  || stripos("_" . $_theme, 'tron'))
  echo "<IMG SRC=images/dialog-error.png>\n";
else   
  echo "<IMG SRC=images/icon_vazio.jpg>\n";
?>
<BR>
<BR>
<B>Este site requer autenticação.</B><BR>
Clique no botão abaixo pra efetuar login.<BR>
<BR>
<?PHP
if (isset($_GET['demanda'])) $demanda = intval($_GET['demanda']); else $demanda = 0;
if (isset($_GET['form'])) $form = $form = intval($_GET['form']); else $form = 0;

$href = "frm_login.php";
if ($demanda){
  $href .= "?demanda=" . $demanda;
}
else
  if ($form) {
    $href .= "?form=" . $form;
  }
  else
    $href .= "?landingPage=" . basename($_SERVER['PHP_SELF']);

echo "<A HREF=\"" . $href . "\"";
if (!stripos("_" . $_theme, "frameless"))
  echo "TARGET=\"_main\"";
echo ">Efetuar Login</A>";?>
</CENTER>
<?PHP
  echo "<SCRIPT>\n";
  echo "$(function() {\n";
  echo "    console.log( \"ready!\" );\n";
  echo "     window.location.href = '" . $href . "';\n";
  echo "  });\n";
  echo "</SCRIPT>\n";
include "page_footer.inc";
?>