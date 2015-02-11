<?php
require_once('includes/point.php');
require_once 'classes/CPoint.class.php';
require_once 'classes/CFileName.class.php';

/**
 * trida pro zpracovani pohybu v tracku
 * @author Kamil
 *
 */
class PointMove {
  private $pohnul_se;
  private $pocet_zastaveni;
  /**
   * @var CPoint
   */
  private $lastxy;
  private $speed = array();
  private $speed_log = "speeds.log";
  private $speedchange_log = "speeds_change.log";
  private $zastavil_poprve = false;
  
  function __construct(){
  	if(file_exists($this->speed_log)) unlink($this->speed_log);
  	if(file_exists($this->speedchange_log)) unlink($this->speedchange_log);
  	$this->Reset();
  }
  /**
   * nastavi na zacatek trialu
   */
  public function Reset(){
    $this->pocet_zastaveni = 0;
    unset($this->lastxy);
    $this->pohnul_se = false;
    $this->speed = array();
  }
  /**
   * prida bod do rychlostniho profilu
   * modifikuje lastxy a pohnul se
   * uklada souradnice bodu
   * 
   * @param CPoint $xy
   */
  public function Add($xy){
   if(!empty($this->lastxy)){
      $this->speed[] = round($this->lastxy->Distance($xy),2);
    }
    if(empty($this->lastxy)){
      $this->lastxy = $xy; // na zacatku kazdeho trial
      $this->pohnul_se = false;
    } elseif($xy!=$this->lastxy){
      if(!$this->pohnul_se) $this->pohnul_se = true; // poprve pri pohybu ze startu
      $this->lastxy = $xy; // pri kazde zmene pozice
    } elseif($this->lastxy==$xy && $this->pohnul_se){
      $this->pocet_zastaveni++;  // pri kazdem zastaveni krome startu
      $this->pohnul_se = false;
      $this->zastavil_poprve= $this->pocet_zastaveni == 1; // pri prvnim zastaveni vraci true
    }
    $this->zastavil_poprve=false;
  }
  /**
   * vraci true, pokud se prave clovek poprve zastavil jinde nez na startu - pokud jsou dva body stejne
   *
   *
   * @param [x,y] $xy aktualni xy
   * @return bool
   */
  public function ZastavilPoprve(){
    return $this->zastavil_poprve;
  }
  /**
   * vraci true, pokud se clovek uz alespon jednou behem chuze k cili zastavil
   * predpoklada predchozi volani Add()
   *
   * @return bool
   */
  public function Zastavil(){
    return $this->pocet_zastaveni>=1;
  }

  /**
   * vrati array rychlosti
   * resetuje 
   * 
   * @return array
   */
  public function SpeedProfile(){
  	if(is_array($this->speed)){
  		$speed = $this->speed;
  		//$this->Reset();
  		return $speed;
  		
  	} else 
  		return array();
  }
  /**
   * vrati array zmen rychlosti
   * @return array
   */
  public function SpeedChangeProfile(){
  	$speedchange = array();
  	for($i=1;$i<count($this->speed);$i++){
  		$speedchange[$i]=$this->speed[$i]-$this->speed[$i-1];
  	}
  	return $speedchange; 
  }
  /**
   * vraci cislo bodu, kdy se clovek rozesel 
   * 0 - n-1
   * @return int
   */
  public function Moved(){
  	if(is_array($this->speed) && count($this->speed)>0){
  		$limit = max($this->speed)/2;
  		for($i=0;$i<count($this->speed);$i++){
  		   if($this->speed[$i]>=$limit){
  		   	  return $i;
  		   }
  		}
  	} 	
  }
  public function LogSpeeds($filename,$trial){
  	$moved = $this->Moved();
  	error_log(basename($filename)."\t$trial\t$moved\t".String::setdelim(implode("\t",$this->SpeedProfile()))."\n",3,$this->speed_log);
    error_log(basename($filename)."\t$trial\t$moved\t".String::setdelim(implode("\t",$this->SpeedChangeProfile()))."\n",3,$this->speedchange_log);
  }
   
}

?>