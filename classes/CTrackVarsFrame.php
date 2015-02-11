<?php
/**
 * trida ma ukladat informace vazane k jednomu framu
 * 
 * @author kamil vlcek
 *
 */

require_once 'classes/CPoint.class.php';

class CTrackVarsFrame {
	public $name; // jmeno framu
	// FRAME SPECIFIC
  // TRACK
  public $roomxy; // aktualni souradnice tracku - pro kazdy bod pri Next
  public $arenaxy;
  public $eyexyz; // aktualni souradnice eyelink
  
  public $anglesubj; // aktualni natoceni subjektu
  public $viewxy; // aktualni bod pohledu
  
  public $roomxylast;
  public $roomxyarr; // pole vsech roomxy
  public $anglearr; // natoceni subjektu
  public $viewxyarr; // bod pohledu subjektu
  public $roomxyavg; // zprumerovane pole vsech roomxt
  public $roomxyline;
  public $roomxymax, $roomxymin; // maximum a minimum souradnic zjistene z tracku
  
  public $arenaxyarr;
  public $arenaxyavg; // vraci se v Next, ale zatim se nikde neplni
  
  public $movedxyarr; // souradnice bodu, kdy se clovek pohnul ze startu
  //SECTOR 
  //TODO AFrame - pridat podporu arenaframu do zakladni tridy ctrackvars
  public  $cvar; // stredovy uhel mista - ve stupnich
  public  $cnamevar; // jmeno mista
  public  $r0var; // vzdalenost mista od stredu - procenta polomeru
  public  $r;     // - neni CVarArr, prumer mista - procenta polomeru
  public  $reltovar; // relativne k cemu 1-n, goal no = trial
  public  $cxyvar; // [x,y] souradnice cile - aktualne se nepouziva
  public  $savoidvar; // jestli je sector avoid nebo preference
 
  // MARKS - CUES
  /**
   * @var deg 0-360
   */
  public  $laservar;  //  laser 0 - 360
  public  $startPointvar;  //  0-1 - 1= je to start
  public  $segmentsvar;  //  segments 0-3; 0 znamena, ze sviti nesviti zadny segment (jen start)
  public  $marknamevar; // jmena znacek, ktera se pak zobrazi v obrazku 25.8.2009
  public  $markxyvar; // pozice znacky xy - 13.11.2012 kvuli analyze pohledu na presnou pozici znacky
  
  public $keyfoundbeepstopvar = array(); // 0 1 nebo 2, oznacuje funkci klavesy f - 9.11.2012 kvuli martinovym datum
  public $keyfoundvar = array(); // klavesa f
  public function __construct($name){
  	$this->name = $name;
  	$this->cvar = array();
    $this->r0var = array();
    $this->r = array();
    $this->cnamevar = array();
    $this->savoidvar = array();
    $this->reltovar = array();
    $this->cxyvar = array();
    $this->keyfoundbeepstopvar = array(); // 0 1 nebo 2, oznacuje funkci klavesy f - 9.11.2012 kvuli martinovym datum
    $this->keyfoundvar = array(); // klavesa f
  }
  /**
   * vlozi dalsi cile; vola se z CTrackVars.SaveSectorDef
   * @param deg $angle
   * @param int $dist vzdalenost od stredu - procenta polomeru
   * @param int $r polomer cile procenta polomeru
   * @param string $name
   * @param int $avoid 0=preference 1=avoidance
   * @param int $relto cislo phase, ke ktere relativni
   */
  public function AddGoal_Angle($track,$phase,$angle,$dist,$r,$name,$avoid,$relto,$keyfound,$keyfoundbeepstop){
  	$this->GoalCheck($track,$phase);
  	
  	$this->cvar[$track][$phase]->add($angle);
    $this->r0var[$track][$phase]->add($dist);
  	$this->r[$track][$phase] = $r; // zatim muze byt je jedna hodnota pro fazi (14.4.2008)
    $this->cnamevar[$track][$phase]->add($name);
    $this->savoidvar[$track][$phase]->add($avoid);
    $this->reltovar[$track][$phase]->add($relto); // relativne k cemu

    $cxy = angledist2xy(deg2rad($angle),$dist/100*ARENAR);// vystup z funkce ma y=+10 nahore a -10 dole
    $cxy[1]=-$cxy[1];// empiricky zjisteno, potrebuju aby y souradnise se zvetsovala dolu
    $this->cxyvar[$track][$phase]->add($cxy); // to je pole o dvou prvcich, 0 a 1 (x a y), snad to bude fungovat
    $this->keyfoundvar[$track][$phase]->add($keyfound);
    $this->keyfoundbeepstopvar[$track][$phase]->add($keyfoundbeepstop); // 12.11.2012
  }
  /** 
   * vola se ze SpanavVars.SaveSectorDef
   * @param int $track
   * @param int $phase
   * @param [x,y] $goalxy max je 140,140
   * @param int $r polomer cile procenta polomeru
   * @param string $name 
   * @param int $avoid 0=preference 1=avoidance
   * @param int $relto cislo phase, ke ktere relativni
   */
  public function AddGoal_XY($track,$phase,$goalxy,$r,$name,$avoid,$relto){
  	$this->GoalCheck($track,$phase);
  	
  	//$goalxy = array($center->x,$center->y);
  	$this->cxyvar[$track][$phase]->add($goalxy);
  	$this->r[$track][$phase] = $r;
  	if(is_multi_array($goalxy)){ // 20.10.2014 kvuli probetrialum ve vFGN
  		$cvar = array();
  		$r0var = array();
  		foreach($goalxy as $key=>$xy){
  			$cvar[$key] = rad2deg(angletocenter($xy,array(0,0)));
  			$r0var[$key] = distance($xy,array(0,0))/140*100;
  		}
  	} else { // i tady je goalx array, ale jen s hodnotama [x,y]
  		$cvar = rad2deg(angletocenter($goalxy,array(0,0)));
  		$r0var = distance($goalxy,array(0,0))/140*100; //TODO 140 fixni hodnotu odstranit
  	}
    $this->cvar[$track][$phase]->add($cvar);
    $this->cnamevar[$track][$phase]->add($name); // jmeno bude cislo cile v seznamu pozic
    $this->r0var[$track][$phase]->add($r0var); 
    $this->savoidvar[$track][$phase]->add($avoid); // je to preference sector
    $this->reltovar[$track][$phase]->add($relto); // neni relativni k nicemu
  }
  /**
   * @param int $track
   * @param int $phase
   * @param int $cue
   * @param deg $laser 0-360
   * @param int $segments
   * @param int $startPoint
   * @param string $name
   * @param CPoint $xy pokud zadam false (BVA Data), nebude se ukladat presna pozice znacky
   */
  public function AddCue($track,$phase,$cue,$laser,$segments,$startPoint,$name,$xy){
  	   $this->CueCheck($track,$phase,$cue);
  	   $this->laservar[$track][$phase][$cue]->add($laser);
       $this->segmentsvar[$track][$phase][$cue]->add($segments);
       $this->startPointvar[$track][$phase][$cue]->add($startPoint);
       $this->marknamevar[$track][$phase][$cue]->add($name); // 24.5.2010
       if($xy) $this->markxyvar[$track][$phase][$cue]->add($xy); // 13.11.2012
  }
 
