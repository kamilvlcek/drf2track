<?php
// tyhle adresare jsou jen pro zend studio, z php primo nefunguji, a to ani vnorene
//  nejde nejak nastavit basedir "../"?
require_once 'classes/TableFile.class.php';
require_once 'classes/CFileName.class.php';
require_once('classes/filelist.class.php');

$dir_data = "d:\\prace\\tests\\hgt\\vyhodnoceni\\tables\\";
$dir_filelist = "d:\\prace\\tests\\hgt\\vyhodnoceni\\";
$pripona_re = '_re_hgt';
$pripona_ve = '_ve_hgt';

if(isset($_GET['arg'])) { $argc=2; $argv[1]=$_GET['arg']; } // opatreni pro debug ve studiu 7.1.1
if(isset($argc) && $argc >1){
	$filelist = $argv[1];
} else {
	//$filelist = "filelist_farmakoth_final_II";
	//$filelist = "RS_eva_hyncicova";
	//$filelist = "RS";
	//$filelist = "Subjects_for_HGT_updt";
	//$filelist = "filelist_add_APOE_new"; // 28.5.2013 Honza Laczo
	//$filelist = 'nicknames28_5'; // 1.6.2013 Honza Laczo
	//$filelist = 'Dodelavka_TOMM40'; // 10.6.2013 Honza Laczo
	//$filelist = 'dgMenrot201307'; // 18.7.2013 Hanka Markova
	//$filelist = 'proZuzku201309'; // 4.9.2013 Ivana Gazova
	//$filelist = 'proHanku201311'; // 6.11.2013 pro Hanku Markovou a Honzu Laczo
	//$filelist = 'dodelavka_pacienti_BVA'; // 14.3.2014 pro Honzu Laczo
	//$filelist = 'skopolamin2014'; // 9.9.2014 pro Hanku Markovou
	//$filelist = 'mokrisova2014'; // 10.2014 pro Ivanu Mokrisovou
	//$filelist = 'nedelska2014'; // 8.10.2014 pro Ivanu Mokrisovou 
	//$filelist = 'skopolamin2014Trial1'; // 23.10.2014 pro Hanku Markovou
	//$filelist = 'skopolamin2014Trial2'; // 23.10.2014 pro Hanku Markovou
	//$filelist = 'skopolamin2014Trial3'; // 23.10.2014 pro Hanku Markovou
	//$filelist = 'skopolamin2014Trial4'; // 23.10.2014 pro Hanku Markovou
	//$filelist = 'skopolamin2014Trial0'; // 24.10.2014 pro Hanku Markovou
	// $filelist = 'lerch2015'; // 24.10.2014 pro Ondreje Lerche
	//$filelist = 'homocystein2015'; // 24.10.2014 pro Ondreje Lerche
	//$filelist = 'nedelska2015'; // 9.7.2015 pro Zuzanu Nedelskou
	$filelist = 'nedelska2016'; // 14.1.2016 pro Zuzku
}
($subjects = get_subjects($dir_filelist.$filelist.'_ve.txt')) or die; 
// chybejici soubory jsou ve vyslednych datech vynechany, tak musim pracovat s puvodnim souborem


$CTable_out = new TableFile($dir_data.$filelist."_souhrn.xls");
$CTable_out->AddColumns(array("subject","group",
		"HGTrAE1", "HGTrAE2", "HGTrAE3", "HGTrAE4", "HGTrAE5", "HGTrAE6", "HGTrAE7", "HGTrAE8", "HGTrAEavg", 
		"HGTrE1", "HGTrE2", "HGTrE3", "HGTrE4", "HGTrE5", "HGTrE6", "HGTrE7", "HGTrE8", "HGTrEavg", 
		"HGTrA1", "HGTrA2", "HGTrA3", "HGTrA4", "HGTrA5", "HGTrA6", "HGTrA7", "HGTrA8", "HGTrAavg", 
		"HGTrD1", "HGTrD2", "HGTrDavg"));
