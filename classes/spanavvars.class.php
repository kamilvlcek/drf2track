<?php
/*
Musim si vyjasnit
  - co je track, phase, trial
    cvar[$track][$phase]
    tady je jenom track0, trial0 a phase0 a jen ruzne trialy
  -
    laservar[$track][$phase][$cue]



*/
//define('GOALRADIUS',15); // nepouziva se tady
define('STOPMAKEPHASE',0); // jestli prvni zastaveni oddeluje 1. a 2. fazi
// filelist vFGN_manuscriptIIprobes.txt - 30.10.2014
//define('SPOJITCILETRIALS',"6+0,7+112,8+225"); // uhly 112 a 225 podle 1111_3_0.tr_0_ROOM.cdr
//define('SPOJITCILETRIALS',"26+0,27+112,28+225"); // uhly 112 a 225 podle 1111_3_0.tr_0_ROOM.cdr
require_once('includes/point.php');
require_once 'classes/cvararr.class.php';
require_once 'classes/pointmove.class.php';

/**
 * trida na data z tracku Spanav
 * tvorba od 2.4.2009
 * 28.8.2009 - umi rozdelit trialy na faze 0 a 1 podle mista prvniho zastaveni. Pokud je STOPMAKEPHASE = 1
 *
 */
class SpaNavVars extends CTrackVars {
  var $dir;
  /**
   * pouziva se jen pro import marks do SpaNavVars
   * @var int
   */
  var $trial; // TODO this-trial sjednotit s $trial v SaveTrack
  var $myphase; // od 1, phase musi byt od 0
  /**
   * xls tabulka, ktera se uklada do _trackExp.txt;
   * sloupce track, trial, time, track x,y,z, eye x,y,z - neprepocitane primo z tracku
   * @var string
   */
  var $exporttrack=''; // tam budu ukladat retezec vystupu pro jirku -
  /**
   * xls tabulka, ktera se uklada do _trackMark.txt;
   * sloupce track,trial,markname,x,y,x
   * @var string
   */
  var $exportmarks=''; // tam budu ukladat retezec s pozicemi znacek
  /**
   * @var SpaNavCfg
   */
  var $CSpaNavCfg; // konfigurace ze souboru
  var $aimareas; // pozice jednotlivych aim areas v tracku

  var $klavesa_faze; // 28.7.2009 14:49:06
  /**
   * vraci true, pokud tento typ dat umi eyetracking
   * @var unknown_type
   */
  private $eyetracking=true;
  private $mapname;
  private $trial_started = false; // ze zacal dalsi strial
  private $arenaangle = false; // posledni rotace areny - nastavuje se v SaveTrack

  /**
   * trida na zpracovavani charakteristik pohybu clovek
   *
   * @var PointMove
   */
  private $CPointMove;
  /**
   * @var SpaNavFrame_Attached
   */
  private $CSpaNavFrame;
  /**
   * ktere cile mohou byt prepsany jinymi cily - t(=time) a pokud GOALBYENTRANCE==0 taky e(=entrance);
   * tim resim situaci, kdy GOALBYENTRANCE==0 a neni stlacena klavesa g - 11.7.2012;
   * nastavuje se pro kazdy trial zvlast - 10.12.2012
   * @var array
   */
  private $prepisovatelne_cile; 
  
  
  function SpaNavVars($filename,$cfg,$param=false){
    if(empty($cfg) || !file_exists($cfg) || !is_file($cfg)) {
   		$cfg = "spanavcfg.class.php"; // zakladni soubor, ktery nacte pozice mist z hlavicky - 11.8.2009 16:53:07
    }
    if(file_exists("classes/".$cfg)){
      require_once("classes/".$cfg);
      $classname = substr($cfg,0,strpos($cfg,"."));
      $this->CSpaNavCfg = call_user_func(array($classname,'Factory'));
      $this->CPointMove = new PointMove();
      $this->CSpaNavFrame = new SpaNavFrame_Attached();
    } else {
      dp("  ERR:cfg filename not found: $cfg!!!");
			$this->error = true;
	 	  return;
    }
    parent::CTrackVars($filename);
    dp("track min: [{$this->roomxymin[0]},{$this->roomxymin[1]}], track max: [{$this->roomxymax[0]},{$this->roomxymax[1]}] ");
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

    $this->keytocues[0] = $this->CSpaNavCfg->KeyToCues();
    $this->keytopoint = $this->CSpaNavCfg->KeyToPoint();
    if(STOPMAKEPHASE) $this->keytocues[1] = $this->CSpaNavCfg->KeyToCues();

	  $this->fc = file($this->filename); // dam to sem, protoze to je specificke pro tenhle typ souboru
    $track = 0; // soucasne cislo tracku - je tam vzdy jen jeden track
    $intrack = false;
    $this->trial = 0;
    foreach ($this->fc as $lineno=> $line) {
      $this->line = $lineno;
      // TODO zpracovat taky pozice v poli Mark position, funkce ImportMarks
      if(substr($line,0,11)==' MAP CHANGE'){
      	$track++;
      	$this->trial = 0; // zvysuje se v $this->ResetTrial a to v SaveTrack
      	$intrack = false;
      } elseif(substr($line,0,13)=='Aim position:'){
      	$this->CSpaNavCfg->ImportAims($track,$this->trial,substr($line,13));
      } elseif(substr($line,0,14)=='Mark position:'){
      	$this->CSpaNavCfg->ImportMarks($track,$this->trial,substr($line,14));
      } elseif(substr($line,0,17)=="Position changed:"){
      	$this->CSpaNavCfg->ImportMark($track,$this->trial,substr($line,17),$this->arenaangle);
      	$this->trial_started = true;
      } elseif(substr($line,0,9)=="Map name:"){
      	$this->mapname = trim(substr($line,9));
      } elseif(substr($line,0,5)==' Time'){
      	$intrack = true;
      	$this->AimAreasNameSave($line); // ulozi jmena cilovych mist
      } elseif($intrack && substr($line,0,3)!="---" && substr($line,0,6)!="Avatar"){
      	if(!$this->SaveTrack($line,$track,$lineno)){
      	  return false;
      	}
      }
    }
    //$this->CSpaNavCfg->MarksXY(); // spocita max a min znacek - kvuli velikosti areny
    if(empty($this->goaltypearr)) { $this->error = true; dp("ERR: zadny vstup do cile!"); return false; }
    $this->LastTrialCheck($line);
    $this->SaveSectorDef();
    $this->SaveCueDef(); // data o znackach nactena pomoci importmark(s) nacte to std formadu drf2track
    if(ARENAFRAME) {
    	 $this->SaveSectorDef(false,false,"AF");
    	 $this->SaveCueDef(false,false,"AF"); 
    }
    
  	$this->EyeTrackExport();

    $this->no = -1;// priste se zacne cist od zacatku
    $this->track= 0;
    $this->phase = 0;
    $this->trial = 0; 
    $this->trackstart = true;
    $this->trialstart = true;
    $this->phasestart = true;
    if(!isset($this->roomxyarr) || !is_array($this->roomxyarr) || count($this->roomxyarr)==0) {
  		dp("ERR: zadna data!");
      $this->error = true;
  		return false;
  	} else {
  		$this->error=false;
  		return true;
  	}

  }
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
   * nacita data definovanych sektoru - jednu radku, volano z ReadFile;
   * fazi si nacte s te radky, track dostane v parametrech;
   * 
   *
   * @param str $line
   * @param int $track
   * @param string $frame RF=roomframe, AF = arenaframe
   */
 
