<?php

require_once ('classes/TableFile.class.php');

/** 
 * @author Kamil
 * trida, jejiz prvky mohou byt objekty
 * taha z nich pak tabulkova data pomoci interface TableData
 * 
 */
class TableObject extends Table {
	protected $columnsobj = array(); //jmena sloupcu, ktera tridi objekty 
	protected $dataobj = array(); //pole objektu
	protected $object_type; // typ objektu ktery budu importovat, smi se jen jeden typ
	protected $parameters;
	protected $setparam = false; // jestli se pri table data maji davat nove hodnoty parametru
	/**
	 * 
	 */
	public function __construct($columns,$objecttype,$parameters=false) {
		parent::__construct ();
		$this->columnsobj = $columns;
		$this->object_type = $objecttype;
		$this->parameters = $parameters; 
	}
	/**
	 * vlozi jednu hodnotu do objektu podle columns
	 * @param array $columns
	 * @param mixed $val
	 */
	public function AddVal($columns,$val){
		$this->addvalue($columns, $val,$this->dataobj);
	}
	private function addvalue($colstodo,$val,&$data){
		if(count($colstodo)>0){
			$column= array_shift($colstodo); // sebere prvek ze zacatku
			if(!isset($data["$column"])) $data["$column"]=array();
			$this->addvalue($colstodo, $val, $data["$column"]);
		} else {
			if(!isset($data) || count($data)==0) $data = call_user_func(array($this->object_type,'Factory'),$this->parameters);
			$data->AddVal($val); // tohle uz je objekt s interface TableData
		}
	}
	/**
	 * Enter description here ...
	 * @param unknown_type $tableobjarray
	 * @param unknown_type $colsdone
	 */
	public function ImportArray($tableobjarray,$colsdone){
		// vnorim vstupni pole do stejne urovne jako mam dataobj
		for ($i=0;$i<count($colsdone);$i++){
			$col = array_pop($colsdone);
			$tableobjarray = array($col=>$tableobjarray);
		}
		$this->import_arr($tableobjarray, $this->dataobj, $this->columnsobj);
		
	}
	/**
	 * importuje pole objektu
	 * rekurzivni funkce volana do hloubky pole dataobj
	 * 
	 * @param object $tableobj
	 * @param array $data
	 * @param array $colstodo
	 */
	private function import_arr(&$table,&$data,$colstodo,$colsdone=array()){
		if(count($colstodo)>0){
			$column= array_shift($colstodo); // sebere prvek ze zacatku
			foreach($table as $tablevalue=>$tabledata){
				$columnsdone = $colsdone;
				$columnsdone[]=$tablevalue;
				if(!isset($data[$tablevalue])) $data[$tablevalue]=array();
				$this->import_arr($tabledata,$data[$tablevalue],$colstodo,$columnsdone);
			}
		} else { //  uz mam seznam sloupcu
			if(!isset($data) || count($data)==0) $data = call_user_func(array($this->object_type,'Factory'),$this->parameters);
			$data->ImportVals($table); // tohle uz je objekt s interface TableData
		}
	}
	
	/**
	 * importuje hodnoty do celkove tabulky
	 * @param TableData $tableobj
	 * @param array $column_vals
	 */
	public function ImportVals($tableobj,$column_vals){
		// TODO tady chci importovat data z jine TableObj. Ale jak to udelam, pro ruzne hluboke stromy poli dat?
		// import bude pomoci funkce ImportVals objektu, kterou ma z interface TableData
		if(count($this->columnsobj)==count($column_vals)){
			$this->import($tableobj, $this->dataobj, $column_vals);
		}
	}
	/**
	 * rekurzivni funkce volana do hloubky pole dataobj
	 * @param object $tableobj
	 * @param array $data
	 * @param array $colstodo
	 */
	private function import($tableobj,&$data,$colstodo){
		if(count($colstodo)>0){
			$column=array_shift($colstodo);
			if(!isset($data[$column])) $data[$column]=array();
			
			$this->import($tableobj,$data[$column],$colstodo); // rekurze
		} else { //  uz mam seznam sloupcu
			if(!isset($data) || count($data)==0) $data = call_user_func(array($this->object_type,'Factory'),$this->parameters);
			$data->ImportVals($tableobj); // tohle uz je objekt s interface TableData
		}
	}
	/**
	 * vraci souhrnnou tabulku ze vsech vlozenyc objektu
	 * @return Table
	 */
	public function TableData($parameters=false){
		if($parameters && is_array($parameters)){
			$this->parameters = $parameters;
			$this->setparam = true;
		}
		$table = new Table();
		$this->table($table, $this->dataobj, $this->columnsobj);
		return $table;
		
	}
	private function table(&$table,&$data,$colstodo,$colsdone=array()){
		
		if(count($colstodo)>0){
			$column= array_shift($colstodo); // sebere prvek ze zacatku
			foreach($data as $columnvalue =>$columndata){
				$columnsdone = $colsdone;
				$columnsdone[]=$columnvalue;
				$this->table($table,$columndata,$colstodo,$columnsdone);
			}
		} else {
			if($this->setparam) $data->SetParameters($this->parameters);
			$datatable = $data->TableData();// tabledata muze mit vic radku
			for($row = 0;$row<$datatable->RowCount();$row++){ // pro vsechny radky table data 
				$table->AddToRow($colsdone);
				$table->AddToRow(array($row)); // dalsi sloupec bude radka tabledata
				$table->AddToRow($datatable->Row($row));
				$table->AddRow();
			}
		}
		
		
		
	}
}

?>