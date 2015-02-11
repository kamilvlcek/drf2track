<?php

require_once 'includes/stat.inc.php';
class PxlabData {
	private $col_names;
	private $col_types;
	private $filename;
	public $error = false;
	private $delim = " ";
	private $fc;
	// typy sloupcu
	const INT=1;
	const FLOAT=2;
	const STRING=3;
	/**
	 * vrati jmeno souboru pxlab, zjistene z puvodniho souboru s priponou 001, 002 az 009 (dal nehleda)
	 * @param string $filename jmeno zakladniho souboru
	 * @param string $string definicni retezec na zacatku souboru
	 * @return string|boolean
	 */
	static function CheckFilename($filename,$string){
		$path_parts = pathinfo($filename);
		$found = FALSE;
		$i = 0;
		while($i<10 && !$found){
			$newfilename = $i==0? $filename : $path_parts['dirname']."\\".$path_parts['filename']."00".$i.".".$path_parts['extension'];
			if(file_exists($newfilename)){
				$fc = file_get_contents($newfilename);
				if(substr($fc, 0,strlen($string))==$string){
					$found= true;
				}
			}
			$i++;
		}
		if($found){
			return $newfilename;
		} else {
			return false;
		}
		
	}
	function __construct($filename,$columns){
		$this->filename = $filename;
		$this->col_names = array_keys($columns);
		$this->col_types = array_values($columns);
		if(!file_exists($filename)) {
			$this->error = true;
			return;
		} else {
			$this->fc = file($filename);
		}
	}
	/**
	 * vraci vyhodnoceni datoveho souboru (hits,misses aj, sensitivity, specificity)
	 * 16.7.2013 pridavam parametr col_time
	 * @param int $col_ok
	 * @param int $col_tip
	 * @param int $col_factor pokud se maji vysledky roztridit navic podle hodnot dalsiho sloupce 
	 * @param int $col_time sloupec casu odpovedi
	 * @return array
	 */
	public function RecognitionScores($col_ok,$col_tip, $col_factor=false, $col_time=false){
		$hits = 0;
		$false_alarms = 0;
		$true_false = 0;
		$misses = 0;
		$lines =0;
		$linesHits = array('hits'=>0,'true_false'=>0);
		$factor_vals = array();
		$casy = array();
		foreach($this->fc as $lineno=>$line){
			$cols = explode($this->delim,trim($line));
			$factor = ($col_factor!==false) ? $cols[$col_factor] : 0; // u menrot uhel
			if(count($cols)==count($this->col_names)){
				if(empty($factor_vals[$factor])){
					$factor_vals[$factor]=array("hits"=>0,"misses"=>0,"false_alarms"=>0,"true_false"=>0, "casy"=>array());
				} 
				$lines++;
				$spravne = (int) $cols[$col_ok];
				$response = (int) $cols[$col_tip];
				if($col_time!==false) {
					$factor_vals[$factor]["casy"][]= (double) $cols[$col_time];
					$casy[]=(double) $cols[$col_time];
				}
				if($spravne==1 && $response==1) { $hits++;  		$factor_vals[$factor]['hits']++; 		$linesHits['hits']++;  } //menrot vpravo
				if($spravne==1 && $response==0) {$misses++; 		$factor_vals[$factor]['misses']++;   	$linesHits['hits']++;  }
				if($spravne==0 && $response==1) {$false_alarms++; 	$factor_vals[$factor]['false_alarms']++;$linesHits['true_false']++;   } //menrot vlevo
				if($spravne==0 && $response==0) {$true_false++;		$factor_vals[$factor]['true_false']++;  $linesHits['true_false']++; 	}
			}
		}
		$sensitivity = ($hits+$misses==0)				?"":$hits/($hits+$misses); //=hit rate
		$specificity = ($true_false+$false_alarms==0)	?"":$true_false/($true_false+$false_alarms); //=false alarm rate
		foreach($factor_vals as $factor=>$vals){
			$factor_vals[$factor]['sensitivity'] = ($factor_vals[$factor]['hits']+$factor_vals[$factor]['misses']==0)			?"":$factor_vals[$factor]['hits']      /($factor_vals[$factor]['hits']+$factor_vals[$factor]['misses']);
			$factor_vals[$factor]['specificity'] = ($factor_vals[$factor]['true_false']+$factor_vals[$factor]['false_alarms']==0)	?"":$factor_vals[$factor]['true_false']/($factor_vals[$factor]['true_false']+$factor_vals[$factor]['false_alarms']);
			$factor_vals[$factor]['casy_prumer'] = average($factor_vals[$factor]["casy"]);
		}
		 // viz tutorial http://wise.cgu.edu/sdtmod/overview.asp
		return array("hits"=>$hits,"misses"=>$misses,"false_alarms"=>$false_alarms,"true_false"=>$true_false,
			"trials"=>$lines,"sensitivity"=>$sensitivity,"specificity"=>$specificity,"casy_prumer"=>average($casy),
			"trialsHits"=>$linesHits['hits'],"trialsFA"=>$linesHits['true_false'],
			"factor_vals"=>$factor_vals);
		
	} 
	/**
	 * zmenit typy prvku pole podle col_types na int nebo double
	 * @param unknown_type $datarow
	 * @return number
	 */
	private function FormatDataRow($datarow){
		foreach($datarow as $col=>$cell){
			if($this->col_types[$col]==PxlabData::INT){
				$datarow[$col] = (int) $cell;
			} elseif($this->col_types[$col]==PxlabData::FLOAT){
				$datarow[$col] = (double) $cell;
			} 
		}
		return $datarow;
	}
	/**
	 * vytvori tabulky z vystupu pxlab
	 * @param array $datacols pole cisel sloupcu k exportu
	 * @param Table $CTable vystupni tabulka excel
	 * @param Table $CTable_matlab vystupni tabulka matlab
	 * @param array $matlabprevod - pole prevodu retezcu do cisel [sloupec][slovo]=>cislo 
	 */
	public function TableExport($datacols,&$CTable,&$CTable_matlab,$matlabprevod=false){
		$CTable->Erase();
		$CTable_matlab->Erase();
		foreach($datacols as $col){
			$CTable->AddColumns(array($this->col_names[$col]));
			$CTable_matlab->AddColumns(array($this->col_names[$col]));
		}
		$CTable_matlab->setMatlab();
		
		foreach($this->fc as $lineno=>$line){
			$datarow= explode($this->delim,trim($line));
			if(count($datarow)==count($this->col_names)){ // kratsi radky neberu
				$datarow = $this->FormatDataRow($datarow);
				$data = array();
				foreach($datacols as $col){
					$data[]=$datarow[$col];
				}
				$CTable->AddRow($data);
				// 14.10.2013 - nektere retezce chci pro matlab prevest do cisel
				if($matlabprevod && is_array($matlabprevod)){
					foreach($matlabprevod as $sloupec=>$prevod){
						if(isset( $prevod[$data[$sloupec]])){
							$data[$sloupec] = $prevod[$data[$sloupec]];
						}
					}
				}
				$CTable_matlab->AddRow($data);
			}
		}
	}
	/**
	 * vrati odpovedi jednoho cloveka, roztridene podle obrazku
	 * @param int $col_name sloupec se jmenem obrazku
	 * @param int $col_ok sloupec se spravnou odpovedi
	 * @param int $col_tip sloupec s odpovedi cloveka
	 * @param int $col_time sloupec s casem odpovedi nebo false
	 * @return array  
	 */
	public function Polozky($col_name, $col_ok,$col_tip, $col_time=false){
		$names = array ();
		$spravne = array();
		$tip = array();
		$casy = array();
		foreach($this->fc as $lineno=>$line){
			$cols = explode($this->delim,trim($line));
			if(count($cols)>=$col_tip && $cols[1]!="Celkem"){
				$names[]=$cols[$col_name];
				$spravne[]=(double) $cols[$col_ok];
				$tip[]=(double) $cols[$col_tip];
				if($col_time){
					$casy[]= (double) $cols[$col_time];
				}
			}
				
		}
		return array("names"=>$names,"spravne"=>$spravne,"tip"=>$tip,"casy"=>$casy);
	}
}

?>