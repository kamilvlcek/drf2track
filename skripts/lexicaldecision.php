<?php
//10.10.2013 Kamil Vlcek

$filelist = "lexic.txt";      
$testname = "lexicaldecision";
if(!defined('LOGFILE')) define('LOGFILE',$filelist.".$testname.log");
define("FILELISTDIR","d:/prace/programovani/php/drf2track/skripts/filelists/"); // podadresar, kde jsou filelisty a kam se i ukladaji logy

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
$fileno = 0;
$CTable= new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".$testname.xls");
$CTableM= new TableFile($CFilelist->Dir()."\\tables\\".CFileName::Filename($filelist).".$testname.txt");
$CTableM->setMatlab();
foreach ($filename_arr as $groupname=>$groupdata) {
	if($groupname==Filelist::$group_not_set) $group = false; else $group = $groupname;
	foreach ($groupdata as $no=>$filename) {
		$newfilename = PxlabData::CheckFilename($filename, $testname);
		if($newfilename){
				dp("OK ",basename($newfilename));
				$CPxlabData = new PxlabData($newfilename,
					array("test"=>PxlabData::STRING,
							"subjekt"=>PxlabData::STRING,
							"trial"=>PxlabData::INT,
							"block"=>PxlabData::INT,
							"slovo1"=>PxlabData::STRING,"w1"=>PxlabData::INT,"resp1"=>PxlabData::INT,"time1"=>PxlabData::FLOAT,
							"slovo2"=>PxlabData::STRING,"w2"=>PxlabData::INT,"resp2"=>PxlabData::INT,"time2"=>PxlabData::FLOAT,
							"kategorie"=>PxlabData::STRING
						)
					);
				$CT = new Table();
				$CTM = new Table();
				$prevod = array(11=>array("Tr"=>0,"PrS"=>1,"NpS"=>2,"OpS"=>3,"NeNe"=>4,"SNe"=>5,"NeS"=>6),
					0=>array('Bara'=>2,'kamil'=>1));
				
				$CPxlabData->TableExport( array(1, 2, 3,4,5,6,7,8,9,10,11,12),$CT, $CTM,$prevod);
				$CTable->AppendTable($CT);
				$CTableM->AppendTable($CTM);
				$fileno++;
		}
	}
}
$CTable->SaveAll();
$CTableM->SaveAll();
dp($fileno,"files OK");

?>