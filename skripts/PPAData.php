<?php 
require_once 'classes/PsychopyDataPPA.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CmdLine.class.php';
// 31.3.2015 prepracovano z AEDistData
//define('DIR','d:\\prace\\homolka\\epileptici EEG\\vysledky\\PPAlocalizer\\');
define('DIR','d:\\prace\\programovani\\psychopy\\PPAlocalizer\\data\\');

$cmdline = new CmdLine();
if($cmdline->Pocet()<2) {
	echo "potrebuji dva argumenty: kod subjektu a jmeno souboru";
	exit(-1);
}
$jmeno_pacienta = $cmdline->Arg(0); // kod subjektu napr p85
$filenames = array($cmdline->Arg(1)); // jmeno vystupu z psychopy, pro PPA vzdy jen jeden soubor

$CTable = new TableFile(DIR.$jmeno_pacienta."_ppa.xls");
$CTable->AddColumns(array("file","keys","corr","rt","opakovani_obrazku","cisloobrazku","pauza","kategorie"));

$CTableM = new TableFile(DIR.$jmeno_pacienta."_ppa.txt");
$CTableM->AddColumns(array("file","keys","corr","rt","opakovani_obrazku","cisloobrazku","pauza","kategorie"));
$CTableM->setMatlab(true);

foreach($filenames as $f=>$fn){
	$psychopy = new PsychopyDataPPA(DIR.$fn);
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani_obrazku","pauza","kategorie","cisloobrazku")); 
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTable->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani_obrazku'],$factor['cisloobrazku'],$factor['pauza'],$factor['kategorie']));
	}
	$psychopy->SetKeyValues(array('None'=>-1,'space'=>1));
	$psychopy->opakovaniReset();
	//$psychopy->SetFactors(array('kategorie'=>array('cervena'=>0,'vy'=>1,'znacka'=>2))); // kategori v datech neni
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani_obrazku","pauza","kategorie","cisloobrazku"),true);
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTableM->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani_obrazku'],$factor['cisloobrazku'],$factor['pauza'],$factor['kategorie']));
	}
}
$CTable->SetPrecision(4,3);
$CTable->SaveAll(true);

$CTableM->SetPrecision(4,3);
$CTableM->SaveAll(true);

echo "OK";


?>