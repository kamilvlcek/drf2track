<?php


/**
 * @author kamil
 * trida na ulozeni a poskytovani dat o jednom zasahu 
 * bude vracet ruzne druhy chyb
 *
 *
 */
class Zasah  {
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
  /**
   * pozice znacek (bez startu)
   * @var array
   */
  public $markpositions;
  public $markname;
  public $startposition; // deg
  
  private $dist ; // posledni spocitana vzdalenostni chyba
  private $goal_array; // pole ruznych cilu, pokud jich je vic
	/**
	 * @param CPoint $hit
   * @param CPoint $goal nebo array
   * @param char $goaltype
   * @param string $goalname
	 * @param CPoint $start
	 * @param array $markpositions
	 * @param string $markname
	 */
	function __construct($hit,$goal,$goaltype='g',$goalname='',$start=false,$markpositions=false,$markname=false,$startposition=false) {
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
    $this->goalname = $goalname;
    $this->markpositions = $markpositions;
    $this->markname = $markname;
    $this->startposition = $startposition;
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
		return $this->start->Distance($this->goal);
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
  public function measure($measure){
     $defined_measures = array('disterr','distfromgoal','mindist','angleerr','disterr','distsymmetry',
          'distfromcenter','angleerrfromcenter','angleerrfromcenter_cm');
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
    // chci ziskat aktualni pozici znacky nebo startu (pokud neni definovana zadna znacka)
    // pokud bude vic znacek, zatim beru jen tu s nejnizsim cislem (v poli trackvars->segmentsvar)
    // vratim bod[x,y]
    if(is_array($this->markpositions) && count($this->markpositions)>0){
    	$mark = reset($this->markpositions);// beru prvni znacku v poradi
    	if(!isset($mark['uhel'])) return $this->hit;
    	$uhel = $mark['laser'];
    } else {
    	// zadna znacka, udelam symetrii podle pozice startu
  	  $uhel = $this->startposition; 
    } 
    
    $pozice = new CPoint();
    $pozice->DefineByAngleDistance($uhel, ARENAR);
    $line = new CLine();
    $line->DefineByPoints($pozice,new Cpoint(0,0));
    return $line->Symmetry($cilxy);
  }
  
	
	
}


?>