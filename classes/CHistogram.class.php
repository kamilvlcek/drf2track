<?php
require_once 'classes/TableFile.class.php';
require_once 'classes/TableData.interface.php';

define("HISTOGRAM_DELTA",0.00001);
/**
 * trida je jednorozmerny histogram - vysledkem je array frekvenci
 * @author kamil
 *
 */
class Histogram implements TableData {
	// zadane hodnoty
	protected $min = false;
	protected $max = false;
	protected $count = false;
	protected $step = false;
	protected $circular = false;
	protected $max_estimate = false; // max mam jen odhadnute, chci ho jeste spocitat presne, az budu mit hodnoty
	
	//vypoctene hodnoty
	protected $data = array(); // ulozene cetnosti
	protected $pocet_hodnot = 0; // celkovy pocet vlozenych hodnot
	protected $limits = array(); // hranice jednotlivych binu
	protected $hodnoty= array(); // ulozene vsechny zadane hodnoty
	protected $compute_minmax = false; // jestli se maj vypocitat max a min z vlozenych hodnot
	protected $frequencies = false; // pole s ulozenyma relativnima frekvencema
	/**
	 * jestli se maji pocitat limitni hodnoty jako stredy intervalu; 
	 * pokud ano, odecte se pred zpracovanim od kazde hodnoty polovina intervalu
	 * @var bool
	 * @since 15.11.2012
	 */
	protected $limitmiddle = false; 
	/**
	 * jestli se limit ma zaradit na horni konec intervalu (nebo na dolni)
	 * true= intervaly budou napr <0;1] (jedna patri do intervalu 0-1), false= [0;1>
	 * @var bool
	 */
	protected $limitup = false; 
	
