<?php
//29.1.2013 Kamil Vlcek
//$filelist = "dataBVA.txt";
//$filelist = "LandmarksKatka.txt";
$filelist = "LandmarksMCI_AD.txt";

if(!defined('LOGFILE')) define('LOGFILE',$filelist.".landmarks.log");
define("FILELISTDIR","d:/prace/programovani/php/drf2track/skripts/filelists/");
require_once('classes/filelist.class.php');
require_once('includes/logfile.php');
require_once 'classes/PxlabData.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CFileName.class.php';

$CFilelist = new Filelist(FILELISTDIR.$filelist);

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

$CTable = new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".landmarks.xls");
$CTable_polozky = new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".landmarks_polozky.xls");
$CTable->AddColumns(array("subject","trials","hits","misses","false_alarms","true_false","HitRate","FalseAlarmRate"));
$CTable_polozky->AddColumns(array("subject"));

$fileno = 0;
$polozky_prumery = array();

foreach ($filename_arr as $groupname=>$groupdata) {
	if($groupname==Filelist::$group_not_set) $group = false; else $group = $groupname;
	dp("*** skupina ***",$group);
	
	$CTable_polozky_pairs = new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".landmarks_polozky_pary_$group.xls");
	$CTable_polozky_pairs->AddColumns(array("subject"));
	$landmark_pairs = read_landmarkPairs();
	foreach($landmark_pairs as $pair){
		$nonfake = str_replace("_1000", "",str_replace(".jpg", "",$pair[1]));
		$CTable_polozky_pairs->AddRow(array($nonfake)); // prvni sloupec bude jmeno non-fake
	}

	foreach ($groupdata as $no=>$filename) {
		//dp("\n------- file ".(++$fileno)."/".$CFilelist->Count()." ----------");
		$subjekt =  CFileName::Filename($filename);
		$newfilename = PxlabData::CheckFilename($filename, "Landmarks_test");
		if($newfilename){
			dp("OK",basename($newfilename));
			$filename = $newfilename;
			$CPxlabData = new PxlabData($filename, array("test"=>PxlabData::STRING,"subjekt"=>PxlabData::STRING,
				"trial"=>PxlabData::INT,
				"obrazek"=>PxlabData::STRING,"nic"=>PxlabData::STRING,
				"col_ok"=>PxlabData::INT,"col_tip"=>PxlabData::INT,"time"=>PxlabData::FLOAT));
			$vysl = $CPxlabData->RecognitionScores(5,6);		
			$CTable->AddRow(array($subjekt,$vysl['trials'],$vysl['hits'],$vysl['misses'],$vysl['false_alarms'],$vysl['true_false'],$vysl['sensitivity'],$vysl['specificity']));
			$polozky = $CPxlabData->Polozky(3, 5, 6,7);
			if($fileno==0){ // prvni soubor
				foreach($polozky['names'] as $key=>$name) $CTable_polozky->AddColumns(array($name)); 					// dalsi sloupce podle poctu polozek
				//foreach($polozky['names'] as $key=>$name) $CTable_polozky->AddColumns(array($name."_time"));
				// prvni radka se spravnymi odpovedmi
				$CTable_polozky->AddRow(array_merge(array("spravne"),$polozky['spravne']));//,array_fill(0,count($polozky['spravne']),0)
			}
			$CTable_polozky->AddRow(array_merge(array($subjekt),$polozky['tip']));//,$polozky['casy']
			foreach($polozky['tip'] as $key=> $tip){
				$polozky_prumery[$key][]=$tip;
			}
			// 3.2.2014 - polozky pro polozkovou analyzu v parech
			$pary_odpovedi = usporadej_polozky($polozky,$landmark_pairs);
			$CTable_polozky_pairs->AddColumnData($pary_odpovedi[0], "0-$subjekt"); //fake
			$CTable_polozky_pairs->AddColumnData($pary_odpovedi[1], "1-$subjekt");
			
			$fileno++;
		} else {
			dp("nenalezeno",basename($filename));
			$CTable->AddRow(array_merge(array($subjekt),array_fill(0, 7, "")));
		}
		
	}
	$CTable_polozky_pairs->SaveAll(true); // tabulka pro kazdou skupinu zvlast
	unset($CTable_polozky_pairs);
}
$CTable_polozky->AddToRow(array("prumery"));
foreach($polozky_prumery as $p){
	$CTable_polozky->AddToRow(array(average($p)));
}
$CTable_polozky->AddRow();
$CTable->SaveAll();
$CTable_polozky->SaveAll();



/**
 * vrati pole paru fake-nonfake, podle souboru landmarkpairs.txt
 * @return marray  
 * @since 3.2.2014
 */
function read_landmarkPairs(){
	$filename = "landmarkpairs.txt";
	$fc = file($filename);
	$pairs = array();
	foreach($fc as $line){
		list($fake,$nonfake) = explode("\t",trim($line));
		$pairs[]=array($fake,$nonfake);
	}
	return $pairs;
}
/**
 * vrati pole o dvou sloupcich - tip subjektu fake-nonfake
 * @param array $polozky  z $CPxlabData->Polozky
 * @param array $pairs z read_landmarkPairs
 * @return array
 */
function usporadej_polozky($polozky,$pairs){
	$polozky2 = array();
	foreach($polozky['names'] as $l=>$name){
		$polozky2[$name]=array($polozky['spravne'][$l],$polozky['tip'][$l]); // polozku spravne nebudu pouzivat
	}
	$odpovedi= array();
	foreach($pairs as $l=>$pair){
		$odpovedi[0][$l]=$polozky2[$pair[0]][1]/*fake*/;		
		$odpovedi[1][$l]=$polozky2[$pair[1]][1]/*nonfake*/;
	}
	return $odpovedi;
}
?>
