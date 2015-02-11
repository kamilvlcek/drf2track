<?php


if(!defined('GOALRADIUS')) define('GOALRADIUS',15);
require_once('includes/point.php');
require_once('classes/cvararr.class.php');
class UTBvaVars extends CTrackVars {
  var $dir;
  var $trial;
  var $filenames;
  var $myphase; // od 1, phase musi byt od 0
  /* to snad prevezme z CTrackVars
   function UTBvaVars($dir){
    if(!is_dir($dir)){
      dp("  ERR:dir not found: $dir!!!");
			$this->error = true;
	 	  return;
		} else {
		  $this->error = false;
      $this->line = 0;
      $this->track = 0;
      $this->phase = 0;
      $this->trial = 0;
      $this->intrack = false;
      $this->trackstart = true; //zacina mi track - novy obrazek
      $this->dir = $dir;
    }
  }*/
  /**
   * nacita data definovanych sektoru - jednu radku, volano z ReadFile
   * fazi si nacte s te radky, track dostane v parametrech
   *
   * @param str $line
   * @param int $track
   */
  function SaveSectorDef($line,$track){
  }
  /**
   * nacita data definovanych cues, volano z ReadFile
   * fazi si nacte s te radky, track dostane v parametrech
   *
   * @param str $line
   * @param int $track
   */
  function SaveCueDef($line,$track) {
      // to uz dela ReadFile
  }
  /**
   * nacita data tracku, volano z ReadFile
   * lineno je tu jen kvuli vypisu
   *
   * @param str $line
   * @param int $track
   * @param int $lineno
   */
  function SaveTrack($line,$track,$lineno){
      // to uz dela ReadFile
  }
  /**
   * tohle musim predelat na predchozi 4 funkce
   * nacte data do poli:
   * ve funkci SaveSectorDef:
   *        *cvar[$track][$phase],
   *        *cnamevar[$track][$phase],
   *        *r0var[$track][$phase]
   *        *cxyvar[$track][$phase],
   *        *reltovar[$track][$phase],
   *        *savoidvar[$track][$phase],
   *        *r[$track][$phase]
   *        *keytocues[$phase]
   *
   * ve funkci SaveCueDef:
   *        *laservar[$track][$phase][$cue],
   *        *segmentsvar[$track][$phase][$cue],
   *        *startPointvar[$track][$phase][$cue]
   * ve funkci SaveTrack:
   *        *counts[$track][$phase]
   *        *roomxyarr[$track][$phase][$trial]
   *        *framearr[$track][$phase][$trial]
   *        *goalnoarr[$track][$phase][$trial]
   *        *klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]
   *        *avoidarr[$track][$phase][$trial][]
   *
   */
  function ReadFile(){
    // naplni:
    // PRO VYSTUP:
    // $this->counts[$track][$phase] - pocet trialu v kaze fazi, fazi v tracku a tracku
    // $this->keytocues[$phase] - klavesa ktera je stlacena na zacatku tracku
    // $this->reltovar[$track][$phase] - k pozici jakeho startu se bere cil relativne - pokud 0 =prazdne - jinak cislo faze + 1
    // $this->cvar[$track][$phase] - postupne se stridajici pozice cile - c
    // $this->r0var[$track][$phase] - postupne se stridajici pozice cile r0
    // $this->cxyvar[$track][$phase]
    // $this->r[$track][$phase] - prumer cile
    // $this->laservar[$track][$phase][$cue]
    // $this->segmentsvar[$track][$phase][$cue]
    // $this->startPointvar[$track][$phase][$cue]
    // $this->sectors  - cisla sloupcu, kde jsou ulozene jednotlive sektory
    // $this->names

    // PRO INTERNI POTREBU
    // $this->klavesaposition, - cislo slupce, kde je klavesa


    //2008-5-13_12-57-48-Experiment_1_FirstStage_15Degrees.log
    // 0        1                   2 3           4
    // 1-8 budou faze
    // FirstStage a SecondStage budou trialy
    // 15Degress a 130Degress budou tracky
    if (($handle = opendir($this->filename))!=false) {
         while (false !== ($file = readdir($handle))) {
             if(substr($file,0,1)!="."){
               dp( "$file"); // ted treba 2008-11-4_14-47-31-Experiment_1_ShowGBefore_ShowGAfter.log
               // 0 - datum, 1 - cas, 2- trial, 3-poznamky
               // puvodne 2008-5-13_14-14-44-Experiment_8_SecondStage_15Degrees.log
               //if(substr($parts[4],0,2)=="15") $track = 0; else $track = 1;
               //$uhelcile = substr($parts[4],0,3);
               //if(substr($parts[3],0,5)=="First") $trial = 0; else $trial = 1;
               $parts = explode("_",$file);
               $track = 0;
               // musim to udelat tak, ze jdou po sobe faze a pak teprv dalsi trial. Na tom je zalozena funkce NextNo
               $trial = intval($parts[2]/8); // kamil 10.11.2008
               $phase = $parts[2]%8; //uz to je od 0
               $name = $parts[3].(isset($parts[4])?"_".$parts[4]:"");
               if(strpos($name,".")) $name = substr($name,0,strpos($name,".")); // uriznu .log

               if(!isset($this->counts[$track][$phase])){
                 $this->counts[$track][$phase]=1;
                 $this->reltovar[$track][$phase]=new CVarArr();
                 $this->reltovar[$track][$phase]->add(0);
                 $this->r[$track][$phase]=GOALRADIUS;
               } else {
                 $this->counts[$track][$phase]++;
               }
               $this->filenames[$track][$phase][$trial]="$file";
               $fc = file($this->filename."/".$file);
               $intrack = false;
               foreach ($fc as $lineno=>$line){
                 if(substr($line,0,12)=="GoalPosition"){
                    if(!isset($this->cxyvar[$track][$phase])) $this->cxyvar[$track][$phase]=new CVarArr();
                    if(!isset($this->cvar[$track][$phase])) $this->cvar[$track][$phase]=new CVarArr();
                    if(!isset($this->r0var[$track][$phase])) $this->r0var[$track][$phase]=new CVarArr();
                    if(!isset($this->cnamevar[$track][$phase])) $this->cnamevar[$track][$phase]=new CVarArr();
                    if(!isset($this->savoidvar[$track][$phase])) $this->savoidvar[$track][$phase]=new CVarArr();
                    $xy = $this->getxy(substr($line,14));
                    $xy[1]=-$xy[1]; // nevim proc, ale v drftrack se prevraci souradnice, taky ji tady musim taky prevratit
                    $this->cxyvar[$track][$phase]->add($xy);
                    $this->cnamevar[$track][$phase]->add($name);
                    $this->savoidvar[$track][$phase]->add(0); // je to preference sector
                    $this->cvar[$track][$phase]->add(rad2deg(angletocenter($xy,array(0,0)))); // $c a r0 je relativne k centru
                    $this->r0var[$track][$phase]->add(distance($xy,array(0,0))/ARENAR*100); // to ma byt v procentech polomeru kruhu

                 } elseif (substr($line,0,12)=="MarkPosition"){
                   $xy = $this->getxy(substr($line,14));
                   if(!isset($this->laservar[$track][$phase][0])) $this->laservar[$track][$phase][0]=new CVarArr();
                   if(!isset($this->segmentsvar[$track][$phase][0])) $this->segmentsvar[$track][$phase][0]=new CVarArr();
                   if(!isset($this->startPointvar[$track][$phase][0])) $this->startPointvar[$track][$phase][0]=new CVarArr();
                   $this->laservar[$track][$phase][0]->add(rad2deg(angletocenter($xy,array(0,0))));
                   $this->segmentsvar[$track][$phase][0]->add(1);
                   $this->startPointvar[$track][$phase][0]->add(0);
                 } elseif(substr($line,0,13)=="StartPosition"){
                   // start
                   $xy = $this->getxy(substr($line,15));
                   if(!isset($this->laservar[$track][$phase][1])) $this->laservar[$track][$phase][1]=new CVarArr();
                   if(!isset($this->segmentsvar[$track][$phase][1])) $this->segmentsvar[$track][$phase][1]=new CVarArr();
                   if(!isset($this->startPointvar[$track][$phase][1])) $this->startPointvar[$track][$phase][1]=new CVarArr();
                   $this->laservar[$track][$phase][1]->add(rad2deg(angletocenter($xy,array(0,0))));
                   $this->segmentsvar[$track][$phase][1]->add(0);
                   $this->startPointvar[$track][$phase][1]->add(1);
                 } elseif(substr($line,0,10)=="PlayerPath"){
                   $intrack = true;
                 } elseif($intrack){
                   if(substr($line,0,11)=="First Beep!"){
                       $line = $fc[$lineno+1];
                       $this->roomxyarr[$track][$phase][$trial][] = $this->getxy($line);
                       $this->framearr[$track][$phase][$trial] = 0;
                       $this->goalnoarr[$track][$phase][$trial] = 0;
                       $this->klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]="";
                       $this->avoidarr[$track][$phase][$trial][] = 0;
                       break;
                   } else {
                       $this->roomxyarr[$track][$phase][$trial][] = $this->getxy($line);
                       $this->framearr[$track][$phase][$trial] = 0;
                       $this->goalnoarr[$track][$phase][$trial] = 0;
                       $this->klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]="";
                       $this->avoidarr[$track][$phase][$trial][] = 0;
                   }
                 }
               }
               $this->klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]=$this->KeyToNext();
             }
         }
         closedir($handle);
      }
      $this->keytocues[$phase] = "";

      $this->sectors = array();
      $this->names = array();
      $this->no = -1;// priste se zacne cist od zacatku
        $this->track= 0;
        $this->phase = 0;
        $this->trial = 0;
        $this->trackstart = true;
        $this->trialstart = true;
        $this->phasestart = true;
  }
  /**
   * tohle snad uz nebudu potrebovat, pouziju z CTRACK VARS
   *
   * @param bool $first
   * @return unknown
   */
  /*function Next($first=true){
    // naplni:
    // $this->roomxy
    // $this->arenaxy
    // $this->phase
    // $this->frame
    // $this->pausa
    // $this->goalno
    // $this->avoid
    // obrazky se meni pri zmene faze

    if(empty($this->fc)){
      if(!file_exists($this->dir."/".$this->filenames[$this->track][$this->phase][$this->trial])){
        dp($this->filenames[$this->track][$this->phase][$this->trial],"file not exist: $this->track $this->phase $this->trial");
        // co ted? - nic fc bude porad prazdne a kousek niz se pojede na dalsi soubor v poradi
      } else {
        $this->fc = file($this->dir."/".$this->filenames[$this->track][$this->phase][$this->trial]);
        dp($this->dir."/".$this->filenames[$this->track][$this->phase][$this->trial]);
        $this->line = 0;
        while(substr($this->fc[$this->line++],0,10)!='PlayerPath')
          ;
      }
    }
    if(isset($this->fc[$this->line])){
      $this->roomxy = $this->getxy($this->fc[$this->line]);
      $this->arenaxy = $this->roomxy;
      if($this->intrack) $this->trackstart = false;
      $this->frame = 0;
      $this->pausa = false;
      $this->goalno = $this->trial;
      $this->avoid = false;
      $this->intrack = true;
      if($this->line == count($this->fc)-1){
        $this->klavesa = KEYTONEXT;
      } else {
        $this->klavesa = "";
      }
      $this->line++;
      return true;
    } else {
      unset($this->fc);
      $this->line = 0; // budu zacinat dalsi soubor
      $this->intrack = false;
      if(++$this->phase > count($this->counts[$this->track])-1){
        $this->phase = 0;
        if(++$this->trial > $this->counts[$this->track][$this->phase]-1){
         $this->trial = 0;
          if(++$this->track > count($this->counts)-1){
            return false; // uz nic dalsiho neni
          } else {
            $this->trackstart = true;
          }
        }
      }
      return $this->Next(false);
    }


  }*/
  function getxy($str){
    $parts = explode(",",$str);
    $xparts = explode(".",$parts[0]);
    $yparts = explode(".",$parts[1]);

    return $this->recalc_140(array($xparts[0],$yparts[0]));
  }
  /**
   * prepocitam souradnice bodu na min a max [-140;140]
   *
   * @param [x,y] $bod
   * @return [x,y]
   */
  function recalc_140($bod){
    $bod=tocenter($bod,$this->Center());
    $bod[1]=-$bod[1];
    return todiam($bod,$this->Radius(),ARENAR);
  }

  function ArenaRealXMin(){
    return -400;
  }
  function ArenaRealXMax(){
    return -127;
  }
  function ArenaRealYMin(){
    return 109;
  }
  function ArenaRealYMax(){
    return 390;
  }
  function KeyToNext(){
    return "H"; //HOME
  }
  function FramesToSec($frames){
    return $frames/10; // 1 frame po 100 ms
  }
  function BodSize(){
    return 3;
  }
}
?>