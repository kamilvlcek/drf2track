<?php

require_once 'classes/CPoint.class.php';

/**
 * shromazduje udaje o jednom trialu
 * @author Kamil
 *
 */
class TrialData {
	public $trial;
	
	public $time;
	public $arenaspeeed;
	public $goalname;
	
	
	// UDAJE NA STARTU
	/**
	 * @var CPoint
	 */
	private $startposition;
	private $startangle;
	private $starttime;
	
	// UDAJE PRI STLACENI KLAVESY
	/**
	 * @var CPoint
	 */
	private $keyposition;
	/**
	 * @var CPoint
	 */
	private $keygoalposition;
	private $keyangle;
	public  $keytime; // cas do stlaceni klavesy
	public  $keytime_press; // cas stlaceni klavesy
	
	public  $key_angle_error_data; // docasne - z ceho se pocita key angle error 
	
	/**
	 * vytvori trial 
	 * @param double $time
	 * @param CPoint $position
     * @param deg $angle
	 */
	function __construct($time,$position,$viewangle) {
		$this->starttime = (double) $time;
		$this->trial = 0;
    	$this->reset();
		$this->startposition = $position;
    	$this->startangle = Angle::Normalize($viewangle);
	}
	/**
	 * nastavi zacatek trialu
	 * @param double $time
	 * @param CPoint $position
	 * @param deg $angle
	 */
	function NextTrial($time,$position,$viewangle){
		$this->reset();
		$this->trial++;
		$this->starttime = (double) $time;
		$this->startposition = $position;
		$this->startangle = Angle::Normalize($viewangle);
	}
	private function reset(){
		$this->time = 0;
	    $this->keytime = 0;
	    unset($this->keyangle);
	    unset($this->keygoalposition); 
	    unset($this->keyposition); 
	    unset($this->keytime); 
	    $this->keytime_press = 0;
	}
	/**
	 * @param double $time
	 * @param CPoint $position
	 * @param deg $angle
	 * @param CPoint $goalposition
	 * @param double $arenaangle
	 */
	function AddKey($time,$position,$viewangle,$goalposition){
		if($time<$this->starttime) $this->starttime-=1; //177.906  177.953 178.996  178.042  178.085 - obcas se objevi takovehle casy, starttime je 178.996
		// 10.4.2013 - ale kdyz je klavesa zmacknuta moc pozde, tak tohle nezafunguje, protoze time klavesy neni mensi nez startime. Musel bych kontrolovat vsechny casy
		// v logu UT ve spanavu 26.3.2013 se tyhle chyby asi nestavaj
		$this->keytime = (double)$time-$this->starttime;
		
		$this->keytime_press = (double) $time;
		$this->keyposition = $position;
		$this->keyangle = Angle::Normalize($viewangle);
		$this->keygoalposition = $goalposition;
	}
	/**
	 * trial je ukoncen v case $time
	 * @param double $time
	 * @param string $goalname
	 */
	function End($time,$goalname){
		$this->time = (double)$time-$this->starttime;
		$this->goalname = $goalname;
		
	}
	/**
	 * vrati delku trialu
	 */
	function Time(){
		return $this->time;
	}
	public function KeyTime(){
		if(isset($this->keytime)){
			return $this->keytime;
		} else {
			return 0;
		}
	}
	public function StartTime(){
		return $this->starttime;
	}
	public function KeyTimePress(){
		return $this->keytime_press;
	}
	/**
	 * vraci uhlovou chybu vzhledem k cili pri stlaceni klavesy
	 * @return deg
	 */
	public function KeyAngleError(){
		if(isset($this->keygoalposition) && isset($this->keyposition)){
			$anglecil = $this->keygoalposition->Angle($this->keyposition);
			$this->key_angle_error_data=array("anglecil"=>$anglecil,"keyangle"=>$this->keyangle,"goalxy"=>$this->keygoalposition);
			return Angle::Difference($this->keyangle, $anglecil,true);
			
		} else {
			$this->key_angle_error_data=array("anglecil"=>0,"keyangle"=>0,"goalxy"=>new CPoint(0,0)); // do matlabu musi jit nuly
			return 0;
		}
	}
	/**
	 * vrati uhlel potrebny k ukazani na cil
	 * takze rozdil mezi natocenim v prvnim bode trialu a uhlem k cili
	 * @return deg
	 */
	public function AngleToGoal(){
		if(isset($this->keygoalposition) && isset($this->keyposition)){
			$anglecil = $this->keygoalposition->Angle($this->startposition); // uhel cile v miste startu trialu
			return Angle::Difference($this->startangle, $anglecil,true);
		} else {
			return 0;
		}
	}
	
	
}

?>