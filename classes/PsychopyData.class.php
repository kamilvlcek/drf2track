<?php

class PsychopyData {
	private $fc;
	private $filename;
	public $isopen=false;
	private $factors = array(); // nazvy faktoru a jejich ciselne hodnoty pro matlab
	private $keyvalues = array();  // nazvy stlacenych klaves a jejich ciselne hodnoty pro matlab 
	function __construct($filename){
		$this->filename = $filename;
		if(file_exists($filename)){
			$this->fc = file($filename);
			$this->isopen = true;
		}
	}
	/**
	 * ulozi ciselne hodnoty faktoru
	 * @param array $factors [name=>val]
	 */
	public function SetFactors($factors){
		$this->factors = $factors;
	}
	/**
	 * ulozi ciselne hodnoty stlacenych klaves
	 * @param array $keyvalues [name=>val]
	 */
	public function SetKeyValues($keyvalues){
		$this->keyvalues = $keyvalues;
	}
 	/**
 	 * vrati pole odpovedi a souvisejicich hodnot faktoru
 	 * @param string $name jmena sloupcu, kde jsou odpovedi
 	 * @param array $factor_names jmena sloupcu s faktory
 	 * @param bool $matlab jesti ma byt vystup tabulka vhodna pro matlab 
 	 * @return array[odpovedi,faktory]
 	 */
 	public function Odpovedi($name,$factor_names,$matlab = false){
 		$col_keys = false;
 		$col_corr = false;
 		$col_rt = false;
 		$col_factors = array(); // cisla sloucu ve kterych jsou faktory
 		
 		$odpovedi = array(); // tam budu sbirat data o odpovedich
 		$faktory = array();  // hodnoty faktoru u jednotlivych odpovedi
 		
 		foreach($this->fc as $lineno=>$line){
 			$vals = explode(",", $line);
			if($lineno==0){ // nactu jmena sloupcu z prvniho radku
	 			$col_keys = array_search($name.".keys", $vals);
	 			$col_corr = array_search($name.".corr", $vals);
	 			$col_rt = array_search($name.".rt", $vals);
	 			if($col_corr==false || $col_keys==false || $col_rt== false){
	 				return false;
	 			}
	 			foreach($factor_names as $fn){ //fn = factor name
	 				if( ($col = array_search($fn, $vals)) !=false)
	 					$col_factors[$fn]= $col;
	 			}
			} elseif(!empty($vals[$col_keys])) { // hodnoty z dalsich radku
				// prevedu stlacenou klavesu na cislo pokud je treba
				$keyval = $matlab && isset($this->keyvalues[$vals[$col_keys]]) ? (int) $this->keyvalues[$vals[$col_keys]] : $vals[$col_keys];
				$odpovedi[$lineno]=array('keys'=>$keyval, 'corr'=>(int) $vals[$col_corr],'rt'=>(double) $vals[$col_rt]);
				$faktory[$lineno]=array();
				foreach($col_factors as $fn=>$cl){ //fn = factor name, cl = columnt
					if(is_numeric($vals[$cl])){
						$faktory[$lineno][$fn]= (int) $vals[$cl];
					} elseif($matlab && isset($this->factors[$fn][$vals[$cl]])) {
						$faktory[$lineno][$fn] = (int) $this->factors[$fn][$vals[$cl]];
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