<?php
define('MAXDISTOK',50);
define('FILTER',0);
define('FILTERMAX',8);
define('PROBETIMEKEY','PROBE');

//define('KEYTONEXT','g');
define('KEYCIL','f');

// premisteno do defineconst.php  10.4.2012
// define('PLOTTOSTART',0); // jestli se ma kresli i cast tracku nez bylo zmacknuto c
//define('PLOTAFTERCIL',0);


require_once('includes/point.php');
require_once 'classes/CKeySequence.class.php';
require_once 'classes/CTrackVarsFrame.php';
/**
 * funkce ktere se nutne musi prepsat v dedici
 * - ReadFile, nejlepe spolu s SaveSectorDef, SaveCueDef, SaveTrack
 * - asi take ArenaRealXMin, ArenaRealXMax, ArenaRealYMin, ArenaRealYMax
 * jestli se vse dobre naplni, tak to snad bude fungovat
 * Tato trida zpracovava BVA data
 */
class CTrackVars {
  public $track; // cislo tracku
  public $phase;
  public $trial;
  public $frame;
  public $pausa;
  public $avoid;
  public $klavesa;
  public $goalno; // kolikrat uz clovek vstoupil do aktualniho cile
  
  var $no; // cislo v techto hodnotach, ktere se vraci pomoci Next
  
  var $lasttrack; // cislo minuleho tracku - za ucelem vytvoreni obrazku pri objeveni se noveho tracku
  var $lasttrial;
  var $lastphase;
  var $lastframe; // minule aktivni frame - ARENA nebo ROOM
  var $lastavoid; // minule avoid - 25.7.2013
  
  var $trackstart;
  var $phasestart;
  var $trialstart;
  
  var $error = false; // 25.5.2012 - dotedka to tu nebylo?
  var $time;
  var $fc;
  var $line; // ktera line se priste vrati
  var $klavesaposition; // v kterem sloupci je klavesa
  var $names; // jmena jednotlivych sloupcu souboru
  var $intrack;
  
  
  var $validtrack; // track, ktery neni prazdny - z toho se urcuje last track
  var $odfiltrovano;
  
  var $sectors; // cisla sloupcu, kde jsou ulozen informace o vstupech do nich
  
  var $framearr; //[$track][$phase][$trial] - hodnoty jsou - ARENA nebo ROOM
  public $movedarr; //[$track][$phase][$trial] - cisla $no, kdy se clovek pohnul
  var $klavesyarr;// ulozene klavesy
  /**
   * typy cilu,jestli tim, ze dosel do cile, nebo klavesou 
   * - 'g' bude klavesa a 'e' bude enter, 't' bude, ze vyprsel cas
   * - X - cil chybi
   * @var char
   */
  var $goaltypearr; 
  /**
   * jestli je zrovna v sektoru; [$track][$phase][$trial][] 
   * @var int
   */
  var $avoidarr;  
  var $goalnoarr; // pocet vstupu do cile [$track][$phase][$trial]
  /**
   * souradnice eyelink; plni se ve zdedenych tridach z CTrackVars - kladne y je dolu
   * @var array[$track][$phase][$trial][]
   */
  protected $eyexyzarr; // souradnice eyelink / plni se ve zdedenych tridach
  protected $keyseq; // spravna sekvence klaves na kontrolu
  
  public $arenaangle0; // uhel arenaangle v tracku na zacatku tracku
  public $arenaRFangle; // realny uhel mezi bodem v AF a RF nez se arena zacne otacet
  
  public $keytocues; // klice 'c' po fazich - pole[phase]
  /**
   * klavesa ukazani na cil ze startu - stejna pro vsechny faze; 23.3.2012
   * @var char
   */
  public $keytopoint = ''; 
   
  // hodnoty puvodne z drf2track - vsechno typu CVarArr

  var  $envelope; // array[0-1][a,b,c] - dve obecne rovnice primek
  var  $filename;
  var  $timearr; // plni se jen pokud je TimeInfo() = true;
  
  // FRAME SPECIFIC
  // TRACK
  public $roomxy; // aktualni souradnice tracku - pro kazdy bod pri Next, kladne y je dolu
  public $arenaxy;
  public $eyexyz; // aktualni souradnice eyelink, kladne y je dolu
  
  /**
   * aktualni natoceni subjektu
   * @var deg
   */
  var $anglesubj;  
  /**
   * aktualni bod pohledu
   * @var unknown_type
   */
  var $viewxy; // 
  
  var $roomxylast;
  var $roomxyarr; // pole vsech roomxy
  var $anglearr; // natoceni subjektu
  var $viewxyarr; // bod pohledu subjektu
  var $roomxyavg; // zprumerovane pole vsech roomxt
  var $roomxyline;
  var $roomxymax, $roomxymin; // maximum a minimum souradnic zjistene ztracku
  
  var $arenaxyarr;
  var $arenaxyavg; // vraci se v Next, ale zatim se nikde neplni
  
  /**
   * jestli je v datech y zaporne nahore; v datech BVA je, v datech bvatest2D neni
   * @var boolean
   * @since 9.11.2012
   */
  private $inversey = true;
  
  //SECTOR 
  /*var  $cvar; // stredovy uhel mista - ve stupnich
  var  $cnamevar; // jmeno mista
  var  $r0var; // vzdalenost mista od stredu - procenta polomeru
  var  $r;     // - neni CVarArr, prumer mista - procenta polomeru
  var  $reltovar; // relativne k cemu 1-n, goal no = trial
  var  $cxyvar; // [x,y] souradnice cile
  var  $savoidvar; // jestli je sector avoid nebo preference*/
 
