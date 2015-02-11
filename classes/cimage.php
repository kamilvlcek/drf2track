<?php
require_once("includes/point.php");
define('RECALC',1); // jestli automaticky prepocitavat zadane hodnoty na centrum 0,0 v centru plotu

class Cimage {
  var $image;
  var $white, $gray, $black, $blue, $red, $green;
  var $lastpoint; // ukladaji se neprepocitane (zadane) hodnoty
  var $sizex, $sizey;
  var $centerx, $centery;
  var $subplotsx,$subplotsy;
  var $subactivex, $subactivey;
  var $errmsg;
  var $font;
  function Cimage($sizex, $sizey,$subx = 1,$suby = 1){
    $this->sizex = $sizex * $subx;
    $this->sizey = $sizey * $suby;
    if(MAKEIMG){ // pomoci MAKEIMG se da ovlivnit, jestli se vytvori obrazek nebo ne - 27.3.2009
      $this->img = imagecreate($this->sizex,$this->sizey) or die("Nemuzu vytvorit obrazek $this->sizex x $this->sizey");
      $this->white = imagecolorallocate($this->img, 255, 255, 255);
      $this->gray = imagecolorallocate($this->img, 200, 200, 200);
      $this->black = imagecolorallocate($this->img, 0, 0, 0);
      $this->blue = imagecolorallocate($this->img, 0, 0, 255);
      $this->red = imagecolorallocate($this->img, 255, 0,0);
      $this->green = imagecolorallocate($this->img, 0, 255,0);
      $this->orange = imagecolorallocate($this->img, 255, 165,0);
      $this->yellow = imagecolorallocate($this->img, 255, 255,0);
      $this->gold = imagecolorallocate($this->img, 255, 215,0);
      $this->crimson = imagecolorallocate($this->img, 220, 20,60);
      $this->violet = imagecolorallocate($this->img, 238, 130,238);
    }
    $this->subplotsx = $subx;
    $this->subplotsy = $suby;
    for($x=0;$x<$subx;$x++)
      for($y=0;$y<$suby;$y++)
        $this->lastpoint[$x][$y]=array(0,0);
    $this->SubplotActivate(0,0);
    $this->errmsg = "";
    $fontpath = dirname($_SERVER['PHP_SELF']).'\\font\\';
      // oprava 25.2.2010 pro spousteni skriptu z mista dat
      //$fontpath = realpath('./font/');
      putenv('GDFONTPATH='.$fontpath);
      $this->font = "arialbd.ttf";
      $this->font = $souborfontu = $fontpath.$this->font;
      if(!file_exists($souborfontu)){
         echo "soubor fontu: ".$souborfontu." neexistuje!";
         exit;
      } 
  }
  /**
   * udela aktivni subplot, x a y jsou od 0
   *
   * @param int $x
   * @param int $y
   */
  function SubplotActivate($x,$y=false){
    if(is_array($x)){ // kamil 4.7.2008 - xy se muze zadata taky jako array[x,y]
      $y = $x[1];
      $x = $x[0];
    }
    if($x<$this->subplotsx && $x>=0 && $y<$this->subplotsy && $y >=0) {
      $this->subactivex = $x;
      $this->subactivey = $y;
      $xhalf = $this->sizex/$this->subplotsx/2;
      $yhalf = $this->sizey/$this->subplotsy/2;
      $this->setcenter(array($xhalf*(2*$x+1),$yhalf*(2*$y+1)));
    } else {
      $msg = "Subplot [$x,$y] je mimo rozmery [$this->subplotsx,$this->subplotsy]";
      if($this->errmsg != $msg){ // abych nepsal porad tu samou dokolecka
        dp($msg);
        $this->errmsg = $msg;
      }
    }
  }
  function Point($point, $color, $diam, $filled = true,$lastupdate = true){
    if($lastupdate) $this->lastpoint[$this->subactivex][$this->subactivey]=$point; // ulozi se pred prepocitanim
    if(RECALC) $point = $this->makecenter($point);
    if($filled) {
      if(MAKEIMG) imagefilledarc($this->img, $point[0],$point[1], $diam,$diam, 0, 360, $this->$color,'IMG_ARC_PI');
    } else {
      if(MAKEIMG) imagearc($this->img, $point[0],$point[1], $diam,$diam, 0, 360, $this->$color);
    }
  }
  function Line($point1, $point2, $color,$lastupdate = true){
    if($lastupdate) $this->lastpoint[$this->subactivex][$this->subactivey]=$point2; // ulozi se pred prepocitanim
    if(RECALC) {
        $point1 = $this->makecenter($point1);
        $point2 = $this->makecenter($point2);
    }
    if(MAKEIMG) imageline($this->img,$point1[0],$point1[1],$point2[0],$point2[1],$this->$color);

  }
  function Lineto($point, $color,$lastupdate = true){
    $this->Line($this->lastpoint[$this->subactivex][$this->subactivey],$point, $color,$lastupdate);
  }
  function SavePng($filename){
      if(MAKEIMG) imagepng($this->img,$filename);
  }
  function Delete(){
    if(MAKEIMG) imagedestroy($this->img);
  }
  // to translate coordinates from 00 in center to 00 in left top corner
  function makecenter($point) {
    $new = array();
    $new[1]=$point[1] + $this->centery; // Y - aby to doma nebylo prevraceny shora dolu
    $new[0]=$point[0] + $this->centerx; // X
    return $new;
  }
  function setcenter($point){
    $this->centerx = $point[0];
    $this->centery = $point[1];
  }
  function Circle($center, $radius,$color,$filled = true){
    if(RECALC) $center = $this->makecenter($center);
    $diam = $radius*2;
    if($filled) {
        if(MAKEIMG) imagefilledarc($this->img, $center[0],$center[1], $diam,$diam, 0, 360, $this->$color,'IMG_ARC_PI');
    } else {
        if(MAKEIMG) imagearc($this->img, $center[0],$center[1], $diam,$diam, 0, 360, $this->$color);
    }

  }
  function Sector($center, $radius, $center_angle,$width,$from,$color){
    if(RECALC) $center = $this->makecenter($center);
		$diam = $radius*2;
		$diam_sm = $diam*$from/100;
		//$center_angle+=180; // 0 ma smerovat doprava a ted je doleva
		if(MAKEIMG) imagearc($this->img, $center[0],$center[1], $diam,$diam,
			$center_angle-$width/2, $center_angle+$width/2, $this->$color);
		if(MAKEIMG) imagearc($this->img, $center[0],$center[1], $diam_sm,$diam_sm,
			$center_angle-$width/2, $center_angle+$width/2, $this->$color);

		$bod1 = rotate($width/2,array($center[0],$center[1]-$diam_sm));
		$bod2 = rotate($width/2,array($center[0],$center[1]-$diam));
		if(MAKEIMG) imageline($this->img,$bod1[0],$bod1[1],$bod2[0],$bod2[1],$this->$color);

		$bod1 = rotate(-$width/2,array($center[0],$center[1]-$diam_sm));
		$bod2 = rotate(-$width/2,array($center[0],$center[1]-$diam));
		if(MAKEIMG) imageline($this->img,$bod1[0],$bod1[1],$bod2[0],$bod2[1],$this->$color);

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
	 * @param [x,y] $center
	 * @param float $radius
	 * @param float $laser 0-360
	 * @param int $segments 0-3
	 * @param int $startpoint 0-1
	 */
	function Cue($center, $radius,$laser,$segments,$startpoint){
	  if($center < 0) return; // 29.7.2009 16:04:52 - kdyz dam -1, nema se znacka zobrazit
      if(RECALC) $center = $this->makecenter($center);
      $diam = $radius*2;
    //'z 2 udelam 1 a z 1 udelam 8
    $c = $laser; // laser uz je ve stupnich

	  if($segments > 0 && $c>=0){
	    $barvy = array(1=>"red","green","orange");
	    $color = $barvy[$segments];
	    $cleft = $c-8;
	    while($cleft<0) $cleft += 360;
	    $cright = $c+8;
	    while($cright>=360) $cright -= 360;
	    if(MAKEIMG) {
	      imagearc($this->img, $center[0],$center[1], $diam*1.05,$diam*1.05, $cleft, $cright, $this->$color);
	      imagearc($this->img, $center[0],$center[1], $diam*1.06,$diam*1.06, $cleft, $cright, $this->$color);
	      imagearc($this->img, $center[0],$center[1], $diam*1.065,$diam*1.065, $cleft, $cright, $this->$color);
	    }
	  }
	  if($startpoint > 0 && $c>=0){
	    $color = "black";
	    $cleft = $c-5;
	    while($cleft<0) $cleft += 360;
	    $cright = $c+5;
	    while($cright>=360) $cright -= 360;
	    if(MAKEIMG) imagearc($this->img, $center[0],$center[1], $diam*1.03,$diam*1.03, $cleft, $cright, $this->$color);
	  }
	}

	function Text($center,$size,$color,$text){
	  if(RECALC) $center= $this->makecenter($center);
	  if(MAKEIMG) imagettftext($this->img, $size, 0, $center[0], $center[1], $this->$color,  $this->font, $text);
	}

}
?>
