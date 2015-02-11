<?php

require_once 'classes/CPoint.class.php';
//define('MAKEIMG',1);
require_once 'classes/TrackPoint.class.php';
require_once 'classes/TrackImage.class.php';
require_once 'classes/HistoSum.php';
require_once 'includes/stat.inc.php';

define('ANGLEDIFFMIN',1);
define('TIMETOTURN_SPEEDMIN',1000);
define('UHELPOHLEDUVSUDE',1); // zpracuji se vsude uhly pohledu kvuli pocitani zacatku otaceni, ale pro obrazky to neni dobre
define('TRIALSMERGE',0);

class PathData {
	private $cislocloveka=false;
	private $faze;
	/**
	 * usla draha; !pocita se v roomframu!
	 * @var double
	 */
	private $path_lenght;
	/**
	 * celkove otoceni za trial
	 * @var double
	 */
	private $angle;
	private $ukazano = false; // jestli v trialu ukazan smer - jen do toho se meri celkove otoceni
	/**
	 *  cas zacatku trialu; plni se v NextTrial aktualnim casem
	 * @var float
	 */
	private $start_time; //pole startovnich casu kazdeho trialu [$trial]
	private $last_time; // minuly cas;
	private $time; // celkovy cas trialu
	
	private $timeToGoal; // cas otaceni k cili (keytime - cas stani a cas otaceni na druhou stranu)
	private $timeStay;   //cas co stal
	private $timeFromGoal; // cas co se otacel od cile
	
	private $arenaangle; // celkove otocni areny v trialu
	private $arenaangle_trial; // natoceni areny na zacatku kazdeho trialu
	//private $timetoturn_speed=-1; // cas, kdy se clovek zacal otacet, podle rychlosti
	/**
	 * @var CPoint
	 */
	private $last_bod;
	/**
	 * posledni viewangle
	 * @var deg
	 */
	private $last_angle;
	private $last_arenaangle;
	//private $last_timetoangle; // naposled ulozeni cas do otoceni pomoci funkce timetoangle
	
	/**
	 * @var CPoint
	 */
	private $start;
	private $angle_arr;
	private $points;
	private $trial = -1;
	private $trialgroups = array(); // kazdemu trialu je mozne priradit skupinu a rozclenit je tak
	private $trial_data = array(); // ke kazdemu trial vlastnosti
	/**
	 * plni se v AddPoint
	 * @var TrackPoint array
	 */
	private $trackdata = array(); // pole hodnot roztridene podle trialu
	/**
	 * pole natoceni areny v pokuse
	 * @var array
	 */
	private $arenadata = array(); // pole natoceni areny v pokuse
	private $goalpositions = array(); // pozice cilu
	private $angle_speed = array(); // rychlost otaceni cloveka behem tracku 
	public  $angle_speed_histo = array(); // prumerny histogram z cloveka
	private $timetostartturn = array(); // casy do zacatku otaceni podle trialu
	public  $angletostarturn = array(); // uhly pri zacatku otaceni podle trialu
	/**
	 * jestli se otocil spravne k cili podle trialu; 
	 * 1 ano, 0 ne, -1  nelze vyhodnotit
	 * @var array
	 */
	private $turnedtogoal = array(); // 
	private $stredareny;
	private $arenaframetrial = array(); // jestli je cil v arena frame v kazdem trialu
	
	private $keytime = array(); // chci si ho tu pocitat samostatne
	private $LPTbite= array(); // nahozeny bit paralelniho portu - pole hodnot1/0 pro kazdy trial a bit - 12.6.2013
	
