<?php
if(!defined('SPANAVCFG')){
define('SPANAVCFG',1);
require_once('includes/point.php');
require_once 'classes/CPoint.class.php';
require_once 'classes/CPoint3D.class.php';

// Test-PsuAVCR_04_30mA_cil.ut2
// vytvoreno  kamil 27.5.2009 18:55:29
/**
 * @desc trida ukladaji informace souvisejici s jednim framem room nebo arena
 * @author kamil
 *
 */
class SpaNavCfg_frame {
	public $marks_x, $marks_y;
	public $goals; // goals[$track][$name][$trial] =new CPoint((int)$x,(int)$y); 
	public $starts; //starts[$track][$name]=array((int)$x,(int)$y);
	public $marks; //marks[$track][$trial][$name] = array((int)$x,(int)$y);

	/**
	 * [track][trial] CPoint
	 * @var array
	*/
	public $sekv_goals;
    /**
     * [track][trial] string
     * @var array 
     */
    public $sekv_goal_names;
  /**
   * ulozi pozici a jmeno cile do sekv_goals a sekv_goal_names
   * @param int $track
   * @param int $trial
   * @param string $name aimname jmeno cile
   * @param CPoint $position
   */
  public function ImportAim($track,$trial,$name,$position){
      $this->sekv_goals[$track][$trial]=clone $position;
      $this->sekv_goal_names[$track][$trial]=$name;
  }
  /**
   * @param int $track
   * @param int $trial
   * @param string $name
   * @param CPoint $position
   */
  public function ImportMark($track,$trial,$name,$position){
  	$this->marks[$track][$trial][$name]= clone $position;
  }
  /**
   * importuje znacky z jineho framu, pred tim je pripadne otoci o dany uhle
   * @param int $track
   * @param int $trial
   * @param SpaNavCfg_frame $spanav_frame
   * @param deg $rotation
   * @param CPoint $center
   */
  public function ImportMarksFrame($track,$trial,$spanav_frame,$rotation=false,$center=false){
  	if(empty($spanav_frame->marks[$track][$trial])) return false;
  	foreach($spanav_frame->marks[$track][$trial] as $name=>$position){
  		/* @var $position CPoint */
  		$position_new = clone $position;
  		if($rotation && $center) $position_new->Rotate($rotation,$center);
  		$this->ImportMark($track,$trial,$name,$position_new);
  	}
  }
  public function ClearMarks($track){
  	unset($this->marks[$track]); // 3.7.2012 - smazu jen znacky z aktualniho tracku
  }
}
class SpaNavFrame_Attached {
	public $last='-'; // true, false nebo -
	public $current='-'; // true false nebo -
	public $last_valid=false; // jen true nebo false
	public $current_valid=false;
	/**
	 * aktualizuje frame cile z tracku
	 * @param string $frame PrefAttached true/false
	 */
	public function Add($frame){
		$this->last = $this->current;
		$this->last_valid = $this->current_valid;
		$this->current = strtolower(trim($frame));
		if($this->IsValidFrame()){
			$this->current_valid = $this->current;
		}
	}
	/**
	 * vraci true, pokud ted zrovna je cil attached
	 * @return bool
	 */
	public function Attached(){
		return $this->current_valid=='true';
	}
	
	/**
	 * vraci true, pokud last cil attached (v predchozim radku)
	 * @return bool
	 */
	public function Attached_last(){
		return $this->last_valid=='true';
	}
	
	/**
	 * vraci true, pokud je aktualni attached true,nebo fals
	 * to znamena, pokud je zrovna aktivni nejaky cil
	 * @return bool
	 */
	public function IsValidFrame(){
		return in_array($this->current,array("true","false"));
	}
}
class SpaNavCfg {
  public $radius_to_goal;
  public $sekv_starts;
  public $sekv_marks;
  
  public $radius_arena;
  public $radius_goal=GOALRADIUS; // POLOMER CILE // iveta schizo 125UU, cognmap asi 100UU polomer //do 22.7.2013 byl 300
  
  private $marks_index;
  private $goals_index;
  private $start_index;
  
  private $markaim;
  public $keytocues = KEYTOSTART; // KLAVESA NA STARTU
  public $keytonext = KEYTONEXT; // KLAVESA PRO DALSI TRIAL
  public $keytopoint = KEYTOPOINT; // KLAVESA PRO UKAZANI SMERU NA CIL - predpokladam, ze ukazuje jeste na startu - od te doby pocitam cas - 23.3.2012
  private $probetrial_length = PROBETRIALLENGTH; // 10.4.2012 pocet vterin probetrialu
  public $framestosec = 3.5; // POCET FRAMU (RADKU) ZA SEKUNDU
  /**
   * polomer znacky, 140
   * @var int
   */
  public $markradius = 140; 
  /**
   * z pozice (vyska) znacky; nastavi se v ImportMark
   * @var float
   */
  private $zpos_mark;
  private $zpoz_aim;

  public $marks_x, $marks_y;
  public $goals; 
  public $starts;
  public $marks;
  public $arenapositions;

  private $arenaminx, $arenaminy, $arenamaxx, $arenamaxy;
  
  /**
   * roomframe
   * @var SpaNavCfg_frame
   */
  public $RF;
   /**
   * arenaframe
   * @var SpaNavCfg_frame
   */
  public $AF;
  
  /**
   * jestli byly znacky naimportovany na zacatkou souboru
   * kdyz se pak narazi na position changed, tak se smaze puvodni import a takhle promenna se nastavi na false
   * @var bool
   */
  private $marks_imported_beginning; 
  //private $aims_imported_beginning;
  
  /**
   * jestli se za dosazeni cile povazuje vstup do cile; prebira hodnotu z GOALBYENTRANCE a rozcleni ji po trialech
   * @var array
   */
  private $goalbyentrance;
  
  function SpaNavCfg($filename){
    $this->marks_index = $this->goals_index = $this->start_index = 0;
    $this->RF = new SpaNavCfg_frame();
    if(ARENAFRAME) $this->AF = new SpaNavCfg_frame();
    $this->SetGoalbyEntrance();
  }

  public function ArenaMinX($track){
  	if(empty($this->arenaminx[$track])) {
  		$this->arenaminx[$track] =  min($this->marks_x[$track]);
  		dp($this->arenaminx[$track],"arenaminx spocitano z pozic znacek - track $track");
  	}
    return $this->arenaminx[$track];
    //return -3500; //-3317; // minimalni hodnota tracku
  }
  public function ArenaMaxX($track){
   if(empty($this->arenamaxx[$track])) {
  		$this->arenamaxx[$track] =  max($this->marks_x[$track]);
  		dp($this->arenamaxx[$track],"arenamaxx spocitano z pozic znacek - track $track");
  	}
    return $this->arenamaxx[$track];
    //return 3500; //3355;
  }
  public function ArenaMinY($track){
  	if(empty($this->arenaminy[$track])) {
  		$this->arenaminy[$track] =  min($this->marks_y[$track]);
  		dp($this->arenaminy[$track],"arenaminy spocitano z pozic znacek - track $track");
  	}
    return $this->arenaminy[$track];
    //return -3500; //-3427;
  }
  public function ArenaMaxY($track){
  	if(empty($this->arenamaxy[$track])) {
  		$this->arenamaxy[$track] = max($this->marks_y[$track]);
  		dp($this->arenamaxy[$track],"arenamaxy spocitano z pozic znacek - track $track");
  	}
    return $this->arenamaxy[$track];
    //return 3500;//3099;
  }
  /**
   * vrati pozici cile v tracku/trialu
   * @param int $track
   * @param int $trial
   * @param string $frame RF=roomframe, AF = arenaframe
   * @return CPoint
   */
  public function Goal($track,$trial,$frame="RF") {
  	if(!$this->FrameOK($frame)) return false;
  	if(isset($this->$frame->sekv_goals[$track][$trial])){
  		$goal =	$this->$frame->sekv_goals[$track][$trial];
  		  //tosize($this->sekv_goals[$track][$trial],
        /*  array($this->MarksMinX($track),$this->MarksMinY($track)),
          array($this->MarksMaxX($track),$this->MarksMaxY($track)),
          array($this->ArenaMinX($track),$this->ArenaMinY($track)),
          array($this->ArenaMaxX($track),$this->ArenaMaxY($track))
       );*/
       /*if( !is_a($goal,"CPoint") ){
       	 $goal = new CPoint($goal);
       }*/
       return $goal;
  	} else {
  		return false;
  	}
  	
  	/*while(!isset($this->goals[$track][$name])){ //[$trial] vyhozeno 11.6.2010
  		if(--$trial<0) return false; // kdyz nemam definovanou pozici cile pro tento pokus, tak 
  		// zkusim pro predchozi pokus. 
  		// zatim myslim, ze se to bude pouzivat jen ve variantach 1pozice cile pro cely track/pro kazdy trial jina pozice cile
  		// cile jsou pojmenovane, cili pokud se aktivuji jen ruzne cile v run
  	}*/
	    
  }
  /**
   * vrati jsmeno cile v tracku/trialu
   * 
   * @param int $track
   * @param int $trial
   * @param string $frame RF=roomframe, AF = arenaframe
   */
  public function GoalName($track,$trial,$frame="RF"){
  	if(!$this->FrameOK($frame)) return false;
  	if(isset($this->$frame->sekv_goal_names[$track][$trial])){
  		return $this->$frame->sekv_goal_names[$track][$trial];
  	} else {
  		return "-X-";
  	}
  }
  /**
   * vrati pole pozic cilu [track][trial]
   * 
   * @param string $frame RF=roomframe, AF = arenaframe
   * @return array
   */
  public function SekvGoals($frame="RF"){
  	if(!$this->FrameOK($frame)) return false;
  	$goals = array();
  	if(isset($this->$frame->sekv_goals)){
	  	foreach($this->$frame->sekv_goals as $track =>$trackdata){
	  		foreach($trackdata as $trial=>$goal){
	  			$goals[$track][$trial] = $this->Goal($track,$trial,$frame);
	  		}
	  	}
  	}
  	return $goals;
  }
  public function Mark($track,$n) {
  	if(!isset($this->RF->marks[$track][$n]))
  		return false;
    else
    	return tosize($this->RF->marks[$track][$n],
	        array($this->MarksMinX($track),$this->MarksMinY($track)),
	        array($this->MarksMaxX($track),$this->MarksMaxY($track)),
	        array($this->ArenaMinX($track),$this->ArenaMinY($track)),
	        array($this->ArenaMaxX($track),$this->ArenaMaxY($track))
     );
  }
  public function Start($n) {
  	if(!isset($this->starts[$n]))
  		return false;
    else
	    return tosize($this->starts[$n],
	        array($this->MarksMinX($track),$this->MarksMinY($track)),
	        array($this->MarksMaxX($track),$this->MarksMaxY($track)),
	        array($this->ArenaMinX($track),$this->ArenaMinY($track)),
	        array($this->ArenaMaxX($track),$this->ArenaMaxY($track))
	     );
  }
  /**
   * vraci klavesu mackanou pro zobrazeni cues - kdyz je clovek na startu
   *
   * @return char
   */
  public function KeyToCues(){
  	return $this->keytocues;
  }
  /**
   * vraci klavesu mackanou pro ukazani na cil - kdyz je clovek na startu;
   * 23.3.2012
   * 
   * @return char
   */
  public function KeyToPoint(){
  	return $this->keytopoint;
  }
  /**
   * vraci klavesu mackanou pro prechod na dalsi trial
   *
   * @return char
   */
  public function KeyToNext(){
  	return empty($this->keytonext)?false:$this->keytonext;
  }
  /**
   * vraci pocet framu za vterinu
   *
   * @return double
   */
  public function FramesToSec($frames = 0){
  	return $this->framestosec;
  }
  /**
   * vraci delku probetrialu nastavenou pomoci PROBETRIALLENGTH
   * @return int
   */
  public function ProbeLenght(){
    return intval($this->probetrial_length);
  }
  /**
   * nacte radek Aim position:, ktera je pred trackem v hlavicce
   * dostane radku az od dvoutecky dal
   * plni pozice cilu ktere se jmenuji AimXX do promenne $this->goals
   *
   *
   */
  public function ImportAims($track,$trial,$line){
  	$aims = explode("],",$line);
  	unset($this->goals[$track]);
  	foreach($aims as $aim){
  	  $aim = trim($aim);
  		if(!empty($aim)){ // posledni v rade byva empty
      	  list($name,$x,$y,$z)=$this->SplitMark($aim);
      		if($this->IsAim($name)){
      			// budu cile importovat dvojite - podle jmen a podle trialu. 
      			// pak pri hledani pozice cile, pokud bude definovan pro trial, tak ho vezmu, 
      			// . pokud ne, tak budu hledat podle jmena - v prvku default
      			$this->goals[$track][$name]['default']=new CPoint((int)$x,(int)$y); 
    			  $this->zpoz_aim = (int) $z;
    			  //$this->aims_imported_beginning = true;
      		}
  		}
  	}
  }
  /**
   * nacte radek hlavicky souboru 'Mark position:' , ktera je pred trackem v hlavicce
   * dostane radku az od dvoutecky dal
   * plni pozice znacek a startu ktere se jmenuji Mark a Start
   *
   */
  public function ImportMarks($track,$trial,$line){
  	$marks = explode("],",$line);
  	//unset($this->marks); // to nemuzu mazat, nastavuje se v ImportMark
  	unset($this->starts[$track]);
  	foreach($marks as $mark){
  	    $mark = trim($mark);
  	    if(!empty($mark)){
      	    list($name,$x,$y,$z)=$this->SplitMark($mark);
      		if($this->IsStart($name)){
      			$this->starts[$track][$name]=array((int)$x,(int)$y,(int) $z);
      			// pozice startu potrebuju na zjisteni velikosti areny
      		} elseif($this->IsMark($name)){
      			$this->RF->ImportMark($track,$trial,$name,new CPoint3D((int)$x,(int)$y, (int) $z));
      			$this->marks_imported_beginning = true;
      			// 16.3.2010 - budu to importovat, v Karlovych experimentech nejsou zmeny znacek pozdeji
      			// 25.5.2010 - kdyz ale pozdeji narazim na Position Changed, tak to zase smazu
      		}
  	    }
  	}
  	$this->MarksXY($track); // nacte znovu marks_x a marks_y a umozni podle novych hodnot pocitat velikost areny
  }

  /**
   * importuje novou zmenenou pozici znacky a cile
   * 'Position changed:' - vola se v prubehu tracku 
   * 
   * @param int $track
   * @param int $trial
   * @param string $line
   * @param float $arenaangle posledni natoceni areny
   */
  public function ImportMark($track,$trial, $line,$arenaangle){
  	list($name,$x,$y,$z)=$this->SplitMark($line);
  	$name = trim($name);
  	if($this->IsMark($name)){
      if($this->marks_imported_beginning) {
	      $this->RF->ClearMarks($track); // 25.5.2010
	      $this->marks_imported_beginning = false; // priste uz marks mazat nebudu
	    }
  		//$n = count($this->marks[$track]);
  		$this->RF->ImportMark($track,$trial,$name,new CPoint3D((int)$x,(int)$y, (int) $z));
  		//$this->marks[$track][$trial][$name]=array(); // taky podle tracks aby to bylo stejne jako goals
  		// // kamil 13.8.2009 12:46:05 - kdyz budu takhle strukturovat pole Marks po track a trial, je uz pole sekv_marks zbytecne
  		//$this->sekv_marks[$track][$trial][]=$name; // budu tam vrsit postupne vsechny pozice znacek bez opakovani
  		// - nevim jestli se nejaka z nich opakuje nebo ne
  		$this->zpos_mark = (int) $z;
  		$this->marks_x[$track][]=(int) $x; // pridano 19.4.2010 - aby se pro velikost areny pouzivaly i dynamicke pozice znacek
  		$this->marks_y[$track][]=(int) $y; // pouziva se jen pro urceni velikosti areny
  	} elseif($this->IsAim($name)){
  	  /*if($this->aims_imported_beginning) {
	      unset($this->goals); // 25.5.2010
	      $this->aims_imported_beginning = false; // priste uz marks mazat nebudu
	    }*/
	    $this->goals[$track][$name][$trial]=new CPoint((int)$x,(int)$y); 
	    // !! CILE KTERE SE TADY ULOZI SE PREVEDOU JINAM V Spanavvars->SaveSectorDef
	    $this->arenapositions[$track][$name][$trial] = $arenaangle; // potrebuju to otocit, protoze relativne k tomu budu pak otacet cil
	    //[$trial] jsem zrusil, protoze nevim v jakem trialu bude aim
	    // 6.9.2010 - trial zase pouzivam, budu ukladata dvojite, i podle jmena i podle trialu 
	    
  	}
  }
  /**
   * naplni pole marks_x a marks_y kvuli maximu a mimimu ze souradnic znacek
   * @param int $track
   */
  public function MarksXY($track){
  	unset($this->marks_x[$track]);
  	unset($this->marks_y[$track]);
  	if(isset($this->starts[$track])){
	    foreach ($this->starts[$track] as $name=>$position){
	      	$this->marks_x[$track][]=(int)$position[0];
	      	$this->marks_y[$track][]=(int)$position[1];
	    }
  	} elseif(isset($this->RF->marks[$track])){
  		foreach($this->RF->marks[$track] as $trial=>$trialdata){
  			foreach ($trialdata  as $name => $position) {
  				$this->marks_x[$track][]=(int)$position[0];
          		$this->marks_y[$track][]=(int)$position[1];
  			}
  		}
  	} else {
  		trigger_error("nejsou definovane znacky ani stary, neni z ceho spocitat velikost areny",E_USER_ERROR);
  	}
  }
  /**
   * vrati maximalni pocet znacek v jednom trialu v tracku
   *
   * @param int $track
   */
  public function MarkCount($track){
    $n = 0;
    foreach ($this->RF->marks[$track] as $trial=>$marks) {
    	$n = max($n,count($marks));
    }
    return $n;
  }
  /**
   * seznam jmen znacek spolu s jim prirazenymi cisly 
   * pro kazde jmeno znacky jedno cislo
   * 
   * @param int $track
   * @param int $startcount pokud se pridava k jiz existujicimu list znacek
   * @return array [name]=int
   */
  public function MarkList($track=false,$startcount=1){ 
    $list = array();
    if($track===false){
      foreach($this->RF->marks as $track=>$marks){
        $list = array_merge($list,$this->MarkList($track,count($list)+1)); 
      }
    } else {
      foreach ($this->RF->marks[$track] as $trial=>$marks) {
        foreach($marks as $name=>$position){
      	 if(!isset($list[$name]))
      	   $list[$name]=count($list)+$startcount; // budou to cisla od 1, rovnou budou odpovidat cislu barev
        }
      }
    }
    return $list;
  }

  // -------------- interni funkce
  /**
   * vrati jmeno a souradnice znacky v poli
   * napriklad z retezce Aim1[-2289, 3440, -2888]
   *
   * @param string $str
   * @return array
   */
  private function SplitMark($str){
  	$parts=preg_split("/[\[\],]/",trim($str)); // name, x, y, z
  	
  	return array($parts[0],$parts[1],$parts[2],$parts[3]);
  }
  protected function IsMark($name){ // funkce k prepsani v jine konfiguraci
  	return (strpos($name,"Mark")!==false && strpos(trim($name)," ")===false); 
  	// znacka je cokoliv co obsahuje slovo Mark a nema v sobe mezeru
  }
  protected function IsAim($name){ // funkce k prepsani v jine konfiguraci
  	return (strpos($name,"Aim")!==false || strpos($name,"Goal")!==false); // cil je cokoliv co obsahuje slovo Aim nebo Goal
  }
  protected function IsStart($name){ // funkce k prepsani v jine konfiguraci
    return (strncmp($name,"Start",5)==0);
  }

  function MarksMinX($track){
    return min($this->marks_x[$track]);
  }
  function MarksMaxX($track){
    return max($this->marks_x[$track]);
  }
  function MarksMinY($track){
    return min($this->marks_y[$track]);
  }
  function MarksMaxY($track){
    return max($this->marks_y[$track]);
  }
  private function MarkSnizcisla(){
  	 foreach ($this->sekv_starts as $key=>$start){ // musim to predelat na hodnoty 0-15
    	if(is_array($start)){ //28.7.2009 12:25:36 kvuli testu eyelink
    		foreach ($start as $trial => $value) {
    			$this->sekv_starts[$key][$trial]= $value-1;
    		}
    	} else {
    		$this->sekv_starts[$key]= $start-1;
    	}
    }
    foreach ($this->RF->sekv_goals as $key=>$goal){ // musim to predelat na hodnoty 0-15
    	if(is_array($goal)){ //28.7.2009 12:25:36
    		foreach ($goal as $trial => $value) {
    			$this->RF->sekv_goals[$key][$trial]= $value-1;
    		}
    	} else {
    		$this->RF->sekv_goals[$key]= $goal-1;
    	}
    }
    foreach ($this->sekv_marks as $znacka=>$sekv) {  // musim to predelat na hodnoty 0-15
      foreach ($sekv as $key=>$mark) {
      	if(is_array($mark)) { // 28.7.2009 12:27:38
      		foreach ($mark as $trial => $value) {
      			$this->sekv_marks[$znacka][$key][$trial]= $value-1;
      		}
      	} else {
    	 $this->sekv_marks[$znacka][$key]= $mark-1;
      	}
      }
    }
  }
  /**
   * vraci kategorii vysky pohledu: 0=platforma,1=pod znacku,2=znacka,3=strop;
   * znacka je definovana pomoci zpozice, prumeru a +-50 okrajem
   * @param int $z
   */
  function ZName($z){
    if(empty($this->zpos_mark)){ // pokud nejsou definovane zadne znacky
      return $z <= $this->zpoz_aim?0:1;
    }
    if($z <= $this->zpoz_aim){
      return 0; //return "platform";
    } elseif($z<=$this->zpos_mark-$this->markradius-50){
      return 1;//return "bottom";
    } elseif($z<=$this->zpos_mark+$this->markradius+50){
      return 2;//return "mark";
    } else {
      return 3;//return "top";
    }
  }
  /**
   * ulozi pozici cile do ->RF->sekv_goals[track][trial] a jmeno do ->RF->sekv_goal_names[track][trial]
   * @param int $track
   * @param int $trial
   * @param string $aim
   * @param string $mapname
   */
  public function GetRoomAims($track,$trial,$aimname){
  	if(empty($this->RF->sekv_goals[$track][$trial])){ // tady jsou skutecne pozice cilu nactene v cili
  	  if(isset($this->goals[$track][$aimname][$trial])){ // tady jsou pozice cilu nactene pomoci Position changed a v hlavicce
  	  	$this->RF->ImportAim($track,$trial,$aimname,$this->goals[$track][$aimname][$trial]);
  	  } elseif(isset($this->goals[$track][$aimname]['default'])){
  	  	$this->RF->ImportAim($track,$trial,$aimname,$this->goals[$track][$aimname]['default']);
      }
  	}
  }
  /**
   * @param unknown_type $track
   * @param unknown_type $trial
   * @param unknown_type $aimname
   */
  public function GetArenaAim($track,$trial,$aimname,$rotation0,$center){
  	if(empty($this->AF->sekv_goals[$track][$trial])){
  	  if(isset($this->goals[$track][$aimname][$trial])){
  	  	$position = clone $this->goals[$track][$aimname][$trial];
  	  } elseif(isset($this->goals[$track][$aimname]['default'])){
  	  	$position = clone $this->goals[$track][$aimname]['default'];
      } else {
      	return;
      }
      if(empty($this->arenapositions[$track][$aimname][$trial])){
        // tohle snad bude platit jen pro uplne prvni cil v tracku
      	$this->arenapositions[$track][$aimname][$trial] = $rotation0; // prvni udana pozice areny
      }
      /* @var $position CPoint */
      // nulova pozice cile je na zacatku trialu. 
      $rotation = -$this->arenapositions[$track][$aimname][$trial]+$rotation0;
      $position->Rotate($rotation,$center); 
      $this->AF->ImportAim($track,$trial,$aimname,$position);
      $this->AF->ImportMarksFrame($track,$trial,$this->RF,$rotation,$center);
  	}
  }
  private function GetAimFromMark($mark,$mark0,$aim0,$track){
    $center0 = center( // stred areny - originalni, protoze by mel byt stejny
        array($this->ArenaMinX(0),$this->ArenaMinY(0)),
        array($this->ArenaMaxX(0),$this->ArenaMaxY(0)) );
    $center = center(array($this->ArenaMinX($track),$this->ArenaMinY($track)),
        array($this->ArenaMaxX($track),$this->ArenaMaxY($track)) );
    $mark0 = boddiff($mark0,$center0);
    $mark = boddiff($mark,$center); //obe posunu k k centru [0,0], abych mohl pocital uhlovy rozdil
    $aim0 = boddiff($aim0,$center0);

    // zmena cile ma dve slozky - rotaci a posun
    $rotation = anglediff($mark0,$mark); // + je asi po smeru rucicek ( pokud je + y nahore)
    #TODO musim zkontrolovat smer otaceni

    $aim0 = rotate($rotation,$aim0);
    $mark0 = rotate($rotation,$mark0);

    // posun
    $posun = boddiff($mark,$mark0);
    $aim = bodadd($aim0,$posun);
    $aim = bodadd($aim,$center); // posunu k novemu stredu
    return $aim;
  }
  private function GetAimFrom2Marks($marks,$marks0,$aim0,$track){
    $ratio = distance($marks[0],$marks[1])/distance($marks0[0],$marks0[1]); // zvetseni vzdalenosti znacek.

    $center0 = center( // stred areny - originalni, protoze by mel byt stejny
        array($this->ArenaMinX(0),$this->ArenaMinY(0)),
        array($this->ArenaMaxX(0),$this->ArenaMaxY(0)) );
    $center = center(array($this->ArenaMinX($track),$this->ArenaMinY($track)),
        array($this->ArenaMaxX($track),$this->ArenaMaxY($track)) );
    $aim0 = boddiff($aim0,$center0);
    $mark0 = boddiff($marks0[0],$center0);
    $mark  = boddiff($marks[0],$center);

    $rotation = anglediff($mark0,$mark);
    $aim0 = rotate($rotation,$aim0);
    $mark0= rotate($rotation,$mark0);

    $aimangle = rad2deg(angletocenter($aim0,$mark0));
    $aimdist = distance($aim0,$mark0);
    $distnew = $aimdist * $ratio;
    $aim = angledist2xy(deg2rad($aimangle),$distnew);
    //$aim[1]=-$aim[1];
    $aim = bodadd($aim,$mark);
    $aim = bodadd($aim,$center);

    return $aim;
  }
  /**
   * vraci novy objekt teto tridy
   * vola se pomoci call_user_func(array($classname,'Factory')), kde je mozne specifikovat jmeno tridy
   * @return SpaNavCfg
   */
  static function Factory(){
    return new SpaNavCfg(false);
  }
  public function MarkShortName($name){
  	return ("M".substr(trim($name),4));
  }
  public function ExpName($filename){
  	return 'x';
//  	$tecka = strrpos($filename,'.');
//    $podtrziko = strrpos(substr($filename,0,$tecka),'_');
//    return strtolower(substr($filename,$podtrziko+1,$tecka-$podtrziko-1)); // cast mezi podrtzitkem a teckou
  }
  /**
   * vrati true pokud RF nebo AF
   * @param string $frame
   * @return boolean
   */
  private function FrameOK($frame){
  	return ($frame == "RF" || $frame=="AF");
  }
  /**
   * ulozi hodnotu konstanty GOALBYENTRANCE a pripadne jejich variant pro individualni trialy;
   * napr. GOALBYENTRANCE|0!0,10,20,30,40,50
   * @since 10.12.2012
   */
  private function SetGoalbyEntrance(){
  	if(strpos(GOALBYENTRANCE,"!")!==false){
  		list($gbe,$trials) = explode("!", GOALBYENTRANCE);
  		$gbe = $gbe?1:0; // chci jen hodnoty 1 a 0
  	} else {
  		$gbe = GOALBYENTRANCE?1:0;
  		$trials = false;
  	}
  	$this->goalbyentrance['default']=$this->goalbyentrance['last']=$gbe;
  	if($trials){
  		$vals = explode(",",$trials);
  		foreach($vals as $val){
  			$this->goalbyentrance[$val] = $gbe?0:1; // opacnou hodnoty k defaultni hodnote
  		}
  	}
  }
  /**
   * vrati hodnotu konstanty GOALBYENTRANCE pro konkretni trial; pokud neni zadany trial, vraci posledni hodnotu
   * @param int $trial
   * @return 1|0
   */
  public function GetGoalByEntrance($trial=false){
  	 if($trial===false){
  	 	return $this->goalbyentrance['last'];
  	 } elseif(isset($this->goalbyentrance[$trial])){
  	 	$this->goalbyentrance['last']=$this->goalbyentrance[$trial];
  	 	return $this->goalbyentrance[$trial];
  	 } else {
  	 	$this->goalbyentrance['last']=$this->goalbyentrance['default'];
  	 	return $this->goalbyentrance['default'];
  	 }
  }
  /**
   * vraci true, pokud jsou souradnice $roomxy v aktualnim cili.
   * @param [x,y] $roomxy
   * @param int $track
   * @param int $trial
   * @param string $aimname
   * @return bool
   * @since 25.7.2013 kvuli pocitani vstupu do cile behem probetrialu
   */
  public function InGoalArea($roomxy,$track,$trial,$aimname){
  	if(isset($this->goals[$track][$aimname][$trial])){
  		$goalposition = $this->goals[$track][$aimname][$trial];
  	} elseif(isset($this->goals[$track][$aimname]['default'])) {
  		$goalposition = $this->goals[$track][$aimname]['default'];
  	}
  	if(isset($goalposition)){
  		$distance = $goalposition->Distance( new CPoint($roomxy));
  		return $distance <=$this->radius_goal;
  	} else {
  		return false;
  		
  	}
  }

} //class SpaNavCfg 

} //if(!defined('SPANAVCFG')){

?>