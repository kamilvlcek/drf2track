<?php 

/**
 * vrati cislo mista z jeho nazvu 
 * napriklad ArenaPlaceGoal na 2
 * @param string $misto
 * @return int
 */
function misto2matlab($misto){
  global $cislamist;
  return $cislamist[$misto];
}
/**
 * vrati ceske jmeno podle nazvu mista 
 * napr ArenaPlaceGoal na ARENA1
 * @param string $misto
 * @return string
 */
function misto2cz($misto){
  global $ceskajmena;
  return $ceskajmena[$misto];
}
/**
 * vraci ceske jmeno podle kodu mista
 * @param int $placecode
 * @return $string
 */
function placecode2cz($placecode){
	global $cislamist;
	global $ceskajmena;
	$place = array_search($placecode,$cislamist);
	return $ceskajmena[$place];
}
/**
 * vraci true pokud cil je v arenaframu
 * @param string $goalname
 * @return boolean
 */
function arenaframe($goalname,$frames){
	return in_array($goalname,$frames->arenaplaces);
}
/**
 * vraci pozici cile 
 * pokud zadam parametr uhel areny, vraci otociny cil v arenaframu
 * 
 * @param string $goalname
 * @param Frames $frames
 * @return CPoint
 */
function goalposition($goalname,$frames,$arenaangle=false){
  global $souradnicemist;
  global $stredareny;
  $stred = $stredareny; //new CPoint(28,-44); // xmax = 582.45 xmin=-542.45 ymax = 558.45 ymin = -566.45 
  $goal = new CPoint($souradnicemist[$goalname]);
  if(in_array($goalname,$frames->roomplaces)){
    return $goal;
  } elseif(in_array($goalname,$frames->arenaplaces)){
  	if($arenaangle!==false){
  		return $goal->Rotate($arenaangle,$stred); //kamil 20.10.2011 - arenaangle
  	} else {
    	return $goal; 
  	}
  }
}
/**
 * udela a ulozi rychlostni histogramy otaceni
 * 
 */
function histogramy(){
  global $AngleHistoT;
  global $tabledir;
  $parametry = array(array("min"=>0.02,"step"=>0.08), array("min"=>0.02,"step"=>0.12),
    array("min"=>0,"step"=>0.2),
    array("min"=>0,"step"=>0.4),
    array("min"=>0,"step"=>0.5));
  foreach($parametry as $p){
    $tableT = $AngleHistoT->TableData($p);
    $ms = $p['step']*1000;
    $tableT->SaveAll(false,"$tabledir/tablehistoT$ms.xls",0);
    $tableT->SaveAll(true,"$tabledir/tablehistoT$ms.txt",1);  
  }
  
}

/**
 * ulozi pocty trialu pro jednotliva mista do 2 souborù
 * @param array $trialcount
 * @param string $tabledir
 */
function trial_count_save($trialcount,$tabledir){
  $CTable_trials = new TableFile("$tabledir/aapptrials.xls");
  $CTable_trials->AddColumns(array("personno","phase","filename","placecode","misto","trials"));
  
  $CTable_trials_sum = new TableFile("$tabledir/aapptrials_sum.xls");
  $CTable_trials_sum->AddColumns(array("personno",
            "ARENA1 3","ARENA2 3","ROOM1 4","ROOM2 4",
            "ARENA1 5","ARENA2 5","ROOM1 5","ROOM2 5",
            "ARENA1 6","ARENA2 6","ROOM1 6","ROOM2 6",
  			"ARENA1 7","ARENA2 7","ROOM1 7","ROOM2 7",
  			"ARENA1 8","ARENA2 8","ROOM1 8","ROOM2 8"
      ));
  $faze_seznam = array(3,4,5,6,7,8);
  
  // $trial_count[basename($filename)][$cislocloveka][$CSpanavName->Faze()][misto2matlab($mistolast)]++;
  foreach ($trialcount as $cislocloveka => $cdata) {
			$sumdata = array();		
  			foreach($faze_seznam as $f){		// defaultni hodnoty
  				$sumdata[$f] = array(1=>0,0,0,0); //pocty trialu ve fazich a mistech
  			}
			foreach ($cdata as $faze => $fazedata) {
				foreach( $fazedata as $filename=>$fdata){
					foreach ($fdata as $placecode => $trials) {
						$CTable_trials->AddRow(array($cislocloveka,$faze,$filename,$placecode,placecode2cz($placecode),$trials));
						$sumdata[$faze][$placecode]=$trials;
					}
				}
			}
			$CTable_trials_sum->AddToRow(array($cislocloveka));
			$CTable_trials_sum->AddToRow(array($sumdata[3][2],$sumdata[3][1],$sumdata[4][4],$sumdata[4][3]));
			$CTable_trials_sum->AddToRow(array($sumdata[5][2],$sumdata[5][1],$sumdata[5][4],$sumdata[5][3]));
			$CTable_trials_sum->AddToRow(array($sumdata[6][2],$sumdata[6][1],$sumdata[6][4],$sumdata[6][3]));
			$CTable_trials_sum->AddToRow(array($sumdata[7][2],$sumdata[7][1],$sumdata[7][4],$sumdata[7][3]));
			$CTable_trials_sum->AddToRow(array($sumdata[8][2],$sumdata[8][1],$sumdata[8][4],$sumdata[8][3]));
			$CTable_trials_sum->AddRow();
			
		
	}
	$CTable_trials->SaveAll(true);
	$CTable_trials_sum->SaveAll(true);
	

}

/**
 * vraci cislo pojmenovani mista ABCD 1234
 * @param char $mistoname
 * @return int
 * @since 3.7.2013
 */
function mistoname2matlab($mistoname){
	$mista = array ("A"=>1, "B"=> 2, "C"=>3, "D"=>4, "E"=> 4 /*adela 3.7.2013, misto D mela E*/);
	if(isset($mista[$mistoname])) {
		return $mista[$mistoname];
	} else {
		return 0;
	}
}

function beep($n){
	  for($i=0;$i<$n;$i++){
	      echo chr(7); // beep
	      usleep(300000); // 0.3 s
	  }
	}
?>