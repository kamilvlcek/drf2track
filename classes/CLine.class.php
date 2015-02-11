<?php
require_once 'classes/CPoint.class.php';
/**
 * primka ax + by + c = 0
 * svisla primka b = 0
 * vodorovna primka a = 0
 * [1,-1,0] osa 1. a 3. kvadrantu
 * [1,1,0] osa 2. a 4. kvadrantu
 * 
 * @author Kamil
 *
 */
class CLine {
	public $a, $b,$c; // koeficienty primy ax + by + c = 0;
	
	/**
	 * definuje primku tremi koeficienty ax + by + c = 0;
	 * @param float $a
	 * @param float $b
	 * @param float $c
	 */
	public function DefineByCoef($a,$b,$c) {
	   $this->a = $a;
	   $this->b = $b;
	   $this->c = $c;
	}
	/**
	 * definuje primku dvemi body
	 * @param CPoint $bod1
	 * @param CPoint $bod2
	 */
	public function DefineByPoints($bod1,$bod2){
	  if($bod1->x==$bod2->x){ // primka svisla
	    $this->DefineByCoef(1,0,-$bod1->x);
	  } elseif($bod1->y==$bod2->y){ // primka vodorovna
	  	$this->DefineByCoef(0, 1, -$bod1->y);
	  } else { // svisla primka
	    $p1 = ($bod1->y-$bod2->y)/($bod1->x-$bod2->x); // smernice
	    $p0 = $bod1->y-$p1*$bod1->x; // absolutni cast
	    $this->DefineByCoef($p1, -1, $p0); // potrebuju obecnou rovnici primky, kvuli primkam ktere jsou rovnobezne s y
	  }
	 
	}
	/**
	 * @param CPoint $bod
	 * @param deg $angle
	 */
	public function DefineByPointAngle($bod,$angle){
		if($angle==90 || $angle==270){
			$this->DefineByCoef(1, 0, -$bod->x);
		} elseif($angle==0 || $angle==180) {
			$this->DefineByCoef(0, 1, -$bod->y);
		} else {
			$bod2 = clone $bod;
			$bod2->MoveAngleDistance($angle,100);
			$this->DefineByPoints($bod,$bod2);
		}
	}
	/**
	 * vrati vzdalenost bodu od primky
	 * @param CPoint $bod
	 * @return float
	 */
	public function DistancePoint($bod){
		//http://www.intmath.com/Plane-analytic-geometry/Perpendicular-distance-point-line.php
		//http://www.solitaryroad.com/c426.html
		return abs($this->a*$bod->x+$this->b*$bod->y+$this->c)/sqrt( pow($this->a,2)+pow($this->b,2));
	}
	/**
	 * vrati uhel od bodu k primce nejmensi vzdalenosti =kolmo
	 * v rozsahu 0-360, 90 je nahoru
	 * @param CPoint $bod
	 * @return float
	 */
	public function AngleFromPoint($bod){
		if($this->a!=0){
			if($this->X($bod->y)>$bod->x) {
			  $angle =  $this->Angle()-90; // bod vlevo od primky, k primce doprava
			  if($angle<0) $angle += 360;
			  return $angle;
			} elseif($this->X($bod->y)==$bod->x) {
		    return false; // bod je naprimce, uhel se neda urcit
			} else {
			  $angle = $this->Angle()+90; // bod vlevo od primky, k primce doleva
			  if($angle >360) $angle -=360; // chci mit v rozsahu 0 - 360
			  return $angle;
			}
		} else { // vodorovna primka
		  if($this->Y($bod->x)>$bod->y) {
		    return 90; // ke primce nahoru
		  } elseif($this->Y($bod->x)==$bod->y) {
		    return false;// bod je naprimce, uhel se neda urcit
		  } else { 
		    return 270; // ke primce doleva
		  }
			
		}
	}
	/**
	 * vrati X souradnici primky podle y
	 * @param float $y
	 * @return float
	 */
	public function X($y){
		// ax + by + c = 0; x = (by+c)/a
		if($this->a == 0) 
		  return false;
		else 
		  return -($this->b*$y)/$this->a;
	}
	/**
	 * vrati Y souradnici primky podle x
	 * @param float $x
	 * @return float
	 */
	public function Y($x){
		// y = (ax + c)/b
		if($this->b == 0)
		  return false;
		else
		  return -($this->a*$x)/$this->b;
	}
	/**
	 * smernice primky
	 * @return float
	 */
	public function Slope(){
		//http://en.wikipedia.org/wiki/Slope
		if($this->b == 0) 
		  return false; // svisla primka
		else
		  return -$this->a/$this->b;
	}
	/**
	 * uhel primky ve stupnich 
	 * v rozsahu 0-179.9999
	 * 90 je nahoru
	 * 
	 * @return float
	 */
	public function Angle(){
		if($this->b==0) // svisla primka
		  return 90;
		else {
		  $uhel =rad2deg(atan($this->Slope()));
		  if($uhel<0) $uhel += 180;
		  return $uhel;
		}
	}
	/**
	 * vrati bod prevraceny symetricky podle teto primky
	 * @param CPoint $bod
	 * @return CPoint
	 */
	public function Symmetry($bod){
		if( ($distance=$this->DistancePoint($bod))>0){
			$angle = $this->AngleFromPoint($bod);
			$bod = clone $bod;// nechci menit puvodni bod mimo tuto funkci
			$bod->MoveAngleDistance($angle,$distance * 2);
		}
		return $bod;
	}
	function __toString(){
		return "$this->a*x+$this->b*y+$this->c=0";
	}
	/**
	 * pocita souradnice pruseciku primky s kruznici udanou jejim stredem a polomerem.
	 * odvozoval jsem 7.6.2010 sam z rovnice kruznice, primky a kvadraticke rovnice
	 * @param CPoint $bod
	 * @param int $r
	 * @return array[CPoint,CPoint]
	 */
	function CircleIntersection($bod,$r){
		/*
		 * ax + by + c = 0
		 * x = -c/a -by/a
		 * (x-k)^2 + (y-l)^2 = r^2
		 * x^2 -2kx + k^2 + y^2 - 2yl + l^2 = r^2
		 * (-c/a - by/a)^2 - 2k(-c/a -by/a) + k^2 + y^2 - 2yl + l^2 = r^2
		 * c^2/a^2 + 2c/a*by/a + (by/a)^2  - 2k(-c/a -by/a) + k^2 + y^2 - 2yl + l^2 = r^2
		 * c^2/a^2 + 2cby/a^2 + b^2 y^2 /a^2 - 2k(-c/a -by/a) + k^2 + y^2 - 2yl + l^2 = r^2
		 * c^2/a^2 + 2cby/a^2 + b^2 y^2 /a^2 + 2kc/a +2kby/a + k^2 + y^2 - 2yl + l^2 = r^2
		 * b^2 y^2 /a^2 + y^2  + 2cby/a^2  +2kby/a - 2yl + k^2   + l^2 + c^2/a^2 +  2kc/a= r^2  
		 * y^2(b^2/a^2 + 1) + y(2cb/a^2 + 2kb/a - 2l) + k^2 + l^2  + c^2/a^2 +  2kc/a
		 * 
		 * A = (b2/a2 + 1)
		 * B = (2cb/a2 + 2kb/a - 2l)
		 * C = k2 + l2+ c2/a2 +  2kc/a - r2 
		 */
		// koeficienty kvadraticke rovnice
		$a = pow($this->b/$this->a,2) + 1;
		$b = 2*$this->c*$this->b/pow($this->a,2) 
		     + 2*$bod->x*$this->b/$this->a 
		     - 2*$bod->y;
		$c = pow($bod->x,2) + pow($bod->y,2) - pow($r,2)
		    + 2*$bod->x*$this->c/$this->a 
		    + pow($this->c/$this->a,2);
		// cast pod odmocninou reseni kvadraticke rovnice
		// discriminant
	  $D = pow($b,2)-4*$a*$c;
	  
		 if($D <0) {  
		 	  return array(); // zadny prusecik primky a kruznice 
	   } elseif($D==0) {
	   	  $y = -$b/(2*$a);
	   	  $x = (-$y*$this->b - $this->c)/$this->a;
	   	  return array( new CPoint($x,$y)); // jediny pruseci - primka je tecnou kruznice
	   } else {
		 	 $y = array((-$b + sqrt($D))/(2*$a),(-$b - sqrt($D))/(2*$a));
		 	 $x = array( (-$y[0]*$this->b - $this->c)/$this->a ,
		 	             (-$y[1]*$this->b - $this->c)/$this->a);
		 	 // primka protina kruznici - dva pruseciky
		 	 return array(new CPoint($x[0],$y[0]), new CPoint($x[1],$y[1]));
		 }
		 
	}
	function Perpendicular($bod){
		$angle = $this->Angle()+90;
		while($angle>=180) $angle-=180;
        $line2 = new CLine();
        $line2->DefineByPointAngle($bod, $angle);
        return clone $line2;		 
	}
	 	    
		     
}
	


?>