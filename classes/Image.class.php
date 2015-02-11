<?php
require_once("includes/point.php");
require_once 'classes/CPoint.class.php';

require_once 'classes/canvas.interface.php';
require_once("classes/canvas_png.class.php");
require_once 'classes/canvas_svg.class.php';

define('RECALC',1); // jestli automaticky prepocitavat zadane hodnoty na centrum 0,0 v centru plotu
define('IMAGELINEARCOLUMNS',4);
if(!defined('MAKEIMG')) define('MAKEIMG',1);
if(!defined('IMAGETYPE')) define('IMAGETYPE','SVG');
define('MARKNAMES',0); // 15.10.2014 - jestli vypisovat nazvy znacek
class Image {
  var $image;
  var $white, $gray, $black, $blue, $red, $green;
  var $lastpoint; // ukladaji se neprepocitane (zadane) hodnoty
  public $sizex, $sizey; // velikost v jednotkach vysledneho SVG (takze ne tracku)
  var $centerx, $centery; // centrum kazdeho subplotu v jednotkach SVG (ne tracku)
  var $subplotsx,$subplotsy;
  var $subactivex, $subactivey;
  var $errmsg;
  var $font;
  private $linear = false; //jestli se udava jen jeden rozmer subplotu. rozdeli se pak po 4 na jednotlive radky
  private $reverseY = true; // jestli ma osa Y zacinat nahore, default ano
  /**
   * cim se maji souradnice vynasobit
   * @var double
   */
  private $ratio = 1;
  
