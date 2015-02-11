<?php

/**
 * trida pro bod 2D
 * @author kamil
 *
 */
class CPoint implements ArrayAccess {
	public $x=0;
	public $y=0;
	function __construct($x=false,$y=false) {
	  if(is_array($x) && isset($x[0]) && isset($x[1])){
	  	$this->x = $x[0];
	  	$this->y = $x[1];
	  } elseif( $x instanceof CPoint){
	  	$this->x = $x->x;
	  	$this->y = $x->y;
	  } elseif(is_numeric($x) && is_numeric($y)) {
		  $this->x = $x;
	    $this->y = $y;
	  }
	}
	/**
	 * definuje bod jeho uhlem a vzdalenosti od stredu
	 * @param float $angle 0-360, 90 nahoru
	 * @param float $distance
	 * @return CPoint
	 */
	public function DefineByAngleDistance($angle,$distance){
		$this->x = cos(deg2rad($angle)) * $distance;
	  $this->y = sin(deg2rad($angle)) * $distance;
	  return $this;
	}
	function __toString(){
		return "[".round($this->x,4).",".round($this->y,4)."]";
	}
	/**
	 * posune bod o vektor
	 * @param CPoint $vektor
	 * @return CPoint
	 */
	public function Move($vektor){
		$this->x += $vektor->x;
		$this->y += $vektor->y;
		return $this;
	}
	/**
	 * vrati vektorovy soucet bodu a vektoru ( tj. posun).
	 * Nemeni puvodni bud
	 * @param CPoint $vector
	 * @return CPoint
	 */
	public function Sum($vektor){
		$bod = clone $this;
		/* @var $bod CPoint */
		return $bod->Move($vektor);
	}
	/**
	 * odecte vektor od bodu a vrati vysledek
	 * nemeni vlastni bod
	 * @param CPoint $vektor
	 * @return CPoint
	 */
	public function Diff($vektor){
		return new CPoint($this->x - $vektor->x,$this->y - $vektor->y);
	}
	/**
	 * posune bod o uhel a vzdalenost
	 * @param float $angle 0-360, 90 nahoru
	 * @param float $distance
	 * @return CPoint
	 */
	public function MoveAngleDistance($angle,$distance){
		$vector = new CPoint(0,0);
		$vector->DefineByAngleDistance($angle,$distance);
		$this->Move($vector);
		return $this;
	}
	/**
	 * vrati uhel bodu vzhledem k pocatku [0,0] nebo $center;
	 * ve stupnich 0-360, 90 je nahoru - ke kladnym y souradnicim;
	 * predpoklada y osu kladnou nahoru 
	 * @param CPoint $center
	 * @return deg
	 */
	public function Angle($center=false){
	  if($center) {
	  	$bod0 = $this->Diff($center);
	  } else {
	  	$bod0 = $this;
	  }
	  
      if($bod0->x==0){      // zakladni uhly po 90 stupnich
      	if($bod0->y==0)      return false;
      	elseif($bod0->y>0)    $a= pi()/2; // pokud y kladne a x nulove - uhel 90
	    else                  $a= pi()+pi()/2;
	  } elseif($bod0->x==0){
	    if($bod0->y>0)        $a= 0;
	    else                  $a= pi(); // oprava 30.5.2014 - do te doby to vsechno vracelo rovnou uhel v radianech
	  } else {
	
		  $a = atan(floatval($bod0->y)/$bod0->x); // uhel pomoci tangens 
		
		  if($bod0->y<0 && $bod0->x<0)   $a = $a+pi();
		  else if($bod0->y<0 )           $a = pi()*2+$a;
		  else if($bod0->x<0 )           $a = pi()+$a;
	  }
	  return rad2deg($a);
	}
	/**
	 * vraci uhlovy rozdil mezi dvema body vzhledem k pocatku $pocatek
	 * hodnota -180 az + 180
	 * kdyz je this bod protismeru od bod, tak je hodnota kladna 
	 * 
	 * @param CPoint $bod
	 * @param CPoint $pocatek
	 * @return deg
	 */
	public function AngleDiff($bod,$pocatek=false){
		if( !($bod instanceof CPoint))	{
			trigger_error("bod neni CPoint",E_USER_ERROR);
			return 0;
		}
		if($pocatek){
			$bod0 = $this->Diff($pocatek);
			$diff = $bod0->AngleDiff($bod->Diff($pocatek));
			return $diff;
		} else {
			$diff = $this->Angle()-$bod->Angle();
		  while($diff > 180) $diff -= 360;
		  while($diff <= -180) $diff += 360;
		  return $diff;
		}
	}
	
