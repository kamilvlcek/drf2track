<?php 
require_once 'classes/PsychopyData.class.php';
require_once 'classes/TableFile.class.php';

//define('DIR','d:\\prace\\homolka\\epileptici EEG\\vysledky\\AEDist\\');
define('DIR','d:\\prace\\programovani\\psychopy\\AEDist\\data\\');
/* 
$filenames = array("jk140514_2014_5_14_1110.csv","jk140514_2014_5_14_1118.csv");
$jmeno_pacienta = "jk";
*/


//$filenames = array("stropek_2014_6_20_1307.csv","stropek_2014_6_20_1321.csv");
//$jmeno_pacienta = "stropek";

/*
$filenames = array(
	"vg140410_2014_IV_10_1035.csv","vg140410_2014_IV_10_1043.csv",
	"rc140410_2014_IV_10_1445.csv",
	"jj140423_2014_IV_23_1445.csv","jj140423_2014_IV_23_1452.csv",
	"jm140424_2014_IV_24_1041.csv","jm140424_2014_IV_24_1050.csv",
	"mp140424_2014_IV_24_1416.csv","mp140424_2014_IV_24_1424.csv",
	"bs140428_2014_IV_28_1637.csv","bs140428_2014_IV_28_1645.csv",
	"js140429_2014_IV_29_1028.csv","js140429_2014_IV_29_1036.csv",
	"jk140514_2014_5_14_1110.csv","jk140514_2014_5_14_1118.csv"
);
$jmeno_pacienta = "kontroly";
*/
$filenames = array(
	'tn160211_AEdist201601_2016_2_11_0931.csv'
);
$jmeno_pacienta = "p97";


$CTable = new TableFile(DIR.$jmeno_pacienta."_aedist.xls");
$CTable->AddColumns(array("file","keys","corr","rt","opakovani","zpetnavazba","podle"));
foreach($filenames as $f=>$fn){
	$psychopy = new PsychopyData(DIR.$fn);
	list($odpovedi,$factors) = $psychopy->Odpovedi("odpoved", array("opakovani","zpetnavazba","podle"));
	foreach($odpovedi as $ln=>$o){
		$factor = $factors[$ln];
		$CTable->AddRow(array($f,$o['keys'],$o['corr'],$o['rt'],$factor['opakovani'],$factor['zpetnavazba'],$factor['podle']));
	}
}
$CTable->SetPrecision(4,3);
$CTable->SaveAll(true);
?>