  /**
   * stred obrazku v jednotkach tracku (ne SVG)
   * @var CPoint
   */
  private $stred;
  /**
   *
   * @var iCanvas
   */
  private $CCanvas;
  /**
   * barvy znacek podle cisel segmentu
   * @var array
   */
  public $barvy_segments = array(1=>"red","green","orange","teal","magenta","gold","cyan","pink");
  /**
   * sizex a y jsou velikosti jednotlivych obrazku v jednotkach tracku
   * subx a suby je velikost matice obrazku 
   * stred kazdeho obrazku ma souradnice stred
   * $sizeScreenX pokud chci jine rozliseni obrazku nez sizex, musim zde zadat pozadovanou velikost jednoho obrazku
   * 
   * @param double $sizex Velikost jednotlivych obrazku v jednotkach tracku
   * @param double $sizey
   * @param int $subx Pocet obrazku v matici X x Y
   * @param int $suby
   * @param bool $sizeScreenX Velikost X v jednotkach vysledneho obrazku 
   * @param CPoint $stred Souradnice stredu obrazku v jednotkach tracku
   */
  function Image($sizex, $sizey,$subx = 1,$suby = 0,$sizeScreenX=false,$stred = false){
  	if($suby==0){
  		$this->linear = true; // 1 rozmer subplotu budu prepocitavat na 4 sloupce
  		$subx2 = min(IMAGELINEARCOLUMNS,$subx); // minimum z poctu trial a cisla 4
  		$suby2 = intval(($subx-1)/IMAGELINEARCOLUMNS)+1;
  		$subx = $subx2; $suby = $suby2;
  	}
    if($sizeScreenX) $this->ratio = $sizeScreenX/$sizex;
    $this->stred = ($stred)?$stred:new CPoint(0,0);
    
  	$this->sizex = $sizex * $subx * $this->ratio; // velikost v jednotkach SVG 
    $this->sizey = $sizey * $suby * $this->ratio;
    
    if(MAKEIMG){ // pomoci MAKEIMG se da ovlivnit, jestli se vytvori obrazek nebo ne - 27.3.2009
		  if(IMAGETYPE=='PNG')
		    $this->CCanvas = new Canvas_PNG($this->sizex,$this->sizey);
		  else
		    $this->CCanvas = new Canvas_SVG($this->sizex,$this->sizey);
    }
    $this->subplotsx = $subx;
    $this->subplotsy = $suby;
    for($x=0;$x<$subx;$x++)
      for($y=0;$y<$suby;$y++)
        $this->lastpoint[$x][$y]=array(0,0);
    $this->SubplotActivate(0,0);
    $this->errmsg = "";

  }
  /**
   * udela aktivni subplot, x a y jsou od 0
   *
   * @param int $x
   * @param int $y
   * @return bool
   */
  function SubplotActivate($x,$y=false){
  	if($this->linear){
  		$xx = $x;
  		$x = $xx % IMAGELINEARCOLUMNS;
  		$y = intval($xx/IMAGELINEARCOLUMNS);
  	} elseif(is_array($x)){ // kamil 4.7.2008 - xy se muze zadata taky jako array[x,y]
      $y = $x[1];
      $x = $x[0];
    }
    if($x<$this->subplotsx && $x>=0 && $y<$this->subplotsy && $y >=0) {
      $this->subactivex = $x;
      $this->subactivey = $y;
      $xhalf = $this->sizex/$this->subplotsx/2;
      $yhalf = $this->sizey/$this->subplotsy/2;
      $this->setcenter(array($xhalf*(2*$x+1),$yhalf*(2*$y+1)));
      return true;
    } else {
      $msg = "Subplot [$x,$y] je mimo rozmery [0-($this->subplotsx-1),0-($this->subplotsy-1)]";
      if($this->errmsg != $msg){ // abych nepsal porad tu samou dokolecka
        dp($msg);
        $this->errmsg = $msg;
      }
      return false;
    }
  }
  /**
   * predpoklada kladne y dolu
   * @param [x,y] $point
   * @param string $color
   * @param float $diam
   * @param bool $filled
   * @param bool $lastupdate
   * @param bool $force
   */
  function Point($point, $color, $diam, $filled = true,$lastupdate = true,$force=false){
  	  if(!pointsame($point,$this->lastpoint[$this->subactivex][$this->subactivey]) || $force){
      if($lastupdate) $this->lastpoint[$this->subactivex][$this->subactivey]=$point; // ulozi se pred prepocitanim
      if(RECALC) $point = $this->makecenter($point);
      if(MAKEIMG) $this->CCanvas->Circle($point,$diam,$color,$filled);
  	}
  }
  /**
   * nakresli caru
   * @param array $point1
   * @param array $point2
   * @param string $color
   * @param bool $lastupdate
   * @param int $width
   */
  function Line($point1, $point2, $color,$lastupdate = true,$width=false){
	    if($lastupdate) $this->lastpoint[$this->subactivex][$this->subactivey]=$point2; // ulozi se pred prepocitanim
	    if(RECALC) { $point1 = $this->makecenter($point1);    $point2 = $this->makecenter($point2);   }
	    if(MAKEIMG) $this->CCanvas->Line($point1,$point2,$color,$width);
  }
  /**
   * nakresli caru jmena $name do dalsiho bodu; pokud line=false jen presune bod
   * @param array[x][y] $point
   * @param bool $lastupdate jestli bod ukladat jako posledni bod tracku
   * @param string $colormark
   * @param int $diammark
   * @param string $name
   * @param bool $line jestli kreslit caru
   */
  function Lineto($point, $lastupdate = true,$colormark=false,$diammark=false,$name='line',$line=true){//$point, $color,$lastupdate = true){
  	if(!pointsame($point,$this->lastpoint[$this->subactivex][$this->subactivey])){
	    if($lastupdate) $this->lastpoint[$this->subactivex][$this->subactivey]=$point; // ulozi se pred prepocitanim
	    if(RECALC) $point = $this->makecenter($point);
	    if(MAKEIMG) $this->CCanvas->PathAddNode($name,$point,$colormark,$diammark,$line);
	    //$this->Line($this->lastpoint[$this->subactivex][$this->subactivey],$point, $color,$lastupdate);
  	}
  }
  /**
   * zacatek cary daneho jmena;
   * Ulozi se jeji barva a tlouska; pokud $diammarker>0, prida se Marker 
   * 
   * @param string $colorline barva cary
   * @param string $colormarker barva znacky
   * @param int $diammarker velikost znacky
   * @param string $name jmeno cary
   */
  function LineTrackStart($colorline,$colormarker,$diammarker,$name='line'){
    $this->CCanvas->PathStart($name,$colorline);
    if($diammarker>0) $this->CCanvas->PathAddCircleMarker($name,$diammarker,$colormarker,true);
  }
  /**
   * jmeno souboru se udava bez pripony. Tu si prida canvas a vrati jmeno vysledneho souboru
   *
   * @param string $filename
   * @return string
   */
  function SaveImage($filename){
      if(MAKEIMG) return $this->CCanvas->SaveImg($filename);
  }
  function Delete(){
    if(MAKEIMG) unset($this->CCanvas);
  }
  /**
   *  translate coordinates from 00 in center to 00 in left top corner
   *  @param array $point
   */
  private function makecenter($point) {
    $new = array();
    $new[1]=$this->centery + ($point[1]-$this->stred->x)*($this->reverseY?1:-1)*$this->ratio; // Y - aby to doma nebylo prevraceny shora dolu
    $new[0]=$this->centerx + ($point[0]-$this->stred->y)*$this->ratio; // X
    return $new;
  }
  private function setcenter($point){
    $this->centerx = $point[0];
    $this->centery = $point[1];
  }
  /**
   * Default ma obrazek 0,0 vlevo nahore -
   * Kdyz chci aby bylo 0,0 vlevo dole, musim zde zadat false. 
   * @param bool $reverseY
   */
  public function SetReverseY($reverseY=false){
  	$this->reverseY=$reverseY;
  }
  
