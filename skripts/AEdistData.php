<?php 
require_once 'classes/PsychopyData.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CmdLine.class.php';
//define('DIR','d:\\prace\\homolka\\epileptici EEG\\vysledky\\AEDist\\');
define('DIR','d:\\prace\\programovani\\psychopy\\AEDist\\data\\');


$cmdline = new CmdLine();
if($cmdline->Pocet()<2) {
	echo "potrebuji dva argumenty: kod subjektu a jmeno souboru";
	exit(-1);
}
$jmeno_pacienta = $cmdline->Arg(0); // kod subjektu napr p85
$filenames = array();
$filenames[] = $cmdline->Arg(1); // jmeno vystupu z psychopy, pro AEdist ze zacatku dva a pozdeji jeden soubor
if($cmdline->Pocet()>=3) {
	$filenames[] = $cmdline->Arg(2); 
}

$CTable = new TableFile(DIR.$jmeno_pacienta."_aedist.xls");
$CTable->AddColumns(array("file","keys","corr","rt","opakovani","zpetnavazba","podle"));

$CTableM = new TableFile(DIR.$jmeno_pacienta."_aedist.txt");
$CTableM->AddColumns(array("file","keys","corr","rt","opakovani","zpetnavazba","podle"));
$CTableM->setMatlab(true);

foreach($filenames as $f=>$fn){
	$psychopy = new PsychopyData(DIR.$fn);
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani","zpetnavazba","podle"));
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTable->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani'],$factor['zpetnavazba'],$factor['podle']));
	}
	$psychopy->SetKeyValues(array('None'=>-1,'left'=>0,'right'=>1));
	$psychopy->SetFactors(array('podle'=>array('cervena'=>0,'vy'=>1,'znacka'=>2)));
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani","zpetnavazba","podle"),true);
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTableM->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani'],$factor['zpetnavazba'],$factor['podle']));
	}
}
$CTable->SetPrecision(4,3);
$CTable->SaveAll(true);

$CTableM->SetPrecision(4,3);
$CTableM->SaveAll(true);

echo "OK";
?>