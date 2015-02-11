<?php
//30.1.2013 Kamil Vlcek
//$filelist = "dataBVAdiag.txt";
//$filelist = "dgMenrot201307.txt"; //16.7.2013
//$filelist = "dgMenrot201308.txt"; //28.8.2013
$filelist = "dgMenrot201308b.txt"; //2.9.2013      

if(!defined('LOGFILE')) define('LOGFILE',$filelist.".menrot.log");
require_once('classes/filelist.class.php');
require_once('includes/logfile.php');
require_once 'classes/PxlabData.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CFileName.class.php';

$CFilelist = new Filelist($filelist);

if(isset($CFilelist)){
   if($CFilelist->Ok()){
     if(($duplicates=$CFilelist->Duplicates())!=false){
     	echo "\n!!duplikovane soubory: ".count($duplicates)."\n".implode("\n",$duplicates)."\n";
        exit;
     }
     $filename_arr = $CFilelist->GetList();
     $extensions = array('');
   } else {
     echo "nemuzu precit filelist $filelist\n";
     exit;
   }
} else {
  echo "neni zadany zadny filelist!";
  exit;
}
$subtests = array("menrot_egoallo","menrot_bva");
$uhly = array("45","90","135","180","225","270","315");
$CTable = new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".menrot.xls");
$CTable->AddColumns(array("subject",'group',//2
		"trials2D","LeftHits2D","LeftMisses2D","RightHits2D","RightMisses2D","LeftRate2D","RightRate2D", //7
		"45-2D","90-2D","135-2D","180-2D","225-2D","270-2D","315-2D",//7
		"cas","c45-2D","c90-2D","c135-2D","c180-2D","c225-2D","c270-2D","c315-2D",//8, celkem 22
		"trials3D","LeftHits3D","LeftMisses3D","RightHits3D","RightMisses3D","LeftRate3D","RightRate3D",//7
		"45-3D","90-3D","135-3D","180-3D","225-3D","270-3D","315-3D",//7
		"cas","c45-3D","c90-3D","c135-3D","c180-3D","c225-3D","c270-3D","c315-3D"//8 = 46
		));
//druha tabulka 27.9.2013, chyby pri odpovedich vlevo a vpravo zvlast - delam kvuli krajnim uhlum
$CTableLR = new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".menrotLR.xls");
$CTableLR->AddColumns(array("subject",'group','Ltrials2D','Rtrials2D',
		"L45-2D","L90-2D","L135-2D","L180-2D","L225-2D","L270-2D","L315-2D",
		"R45-2D","R90-2D","R135-2D","R180-2D","R225-2D","R270-2D","R315-2D",
		'Ltrials3D','Rtrials3D',
		"L45-3D","L90-3D","L135-3D","L180-3D","L225-3D","L270-3D","L315-3D",
		"R45-3D","R90-3D","R135-3D","R180-3D","R225-3D","R270-3D","R315-3D" /* 4+7*4=32 sloupcu */
));
$fileno = 0;
foreach ($filename_arr as $groupname=>$groupdata) {
	if($groupname==Filelist::$group_not_set) $group = false; else $group = $groupname;
	foreach ($groupdata as $no=>$filename) {
		//dp("\n------- file ".(++$fileno)."/".$CFilelist->Count()." ----------");
		// filename je jmeno souboru s priponou dat a s adresarem
		$subjekt = CFileName::Filename($filename); // jen samotny nickname
		$CTable->AddToRow(array($subjekt,$groupname));
		$CTableLR->AddToRow(array($subjekt,$groupname));
		foreach($subtests as $s=>$testname){
			$newfilename = PxlabData::CheckFilename($filename, $testname);
			if($newfilename){
				dp("OK $testname",basename($newfilename));
				//$filename = $newfilename;
				$CPxlabData = new PxlabData($newfilename, array("test"=>PxlabData::STRING,"subjekt"=>PxlabData::STRING,
					"trial"=>PxlabData::INT,"block"=>PxlabData::INT,
					"obrazek"=>PxlabData::STRING,"nic"=>PxlabData::STRING,
					"col_ok"=>PxlabData::INT,"col_tip"=>PxlabData::INT,"time"=>PxlabData::FLOAT));
				$vysl = $CPxlabData->RecognitionScores(6/*spravna odpoved*/,7/*odpoved*/,5/*uhel*/,8/*cas*/);
				$CTable->AddToRow(array($vysl['trials'],$vysl['hits'],$vysl['misses'],$vysl['true_false']/*RightHits2D*/,$vysl['false_alarms'],
					$vysl['sensitivity']/*LeftRate*/,$vysl['specificity']/*RightRate*/));
					// ve vysledcich: left=0, right=1. Takze hits jsou 
					// senzitivita = hitrate (hits/hits+misses). trufalse = vlevo spravne, Hits=vpravo spravne
				$CTableLR->AddToRow(array($vysl['trialsFA'],$vysl['trialsHits']));
				foreach($uhly as $uhel){
					$score_uhel = ($vysl['factor_vals'][$uhel]['sensitivity'] + $vysl['factor_vals'][$uhel]['specificity'])/2;
					$CTable->AddToRow(array($score_uhel));
				}
				$CTable->AddToRow(array($vysl['casy_prumer']));
				foreach($uhly as $uhel)  $CTable->AddToRow(array($vysl['factor_vals'][$uhel]['casy_prumer']));
				// rozdeleni uspesnoti podle vlevo/vpravo - 27.9.2013
				foreach($uhly as $uhel)	 $CTableLR->AddToRow(array($vysl['factor_vals'][$uhel]['specificity']));
				foreach($uhly as $uhel)	 $CTableLR->AddToRow(array($vysl['factor_vals'][$uhel]['sensitivity']));
			} else {
				dp(" ** nenalezeno !! $testname **",basename($filename));
				$CTable->AddToRow(array_fill(0, 22, "")); // v tabulce budou prazdne bunky za chybejici soubor
				$CTableLR->AddToRow(array_fill(0, 16, "")); // v tabulce budou prazdne bunky za chybejici soubor
			}
		}
		$CTable->AddRow();
		$CTableLR->AddRow();
		$fileno++;
		
	}
}
$CTable->SaveAll();
$CTableLR->SaveAll();
dp($fileno,"files OK");
?>