  // MARKS - CUES
  /**
   * @var deg 0-360
   */
/*  public  $laservar;  //  laser 0 - 360
  public  $startPointvar;  //  0-1 - 1= je to start
  public  $segmentsvar;  //  segments 0-3; 0 znamena, ze sviti nesviti zadny segment (jen start)
  public  $marknamevar; // jmena znacek, ktera se pak zobrazi v obrazku 25.8.2009*/
  
  /**
   * RoomFrame data - zatim cil
   * @var CTrackVarsFrame
   */
  public $RF;
  
  /**
   * ArenaFrame data - zatim cil
   * @var CTrackVarsFrame
   */
  public $AF;
  private $keyfoundpressed = false; // 12.11.2012 spolecne s this->avoidval
  /**
   * polomer znacky v procentech polomeru - 13.11.2012
   * @var float
   */
  public  $markradius=1;
  /**
   * konstruktor - zatim mi prijde ze stejny pro vsechny tridy
   *
   * @param unknown_type $filename
   * @return CTrackVars
   */
  function CTrackVars($filename){
    if(!file_exists($filename)){
			dp("  ERR:file not found: $filename!!!");
			$this->error = true;
	 	  return;
    } else {
      $this->filename=$filename;
      $this->line = 0;
      $this->track = -1;
      $this->intrack = false;
      $this->trackstart = false;
      $this->lasttrack = -1;
      $this->validtrack = -1;
      $this->odfiltrovano = 0;
      $this->counts=array();
      $this->keyseq = new CKeySequence();
      $this->RF = new CTrackVarsFrame("RoomFrame");
    
      if(ARENAFRAME) $this->AF = new CTrackVarsFrame("ArenaFrame");
      if($this->ReadFile()){
	      $this->Median();
	      $this->Average();
	      $this->AverageDist();
	      $this->LineFit();
	      $this->error = false; // 1.6.2012 - chyba se muze vyskytnout, pokud jen nedokoncena posledni radka
	      	// dulezite je, jstli readfile vrati true - pak pokracovat 
	      //$this->SaveMoved(); //TOxDO SaveMoved nefunguje
      }
    }
  }
  /**
   * nacte data do poli:
   * ve funkci SaveSectorDef:
   *        cvar[$track][$phase],
   *        cnamevar[$track][$phase],
   *        r0var[$track][$phase]
   *        cxyvar[$track][$phase],
   *        reltovar[$track][$phase],
   *        savoidvar[$track][$phase],
   *        r[$track][$phase]
   *        keytocues[$phase]
   *
   * ve funkci SaveCueDef:
   *        laservar[$track][$phase][$cue],
   *        segmentsvar[$track][$phase][$cue],
   *        startPointvar[$track][$phase][$cue]
   * ve funkci SaveTrack:
   *        counts[$track][$phase]
   *        roomxyarr[$track][$phase][$trial]
   *        framearr[$track][$phase][$trial]
   *        goalnoarr[$track][$phase][$trial]
   *        klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]
   *        avoidarr[$track][$phase][$trial][]
   *
   */
  function ReadFile(){
    $this->fc = file($this->filename); // dam to sem, protoze to je specificke pro tenhle typ souboru
    $track = -1; // soucasne cislo tracku
    $intrack = false;
    $insectordef = false;
    $incuedef = false;
    foreach ($this->fc as $lineno=> $line) {
      $this->line = $lineno;
      if(substr($line,0,5)=='*****'){
        $intrack = false;
        $insectordef= false;
        $incuedef = false;
        $track++;
      } elseif(substr($line,0,5)=='frame'){
        $intrack = true;
        $insectordef = false;
        $incuedef = false;
        //unset($lastphase);
        $klavesalast='';// jaka byla klavesa stlacena v prechozi radce
        $this->names = $this->splitline($line,2,11,true); //preg_split("/\s{2,13}/",trim($line));
        $this->klavesaposition = array_search('klavesa',$this->names);// na jake pozici v poli hodnot tracku je stlacena klavesa
        $this->sectors = arr_series(array_search('sector',$this->names)+1,$this->klavesaposition-1);
      } elseif($this->sectordef($line)){
         // definice sektoru
        $insectordef = true;
        $intrack = false;
        $incuedef = false;
      } elseif($this->cuedef($line)){
        // definice cues
        $incuedef = true;
        $insectordef = false;
        $intrack = false;
      } elseif($insectordef){
        $this->SaveSectorDef($line,$track);
      } elseif ($incuedef){
        $this->SaveCueDef($line,$track);
      } elseif($intrack) {
        $this->SaveTrack($line,$track,$lineno);
      }
    }
    $this->no = -1;// priste se zacne cist od zacatku
    $this->track= 0;
    $this->phase = 0;
    $this->trial = 0;
    $this->trackstart = true;
    $this->trialstart = true;
    $this->phasestart = true;
    if(!isset($this->roomxyarr) || !is_array($this->roomxyarr) || count($this->roomxyarr)==0){
    	$this->error = true;
    	return false;
		} else {
			$this->error=false;
			return true;
		}

  }
  /**
   * nacita data definovanych sektoru - jednu radku, volano z ReadFile
   * fazi si nacte s te radky, track dostane v parametrech
   *
   * @param str $line
   * @param int $track
   * @param string $frame RF=roomframe, AF = arenaframe
   * 
   */
  function SaveSectorDef($line,$track,$frame="RF"){
    $vals = $this->splitline($line, 9,12); //preg_split("/\s{9,13}/",trim($line));
    if($vals != false){
	    $phase = $vals[0];
	    $this->keytocues[$phase]=$vals[12];
	    $keyfound = (KEYFOUND!="") ? KEYFOUND : /*keyfound*/$vals[15]; //17.9.2014 - zkousim kvuli darkevel definovat g jako klavesu pro oznaceni cile
	    $this->RF->AddGoal_Angle($track,$phase,$vals[10],$vals[6],$vals[5],isset($vals[17])?$vals[17]:"",$vals[3],
	    	/*relto*/$vals[11],$keyfound,$vals[16]);
    }

/*  PRESUNUTO DO CTrackVarsFrame
    if(!isset($this->cvar[$track][$phase]))   $this->cvar[$track][$phase]=new CVarArr();
    if(!isset($this->cnamevar[$track][$phase]))   $this->cnamevar[$track][$phase]=new CVarArr();
    if(!isset($this->r0var[$track][$phase]))  $this->r0var[$track][$phase]=new CVarArr();
    if(!isset($this->cxyvar[$track][$phase])) $this->cxyvar[$track][$phase]=new CVarArr();
    if(!isset($this->reltovar[$track][$phase])) $this->reltovar[$track][$phase]=new CVarArr();
    if(!isset($this->savoidvar[$track][$phase])) $this->savoidvar[$track][$phase]=new CVarArr();

    $this->cvar[$track][$phase]->add($vals[10]);
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
   * nacita data definovanych cues, volano z ReadFile
   * fazi si nacte s te radky, track dostane v parametrech
   *
   * @param str $line
   * @param int $track
   */
  function SaveCueDef($line,$track){
    if( ($vals = $this->splitline($line, 9,6))!=false){ //preg_split("/\s{9,13}/",trim($line));
	    $phase = $vals[0];
	    $cue = $vals[1];
	     /*if(!isset($this->laservar[$track][$phase][$cue]))        $this->laservar[$track][$phase][$cue]=new CVarArr();
	     if(!isset($this->segmentsvar[$track][$phase][$cue]))     $this->segmentsvar[$track][$phase][$cue]=new CVarArr();
	     if(!isset($this->startPointvar[$track][$phase][$cue]))   $this->startPointvar[$track][$phase][$cue]=new CVarArr();
	     if(!isset($this->marknamevar[$track][$phase][$cue]))   $this->marknamevar[$track][$phase][$cue]=new CVarArr();*/
	     $laser = $vals[3];
	     if($laser>=0 && $laser<=8){ // taky muze byt omylem laser 0 - to nechci
	       // laser 0 znamena, ze nechci zadny zobrazit
	       $this->RF->AddCue($track,$phase,$cue,$this->laserc($laser),$vals[5],$vals[4],'L'.$vals[3],false);
	       /*$this->laservar[$track][$phase][$cue]->add($this->laserc($vals[3]));
	       $this->segmentsvar[$track][$phase][$cue]->add($vals[5]);
	       $this->startPointvar[$track][$phase][$cue]->add($vals[4]);
	       $this->marknamevar[$track][$phase][$cue]->add('L'.$vals[3]); // 24.5.2010*/
	     }
    }
  }
  /**
   * nacita data tracku, volano z ReadFile
   * lineno je tu jen kvuli vypisu
   *
   * @param str $line
   * @param int $track
   * @param int $lineno
   */
  function SaveTrack($line,$track,$lineno) {
    static $lastphase=-1,$klavesalast=false,$startbyl=true,$cilbyl=false,$phaserepeatdiff=array();
    if(empty($this->counts[$track][0])) { // protoze kdyz delam druhy soubor v poradi, nejak mi ty defaultni hodnoty static nefunguji
      // kamil 26.8.2008
      // this counts obsahuje pocty tracku - takze pokud nezacala jeste faze 0
      $lastphase = -1;
      $startbyl = true; // aby se se vykonala cast, ze zacina dalsi faze
      $cilbyl = false;
      $phaserepeatdiff = array(); // kamil 27.7.2010 - rozdil mezi trialem a phaserepeat - kazda zmena znamena vaznou chybu - jak vznika?
    }
    if( ($vals =$this->splitline($line,7,11))!=false){ // kdyz jsou zaporne souradnice, maj pred sebou mezeru 7
      $phase = $vals[6];
      $pausa = $vals[7];
      $frameno = $vals[0];
      $klavesa = $vals[$this->klavesaposition];
      $phaserepeat = $vals[$this->klavesaposition+1];
      $roomxy = $this->inversey ? array( (float) $vals[1], (float) $vals[2])  : array( (float) $vals[1], - (float) $vals[2]); 
      if(!empty($this->keytocues[$phase]) && $klavesa==$this->keytocues[$phase] ){
        $startbyl = true; //
      }

      if( $startbyl // uz byl start - take na zacatku kazdeho tracku
          && ($lastphase<0 || $lastphase != $phase // zacina faze
            || $klavesalast ==$this->KeyToNext() // bylo g
            || empty($this->counts[$track][$phase])) // zacina faze
       ) {
        // proc tam ma byt to empty(..) nevim, ale jinak se mi stavalo, ze $lastphase bylo na zacatku 0 a takze pak niz byl $trial -1
        // ZACINA DALSI FAZE
        $lastphase = $phase;
        if(empty($this->counts[$track][$phase]))
          $this->counts[$track][$phase]=1;
        else
          $this->counts[$track][$phase]++; // dalsi trial
        if(PLOTTOSTART || empty($this->keytocues[$phase])){
          $startbyl = true;
        } else {
          $startbyl = false;
        }
        $cilbyl = false;
      } // zacina dalsi faze konec

      // PRO KAZDY BOD mezi startem a cilem
      $trial = $this->counts[$track][$phase]-1;
      if($trial<0 && $startbyl){
        trigger_error("trial $trial v $track/$phase, lastphase:$lastphase, counts[track][phase]:".$this->counts[$track][$phase],E_USER_ERROR);
      }
      if($startbyl && !$pausa && (!$cilbyl || PLOTAFTERCIL)) {
        if(!isset($this->roomxyarr[$track][$phase][$trial])) {
          // tisk az po tom, co je start - protoze on taky nemusi byt start v nekterem trialu
          //dp($lineno,"track $track, phase $phase, trial $trial, frame ".$vals[0]);
          echo "line: $lineno, track $track, phase $phase, trial $trial, frame ".$vals[0]."\r";
          if(!isset($phaserepeatdiff[$phase])) $phaserepeatdiff[$phase]=0;
          if($trial - $phaserepeat <> $phaserepeatdiff[$phase]){
          	dp($phaserepeat,"ERR: rozhozene phaserepeat (track $track, phase $phase, trial $trial)");
          	$phaserepeatdiff[$phase] = $trial-$phaserepeat;
          	// kdyz vice ruznych fazi, phaserepeat se pocita pro kazdou zvlast
          }
        }
        $this->roomxyarr[$track][$phase][$trial][] = $roomxy; // ukladam cisla s Y kladnym dolu - tak je to tradicne v BVA datech
        if(ARENAFRAME) {
        	$arenaxy = $this->inversey ? array( (float) $vals[4], (float) $vals[5]) : array( (float) $vals[4], -(float) $vals[5]);
        	$this->arenaxyarr[$track][$phase][$trial][] = $arenaxy;
        }
        //$this->phase = $vals[6];
        $this->framearr[$track][$phase][$trial] = ($vals[8]==0) ? "ROOM" : "ARENA";
        //$this->pausa = $vals[7];
        $this->goalnoarr[$track][$phase][$trial] = $vals[$this->klavesaposition+2];
        if(!empty($klavesa)){
          $this->klavesyarr[$track][$phase][$trial][count($this->roomxyarr[$track][$phase][$trial])-1]=$klavesa;
        }
        // **************************** 12.11.2012:
        $this->avoidarr[$track][$phase][$trial][] = $this->avoidval($vals,$track,$phase,$klavesa);
        // *********************
        if($this->TimeInfo()){
          $this->timearr[$track][$phase][$trial][]=$this->FramesToSec($frameno); // cas v sekundach
        }
      }
      // VSTUP DO CILE
      if( ($klavesa == KEYCIL || $klavesa == KEYTONEXT) && $startbyl /* oprava 27.3.2009 - nemuze byt cil pred startem */){
        $cilbyl = true;
        $this->goaltypearr[$track][$phase][$trial]='g'; // tady neni jina moznost?
        $this->RF->keyfoundbeepstopvar[$track][$phase]->next();
        $this->RF->keyfoundvar[$track][$phase]->next();
        $this->keyfoundpressed = false; // jeste nebyla zmacknuta klavesa f oznaceni cile 
      }
      $klavesalast = $klavesa;
      $this->CheckKeySeq($klavesa,$track,$phase,$trial);
      
    } else {
      if(strlen($line)>0) dp(($this->line-1),"radka v tracku chybna! ".trim($line));
    }
  }
  function GetRoomXY($track,$phase,$trial,$no){
    return $this->roomxyarr[$track][$phase][$trial][$no];
  }
  function GetArenaXY($track,$phase,$trial,$no){
    if(ARENAFRAME) return $this->arenaxyarr[$track][$phase][$trial][$no];
    else return false;
  }
  function GetKlavesa($track,$phase,$trial,$no){
    if(isset($this->klavesyarr[$track][$phase][$trial][$no])){
      return $this->klavesyarr[$track][$phase][$trial][$no];
    } else {
      return false;
    }
  }
  function GetFrame($track,$phase,$trial){
    return $this->framearr[$track][$phase][$trial];
  }
  function GetGoalno($track,$phase,$trial){
    $this->goalnoarr[$track][$phase][$trial];
  }
  /**
   * posune index v hodnotach $this->no na dalsi hodnotu
   * meni $this - track, lasttrack,phase,trial, no, phasestart, trialstart,  trackstart
   *
   * @return bool
   */
  function NextNo(){
    if($this->no==-1){
      $this->trackstart = $this->trialstart = $this->phasestart = true; // uplny zacatek souboru
      $this->lasttrack = $this->lastphase = $this->lasttrial = $this->lastavoid = 0;
      $this->lastframe = "";
      while(!isset($this->roomxyarr[$this->track]) && $this->track <max(array_keys($this->roomxyarr))){
        // PRESUN NA PRVNI EXISTUJICI TRACK
        $this->track++;  // kdyz bude track 0 prazdny (nedefinovany), tak se tahle posunu na dalsi track 6.2.2009
      }
    } else {
      $this->trackstart = $this->trialstart = $this->phasestart = false;
    }
    $this->no++;
    if(isset($this->roomxyarr[$this->track][$this->phase][$this->trial])
      && $this->no>= count($this->roomxyarr[$this->track][$this->phase][$this->trial])){
      // JSEM NAKONCI TRIALU - MUSIM SE POSUNOUT NA DALSI FAZI
      $this->lasttrial = $this->trial;
      $this->lastphase = $this->phase;
      $this->lasttrack = $this->track;
      $this->lastframe = $this->frame;
      
      $this->phase++; $this->no = 0; // posunuju fazi, protoze za sebou v case jdou vsechny faze a pak je teprv dalsi trial
      $this->trialstart = true;
    }
    if(isset($this->roomxyarr[$this->track])
      && $this->phase>= count($this->roomxyarr[$this->track])) {
      // JSEM NA KONCI RADY FAZI - MUSI NASTOUPIT ZASE PRVNI FAZE A DALSI TRIAL
      $this->lasttrial = $this->trial;
      //$this->lastphase = $this->phase;
      $this->lasttrack = $this->track;
      do {
          $this->trial++; $this->no = 0; $this->phase=0;
          while (!isset($this->roomxyarr[$this->track][$this->phase][$this->trial])
            && $this->phase<count($this->roomxyarr[$this->track])){
            $this->phase++; // trial muze byt definovan jen v jedne fazi
          }
          if($this->phase>=count($this->roomxyarr[$this->track])) $this->phase = 0; // kvuli nasledujici podmince
      } while (!isset($this->roomxyarr[$this->track][$this->phase][$this->trial]) && $this->trial<count($this->roomxyarr[$this->track][$this->phase])); // muze se stat, ze jeden trial bude vynechanej, pokud nebylo zmacknuto c
      $this->phasestart = true;
      $this->trialstart = true;
    }
    if(isset($this->roomxyarr[$this->track][$this->phase])
      && $this->trial>= count($this->roomxyarr[$this->track][$this->phase])){
      // JSEM NA POSLEDNIM TRIALU V POSLEDNI FAZI - MUSIM SE PRESUNOUT NA DALSI TRACK
      //$this->lasttrial = $this->trial;
      $this->lastphase = $this->phase;
      $this->lasttrack = $this->track;
      do {
          $this->track++;
      } while (!isset($this->roomxyarr[$this->track]) && $this->track<max(array_keys($this->roomxyarr))); // muze se stat, ze jeden track bude vynechanej
      $this->phase = 0; $this->trial = 0; $this->no = 0;
      $this->trackstart = true;
      $this->phasestart = true;
      $this->trialstart = true;
    }
    if($this->track>max(array_keys($this->roomxyarr))){
      // UZ ZADNEJ DALSI TRACK NENI = KONEC SOUBORU
      return false;
    } else {
      return true;
    }
  }
  /**
   * nastavuje dalsi hodnoty tracku k preceni
   * nastavuje $this roomxy,arenaxy,frame,goalno,klavesa,avoid, pausa
   * drf2track class pouziva odsud:
   *    roomxy, arenaxy, frame, goalno, klavesa,avoid,pausa,
   *    track, phase, trial, no, lasttrack, lastphase - nastaveny v NextNo
   *    cvar, cnamevar, r0var, counts , startPointvar, savoidvar, r,laservar, segmentsvar,reltovar- ReadFile
   *    , line - se sice pouziva ve vypisu v Image, ale nikde se asi nenastavuje :-)
   *
   * @param bool $first
   * @return bool
   */
  function Next($first = true){ // first = false - volano podruhe, protoze predchozi bod byl blbe
    if($this->NextNo()){
       // pokud jsou ulozene prumery, vratim spis ty
       $this->roomxy = $this->RoomXY($this->track,$this->phase,$this->trial,$this->no);
  
       if(isset($this->anglearr[$this->track][$this->phase][$this->trial][$this->no])){
       	 $this->anglesubj = $this->anglearr[$this->track][$this->phase][$this->trial][$this->no];
       }
       if(isset($this->viewxyarr[$this->track][$this->phase][$this->trial][$this->no])){
       	 $this->viewxy = $this->viewxyarr[$this->track][$this->phase][$this->trial][$this->no];
       }
       if(isset($this->eyexyzarr[$this->track][$this->phase][$this->trial][$this->no])){
         $this->eyexyz = $this->eyexyzarr[$this->track][$this->phase][$this->trial][$this->no];
       }
       if(ARENAFRAME) {
       	 $this->arenaxy = $this->RoomXY($this->track,$this->phase,$this->trial,$this->no,"arenaxy"); 
         /*if(isset($this->arenaxyavg[$this->track][$this->phase][$this->trial]['averages'][$this->no])){
           $this->arenaxy = $this->arenaxyavg[$this->track][$this->phase][$this->trial]['averages'][$this->no];
         } else {
           $this->arenaxy = $this->arenaxyarr[$this->track][$this->phase][$this->trial][$this->no];
         }*/
       }
       $this->frame = $this->framearr[$this->track][$this->phase][$this->trial];
       if($this->TimeInfo()) $this->time = $this->timearr[$this->track][$this->phase][$this->trial][$this->no];
       $this->goalno = $this->goalnoarr[$this->track][$this->phase][$this->trial];
       if(isset($this->klavesyarr[$this->track][$this->phase][$this->trial][$this->no])){
        $this->klavesa = $this->klavesyarr[$this->track][$this->phase][$this->trial][$this->no];
       } else {
         $this->klavesa = "";
       }
       $this->lastavoid = $this->avoid; //25.7.2013
       $this->avoid = $this->avoidarr[$this->track][$this->phase][$this->trial][$this->no];
       $this->pausa = 0; // useky s pauzou jsem vyhodil uz driv;
       return true;
    } else {
      return false;
    }
  }
  /**
   * EXPORT do drf2track.class
   *
   * @return unknown
   */
  function Trackstart(){
     return $this->trackstart;
  }
  function PhaseStart(){
    return $this->phasestart;
  }
  /**
   * EXPORT do drf2track.class
   *
   * @return unknown
   */
  function TrialStart(){
    return $this->trialstart;
  }
  /**
   * vraci true, pokud na teto radce clovek ukazuje do cile; 23.3.2012
   * @return boolean
   */
  function PointingToGoal(){
  	return (!empty($this->keytopoint) && $this->klavesa==$this->keytopoint);
  }
  /**
   * prepocita cue 1-8 na uhel 0 - 360
   *
   * @param int $laser
   * @return double
   */
  function laserc($laser){
    if($laser <= 0) return -1;
    $laser--;
    $c = 360-($laser/8 * 360);
    return $c;
  }
  /**
   * plni pole $this->roomxyavg
   *
   */
  function Average() {
    if(AVERAGEPOINTS>0 && AVERAGEDIST <=0) { // nebudu delat average i averagedist soucasne
     dp("averaging room frame: ".AVERAGEPOINTS);
     $this->averagearr("roomxyarr","roomxyavg");
     /*foreach($this->roomxyarr as $track=>$trackvals) {
       foreach($trackvals as $phase=>$phasevals) {
         foreach ($phasevals as $trial=>$trialvals) {
           $maxno = count($trialvals)-1; // maximalni cislo bodu
         	 foreach ($trialvals as $no => $xy) {
         	      $points = min(AVERAGEPOINTS,2*($maxno-$no)+1,2*($no-0)+1); // kolik bodu chci prumerovat - u kraje to bude min, abych si krajni bod neposunoval 12.9.2008
         	      $min = max(0,$no-$points);
                $hodnoty = array_slice($trialvals,$min,$points);
                $gravity = gravity($hodnoty);
                $this->roomxyavg[$track][$phase][$trial][$no]=$gravity;
         	 }
         }
       }
     }*/
     if(ARENAFRAME) {
       dp("averaging arena frame: ".AVERAGEPOINTS);
       $this->averagearr("arenaxyarr","arenaxyavg");
     }
    }
  }
  /**
   * zpracuje average pro jedno pole
   * @param string $vstup jmeno vstupniho pole
   * @param string $vystup jmeno vystupniho pole
   */
  private function averagearr($vstup,$vystup){
    foreach($this->$vstup as $track=>$trackvals) {
         foreach($trackvals as $phase=>$phasevals) {
           foreach ($phasevals as $trial=>$trialvals) {
             $maxno = count($trialvals)-1; // maximalni cislo bodu
             foreach ($trialvals as $no => $xy) {
                $points = min(AVERAGEPOINTS,2*($maxno-$no)+1,2*($no-0)+1); // kolik bodu chci prumerovat - u kraje to bude min, abych si krajni bod neposunoval 12.9.2008
                $min = max(0,$no-$points);
                $hodnoty = array_slice($trialvals,$min,$points);
                $gravity = gravity($hodnoty);
                $this->{$vystup}[$track][$phase][$trial][$no]=$gravity;
             }
           }
         }
      }
  }
  /**
   * zpracuje median pro jedno pole
   * @param string $vstup jmeno vstupniho pole
   * @param string $vystup jmeno vystupniho pole
   */
  private function medianarr($vstup,$vystup){
    foreach($this->$vstup as $track=>$trackvals) {
       foreach($trackvals as $phase=>$phasevals) {
         foreach ($phasevals as $trial=>$trialvals) {
          // jeden trial:
           $maxno = count($trialvals)-1; // maximalni cislo bodu
           foreach ($trialvals as $no => $xy) {
                $points = min(MEDIANPOINTS,2*($maxno-$no)+1,2*($no-0)+1); // kolik bodu chci prumerovat - u kraje to bude min, abych si krajni bod neposunoval 12.9.2008
                 $min = max(0,$no-$points);
               $hodnoty = array_slice($trialvals,$min,MEDIANPOINTS);
                $median = median_points($hodnoty);
                $this->{$vystup}[$track][$phase][$trial][$no]=$median;
           }
         }
       }
     }
  }
  /**
   * plni pole $this->roomxyline na zaklade roomxyavg
   *
   */
  function LineFit(){
  if(LINEPOINTS>0) {
     dp("fitting line in room frame: ".LINEPOINTS);
     if(!isset($this->roomxyavg))
        $this->roomxyavg = $this->roomxyarr;
     foreach($this->roomxyavg as $track=>$trackvals) {
       foreach($trackvals as $phase=>$phasevals) {
         foreach ($phasevals as $trial=>$trialvals) {
           $maxno = count($trialvals)-1; // maximalni cislo bodu
         	 foreach ($trialvals as $no => $xy) {
         	      //$min = max(0,$no-LINEPOINTS);
         	      //$length = min(count($trialvals)-$min-1,LINEPOINTS+$no-$min+1);
                //$hodnoty = array_slice($trialvals,$min,$length);

                $points = min(LINEPOINTS,2*($maxno-$no)+1,2*($no-0)+1); // kolik bodu chci prumerovat - u kraje to bude min, abych si krajni bod neposunoval 12.9.2008
         	      $min = max(0,$no-$points);
                $hodnoty = array_slice($trialvals,$min,$points);
                $fit = least_squares($hodnoty);
                $novybod = primka($fit['a'],$fit['b'],array(0=>$xy));
                $this->roomxyline[$track][$phase][$trial][$no]=array($xy[0],$novybod[0][1]);
         	 }
         }
       }
     }
    }
  }
  /**
   * prepisuje puvodni body v $this->roomxyarr a arenaxyarr
   *
   */
  function Median(){
    if(MEDIANPOINTS>0) {
     dp("counting median in room frame: ".MEDIANPOINTS);
     $this->medianarr("roomxyarr","roomxyavg");
     if(ARENAFRAME){
	     	dp("counting median in arena frame: ".MEDIANPOINTS);
	     $this->medianarr("arenaxyarr","arenaxyavg");
     }
    }
  }

  /**
  * pocita prumer z AVERAGEPOINTS bodu okolo, ktere jsou ale soucasne vzdaleny min nez AVERAGEDIST
  * plni pole $this->roomxyavg
  *
  */
  function AverageDist() {
    if(AVERAGEPOINTS>0 && AVERAGEDIST > 0) {
     dp("dist-averaging room frame: ".AVERAGEPOINTS."-".AVERAGEDIST);
     foreach($this->roomxyarr as $track=>$trackvals) {
       foreach($trackvals as $phase=>$phasevals) {
         foreach ($phasevals as $trial=>$trialvals) {
           $maxno = count($trialvals)-1; // maximalni cislo bodu
         	 foreach ($trialvals as $no => $xy) {
         	      $points = min(AVERAGEPOINTS,$maxno-$no,$no-0); // kolik bodu chci prumerovat - u kraje to bude min, abych si krajni bod neposunoval 12.9.2008
                $hodnoty = array_slice($trialvals,$no-$points,$points*2+1);
                foreach($hodnoty as $h=>$bod){
                  if(distance($bod,$xy)>AVERAGEDIST){
                    unset($hodnoty[$h]);
                  }
                }
                $gravity = gravity($hodnoty);
                $this->roomxyavg[$track][$phase][$trial][$no]=$gravity;
         	 }
         }
       }
     }
    }
  }
  /**
   * pocita dve rovnobezne primky se soucasnym smerem, a pokud bod je mimo, updatuje je
   * potrebuju - funkci na vypocet dvou primek, funkci na zjisteni, jestli je bod mimo ne.
   *
   *
   */
  function Envelope(){
    if(ENVELOPESIZE>0){
       dp("fitting line in room frame: ".LINEPOINTS);
       if(!isset($this->roomxyavg))
          $this->roomxyavg = $this->roomxyarr;
       foreach($this->roomxyavg as $track=>$trackvals) {
         foreach($trackvals as $phase=>$phasevals) {
           foreach ($phasevals as $trial=>$trialvals) {
             $rel_to = $this->reltovar[$track][$phase]->next();
              if($rel_to==0 || ($rel_to>0 && $rel_to==$phase+1)){
                // jsem na startu, cili musim udelat evelope vzhledem ke stredu
                 $this->envelope=primka2rovnobezne(primka2body($trialvals[0],array(0,0)),ENVELOPESIZE);
              }
           }
         }
       }
    }
  }
    /**
   * vrati rozmery skutecne areny [x,y]
   *
   * @return [x,y]
   */
  function Diameter(){
    return array($this->ArenaRealXMax()-$this->ArenaRealXMin(),$this->ArenaRealYMax()-$this->ArenaRealYMin());
  }
  /**
   * vrati X polomer areny z ArenaRealXMax() a ArenaRealXMin()
   *
   * @return int
   */
  function Radius(){
    return ($this->ArenaRealXMax()-$this->ArenaRealXMin())/2;
  }
  /**
   * vrati souradnice stredu realne areny
   *
   * @return [x,y]
   */
  function Center(){
    return array($this->ArenaRealXMin()+($this->ArenaRealXMax()-$this->ArenaRealXMin())/2,
      $this->ArenaRealYMin()+($this->ArenaRealYMax()-$this->ArenaRealYMin())/2
      );
  }
  function RealXYMin(){
    return array($this->ArenaRealXMin(),$this->ArenaRealYMin());
  }
  function RealXYMax(){
    return array($this->ArenaRealXMax(),$this->ArenaRealYMax());
  }
  function ArenaRealXMin(){
    return -ARENAR; //-140;
  }
  function ArenaRealXMax(){
    return ARENAR; // 140;
  }
  function ArenaRealYMin(){
    return -ARENAR; //-140;
  }
  function ArenaRealYMax(){
    return ARENAR; //140;
  }
  function KeyToNext(){
    return "g";
  }
  /**
   * prevadi framy na vteriny
   *
   * @param int $frames
   * @return float
   */
  public function FramesToSec($frames){
    return $frames/25;
  }
  public function BodSize(){
    return 2;
  }
  public function AngleInfo(){
  	return false;
  }
  /**
   * jestli soubor poskytuje bod pohledu; ted vzdy false SpaNav i BVA
   * @return boolean
   */
  public function ViewPointInfo(){
  	return false;
  }
  /**
   * jestli soubor poskutuje primy casovy udaj
   *
   * @return bool
   */
  public function TimeInfo(){
    return true; // zkousim to od 28.8.2012 i pro BVA data - prepocitam cislo framu na vteriny
  }
  /**
   * vraci cas aktualniho trialu v sekundach; bud podle skutecneho casu v datech nebo podle prepoctu z framu
   * @return float
   * @since 23.11.2012
   */
  public function TimeInTrial(){
  	if($this->TimeInfo()){
  		$timestart = reset($this->timearr[$this->track][$this->track][$this->trial]); // cas zacatku trialu
  		return $this->time-$timestart;
  	} else {
  		return $this->FramesToSec($this->no);
  	}
  }
  /**
   * true, pokud typ dat umi eyetracking; dedi se z CTrackVars
   * @return boolean
   */
  public function EyeTrackingInfo(){
    return false;
  }
/**
   * vraci jmeno experimentu, ktere se bere nezavisle od ostatnich exp - jiny wholeimage aj
   * @return string
   */
  public function ExpName(){
    return strtolower(substr($this->filename,strrpos($this->filename,'.')+1)); // pripona souboru , tr1, tr2 aj
  }
  
  public function CheckKeySeq($klavesa,$track,$phase,$trial){
    if(!$this->keyseq->AddKey($klavesa)){
    	 if($klavesa == $this->KeyToNext()) // KRITICKA CHYBA SPATNE UMISTENE G
          dp($klavesa,"ERR: klavesa g porusujici sekvenci v track $track - phase $phase - trial $trial");
    }
  }
  
  /**
   * vraci souradnice v roomxy nebo jine promenne, ktera ma pole
   * ..line ..avg nebo ..arr
   * 
   * @param int $track
   * @param int $phase
   * @param int $trial
   * @param int $no
   * @param string $varname
   * @return [x,y]
   */
  private function RoomXY($track,$phase,$trial,$no,$varname='roomxy'){
  	  $var_line = $varname."line";
  	  $var_avg =  $varname."avg";
  	  $var_arr =  $varname."arr";
  	  // primo na prvek pole se pomoci variable name asi dotazovat nemuzu,
  	  // hazi to chybu Cannot use string offset as an array
      if(isset($this->$var_line)){
         $varnameP = &$this->$var_line;  
      } elseif(isset($this->$var_avg)){
         $varnameP = &$this->$var_avg;
      } else {
      	 $varnameP = &$this->$var_arr;
         
      }
      if(!isset($varnameP[$track][$phase][$trial][$no])){
      	$xy = array(0,0); // TOxDO rhodosExpPCP.txt  nektery z indexu neni definovany
      } else {
      	$xy = $varnameP[$track][$phase][$trial][$no];
      }
      return $xy;
  }
  
  /**
   * ulozi do obou framu souradnice bodu, kde se clovek zastavil
   */
  protected function SaveMoved(){
  	if(!empty($this->movedarr) && is_array($this->movedarr)){
  		foreach($this->movedarr as $track=>$trackdata){
  			foreach($trackdata as $phase=>$phasedata){
  				foreach($phasedata as $trial=>$no){
  					$this->RF->movedxyarr[$track][$phase][$trial] = $this->RoomXY($track,$phase,$trial,$no);
  					if(ARENAFRAME)
  					 $this->AF->movedxyarr[$track][$phase][$trial] = $this->RoomXY($track,$phase,$trial,$no,"arenaxy");
  				}
  			}
  		}
  	}
  }
  /**
   * rozdeli radku dat na jednotlive hodnoty;
   * vrati vals; pracuje s daty z BVA z dr2ff iz bvatest 2D
   * @param string $line
   * @param int $min pocet mezer od min do 13
   * @param int $minpocet kolik minimalni pocet hodnot
   * @param bool $nonnumeric jestli prvni hodnota muze nebyt numericka
   * @return array|boolean
   * @since 5.11.2012 kvuli datum z BVAtest 2D
   */
  private function splitline($line,$min,$minpocet,$nonnumeric=false){
  	$minmax = "{"."$min,13"."}";
  	$vals = preg_split("/\s$minmax/",trim($line)); // data z BVA v Motole
    if(count($vals)>=$minpocet && (is_numeric($vals[0]) || $nonnumeric)) { // pro jistotu 
    	return $vals;
    } else {
    	$vals = explode("\t",trim($line)); // data z bvaTest 2D
    	if(count($vals)>=$minpocet && (is_numeric($vals[0]) || $nonnumeric)){
    		if($nonnumeric) {
    				$vals = array_values(array_filter($vals)); // jen pro jmena sloupcu, martin tam ma chybu - za pausa jsou 2x \t
    				// bez call back vraci neprazne hodnoty
    		 		// chci aby byly cislovane od 0 do n
					$this->inversey = false;    // zrejme to jsou data z bvaTest2D od Martina, ktery ma y nahoru kladne 				
    		}
    					
    		return $vals;
    	} else {
    		return false;
    	}
    	
    }
  }
  /**
   * vraci true pokud na tomto radku zacina definice cilu
   * @param string $line
   * @return boolean
   */
  private function sectordef($line){
  	return (substr($line,0,20)=="phase         sector" /* BVA data drf2ff*/ 
  			|| substr($line,0,12)=="phase\tsector" /* Bvatest 2D data - 5.11.2012*/
  	);
  }
  /**
   * vraci true, pokud na tomto radku zacina definice cues
   * @param string $line
   * @return boolean
   */
  private function cuedef($line){
  	return (substr($line,0,17)=="phase         cue"  /* BVA data drf2ff*/ 
  		|| substr($line,0,9)=="phase\tcue"  /* Bvatest 2D data - 5.11.2012*/
  	);
  }
  /**
   * vraci hodnotu -1 0 1, ukazujici, jestli clovek vstoupil do sektoru;
   * kvuli martinovym datum bvatest2d musim tvorit
   * @param array $vals
   * @return int
   * @since 12.11.2012
   */
  private function avoidval($vals,$track,$phase,$klavesa){ //,$track,$phase,$trial
  		$keyfound = $this->RF->keyfoundvar[$track][$phase]->current(); // TODO kde bude next?
  		$keyfoundbeepstop = $this->RF->keyfoundbeepstopvar[$track][$phase]->current();
  		if(!empty($keyfound) && in_array($keyfoundbeepstop,array(0,2))){ // keyfoundbeepstop 1 znaci, ze se tim jen vypina piskani, ale ne oznacuje cil
  		  if($klavesa==$keyfound && !$this->keyfoundpressed){ // pokud je definovana klavesa pro oznaceni cile, avoid=1 se uklada jen kdyz je zmacknuta
  		  	$this->keyfoundpressed = true; // v tomto trialu jiz bylo f zmacknute
  		  	return 1; // zde vlastne prepisuji v tracku ulozene avoid, protoze v datech BVAtest2D je blbe - ignoruje definici f
  		  } else { 
  		  	return 0;
  		  }	
  		} else {// pokud neni definovana klavesa pro oznaceni cile nebo je keyfoundbeepstop=1, ukladam v tracku ulozene avoid
	    	foreach ($this->sectors as $s) {
	          if($vals[$s]!=99){
	          	return (int) $vals[$s];
	           // $this->avoidarr[$track][$phase][$trial][] = 
	            break;
	          }
	        }
  		}
  }
  

}

?>