  function SaveSectorDef($line=false,$track=false,$frame="RF"){
     // kamil 13.8.2009 8:25:37 - predelal jsem pro automaticke nacitani goals z tracklogu
     
    $sekv_goals = $this->CSpaNavCfg->SekvGoals($frame);
  	if(count($sekv_goals)==0){
    	return; // nic nevracim
    } 
  	foreach($sekv_goals as $track=>$sekv_goals_track){
       $r = $this->CSpaNavCfg->radius_goal/$this->Radius($track)*100; // prevod na procenta polomeru
  	   foreach($sekv_goals_track as $trial=>$goal){
  	          if(defined('SPOJITCILETRIALS') && SPOJITCILETRIALS!="") {
  	          	$goalxy = $this->SpojitCile($trial, $sekv_goals_track, $track);
  	          } else {
  	   		   	$goalxy = $this->recalc_140($goal,$track); // -140 x je vlevo, 140 y je dolu
    	       	$goalxy[1]=-$goalxy[1];// empiricky zjisteno, ted uz je 140y nahoru
  	          }
    	      $this->$frame->AddGoal_XY($track,0,$goalxy,$r,$this->CSpaNavCfg->GoalName($track,$trial,$frame),0,0);
    	      if(STOPMAKEPHASE)  // jestlil zastaveni rozdeli prvni a druhou fazi
    	           $this->$frame->AddGoal_XY($track,1,$goalxy,$r,$this->CSpaNavCfg->GoalName($track,$trial,$frame),0,0);

       // 17.10.2014 - plan na vypocet vzdalenosti o tri cilu soucasne, kvuli probetrialum v vFGN
       // definovat, ktere cile spojit. 
       // pridat ostatni cile do trialu jako array
       // pridat funkcnost array do    AddGoal_XY
       // pridat vypocet vzdalenosti od tri cilu do  UlozKvadranty

     
    	       /* PREMISTENO DO CTrackVarsFrame
    	       $this->cxyvar[$track][0]->add($goalxy);
    	       $this->cvar[$track][0]->add(rad2deg(angletocenter($goalxy,array(0,0))));
    	       $this->cnamevar[$track][0]->add($this->CSpaNavCfg->GoalName($track,$trial)); // jmeno bude cislo cile v seznamu pozic
    	       $this->r0var[$track][0]->add(distance($goalxy,array(0,0))/140*100);
    	       $this->savoidvar[$track][0]->add(0); // je to preference sector
    	       $this->reltovar[$track][0]->add(0); // neni relativni k nicemu
    	       */
       }
       /*
         if(STOPMAKEPHASE) { // jestlil zastaveni rozdeli prvni a druhou fazi
         $this->cvar[$track][1] = clone $this->cvar[$track][0];
         $this->cnamevar[$track][1] = clone $this->cnamevar[$track][0];
         $this->r0var[$track][1] = clone $this->r0var[$track][0];
         $this->savoidvar[$track][1] = clone $this->savoidvar[$track][0];
         $this->reltovar[$track][1] = clone $this->reltovar[$track][0];
         $this->cxyvar[$track][1] = clone $this->cxyvar[$track][0];
         $this->r[$track][1] = $this->r[$track][0];
       }*/
     }

    /*
    $this->cvar[$track][$phase]->add($vals[10]); -
    $this->cnamevar[$track][$phase]->add(isset($vals[17])?$vals[17]:"");
    $this->r0var[$track][$phase]->add($vals[6]);
    $this->savoidvar[$track][$phase]->add($vals[3]);
    $this->r[$track][$phase] = $vals[5]; // zatim muze byt je jedna hodnota pro fazi (14.4.2008)
    $this->reltovar[$track][$phase]->add($vals[11]); // relativne k cemu
    $cxy = angledist2xy(deg2rad($vals[10]),$vals[6]/100*ARENAR);// vystup z funkce ma y=+10 nahore a -10 dole
    $cxy[1]=-$cxy[1];// empiricky zjisteno, potrebuju aby y souradnise se zvetsovala dolu
    $this->cxyvar[$track][$phase]->add($cxy); // to je pole o dvou prvcich, 0 a 1 (x a y), snad to bude fungovat
    */
  }
  /**
   * udela pole cilu pro aktualni trial, podle konstanty SPOJITCILETRIALS; kvuli probetrialum ve vFGN
   * @param int $trial
   * @param array $sekv_goals_track
   * @param int $track
   * @return array
   * @since 20.10.2014
   */
  private function SpojitCile($trial,$sekv_goals_track,$track){
   	  if(defined('SPOJITCILETRIALS') && SPOJITCILETRIALS!=""){
   	  	 $goal_arr[]=$sekv_goals_track[$trial];
      	 $spojit = explode(",",SPOJITCILETRIALS); //"6+0,7+120,8+240" - trial + uhel otoceni vzhledem k privnimu ze spojenych trialu
      	 $spoj_cile = array();
      	 foreach($spojit as $s){
      	 	list($s_trial,$s_uhel)=explode("+", $s);
			$spoj_cile[$s_trial]=$s_uhel;      	 	
      	 }
      	 if(in_array($trial, array_keys($spoj_cile))){
      	 	foreach($spoj_cile as $s_trial=>$s_uhel){
      	 		if($s_trial!=$trial) {
      	 			$otocit = -($s_uhel - $spoj_cile[$trial]); // o kolik se ma cil otocit, 
      	 			// - kdyz jsou cile ve skutecnosti na stejnych mistech ale znacky otoceny
      	 			// zapornou hodnotu tam, mam protoze souradnice jsou y obracene a takhle to funguje 
      	 			$goal_arr[]=rotatecenter($otocit,$sekv_goals_track[$s_trial],$this->Center($track));
      	 		}
      	 	}
      	 } 
	   	 foreach($goal_arr as &$goalxy){
	      	 	   $goalxy = $this->recalc_140($goalxy,$track); // -140 x je vlevo, 140 y je dolu
	    	       $goalxy[1]=-$goalxy[1];// empiricky zjisteno, ted uz je 140y nahoru
	     }
	     if(count($goal_arr)>1){ // pokud opravdu vice cilu, vratim pole
	     	return $goal_arr;
	     } else {
	     	return reset($goal_arr); // pokud se tento trial nema spojovat, nebudu vracet pole
	     }
      } else {
      	return false;
      }
  }
  /**
   * nacita data definovanych cues, volano z ReadFile
   * fazi si nacte s te radky, track dostane v parametrech
   *
   * @param str $line
   * @param int $track
   * @param string $frame RF=roomframe, AF = arenaframe
   */
  function SaveCueDef($line=false,$track=false,$frame="RF") {
    /* $this->laservar[$track][$phase][$cue]->add($this->laserc($vals[3])); // laser 1 - 8
       $this->segmentsvar[$track][$phase][$cue]->add($vals[5]); // segments 0-3; 0 znamena, ze sviti nesviti zadny segment (jen start)
       $this->startPointvar[$track][$phase][$cue]->add($vals[4]); // 0-1 - 1= je to start
       */
    // je tu jen jeden track, jedna phase a jedno cue

    // STARTY
    //$this->laservar[0][0][0]=new CVarArr();
    //$this->segmentsvar[0][0][0]=new CVarArr();
    //$this->startPointvar[0][0][0]=new CVarArr();
    // KAMIL 13.8.2009 8:28:32 - zatim nemuzu vic udelat, protoze nemam poradi startu.
	  /*foreach ($this->CSpaNavCfg->sekv_starts as $start) {
	       $markxy = $this->recalc_140($this->CSpaNavCfg->Start($start));
	       //$markxy[1]= -$markxy[1];
	       $this->laservar[0][0][0]->add(rad2deg(angletocenter($markxy,array(0,0))));
	       $this->segmentsvar[0][0][0]->add(0);
	       $this->startPointvar[0][0][0]->add(1);

    }*/
    // ZNACKY
    /*ob_start();
    var_dump($this->CSpaNavCfg->RF); echo "\n";
    file_put_contents("var_dump.txt",ob_get_clean());
    exit;*/
    if(isset($this->CSpaNavCfg->$frame->marks)){
      $cuelist = $this->CSpaNavCfg->MarkList();
      foreach($this->CSpaNavCfg->$frame->marks as $track=>$marks){
      	// cilova struktura laservar[$track][$phase][$cue]->add
      	// struktura marks: [track][trial][cue]
        $cuemax = $this->CSpaNavCfg->MarkCount($track);
  	    foreach ($marks as $trial=>$mark){
  	      $cue = 0;
  	      foreach($mark as $name=>$position){
  	        //$this->CheckCueVarArr($track,$cue); // kontrola, jestli nemam vytvorit nove polozky poli
            $markxy = $this->recalc_140($position,$track);
            //vypocet uhlu znacek v Unrealu - predpokladam stred [0,0] 
            $this->$frame->AddCue($track,0,$cue,rad2deg(angletocenter($markxy,array(0,0)))/*laser*/,
                  ($cuelist[$name]-1)%8+1 /*segments*/,0/*neni start*/,$this->CSpaNavCfg->MarkShortName($name), new CPoint($markxy));
            if(STOPMAKEPHASE)  {
            	$this->$frame->AddCue($track,1,$cue,rad2deg(angletocenter($markxy,array(0,0))),
                  ($cuelist[$name]-1)%8+1 /*segments*/,0/*neni start*/,$this->CSpaNavCfg->MarkShortName($name), new CPoint($markxy));
            }
            /*
            $this->laservar[$track][0][$cue]->add(rad2deg(angletocenter($markxy,array(0,0))));
            $this->segmentsvar[$track][0][$cue]->add(($cuelist[$name]-1)%8+1); //$barvy = array(1=>"red","green","orange");
            $this->startPointvar[$track][0][$cue]->add(0);
            $this->marknamevar[$track][0][$cue]->add($this->CSpaNavCfg->MarkShortName($name)); // predpokladam jmeno Mark
            */
            $cue++;
  	      }
  	      for(;$cue<count($cuelist);$cue++){ // znacka se nezobrazi. Musim ji pridat
  	        //$this->CheckCueVarArr($track,$cue);
  	        $this->$frame->AddCue($track,0,$cue,-1,0,0,' ',new CPoint(0,0));
  	        if(STOPMAKEPHASE) $this->$frame->AddCue($track,1,$cue,-1,0,0,' ',new CPoint(0,0));
             /*     
  	        $this->laservar[$track][0][$cue]->add(-1);
            $this->segmentsvar[$track][0][$cue]->add(0);
            $this->startPointvar[$track][0][$cue]->add(0);
            $this->marknamevar[$track][0][$cue]->add('');
            */
  	      }
  	    }
  	    /*
  	    if(STOPMAKEPHASE){ // jestli zastaveni subjekta v tracku oddeluje faze
  	      foreach($this->laservar[$track][0] as $cue=>$data){
      	    $this->laservar[$track][1][$cue] = clone $this->laservar[$track][0][$cue];
            $this->segmentsvar[$track][1][$cue] = clone $this->segmentsvar[$track][0][$cue];
            $this->startPointvar[$track][1][$cue] = clone $this->startPointvar[$track][0][$cue];
            $this->marknamevar[$track][1][$cue]= clone $this->marknamevar[$track][0][$cue];
  	      }
  	    }
  	    */
      }
	  $this->markradius = $this->CSpaNavCfg->markradius/$this->Radius($track)*100; // prevod na procenta polomeru
    }


    // DRUHA ZNACKA POKUD JE DEFINOVANA
    // to uz je nahrazene dvourozmernym polem sekv_marks
    /*if(isset($this->CSpaNavCfg->sekv_marks2)
    	&& is_array($this->CSpaNavCfg->sekv_marks2)
    	&& count($this->CSpaNavCfg->sekv_marks2)>0){
	    	$this->laservar[0][0][2]=new CVarArr();
		    $this->segmentsvar[0][0][2] = new CVarArr();
		    $this->startPointvar[0][0][2] = new CVarArr();
		    foreach ($this->CSpaNavCfg->sekv_marks2 as $mark) {
		       $markxy = $this->recalc_140($this->CSpaNavCfg->marks[$mark]);
		       //$markxy[1]= -$markxy[1];
		       $this->laservar[0][0][2]->add(rad2deg(angletocenter($markxy,array(0,0))));
		       $this->segmentsvar[0][0][2]->add(1);
		       $this->startPointvar[0][0][2]->add(0);
		    }
    }*/
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
    static $lastphase=-1,/*$klavesalast,*/$startbyl=false,$cilbyl=false, $aim_entered = false, $probe_end = false; 
    static $aims=array('current'=>'-','last'=>'-','lastvalid'=>'-');
    static $goaltype = false, $pointed = false; //jestli jsem uz v tomto trialu ukazano pomoci klavesy s
    if($this->trial_started){
      $lastphase = 0;
      $this->trial_started = false;
      $this->CPointMove->Reset();
    }

    $phase = $this->StopMakePhase($startbyl,$lastphase); // zmeni startbyl, osetruje kdyz se ma oddelit faze pri zastaveni

    if(empty($this->counts[$track][$phase])) { // protoze kdyz delam druhy soubor v poradi, nejak mi ty defaultni hodnoty static nefunguji
      // kamil 26.8.2008
      // this counts obsahuje pocty tracku - takze pokud nezacala jeste faze 0
      //$lastphase = -1;
      $startbyl = false;
      if(!$this->Start_KeyToCues($phase) || !KEYTOSTART_AFTERMARK) {
          $this->trial = -1; // jestli neni definovana klavesa pro start 
          // predpoklad: 
          // - kdyz je definovano c pro start, tak je v datech az po Position changed: Mark13
          // takze ResetTrial zvysi cislo $this->trial a to bude az pro dalsi PositionChanged
          // - kdyz neni definovano, je treba aby this->trial = -1 protoze ResetTrial se vola hned na zacatku, 
          // pred Position changed: 
          //23.5.2012 - udelam nastaveni ve filelistu - hotovo
          //	- C pred PositionChanged nebo nedefinovano - trial = -1
          //    - C definovano po PositionChanges - trial = 0 
          // data d:\prace\mff\data\HGTestSCH\data\K-MUn-120517_1_0.tr
      } 
      $cilbyl = false;
      $aim_entered = false;
      /**
       * cas konce probetrialu; false pokud se nejedna o probetrial
       */
      $probe_end = false;
      $pointed = false;
      $goaltype = false;
      $aims=array('current'=>'-','last'=>'-','lastvalid'=>'-');
      
      //$aim_active = false; // ktery aim je aktivni - ktery hledam
      //$aim_entered_num = false; // kolik mam do nej uz vstupu
    }

    $vals = explode("\t",$line); //preg_split("/\s{8,13}/",trim($line));<br>

    if(count($vals)>=11 && is_numeric($vals[0]) && is_numeric($vals[7])) { // pro jistotu
      $pausa = $vals[10];
      $frameno = $vals[1];
      $cas = (float) $vals[0]; // 10.4.2012
      $klavesa = ($vals[9]=='-' || empty($vals[9]))? "":strtolower($vals[9]); // $vals[$this->klavesaposition];
      if(!empty($this->keytopoint) && $klavesa==$this->keytopoint && $pointed) $klavesa = ""; // pokud podruhe ukazovano, budu ignorovat - 10.4.2012
      if($this->CSpaNavCfg->GetGoalByEntrance() || (!empty($aims['current']) && $aims['current']!='-')){
      	$aims['lastvalid'] = $aims['current']; // posledni neprazny cil - 11.7.2012 - kluli GOALBYENTRANCE
      }
      $aims['last'] = $aims['current']; 
      $aims['current']= trim($vals[12]); // 0 je aktualni cil, 1 je minuly cil (sloupec Preference v datech)
      $this->CSpaNavFrame->Add($vals[13]); //PrefAttached, jestli je cil vazany k arene
      $arenaangle = $this->arenaangle = $vals[4];

      // VYHODIT
      if(in_array($klavesa,array(1,2,3,4,5,6))) $this->klavesa_faze = $klavesa-1; // - 28.7.2009 14:50:17 budu to pocitat od nuly

      // KLAVESA C - ZACATEK TRIALU
      if(!$startbyl && ($this->Start_KeyToCues($phase,$klavesa) || $this->Start_StopMakePhase($phase,$lastphase))){
      	  // pridat zacatek noveho trialu pomoci zmeny pozice znacek
          $this->ResetTrial($startbyl,$cilbyl,$aim_entered,$probe_end,$vals[12],$cas,$track,$phase,$goaltype);
          $lastphase = $phase;
          if($this->trial>0){
          	//$this->CPointMove->LogSpeeds($this->filename,$this->trial-1);
            $this->CPointMove->Reset();
          }
          $pointed = false;
         
          
      }

      // MAM ULOZIT RADKU TRACKU - po startu a pred cilem
      if($this->StartByl($startbyl, $cilbyl, $goaltype, $pausa)){ // pri probetrialu chci aby se zakreslovala i cast po uplynuti casu - 10.4.2012
      	$trial = $this->counts[$track][$phase]-1;
      	$this->prepisovatelne_cile = $this->CSpaNavCfg->GetGoalByEntrance($trial)?array('t','e'):array('t');
        if(!isset($this->roomxyarr[$track][$phase][$trial])) {
          // tisk az po tom, co je start - protoze on taky nemusi byt start v nekterem trialu
          //dp($lineno,"track $track, phase $phase, trial $trial");
          echo "line: $lineno, track $track, phase $phase, trial $trial, frame ".$cas."\r"; 
        }
        // SOURADNICE TRACKU
        $xy_spanav = array(intval($vals[2]),intval($vals[3]));
        $this->roomxymax = (!isset($this->roomxymax))? $xy_spanav : pointmax($xy_spanav,$this->roomxymax);
        $this->roomxymin = (!isset($this->roomxymin))? $xy_spanav : pointmin($xy_spanav,$this->roomxymin);
        
        /* @var $xy_room [x,y] -140;140 */
        $xy_room = $this->int_point($this->recalc_140($xy_spanav,$track));
        //$xy[1]=-$xy[1]; // nevim proc, ale v drftrack se prevraci souradnice, taky ji tady musim taky prevratit
        $this->roomxyarr[$track][$phase][$trial][] = $xy_room;
        
        // OTOCENI ARENY
        if(ARENAFRAME) { 
          //$AFpozice = new CPoint($xy_arena);
          //$RFpozice = new CPoint($xy);
          $arenaRFangle = 0;//$AFpozice->AngleDiff($RFpozice);
          if(!isset($this->arenaangle0)) {
            $this->arenaangle0 = $arenaangle; // natoceni areny na zacatku tracku
            $this->arenaRFangle = $arenaRFangle; // rozdil mezi RF a AF na zacatku tracku
          } elseif(abs($this->arenaangle0-$arenaangle)<0.1 && $this->arenaRFangle != $arenaRFangle){ // pokud se arena zatim neotocila
            $this->arenaRFangle = $arenaRFangle; // ulozim novy uhel mezi RF a AF 
          }
        }
        
        if(ARENAFRAME){
        	//$xy_arena = array(intval($vals[5]),intval($vals[6]));
        	//$xy_arena = $this->int_point($this->recalc_140($xy_arena,$track));
        	// zkusim pocitat z roomframu
        	$xy_arena = rotate($arenaangle-$this->arenaangle0,$xy_room);
        	$this->arenaxyarr[$track][$phase][$trial][]=$xy_arena; 
        }

        // UHEL POHLEDU - kamil 4.5.2009
        //$view = $this->recalc_140(array(intval($vals[5]),intval($vals[6])));
        $this->anglearr[$track][$phase][$trial][] = $this->AngleViewNormalize($vals[7]);// rad2deg(angletocenter($view,$xy));
        // mail iva 6.5.2009 - posunout sloupce > 3 o dva, diky spatne verzi chybi udaje ViewX a ViewY
        //$this->viewxyarr[$track][$phase][$trial][] = $view;

        // DALSI UDAJE
        
        $this->goalnoarr[$track][$phase][$trial] = $trial; // ani nevim jestli to na neco je
        $this->CPointMove->Add(ARENAFRAME? new CPoint($xy_arena) : new CPoint($xy_room));
        if($this->CPointMove->ZastavilPoprve() && STOPMAKEPHASE) $klavesa = 's'; // stop - oznaceni prvniho zastaveni //TODO nemuze byt natvrdo 's'
        
        if(!empty($klavesa)){
          $this->uloz_klavesu($klavesa,$track,$phase,$trial);
          if( !empty($this->keytopoint) && $klavesa==$this->keytopoint && !$pointed){
          		$pointed = true;  // uz ukazal na cil
          		//if($probe_end) //24.9.2012 - pokud je definovana klavesa pro ukazani, nenastavuje probe_end pri stlaceni C, takze takhle podminka tam byt nemuze 
          		$probe_end =  $cas+$this->CSpaNavCfg->ProbeLenght();
          }
        }

        if($vals[12]!='-' && $aim_entered===false){
        	$aim_entered = $vals[$this->aimareas[$vals[12]]]; // kolik uz je vstupu do te aktualni aim, budu pak hlidat, jestli se to nezmeni
        	//$probe_end = false; // osetreni toho, ze se probe_end nekdy nepozna hned pri zmacknuti c, protoze se Aim nastavi v tracku az v pristim radku
        	// probe end false jsem zrusil 10.4.2012- proc to tu bylo?
        } /*elseif($vals[12]=='-') {
        	$aim_entered = false;
        }*/
		
		/*
        if(false && $aim_entered!==false && /x*$vals[12]!='-' &&*x/ $aim_entered!= $vals[$this->aimareas[$vals[12]]])
        	$this->avoidarr[$track][$phase][$trial][] = 1; // tohle nemuze byt 1, protoze jinak to meri vzdalenost a chybu pro pruchod cilem a na pro G
        else
        	$this->avoidarr[$track][$phase][$trial][] = 0;
        plneni avoidarr jsem premistil do nasledujici podminky CilByl
        */
   		$this->avoidarr[$track][$phase][$trial][] = $this->CSpaNavCfg->InGoalArea($xy_spanav, $track, $trial, $aims['lastvalid'])? 10:0; // tady nastavim na 0, dale kdyz tak zmenim na 1

        if($this->TimeInfo()){
          $this->timearr[$track][$phase][$trial][]=$cas; // cas v sekundach
        }

        // EYE TRACKING
        if($this->EyeTrackingInfo() && EYETRACKING){
          $this->EyeTrackSave($track,$phase,$trial,$vals);
        }
        // CIL BYL - 12.11.2012 nema to smysl, pokud nebyl start a nema se ulozit radka tracku
      	$this->CilZpracuj($track,$phase,$trial,$klavesa, $probe_end, $cilbyl, $startbyl,$cas, $aims, $goaltype);
      	  
      } // if start byl

      return true;
    } else {
      dp($lineno,"ERR spanavvars.class.php: spatny format radky - na pozici 7 ma byt ViewX! a na pozici 0 cas; data line");
      if($this->error)
      	return false; // pokus uz takova chyba nastala, ukoncim
      else {	
      	$this->error = true; // jen nastavim priznak chyby
      	return true; // zatim OK
      }
    }

  }
  /**
   * zvysi aktualni trial, resetuje hodnoty startbyl cilbyl, pocet vstupu do cile a cas do konce probetrialu
   * @param bool $startbyl
   * @param bool $cilbyl
   * @param bool|int $aim_entered  - kolikrat uz jsem vstoupil do aktivni oblasti
   * @param bool|float $probe_end - cas ve vterinach konce probetrialu
   * @param string $aim - jmeno cile primo z tracku
   * @param string $cas - cas z tracku
   * @param int $track
   * @param int $phase
   */
  function ResetTrial(&$startbyl,&$cilbyl,&$aim_entered,&$probe_end,$aim,$cas,$track,$phase,&$goaltype){
    $startbyl = true; //
    $cilbyl = false;
    $aim_entered = false; // kolikrat uz jsem vstoupil do aktivni oblasti
    $probe_end = false;
    $goaltype = false;

    // TADY UKLADAM JEDNU RADKU TRACKU
  	if(empty($this->counts[$track][$phase])) {
      $this->counts[$track][$phase]=1;
  	} else {
      $this->counts[$track][$phase]++; // dalsi trial
  	}
  	// NENI TO PROBETRIAL?
    if($aim=='-' && $this->CSpaNavCfg->ProbeLenght() > 0 && empty($this->keytopoint) ){ 
    	/* 6.6.2012 - kdyz je definovana klavesa pro ukazani, nemuze probetrial zacit u stlaceni C - zacatku trialu, ale az u S */
    	// 24.9.2012 - kdyz nastavuju probeend i pri klavese C a soucasne je definovana klavesa pro ukazani 
      $probe_end = (float) $cas + $this->CSpaNavCfg->ProbeLenght(); // cas ve vterinach konce probetrialu
      // obcas se stane, ze aktivni oblast je nastavena az v pristim radku
    }
    //$aim_active = intval(substr($vals[10],3));
    //$aim_entered_num = $vals[12+$aim_active]
    if($phase==0) $this->trial++; // tohle cislo pouzivam v ReadFile
  }
  /**
   * provede vsechno potrebne kolem nalezeni cile behem nacitani radek tracku; volano ze SaveTracke
   * @param char $klavesa
   * @param double $probe_end
   * @param bool $cilbyl
   * @param double $cas
   * @param array $aims
   * @param char $goaltype
   * 
   * @since 10.12.2012
   */
  function CilZpracuj($track,$phase,$trial,$klavesa,$probe_end,&$cilbyl,&$startbyl,$cas ,$aims,&$goaltype){
  	if( ($goaltype = $this->CilByl($klavesa,$probe_end,$cilbyl,$cas ,$aims,$goaltype,$this->lastavoid($track,$phase,$trial)))!=false){
      	
    	// pokud je cil e, a e muzu prepsat (<=cil se oznacuje pomoci g), nechci oznacovat ze byl cil
    	if(($goaltype=='e' ) && $this->CSpaNavCfg->GetGoalByEntrance($trial)==0){
      		$cilbyl = false; // v pripade prepisovatelneho cile typu e
      	} else {
      		$cilbyl = true; // i v pripade cile typu t
      	}
      	
      	// v probetrialu pri vyprseni casu chci mit dale aktivni start
      	if( !($probe_end!=false && $goaltype=='t') /* pokud cilem neni vyprseni casu probetrialu */ ) { //&& !(GOALBYENTRANCE==0 && $goaltype=='e')
      		$startbyl = false; // deaktivuje se start vzdy krome casu probetrialu 
      		//10.4.2012 - abych mohl kreslit a pocitat cas i po konci probetrialu.
      		//Predpokladam, ze ve SPANAV datech bude zmena aktivniho cile i po konci probetrilalu
      		// pri konci probetrialu se tedy vrati goaltype t a po zmene aktivniho cile se jeste jednou vrati goaltype e ale ten se uz neulozi 
      	}
      	
      	// pokud se cil oznacuje pomoci g a soucasny cil je g, vymazu predchozi pozici cile (treba e cil), abych mohl ulozit tu soucasnou
      	if($goaltype=='g' && $this->CSpaNavCfg->GetGoalByEntrance($trial)==0 && isset($trial)) {
      		// !! kdyz vyprsi 60s a pak teprv vstoupi do cile (a goalbyentrance je 1), uz se neulozi novy cil - u testu Schizofreniku je to asi dobre
      		unset($this->CSpaNavCfg->RF->sekv_goals[$track][$trial]); 
      		unset($this->goaltypearr[$track][$phase][$trial]);
      	}	 
        
        // pokud nebyl zadny cil ulozen v tomhle trialu; nebo byl smazan pomoci aktualne stlaceneho g
      	if( (isset($trial) && empty($this->goaltypearr[$track][$phase][$trial]))){ 
      		 //11.7.2012 - tento blok se vykonava, jen pokud jeste nebyl zadny goaltype v tomhle trialu
      		
	      	// ulozim pozici cile do sekv_goals
	        $this->CSpaNavCfg->GetRoomAims($track,$trial,$aims['lastvalid'] /*aimname*/); 
	      	if(ARENAFRAME) $this->CSpaNavCfg->GetArenaAim($track,$trial,
	      	     $aims['lastvalid'],//$this->CSpaNavCfg->GoalName($track,$trial,"RF"), // jmeno cile z RF
	      	     $this->arenaangle0,//$this->arenaangle0+$this->arenaRFangle, // rotace
	      	     $this->Center($track)); // pozice stredu
	      	
	      	// ulozim dalsi hodnoty
	      	$this->framearr[$track][$phase][$trial] = !$this->CSpaNavFrame->Attached_last()?"ROOM":"ARENA";
	        $this->CPointMove->LogSpeeds($this->filename,$trial);
	        $this->movedarr[$track][$phase][$trial]=$this->CPointMove->Moved();
      		$this->goaltypearr[$track][$phase][$trial] = $goaltype; // kamil 14.6.2010

      		// vyprseni casu probetrialu se bude zatim vykreslovat jako klavesa p
      		if($goaltype=='t') $this->uloz_klavesu(PROBETIMEKEY,$track,$phase,$trial); 
      		$this->avoidarr[$track][$phase][$trial][count($this->avoidarr[$track][$phase][$trial])-1] = 1; 
      			//pro drftrack.class signal, ze ted je cil
      			
      		if($goaltype=='g' && $this->CSpaNavCfg->GetGoalByEntrance($trial)==0) { // pokud ma byt cil pomoci g a je g
	      		$goaltype = false; // smazu ho, aby se mi priste nemazal sekv_goals a goaltypearr
	      	}	 
      	} 
      }
  }
  /**
   * vraci typ cile, nebo false, pokud prave ted neni cil;
   * vraci posledni typ cile v trialu: g-klavesa g, e-vstup do cile,t-cas v probetrialu;
   * Pokud to bylo t (nebo e pro GOALBYENTRANCE=0) - je mozne prepsat jinym typem
   * 
   * @param char $klavesa
   * @param double $probe_end
   * @param bool $cilbyl
   * @param double $cas
   * @param array $aims
   * @param char $goaltype
   * @return char/bool
   */
  function CilByl($klavesa,$probe_end,$cilbyl,$cas,$aims,$goaltype,$lastavoid){
  	foreach($aims as &$aim) {
  		if($aim=='-') $aim=''; // 16.3.2012 - '-' je prazdny cil
  	}
    if($cilbyl && !in_array($goaltype,$this->prepisovatelne_cile)) {
    	// kdyz cil byl, tak se jednoduse vraci goaltype (krome prepsatelnych cilu - t a pripade e)
        return $goaltype; // normalne to bude false, jen po konci probetrialu to bude t - 10.4.2012
        // 11.7.2012 - nebo po nepovolenem e trialu pomoci GOALBYENTRANCE=0
    } elseif($this->Cil_KeyToNext($klavesa)) {
        return 'g';
    } elseif($aims['current']!=$aims['last']  /*&& $aims['current']=='-' - to nemuze byt v aapp*/ 
        /*&& !empty($aims['current']) kamil 16.3.2012 - data ze schizofreniku*/ 
    	&& !empty($aims['last']) // pokud se zmeni aktivni cil, je taky cilbyl - asi ho nasel - 5.1.2010
    	//&& GOALBYENTRANCE // povolim nalezeni cile pomoci vstupu - 11.7.2012 - je povoleno vzdy ale pokud GOALBYENTRANCE==1 da se prepsat 
    	) {
        // 0 je aktualni cil, 1 je cil z minule radky
        //16.3.2012 - vstup u schizofreniku: - jedna radka Aim14, dalsi radka -
        return 'e';
    } elseif(GOALBYAVOID && $lastavoid>0){
    	return 'E';	///skutecny vstup do pozice cile - normalne se nepouzivat toto, ale zmena aktivniho cile v tracku
    } elseif($probe_end!==false && $probe_end <= $cas && $goaltype !='t'){
        return 't';
    } else {
      return $goaltype; // normalne to bude false, jen po konci probetrialu to bude t
    } 
  }
  /**
   * vraci true, pokud se ma ulozit radka tracku - byl start a nebyl cil
   * @param bool $startbyl
   * @param bool $cilbyl
   * @param char $goaltype
   * @param int $pausa
   * @return boolean
   */
  private function StartByl($startbyl,$cilbyl,$goaltype,$pausa){
  	if(!($startbyl || PLOTTOSTART)) {
  		// pokud nebyl start, cili pokud startbyl==false
  		return false; 
  		// po e a GOALBYENTRANCE==0 chci mit starbyl = false, abych mohl detekovat novy start a soucasne zapisoval radky a cislo trialu
  		//|| (in_array($goaltype, $this->prepisovatelne_cile)) && $this->CSpaNavCfg->GetGoalByEntrance($trial)==0 
  		// tohle asi nepotrebuju protoze po $goaltype=e a GOALBYENTRANCE=0 mam $cilbyl = false 10.12.2012 
  	}
  	if(!($cilbyl || PLOTAFTERCIL  || empty($goaltype))) {
  		// pokud nebyl cil, cili pokud cilbyl==false
  		return false; 
		//TODO 2012-12-10 overit funkcnost u probetrialu  		
  		//potrebuju tohle? 10.12.2012 || in_array($goaltype,$this->prepisovatelne_cile)
  	}
  	if($pausa!=0) return false;
  	return true;
  }
  function AimAreasNameSave($line) {
  	$vals = explode("\t",$line); //preg_split("/\s{8,13}/",trim($line));<br>
  	$aimsline = $vals[14];
  	$aims = explode("   ",trim($aimsline));
  	foreach($aims as $i=>$aim){
  		$this->aimareas["$aim"]=14+$i; // 12 a predtim 14 kvuli nadbytecnym dvema sloucpum v radku TIME
  	}
  	if(isset($vals[17]) && $vals[17]=='Eye3D.X'){
  	  $this->eyetracking = true;
  	}
  }

