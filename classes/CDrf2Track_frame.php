<?php

/**
 * sdruzuje informace pro tridu Drf2Track specificke pro jeden frame
 * @author Kamil
 *
 */
class CDrf2Track_frame {
  var $lastxy; //posledni kreslene souradnice bodu / array[n][x,y]
  var $cilxy; // souradnice posleniho cile
  var $startxy; // souradnice posledniho startu
  var $lasteyexy; // posledni souradnice eyexy;
  var $trialdistance; // kolik clovek usel behem pokusu
  var $cilc; // uhel posledniho cile
  public $name;
  function __construct($name){
  	$this->name = $name;
  }
  /**
   * vrati true pokud framename souhlasi s activeframe
   * @param string $framename
   * @param string $activeframe
   * @return boolean
   */
  static function FrameIsActive($framename,$activeframe){
  	return ($activeframe=="ARENA" && $framename=="AF") || ($activeframe=="ROOM" && $framename=="RF");
  }
  /**
   * vrati RF z ROOM a AF z ARENA
   * @param string $activeframe
   * @return string
   */
  static function FrameName($activeframe){
  	if($activeframe=="ARENA") {
  		return "AF";
  	}	elseif($activeframe=="ROOM") {
  		return "RF";
  	}  	else {
  		debug_print_backtrace();
		trigger_error("neznamy FrameName $activeframe",E_USER_ERROR);
  		return false;
  	} 
  }
  	
  public function ResetTrial(){
  	$this->trialdistance =0;
  }
  
}


?>