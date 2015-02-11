<?php

function tocenter($bod,$center){
  $new = array();
  $new[0]=$bod[0]-$center[0];
  $new[1]=$bod[1]-$center[1];
  return $new;
}
/**
 * prepocte bod v pomeru dvou polomeru kruhu, bod musi byt relativne ke stredu [0,0]
 * $diam1 je cilovy, $diam0 je vstupni
 *
 * @param [x,y] $bod
 * @param int $diam0
 * @param int $diam1
 * @return [x,y]
 */
function todiam($bod,$diam0,$diam1){
  $ratio = $diam1/$diam0;
  return array($bod[0]*$ratio,$bod[1]*$ratio);
}
function todiamxy($bod,$diam0,$diam1){
  $ratiox = $diam1[0]/$diam0[0];
  $ratioy = $diam1[1]/$diam0[1];
  return array($bod[0]*$ratiox,$bod[1]*$ratioy);
}
/**
 * Enter description here...
 *
 * @param point $bod
 * @return point
 */
function makecenter($bod){
  $new = array();
  $new[1]=ARENAIMAGER*2-($bod[1]+ARENAIMAGER);
  $new[0]=$bod[0]+ARENAIMAGER;
  return $new;
}
/*function makecuexy($angle){
  return rotate($angle,tocenter(array(450,240)));
}*/
/**
 * otoci bod o uhel ve stupnich okolo pozice [0,0]
 *
 * @param deg $angle
 * @param [x,y] $bod
 * @return [x,y] 
 */
function rotate($angle, $bod){
  $new = array();
  //$new = $bod;
  $angle = deg2rad(360-$angle); // convert to clock-wise (this is how the angles in hgt are made)
  $new[0]=($bod[0]*cos($angle)-$bod[1]*sin($angle));
  $new[1]=($bod[0]*sin($angle)+$bod[1]*cos($angle));
  return $new;
}
/**
 * otoci bod o uhel ve stupnich okolo pozice $center
 * @param deg $angle
 * @param [x,y]  $bod
 * @param [x,y] $center
 * @return [x,y] 
 */
function rotatecenter($angle,$bod,$center){
  $bod = boddiff($bod,$center);
  $bod = rotate($angle,$bod);
  $bod = bodadd($bod,$center);
  return $bod;
}
/**
 * teziste bodu - prumer x a y souradnic
 *
 * @param array[0,1] $body
 * @return bod[0,1]
 */
function gravity($body){
  $center = array(0=>0,1=>0);
  foreach($body as $bod){
    $center[0]+=$bod[0];
    $center[1]+=$bod[1];
  }
  $center[0]/=count($body);
  $center[1]/=count($body);
  return $center;
}
/**
 * median z bodu - zvlast z x a y souradnic
 *
 * @param  array[0,1] $body
 * @return bod[0,1]
 */
function median_points($body){
  $x = array();
  $y = array();
  foreach($body as $bod){
    $x[] =$bod[0];
    $y[] =$bod[1];
  }
  return array(0=>median($x),1=>median($y));
}
function distance($bod1, $bod2){
  return sqrt( pow($bod1[0]-$bod2[0],2) + pow($bod1[1]-$bod2[1],2) );
}
/**
 * 
 * vrati uhel bodu v radianech - y kladne je pi/2
 * @param unknown_type $bod
 */
function angle($bod){
   //echo $bod[0]." ".$bod[1]."\n";
    if($bod[0]==0){ //x==0
      if($bod[1]==0)      return false; //y==0
    else if($bod[1]>0)    return pi()/2; //x==0 a y>0
    else                  return pi()+pi()/2;
  } else if($bod[0]==0){
      if($bod[1]>0)       return 0;
    else                  return pi();
  }

  $a = atan(floatval($bod[1])/$bod[0]);

  //echo $a."\n";
  if($bod[1]<0 && $bod[0]<0)    $a = $a+pi();
  else if($bod[1]<0 )           $a = pi()*2+$a;
  else if($bod[0]<0 )           $a = pi()+$a;
  return $a;
}
/**
 * vrati uhel relativne ke stredu center
 *
 * @param bod[x,y] $bod
 * @param bod[x,y] $center
 * @return double
 */
function angletocenter($bod,$center){
  $bod = tocenter($bod,$center);
  return angle($bod);
}
/**
 * vraci uhel ve stupnich -180 az +180
 *
 * @param [x,y] $bod1
 * @param [x,y] $bod2
 * @return double
 */
