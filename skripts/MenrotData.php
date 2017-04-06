<?php 
require_once 'classes/PsychopyData.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CmdLine.class.php';
define('DIR','d:\\prace\\homolka\\epileptici EEG\\vysledky\\Menrot\\');

$cmdline = new CmdLine();
if($cmdline->Pocet()<2) {
	echo "potrebuji dva argumenty: kod subjektu a jmeno souboru";
	exit(-1);
}
$jmeno_pacienta = $cmdline->Arg(0); // kod subjektu napr p85
$filenames = array();
$filenames[] = $cmdline->Arg(1); // jmeno vystupu z psychopy, pro Menrot ze zacatku dva a pozdeji jeden soubor
if($cmdline->Pocet()>=3) {
	$filenames[] = $cmdline->Arg(2); 
}

$CTable = new TableFile(DIR.$jmeno_pacienta."_menrot.xls");
$CTable->AddColumns(array("file","keys","corr","rt","opakovani","zpetnavazba","podle","verze"));

$CTableM = new TableFile(DIR.$jmeno_pacienta."_menrot.txt");
$CTableM->AddColumns(array("file","keys","corr","rt","opakovani","zpetnavazba","podle","verze"));
$CTableM->setMatlab(true);

foreach($filenames as $f=>$fn){
	$psychopy = new PsychopyData(DIR.$fn);
	// nejdriv data pro XLS soubor
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani","zpetnavazba","podle","verze"));
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTable->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani'],$factor['zpetnavazba'],$factor['podle'],$factor['verze']));
	}
	// ted data pro Matlab soubor
	$psychopy->SetKeyValues(array('None'=>-1,'left'=>0,'right'=>1)); // ciselne hodnoty pro stlacene klavesy
	$psychopy->SetFactors(array('podle'=>array('vy'=>0,'znacka'=>1),'verze'=>array('2D'=>0,'3D'=>1)));
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani","zpetnavazba","podle","verze"),true);
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTableM->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani'],$factor['zpetnavazba'],$factor['podle'],$factor['verze']));
	}
}
$CTable->SetPrecision(4,3);
$CTable->SaveAll(true);

$CTableM->SetPrecision(4,3);
$CTableM->SaveAll(true);

echo "OK";
?>