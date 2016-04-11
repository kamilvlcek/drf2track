<?php

/**
 * trida na predavani argumentu prikazove radky
 * @author Kamil.Vlcek
 * @since 11.4.2016
 */
class CmdLine {	
	/**
	 * pocet argumentu +1, 1 znamena zadny argument
	 * @var int
	 */
	private $argc;
	/**
	 * pole argumentu, 0 volaci skript, 1 je prvni argument
	 * @var array
	 */
	private $argv;	

	public function __construct(){
		global $argc, $argv;
		if(isset($_GET['arg'])) { // opatreni pro debug v Zend studiu 7.1.1
			$argv = array_merge(
				array(""), // index 0 musi byt jmeno aktualniho skriptu
				explode("|",trim($_GET['arg'])) // jednotlive argumenty oddelit znakem |
			); 
			$argc = count($argv);			 
		} 
		$this->argc = $argc;
		$this->argv = $argv;
	}
	/**
	 * vraci pocet argumentu
	 * @return int
	 */
	public function Pocet(){
		return $this->argc-1; 
	}
	/**
	 * vraci argument c. n, n je pocitano od 0
	 * nebo false, pokud spadne cislo argumentu
	 * @return mixed|false
	 */
	public function Arg($n){
		if($n < $this->argc-1){
			return $this->argv[$n+1];
		} else {
			return false;
		}
	}
}

?>