 <?php

/**
 * trida pro praci s udaji o subjektech
 * zatim je hlavne pocita a pocita, kolikrat opakovali kterou fazi
 * 13.4.212
 * @author Kamil
 *
 */
class SubjectData {
	private $data = array();
	private $subjectcode = ""; // posledni vlozeny clovek
	private $phase = -1; // posledni vlozena faze 
	/**
	 * vlozim dalsiho cloveka, pokud jiz ponekolikate, zvysim pocet opakovani faze
	 * @param string $subjectcode
	 * @param int $phase
	 */
	public function Add($subjectcode,$phase){
		if(isset($this->data[$subjectcode])){
			if(isset($this->data[$subjectcode]['phases'][$phase])){
				$this->data[$subjectcode]['phases'][$phase]++;
			} else {
				$this->data[$subjectcode]['phases'][$phase] = 0;
			} 
		} else {
			$this->data[$subjectcode]=array(
				'phases'=>array($phase=>0),
				'subjectno'=>count($this->data)+1); // cislo cloveka od 1
		}
		$this->subjectcode= $subjectcode;
		$this->phase = $phase;
	}
	/**
	 * kolikrat ulozena tato faze pro tohoto cloveka
	 * pokud bez argumentu, bere pro posledni vklad 
	 * @param string $subjectcode
	 * @param int $phase
	 * @return int|bool
	 */
	public function PhaseRepeat($subjectcode=false,$phase=false){
		if($subjectcode===false) $subjectcode = $this->subjectcode;
		if($phase===false) $phase = $this->phase;
		if(isset($this->data[$subjectcode]['phases'][$phase])){
			return $this->data[$subjectcode]['phases'][$phase];
		} else {
			return false;
		}
	}
	/**
	 * cislo od 1 cloveka, pokud bez argumentu, bere posledniho vlozeneho cloveka 
	 * @param string $subjectcode
	 * @return int|bool
	 */
	public function SubjectNo($subjectcode=false){
		if($subjectcode===false) $subjectcode = $this->subjectcode;
		if(isset($this->data[$subjectcode])){
			return $this->data[$subjectcode]['subjectno'];
		} else {
			return false;
		}
	}
	
	
	
}

?>