  /**
   * prepocitam souradnice bodu na min a max [-140;140]
   *
   * @param [x,y] $bod
   * @return [x,y]
   */
  function recalc_140($bod,$track){
    $bod=tocenter($bod,$this->Center($track));
    //$bod[1]=-$bod[1];
    return todiam($bod,$this->Radius($track),ARENAR);
  }

  function ArenaRealXMin($track){
    return $this->CSpaNavCfg->ArenaMinX($track);
  }
  function ArenaRealXMax($track){
    return $this->CSpaNavCfg->ArenaMaxX($track);
  }
  function ArenaRealYMin($track){
    return $this->CSpaNavCfg->ArenaMinY($track);
  }
  function ArenaRealYMax($track){
    return $this->CSpaNavCfg->ArenaMaxY($track);
  }
  function KeyToNext(){
    return $this->CSpaNavCfg->KeyToNext(); //HOME
  }
  function FramesToSec($frames){
    return $frames/$this->CSpaNavCfg->FramesToSec();
  }
  function BodSize(){
    return 5;
  }
  function AngleInfo(){
  	return true; // jsou tam nejake divne hodnoty
  }
  function ViewPointInfo(){
  	return false;
  }
  function TimeInfo(){
    return true;
  }
  /**
   * vrati souradnice stredu realne areny
   * overload puvodni tridy, protoze potrebuju parametr track
   *
   * @return CPoint
   */
  function Center($track){
    return new CPoint(
      $this->ArenaRealXMin($track)+($this->ArenaRealXMax($track)-$this->ArenaRealXMin($track))/2,
      $this->ArenaRealYMin($track)+($this->ArenaRealYMax($track)-$this->ArenaRealYMin($track))/2
      );
  }
   /**
   * vrati X polomer areny z ArenaRealXMax() a ArenaRealXMin()
   *
   * @return int
   */
  function Radius($track){
    return ($this->ArenaRealXMax($track)-$this->ArenaRealXMin($track))/2;
  }
   /**
   * vrati rozmery skutecne areny [x,y]
   *
   * @return [x,y]
   */
  function Diameter($track){
    return array(
      $this->ArenaRealXMax($track)-$this->ArenaRealXMin($track),
      $this->ArenaRealYMax($track)-$this->ArenaRealYMin($track)
     );
  }
  /*
    private function CheckCueVarArr($track,$cue){
    if(!isset($this->laservar[$track][0][$cue]))      $this->laservar[$track][0][$cue]=new CVarArr();
    if(!isset($this->segmentsvar[$track][0][$cue]))   $this->segmentsvar[$track][0][$cue]=new CVarArr();
    if(!isset($this->startPointvar[$track][0][$cue])) $this->startPointvar[$track][0][$cue]=new CVarArr();
    if(!isset($this->marknamevar[$track][0][$cue]))      $this->marknamevar[$track][0][$cue]=new CVarArr();
  }
  */
  
