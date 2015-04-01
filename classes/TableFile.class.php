<?php
require_once 'classes/logout.class.php'; //  drf2track musi byt classes/
if(!defined('TABLEFILE_COLUMNDELIM')) define('TABLEFILE_COLUMNDELIM',"\t"); // oddelovat sloupcu tabulky
if(!defined('TABLEFILE_DELIM')) define('TABLEFILE_DELIM',","); // oddelovac desetinna CARKA
if(!defined('TABLEFILE_MATLAB')) define('TABLEFILE_MATLAB',0);
define("TABLEFILE_QUOATIONMARKS",0); // jestli maji byt uvozovky kolem retezcu
define('TABLEFILE_MATLAB_NAN',1); // jestli se maji do matlabu retezce psat jako NaN
define('TABLEFILE_MATLAB_COLUMNAMES',1); // jestli se maji do matlabu psat nazvy sloupcu

class TableFile  {
	protected $filename;
	private $fh;
	private $error=false; // chyba v otevrenem souboru
	private $fileopened;
	private $filehandle_isset = false;
	private $precision = 2; // desetinna mista vystupu
	private $precision_columns = array();
	// ulozena data
	public $columnames = array();
	public $data = array(); 
	public $title = "";
	public $datarow = array();
	static $fh_array = array(); // staticka metoda, urcujici v kolika tridach je pouzite ten konkretni filehandle
	protected $floatdelim;
	protected $columndelim;
	private $emptycell = "";
	private $matlab = false;
	private $columns; // pocet sloupcu - pocita se pouze pri ukladani tabulky
	
  /**
   * tabulka k zapisu do souboru
   * @param string $filename
   * @param FH $fh
   */
  function __construct($filename=false,$fh=FALSE){
  	if(!$fh && $filename){
  		$this->fileopen($filename);
  	} else {
      $this->filehandleset($fh);
  	}
  	$this->filename = $filename;
  	$this->columndelim = TABLEFILE_COLUMNDELIM;
  	$this->floatdelim = TABLEFILE_DELIM;
  }
  function __destruct(){
  	$this->fileclose();
  }
  /**
   * nastavi oddelovace sloupcu a desetinnych mist
   * @param string $columndelim
   * @param string $floatdelim
   */
  public function setDelims($columndelim,$floatdelim){
  	$this->columndelim = $columndelim;
  	$this->floatdelim = $floatdelim;
  }
  /**
   * nastavi co se ma davat misto prazdnych bunek
   * @param string $empty
   */
  public function setEmptyCell($empty){
  	$this->emptycell = $empty;
  }
  
