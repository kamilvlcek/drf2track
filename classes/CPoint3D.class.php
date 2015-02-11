<?php

require_once ('classes/CPoint.class.php');

/**
 * trida na praci s 3D bodem; vytvorena kvuli pozici znacek a eyetrackingu
 * @author Kamil
 * @since 1.10.2012
 *
 */
class CPoint3D extends CPoint {
	public $z=0;
	public function __construct($x,$y=false,$z=false) {
	  if(is_array($x) && isset($x[0]) && isset($x[1]) && isset($x[2])){
	  	$this->x = $x[0];
	  	$this->y = $x[1];
	  	$this->z = $x[2];
	  } elseif( $x instanceof CPoint3D){
	  	$this->x = $x->x;
	  	$this->y = $x->y;
	  	$this->z = $x->z;
	  } elseif( $x instanceof CPoint){
	  	$this->x = $x->x;
	  	$this->y = $x->y;
	  	$this->z = 0;
	  } elseif(is_numeric($x) && is_numeric($y) && is_numeric($z)) {
		  $this->x = $x;
	      $this->y = $y;
	      $this->z = $z;
	  }
	
	}
	function __toString(){
		return "[".round($this->x,4).",".round($this->y,4).",".round($this->z,4)."]";
	}
	
	// nasleduji 4 funkce abych mohl CPoint pouzivat jako pole[x,y]
	// jsou z interface ArrayAccess 
	function offsetExists($i){
		if($i==0 || $i==1 || $i==2) return true; 
		else return false;
	}
	function offsetGet($i){
		if($i==0) return $this->x;
		elseif ($i==1) return $this->y;
		elseif ($i==2) return $this->z;
		else return false;
	}
	function offsetSet($i,$val){
		if($i==0) $this->x = $val;
		if($i==1) $this->y = $val;
		if($i==2) $this->z = $val;
	}
	function offsetUnset($i){
		if($i==0) $this->x = 0;
		if($i==1) $this->y = 0;
		if($i==2) $this->z = 0;
	}
}

?>