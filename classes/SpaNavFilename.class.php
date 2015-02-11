<?php

/**
 * trida zachazejici s nazvem spanav tracku
 * @author Kamil
 *
 */
class SpaNavFilename {
	private $filename;
	private $person;
	private $faze;
	private $opakovani;
	function __construct($filename) { //FD070111_5_0.tr
	   $this->filename = $filename;
	   list($basename,$ext) = explode(".",$filename);
	   list($this->person,$this->faze,$this->opakovani) = explode("_",$basename);
	   
	}
	
	function Person(){
		return $this->person;
	}
	/**
	 *vraci cislo faze podle souboru
	 * @return double
	 */
	function Faze(){
		return (double) $this->faze;
	}
	function Opakovani(){
		return $this->opakovani;
	}
}

?>