	/**
	 * v parametrech se muze zadat min, max, count step circular
	 * muzu nezadat parametry, ale pak nemuzu pocitat frakvence. Muzu jen sbirat hodnoty
	 * 
	 * @param array $parameters
	 */
	function __construct($parameters=false) {
	   $this->SetParameters($parameters);
	   /*if($min!==false && $max!==false){
	   	$this->freq_init($count,$min,$max);
	   } else {
	   	$this->compute_minmax = true;
	   }*/
	}
	public function SetParameters($parameters){
		$this->min= $this->max = $this->count  =false;
		$this->step = $this->circular = $this->max_estimate =false;
		// frekvence se musi spocitat znova
		$this->limits=array(); $this->pocet_hodnot=0; $this->data=array() ; $this->frequencies=false;
		
		if(is_array($parameters)){
			 if(isset($parameters['min']))   $this->min = $parameters['min'];
			 if(isset($parameters['max']))   $this->max = $parameters['max'];
			 if(isset($parameters['count'])) $this->count = $parameters['count'];
			 if(isset($parameters['step']))  $this->step = $parameters['step'];
			 if(isset($parameters['circular']))  $this->circular = $parameters['circular']==1;
			 if(isset($parameters['middle'])) $this->limitmiddle = $parameters['middle']==1;
			 if(isset($parameters['limitup'])) $this->limitup = $parameters['limitup']==1;
			 if($this->min!==false && $this->max!==false && $this->count>0){
			   $this->step = ($this->max - $this->min) /$this->count;
			 } elseif($this->min!==false && $this->step!==false && $this->max===false){
			 	 $this->max = $this->min + 1000 * $this->step; // potrebuju nejak spocitat ty ranges, doufam, ze vic nez 1000 hodnot v histogramu mit nebudu
			 	 $this->max_estimate = true;
			 }
	   }
		
	}
  /**
   * ulozi jedno cislo, nebo pole cisel
   * prijima i pole cisel
   * @param double/array $val
   * @param bool $store
   */
  public function AddValue($val){
    if(is_array($val)){ // pole hodnot
      foreach($val as $v) $this->AddValue($v);
    } else {
        $this->hodnoty[]=$val;
    }
  }
  /**
   * vlozi cely rozsah hodnot od $range[0] do $range[1], do kazdeho binu histogramu jednou
   * @param array $range
   */
  public function AddRange($range){
    $hodnoty = self::ChopRange($range,$this->min,$this->max,$this->step,$this->circular,$this->limitmiddle,$this->limitup);
    $this->AddValue($hodnoty); // muze to byt i pole hodnot
  }
  /**
   * vrati pole hodnot pro vlozeni do histogramu od range[0] do $range[1]
   * 
   * @param array $range
   * @param float $min
   * @param float $max
   * @param int $step
   * @param bool $circular
   * @return array
   */
  static function ChopRange($range,$min,$max,$step,$circular,$limitmiddle,$limitup){
  	if($min===false || $max===false || $step===false){
  		echo "ERR: min: $min, max: $max, step: $step\n";
  	}
  	$hodnoty = array();
    $limits = self::limits_stepminmax($step,$min,$max);
    $limit_arr = array();// do ktereho limitu hodnota prijde
    $x = $range[0]; // minimum 
    if($circular){
    	$over_max_go = $range[0]>$range[1]; // jestli pujdu tim range pres maximum histogramu
        $over_max = false; // jestli x prelezlo pres maximum u cirkularnich dat 
	    while( $x<=$range[1] || (!$over_max && $over_max_go)){ 
	        $hodnoty[]=$x;
	        $limit_arr[]=self::array_find_region($limits,$x,$limitmiddle,$step,$limitup);
		    $x+=$step;
	        if($x>=$max && !$over_max && $over_max_go){
	          $x = $x-$max+$min;
	          $over_max = true;
	        } 
	    }
    } else {
	    while($x<=$range[1]){ // maximum
	      $hodnoty[]=$x;
	      $limit_arr[]=self::array_find_region($limits,$x,$limitmiddle,$step,$limitup);
	      if($x>=$max) break; // hodnoty vetsi nez max histogramu chci nacist jen jednou 
	      $x+=$step;
	    }
    }
    // v nekterych pripadech musim jeste pridat maximalni hodnotu range. Ale abych vedel kdy
    // musim overit, jestli tam prislusny vychazejici bin uz nemam
    $limit = self::array_find_region($limits,$range[1],$limitmiddle,$step,$limitup);
    if(!in_array($limit,$limit_arr)){ 
    	$hodnoty[]=$range[1];
    }
    return $hodnoty;
  }
  /**
   * vraci frekvence v jednotlivych binech 0-1
   * @return array
   */
  public function Frequencies(){
    $this->freq_compute();
    $freq = array();
    foreach($this->data as $i=>$f){  
    	if($this->circular && ($i==0 || $i==count($this->data) )) {
    	     continue; 
    	     // pokud jde o cicularni data a tudiz min je totez co max (0 = 360 deg), 
    	     // prvni a posledni udaj v rade nedavaji vyznam
    	} else { 
          $freq[$i]=$f/$this->pocet_hodnot;
    	}
    }
    $this->frequencies= $freq;
    return $freq;
  }
  /**
   * vraci tabulku s frekvencema
   * @return Table
   */
  public function FreqTable(){
  	if(!$this->frequencies) $this->Frequencies();
  	$table = new Table();
  	foreach($this->frequencies as $n=>$freq){
  		if(($x = $this->limitx($n))!==false){ // false to bude u nejvyssiho intervalu, ten se vrace nebude - neni pro nej jasny limit
  			$table->AddColumnData(array($x,$freq),$n);
  		}
  	}
  	return $table;
  }
  /**
   * vrati bin, do ktereho bude vlozena v histogramu prislusna hodnota $value
   * zatim funguje, jen kdyz je definovano min, max, step
   * 
   * @param double $value
   * @return int/bool
   */
  public function Bin($value){
  	if($this->min!==false && $this->step!==false && $this->max===false){
  		// zatim mi staci tohle, kvuli casovym binum, kdyztak pridam dalsi
  		return self::array_find_region(self::limits_stepminmax($this->step,$this->min,$this->max),$value,$this->limitmiddle,$this->step,$this->limitup);
  	} else {
  		// neni definovano, co potrebuju k vypoctu
  		return false;
  	}
  }
	/**
	 * vytvori seznam limitu podle jeji poctu, minima a maxima
	 * inicializuje data histogramu
	 * 
	 * @param double $min
	 * @param double $max
	 * @param int $count
	 * @return array
	 */
	static function limits_countminmax($count,$min,$max){
		 $limits = array();
		 $step = ($max - $min) /$count;
     for($i = $min; $i <=$max;$i += $step)    $limits[] = $i;
     //self::data_init($limits); // aby mi volal lokalni funkci
     return $limits;
	}