  /**
   * nastavi typ tabulek pro matlab
   * @param bool $matlab
   */
  public function setMatlab($matlab=true){
  	if($matlab){ // matlab
  		$this->setDelims("\t", ".");
  		$this->setEmptyCell("NaN");
  		$this->matlab = true;
  		$this->SetPrecision(false);
  	} else { // excel
  		$this->setDelims("\t", ",");
  		$this->setEmptyCell("");
  		$this->matlab = false;
  		$this->SetPrecision(2);
  	}
  }
  protected function fileopen($filename){
      if(!is_dir(dirname($filename))) {
      	if(mkdir(dirname($filename))==false){
      		echo "\n!!adresar nelze vytvorit: ".dirname($filename)."\n";
      		$this->error = true;
      		return;
      	}
      }
      if(!($this->fh = @fopen($filename,"wt"))){
        $this->error = true;
        echo "\n!!soubor se nepodarilo otevrit pro zapis: \n".$filename."\n";
      } else {
        $this->error = false;
        $this->fileopened = true;
        $this->filehandle_isset = true;
        $this->filename = $filename;
        if(empty(self::$fh_array[(int)$this->fh])){
        	 self::$fh_array[(int)$this->fh]=1;
        } else {
        	 self::$fh_array[(int)$this->fh]++;
        }
      }
  }
  public function isError(){
  	return $this->error;
  }
  protected function fileclose(){
    if($this->fileopened && isset(self::$fh_array[(int)$this->fh]) && self::$fh_array[(int)$this->fh]==1){
      unset(self::$fh_array[(int)$this->fh]);
      if(!fclose($this->fh)){
      	echo "soubor ".basename($this->filename)." nejde zavrit\n";
        //Tisk::backtrace();
      } else {
      	//echo "soubor ".basename($this->filename)." uzavren\n"; - normalni situace
      }
    } elseif(isset(self::$fh_array[(int)$this->fh])) {
    	self::$fh_array[(int)$this->fh]--; // odecte se pocet instaci tridy, ktere pouzivaji tento handle
    	echo "soubor ".basename($this->filename)." pouziva jina instance\n";
    } else {
    	//echo "soubor $this->filename nebyl otevren\n"; // - normalni situace
    }
  }
  protected function filehandleset($fh){
  	  $this->fh = $fh;
      $this->error = false;
      $this->fileopened = false; // jestli ho mam na konci zavrit
      $this->filehandle_isset = true; // jestli je do ceho psat
  }
  /**
   * prida dalsi jmena sloupcu - zvetsi celkovy pocet sloupcu v tabulce
   * @param array $cols
   */
  public function AddColumns($cols){
  	$this->columnames = array_merge($this->columnames,$cols);
  }
  /**
   * prida sloupec do tabulky i s datama a jmenem
   * neprida nic do radek, ktere jsou kratsi nez seznam jmen sloupcu
   * pouziva klice z pole $vals
   * 
   * @param array $vals
   * @param string $name
   */
  public function AddColumnData($vals,$name){
  	//$this->columnames[]=$name;
  	foreach($vals as $row=>$cell){
  		 if(!isset($this->data[$row]) && count($this->columnames) == 0) { // pokud je to prvni sloupec tabulky
  		    $this->data[$row]=array();
  	   } 
       if(count($this->data[$row])==count($this->columnames)){
       	  $this->data[$row][]=$cell;
       }  		
  	}
  	$this->columnames[]=$name;
  }
  /**
   * prida tabulce titul, ktery se zobrazi v prvnim radku souboru
   * @param string $string
   */
  public function AddTitle($string){
  	$this->title = $string;
  }
  /**
   * prida radku hodnot, ktera musi mit stejne prvku jako je sloupcu
   * bez parametru prida radku, ktera byla predtim prubezne pridavana
   *   pomoci AddToRow()
   * @param array $datarow
   * @return int
   */
  public function AddRow($datarow=false){
  	if( count($this->columnames)==0){ // nemam definovane sloupce
  		if($datarow==false && count($this->datarow)>0){
  			$this->data[]=$this->datarow;
  			$this->datarow = array();
  		} elseif($datarow){
  			$this->data[]=$datarow;
  		}
  	} else {
	  	if($datarow==false){
	  		if(count($this->datarow)!= count($this->columnames)){
	  			//debug_print_r($this->datarow,"datarow");
	  			//debug_print_r($this->columnames,"columnames");
	  			$this->datarow = array(); // i tak smazu datovou radku 
	        return false;
	  		}
	      $this->data[]=$this->datarow; // smazu datovou radku, aby se nepridavalo
	      $this->datarow = array();
	  	} else {
		  	if(count($datarow)!= count($this->columnames))
		  		return false;
		  	$this->data[]=$datarow;
	  	}
  	}
  	return count($this->data)-1; // vrati cislo vlozene radky
  }
  /**
   * pridat dalsi hodnoty do vnitrni radky
   * ta se pak prida to tabulky dat pomoci AddRow()
   * @param array $values
   */
  public function AddToRow($values){
  	$this->datarow = array_merge($this->datarow,$values);
  }
  