	/**
	 * startuje data pro cloveka ale ne trial. Trial se musi nastartovat pomoci nexttrial
	 * @param CPoint $stred
	 */
	function __construct($cislocloveka=false,$faze=0,$stredareny) {
	  $this->reset();
	  $this->cislocloveka = $cislocloveka; 
	  $this->faze = $faze; 
	  $this->stredareny = $stredareny;
	}
	private function reset(){
		$this->path_lenght = 0;
		$this->angle =0;
		unset($this->start);
		unset($this->last_bod);
		unset($this->angle);
		//unset($this->start_time);
		$this->timetoturn_speed=-1; // defaultni hodnota
		$this->angle_arr = array();
		$this->time = 0;
		$this->arenaangle = 0;
		$this->points =0;
		$this->ukazano = false;
		$this->timeToGoal=0; // 20.7.2012
		$this->timeFromGoal=0; // 20.7.2012
		$this->timeStay=0; // 20.7.2012
	}
  /**
   * prida bod tracku do aktualniho trialu
   * $key znamena, ze byl ukazan cil
   * 
   * @param CPoint $bod
   * @param deg $viewangle
   * @param float $time
   * @param string $key
   * @param deg $arenaangle
   */
  public function AddPoint($bod,$viewangle,$time,$key=false,$arenaangle=0){
  	$this->points++;
  	$viewangle = Angle::Normalize($viewangle);
  	if(isset($this->last_bod)) $this->path_lenght += $this->last_bod->Distance($bod); //TODO usla vzdalenost je v roomframu
  	if($key!==false) $this->ukazano = true;
  	//if(!isset($this->start_time)) $this->start_time = $time; - 19.10.2011 - starttime se nastavuje v NextTrial
    if($time<$this->start_time[$this->trial]) $this->start_time[$this->trial]-=1; // obcas se objevi takovehle casy   236.949 237.996 237.039 237.085
  	$this->time = $time - $this->start_time[$this->trial];
  	if($this->time - $this->last_time > 1) $this->time -= 1; // chyby v tracku, ojedinele body o vterinu napred
    
  	if(isset($this->last_angle)) {
  		if(Angle::Difference($this->last_angle, $viewangle,false)>ANGLEDIFFMIN && !$this->ukazano){
  			$this->angle += Angle::Difference($this->last_angle, $viewangle,false);
  			// TODO celkovy uhel otoceni se pocita jen v roomframu
  		}
  		$bylkeytime = isset($this->keytime[$this->trial]) && $this->keytime[$this->trial] <= $this->time;
  		// rychlost otaceni chci merit i kdyz se neotaci, aby tam byl videt rozdil
  		if($this->time>$this->last_time && !$bylkeytime){
  			//vse  jen do keytime - 19-20.7.2012
  			$this->angle_speed[$this->trial][(string)$this->time]=$this->anglespeed($viewangle);
  			switch ($this->turnstogoal($viewangle, $arenaangle)){
	  			case 1:  $this->timeToGoal+= ($this->time - $this->last_time); break;
	  			case -1: $this->timeFromGoal+= ($this->time - $this->last_time); break;
	  			case 0:  $this->timeStay += ($this->time - $this->last_time); break;
  			}
  		}
  		
  		
  	}
  	$this->last_angle = $viewangle;
  	$this->angle_arr[]=$viewangle;
  	$this->last_bod = $bod;
  	if(!isset($this->start)) $this->start = $bod;
  	
  	if(isset($this->last_arenaangle)) $this->arenaangle+= abs($arenaangle - $this->last_arenaangle);
  	if(!isset($this->arenaangle_trial[$this->trial])) $this->arenaangle_trial[$this->trial]=$this->last_arenaangle; // protoze trial zacal uz v minulem bodu
  	$this->last_arenaangle = $arenaangle;
  	
  	
  	$this->last_time = $this->time;
  	
  	
  	if(!UHELPOHLEDUVSUDE){// kvuli pocitani zacatku otaceni chci uhel pohledu vsude - UHELPOHLEDUVSUDE dam 1
  		if(!$key && isset($this->trackdata[$this->trial]) /*kamil 6.9. 2011 - jen uhel stlaceni klavesy */) $viewangle=false; // chci uhel pohledu jen u stlacene klavesy a prvniho bodu tracku
  	}
  	$this->trackdata[$this->trial][(string)$this->time]=new TrackPoint($bod,$viewangle,$key); 
  	$this->arenadata[$this->trial][(string)$this->time] = $arenaangle;
  }
  /**
   * ulozi polohu cile pro aktualni trial
   * @param CPoint $stred
   * @param float $polomer
   * @param float $keytime
   */
  public function AddGoal($stred,$polomer,$keytime=false){
  	$this->goalpositions[$this->trial]=array("stred"=>$stred,"polomer"=>$polomer);
  	if($keytime) $this->AddKey($keytime);
  }
  /**
   * vraci uslou drahu za trial
   */
  public function PathLength(){
  	return $this->path_lenght;
  }
  /**
   * vrati celkove otoceni za trial;
   *  !pocita se jen v roomframu
   */
  public function Angle(){
  	return isset($this->angle)?$this->angle:0;
  }
  /**
   * vrati uhel otoceni areny od predchoziho vstupu do cile 
   * @param deg $arenaangle
   * @return deg
   * @since 30.7.2012
   */
  public function AngleChange($arenaangle){
  	if(isset($this->arenaangle_trial[$this->trial-1])){
  		return Angle::Difference($arenaangle/*aktualni natoceni*/, $this->arenaangle_trial[$this->trial-1]/*uhel na zacatku trialu, kdy sel minule do stredu*/,true);
  	} else {
  		return -1;
  	}
  }
  /**
   * vraci celkovy cas v trialu kdy se clovek otacel podle which: -1 od cile 1 k cili 0 neotacel se 
   * @return double
   * @since 20.7.2012
   */
  public function TimeToGoal($which){
  	switch ($which){
  		case -1; return $this->timeFromGoal; break;
  		case 1: return $this->timeToGoal; break;
  		case 0: return $this->timeStay; break;
  	}
  }
  /**
   * vrati cas predchoziho trialu, kdy subjekt sel do stredu areny; 
   * @return float
   * @since 30.7.2012
   */
  public function TimeToStred(){
  	return $this->start_time[$this->trial]/*cas zacatku tohoto trialu, kdy sel do cile*/
  			- $this->start_time[$this->trial-1]/*cas zacatku predchozi trialu, kdy sel do stredu*/;
  			// vysledkem je tedy cas mezi predchozim prichodem do cile a naslednym prichodem do stredu (poslednim pred vstupem do aktualni hocile)
  }
  /**
   * vraci cas zacatku trialu
   * pokud neuvedu trial, vrati se aktualni trial
   * @param int $trial
   * @return float
   * @since 31.7.2012
   */
  public function StartTime($trial=false){
  	return $this->start_time[$trial?$trial:$this->trial];
  }
  
