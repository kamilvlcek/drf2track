<?php
if(!defined('KVADRANTY')) define('KVADRANTY',4);

/**
 * trida na vyhodnoceni podilu vyskytu v jednotlivych kvadrantech kruhove areny
 * kvadrant 0 je vysec areny se stredem v cili.
 * 
 * @author Kamil
 *
 */
class Kvadranty {
  private $kvadranty=array();
  private $pocet=array(); //27.11.2014 - budu pracovat s polem cilu. Pokud bude jeden (skoro vzdy), bude to cil cislo 0

  function __construct(){
  	$this->Reset();
  }
  /**
   * pridam dalsi uhel mezi cilem a aktualni pozici
   * @param deg $uhel -180 az 180
   */
  public function Add($uhel,$goalname=0){
  	if(isset($this->pocet[$goalname])){
  		$this->pocet[$goalname]++;
  	} else {
  		$this->pocet[$goalname]=1;
  	}
    $velikost_kvadrantu = 360/KVADRANTY;
    if($uhel>0){
        $kvadrant = intval(($uhel+$velikost_kvadrantu/2)/$velikost_kvadrantu);
    } else {
        $kvadrant = KVADRANTY + intval(($uhel-$velikost_kvadrantu/2)/$velikost_kvadrantu);
    }
    if($kvadrant == KVADRANTY) $kvadrant = 0;
    if(isset($this->kvadranty[$kvadrant][$goalname])){  
      	$this->kvadranty[$kvadrant][$goalname]++;
    } else {
      	$this->kvadranty[$kvadrant][$goalname]=1;
    }
  }
  /**
   * vrati podily 0-1 v jednotlivych kvadrantech
   * @return array
   */
  public function Podily($goalname=0){
  	$podily = array();
  	foreach($this->kvadranty as $key=>$kvadrant){
  		if(!isset($kvadrant[$goalname])) $kvadrant[$goalname]=0; // do kvadrantu za cely trial nevesel
  		$podily[$key] = $this->pocet[$goalname]>0 ? $kvadrant[$goalname]/$this->pocet[$goalname]: 0;
  	}
  	ksort($podily);
  	
  	return $podily;
  }
  /**
   * vrati jmena kvadrantu do tabulky
   * @return array
   */
  public function Kvadranty_names(){
    $names = array(0=>"cilovykvadrant");
    for($i=1;$i<KVADRANTY;$i++){
      $names[$i]="kvadrant$i";
    }
    ksort($names);
    return $names; // seradi podle cisel kvadrantu
  }
  /**
   * vymaze ulozene hodnoty
   */
  public function Reset(){
  	$this->pocet = array(0=>0); //27.11.2014 - budu pracovat s polem cilu. Pokud bude jeden (skoro vzdy), bude to cil cislo 0
  	$this->kvadranty = array();
  	for($i=0;$i<KVADRANTY;$i++){
  		$this->kvadranty[$i]=array(0=>0);
  	}
  }
}

?>