<?php

/**
 * trida na ulozeni nekolika hodnot pro pozici cile a cues (carr, r0arr ...), ktere se maji za sebou stridat
 *
 */
class CVarArr {
  private $varno; // pocet udaju za sebou, ktere se maji stridat
  private $arr;
  private $i; // index na soucasny udaj
  function __construct(){
    $this->arr = array();
    $this->varno = 0;
    $this->i = 0;
  }
  /**
   * prida dalsi hodnotu, ale neposune index. Takze cteni a pridavani je nezavisle
   * @param unknown_type $a
   */
  public function add($a){
    $this->arr[]=$a;
    $this->varno++;
  }
  public function reset(){
    $this->i = 0;
  }
  /**
   * vrati soucasnou hodnotu a posune index na dalsi.
   *
   * @return unknown
   */
  public function next() {
    $ret = isset($this->arr[$this->i]) ? $this->arr[$this->i] : false; //15.11.2012 - taky muze byt nevlozena zadna hodnota
    if(++$this->i >= $this->varno){
      $this->reset();
    }
    return $ret;
  }
  /**
   * vrati soucasnou hodnotu a NEPOSUNE index
   *
   * @return unknown
   */
  public function current() {
    return $this->arr[$this->i];
  }
  /**
   * vrati hodnotu podle indexu
   * @param int $i
   * @return mixed
   */
  public function val($i){
  	return $this->arr[$i];
  }
  /**
   * vrati pocet nactenych hodnot
   * @return int
   * @since 7.1.2013
   */
  public function count(){
  	return count($this->arr);
  }
}
?>