$CTable_out->AddColumns(array(
		"HGTvAE1", "HGTvAE2", "HGTvAE3", "HGTvAE4", "HGTvAE5", "HGTvAE6", "HGTvAE7", "HGTvAE8", "HGTvAEavg", 
		"HGTvE1", "HGTvE2", "HGTvE3", "HGTvE4", "HGTvE5", "HGTvE6", "HGTvE7", "HGTvE8", "HGTvEavg", 
		"HGTvA1", "HGTvA2", "HGTvA3", "HGTvA4", "HGTvA5", "HGTvA6", "HGTvA7", "HGTvA8", "HGTvAavg", 
		"HGTvD1", "HGTvD2", "HGTvDavg"));
$subtests = array("Allo-idio","Idio","Allo","Allo-DelRec");
$CTable_re = new Table();
$CTable_re->ReadFile($dir_data.$filelist.$pripona_re.".txt");
$CTable_ve = new Table();
$CTable_ve->ReadFile($dir_data.$filelist.$pripona_ve.".txt");
//$subjects = merge_re_ve($CTable_re->Unique("filename"),$CTable_re->Unique("filename")); 

foreach($subjects as $groupname=>$groupdata){
	foreach($groupdata as $subject){
		$subject = CFileName::Filename($subject);
		$CTable_out->AddToRow(array($subject,$groupname));
		$CTableSubject = $CTable_re->Select("filename", $subject.".re");
		echo CFileName::Filename($subject)."\n";
		foreach($subtests as $subtest){
			$CTableRow = $CTableSubject->Select("test", $subtest);
			if($subtest =="Allo-DelRec") 
				$columnames = array("trial 1", "trial 2",  "average");
			else 
				$columnames = array("trial 1", "trial 2", "trial 3", "trial 4", "trial 5", "trial 6", "trial 7", "trial 8", "average");
			if($CTableRow->RowCount()>0)
				$CTable_out->AddToRow($CTableRow->Row(0,$columnames));
			else 
				$CTable_out->AddToRow(array_fill(0, count($columnames), ""));	// v pripade ze radku nenajdu, doplnim chybejici hodnoty
		}
		
		$CTableSubject = $CTable_ve->Select("filename", $subject.".ve"); 
		//echo CFileName::Filename($subject)."\n";
		foreach($subtests as $subtest){
			$CTableRow = $CTableSubject->Select("test", $subtest);
			if($subtest =="Allo-DelRec") 
				$columnames = array("trial 1", "trial 2",  "average");
			else 
				$columnames = array("trial 1", "trial 2", "trial 3", "trial 4", "trial 5", "trial 6", "trial 7", "trial 8", "average");
			if($CTableRow->RowCount()>0)
				$CTable_out->AddToRow($CTableRow->Row(0,$columnames));
			else 
				$CTable_out->AddToRow(array_fill(0, count($columnames), ""));	// v pripade ze radku nenajdu, doplnim chybejici hodnoty
		}
		$CTable_out->AddRow();
	}
}
$CTable_out->SaveAll();

/**
 * asi nebudu pouzivat, misto toho get_subjects
 * vrati seznam unikatnich jmen subjektu
 * @param array $reSubjects pripony.re
 * @param array $veSubjects pripony.ve
 * @return array
 */
function merge_re_ve($reSubjects,$veSubjects){
	foreach($veSubjects as $s=>$subj){
		$veSubjects[$s] = CFileName::Filename($subj);
	}
	foreach($reSubjects as $s=>$subj){
		$reSubjects[$s] = CFileName::Filename($subj);
	}
	return array_unique(array_merge($veSubjects,$reSubjects));
}
/**
 * vrati pole skupin a soubour z filelistu; v pripde chyb vypisuje hlasky
 * @param string $filelist i s cestou
 * @return boolean|array
 * @since 1.2.2013
 */
function get_subjects($filelist){
	$CFilelist = new Filelist($filelist,true);
	if(isset($CFilelist)){
	   if($CFilelist->Ok()){
	     if(($duplicates=$CFilelist->Duplicates())!=false){
	     	echo "\n!!duplikovane soubory: ".count($duplicates)."\n".implode("\n",$duplicates)."\n";
	        return false;
	     }
	     $filename_arr = $CFilelist->GetList();
	     $extensions = array('');
	   } else {
	     echo "nemuzu precit filelist $filelist\n";
	     return false;
	   }
	} else {
	  echo "neni zadany zadny filelist!";
	  return false;
	}
	return $filename_arr;
}
?>