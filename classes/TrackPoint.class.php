<?php
require_once 'classes/CPoint.class.php';

/**
 * trida ktera obsahuje pozici subjektu, natoceni a stlacenou klavesu
 * @author Kamil
 *
 */
class TrackPoint {
	/**
	 * souradnice v rovince
	 * @var CPoint
	 */
	public $point;
	/**
	 * uhel pohledu
	 * @var deg
	 */
	public $viewangle;
	
	/**
	 * stlacena klavesa
	 * @var string
	 */
	public $key;
	/**
	 * ulozi hodnoty
	 * @param CPoint $point
	 * @param deg $viewangle
	 * @param string $key
	 */
	function __construct($point,$viewangle=false,$key='') {
		$this->point = clone $point;
		if($viewangle!==false){
			$this->viewangle = (double) $viewangle;
		}
		if($key!==''){
			$this->key = $key;
		}
	}
}



?>