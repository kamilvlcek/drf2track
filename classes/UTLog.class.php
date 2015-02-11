<?php

/**
 * zpracovava udaje z logu od Unrealu, viz verze SpaNav z 26.3.2013
 * @author Kamil
 *
 */
class UT2004Log {
	private $trials = array();
	/**
	 * precte soubor a vytahne klavesy a casy
	 * @param string $filename cesta a nazev souboru
	 * @param array $texts seznam moznosti zobrazeneho textu
	 * @param char $keytosearch klavesa ktera se mela zmackout
	 */
	function __construct($filename,$texts,$keytosearch){
		if(file_exists($filename)){
			$fc = file ($filename);
			$status = 0; // ceka se na zobrazeni napisu
			$trial = 0;
			$timezacatek = false; // cas prvni radky logu
			foreach($fc as $lineno => $line){
				@list($time,$msg,$id) = explode(";",$line);
				if(!$timezacatek && !empty($msg)) $timezacatek = $this->militime($time);
				$msg = trim($msg);
				if(/*$status==0 &&*/ substr($msg,0,21) == "Screen Text  Modified"){
					// 3.7.2013 - zjistil jsem, ze kdyz clovek ukaze a pak ma jit do cile, tak se znovu objevi napis Screen Text  Modified; Id:B. Ale pak neni klavesa S
					// takze ho musim hledat vzdy, i kdyz predtim neni S
					$txt = substr($id,4,1); // docasne jmeno
					if(in_array($txt,$texts)){
						$status = 1;
						$texttime = $this->militime($time);
						$texttext = $txt; // jmeno mista potvrzene 
					}
				} elseif($status==1 && substr($msg,0,12)=="Key Pressed:"){
					$key = substr($msg,13);
					if($key==$keytosearch){
						$status = 0;
						$timekey = $this->militime($time);
						$this->trials[$trial]=array("text"=>$texttext,"time"=>round($timekey-$texttime,3),"timestamp"=>$time,
							"timeText"=>round($texttime-$timezacatek,3),"timeKey"=>round($timekey-$timezacatek,3));
						$trial++;
					}
					
				}
			}
		} 
	}
	/**
	 * vrati cas zmackuti klavesy v danem trialu
	 * @param int $trial
	 * @return float
	 */
	public function Time($trial){
		if(isset($this->trials[$trial]['time'])){
			return $this->trials[$trial]['time'];
		} else {
			return 0;
		}
	}
	/**
	 * vrati zobrazeny text v danem trialu
	 * @param int $trial
	 * @return string
	 */
	public function Text($trial){
		if(isset($this->trials[$trial]['text'])){
			return $this->trials[$trial]['text'];
		} else {
			return "";
		}
	}
	/**
	 * vrati casovou znacku kdy byla zmacknuta klaves S (=konec trialu)
	 * napr 11:49:16.056
	 * @param int $trial
	 * @return string
	 */
	public function Timestamp($trial){
		if(isset($this->trials[$trial]['timestamp'])){
			return $this->trials[$trial]['timestamp'];
		} else {
			return "";
		}
	}
	/**
	 * vraci cas zobrazeni textu, relativne k zacatku logu
	 * @param int $trial
	 * @return double
	 */
	public function TimeText($trial){
		if(isset($this->trials[$trial]['timeText'])){
			return $this->trials[$trial]['timeText'];
		} else {
			return 0;
		}
	}
	/**
	 * vrati cas stlaceni klavesy, vzhledem k zacatku logu
	 * @param int $trial
	 * @return double
	 */
	public function TimeKey($trial){
		if(isset($this->trials[$trial]['timeKey'])){
			return $this->trials[$trial]['timeKey'];
		} else {
			return 0;
		}
	}
	
	private function militime($timestring){
		list($time,$ms)=explode(".",$timestring);
		$militime = strtotime($time)+$ms/1000;
		return $militime;
	}
	
	
}

?>