  /**
   * vlozi a ulozi radku do souboru
   * addrow nasledovano saverow
   * 
   * @param array $datarow
   */
  public function AddSaveRow($datarow=false){
  	$this->SaveRow($this->AddRow($datarow));
  }
  /**
   * ulozi retezec jako radku do dat
   * @param string $string
   * @return int
   */
  public function AddRowString($string){
  	$this->data[]=array($string);
  	return count($this->data)-1; // vrati cislo vlozene radky
  }
  /**
   * vraci pocet sloupcu
   * @return int
   */
  public function ColumnCount(){
  	if(count($this->columnames)==0){ 
  		// pokud nemam definovane jmena sloupcu, pocitam delku nejdelsiho radku
  		$lenght = 0;
  		foreach($this->data as $row){
  			$lenght = max($lenght,count($row));
  		}
  		return $lenght;
  	} else {
  		return count($this->columnames);
  	}
  }
  /**
   * pocet radek tabulky
   * @return int
   */
  public function RowCount(){
  	return count($this->data);
  }
  /**
   * ulozi jmena sloupcu do jednoho radku souboru
   */
  public function SaveHead(){
  	if(!TABLEFILE_MATLAB || TABLEFILE_MATLAB_COLUMNAMES){
	  	if(!$this->error && strlen($this->title)>0 )
	  	   fwrite($this->fh,$this->title."\n");
	  	$out = implode($this->columndelim,$this->columnames);
	  	if(!$this->error && $this->filehandle_isset) fwrite($this->fh,$this->setdelim($out)."\n");
  	}
  }
  /**
   * zaokrouhli double cleny pole
   * v modu MATLAB retezcove cleny radky nahradi NaN
   * nastavi floatdelim u cisel
   * 
   * @param array $row
   * @return array
   */
  private function roundrow($row) {
  	foreach($row as $column => &$cell){
      if($this->isfloat($cell)){ // je to float a neni to datum
      	if(isset($this->precision_columns[$column])){
          	$cell = round($cell,$this->precision_columns[$column]);
      	} elseif($this->precision) { // pokud je precision false, nechci zaokrouhlovat vubec
      		$cell = round($cell,$this->precision);
      	} 
      	$cell = $this->setdelim($cell); // zmenu desetinne tecky na carku udelam tady, jinak se mi meni i v datumu
      } elseif(is_int($cell)){
      	; // neupravuju cell
      } elseif(empty($cell)){
      	$cell = $this->emptycell;
      } elseif(is_string($cell) && (TABLEFILE_MATLAB || $this->matlab)){
      	$cell = TABLEFILE_MATLAB_NAN?'NaN':$cell;
      } elseif (is_string($cell) && TABLEFILE_QUOATIONMARKS){
      	$cell = "\"".$cell."\"";
      }
    }
    if(count($row)<$this->columns){
    	$rozdil = $this->columns-count($row);
    	for($i=0;$i<$rozdil;$i++){
			$row[]=$this->emptycell;   		
    	}
    }
    return $row;
  }
  /**
   * nastavi presnost vystupu
   * pokud false, nebude se zaokrouhlovat
   * @param int $digits
   */
  public function SetPrecision($digits,$column = false){
  	if($column !==false && $column >= 0 && $column<$this->ColumnCount()){
  		$this->precision_columns[$column]=$digits;
  	} else {
  	 $this->precision = $digits;
  	}
  }
  /**
   * ulozi vybranou radku do souboru
   * @param in $n
   */
  public function SaveRow($n) {
  	$output = implode($this->columndelim,$this->roundrow($this->data[$n])); // zaokrouhlovat budu az pri tisku
  	if(!$this->error && $this->filehandle_isset) fwrite($this->fh,$output."\n");  //$this->setdelim(
  }
  /**
   * ulozi vsechny radky do souboru
   * echo znamena, jesti ma vypsat zpravu o ulozeni
   * @param bool $echo
   */
  public function SaveAllRows($echo = false){
  	$this->columns = $this->ColumnCount();
  	for($i=0;$i<count($this->data);$i++){
  		$this->SaveRow($i);
  	}
  	if($echo) echo "Table ".basename($this->filename)." saved\n";
  }
  /**
   * ulozi titul, jmena sloupcu a tabulku dat
   */
  public function SaveAll($echo = false){
  	if(!$this->matlab) $this->SaveHead();
  	$this->SaveAllRows($echo);
  }
  /**
   * v retezci nahradi desetinou tecku hodnotou DELIM
   * @param string $str
   * @return string
   */
  private function setdelim($str){
    return preg_replace("/([0-9])\.([0-9])/","$1".$this->floatdelim."$2",$str);
  }
  /**
   * vrati handle z fopen
   * @return int
   */
  public function Handle(){
  	return $this->fh;
  }
  /**
   * vrati aktualni filename
   * @return string
   */
  public function FileName(){
  	return $this->filename;
  }
  /**
   * vymaze data o sloupcich a tabulku dat, ale neuzavre soubor
   */
  public function Erase(){
  	$this->data = array();
  	$this->columnames = array();
  	$this->datarow = array();
  	$this->title = "";
  }
  /**
   * pripoji tabulku na konec teto tabulky
   * @param TableFile $table
   * @return bool
   */
  public function AppendTable($table){
  	if(!empty($this->data) && count($table->columnames)!=count($this->columnames)){
  		return false;
  	} else {
  		if(empty($this->columnames))  $this->columnames = $table->columnames;
      if(empty($this->title))  $this->title = $table->title;
  		foreach($table->data as $row)
  		 			$this->AddRow($row);
  		if(isset($table->precision)) $this->precision = $table->precision;
  		if(isset($table->precision_columns)) $this->precision_columns = $table->precision_columns;
  		return true;
  	}
  }
  /**
   * If you want to test whether a string is containing a float, rather than if a variable is a float, you can use this simple little function:
   * @param mixed $f
   * @return boolean
   * @since 12.10.2012
   */
  public function isfloat($f) {
  	return is_float($f); //mam cisla double opravdu, tenhle vyraz nefunguje a stejne ho nepotrebuju ($f == (string)(float)$f);  
  }
}
/**
 * trida, ktera nemusi mit automaticky otevreny soubor
 * @author kamil
 *
 */
