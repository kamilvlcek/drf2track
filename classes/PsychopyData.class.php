<?php

class PsychopyData {
	private $fc;
	private $filename;
	public $isopen=false;
	private $factors = array(); // nazvy faktoru a jejich ciselne hodnoty pro matlab
	private $keyvalues = array();  // nazvy stlacenych klaves a jejich ciselne hodnoty pro matlab 
	protected $col_factors = array(); //  cisla sloucu ve kterych jsou faktory
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
	 			$this->ColFactors($factor_names, $vals);
			} elseif(!empty($vals[$col_keys])) { // hodnoty z dalsich radku
				// prevedu stlacenou klavesu na cislo pokud je treba
				$keyval = $this->GetKeyValue($matlab, $vals, $col_keys);
				$odpovedi[$lineno]=array('keys'=>$keyval, 'corr'=>$this->GetCorr($vals,$col_corr,$col_keys),'rt'=>(double) $vals[$col_rt]);
				$faktory[$lineno]=array();
				foreach($this->col_factors as $fn=>$cl){ //fn = factor name, cl = columnt
					$faktory[$lineno][$fn] = $this->GetFactorValue($matlab, $fn, $vals, $cl);
				}
			}
 		}
 		return array($odpovedi,$faktory);
 	}
 	/**
 	 * ulozi cisla sloupce pro faktory; funkce aby mohla byt overloaded
 	 * @param array $factor_names jmena sloupcu 
 	 * @param array $vals hodnoty sloupcu z aktualniho radku
 	 * @return mixed
 	 */
 	protected function ColFactors($factor_names,$vals){
 		foreach($factor_names as $fn){ //fn = factor name
 			if( ($col = array_search($fn, $vals)) !==false)
 				$col_factors[$fn]= $col;
 		}
 		$this->col_factors = $col_factors;
 	}
 	/**
 	 * vrati hodnotu stlacene klavesy; funkce aby mohla byt overloaded
 	 * @param bool $matlab jestli vracet int
 	 * @param array $vals hodnoty sloupcu z aktualniho radku
 	 * @param int $col_keys sloupec kde je stlacena klavesa
 	 * @return number|unknown
 	 */
 	protected function GetKeyValue($matlab,$vals,$col_keys){
 		if($matlab && isset($this->keyvalues[$vals[$col_keys]]))
 			return (int) $this->keyvalues[$vals[$col_keys]];
 		else
 			return $vals[$col_keys];	
 	}
 	/**
 	 * vraci spravnost odpovedi; funkce aby mohla byt overloaded
 	 * @param array $vals hodnoty sloupcu z aktualniho radku
 	 * @param int $col_corr cislo sloupce s originalni spravnosti
 	 * @param int $col_keys cislo sloupce s odpovedi
 	 * @return int
 	 */
 	protected function GetCorr($vals,$col_corr,$col_keys){
 		return (int) $vals[$col_corr]; // $col_keys se tady nepouziva, ale v pretizenych funkcich ano
 	}
 	/**
 	 * vraci hodnotu faktoru; funkce aby mohla byt overloaded
 	 * @param bool $matlab jestli vracet int
 	 * @param string $fn jmeno faktoru
 	 * @param array $vals hodnoty sloupcu z aktualniho radku
 	 * @param int $cl sloupec kde je faktor
 	 * @return number|unknown
 	 */
 	protected function GetFactorValue($matlab,$fn,$vals,$cl){
 		if(is_numeric($vals[$cl])){
			return (int) $vals[$cl];
		} elseif($matlab && isset($this->factors[$fn][$vals[$cl]])) {
			return (int) $this->factors[$fn][$vals[$cl]];
		} else {
			return $vals[$cl];
		}
 	}
	
}

?>