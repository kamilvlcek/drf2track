<?php

/**
 * trida na ukladani sql dotazu,jejich vysledku a jinych tisku do zvlastniho soubour
 * zavedena 8.10.2009 kvuli moznosti nastaveni limitu bufferu
 */

class Logout {
  private $buffer; // buffer v ram, pokud bude vetsi nez MAXSIZE, ulozi se do souboru
  const MAXSIZE = 10000;
  private $filename;
  private $saved; // kolik jiz bylo ulozeno
  private $nechat_soubor; // jestli soubor nechat po ukonceni
  private $anchors_xml;
  private $anchors_sql;
  private $logid;
  function __construct($filename=false,$nechat=false,$logid=false){
    if($nechat) $this->nechat_soubor = true;
    $this->start($filename,$logid);
  }
  public function log($msg,$type=0) {
    switch ($type){
    case 1:
      $name = "xml".count($this->anchors_xml);
      $msg = "<a name='$name'></a>\n".$msg;
      $this->anchors_xml[]=$name;
      break;
    case 2:
      $name = "sql".count($this->anchors_sql);
      $msg =  "<a name='$name'></a>\n".$msg;
      $this->anchors_sql[]=$name;
      break;
    }
    $this->buffer.=$msg."\n";
    if(strlen($this->buffer)>self::MAXSIZE){
     $this->flush();
    }
  }
  /**
   * na pozadani vyprazni obsah bufferu do docasneho souboru - ten pak najdu s cislem ssesid
   * budu to volat pred kazdym sql dotazem, abych vedel kde ty docasne neukoncene soubory vznikaj. 
   * 19.9.2011
   */
  public function flush(){
      $this->save();
      $this->buffer = '';
  }
  public function start($filename=false,$logid=false){
    $this->buffer = '';
    $this->saved = 0;
    $sid = $logid ? $logid :""; //session_id();
    $this->filename = ($filename)?$filename:dirname($_SERVER['SCRIPT_FILENAME'])."/files/logout.".self()."-$sid.log.html";
    if(file_exists($this->filename)) unlink($this->filename);
    $this->anchors_sql = $this->anchors_xml= array();
    //echo $this->filename;
    // pridam sid do filename, protoze muze na tomtez souboru byt nekolik pozadavku soucasne
      /*
    $filename1 = dirname($_SERVER['SCRIPT_FILENAME'])."/files/logout.".self()."1.log.html";
    $filename2 = dirname($_SERVER['SCRIPT_FILENAME'])."/files/logout.".self()."2.log.html";

    if(!file_exists($filename1))                             $filename = $filename1;
    elseif(!file_exists($filename2))                         $filename = $filename2;
    elseif( filemtime($filename1) < filemtime($filename2))   $filename = $filename1;
    else                                                     $filename = $filename2;
    */

  }
  private function filename_permanent(){
  	$id = !empty($this->logid) ? "-".$this->logid."-": "";
    return dirname($_SERVER['SCRIPT_FILENAME'])."/files/logout.".self()."$id.log.html";
  }
 /**
   * uklada periodicky po MAXSIZE logy do soubory s SessionID
   * pokud zadam jmeno aktualni log zkopiduje do udaneho jmena, ale pak pokracuje dal v puvodnim ukladani
   * 
   * @param string $filename
   */
  public function save($filename=false){
    if($filename && $filename!=$this->filename && file_exists($this->filename)){
      copy($this->filename,$filename); // zkopiruje dosud ulozena data
    } else {
      $filename = $this->filename;
    }
    if(($fh = fopen($filename,"a"))!=false){
      fwrite($fh,$this->buffer);
      fclose($fh);
     $this->saved += strlen($this->buffer);
    }
  }
  /**
   * umoznuje vlozit id logu, v tomto pripade filelist
   * @param string $logid
   */
  public function setid($logid){
  	$this->logid = $logid;
  }
  function __destruct(){
    //if(function_exists('memory_get_usage')) $this->buffer.= "Logout destruct: Memory Usage: ".memory_get_usage()."\n";
    $this->save();
    if(file_exists($this->filename)){
      if($this->nechat_soubor){
        copy($this->filename,$this->filename_permanent());
      }
      unlink($this->filename);
    }
  }
  /**
   * nedodelana funkce zamyslena pro ulozeni seznamu xml a sql prikazu. Ale jeste to bude chtit dodelat nejak nazvy (1. radky) prikazu
   *
   */
  private function saveanchors(){
    $arr = array();
    foreach ($this->anchors_sql as $a) {
    	$arr[]="<a href='#$a'>$a</a>";
    }
    $this->buffer .="<br>SQL: ".implode("|",$arr);

    $arr = array();
    foreach ($this->anchors_xml as $a) {
    	$arr[]="<a href='#$a'>$a</a>";
    }
    $this->buffer .="<br>XML: ".implode("|",$arr);
  }
}