class Table extends TableFile {
	function __construct(){
		$this->columndelim = TABLEFILE_COLUMNDELIM;
  		$this->floatdelim = TABLEFILE_DELIM;
	}
	function __destruct(){
		$this->fileclose(); // pokud je otevren
	}
	/**
	 * otevre soubor pro zapis
	 * jmenu souboru muze obsahovat cestu, adresar se v pripade potreby vytvori
	 * @param string $filename
	 */
	public function OpenFile($filename){
		$this->fileopen($filename);
		$this->filename = $filename;
	}
	/**
	 * zavre otevreny soubor
	 */
	public function CloseFile(){
		$this->fileclose();
	}
	/**
	 * priradi filehandle
	 * @param resource $fh
	 */
	public function FileHandle($fh){
		$this->filehandleset($fh);
	}
	/**
	 * vraci jednu radku tabulky
	 * @param int $n
	 * @return array|bool
	 */
	public function Row($n,$columns=false){
		if(isset($this->data[$n])){
			if($columns!=false){
				$columnindexes = array_combine(array_values($this->columnames), array_keys($this->columnames)); // vymenim klice a hodnoty pole
				$row = array();
				foreach($columns as $colname){
					$row[]=$this->data[$n][$columnindexes[$colname]];
				}
				return $row; // vratim vyber radky
			} else {
				return $this->data[$n]; // vratim celou radku
			}
		} else {
			return false;
		}
	}
	/**
	 * ulozi data, muze i otevrit soubor
	 * $matlab 1 nebo 0 nastavi vystup ve formatu matlabu
	 * @see TableFile::SaveAll()
	 */
	public function SaveAll($echo=false,$filename=false,$matlab=false){
		if($matlab!==false) parent::setMatlab($matlab==1);
  		if($filename!==false) $this->OpenFile($filename);
  		parent::SaveAll($echo); 
  		if($filename!==false) $this->CloseFile();
  		
	}
	/**
	 * ulozi radky bez hlavicky, muze i otevrit soubor,
	 * $matlab 1 nebo 0 nastavi vystup ve formatu matlabu
	 * @see TableFile::SaveAllRows()
	 */
	public function SaveAllRows($echo=false,$filename=false,$matlab=false){
		if($matlab!==false) parent::setMatlab($matlab==1);
  		if($filename!==false) $this->OpenFile($filename);
  		parent::SaveAllRows($echo);
	}
	/**
	 * vrati Table s radky, kde hodnota ve sloupci je rovna $val;
	 * zachova vsechny sloupce
	 * @param string $columname
	 * @param mixed $val
	 * @return Table|boolean
	 * @since 20.9.2012
	 */
	public function Select($columname,$val){
		if( ($col=array_search($columname, $this->columnames))!=false){
			$tbl = clone $this;
			$tbl->data = array();
			foreach($this->data as $row){
				if($row[$col]==$val) $tbl->AddRow($row);
			}
			return $tbl;
		} else {
			return false;
		}
	}
	/**
	 * vrati hodnoty jednoho sloupce v poli
	 * @param string $columname
	 * @return array |boolean
	 * @since 20.9.2012
	 */
	public function Column($columname){
	if( ($col=array_search($columname, $this->columnames))!=false){
			$data = array();
			foreach($this->data as $key=>$row){
				$data[$key]=$row[$col];
			}
			return $data;
		} else {
			return false;
		}
	}
	/**
	 * vrati prumer sloupce
	 * @param string $columname
	 * @return number|boolean
	 * @since 20.9.2012
	 */
	public function Average($columname){
		if( ($data = $this->Column($columname))!=false){
			return array_sum($data)/count($data);
		} else {
			return false;
		}
	}
	/**
	 * vrati sum of square
	 * @param string $columname
	 * @return number|boolean
	 * @since 20.9.2012
	 */
	public function SS($columname){
		if( ($data = $this->Column($columname))!=false){
		  foreach($data as $val){
		    $vals_sq[] = pow($val,2);
		  }
		  return array_sum($vals_sq)-pow(array_sum($data),2)/count($data);
		} else {
			return false;
		}
	}
	/**
	 * vrati stdev
	 * @param string $columname
	 * @return boolean|number
	 * @since 20.9.2012
	 */
	public function StDev($columname){
		if( ($data = $this->Column($columname))!=false){
			if(count($data)<2)
		      	return false;
			   else
			    return sqrt($this->SS($data)/(count($data)-1));
		} else {
			return false;
		}
	}
	/**
	 * nacte tabulku v souboru; smaze aktualni data; vraci pocet nactenych radku nebo false, pokud soubor nebyl nalezen
	 * @param string $filename
	 * @return int|boolean
	 * @since 30.1.2013
	 */
	public function ReadFile($filename){
		if(file_exists($filename)){
			$this->data = array();
			$this->columnames = array();
			$fc = file($filename);
			foreach($fc as $lineno=>$line){
				$line = trim($line);
				if($lineno==0){
					$this->columnames = explode($this->columndelim,$line);
				} else {
					$this->data[$lineno-1]=explode($this->columndelim,$line);
				}
			}
			return $lineno+1;
		} else {
			return false;
		}
	}
	/**
	 * vrati seznam unikatnich hodnot sloupce; vraci false pokud sloupce neexistuje
	 * @param string $columname jmeno sloupce
	 * @return array|bool
	 */
	public function Unique($columname){
		if( ($col=array_search($columname, $this->columnames))!=false){
			$values = array();
			foreach($this->data as $row){
				if(array_search($row[$col], $values)===false){
					$values[]=$row[$col];	// pokud hodnota jeste neni v poli, pridam ji			
				}
			}
			return $values;
		} else {
			return false;
		}
	}
	
	
}


?>