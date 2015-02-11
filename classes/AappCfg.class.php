<?php
if(!defined('AAPPCFG')){
define('AAPPCFG',1);

require_once ('classes/spanavcfg.class.php');
define('CFGCLASS','AappCfg');
class AappCfg extends SpaNavCfg {
  protected function IsMark($name){
  	return strpos($name,"Mark")>strlen($name)-6;
  	//(strpos($name,"Mark")!==false && strpos(trim($name)," ")===false); 
  	// znacka je cokoliv co obsahuje slovo Mark na konci nazvu
  }
  protected function IsAim($name){
  	return strpos($name,"Goal")>strlen($name)-6;
  }
   /**
   * vraci novy objekt teto tridy
   * vola se pomoci call_user_func(array($classname,'Factory')), kde je mozne specifikovat jmeno tridy
   * @return AappCfg
   */
  static function Factory(){
  	return new AappCfg(false);
  }
  public function MarkShortName($name){ 
  	return $name; // v AAPP chci vracet plne jmeno znacky
  }
  public function ArenaMinX($track){
  	return -4588;
  }
  public function ArenaMaxX($track){
    return -808;
  }
  public function ArenaMinY($track){
    return 824;
  }
  public function ArenaMaxY($track){
    return 4496;
  }
}

}

?>