function anglediff($bod1,$bod2){
  $diff = rad2deg(angle($bod1)-angle($bod2));
  if($diff > 180) $diff -= 360;
  if($diff < -180) $diff += 360;

  return $diff;

}
/**
 * vraci uhlovy rozdil vzhledem k centeru
 *
 * @param [x,y]  $bod1
 * @param [x,y]  $bod2
 * @param [x,y]  $center
 * @return double deg
 */
function anglediffcenter($bod1,$bod2,$center){
  return anglediff(boddiff($bod1,$center),boddiff($bod2,$center));
}
/**
 * vraci rozdil mezi dvema body $bod1 - $bod2
 *
 * @param [x,y] $bod1
 * @param [x,y]  $bod2
 * @return [x,y]
 */
function boddiff($bod1,$bod2){
  return array($bod1[0]-$bod2[0],$bod1[1]-$bod2[1]);
}
function bodadd($bod1,$bod2){
  return array($bod1[0]+$bod2[0],$bod1[1]+$bod2[1]);
}
function nasobit($point,$nasobek){
  return array($point[0]*$nasobek,$point[1]*$nasobek);
}
function frames2time($frames){
	$min = (int)($frames/(25*60));
	$sec = round(60*($frames/(25*60)-$min),2);
	return "0:$min:$sec";
}
function frames2sec($frames){
	return $frames/25;
}
function BodySame($bod1,$bod2){

    	if ($bod1[0]==$bod2[0]
    			&& $bod1[1]==$bod2[1])
    	 return true;
    	else return false;
}
/**
 * prolozi primku dvema body (prvnim z $prolozit a $podle)
 * a nahradi zvyvajici body v $prolizit body teto primky
 *
 * @param unknown_type $prolozit
 * @param unknown_type $podle
 * @return unknown
 */
function prolozit_primku($prolozit,$podle){
	$stepx = ($podle[0]-$prolozit[0][0])/count($prolozit);
	$stepy = ($podle[1]-$prolozit[0][1])/count($prolozit);
	for ($i=1;$i<count($prolozit);$i++){
		$prolozit[$i][0]=$prolozit[0][0]+$i*$stepx;
		$prolozit[$i][1]=$prolozit[0][1]+$i*$stepy;
	}
	return $prolozit;
}
/**
 * prolozi primku zadanymi body metodou nejmensich ctvercu
 *  f(x) = ax + b
 * viz http://cs.wikipedia.org/wiki/Lineární_regrese
 *  *
 * @param array $body
 * @return array[a,b]
 */
function least_squares($body){
    $sumax = $sumay = $sumaxy = $sumax2 = 0;
    foreach($body as $bod){
        $sumax += $bod[0];
        $sumay += $bod[1];
        $sumaxy+= $bod[0]*$bod[1];
        $sumax2+= $bod[0]*$bod[0];
    }
    $n = count($body);
    $jmenovatel = $n*$sumax2-$sumax*$sumax;
    if($jmenovatel==0){
        $a=0;
        $b=$sumay/$n; // vsechny body jsou totozne a nebo hodne blizke
    } else {
        $a = ($n*$sumaxy-$sumax*$sumay)/$jmenovatel;
        $b = ($sumax2*$sumay-$sumax*$sumaxy)/$jmenovatel;
    }
    return array("a"=>$a,"b"=>$b);
}
/**
 * spocte primku ax+b podle x zadanych bodu
 * vrati prepocitane body
 *
 * @param unknown_type $a
 * @param unknown_type $b
 * @param unknown_type $body
 * @return unknown
 */
function primka($a,$b,$body){
   foreach ($body as $i=>$bod) {
   	 $body[$i][1]=$a*$bod[0]+$b;
   }
   return $body;
}
/**
 * vraci obecnou rovnici primky y=ax + by + c
 * ktera je definovana dvema body
 * pro svislou primku je b = 1, pro ostatni b=-1
 *
 * @param [x,y] $bod1
 * @param [x,y] $bod2
 * @return [a,b,c]
 */