  function Circle($center, $radius,$color,$filled = true,$width=2){
    if(RECALC) $center = $this->makecenter($center);
    $diam = $radius*2*$this->ratio;
    if(MAKEIMG) $this->CCanvas->Circle($center,$diam,$color,$filled,$width);
  }
  function Sector($center, $radius, $center_angle,$width,$from,$color){
    if(RECALC) $center = $this->makecenter($center);
		$diam = $radius*2*$this->ratio;
		$diam_sm = $diam*$from/100;
		//$center_angle+=180; // 0 ma smerovat doprava a ted je doleva
		if (MAKEIMG){
			$this->CCanvas->ArcAngles($center,$diam,      $center_angle-$width/2,$center_angle+$width/2,$color);
			$this->CCanvas->ArcAngles($center,$diam_sm   ,$center_angle-$width/2,$center_angle+$width/2,$color);
		}

		$bod1 = rotate($width/2,array($center[0],$center[1]-$diam_sm));
		$bod2 = rotate($width/2,array($center[0],$center[1]-$diam));
		if(MAKEIMG) $this->CCanvas->Line($bod1,$bod2,$color);

		$bod1 = rotate(-$width/2,array($center[0],$center[1]-$diam_sm));
		$bod2 = rotate(-$width/2,array($center[0],$center[1]-$diam));
		if(MAKEIMG) $this->CCanvas->Line($bod1,$bod2,$color);

  }
	function cumulative($values,$color){ // v posledni hodnote  predpoklada celkovy pocet framu
		if(!is_array($values)) return;
		$stepy = $this->sizey/count($values);
		$stepx = $this->sizex/$values[count($values)-1];
		$y = $this->sizey;
		$this->Point(array(0,$y),$color,1,true);
		foreach ($values as $val){
			$x = $val*$stepx;
			$this->Lineto(array($x,$y),$color);
			$y-=$stepy;
			$this->Lineto(array($x,$y),$color);
		}

	}
	/**
	 * vykresli cue i start
	 * @param [x,y] $center stred areny
	 * @param int $radius polomer areny
	 * @param deg $laser uhlova pozice znacky
	 * @param int $segments barva znacky - segmenty na stene BVA
	 * @param int $startpoint 0/1 jestli se jedna o start
	 * @param string $markname jmeno znacky
	 */
	function Cue($center, $radius,$laser,$segments,$startpoint,$markname=''){
	  if($laser < 0) return; // 29.7.2009 16:04:52 - kdyz dam -1, nema se znacka zobrazit
      if(RECALC) $center = $this->makecenter($center);
      $diam = $radius*2*$this->ratio;
    //'z 2 udelam 1 a z 1 udelam 8
    $c = $laser; // laser uz je ve stupnich

	  if($segments > 0 && $c>=0){
	    $color = $this->barvy_segments[$segments];
	    $cleft = $c-8;
	    while($cleft<0) $cleft += 360;
	    $cright = $c+8;
	    while($cright>=360) $cright -= 360;
	    if(MAKEIMG) { // tri cary vedle sebe, aby byla poradne videt. Tlusta cara udelat nejde
	      $this->CCanvas->ArcAngles($center,$diam*1.05,$cleft,$cright,$color);
	      $this->CCanvas->ArcAngles($center,$diam*1.06,$cleft,$cright,$color);
	      $this->CCanvas->ArcAngles($center,$diam*1.065,$cleft,$cright,$color);
	      if(!empty($markname) && MARKNAMES){
	      	$xy = angledist2xy(deg2rad($c),$diam/2);
	      	$this->Text($xy,12,'black',$markname);
	      }

	    }
	  }
	  if($startpoint > 0 && $c>=0){
	    $color = "black";
	    $cleft = $c-5;
	    while($cleft<0) $cleft += 360;
	    $cright = $c+5;
	    while($cright>=360) $cright -= 360;
	    if(MAKEIMG) $this->CCanvas->ArcAngles($center,$diam*1.03, $cleft, $cright,$color);
	  }
	}

	function Text($center,$size,$color,$text){
	  if(RECALC) $center= $this->makecenter($center);
	  if(MAKEIMG) $this->CCanvas->Text($center,$size*$this->ratio,$color,$text);
	}
	/**
	 * nakresli sekce areny
	 * @param int $n
	 * @param [x,y] $center
	 * @param float $radius
	 * @param string $color
	 * @param bool $kolemstredu
	 * @param deg $stred 
	 */
	public function Sections($n,$center, $radius,$color,$kolemstredu=true,$stred=0){
	  if($n>0){
		  $posun = ($kolemstredu?360/$n/2:0) -$stred; // parametr stred - kde ma byt pocatek sekce, jsem pridal 3.10.2014
		  for($uhel=0;$uhel<360;$uhel+=360/$n){
		    $bod1 = bodadd(angledist2xy(deg2rad($uhel+$posun),$radius),$center);
		    $bod2 = bodadd(angledist2xy(deg2rad($uhel+$posun),$radius+7),$center);
		    $this->Line($bod1,$bod2,$color,false);
		  }
	  }
	}
	/**
	 * nastavi sirku cary pro svg obrazky
	 * @param int $width
	 */
	public function SetWidth($width){
		$this->CCanvas->SetWidth($width); // 27.3.2012
	}

}
?>