/**
 * trida pro manipulaci s ruznymi vypisy
 * @author kamil
 *
 */
class Tisk  {
    /**
     * uprava s parametrem lenght 14.8.2009
     * uprava s parametrem $max_array 26.9.2009
     *
     * @param mixed $a
     * @param str $label
     * @param int $length
     * @param int $max_array
     * @param book $newline pokud false, odpoved je jeden radek bez \n a -- ; 29.1.2010
     * @return string
     */
    static public function dpshort($a,$label="",$length=1000,$max_array=100,$newline = true){
        //global $flog, $echodp;
        $ret = ($newline?"\n--":"").$label.":".($newline?"\n":"");
        if(is_array($a)){
          $n = 0;
          foreach($a as $key => $aa) {
            if(is_array($aa)){
              $ret .= self::dpshort($aa,"--$key",$length,$max_array);
            } elseif(is_object($aa)){
              $ret .= "".$key."=>Object; ";
            } elseif(is_bool($aa)){ // kamil 24.8.2010
              $ret .= "".$key.($aa?"=>bool(true); ":"=>bool(false); ");
            } else {
              $ret .= "".$key."=>".substr($aa,0,$length)."; ";
    
            }
            if(++$n>$max_array) break;
          }
    
        } else {
          $ret .= $a;
        }
        return $ret;
    }
    
    /**
     * vypise zformatovany backtrace
     * prevzato z projektu nev-dama
     */
    static public function backtrace($html=false){
        $backtrace = debug_backtrace();
        foreach($backtrace as $i=>$trace){
          echo "call $i: file ".$trace['file'].", line ".
          $trace['line'].", args: ".self::dpshort($trace['args'],"",30,5).($html?"<br>":"")."\n";
        }
    }

}

class ErrorBuffer {
	private $errors;
	private $CLogout;
	function __construct($logid){
		$this->CLogout = new Logout(false,false,$logid);
	}
	function Add($errno, $errstr, $errfile, $errline){
		if(empty($this->errors[$errno][$errstr][$errfile][$errline])){
			
			switch ($errno) {
				case E_ERROR:
					$errname = "E_ERROR";
					break;
				case E_WARNING;
				  $errname = "E_WARNING";
					break;
				case E_NOTICE;
				  $errname = "E_NOTICE";
					break;
				case E_RECOVERABLE_ERROR;
				  $errname = "E_RECOVERABLE_ERROR";
					break;
				case E_USER_ERROR;
				  $errname = "E_USER_ERROR";
					break;
				case E_USER_WARNING;
				  $errname = "E_USER_WARNING";
					break;
				default:
				  $errname = "$errno";
					break;
			}
			$messageHtml = "\n\nPHP ERROR **************************\ndatum a cas: ".date("j.n.Y H:i:s")."\n<br>";
			$messageHtml.= "$errname:$errstr, errfile:$errfile, errline: $errline\n<br>";
			$backtrace = debug_backtrace();
			foreach($backtrace as $i=>$trace){
			  	if(empty($trace['function'])) $trace['function']="";
			  	if(empty($trace['class'])) $trace['class']="";
			  	if(empty($trace['file'])) $trace['file']="";
			    if(empty($trace['line'])) $trace['line']="";
			    $messageHtml .= "call $i: file ".basename($trace['file']).", line ".$trace['line']."\n<br>";
			    $messageHtml .= "func: $trace[class].<b>$trace[function]</b>, ";
			    $messageHtml .= "args: <blockquote>".$this->pre($trace['args'],"arguments")."</blockquote>";
			}
			$this->errors[$errno][$errstr][$errfile][$errline]['pocet'] = 1;
			$this->errors[$errno][$errstr][$errfile][$errline]['html'] = $messageHtml;
			$txt = "$errname:$errstr, ".basename($errfile)."|$errline \n";
			$this->errors[$errno][$errstr][$errfile][$errline]['txt'] = $txt;
			$this->CLogout->log($messageHtml);
			echo $txt;
		} else {
			$this->errors[$errno][$errstr][$errfile][$errline]['pocet']++;
		}
	}

