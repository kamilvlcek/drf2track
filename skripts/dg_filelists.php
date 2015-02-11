<?php
require_once '../classes/filelist.class.php';
require_once '../classes/CFilename.class.php';
$filelists = array(
"d:\\prace\\tests\\hgt\\vyhodnoceni\\mladi_a_stari_dobrovolnici_re.txt",
"d:\\prace\\programovani\\php\\drf2track\\filelists\\darkevel_for_Kamil_MCI_together.txt",
"d:\\prace\\tests\\hgt\\vyhodnoceni\\BVAxNPSYCH_filelist_re.txt",
"d:\\prace\\tests\\hgt\\vyhodnoceni\\MCInormy_ve.txt",
"d:\\prace\\tests\\hgt\\vyhodnoceni\\New_hippocampal_filelist_ve.txt",
"d:\\prace\\programovani\\php\\drf2track\\filelists\\filelist_demence_Allo-Ego.txt"
);

$subjects = array();
foreach ($filelists as $fl){
	$CFilelist = new Filelist($fl);
	if($CFilelist->OK()){
		$list = $CFilelist->GetList();
		foreach($list as $group=>$groupdata){
			foreach($groupdata as $filename){
				$s = strtolower(trim(CFileName::Filename($filename)));
				while( $s{strlen($s)-1}=='x' ){
					$s = substr($s,0,strlen($s)-1); // odstranim koncova x
				}
				$group = trim($group);
				if( !isset($subjects[$s]) ||  !in_array($group, $subjects[$s])) 
					$subjects[$s][]= $group;
			}
		}
		
	} else {
     echo "nemuzu precit filelist $fl\n";
     exit;
    }
}
$output = "";
ksort($subjects);
foreach($subjects as $s=>$groups){
	$output .="$s";
	for($i = 0;$i<count($filelists);$i++){
		if(isset($groups[$i]))
			$output.="\t$groups[$i]";
		else
			$output.="\t"; 
			
	}
	$output.="\n";
}

file_put_contents("dg_filelist.xls", $output);
echo "done";
?>