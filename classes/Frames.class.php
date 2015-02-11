<?php
define("FRAMES_CHANGE0123",1); // jestli se ma placechange pocitat v rozsahu 0-3 - 12.4.2013

class Frames {
	private $lastplace;
	private $lastframe;
	private $lastplacecode;
	
	private $place;
	private $frame;
	private $placecode; // index v poli mist
	
	public $roomplaces = array();
	public $arenaplaces = array();
	private $framechange = -1;
	private $placechange = -1; // 0=stejne misto jako minule, 1=stejny frame, ale jine misto, 2=jiny frame
	private $pocet_stejnychframu = 0; //12.1.2015 - kolik trialu je stejny frame
	private $pocet_zmenframu = 0; //13.1.2015 - kolik trialu je zmena framu
	private $pocet_history = 0; // 13.1.2015 - kolik bylo stejnych udalosti predtim - zmen framu (pri noframechange), nebo naopak stejnych framu (pri zmene frame)
	function __construct($roomplaces,$arenaplaces) {
	  $this->roomplaces = $roomplaces;
	  $this->arenaplaces = $arenaplaces;
	}
	function Reset(){
		unset($this->lastframe);
		unset($this->lastplace);
		unset($this->frame);
		unset($this->place);
		unset($this->lastplacecode);
		$this->framechange=-1;
		unset($this->placecode);
		$this->pocet_stejnychframu = 0;
	}
	/**
	 * ulozi aktualni pozici cile spolu s jeho framem
	 * vraci frame toho cile - 1 = arena, 0 = room
	 * @param string $placename
	 * @return int
	 */
	function AddGoal($placename){
		$is_place = false;
		$this->lastplacecode = isset($this->placecode)? $this->placecode : -1;
		$this->lastframe = isset($this->frame)? $this->frame: false;
		$this->lastplace = isset($this->place)? $this->place: false;
		
		if($this->roomframe($placename)){ // ROOM 
			$this->frame = 0; // roomframe
			$this->place = $placename;
			$this->placecode = array_search($placename,	$this->roomplaces);
			$is_place = true;
		} elseif($this->arenaframe($placename)){ // ARENA
			$this->frame = 1;
		  	$this->place = $placename;
		  	$this->placecode = array_search($placename,	$this->arenaplaces);
		  	$is_place = true;	
		} // jinak se nedela nic. stred nechci vubec ukladat
		if(!isset($this->lastframe) || $this->lastframe===false){
			$this->framechange = -1;
			$this->pocet_stejnychframu=0; // opet stejny frame jako minule
			$this->pocet_zmenframu=0;
			$this->stejnychpredtim = -1;
		} else {
			$this->framechange = $this->frame != $this->lastframe?1:0;
			if($this->framechange){ // je zmena framu
				$this->stejnychpredtim = $this->pocet_stejnychframu;
				$this->pocet_stejnychframu = 0; // pri zmene framu nuluju
				$this->pocet_zmenframu++;
			} else { // stejny frame jako predtim
				$this->stejnychpredtim = $this->pocet_zmenframu;
				$this->pocet_stejnychframu++;
				$this->pocet_zmenframu=0;
			} 
		}
		if(!isset($this->lastplace) || $this->lastplace===false ){
			$this->placechange = -1;
		} else {
			$this->placechange = $this->framechange? 2 : ($this->place !=$this->lastplace ? 1 : 0); // 2 1 0
		}
		
		return ($this->frame && $is_place)?1:0;			
	}
	/**
	 * zmena framu 0 nebo 1
	 * pokud zadano $placename tak aktualni misto k udanemu mist
	 * jinak aktualni misto k minulemu mistu
	 * @param $placename
	 * @return int
	 */
	function FrameChange($placename=false){
		if($placename && ($this->roomframe($placename) || $this->arenaframe($placename))){
			// stred ignoruju, jako kdyby false
		   	$frame = $this->roomframe($placename)?0:1;
		   	if(!isset($this->frame)) 
		   		return -1; // prvni trial faze, neni to ani frame change ani no change
		   	else 
		   		return   $this->frame!= $frame ?1:0; 
		} else {
			return $this->framechange;
		}
	}
	/**
	 * vraci 0=stejne misto jako minule, 1=stejny frame, ale jine misto, 2=jiny frame
	 * pokud zadam $placename, tak aktualni misto k udanemu mistu
	 * @return int
	 */
	function PlaceChange($placename =false){
		if($placename && ($this->roomframe($placename) || $this->arenaframe($placename))){
			if(!isset($this->place)) {
				return -1;
			} else {
				if(FRAMES_CHANGE0123){
					return self::change($this->placecode14name($placename),$this->placecode14name($this->place)); // kod 0-3
				} else {
					return $this->FrameChange($placename)? 2 : ($this->place!=$placename ? 1 : 0);
				}
			}	
		} else {
			if(FRAMES_CHANGE0123){
				if(isset($this->place) && isset($this->lastplace)){
					return self::change($this->placecode14name($this->place),$this->placecode14name($this->lastplace));// kod 0-3
				} else {
					return -1;
				}
			} else {
				return $this->placechange;
			}
		}
	}
	/**
	 * vrati pocet trialu pred aktualnim po kterych byl 
	 * 1) stejny frame pri aktualni zmene framu OR 2) zmena framu pri aktualnim stejnem framu  
	 * @return int
	 * @since 12.1.2015
	 */
	function StejnychFramu(){
		return $this->stejnychpredtim;
	}
	/**
	 * place code predchoziho mista, pokud nebylo zadne vraci -1
	 * @return int
	 */
	function PlaceCodeLast(){
		return $this->lastplacecode; // placecode predchoziho mista
	}
	private function roomframe($placename){
		return in_array($placename,$this->roomplaces);
	}
	private function arenaframe($placename){
		return in_array($placename,$this->arenaplaces);
	}
	/**
	 * vrati kod zmeny 0-3, 3 znamena zmenu napr A1-M2=framu i mista;
	 * placecode 2 je rozcleneno na 2 a 3
	 * kopie sem z aapp_pxlab.php 12.4.2013
	 * 
	 * @param int $place_code
	 * @param int $last_place
	 * @since 12.11.2012
	 */
	static function change($place_code,$last_place){
		if($last_place<0) return -1;
		//$changecodes = array(  1/*A2*/=>array(1=>0,1,2,3/*M1*/),   
		//					   2/*A1*/=>array(1=>1,0,3/*M2*/,2),
		//					   3/*M2*/=>array(1=>2,3/*A1*/,0,1),
		//					   4/*M1*/=>array(1=>3/*A2*/,2,1,0) 
		//	  );
		// 7.2.2014 - zmena, aby placechange 2 bylo na stejne strane centralni znacky a 3 na opacne
		$changecodes = array(  1/*A2*/=>array(1=>0,1,3,2/*M1*/),   
							   2/*A1*/=>array(1=>1,0,2/*M2*/,3),
							   3/*M2*/=>array(1=>3,2/*A1*/,0,1),
							   4/*M1*/=>array(1=>2/*A2*/,3,1,0) 
			  );
		return $changecodes[$last_place][$place_code];
	}
	/**
	 * vraci kod mista 1-4(arena 12, room 34)
	 * @param int $placecode
	 * @param int $frame 0=room,1=arena
	 * @return int
	 */
	static function placecode14($placecode,$frame){
		return $placecode+1+(1-$frame)*2;
	} 
	/**
	 * vrati kod mista 1-4 z $placename
	 * @param string $placename
	 * @return int
	 */
	private function placecode14name($placename){
		if($this->roomframe($placename)){ // ROOM 
			$frame = 0;
			$placecode = array_search($placename,	$this->roomplaces);
		} elseif($this->arenaframe($placename)){ // ARENA
			$frame = 1;
		  	$placecode = array_search($placename,	$this->arenaplaces);
		} else {
			return -1;
		}
		return self::placecode14($placecode, $frame);
	}
}

?>