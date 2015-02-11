<?php
if(!defined('STRING_DELIM')) define('STRING_DELIM',","); // oddelovac desetinna tecka
require_once '../classes/CFileName.class.php'; //obsahuje class String
/**
 * vypocita rank jednorozmerneho array - u stejnych hodnot da prumer jejich ranku
 * zachova keys pole, ale pole zprehazi (bude serazene podle ranku)
 *  
 * @param array $matrix
 * @return array
 */
function rank_array($matrix){
  asort($matrix); // nejdriv musim seradit
  $pocet_stejnych = 0;
  $last_value = false;
  $last_key = false;
  $ranki = 0; // prubezny rank, bude zacinat na 1
  $rank_array = array();
  $stejne_keys = array(); // tam budu ukladat klice tech polozek, ktere budou mit stejny rank
  foreach($matrix as $key=>$val){
    if($last_value===false){
      $stejne_keys=array($key);
    } elseif($val==$last_value) {
      $pocet_stejnych++;
      $stejne_keys[]=$key;
    } elseif($val!=$last_value) {
      if($pocet_stejnych>0){
        $min = $ranki-$pocet_stejnych;
        $max = $ranki;
        $rank = ($max+$min)/2; // prumer nejvyssiho a nejnizzsiho stejneho ranku
        $pocet_stejnych = 0; // musim naplnit vsechny stejne tim rankem
        foreach($stejne_keys as $k)   	$rank_array[$k]=$rank;
      } else {
      	$rank = $ranki;
      	$rank_array[$last_key]=$rank;
      }
      $stejne_keys=array($key); // inicializace pole - je nova hodnota a pocet stejnych je 0
    }
    $last_value = $val;
    $last_key = $key;
    $ranki++;
  }
  
  // na konci se musi udelat jeste to same jako po jinem cisle
  if($pocet_stejnych>0){ 
        $min = $ranki-$pocet_stejnych;
        $max = $ranki;
        $rank = ($max+$min)/2; // prumer nejvyssiho a nejnizzsiho stejneho ranku
        foreach($stejne_keys as $k)   	$rank_array[$k]=$rank;
  } else {
      	$rank = $ranki;
      	$rank_array[$last_key]=$rank;
  }
  return $rank_array;
}

$filename = "matrix.txt";
$fc = file($filename);
$pole = array();
foreach($fc as $y=>$line){
	$vals = explode("\t",$line);
	foreach($vals as $x=>$val){
		$pole["$x-$y"]=(double) trim($val);
	}
}

$r = rank_array($pole);
$matrix = array();
foreach($r as $key=>$val){
	list($y,$x)= explode("-",$key);
	$matrix[$x][$y]=$val;
}

$out = "";
ksort($matrix);
foreach($matrix as $x=>$row){
	ksort($row);
	$out .= implode("\t",$row)."\n";
}
file_put_contents($filename.".out",String::setdelim($out));

?>