function primka2body($bod1,$bod2){
  if($bod1[0]==$bod2[0]){ // pokud je primka svisla
    return array(0,1,-$bod1[0]); // obecna rovnice svisle primky
  } else { // svisla primka
    $p1 = ($bod1[1]-$bod2[1])/($bod1[0]-$bod2[0]); // smernice
    $p0 = $bod1[1]-$p1*$bod1[0]; // absolutni cast
    return array($p1, -1, $p0); // potrebuju obecnou rovnici primky, kvuli primkam ktere jsou rovnobezne s y
  }
}
/**
 * vraci obecne rovnice dvou primek, rovnobeznych se zadanou, ve vzdalenosti dist
 * pro svislou primku je b = 1 a c=-x, pro ostatni b=-1, a=smernice p1, c=konstanta p0
 *
 * @param [a,b,c] $primka
 * @param float $dist
 * @return [0-1][a,b,c]
 */
function primka2rovnobezne($primka,$dist){
 if($primka[1]==-1){ // neni svisla
    $p1 = $primka[0];
    $q0=$primka[2] + $dist/cos(atan($p1));
    $r0=$primka[2] - $dist/cos(atan($p1));
    return array(array($p1, -1, $q0),array($p1,-1,$r0));
 } else { // svisla primka
    return array(array(0,1,$primka[2]-$dist),array(0,1,$primka[2]+$dist));
 }
}
/**
 * vraci true pokud je bod mezi dvema rovnobezkami
 * pro svislou primku je b = 1 a c=-x, pro ostatni b=-1, a=smernice p1, c=konstanta p0
 *
 * @param [x,y] $bod
 * @param [a,b,c] $primka1
 * @param [a,b,c] $primka2
 * @return bool
 */
function bodmeziprimkami($bod,$primka1,$primka2){
  if($primka1[1]==-1){ // neni svisla
    $p1 = $primka1[0];
    $q0 = $primka1[2]; $r0 = $primka2[2]; // prevedu na smernicovy tvar

    if($bod[1]>min($q0+$bod[0]*$p1,$r0+$bod[0]*$p1) && $bod[1]<max($q0+$bod[0]*$p1,$r0+$bod[0]*$p1)){
      return true; // je mezi nimi
    } else {
      return false;
    }
  } else { // svisla primka
    if($bod[0]>min(-$primka1[2],-$primka2[2]) && $bod[0] < max(-$primka1[2],-$primka2[2])){
      return true;
    } else {
      return false;
    }
  }
}
/*
SUB PPangledist2xy (angle, dist, x, y)
' 0 deg is on the right, angle in radians
	x = COS(angle) * dist
	y = SIN(angle) * dist
END SUB*/
/**
 * prijima uhel v radianech
 *
 * @param rad $angle
 * @param float $dist
 * @return [x,y]
 */
function angledist2xy($angle,$dist){
  $x = cos($angle) * $dist;
  $y = sin($angle) * $dist;
  return array($x,$y);
}
function bodmove($bod,$vector){
	return array($bod[0]+$vector[0],$bod[1]+$vector[1]);
}

function center($bod1,$bod2){
  return array(($bod1[0]+$bod2[0])/2,($bod1[1]+$bod2[1])/2);
}
function diam($bod1,$bod2){
  return array(abs($bod2[0]-$bod1[0]),abs($bod2[1]-$bod1[1]));
}
function fromcenter($bod,$center){
  return array($bod[0]+$center[0],$bod[1]+$center[1]);
}
//$a = angledist2xy(deg2rad(90),10);
function tosize($bod,$min0,$max0,$min1,$max1){
  $bod = tocenter($bod,center($min0,$max0)); // nejdriv ho prepocitam na rozdil vuci nule.
  $bod = todiamxy($bod,diam($min0,$max0),diam($min1,$max1));
  $bod = fromcenter($bod,center($min1,$max1));
  return $bod;
}
/**
 * vrati maximum z dvou bodu (kazda souradnice oddelene)
 *
 * @param [x,y] $bod
 * @param [x,y] $max
 * @return [x,y]
 */
function pointmax($bod,$max) {
  return array(max($bod[0],$max[0]),max($bod[1],$max[1]));
}
function pointmin($bod,$max) {
  return array(min($bod[0],$max[0]),min($bod[1],$max[1]));
}
/**
 * vrati true pokud body stejne
 *
 * @param [x,y] $bod1
 * @param [x,y] $bod2
 * @return true
 */
function pointsame($bod1,$bod2){
  if($bod1[0]==$bod2[0] && $bod1[1]==$bod2[1])
    return true;
  else
    return false;
}


?>