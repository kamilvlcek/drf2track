<?php



class CKeySequence  {
  /**
   * sekvence klaves, ktera se ma opakovat
   * @var array
   */
  private $seq;
  /**
   * sekvence klaves jak byly - delka shodna se $seq
   * @var array
   */
  private $keys;


	function __construct($seq=false) {
    if($seq){
    	$this->seq = $seq;
    } elseif(!$seq && defined('KEYSEQUENCE')){
    	$this->seq = explode(" ",KEYSEQUENCE);
    } else {
    	$this->seq = array();
    }
    $this->keys = array();
	}
	/**
	 * ulozi dalsi klavesu a pokud je v sekvenci, porovna ulozene klavesy se sekvenci
	 * vraci false pokud chyba v sekvenci klaves, jinak true
	 * @param string $key
	 * @return bool
	 */
	public function AddKey($key){
		if(in_array($key,$this->seq)){
			array_push($this->keys,$key); // vlozi klavesu na konec
			while(count($this->keys)>count($this->seq)){
				array_shift($this->keys); // smaze klavesu ze zacatku
			}
			return $this->CompareSeq();
		} else {
			return true;
		}
	}
	private function CompareSeq(){
		if(count($this->keys)<count($this->seq)){
			// pocet zatim stlacenych klaves je mensi nez seq - musi sedet od zacatku
			$correct = true;
			for($j=0;$j<count($this->keys);$j++){
			   if($this->keys[$j]!=$this->seq[$j]) {
            $correct = false;
            break;
          }
			}
			if($correct) return true;
		} else {
			// pocet klaves uz je stejny nebo vetsi nez seq - seq se opakuje dokola
			$seq_q_arr = array_keys($this->seq,end($this->keys)); 
	    // vrati klice pole, kde je ulozene posledni klavesa v sekvenci
	    // protoze ta sama klavesa muze byt ulozena v sekvecni vicekrat napr cggg
	    // musim otestovat jestli je kterakoliv z nich spravne
			foreach($seq_q_arr as $seq_q) {
				$correct = true;
				// porovnavam od konce stlacenych klaves dopredu
				for($j=1;$j<count($this->keys);$j++){ // this->keys muze byt na zacatku kratsi nez this->seq
					$jk = count($this->keys)-1 - $j; // index na stlacene klavesy
					$jq = $seq_q - $j; // index na pozici v sekvenci
					if($jq < 0)  $jq += count($this->seq); 
					if($this->keys[$jk]!=$this->seq[$jq]) {
						$correct = false;
						break;
					}
				}
				if($correct) return true;
			}
		}
		return false; // zadna z pozic v sekvenci nesedela
	}
	
	
}


?>