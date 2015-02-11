<?php

class Time {
	
	/**
	 * timestamp v sekundach
	 * @var double
	 */
	private $timestamp;
	/**
	 * mozno vlozit cas zacatku v s, jinak bere aktualni cas
	 * @param double $timestamp
	 */
	function __construct($timestamp=false){
		if($timestamp) $this->timestamp = $timestamp;
		else {
			$this->timestamp = microtime(true);
		}
	}
	/**
	 * vraci rozdil proti zacatku sec, pokud string tak ve formatu m:s.ms
	 * @param double $timestamp
	 * @param bool $string
	 * @return string|number
	 */
	public function Diff($string=true,$timestamp=false){
		if(!$timestamp){
			$timestamp = microtime(true);
		}
		$diff = $timestamp-$this->timestamp;
		if($string){
			$diff = round($diff,1); // na desetiny vterin
			$ms = round(($diff-intval($diff))*10);
			$sec = intval($diff)%60;
			if($sec<10) $sec = "0".$sec;
			$min = intval($diff/60);
			return "$min:$sec.$ms";
		} else {
			return $diff; // vraci v sekundach s 
		}
	}

}

?>