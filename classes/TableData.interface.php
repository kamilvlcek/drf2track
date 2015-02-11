<?php

/**
 * interface pro tabulkova data z libovolneho objektu
 */
interface TableData {
	/**
	 * metoda ktera z objektu vraci tabulku
	 * @return Table
	 */
	public function TableData();
	/**
	 * metoda importujici hodnoty z jineho objektu 
	 */
	public function ImportVals($object);
		
	/**
	 * funkce, tera vytvori objekt tridy
	 * muzu doplnit parametry
	 * @param array $parameters
	 */
	static function Factory($parameters);
	
	/**
	 * nastavi parametry objektu, podobne jako pri konstrukci
	 * @param array $parameters
	 */
	public function SetParameters($parameters);
	
	
	/**
	 * vlozi jednu hodnotu do objektu
	 * val muze urcovat tu hodnotu, nebo i souradnice te hodnoty, nebo cokoliv
	 * @param mixed $val
	 */
	public function AddVal($val);
	
}

?>