  function EyeTrackingInfo(){
    return $this->eyetracking;
  }
  /**
   * Ulozi udaje o mistu pohledu eyexyz; prepocitane na arenu140
   * @param int $track
   * @param int $phase
   * @param int $trial
   * @param array $vals radka tracku
   */
  function EyeTrackSave($track,$phase,$trial,$vals){
    $eyexyz = array($vals[32],$vals[33],$vals[34]);
    // zatim pracuju jen s xy tracku a prepocitam to na -140 az 140
    $eyexyz = $this->recalc_140(array($eyexyz[0],$eyexyz[1]),$track);
    $this->eyexyzarr[$track][$phase][$trial][]=array(
      $eyexyz[0],$eyexyz[1], $this->CSpaNavCfg->ZName((int)$vals[34]));

    // EXPORT
    // sloupce track, trial, time, frame, track x,y,z, eye x,y,z
    $this->exporttrack .="$track\t$trial\t$vals[0]\t$vals[1]\t$vals[2]\t$vals[3]\t$vals[4]\t$vals[32]\t$vals[33]\t$vals[34]";
  }

  /**
   * ulozi soubory _trackExp a _trackMark; pozice cloveka a mista pohledu + pozice znacek;
   * vola se z ReadFile po nacteni celeho souboru
   */
  function EyeTrackExport(){
    if($this->EyeTrackingInfo() && EYETRACKING){
      // exporty se pisou do adresare drf2track/exports
      $fh = fopen($this->filename_towrite("exports")."_trackExp.txt","w");
      fwrite($fh,$this->exporttrack);
      fclose($fh);

      // trackMark - sloupce souboru jsou track, trial, name, 
      if(isset($this->CSpaNavCfg->RF->marks))
        foreach($this->CSpaNavCfg->RF->marks as $track=>$trackdata)
        foreach($this->CSpaNavCfg->RF->marks[$track] as $trial=>$data)
        foreach($this->CSpaNavCfg->RF->marks[$track][$trial] as $name=>$pos){
        	if(empty($pos[2])) $pos[2]=0;
        	$this->exportmarks .="$track\t$trial\t$name\t$pos[0]\t$pos[1]\t$pos[2]\n";
        }
          
     
      $fh = fopen($this->filename_towrite("exports")."_trackMark.txt","w");
      fwrite($fh,$this->exportmarks);
      fclose($fh);
    }
  }
  