	/**
	 * @param double $step
	 * @param int $count
	 * @param double $min
	 * @return array
	 */
	static function limits_stepcountmin($step,$count,$min){
		$limits = array();
		for($i=$min;$i<=$min+$step*$count;$i+=$step)	$limits[] = $i;
		//self::data_init($limits);
		return $limits;
	}
	/**
	 * @param double $step
	 * @param double $min
	 * @param double $max
	 * @return array
	 */
	static function limits_stepminmax($step,$min,$max){
		$limits = array();
     for($i = $min; $i <=$max;$i += $step)    $limits[] = $i;
     //self::data_init($limits); // aby mi volal lokalni funkci
     return $limits;
	}
  /**
   * naplni data nulama
   * o jednou vic nez je limitu
   * @param array $limits
   */
  protected function data_init($limits){
    $this->data = array_fill(0,count($limits)+1,0);
  }

	/**
	 * vrati prvni index pole, jehoz hodnota je vetsi nez $val
	 * predpoklada serazene pole
	 * pokud zadnou hodnotu v poli vetsi nez $val nenajde, vrati pocet prvku pole
	 * 
	 * @param array $array
	 * @param double $value
	 * @param bool $limitmiddle - jestli ma byt limit v puli intervalu - 15.11.2012
	 * @param float $step
	 * @return int
	 */
	static function array_find_region($array,$value,$limitmiddle=false,$step=false,$limitup=false){
	   if($limitmiddle) $value = $value - $step/2; // 15.11.2012 - chci mi napr 180 ve stredu intervalu histogramu
	   for($i=0;$i<count($array);$i++){
	   	if($limitup) {
	   		if($value < $array[$i] || abs($value-$array[$i])<HISTOGRAM_DELTA ) return $i;
	   		// float se nema porovnavat primo na rovnost
	   	} else {
	   		if($value < $array[$i])  return $i;
	   	}
        
        }
       return count($array);
	}
  /**
   * vrati minimum z pole cisel
   * definovano kvuli pretizeni
   * @param array $array
   * @return double
   */
  protected function min_i($array){ 
    // potrebuju to tady definovat a pouzit, abych to mohl v dedici tride zmenit a stale volat freq_compute
    $arr2 = array(); 
    foreach($array as $key =>$val)  $arr2[]=$val; //$max0[]=max($val);
    return min($arr2);
  }
  