	/**
	 * otoci bod o uhel a vrati ho
	 * @param deg $angle
	 * @param CPoint $center
	 * @return CPoint
	 */
	public function Rotate($angle,$center=false){
		$bod2 = new CPoint();
		if($center) $this->Move($center->ReverseXY());		// posunu aby stred otaceni byl 0,0
		$angle = deg2rad($angle); // rotace + je protismeru hodin, - je po smeru hodin
	  $bod2->x=($this->x*cos($angle)-$this->y*sin($angle));
	  $bod2->y=($this->x*sin($angle)+$this->y*cos($angle));
	  $this->x = $bod2->x;
	  $this->y = $bod2->y;
	  if($center) $this->Move($center->ReverseXY()); //  musim center zas obratit zpatky
	  return $this;
	}
	/**
	 * vrati vzdalenost bodu od bodu nebo od [0,0]
	 * @param CPoint $bod
	 * @return float
	 */
	public function Distance($bod = false){
		if($bod==false) $bod = new CPoint(0,0);
		return sqrt( pow($this->x-$bod->x,2) + pow($this->y-$bod->y,2) );
	}
	/**
	 * vrati bod jako array [x,y]
	 * @return array 
	 */
	public function toArray(){
		return array($this->x,$this->y);
	}
	/**
	 * obrati souradnici y
	 * @return CPoint
	 */
	public function ReverseY(){
		$this->y = -$this->y;
		return $this;
	}
	/**
	 * obrati obe souradnice, x i y
	 * a vrati vysledny bod
	 * @return CPoint
	 */
	public function ReverseXY(){
		$this->x = -$this->x;
		$this->y = -$this->y;
    return $this;
	}
	/**
	 * vypocita vzdalenost mezi dvema bodama - static
	 * @param CPoint $bod
	 */
	static function DistanceS($bod1,$bod2){
		return $bod1->Distance($bod2);
	}
	/**
	 * vraci bod uprostred mezi body 1 a 2 (na jejich spojnici)
	 * @param CPoint $bod1
	 * @param CPoint $bod2
	 */
	static function Middle($bod1,$bod2){
		$x = abs($bod1->x-$bod2->x)/2+min(array($bod1->x,$bod2->x));
		$y = abs($bod1->y-$bod2->y)/2+min(array($bod1->y,$bod2->y));
		return clone new CPoint($x,$y);
	}
	/**
	 * vraci true, pokud je bod1 vpravo od bodu 2 (nebo nahore, pokud maji x stejne)
	 * @param CPoint $bod1
	 * @param CPoint $bod2
	 * @return boolean
	 */
	static function ToRight($bod1,$bod2){
		if($bod1->x > $bod2->x) return true;
		elseif ($bod1->x == $bod2->x && $bod1->y > $bod2->y) return true;
		else return false;
	}
	// nasleduji 4 funkce abych mohl CPoint pouzivat jako pole[x,y]
	// jsou z interface ArrayAccess 
	function offsetExists($i){
		if($i==0 || $i==1) return true; 
		else return false;
	}
	function offsetGet($i){
		if($i==0) return $this->x;
		elseif ($i==1) return $this->y;
		else return false;
	}
	function offsetSet($i,$val){
		if($i==0) $this->x = $val;
		if($i==1) $this->y = $val;
	}
	function offsetUnset($i){
		if($i==0) $this->x = 0;
		if($i==1) $this->y = 0;
	}

}
/**
 * trida na praci s uhlama v arene
 * @author kamil
 *
 */
class Angle {
	/**
	 * upravi uhel aby byl do intervalu 0-360
	 * pomoci odecitani a pricitani 360
	 * pokud $signed, upravuje do interval -180 180
	 *  
	 * @param deg $angle
	 * @param bool $signed
	 * @return deg
	 */
	static function Normalize($angle,$signed=false){
		if($signed){
			while($angle<-180) $angle+=360;
			while($angle>=180) $angle-=360;
		} else {
			while($angle<0) $angle+=360;
			while($angle>=360) $angle-=360;
		}
		return $angle;
	}
	/**
	 * rozdil mezi dvema uhly 
	 * pokud signed zalezi na poradi a1-a2, pokud signed=false nezalezi na poradi
	 * - vzdy vraci mensi uhel
	 * @param deg $angle1
	 * @param deg $angle2
	 * @param bool $signed
	 * @return deg
	 */
	static function Difference($angle1,$angle2, $signed=false){
		$diff = self::Normalize($angle1-$angle2,true);
		return $signed?$diff:abs($diff);
	}
	/**
	 * vrati true pokud se interval uhlu prekryva s druhy intervalem uhli;
	 * 1 ma byt vzdy vic vpravo nez 0 - pocita se uhel zvetsujici se od A0 do A1 
	 * @param unknown_type $angleA0
	 * @param unknown_type $angleA1
	 * @param unknown_type $angleB0
	 * @param unknown_type $angleB1
	 * @return boolean
	 */
	static function Intersection($angleA0,$angleA1,$angleB0,$angleB1){
		if($angleA1<$angleA0) $angleA1 += 360; // budu mit hodnoty 0-720 a A1>=A0 - zbavym se problemu s 360
		if($angleB1<$angleB0) $angleB1 += 360;
		if($angleA1>$angleB0 && $angleA0<$angleB0) $prekryv = true;
		elseif($angleB1>$angleA0 && $angleB0<$angleA0) $prekryv = true;
		else $prekryv = false;
		return $prekryv;
	}
	/**
	 * uhel velikosti kruhu ze vzdalenosti;
	 * jakou uhlovou velikost ma znacka z aktualni pozice? 13.11.2012
	 * @param float $distance
	 * @param float $radius
	 * @return deg
	 */
	static function ViewAngle($distance,$radius){
		return $distance==0 ? 0 : rad2deg(atan($radius/$distance)*2);
	}
}

?>