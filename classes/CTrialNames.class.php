<?php



/**
 * trida na zpracovani seznamu jmen trialu
 * 27.7.2010 - kvuli pojmenovani trialu ve vestib testu
 * @author kamil
 *
 */
class CTrialNames  {
   private $names = array();
   private $namegroups = array();

   function __construct() {
     if(defined('TRIALNAMES')){
     	  $names = explode("#",TRIALNAMES);
     	  // napriklad R1[0;0 2 4 6]#R2[1;0 2 4 6]#RS[2;0 2 4 6]#L1[0;1 3 5 7]#L2[1;1 3 5 7]#LS[2;1 3 5 7] 
     	  // napriklad M1[0 1;0 2 4 6]#S2[0 1;0 2 4 6]
     	  foreach ($names as $name){
     	  	list($trialname,$loc) = explode ("[",trim($name));
     	  	list($loc,$namegroup) = explode("]",trim($loc));
     	  	list($phase_str,$trials_string) = explode(",",trim($loc));
     	  	$phases = explode(" ",trim($phase_str));
     	  	$trials = explode(" ",trim($trials_string));
     	  	
     	  	foreach($phases as $phase){
     	  		foreach($trials as $trial){
     	  			$this->names[$phase][$trial] = $trialname;
     	  			$this->namegroups[$phase][$trial]=$namegroup;
     	  		}
     	  	}
     	  }
     }
	 }
	 /**
	  * vrati jmeno podle faze a trialu
	  * pokud group = true - vrati pripojenou skupinu pomoci | - name|group
	  * @param int $phase
	  * @param int $trial
	  * @param bool $group
	  * @return string
	  */
	 public function Name($phase,$trial,$group = false){
	 	 if(isset($this->names[$phase][$trial])){
	 	 	  return $this->names[$phase][$trial].
		 	 	   ($group && !empty($this->namegroups[$phase][$trial])
		 	 	   ?"|".$this->namegroups[$phase][$trial]
		 	 	   :"");
	 	 } else {
	 	 	  return false;
	 	 }
	 }
}


?>