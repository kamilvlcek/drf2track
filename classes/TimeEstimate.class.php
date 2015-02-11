<?php

/**
 * trida na odhad celkoveho casu zpracovani
 * @author Kamil
 * @since 2.7.2012
 */
class TimeEstimate {
	private $time;
	/**
	 * nastartuje casomiru
	 */
	function __construct(){
		$this->time = microtime(true);
	}
	/**
	 * vrati aktualni cas jako retezec
	 * @return string
	 */
	public function time(){
		$time_current = microtime(true)-$this->time;
		return $this->cas_format($time_current);
	}
	/**
	 * vraci odhad celkoveho casu na zaklade podilu prace
	 * @param float $podil
	 * @return string
	 */
	public function estimate($podil){
		$time_current = microtime(true)-$this->time;  
		$time_total_estimate = $time_current/$podil;
	    return $this->cas_format($time_total_estimate);
	}
	/**
	 * vraci kolik casu zbyvat
	 * @param float $podil
	 * @return string
	 */
	public function remains($podil){
		$time_current = microtime(true)-$this->time;  
		$time_total_estimate = $podil==0?0:$time_current/$podil;
		return $this->cas_format($time_total_estimate-$time_current);
	}
	/**
	 * vrati zformatovavy cas min:sec
	 * @param float $time_float sec
	 * @return string
	 */
	private function cas_format($time_float) {
		$sec = $time_float%60; //sekundy
		if($sec<10) $sec = "0".$sec;
	    return intval($time_float/60).":$sec";
	}
}

?>