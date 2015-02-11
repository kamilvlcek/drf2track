<?php

require_once ('classes\CHistogram.class.php');

/** 
 * @author kamil
 * Trida na sumu hodnot Y rozdelenych do binu podle hodnot X
 * 
 * 
 */
class HistoSum extends Histogram implements TableData {
	
	/**
	 * v parametrech se muze zadat min, max, count step circular
	 * @param array $parameters
	 */
	public function __construct($parameters=false) {
		parent::__construct ( $parameters );
		
	}
	
	/**
	 * x hodnoty tvori hranice, y hodnoty se secitaji 
	 * 
	 * @param double $valx
	 * @param double $valy
	 */
	public function AddValue($valx,$valy){
       $this->hodnoty[]=array($valx,$valy);
	}
		
	/**
	 * nejdriv spocita limity a inicializuje data histogramu
	 * prevede drive ulozene hodnoty do histogramu
	 */
	protected function freq_compute(){
    	if($this->set_limits()){
    		$this->data_init($this->limits);
	    	foreach($this->hodnoty as $val){
	      		$this->data[self::array_find_region($this->limits,$val[0])]+= $val[1];
	      		$this->pocet_hodnot++;
	    	}
    	}
	}
	
 /**
   * vrati minimum z pole hodnot $prvek[0]
   * definovano kvuli pretizeni
   * @param array $array
   * @return double
   */
  protected function min_i($array){ 
    // potrebuju to tady definovat a pouzit, abych to mohl v dedici tride zmenit a stale volat freq_compute
    $arr2 = array(); 
    foreach($array as $key =>$val)  $arr2[]=$val[0]; //$max0[]=max($val);
    return min($arr2);
  }
  
  /**
   * vrati maximum z pole hodnot $prvek[0]
   * definovano kvuli pretizeni
   * @param array $array
   * @return double
   */
  protected function max_i($array){
    $arr2 = array();
    foreach($array as $key =>$val)  $arr2[]=$val[0]; //$max0[]=max($val);
    return max($arr2);
  }
  
  /**
   * importuje hodnoty z jineho histogramu
   * ostatni parametry zustavaji
   * @see TableData::ImportVals()
   * @param HistoSum $histo
   */
  public function ImportVals($histo){
  /*	if(!isset($this->min))   $this->min = $histo->min;
	if(!isset($this->max))   $this->max = $histo->max;
	if(!isset($this->count)) $this->count = $histo->count;
	if(!isset($this->step))  $this->step = $histo->step;
	if(!isset($this->circular))  $this->circular = $histo->circular;*/
  	foreach ($histo->hodnoty as $h){
  		$this->AddValue($h[0], $h[1]);
  	}
  }
  
	/**
	 * interface pro tabulkova data objektu
	 * @see TableData::TableData()
	 * @return Table
	 */
	public function TableData() {
		return $this->FreqTable();
	}
	/**
	   * vraci novy objekt teto tridy
	   * vola se pomoci call_user_func(array($classname,'Factory')), kde je mozne specifikovat jmeno tridy
	   * @return HistoSum
	   */
	  static function Factory($parameters){
	    return new HistoSum($parameters);
	  }
	  
	  /** musim implementovat i kdyz je v predkovi?
	   * @see Histogram::SetParameters()
	   */
	  public function SetParameters($parameters){
	  	parent:: SetParameters($parameters);
	  }
	  
	  /**
	   * implementuje rozhrani TableData
	   * @param array $val 
	   * @see TableData::AddVal()
	   */
	  public function AddVal($val){
	  	 $this->AddValue($val[0], $val[1]);
	  }
	  

  
  

	
}

?>
