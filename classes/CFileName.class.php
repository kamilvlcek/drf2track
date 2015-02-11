<?php

if(!defined('STRING_DELIM')) define('STRING_DELIM',"."); // oddelovac desetinna tecka

/**
 * trida zpracovavajici jmena souboru a adresaru
 * @author kamil
 *
 */
class CFileName  {
  
	/**
	 * vraci jmeno souboru vcerne cesty, ve kterem je k ceste pridane $path pred jmeno souboru
   * pokud adresar neexistuje, vytvori ho
   * 
	 * @param string $filename
	 * @param string $path
	 * @return string
	 */
	static function ChangePath($filename,$path){
    if(!is_dir(dirname($filename)."/$path")) mkdir(dirname($filename)."/".$path);
    return dirname($filename)."/$path/".basename($filename);
  }
  
  /**
   * zmeni priponu souboru a vrati vysledek
   * necha $leave pripon (pokus ma nazev v sobe vic tecek)
   * 
   * @param string $filename
   * @param string $extension
   * @param int $leave
   * @return string
   */
  static function ChangeExtension($filename,$extension,$leave=0){
  	$dot = strpos($filename,".",0);
  	while($leave>0){
  		$dot = strpos($filename,".",$dot+1);
  		$leave--;
  	}
  	$filename2 = substr($filename,0,$dot+1).$extension;
  	return $filename2;
  }
  /**
   * vrati jmeno souboru bez cesty a bez pripony
   * @param string $fullfilename
   * @return string
   */
  static function Filename($fullfilename){
  	$ff = basename($fullfilename);
  	if( ($tecka = strpos($ff, "."))!==false){
  		return substr($ff,0,$tecka);
  	} else {
  		return $ff;
  	} 
  }
}

/**
 * trida shrnujici ruzne retezcove funkce
 * @author kamil
 *
 */
class String {
	/**
	 * vrati int schovany v retezci
	 * hleda prvni pozici od zacatku, kde je int > 0
	 *  
	 * @param string $string
	 * @return int
	 */
	static function intval($string){
		$val = 0;
		for($i=0;$i<strlen($string);$i++){
			if( ($val = intval(substr($string,$i)))>0){
				return $val;
			}
		}
		return $val;
	}
	/**
   * v retezci nahradi desetinou tecku hodnotou DELIM nebo $delim pokud je zadana
   * @param string $str
   * @param string $delim
   * @return string
  */
  static function setdelim($str,$delim=false){
  	if(!$delim) $delim = STRING_DELIM;
    return preg_replace("/([0-9])\.([0-9])/","$1".$delim."$2",$str);
  }
}


?>