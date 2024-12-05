<?php 
require_once 'classes/PsychopyData.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CmdLine.class.php';
//define('DIR','d:\\prace\\homolka\\epileptici EEG\\vysledky\\Domecek\\');
//define('DIR','d:\\prace\\programovani\\psychopy\\Domecek\\data\\');
define('DIR','d:\\prace\\programovani\\psychopy\\Domecek\\data\\PORG\\');

$cmdline = new CmdLine();
if($cmdline->Pocet()<2) {
	echo "potrebuji dva argumenty: kod subjektu a jmeno souboru";
	exit(-1);
}
$jmeno_pacienta = $cmdline->Arg(0); // kod subjektu napr p85
$filenames = array();
$filenames[] = $cmdline->Arg(1); // jmeno vystupu z psychopy, jeden soubor
if($cmdline->Pocet()>=3) {
	$filenames[] = $cmdline->Arg(2); 
}

$CTable = new TableFile(DIR.$jmeno_pacienta."_domek.xls"); // vystupni soubor
$CTable->AddColumns(array("file","keys","corr","rt","block","zpetnavazba","condition","caspauza")); // sloupce vystupniho souboru

$CTableM = new TableFile(DIR.$jmeno_pacienta."_domek.txt"); 
$CTableM->AddColumns(array("file","keys","corr","rt","block","zpetnavazba","condition","caspauza"));
$CTableM->setMatlab(true);

foreach($filenames as $f=>$fn){
	$psychopy = new PsychopyData(DIR.$fn);
	// nejdriv data pro XLS soubor
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("block","zpetnavazba","condition","caspauza")); //odpovedi a faktory
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTable->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['block'],$factor['zpetnavazba'],$factor['condition'],$factor['caspauza']));
	}
	// ted data pro Matlab soubor
	$psychopy->SetKeyValues(array('None'=>-1,'space'=>0,'n'=>1)); // ciselne hodnoty pro stlacene klavesy
	$psychopy->SetFactors(array('condition'=>array('match'=>0,'side'=>1,'depth'=>2,'identity'=>3)));
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("block","zpetnavazba","condition","caspauza"),true);
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTableM->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['block'],$factor['zpetnavazba'],$factor['condition'],$factor['caspauza']));
	}
}
$CTable->SetPrecision(4,3);
$CTable->SaveAll(true);

$CTableM->SetPrecision(4,3);
$CTableM->SaveAll(true);

echo "OK";
?>