  /**
   * vrati pomer minimalni ku usle draze (0-1)
   * @return double
   */
  public function PathEfficiency(){
  	if($this->path_lenght==0) 
  		return 0;
  	else 
  		return ($this->start->Distance($this->last_bod))/$this->path_lenght;
  }
  /**
   * vrati maximalni rychlost otaceni z trialu;
   * pokud zadam keytime, pocita se jen do tohoto casu;
   * pokud neni zadan trial, vrati aktualni trial
   * 
   * @param double $keytime
   * @param int $trial
   * @return double
   */
  public function AngleSpeedMax($keytime=0,$trial=false){
  	if($trial===false) $trial = $this->trial;
  	$maxspeed = -1;
  	if(isset($this->angle_speed[$trial])){ //31.7.2013 - stalo se mi, ze neni definovano pro nejaky trial (13)
	  	foreach($this->angle_speed[$trial] as $time=>$speed){
	  		if($speed>$maxspeed) $maxspeed = $speed;
	  		if($keytime !=0 && floatval($time)>= $keytime) return $maxspeed; // nechci vetsi cas nez keytime
	  	}
  	} 
  	return $maxspeed;
  }
  /**
   * vrati nejkratsi vzdalesnot mezi prvnim a poslednim bodem drahy
   * @return double
   */
  public function MinimalDistance(){
  	return $this->start->Distance($this->last_bod);
  }
  /**
   * vraci cas do otoceni 
   * volitelne zpusoby
   * 
   * @param int $trial
   * @return double
   */
  public function TimeToTurn($trial=false,$method=null){ //@param double $keytime - 19.10.2011 - trida ho ma vlastni
  	if($trial===false) $trial = $this->trial;
  	if(isset($this->timetostartturn[$trial])){
  		return $this->timetostartturn[$trial]; 
  	}
  	
  	/*
  	//kvantily bez filtrace
  	$limits = array(5=>array(1=>12.077,9.239,2.728,3.163,25.4982,3.206,
  							 1.977,13.0914,8.795,16.2416,13.6,11.894,
  							 2.5858,3.1908,3.5816,6.932,15.252,
  							 45.8038,29.9972,12.736,4.403,11.862,20.2596,6.0862,1.7326,9.903,23.793),
  				    6=>array(1=>3.2074,2.7908,3.5628,4.2244,1.11,3.2,7.641,
  				    		 20.687,9.85,23.222,23.47,6.2332,1.899,
  				    		 0.5254,2.4878,1.2916,2.776,80.878,9.04,
  				    		 7.321,21.755,11.8354,18.0688,56.146,7.0134,5.1756,19.0804 )
    );
     */
    //kvantily po filtraci
  	$limits = array(5=>array(1=>13.8678, 9.2090, 2.0078, 3.1674, 23.0990, 
  							 2.6900, 2.0834, 12.8712, 8.0332, 13.4780, 
  							 13.0402, 5.1696, 2.3058, 3.2484, 4.5672, 
  							 6.2574, 16.8424, 47.9458, 30.2292, 12.7448, 
  							 3.5584, 12.2780, 19.6062, 0.5178, 1.0528, 
  							 8.2378, 21.2988),
  				    6=>array(1=>3.3414, 2.7514, 3.5844, 3.905, 1.11, 
  				    		 3.4472, 7.725, 21.827, 10.0932, 19.1588, 
  				    		 23.6764, 6.6844, 1.969, 0.5262, 2.5296, 
  				    		 1.5796, 1.5892, 81.15, 9.04, 5.5162, 
  				    		 22.829, 11.9162, 18.3664, 56.578, 5.2016, 
  				    		 5.5188, 19.0996)			    		 
  	); // 0.99 quantil pro kazdeho cloveka zvlast, 27 lidi
  	
  	// zkusim vracet cas to tretiny maximalni rychlosti
  	switch ($method) {
  		case "pul":
  			return $this->timetoanglespeed($trial,2);
  			break;
  		case "tretina":
  			return $this->timetoanglespeed($trial,3);
  			break;
  		case 1:
  			//$limit = $this->faze == 5? 17.5820 /*Q*/:  23.4940 /*Q*/; //11.7.2011 11:00 zjisteno v Matlabu pomoci funkce anglehisto(1:27,5) a anglehisto(1:27,6)
  			if($this->faze >= 5){
  				$limit = $limits[$this->faze][$this->cislocloveka]; // 11.7.2011 16:18
  				return $this->timetoangle($trial, $limit);
  			} else {
  				return 0;
  			}
  				
  			break;
  		case 5:
  			//$limit = $this->faze == 5? 11.8381 /*mean*/:  13.129 /*mean*/;
  			$limit = $this->faze == 5? 30.6200 /*Q*/:  48.7096 /*Q*/; // 8.8.2011
  			return $this->timetoangle($trial, $limit,true); // save results to array
  			break;
  		
  		default:
  			return $this->timetoangle($trial,1);
  	}
  	//   
  	
  		
  }
  /**
   * vraci cas to poloviny maximalni rychlosti otaceni za trial
   * share urcuje jaky podil maximalni rychlosti otaceni se bere - polovina, tretina
   * @param double $keytime
   * @param int $trial
   * @param int $share
   * @return double
   */
  private function timetoanglespeed($trial,$keytime=0,$share=2){
  	$maxspeed = $this->AngleSpeedMax($keytime,$trial);
  	$timetoturn = -1;
  	foreach($this->angle_speed[$trial] as $time=>$anglespeed){
  		if($timetoturn <0  && $anglespeed>$maxspeed/$share){
  			$timetoturn = floatval($time);
  		}
  		if($keytime !=0 && floatval($time)>= $keytime){
  			return $timetoturn;
  		}
  	}
  	return $timetoturn;
  }
   /**
   * vraci cas do otoceni vetsiho nez 1 deg;
   * pokud zadam keytime, pocita se jen do tohoto casu;
   * pokud neni zadan trial, vrati aktualni trial;
   * pocita se pouze v AF arenaframu, coz je OK
   * 
   * @param int $trial
   * @param double $keytime
   * @param double $anglemin
   * @param double $saveresult jestli se maj ukladat vysledky do pole
   * @return double
   */
  private function timetoangle($trial,$anglemin,$saveresult = false){
  	$point0 = reset($this->trackdata[$trial]); // prvni bod tracku
  	$times = array_keys($this->trackdata[$trial]);
  	$time0 = reset($times); // prvni cas tracku
  	
  	foreach ($this->trackdata[$trial] as $time=>$point){
  		/* @var $point0 TrackPoint */
  		/* @var $point TrackPoint */
  		$angle1 = Angle::Difference($point->viewangle, $this->arenadata[$trial][$time],true);
  		$angle2 = Angle::Difference($point0->viewangle, $this->arenadata[$trial][$time0],true);
  		$angle= Angle::Difference($angle1,$angle2, false); // absolutni hodnota rozdil uhlu
  		// uhel relativne k arenaframu
	    // zmena uhlu od zacatku trialu
  	
	  	$time = floatval($time); // v poli je ulozen retezec
	  	if($angle > $anglemin) {
	  		if($saveresult) {
	  			$this->timetostartturn[$trial]=$time; 
	  			$this->angletostarturn[$trial]=array("viewangle"=>$point->viewangle,"viewangle0"=>$point0->viewangle,
	  					"angle1"=>$angle1,"angle2"=>$angle2);
	  		}
	  		return $time;
	  	}
  		if(isset($this->keytime[$trial]) && $this->keytime[$trial] !=0 && $time>= $this->keytime[$trial]){ 
  			// 5.2.2013 - ve nekterych fazich taky nemuselo byt ukazovani
  			if($saveresult){
  				if(isset($this->trackdata[$trial][(string) $this->keytime[$trial]])) {
  					$this->timetostartturn[$trial]=$this->keytime[$trial];
  				} else { 
  					$this->timetostartturn[$trial]=$time;	// nekdy se stane, ze v trackdata zadny bod s casem keytime neni
  				}
  				$this->angletostarturn[$trial]=array("viewangle"=>$point->viewangle,"viewangle0"=>$point0->viewangle,
  						"angle1"=>$angle1,"angle2"=>$angle2);
  			}
  			return $this->keytime[$trial]; // pokud se do stlaceni s neotocil, vratim cas stlaceni
  		} 
  	}
  	if($saveresult) { // vyjimecny pripad, kdy se neukazuje na cil a clovek se moc neodchylil od puvodniho smeru 
  			$this->timetostartturn[$trial]=$time; 
  			$this->angletostarturn[$trial]=array("viewangle"=>$point->viewangle,"viewangle0"=>$point0->viewangle,
  					"angle1"=>$angle1,"angle2"=>$angle2);
	}
  	return $time; // vratim posledni hodnotu casu
  }
  
