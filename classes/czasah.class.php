<?php


/**
 * @author kamil
 * trida na ulozeni a poskytovani dat o jednom zasahu 
 * bude vracet ruzne druhy chyb
 *
 *
 */
class CZasah  {
  /**
   * @var CPoint
   */
  public $hit;
  /**
   * @var CPoint
   */
  public $goal;
  /**
   * @var CPoint
   */
  public $start;
  public $goaltype;
  public $goalname;
  public $namegroup; // skupina jmen napriklad M1 u MWM2 nebo M1M2M3 u ivety
  /**
   * pozice znacek (bez startu)
   * @var array
   */
  public $markpositions;
  public $markname;
  /**
   * pozice startu ve stupnich
   * @var deg
   */
  public $startposition; 
  
  private $dist ; // posledni spocitana vzdalenostni chyba
  private $goal_array; // pole ruznych cilu, pokud jich je vic
  public $settings; // nastaveni tohoto jednoho trialu , napriklad exclude
	/**
	 * @param CPoint $hit
     * @param CPoint $goal nebo array
     * @param char $goaltype
     * @param string $goalname
	 * @param CPoint $start
	 * @param array $markpositions
	 * @param string $markname
	 */
	function __construct($hit,$goal,$goaltype='g',$goalname='',$start=false,$markpositions=false,$markname=false,$startposition=false, $settings=false) {
    $this->hit = $hit;
    $this->start = $start;
    if(is_array($goal)){ // muze byt vic cil, ten prvni v poli je pak default
    	$this->goal_array = $goal;
    	$this->goal = reset($goal);
    } else {
      $this->goal = $goal;
      $this->goal_array = array($goal);
    }
    $this->goaltype = $goaltype;
    
    if(strpos($goalname,"|")!==false){
    	list($this->goalname,$this->namegroup) = explode("|",$goalname); // kamil 13.9.2010
    } else {
    	$this->goalname = $goalname;
    	$this->namegroup = "";
    }
    $this->markpositions = $markpositions;
    $this->markname = $markname;
    $this->startposition = $startposition;
    $this->settings = $settings;
	}
	/**
	 * nastavi goal z pole nekolika, ktery se bude pouzivat pro dalsi vystupy
	 * @param mixed $name
	 */
	public function SetGoal($name=false){
		if(!$name || empty($this->goal_array[$name])){
			$this->goal = reset($this->goal_array);
		} else {
			$this->goal = $this->goal_array[$name];
		}
		unset($this->dist); 
	}
	/**
	 * vrati pole cilu
	 */
	public function GoalArray(){
		return $this->goal_array;
	}
	/**
	 * vraci vzdalenostni chybu
	 * @return float
	 */
	public function distfromgoal(){
		if(isset($this->dist)){
			return $this->dist;
		} else {
		  return $this->dist = $this->goal->Distance($this->hit);
		} 
	}
	public function mindist(){
		return $this->start->Distance($this->hit); 
		// pocitam minimalni vzdalenost k posledni pozici tracku, ne k cili 
	}
	public function angleerr(){
		return $this->hit->AngleDiff($this->goal,$this->start);
	}
	public function disterr(){
		return $this->hit->Distance($this->start) - $this->goal->Distance($this->start);
	}
	public function distsymmetry(){
		return min($this->distfromgoal(),$this->goal->Distance($this->get_symetry_point()));
	}
	public function distfromcenter(){
		return $this->hit->Distance()-$this->goal->Distance();
	}
	public function angleerrfromcenter(){
		return $this->hit->AngleDiff($this->goal);
	}
	public function angleerrfromcenter_cm(){
		// - cast kruhu na urovni odhadu cile
		return $this->hit->Distance()*deg2rad($this->hit->AngleDiff($this->goal));
	}
	/**
	 * vraci vzdalenosti chybu k prvni znacce (startu pokud neni znacka)	 
	 * @return float
	 */
	public function distfromreference(){
		/* @var $reference CPoint */
		$reference = $this->get_reference();
		return $this->hit->Distance($reference)-$this->goal->Distance($reference);
	}
	/**
   * vraci uhlovou chybu k prvni znacce (startu pokud neni znacka)   
   * @return deg
   */
	public function angleerrfromreference(){
		/* @var $reference CPoint */
		$reference = $this->get_reference();
		return $this->hit->AngleDiff($this->goal,$reference);
	}
	/**
	 * beeline/path lenght - opsal jsem z jednoho posteru od mallota
	 * @param double $path_length
	 * @return double
	 */
	public function path_efficiency($path_length){
		if($path_length ==0) return 0;
		else return  $this->mindist()/$path_length;
	}
  public function measure($measure){
     $defined_measures = array('disterr','distfromgoal','mindist','angleerr','disterr','distsymmetry',
          'distfromcenter','angleerrfromcenter','angleerrfromcenter_cm','distfromreference','angleerrfromreference');
     if(in_array($measure,$defined_measures)){
     	 return $this->$measure();
     } else {
     	 return 0;
     }
  }
	
/**
   * vrati bod prevraceny podle spojnice stredu areny a znacky/startu
   * 12.7.2010 - prevzato z drf2track.class
   * 
   * @return CPoint
   */
  private function get_symetry_point(){
    $reference = $this->get_reference();
    $line = new CLine();
    $line->DefineByPoints($reference,new Cpoint(0,0));
    return $line->Symmetry($this->goal);
  }
  /**
   * vrati pozici prvni znacky/starty (pokud neni znacka)
   * pokud REFERENCEBYDIST==1, vrati pozici znacky nejblizsi k cili (pokud je jich vic)
   * @return CPoint
   */
  private function get_reference(){
  	// chci ziskat aktualni pozici znacky nebo startu (pokud neni definovana zadna znacka)
    // pokud bude vic znacek, zatim beru jen tu s nejnizsim cislem (v poli trackvars->segmentsvar)
    // vratim bod[x,y]
    if(is_array($this->markpositions) && count($this->markpositions)>0){
      
      if(count($this->markpositions) > 1 && REFERENCEBYDIST){ //11.7.2012 - vratim pozici znacky nejblizsi k cili
	      $vzdalenosti = array();
	      foreach($this->markpositions as $markname=>$mark){
	      	$pozice = new CPoint();
	      	$pozice->DefineByAngleDistance($mark['laser'], ARENAR);
	      	$vzdalenosti[$markname]=$this->goal->Distance($pozice);
	      }
	      asort($vzdalenosti);
	      $jmena = array_keys($vzdalenosti);
	      $markname = $jmena[0];
	      $uhel = $this->markpositions[$markname]['laser'];
      } else {
      	$mark = reset($this->markpositions);// beru prvni znacku v poradi
	    if(!isset($mark['laser'])) return $this->hit;
	    $uhel = $mark['laser'];
      }
      
    } else {
      // zadna znacka, udelam symetrii podle pozice startu
      $uhel = $this->startposition; 
    } 
    
    $pozice = new CPoint();
    $pozice->DefineByAngleDistance($uhel, ARENAR);
    return $pozice;
  }
  /**
   * vrati pole meritek z retezce napr distfromgoal,distfromreference
   * @param string $measures
   * @return array
   */
  static function MeasureArr($measures){
  	$m = explode(",",$measures);
  	foreach($m as &$m_item) $m_item = trim($m_item); // oriznu v polozkach whitespace
  	return $m;
  }
  /**
   * vrati prvni z rady measures z retezce oddeleneho cerkami
   * @param string $measures
   * @return string
   */
  static function MeasureFirst($measures){
    if(strpos($measures,",")!==false){ // pokud vice measures oddelenych carkama, beru jen prvni - 28.7.2010
         $m = explode(",",$measures);
         $measures = trim(reset($m));
    }
    return $measures;
  }
  /**
   * vraci true, pokud zasah k vyrazeni
   * @return boolean
   */
  public function Excluded(){
  	return $this->settings==EXCLUDEDTRIAL;
  }
	
	
}


?>