  /**
   * vrati maximum z pole cisel
   * definovano kvuli pretizeni
   * @param array $array
   * @return double
   */
  protected function max_i($array){
    $arr2 = array();
    foreach($array as $key =>$val)  $arr2[]=$val; //$max0[]=max($val);
    return max($arr2);
  }
  /**
   * naplni limity podle drive zadanych parametru
   * @return bool
   */
  protected function set_limits(){
	if($this->count!==false && $this->step!==false && $this->min!==false) {
      $this->limits = self::limits_stepcountmin($this->step,$this->count,$this->min);
      $this->max = max($this->limits);
      return true;
    } elseif($this->count!==false && $this->min!==false && $this->max!==false && !$this->max_estimate){
      $this->limits = self::limits_countminmax($this->count,$this->min,$this->max);
      $this->step = $this->limits[1]-$this->limits[0];
      return true;
    } elseif($this->count!==false){
      if($this->min===false) $this->min = $this->min_i($this->hodnoty);
      if($this->max===false) $this->max = $this->max_i($this->hodnoty);
      $this->limits = self::limits_countminmax($this->count,$this->min,$this->max);
      $this->step = $this->limits[1]-$this->limits[0];
      return true;
    } elseif($this->step!==false && $this->min!==false) {
    	if($this->max===false || $this->max_estimate) $this->max = $this->max_i($this->hodnoty);
    	$this->count = intval(($this->max-$this->min)/$this->step) + 1;
    	$this->limits = self::limits_stepcountmin($this->step,$this->count,$this->min);
      return true;
    } else {
    	return false;
    }
  }
	/**
	 * nejdriv spocita limity a inicializuje data histogramu
	 * prevede drive ulozene hodnoty do histogramu
	 */
	protected function freq_compute(){
    if($this->set_limits()){
    	$this->data_init($this->limits);
	    foreach($this->hodnoty as $val){
	      
	      $this->data[self::array_find_region($this->limits,$val,$this->limitmiddle,$this->step,$this->limitup)]++;
	      $this->pocet_hodnot++;
	    }
    }
	}
	/**
	 * vraci hodnotu $nteho limitu
	 * @param int $n
	 * @return double|bool
	 */
	protected function limitx($n){
		return isset($this->limits[$n])?$this->limits[$n]:false;
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
	 * importuje data z jineho histogramu
	 * @see TableData::ImportVals()
	 * @param Histogram $object
	 */
	public function ImportVals($object){
		if(isset($object->hodnoty)){
			$this->AddValue($object->hodnoty);
		}
	}
	static function Factory($parameters){
		return new Histogram($parameters);
	}
	/** 
	 * je implementuje funkci z tabledata rozhrani
	 * @param array $val 
	 * @see TableData::AddVal()
	 */
	public function AddVal($val){
		$this->AddValue($val);
	}

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * trida na dvourozmerny histogram - vysledek je matice frekvenci
 * @author kamil
 *
 */
class Histogram2D extends Histogram {
	protected $countY = false; // pocet hodnot ve druhe svisle dimenzi histogramu
	protected $minY = false;
	protected $maxY =false; // svisla druha dimezne histogramu 
	protected $stepY = false;
	protected $limitsY = array();
	protected $compute_minmaxY = false; // jestli se maj vypocitat max a min z vlozenych hodnot
	protected $circularY = false;
	
	protected $maxY_estimate = false; // max mam jen odhadnute, chci ho jeste spocitat presne, az budu mit hodnoty
	/**
	 * jestli se maji pocitat limitni hodnoty jako stredy intervalu; 
	 * pokud ano, odecte se pred zpracovanim od kazde hodnoty polovina intervalu
	 * @var bool
	 * @since 15.11.2012
	 */
	protected $limitmiddleY = false; 
	/**
	 * seznam diskretnich=ruznych hodnot Y (jako klice pole)
	 * @var array
	 */
	protected $hodnotyY = array();
	/**
	 * jestli se maji frekvence pocitat v ramci kazdho intervalu x (true) nebo celkove (false) 
	 * @var bool
	 * @since 22.11.2012
	 */
	protected $pocetYuse = false;
	/**
	 * jestli se limit ma zaradit na horni konec intervalu (nebo na dolni)
	 * true= intervaly budou napr <0;1] (jedna patri do intervalu 0-1), false= [0;1>
	 * @var bool
	 */
	protected $limitupY = false; 
	
	/**
	 * @param int $countX
	 * @param int $countY
	 * @param array $sizeX (minX,maxX)
	 * @param array $sizeY (minY,maxY)
	 */
	public  function __construct($parametersX,$parametersY) {
    if(is_array($parametersX) && is_array($parametersY)){
		  // inicializace prvni dimenze histogramu
			parent::__construct($parametersX);
	     
			// druha Y dimenze histogramu - bude v $this->data jako druhy rad pole
			if(isset($parametersY['min']))   $this->minY = $parametersY['min'];
      if(isset($parametersY['max']))   $this->maxY = $parametersY['max'];
      if(isset($parametersY['count'])) $this->countY = $parametersY['count'];
      if(isset($parametersY['step']))  $this->stepY = $parametersY['step']; 
      if(isset($parametersY['circular']))  $this->circularY = $parametersY['circular']==1;
      if(isset($parametersY['middle'])) $this->limitmiddleY = $parametersY['middle']==1;
      if(isset($parametersY['pocethodnotY'])) $this->pocetYuse = $parametersY['pocethodnotY']==1; //22.11.2012
      if(isset($parametersY['limitupY'])) $this->limitupY = $parametersY['limitupY']==1;
      if($this->minY!==false && $this->maxY!==false && $this->countY>0){
          $this->stepY = ($this->maxY - $this->minY) /$this->countY;
      } elseif($this->minY!==false && $this->stepY!==false && $this->maxY===false){
         $this->maxY = $this->minY + 1000 * $this->stepY; // potrebuju nejak spocitat ty ranges, doufam, ze vic nez 1000 hodnot v histogramu mit nebudu
         $this->maxY_estimate = true;
      }
    }
	}
 /**
   * pridani hodnoty do 2D histogramu
   * @param double $valX
   * @param double $valY
   * @param bool $store
   */
  public function AddValue($valX,$valY){
    $this->hodnoty[]=array($valX,$valY);
    $this->hodnotyY[(string)$valY]=1; //indexem pole muze byt int nebo string. float se prevede na int
  }
  /**
   * vymaze vsechny vlozene hodnoty
   * @since 10.11.2014
   */
  public function Reset(){
  	$this->hodnoty = array();
  	$this->hodnotyY = array();
  }
  
  /* (non-PHPdoc)
   * @see Histogram::AddRange()
   */
  public function AddRange($valX,$valY){
    if(is_array($valX)){
  	   $hodnotyX = self::ChopRange($valX,$this->min,$this->max,$this->step,$this->circular,$this->limitmiddle,$this->limitup);
    } else {
    	 $hodnotyX = array($valX);
    }
    if(is_array($valY)){
    	$hodnotyY = self::ChopRange($valY,$this->minY,$this->maxY,$this->stepY,$this->circularY,$this->limitmiddleY,$this->limitupY);
    } else {
    	$hodnotyY = array($valY);
    }
    
    foreach($hodnotyY as $hY){
    	foreach($hodnotyX as $hX){
    		$this->AddValue($hX,$hY);
    	}
    }
  }
  
  /**
   * @see Histogram#Frequencies()
   */
  public function Frequencies(){
    $this->freq_compute();
    
    $freq = array();
    foreach($this->data as $X => $valY){
      // pokud jde o cicularni data a tudiz min je totez co max (0 = 360 deg), 
      // prvni a posledni udaj v rade nedavaji vyznam
      if($this->circular && ($X == 0 || $X == count($this->data)-1)) continue; 
      foreach($valY as $Y=>$val) {
      	if($this->circularY && ($Y==0 || $Y == count($valY)-1)) continue; 
      	if($this->pocetYuse) {
      		$freq[$X][$Y]= $val/array_sum($this->hodnotyY);
      	} else {
      		$freq[$X][$Y]=$val/$this->pocet_hodnot;
      	}
      }
    }
    $this->frequencies = $freq;
    return $freq;
  }
  /**
   * vraci tabulku frekvenci jako objekt Table 
   * @return Table
   */
  public function FreqTable(){
  	if($this->frequencies === false)
  		$this->Frequencies();
  	$table = new Table;
  	foreach($this->frequencies as $x=>$xdata){
  		$ykeys = array();
  		foreach(array_keys($xdata) as $y=>$ydata)
  			$ykeys[$y]=isset($this->limitsY[$y])?$this->limitsY[$y]:"over";
  		
  		if($table->ColumnCount()==0) $table->AddColumnData($ykeys,"limits");
  		$table->AddColumnData($xdata,isset($this->limits[$x])?$this->limits[$x]:"over");
  	}
  	return $table;
  }
  /**
   * vrati bin, do ktereho bude vlozena v histogramu prislusna hodnota $value
   * zatim funguje, jen kdyz je definovano min, max, step
   * 
   * @param int $rozmer 0=x nebo 1=y
   * @param double $value
   * @return int/bool
   */
  public function Bin($rozmer,$value){
  	if($rozmer==0){
  		return parent::Bin($value);
  	} elseif ($rozmer == 1){
	  	if($this->minY!==false && $this->stepY!==false && $this->maxY!==false){
	      // zatim mi staci tohle, kvuli casovym binum, kdyztak pridam dalsi
	      return self::array_find_region(self::limits_stepminmax($this->stepY,$this->minY,$this->maxY),
	      		$value,$this->limitmiddleY,$this->stepY,$this->limitupY);
	    } else {
	      // neni definovano, co potrebuju k vypoctu
	      return false;
	    }
  	} else {
  		//  rozmer musi byt jen 0 (=x) nebo 1 (=y)
  		return false;
  	}
  }
	/**
	 * naplni data nulama, v rozmeru X i Y
	 * @param array $limitsX
	 * @param array $limitsY
	 */
	protected function data_init($limitsX,$limitsY){
		parent::data_init($limitsX);
		foreach($this->data as $x=>$dataY)
        $this->data[$x]=array_fill(0,count($limitsY)+1,0); 
    
	}

  /**
   * vrati minimum z dvourozmerneho pole cisel, prvni rozmer podle indexu
   * 
   * @param array $array
   * @return double
   */
	protected function min_i($array,$index=0){
	  $arr2 = array(); 
	  foreach($array as $key =>$val)  $arr2[]=$val[$index]; //$max0[]=max($val);
	  return min($arr2);
	}
	/**
   * vrati maximum z dvourozmerneho pole cisel, prvni rozmer podle indexu
   * 
   * @param array $array
   * @return double
   */
	protected function max_i($array,$index=0){
	  $arr2 = array();
	  foreach($array as $key =>$val)  $arr2[]=$val[$index]; //$max0[]=max($val);
	  return max($arr2);
	}
	/**
	 * naplni limity podle drive zadanych parametru
	 * @return bool
	 */
	protected function set_limits(){
		parent::set_limits();
	  if($this->countY!==false && $this->stepY!==false && $this->minY!==false){
      $this->limitsY = self::limits_stepcountmin($this->stepY,$this->countY,$this->minY);
      return true;
	  } elseif($this->countY!==false && $this->minY!==false && $this->maxY!==false && !$this->maxY_estimate) {
      $this->limitsY = self::limits_countminmax($this->countY,$this->minY,$this->maxY);
      return true;
	  } elseif($this->countY!==false){
      if($this->minY===false) $this->minY = $this->min_i($this->hodnoty,1);
      if($this->maxY===false) $this->maxY = $this->max_i($this->hodnoty,1);
      $this->limitsY = self::limits_countminmax($this->countY,$this->minY,$this->maxY);
      return true;
    } elseif($this->stepY!==false && $this->minY!==false) {
      if($this->maxY===false || $this->maxY_estimate) $this->maxY = $this->max_i($this->hodnoty,1);
      $this->countY = ($this->maxY-$this->minY)/$this->stepY + 1;
      $this->limitsY = self::limits_stepcountmin($this->stepY,$this->countY,$this->minY);
      return true;
    } else {
      return false;
    }
	}
	/**
	 * nastavi limity a s jejich pomoci spocita frekvence 
	 */
	protected function freq_compute(){
    if($this->set_limits()) {
    	$this->data_init($this->limits,$this->limitsY);
			foreach($this->hodnoty as $hodnota){
				$x = self::array_find_region($this->limits,$hodnota[0],$this->limitmiddle,$this->step,$this->limitup); //uhel
				$y = self::array_find_region($this->limitsY,$hodnota[1],$this->limitmiddleY,$this->stepY,$this->limitupY);// time
				$this->data[$x][$y]++;
	     		$this->pocet_hodnot++;
	     		//if(!isset($this->pocethodnotY[$x])) $this->pocethodnotY[$x]=1; else  $this->pocethodnotY[$x]++; //22.11.2012
			}
    }
	}
	

}

  
?>