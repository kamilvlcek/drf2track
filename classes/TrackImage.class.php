<?php
require_once 'classes/TrackPoint.class.php';
require_once 'classes/CPoint.class.php';
require_once 'classes/Image.class.php';
if(!defined('IMAGETYPE')) define('IMAGETYPE','SVG');

class TrackImage {
	/**
	 * obrazek SVG
	 * @var Image
	 */
	private $img;
	private $arenasize;
	private $width;
	/**
	 * stred areny pro track
	 * @var CPoint
	 */
	private $stred;
	
	
	function __construct($arenasize,$stred,$width,$nx,$ny=0) {
		$this->img = new Image($arenasize*2*1.05,$arenasize*2*1.05,$nx,$ny,$width);
  		$this->img->SetReverseY(true);
  		$this->arenasize = $arenasize;
  		$this->width = $width;
  		$this->stred = $stred;
  		
	}
	public function Name($name){
		$this->img->Text(new CPoint(-100,-$this->arenasize+100),150,"black",$name);
	}
	/**
	 * nakresli jeden track od startu k cili
	 * @param array $data
	 * @param int $x
	 * @param int $y
	 * @param string $name
	 * @param CPoint $cilxy
	 * @param float $cilr
	 */
	public function Track($data,$x,$y=0,$name=false,$cilxy=false,$cilr=false,$barvy=false){
		$this->img->SubplotActivate($x,$y);  			
  		$this->img->Circle($this->stred, $this->arenasize,"black",false);
  		if($name){
  			$this->img->Text(new CPoint(-$this->arenasize,-$this->arenasize+50), 100, "black", "Trial ".$name);
  		}
  		//$uhelcile = $cilxy->Angle($this->stred); // kamil 6.9.2011 - otacet tak, aby cil ve vsech trialech na stejne miste?
	    if($cilr){
  			$this->img->Circle($cilxy, $cilr,"red",false,2);
	    }
	    foreach ($data as  $b=>$bod){
	    	/* @var $bod TrackPoint */
	    	if(empty($bod->point)) continue; // 5.2.2013 nasel jsem definovane jen bod->key=U, jako ze ukazal. Ale proc neni definovan point ...
	    	if($bod->key){
	    		if(isset($barvy[$bod->key])){
	    			$barva = $barvy[$bod->key];
	    		} else {
	    			$barva = "black";
	    		}
  				$this->img->Text($bod->point->Sum(new CPoint(10,10)),100,$barva,$bod->key);
	    	} else {
	    		$barva = "green";
	    	}
	    	
  			if(isset($bod->viewangle)){
  				$viewbod = clone $bod->point;
  				$delka = !empty($bod->key) ? $this->arenasize:150;
  				$viewbod->MoveAngleDistance($bod->viewangle,$delka);
  				$this->img->Line($bod->point, $viewbod, $barva); 
  				// "T" = zacatek otaceni
  				// "S" = zacatek trialu
  				if($bod->key) $this->img->Text($viewbod,100,$barva,$bod->key);
  				
  			} // 20.7.2012 - chci vykreslit uhel vsech bodu
	   		

  			if($bod->key /*|| isset($bod->viewangle)*/){
  				$this->img->Point($bod->point, "red", 5,true,true,true); // stlacena klavesa
  			} else {
  				// bezny bod tracku
  				$this->img->Point($bod->point, "blue", $b==0?4:2); // prvni bod tracku vetsi
  			}
	    }
	}
	public function SaveImage($filename){
		$this->img->SaveImage($filename);
	}
	
}

?>