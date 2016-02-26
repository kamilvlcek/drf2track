<?php

class PsychopyData {
	private $fc;
	private $filename;
	public $isopen=false;
	private $factors = array(); // nazvy faktoru a jejich ciselne hodnoty pro matlab
	private $keyvalues = array();  // nazvy stlacenych klaves jejich ciselne hodnoty pro matlab 
	function __construct($filename){
		$this->filename = $filename;
		if(file_exists($filename)){
			$this->fc = file($filename);
			$this->isopen = true;
		}
	}
	public function SetFactors($factors){
		$this->factors = $factors;
	}
	public function SetKeyValues($keyvalues){
		$this->keyvalues = $keyvalues;
	}
 	public function Odpovedi($name,$factor_names){
 		$col_keys = false;
 		$col_corr = false;
 		$col_rt = false;
 		$col_factors = array(); // cisla sloucu ve kterych jsou faktory
 		
 		$odpovedi = array(); // tam budu sbirat data o odpovedich
 		$faktory = array();  // hodnoty faktoru u jednotlivych odpovedi
 		
 		foreach($this->fc as $lineno=>$line){
 			$vals = explode(",", $line);
			if($lineno==0){ // jmena sloupcu
	 			$col_keys = array_search($name.".keys", $vals);
	 			$col_corr = array_search($name.".corr", $vals);
	 			$col_rt = array_search($name.".rt", $vals);
	 			if($col_corr==false || $col_keys==false || $col_rt== false){
	 				return false;
	 			}
	 			foreach($factor_names as $fn){
	 				if( ($col = array_search($fn, $vals)) !=false)
	 					$col_factors[$fn]= $col;
	 			}
			} elseif(!empty($vals[$col_keys])) {
				$odpovedi[$lineno]=array('keys'=>$vals[$col_keys], 'corr'=>(int) $vals[$col_corr],'rt'=>(double) $vals[$col_rt]);
				$faktory[$lineno]=array();
				foreach($col_factors as $fn=>$cl){
					if(is_numeric($vals[$cl])){
						$faktory[$lineno][$fn]= (int) $vals[$cl];
					} else {
						$faktory[$lineno][$fn]= $vals[$cl];
					}
				}
			}
 		}
 		return array($odpovedi,$faktory);
 	}
 	
 	
	
}

?>