  /**
   * overi a kdyz tak zavede promennou CVarArr 
   * @param int $track
   * @param int $phase
   */
  private function GoalCheck($track,$phase){
  	if(!isset($this->cvar[$track][$phase]))   $this->cvar[$track][$phase]=new CVarArr();
    if(!isset($this->r0var[$track][$phase]))  $this->r0var[$track][$phase]=new CVarArr();
  	if(!isset($this->cnamevar[$track][$phase]))   $this->cnamevar[$track][$phase]=new CVarArr();
    if(!isset($this->savoidvar[$track][$phase])) $this->savoidvar[$track][$phase]=new CVarArr();
    if(!isset($this->reltovar[$track][$phase])) $this->reltovar[$track][$phase]=new CVarArr();
    if(!isset($this->cxyvar[$track][$phase])) $this->cxyvar[$track][$phase]=new CVarArr();
    if(!isset($this->keyfoundvar[$track][$phase])) $this->keyfoundvar[$track][$phase]=new CVarArr();
    if(!isset($this->keyfoundbeepstopvar[$track][$phase])) $this->keyfoundbeepstopvar[$track][$phase]=new CVarArr();
  }
  /**
   * overi a kdyz tak zavede promennou CVarArr
   * @param int $track
   * @param int$phase
   * @param int$cue
   */
  private function CueCheck($track,$phase,$cue){
  	 if(!isset($this->laservar[$track][$phase][$cue]))        $this->laservar[$track][$phase][$cue]=new CVarArr();
     if(!isset($this->segmentsvar[$track][$phase][$cue]))     $this->segmentsvar[$track][$phase][$cue]=new CVarArr();
     if(!isset($this->startPointvar[$track][$phase][$cue]))   $this->startPointvar[$track][$phase][$cue]=new CVarArr();
     if(!isset($this->marknamevar[$track][$phase][$cue]))   $this->marknamevar[$track][$phase][$cue]=new CVarArr();
     if(!isset($this->markxyvar[$track][$phase][$cue]))   		$this->markxyvar[$track][$phase][$cue]=new CVarArr();
  }
}
/**
 * vrati true, pokud array obsahuje jiny array
 * @param array $arr
 * @return boolean
 */
function is_multi_array($arr){
	// http://stackoverflow.com/questions/145337/checking-if-array-is-multidimensional-or-not
	$rv = array_filter($arr,'is_array');
    if(count($rv)>0) return true;
    return false;
}
?>.