	function Save(){
		if(!empty($this->errors)){
			foreach ($this->errors as $e1)
				foreach ($e1 as $e2)
					foreach ($e2 as $e3)
						foreach ($e3 as $e4){
							$this->CLogout->log($e4['html']."\n<br>".$e4['pocet']."x\n<br>");
							echo $e4['txt'];
						}
		}
		//$this->CLogout->save();
	}
	function __destruct(){
		$this->Save();
	}
	/**
	 * print_r s <pre> a label, pokud $debug nebo $force
	 *
	 * @param mixed $a
	 * @param string $label
	 * @param bool $force
	 */
	function pre($a,$label="",$force=false){
		global $debug,$logout;
	  	ob_start();
	  	
		echo "<pre> ***PRE ";
		if(!empty($label)) echo "'".$label."': "; else echo "'':";
		
		$backtrace = debug_backtrace();
		$trace = $backtrace[0];
    	echo basename($trace['file'])." - ".$trace['line'].": ";
		if(!is_array($a)) echo "'";	 // apostrofy kolem hodnoty - kamil  14.11.2011
    	print_r($a); // ma tam byt print_r nebo nove var_dump?
		if(!is_array($a)) echo "'";	
    	echo "</pre>";
    	
	    return ob_get_clean();

	}
}

/**
 * zachytavac chyb
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function myErrorHandler($errno, $errstr, $errfile, $errline){
  	global $CErrorBuffer;
	$CErrorBuffer->Add($errno, $errstr, $errfile, $errline);
	//ob_start();  // to se dela vzdy pak se akorat podle nastaveni lisi, co se s vystupem udela
	
	
  //$message .= "self:".$_SERVER["PHP_SELF"]."\nip:".$_SERVER['REMOTE_ADDR']."\n";
  // $message .= "session_id: ".session_id()."\n";
  //$message .= "GET:".get_predat()."\nPOST:".post_predat()."\n";
  
  
  //$message.= "HTTP_REFERER: ".$_SERVER['HTTP_REFERER']."\n";
  
  //error_log($message."\n\n",3,"log/phperror.".self().".".date_yearmon().".log"); // sem se to ma logovat vzdy, i kdyz je logout - logout je na serveru porad true;
 /*
  if($errno==E_USER_ERROR || $errno==E_ERROR) {
	  $CLogout->log($message); //ob_get_clean()
	  $logout_filename = "./files/logout_php_error.".self()."_".date("Y-m-d_H-i-s").".log.html";
      $CLogout->save($logout_filename);
  } else {
	  $CLogout->log($message); // musim vypraznit bufferob_get_clean()
  }
  */
}
/**
 * vraci aktualni soubor bez adresare
 *
 * @return string
 */
function self(){
	//return $_SERVER["PHP_SELF"];
	if(strpos($_SERVER["PHP_SELF"],"/")===false){
		$lomitko = "\\";
	} else {
		$lomitko = "/";
	}
	return substr($_SERVER["PHP_SELF"],strrpos($_SERVER["PHP_SELF"], $lomitko)+1);
}
?>