  /**
	 * vraci jmeno souboru vcerne cesty, ve kterem je cesta zamenena za $dir 
	 * pokud adresar neexistuje, vytvori ho
	 * 
	 * @param string $dir
	 * @return string
	 */
  private function filename_towrite($dir){
		if(!is_dir(dirname($this->filename)."/$dir")) mkdir(dirname($this->filename)."/".$dir);
		return dirname($this->filename)."/$dir/".basename($this->filename);
	}
	/**
	 * zmeni startbyl, osetruje kdyz se ma oddelit faze pri zastaveni;
	 * pokud neni STOPMAKEPHASE, faze je vzdy 0
	 * @param bool $startbyl
	 * @param int $lastphase
	 * @return int
	 */
	private function StopMakePhase(&$startbyl,$lastphase){
		if(STOPMAKEPHASE) 
		   $phase = ($this->CPointMove->Zastavil()?1:0);
	    else 
	       $phase = 0;
	    if(STOPMAKEPHASE && $this->CPointMove->Zastavil()){
	      if($lastphase == 0)   $startbyl = false;
	      $phase = 1;
	    }
	    return $phase;
	}
	/**
	 * jestli ma byt start podle toho, ze se zastavil
	 * @param int $phase
	 * @param int $lastphase
	 * @return boolean
	 */
	private function Start_StopMakePhase($phase, $lastphase){
		// trial s fazi 1 zacina i tam, kde se zastavil
		return (STOPMAKEPHASE && $phase==1 && $lastphase == 0);
	}
	/**
	 * vraci jestli ma byt start podle $klavesa;
	 * pokud $klavesa neni udana, vraci true pokud je klavesa definovana
	 * @param int $phase
	 * @param char $klavesa
	 * @return boolean
	 */
	private function Start_KeyToCues($phase,$klavesa=false){
		// phase je vzdy 0 u spanavu, krome pripadu STOPMAKEPHASE, kdy zastaveni deli faze
    // keytocues se bere z this->spanavcfg, ktere to bere z konstanty KEYTOSTART
     
		if($klavesa===false) // 
		  return !empty($this->keytocues[$phase]); 
		else 
		  return empty($this->keytocues[$phase]) || $klavesa==$this->keytocues[$phase];
	}
	private function Cil_KeyToNext($klavesa){
		if(!$this->KeyToNext() || empty($klavesa)) {
		  return false;
		} elseif(strpos($this->KeyToNext(),$klavesa)===false){ // muze byt definovano i vic ruznych klaves pomoci filelistu
			// napriklad CONST=KEYTONEXT|pf
			// 2.6.2010
			return false;
		} else {
			return true;
		}
	}
	/**
	 * normalizuje uhel pohledu z 0 doprava a 90 dolu (jak je poskytuje spanav)
	 * na format 0 doprava a 90 nahoru
	 * @param int $deg
	 * @return int
	 */
	private function AngleViewNormalize($deg){
		$deg = 360 - $deg;
		while ($deg>360) $deg -= 360;
		while ($deg<0) $deg += 360;
		return $deg;
	}
	/**
	 * vraci jmeno experimentu, ktere se bere nezavisle od ostatnich exp - jiny wholeimage aj
	 * @return string
	 */
	public function ExpName(){
		return $this->CSpaNavCfg->ExpName($this->filename);
	}
	/**
	 * vrati bod array, se souradnicemi int
	 * @param [x,y] $bod
	 * @return [x,y]
	 */
	private function int_point($bod){
		return array(intval($bod[0]),intval($bod[1]));
	}
	private function uloz_klavesu($klavesa,$track,$phase,$trial){
		$this->klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]=$klavesa;	
	}
	/**
	 * vrati posledni ulozeny avoid v poli avoidarr, zavadeno kvuli GOALBYAVOID
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @return mixed
	 * @since 14.10.2014
	 */
	private function lastavoid($track,$phase,$trial){
		$avoidarr = $this->avoidarr[$track][$phase][$trial];
		$avoid = end($avoidarr);
		return $avoid;
	}
	/**
	   * zkontroluje pocet trialu a doplni framearr, goaltypearr, pokud chybi cil na konci;
	   * zatim predpokladam, ze je jen jeden track a tudiz mi staci posledni radka=chybet trial muze jen v poslednim tracku
	   * @param $line posledni radka souboru
	   * @since 8.10.2012
	   */
	  protected function LastTrialCheck($line){
	  	foreach($this->framearr as $trackno=>$trackdata)
	  		foreach($trackdata as $phaseno=>$phasedata){
	  			if(count($phasedata) < count($this->roomxyarr[$trackno][$phaseno])){
	  				// 8.10.2012 - zadni druhy cile na konci posledniho trialu 
	  				trigger_error("neukonceny posledni trial: track $trackno, phase $phaseno",E_USER_WARNING);
	  				$lasttrial = count($this->roomxyarr[$trackno][$phaseno])-1;
	  				$this->framearr[$trackno][$phaseno][$lasttrial]=$this->framearr[$trackno][$phaseno][$lasttrial-1];
	  					//predpokladam, ze frame je stejny jako predchozi
	  				$this->goaltypearr[$trackno][$phaseno][$lasttrial]="X";
	  					// nedefinovany typ cile
	  				$vals = explode("\t",$line); // 9.10.2012 - musim pridat i posledni pozici cile
	  				$goalname= trim($vals[12]);
	  				$this->CSpaNavCfg->GetRoomAims($trackno,$lasttrial,$goalname /*aimname*/); 	
	  				
	  			}
	  			
	  		}
	  	
	  }

}
?>