<?PHP
$useSessions = 1; $ehXML = 1;
include "iniset.php";
include "page_header.inc";
include "pythonocc.inc";

$partCode = trim(intval($_GET['part']));
header("Content-type: model/stl");


cleanPlot(); // erase temp files.
include "page_footer.inc";
?>
