<?php

require_once 'classes/SpaNavFilename.class.php';
define('EXCLUDEDTRIAL','exclude'); // text napsany v trial setttings, ktery znamena, ze trial je vyrazen
//if(!defined('FILELIST_DUPLICATES_OPAKOVANI')) // funguje jako definice ve filelistu
//  define('FILELIST_DUPLICATES_OPAKOVANI',0); //jestli ma byt je jedno opakovani za cloveka a fazi (pouze pro SpaNav data)


/*

version 2
gives array [groups][people]
special characters:
// - new group definition
;  - comment the line

*/
class Filelist {
  private $list;
  private $error =  false;
  private $dir;
  private $filecount;
  private $groups_removed = false;
  public static $group_not_set = "not-set";
  private $missing_files; // seznam neexistujicich souboru ve filelistu
  private $filesetting = array(); // pole vynechanych trialu u jednotlivych souboru
  private $filesall = array(); // na kontrolu duplicity souboru - seznam vsechn souboru s cestami
  private $duplicates = array();
  /**
   * 
   * @param string $filelistname
   * @param bool $empty_lines jestli brat prazdne radky jako soubory
   * @return boolean
   */
  function __construct($filelistname="", $empty_lines=false){
  	    $list = array();
  		if(!empty($filelistname)){ // 25.2.2010 - je mozne neudat zadny filelist a jmena souboru pak pridat individualne
	  	    if(!file_exists($filelistname) || !($fc = file($filelistname))){
	  			$this->error = true;
	  			return false;
	  		}
	  		$ext = "";
	  		$group = self::$group_not_set;
	  		$dir="";
	  		$in_comment = false; // jestli jsem v prubehu komentu /* */
	  		$this->filecount = 0;
	  		$this->missing_files = array(); 
	  		echo "filelist $filelistname:\n";
	  		foreach($fc as $lineno=>$line){
	  		  	if(strpos($line, ";")!==false){ // komentar na konci radky
						$line = substr($line,0,strpos($line, ";"));
			  	}
	  			$line = trim($line);
			  	if($in_comment){                                      // BLOCK COMMENT
		            if(substr(trim($line),0,2)=="*/") $in_comment = false;
		            continue;  			
			  	}	elseif(substr(trim($line),0,2)=="/*"){             // BLOCK COMMENT START
		            $in_comment = true;
		            continue;
		        } elseif(substr($line,0,2)=="**"){                    // EXTENSION of filenames
	  				$ext=substr($line,2);
	  				if(strlen($ext)>0)
	  					$ext = ".".$ext;
	  			} elseif(substr($line,0,2)=="//"){                   // GROUP
	  				$group = substr($line,2);
	  			} elseif(substr($line,0,1)==";" || (strlen($line)==0 && $empty_lines==false) || substr($line,0,1)=="%"){
	  				continue;
	  			} elseif(substr($line,0,4)=="DIR="){                // ADRESAR
	  				$dir=substr($line,4);
	  				if(!in_array(substr($dir,strlen($dir)-1,1),array("/","\\")))
	  				  $dir .= "/";
	  				$this->dir = $dir;
	  			} elseif(substr($line,0,4)=="VER=") {
	  				$version = substr($line,4);
	  			} elseif(substr($line,0,3)=='END' && strlen($line)==3){                 // KONEC VYHODNOCENI
	  				break;
	  			} elseif(substr($line,0,6)=='CONST='){ // novinka 4.1.2010 // NASTAVENI
	  			    $const = explode("|",substr($line,6));
	  			    $const_value = trim($const[1]);
	  			    if(substr($const_value,strlen($const_value)-1,1)=="%"){
	  			        $const_value = $this->AppendLines($const_value,$fc,$lineno);
	  			    }
	  			    if(!defined($const[0])){
	  			    	if($const[0]=='LOGFILE') $const[0] = FILELISTDIR.$const[0];
	  			        define($const[0],$const_value);
	  			        echo "define($const[0], $const_value)\n";
	  			    } else {
	  			        echo "$const[0] already defined\n";
	  			    }
	  			} else {                                            // SOUBOR K VYHODNOCENI
	  				if( ($pos=strpos($line,"|"))!==false){            // nastaveni k jednomu souboru
	  					$filename = trim(substr($line,0,$pos));
	  					$this->FileSettings($group,$dir.$filename.$ext,substr($line,$pos+1));
	  				} else {
	  					$filename = $line;
	  				}
	  				$list[$group][]=$dir.$filename.$ext;
            		if(strlen($filename)>0) $this->DuplicateIs($dir.$filename.$ext);	//14.3.2014 - testovat duplikaty jen u neprazdnych jmen souboru			
	  				if(!file_exists($dir.$filename.$ext)){
	  					$this->missing_files[]=$dir.$filename.$ext;
	  				}
	  				$this->filecount++;
	  			}
	  		}
  		}
  		$this->list = $list;
  }
  public function RemoveGroups(){
    $list = array();
    foreach($this->list as $group=>$files){
      $list = array_merge($list,$files);
    }
    $this->list = $list;
    $this->groups_removed = true;
  }
  public function GetList(){
    return $this->list;
  }
  public function OK(){
    return !$this->error;
  }
  static function Extension($filename){
    return strtolower(substr($filename,strrpos($filename,'.')+1));
  }
  /**
   * prida soubor vcetne cesty do filelistu
   * mozne definovat skupinu
   * 
   * @param string $filename
   * @param string $group default "not-set"
   */
  public function AddFile($filename,$group=false){
  	if(!$group) $group = self::$group_not_set;
  	$this->list[$group][]=$filename;
  }
  public function Dir(){
  	return $this->dir;
  }
  public function Count($group=false){
  	if(!$group || $this->groups_removed){
  		return $this->filecount;
  	} else {
  		if(isset($this->list[$group])){
  			return false;
  		} else {
  			return count($this->list[$group]);
  		}
  	}
  }
  /**
   * vraci seznam souboru ve filelistu, ktere ale neexistuji
   * nebo false, pokud vsechny existuji
   * @return array/false
   */
  public function MissingFiles(){
  	if(is_array($this->missing_files) && count($this->missing_files)>0){
  		return $this->missing_files;
  	} else {
  		return false; 
  	}
  }
  /**
   * prida dalsi radky k retezci pokud zacinaji % ( a pripadne konci %)
   * @param string $const_value
   * @param array $fc
   * @param int $lineno
   * @return string
   */
  private function AppendLines($const_value,$fc,$lineno){
		// radky definice konstanty se mohou spojovat pomoci % na konci jednoho 
		// a zacatku dalsiho radku - 27.7.2010 
		$const_value = str_replace ( "%", "", $const_value ); // smazu posledni znak
		$j = 1;
		$nextline = trim ( $fc [$lineno + $j] );
		while ( substr ( $nextline, 0, 1 ) == "%" ) {
			$const_value .= str_replace ( "%", "", $nextline );
			$j ++;
			$nextline = trim ( $fc [$lineno + $j] );
		}
		return $const_value;
  }
  /**
   * ulozi nastaveni pro jeden soubor
   * @param string $group
   * @param string $filename
   * @param string $settings
   */
  private function FileSettings($group,$filename,$settings){
  	if(strpos($settings,"|")!==false){ // muze byt vic nastaveni na jeden soubor
  		$sett_arr = explode("|",$settings);
  		foreach($sett_arr as $sett){
  			$this->FileSettings($group,$filename,$sett);
  		}
  	}
  	if($settings{0}=="!"){ // vyrazeni trialu - nic jineho zatim neznam
  		$parts = explode(",",substr($settings,1));
  		foreach($parts as $part){
  			list($phase,$trial)=explode("-",trim($part));
  			if(strpos($trial, ":")!==false){ // 18.10.2012 moznost vynechani serie trialu
  				list($trial0,$trial1)= explode(":",$trial);
  				for($j=$trial0;$j<=$trial1;$j++){
  					$this->filesetting[$group][$filename][$phase][$j] = EXCLUDEDTRIAL;
  				}
  			} else {
  		    	$this->filesetting[$group][$filename][$phase][$trial] = EXCLUDEDTRIAL;
  			}
  		}
  	} elseif(substr(strtolower($settings),0,6)=="track:"){
  		$track = (int) substr($settings,6);
  		$this->filesetting[$group][$filename]['track']=$track;
  	}
  }
  /**
   * vrati nastaveni pro jeden soubor
   *
   * @param string $group
   * @param string $filename
   * @return array|false
   */
  public function GetFileSettings($group,$filename){
  	if(isset($this->filesetting[$group][$filename])){
  		return $this->filesetting[$group][$filename];
  	} else {
  		return false;
  	}
  }
  /**
   * vraci true, jestli tento soubor jiz byl drive ve filelistu vlozen; take ho prida do this->duplicates
   * @param string $filename
   * @return bool
   */
  private function DuplicateIs($filename){
  	if(defined('FILELIST_DUPLICATES_OPAKOVANI')){
      if(FILELIST_DUPLICATES_OPAKOVANI>0){ // pridal jsem 9.9.2013 kvuli kontrole nadbytecnich souboru v experimentu aappShort
    		$CS = new SpaNavFilename($filename);
    		if(isset($this->filesall[$CS->Person()][$CS->Faze()])){
    			$this->duplicates[]="$filename X ".$this->filesall[$CS->Person()][$CS->Faze()];
    		} else { 
      			$this->filesall[$CS->Person()][$CS->Faze()] = $filename;	
    		}
    	}
  	} else {
	  	if(in_array($filename,$this->filesall)){
	  		$this->duplicates[]=$filename;
	  		return true;
	  	} else {
	  		$this->filesall[]=$filename;
	  		return false;
	  	}
  	}
  	
  }
  /**
   * vrati seznam duplikatu nebo false
   * @return array|false
   */
  public function Duplicates(){
  	if(count($this->duplicates)>0){
  		 return $this->duplicates;
  	} else {
  		return false;
  	}
  }
  
}



?>
