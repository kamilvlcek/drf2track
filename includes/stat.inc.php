<?php

/**
 * vrati pole s jen ciselnymi prvky
 *
 * @param array $vals
 * @return array
 */
function numeric_only($vals){
  if(!is_array($vals)) return false;
  if(is_array(reset($vals))){
    $vystup = array();
    foreach ($vals as $key => $val) {
    	$vystup[$key]=numeric_only($val);
    }
    return $vystup;
  } else {
	 return array_filter($vals,"is_numeric");
  }
}
function pocet($vals){
  if(!is_array($vals)) return false;
  $vals = numeric_only($vals);
  if(is_array(reset($vals))){
    $pocty = array();
    foreach ($vals as $key => $vals2) {
    	$pocty[$key]= pocet($vals2);
    }
    return $pocty;
  } else {
	 return count($vals);
  }
}
/**
 * vraci soucet prvku pole. Pokud jsou prvky pole dalsi pole, vrati pole souctu
 *
 * @param unknown_type $vals
 */
function sum($vals){
  if(!is_array($vals)) return false;
  $vals = numeric_only($vals);
  if(is_array(reset($vals))){
    $sumy = array();
    foreach($vals as $key=>$val){
      $sumy[$key]=sum($val);
    }
    return $sumy;
  } else {
    return array_sum($vals);
  }
}
/**
 * prumer prvku pole. Pokud jsou prvky pole dalsi pole, vrati pole prumeru
 *
 * @param array $vals
 * @return double/array
 */
function average($vals){
  if(!is_array($vals) || count($vals)==0) return false;

	if(is_array(reset($vals))){ // pokud je prvni prvek pole array, predpokladam, ze i dalsi budou pole a vratim pole prumeru
	  $prumery = array();
	  foreach($vals as $key=>$val){
	    $prumery[$key]=average($val); //
	  }
	  return $prumery;
	} else { // prvky pole $vals nejsou pole
	  $vals = numeric_only($vals); // vyberu jen ciselne prvky pole
	  return array_sum($vals)/count($vals);
	}

}
// SS = suma (x^2) - (suma(x)^2)/N
function ss($vals){
  if(!is_array($vals)) return false;
	$vals = numeric_only($vals);
  foreach($vals as $val){
    $vals_sq[] = pow($val,2);
  }
  return array_sum($vals_sq)-pow(array_sum($vals),2)/count($vals);
}
/**
 * smerodatna odchylka
 *
 * @param array $vals
 * @return double/array
 */
function stdev($vals){
  if(!is_array($vals)) return false;
	$vals = numeric_only($vals);
	if(is_array(reset($vals))){
    $stdev = array();
    foreach ($vals as $key => $val) {
    	$stdev[$key]=stdev($val);
    }
    return $stdev;
	} else {
    if(count($vals)<2)
      return false;
    else
      return sqrt(ss($vals)/(count($vals)-1));
	}
}


/**
 * stredni chyba prumeru
 *
 * @param array $vals
 * @return double/array
 */
function stderr($vals){
  if(!is_array($vals)) return false;
	$vals = numeric_only($vals);
	if(is_array(reset($vals))){
	  $stderr = array();
	  foreach ($vals as $key => $val) {
	   $stderr[$key]=stderr($val);
	  }
	  return $stderr;
	} else {
    return stdev($vals)/sqrt(count($vals));
	}
}
/**
 * vrati array s hodnotami od start do end vcetne s krokem step
 *
 * @param int $start
 * @param int $end
 * @param int $step
 * @return array
 */
function arr_series($start,$end,$step=1){
  $arr = array();
  for($i=$start;$i<=$end;$i+=$step){
    $arr[]=$i;
  }
  return $arr;
}
function ranks($data){
   $buffer = array();
   foreach($data as $group=>$group_data){
     foreach($group_data as $case=>$val) {
       $buffer["$group|$case"] = $val;
     }
   }
   asort($buffer);
   $buffer_ranks = array();
   $rank = 1;
   $last_val = false;
   $last_rank = false;
   $last_key = false;
   $pocet_stejnych= 0;
   $stejne = array();
   foreach($buffer as $key=>$val){
     $buffer_ranks[$key]=$rank;
     if($val == $last_val){
       if(empty($stejne)){
         $stejne[$last_key] = $last_rank;
       }
       $stejne[$key]=$rank;
     } elseif(!empty($stejne)) {
         $rank_average = average($stejne);
         foreach ($stejne as $key=>$nic){
           $buffer_ranks[$key]=$rank_average;
         }
       $stejne = array();
     }
     $last_key = $key;
     $last_val = $val;
     $last_rank = $rank;
     $rank++;
   }

   $out = array();

   foreach($buffer_ranks as $key=>$rank){
     list($group,$case) = explode("|",$key);
     $out[$group][$case]=$rank;
   }
   return $out;
}
function kruskal_wallis($data){
  $N=0;
  $SUMA = 0;
  foreach($data as $group=>$group_data){
    $N+=count($group_data);
    $SUMA += pow(array_sum($group_data),2)/count($group_data);
  }
  $H = 12/($N*($N+1))*$SUMA - 3*($N+1);
  return $H;
}
function degrees_of_freedom($data){
  return count($data)-1; // pocet skupin   -1
}
function chisquare($H,$df){
  // prvni hodnota je pro p<0.01, druha p<0.05, treti p<0.10
  $kriticke_hodnoty=array(
    1=>array(6.63,3.84,2.706),
    2=>array(9.21,5.99,4.605),
    3=>array(11.34,7.81,6.251),
    4=>array(13.28,9.49,7.779),
    5=>array(15.09,11.07,9.236),
    6=>array(16.81,12.59,10.645),
    7=>array(18.48,14.07,12.017),
    8=>array(20.09,15.51,13.362),
    9=>array(21.67,16.92,14.684),
    10=>array(23.21,18.31,15.987)
  );
  if($df>10) return false; // pro to nemame tabulku
  if($H>=$kriticke_hodnoty[$df][0]){
    return "***";
  } elseif($H>=$kriticke_hodnoty[$df][1]){
    return "**";
  } elseif($H>=$kriticke_hodnoty[$df][2]){
    return "*";
  } else {
    return "ns";
  }
}


/**
 * median
 * z http://www.ajdesigner.com/php_code_statistics/median.php
 *
 * @param array $a
 * @return float
 */
function median($a){
  //variable and initializations
  $the_median = 0.0;
  $index_1 = 0;
  $index_2 = 0;

  //sort the array
  sort($a);

  //count the number of elements
  $number_of_elements = count($a);

  //determine if odd or even
  $odd = $number_of_elements % 2;

  //odd take the middle number
  if ($odd == 1)
  {
    //determine the middle
    $the_index_1 = $number_of_elements / 2;

    //cast to integer
    settype($the_index_1, "integer");

    //calculate the median
    $the_median = $a[$the_index_1];
  }
  else
  {
    //determine the two middle numbers
    $the_index_1 = $number_of_elements / 2;
    $the_index_2 = $the_index_1 - 1;

    //calculate the median
    $the_median = ($a[$the_index_1] + $a[$the_index_2]) / 2;
  }

  return $the_median;
}
/*
$v = array(1,2,3,4,5);
echo sprintf("%f %f %f %f ",sum($v),average($v),stdev($v),stderr($v));
*/

/**
 * signum
 *
 * @param number $a
 * @return -1,0,1
 */
function sgn($a){
	if($a>0) return 1;
	if($a<0) return -1;
	return 0;
}
?>