  /**
   * vraci 1 pokud se clovek otocil spravny smerem, 0 pokud na opacnou stranu, 
   * -1 pokud to nejde vyhodnotit (neotocil se clovek, nebo je smer k cili 0) 
   * @param CPoint $goalposition pozice cile, definovana v editoru Unreal
   * @param bool $arenaframe jestli cil v arenaframu
   * @return int 1|0|-1
   */
  public function TurnToGoal($goalposition,$arenaframe=false){
  	$trial = $this->trial; // funkce timetoangle musi bezet na aktualnim trialu
  	if(!isset($this->turnedtogoal[$trial])){ //mohlo byt uz naplneno ve funkci TurnedNow
	  	if(isset($this->timetostartturn[$trial])){
		  	$time = (string) $this->timetostartturn[$trial]; // cas zacatku otaceni
		  	$point = $this->trackdata[$trial][$time]; // bod zacatku rotace 
		  	// zacatek tracku
		  	$point0 = reset($this->trackdata[$trial]); // prvni bod tracku - kam smeroval na zacatku trialu
		  	$time0 = key($this->trackdata[$trial]); // cas na zacatku trialu
		  	/* @var $point TrackPoint */
		  	/* @var $point0 TrackPoint */
		  	
		  	
		  	/* SMER OTOCENI CLOVEKA */
		  	$angle_point0 = !$arenaframe ?
		  					$point0->viewangle : Angle::Difference($point0->viewangle, $this->arenadata[$trial][$time0],true); 
		  			// kam by se clovek dival v case otoceni, kdyby se od zacatku neotocil - v arenaframu
		  	$angle_point =  !$arenaframe ?
		  					$point->viewangle : Angle::Difference($point->viewangle, $this->arenadata[$trial][$time],true); 
		  			// kam se clovek dival v case otoceni - v arenaframu
		  	$smer_otoceni_cloveka= Angle::Difference($angle_point, $angle_point0,true); // relativni hodnota rozdil uhlu
		  	//TODO smer otoceni cloveka ulozim podle klavesy, smer k cili, budu muset stejne pocitat
		  	
		  	
		  	/* SMER OTOCENI K CILI - dal jsem to do funkce, abych to mohl pouzit i v TurnedNow - 30.11.2011
		  	$angle_goal = $goalposition->Angle($this->stredareny); uhelcile 
		  			// neotacim cil ani v arenaframu - cil je v natoceni areny 0, takze se v AF nemeni - 21.10.2011
		  	$smer_otoceni_kcili = Angle::Difference($angle_goal, $angle_point0,true); 
		  	*/
		  	$smer_otoceni_kcili = $this->smer_otoceni_kcili($goalposition, $arenaframe); // kam by se clovek mel otocit, aby dosahl cile
		  			
		  	
		  	$this->angletostarturn[$trial]["smer_cloveka"]=$smer_otoceni_cloveka;
		  	$this->angletostarturn[$trial]["smer_cile"]=$smer_otoceni_kcili;
		  	if($smer_otoceni_kcili==0 || $smer_otoceni_cloveka==0) {
		  		$this->turnedtogoal[$trial]=-1; 
		  		//return -1; // nelze vyhodnotit		
		  	} elseif(sgn($smer_otoceni_cloveka)==sgn($smer_otoceni_kcili)){
		  		$this->turnedtogoal[$trial]=1;
		  		//return 1; // otocil se spravnym smerem
		  	} else {
		  		$this->turnedtogoal[$trial]=0;
		  		//return 0; // otocil se na druhou stranu
		  	}
	  	} else {
	  		return -1;
	  	}
  	}
  	return $this->turnedtogoal[$trial];
  }
  /**
   * vraci rychlost otaceni vzhledem k minulemu bodu
   * @since 20.7.2012
   * @param deg $viewangle
   */
  private function anglespeed($viewangle){
  	return Angle::Difference($this->last_angle, $viewangle,false)/($this->time-$this->last_time);
  }
  /**
   * vraci 1 pokud se clovek otaci smerem k cili, -1 pokud se otaci na druhou stranu, 0 kdyz stoji
   * @since 20.7.2012
   * @param deg $viewangle
   * @param deg $arenaangle
   * @return int 1|-1|0
   */
  private function turnstogoal($viewangle,$arenaangle){
  	$anglespeed = $this->anglespeed($viewangle);
  	if($anglespeed<10 || empty($this->goalpositions[$this->trial])) { // hodnota zjistena z matlabu. pokud clovek stoji, ma rychlost otaceni okolo 5deg/s (rychlost otaceni areny)
  		return 0;
  	} else {
	  	$goalposition = $this->goalpositions[$this->trial]['stred'];
	  	$angle_goal = $goalposition->Angle($this->stredareny); // uhel cile
	  		// cil v arenaframu se uvazuje jako v pozici s arenaangle==0
	  		
	  	// 
	  	//$angle_goal = Angle::Difference($angle_goal, 270,true); // V arena framu by to melo fungovat
	  	if($this->arenaframetrial[$this->trial]) {
	  		//$angle_goal = Angle::Normalize($angle_goal-$arenaangle);
	  		//$bodAF->viewangle = Angle::Difference($bodAF->viewangle, $arenaangle,true);
	  		//$bodAF->point->Rotate(-$arenaangle,$this->stredareny);
	  		$viewangle = Angle::Difference($viewangle, $arenaangle,true);
	  		$lastangle = Angle::Difference($this->last_angle, $arenaangle,true);
	  	} else {
	  		$lastangle = $this->last_angle;
	  	}
	  	$anglediff1 = Angle::Difference($viewangle, $angle_goal); // soucasne absolutni uhlova vzdalenost od cile
	  	$anglediff0 = Angle::Difference($lastangle, $angle_goal); // minula absolutni uhlova vzdalenost od cile
	  	
	  	return $anglediff1<$anglediff0 ? 1 : -1; // 1 pokud se zmensila uhlova vzdalenost od cile
  	}
  }
  /**
   * vrati smer otoceni k cili ze zacatku aktualniho trialu - -1 0 1
   * vraci 1 pokud doprava, -1 pokud doleva, 0 pokud primo pred sebou
   * @param CPoint $goalposition pozice cile, definovana v editoru Unreal
   * @param bool $arenaframe jestli je cil v arenaframu
   * @return int 
   */
  function smer_otoceni_kcili($goalposition,$arenaframe){
  	$angle_goal = $goalposition->Angle($this->stredareny); // uhle cile
  			//neotacim cil ani v arenaframu - cil je v natoceni areny 0, takze se v AF nemeni - 21.10.2011
  	if(!isset($this->trackdata[$this->trial])){
  		echo "ERROR PathData 431: trial $this->trial, trackdata not defined \n";
  		exit(-1);
  		return false;
  	}
  	$point0 = reset($this->trackdata[$this->trial]); // prvni bod tracku - kam smeroval na zacatku trialu
  	$time0 = key($this->trackdata[$this->trial]); // cas na zacatku trialu
  	/* @var $point0 TrackPoint */
  	$angle_point0 = !$arenaframe ?
	  				 /*ROOM FRAME*/	$point0->viewangle : 
  					/*ARENA FRAME*/ Angle::Difference($point0->viewangle, $this->arenadata[$this->trial][$time0],true); 
	  			// kam by se clovek dival v case otoceni, kdyby se od zacatku neotocil - v arenaframu
	
	$smer_otoceni_kcili = Angle::Difference($angle_goal, $angle_point0,true); 
	  			// kam by se clovek mel otocit, aby dosahl cile 
	return sgn($smer_otoceni_kcili);  			  	
  }
  /**
   * vymaze hodnoty pro novy trial
   * 
   * @param double $time
   * @param string $trialgroup jmeno cile
   * @param string $trialname 
   * @param int $arenaframe jestli se hleda cil v arenaframu
   * @param array $goal pozice a polomer cile
   */
  public function NextTrial($time,$trialgroup="not-set",$trialname='',$arenaframe=false,$goal = false){
  	$this->reset();
  	$this->trial++;
  	$pathgroup = TRIALGROUPS?$trialgroup:"vse";
  	$this->trialgroups[$pathgroup][]= array("trial"=>$this->trial,"name"=>$trialname,"group"=>$trialgroup);
  	$this->trial_data[$this->trial]=array("name"=>$trialname,"group"=>$trialgroup);
  	$this->start_time[$this->trial] = $time; // 19.10.2010 - 30.7.2012 predelano na pole vsech trialu
  	$this->arenaframetrial[$this->trial]=$arenaframe;
  	if($goal) $this->goalpositions[$this->trial]=array("stred"=>$goal['stred'],"polomer"=>$goal['polomer']);
  }
  public function ArenaSpeed(){
  	return $this->arenaangle/$this->time;
  }
  /**
   * nakresli tracky s uhly pohledu
   * @param CPoint $stred
   * @param int $arenasize
   * @param int $pocet
   */
  public function Plot($filename,$stred,$arenasize){
  		foreach($this->trialgroups as $trialgroup => $trials){
  			if(strlen(trim($trialgroup))>0){
	  			$img = new TrackImage($arenasize, $stred, 300, count($trials));
	  			$img->Name($trialgroup);
	  			foreach($trials as $trialno=>$trialdata){
	  				$trial = $trialdata['trial']; $trialname = $trialdata['name']; $trialgroup = $trialdata['group'];
	  				
		  			if(isset($this->goalpositions[$trial])){
		  				$goalxy = $this->goalpositions[$trial]["stred"];
		  				$goalr = $this->goalpositions[$trial]["polomer"];
		  			} else {
		  				$goalxy = $goalr = false;
		  			}
		  			if(isset($this->trackdata[$trial])) { // 18.4.2013 - po poslednim ukazani muze vzniknout kraticky track bez bodu; pripadne s bodama ale bez vstupu do cile
			  			reset($this->trackdata[$trial]); // aby se dostal na prvni prvek
			  			$prvni_cas = key($this->trackdata[$trial]); // 19.10.2011- uz to neni vzdy 0
			  			$this->trackdata[$trial][$prvni_cas]->key="Z"; // zacatek trialu // 3.8.2012- zmeni jsem pismena
			  			if(isset($this->timetostartturn[$trial])){
			  				$key = array_key_min($this->trackdata[$trial],$this->timetostartturn[$trial]);
			  				$this->trackdata[$trial][$key]->key = "O"; // zacatek otaceni
			  			}
		  				if(isset($this->keytime[$trial])){
			  				$key = array_key_min($this->trackdata[$trial],$this->keytime[$trial]);
			  				if($key!==false) $this->trackdata[$trial][$key]->key = "U"; // ukazani na cil
			  				// 5.2.2013 - pokud $key= false, key=U neulozim
			  			}
			  			if($this->arenaframetrial[$trial]){
			  				$body = $this->ConvertToAF($this->trackdata[$trial], $this->arenadata[$trial]);
			  			} else {
			  				$body = $this->trackdata[$trial];
			  			}
		  			} else {
		  				$body = array();
		  			}
		  			$barvy = array("Z"=>"red","O"=>"grey","U"=>"orange");
	  				$img->Track($body, TRIALSMERGE?0:$trialno,false,"$trial: $trialgroup",$goalxy,$goalr,$barvy);
	  			}
	  			$img->SaveImage("{$filename}".(TRIALGROUPS?"_{$trialgroup}":""));
  			}
  		}
  }
  /**
   * ulozi rychlostni profily otaceni do souboru
   */
  public function AngleSpeedSave($filename){
  	$out = "";
  	foreach($this->angle_speed as $trial=>$speed){
  		$out.="$trial\t".implode("\t",array_keys($speed))."\n"; // casy
  		$out.="$trial\t".implode("\t",$speed)."\n";             // rychlosti
  		
  	}
  	file_put_contents($filename.".txt",$out);
  }
  /**
   * vrati rychlostni profil otaceni v tabulce
   * pro jednoho cloveka
   * pokud se maji nejake trialgroups vyradit, zada se array s jejich nazvy
   * naplni pole na histogram: angle_speed_histo 
   * @param array|bool $excludegroups
   * @return Table
   */
  public function AngleSpeedProfile($excludegroups=false){
  	$table = new Table();
  	for($t=0;$t<$this->trial;$t++){
  		if(empty($this->angle_speed[$t])) {
  			$this->angle_speed[$t]=array(0=>0); // v kazdem trialu chci mit nejakou hodnotu
  		}
  	}
  	ksort($this->angle_speed);
  	//uksort($this->anglespeed, 'strnatcasecmp'); // nic nefunguje
  	foreach($this->angle_speed as $trial=>$speeds){
  		$trialname = $this->trial_data[$trial]['name'];
  		$trialgroup = $this->trial_data[$trial]['group'];
  		
  		if(!$excludegroups || !is_array($excludegroups) || !in_array($trialgroup, $excludegroups)){
	  		// radka s casy  
	  		$table->AddToRow(array($this->cislocloveka,$this->faze,$trial,$trialgroup,$trialname,0)); //0 znamena souracnici x
	  		$times = array_keys($speeds);
	  		foreach ($times as &$value) {
	  			$value = floatval($value);
	  		}
	  		$table->AddToRow($times);
	  		$table->AddRow();
	  		
	  		// radky s rychlosmi
	  		$table->AddToRow(array($this->cislocloveka,$this->faze,$trial,$trialgroup,$trialname,1)); // 1 znamen souradnici y
	  		$table->AddToRow($speeds);
	  		$table->AddRow();
	  		
	  		if(!isset($this->angle_speed_histo[$trialgroup][$trialname])){
	  			$this->angle_speed_histo[$trialgroup][$trialname] = new HistoSum(); // tady jen sbiram hodnoty, takze zadne parametry nemusim
	  		}
	  		foreach($speeds as $time=>$speed){
	  			$this->angle_speed_histo[$trialgroup][$trialname]->AddValue($time, $speed);
	  		}
	  		
  		}
  	}
  	// sumarni funkce - soucet v intervalech 0.04 s se zacatkem 0.02 s
  	//
  	
  	return $table;
  }
  public function TurnProfile($excludegroups =false){
  	$table = new Table();

  	foreach($this->trackdata as $trial=>$points){
  		/* @var $point0 TrackPoint */
  		$point0 = reset($points); // prvni bod tracku
  		$time0 = reset(array_keys($points));
  		$times = array();
  		$angles = array();
  		foreach($points as $time=>$point){
	  			$times[]=floatval($time);
	  			/* @var $point TrackPoint */
	  			$angles[]= Angle::Difference($point->viewangle-$this->arenadata[$trial][$time], // uhel relativne k arenaframu
	  					       $point0->viewangle-$this->arenadata[$trial][$time0],true); // zmena uhlu od zacatku trialu
	  	}
	  		
  		$trialname = $this->trial_data[$trial]['name'];   // v drf3tomatlab = framechange
  		$trialgroup = $this->trial_data[$trial]['group']; // v drf3tomatlab = placecode 1-4
  		
  		if(!$excludegroups || !is_array($excludegroups) || !in_array($trialgroup, $excludegroups)){
	  		// radka s casy  
	  		$table->AddToRow(array($this->cislocloveka,$this->faze,$trial,$trialgroup,$trialname,0)); //0 znamena souracnici x
	  		$table->AddToRow($times);
	  		$table->AddRow();
	  		
	  		// radky s rychlostmi
	  		$table->AddToRow(array($this->cislocloveka,$this->faze,$trial,$trialgroup,$trialname,1)); // 1 znamen souradnici y
	  		$table->AddToRow($angles);
	  		$table->AddRow();
  		}
  	}
  	// sumarni funkce - soucet v intervalech 0.04 s se zacatkem 0.02 s
  	//
  	
  	return $table;
  }
  /**
   * ulozi keytime - cas ukazani na cil
   * @param double $time
   */
  public function AddKey($time){
  	$this->keytime[$this->trial] = $time-$this->start_time[$this->trial];
  }
  /**
   * prevede body nebo bod do arenaframu;
   * oba parametry musi byt pole s indexem time nebo jedno cislo  
   * @param TrackPoint $points [$time]
   * @param double $arenaangle [$time]
   */
  private function ConvertToAF($points,$arenaangle){
  	if(is_array($points)){
	  	$pointsAF = array();
	  	foreach($points as $time=>$bod){
	  		if(isset($arenaangle[$time])) { // 5.2.2013
	  			$pointsAF[$time] = $this->ConvertToAF($bod,$arenaangle[$time]);
	  		}
	  	}
	  	return $pointsAF;
  	} else {
  		$bodAF = clone $points;
  		/* @var $bod TrackPoint */
  		if(!($bodAF instanceof TrackPoint) ){ //!is_a($bod,"TrackPoint") 
  			echo "ERROR PathData 740: trial $this->trial,\$bod not object\n";
	  		//exit(-1);
	  		return false;
  		}
  		$bodAF->point->Rotate(-$arenaangle,$this->stredareny);
  		$bodAF->viewangle = Angle::Difference($bodAF->viewangle, $arenaangle,true);
  		return $bodAF;
  	}
  }
  /**
   * vraci uhel, kam se clovek dival, kdyz detekovano, ze se zacal otacet
   * @return deg
   */
  public function AngleTurn(){
  		$trial = $this->trial;
  		if(empty($this->angletostarturn[$trial])){
	  		echo "ERROR PathData 616: trial $this->trial, angletostarturn not defined \n";
	  		exit(-1);
	  		return false;   		
  		}
  		return $this->angletostarturn[$trial]["viewangle"];
  }
  /**
   * ulozi cas a smer zacatku otaceni
   * smer 1=doprava (po smeru), -1=doleva (proti smeru)
   * @param double $time
   * @param int $direction -1|1
   * @param CPoint $goalposition aktualni pozice cile
   * @param bool $arenaframe jestli je aktualni cilv arenaframu
   */
  public function TurnedNow($time,$direction,$goalposition, $arenaframe){
  	$this->timetostartturn[$this->trial]=$time-$this->start_time[$this->trial];
  	$smerkcili = $this->smer_otoceni_kcili($goalposition,$arenaframe);
  	if($smerkcili==0){ // kdyz je cil primo pred clovekem, to se asi skoro nestane
  		$this->turnedtogoal[$this->trial] = 1;
  	} else {
  		$this->turnedtogoal[$this->trial] = ($smerkcili == $direction)?1:0; 
  		
  	}
  	$this->angletostarturn[$this->trial] = array("viewangle"=>0,
  		"viewangle0"=>0,"angle1"=>0,"angle2"=>0,
  		"smer_cloveka"=>$direction,"smer_cile"=>$smerkcili);
  		// udaje jen pro kontrolu do vystupni tabulky
  }
  /**
   * zkusti nacit LPT bit z radky. Vraci true, pokud uspesne nacten, false, pokud radka LTP bit neobsahovala
   * ulozi do soucasneho nebo nasledujiciho trialu, podle hodnoty nextTrial
   * @param string $line
   * @param bool $nextTrial
   * @since 12.6.2013
   */
  public function ReadLPT($line,$nextTrial=true){
		if(substr($line, 0,27)=='Setting status of LPT1 byte'){
			$trial = $this->trial + ($nextTrial?1:0);
			list(,$bit,$val)=explode(":",$line);
			$this->LPTbite[$trial][intval($bit)]= (trim($val)=="true"?1:0);
			return true;
		} else {
			return false;
		}
  }
  /**
   * vraci cislo prvniho bitu>0 v soucasnem trialu
   * @return int|boolean
   * @since 12.6.2013
   */
  public function LPTbit(){
  	if(isset($this->LPTbite[$this->trial])){
	  	foreach($this->LPTbite[$this->trial] as $bit=>$val){
	  		if($val>0) return $bit; // pokud je ulozeni v soucasnem trialu bit true, tak vratim prvni v poradi
	  	}
  	}
  	return false;
  }
}

/**
 * vraci prvni klic pole, ktery je vetsi nez $limit
 * pokud zadny klic pole neni vetsi nez limit, vrati false
 * @param array $array
 * @param mixed $limit
 * @return mixed|boolean
 */
function array_key_min($array,$limit){
	$keys = array_keys($array);
	foreach ($keys as $key){
		if($key>=$limit) return $key;
	}
	return false; // kdyz tam limit
}

?>