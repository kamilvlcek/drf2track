<?php
require_once('includes/logfile.php');
require_once('includes/stat.inc.php');
require_once('classes/cvararr.class.php');
require_once('classes/TableFile.class.php');
require_once 'classes/CLine.class.php';
require_once 'classes/czasah.class.php';
require_once 'classes/CTrialNames.class.php';
require_once 'classes/ViewAngle.class.php';
require_once 'classes/Kvadranty.class.php';
require_once 'classes/CDrf2Track_frame.php';
require_once 'classes/Kvadranty.class.php';
require_once 'classes/CHistogram.class.php';
require_once 'classes/TrackExport.class.php';

//if(!defined('INPUTDIR'))
//  define('INPUTDIR','.');
//define('KEYTONEXT','g');

//define('PLOTTOSTART',0); // jestli se ma kresli i cast tracku nez bylo zmacknuto c
//define('PLOTAFTERCIL',0);
define('ROTATERELATIVE',1); // jestli se maji v pripade relativniho startu rotovat hodnoty aby start byl dole
if(MWM2){
	define('ENVELOPESIZE',10); // sirka kolik vlevo a vpravo ma byt envelope // u MWM2 pouzivam 10
	//define('ULOZUHLY',1); // na analyzu uhlu mezi jednotlivymi cili - normalne 0 // u MWM2 matlab musi byt 1
} else {
	define('ENVELOPESIZE',0); // sirka kolik vlevo a vpravo ma byt envelope // u MWM2 pouzivam 10
	//define('ULOZUHLY',1); // na analyzu uhlu mezi jednotlivymi cili - normalne 0 // u MWM2 matlab musi byt 1
	//16.9.2014 - takhle definice presunuta mezi ostatni do defineconst.php
}

//define('BODSIZE',3);
define('TEXTKLAVESASIZE',14);
define('TEXTNAMESIZE',10);
define('EYEVIEWANGLE',4); // jak velky uhel kolem znacky nas zajima, jeho polovina vlevo i vpravo
// fovea ma 14.7 virtualnich stupnu, foveola 3.9 (viz fovea.xls) 
// do 25.4.2013 tu bylo EYEVIEWANGLE=15
define('ANGLEMINDIST',5); // o kolik clovek musi odejit ze startu, aby se zacala pocitat prumerna uhlova odchylka
define('TARGETCORRIDORANGLE',20); // max uhlovy rozdil v target coridoru - uhel od startu k cili
define('KLAVESYKRESLI',1); // jestli do obrazku kreslit stlacene klavesy - 15.10.2014
define('FILENAMEKRESLI',1); // jestli do obrazku kreslit nazev souboru  - 15.10.2014
define('TRACKHISTO',0); //pocita a ulozi do txt souboru histogram z tracku, kvuli probetrialum ve vFGN a pohlavnim rozdilum - 10.11.2014
define('TRACKEXPORT',1); 
define('SAVETABLE',1); // jestli se ma ulozit individualni vystupni tabulka  - 14.11.2014
define('SAVEHTML',0); // jestli se ma ulozit individualni HTML s odkazem na obrazek  - 14.11.2014
define('MARKKOULE',0); // jestli kreslit znacky=cues koulemi na jejich skutecne pozice - u dat SpaNav
/*
jak vyresim tri ukoly
- ziskani dat z tracku do standardizovane formy (a moznosti ruznych vstupnich tracku)
-
*/

class Drf2Track {
  var $filename;
  private $fileno; //29.1.2016 - chci do tabulky uhly ukladat i cislo subjektu
  var $error;
  var $counts; // pole [0-tracks][0-phases]=>trials
  var $names; // nazvy sloupcu tracku
  //var $sectors;
  var $klavesaposition; // na jake pozici v poli hodnot tracku je stlacena klavesa
  //var $cvar; // pozice cile - uhel - pole hodnot - pro kazdou fazi tam bude hodnotou trida vararr
  
  // PREHOZENY DO CTrackVarsFrame - 8.11.2010
  //var $r; // polomer cile [$track][$phase]
  //var $r0var;// pozice cile od stredu areny - pole hodnot [$track][$phase]
  //var $cxyvar; // pozice cile - pole hodnot - nepouziva se
  //var $reltovar; // relativne cil? - pole hodnot
  
  //var $laservar; // pozice cue - pole hodnot - uhel ve stupnich a ne cislo laseru! (kvuli relativnimu startu)
  //var $segmentsvar; // vzor cue - pole hodnot
  //var $startPointvar; // starty - pole hodnot
  var $keytocues; // pole hodnot [faze]
  //var $anletorotate; // uhel o ktery se ma orotovat kazda hodnota - v pripade relativniho startu
  
  
  //var $lastlastxy; // predposledni kreslene souradnice bodu ( kvuli pocitani uhlu) / array[n][x,y]
 
  
  // FRAMESPECIFIC
  var $lastxy; //posledni kreslene souradnice bodu / array[n][x,y]
  var $cilxy; // souradnice posleniho cile
  var $startxy; // souradnice posledniho startu
  var $lasteyexy; // posledni souradnice eyexy;
  var $cilearr; // pole pozic cile - spravne a udane pozice
    /**
   * @var CDrf2Track_frame
   */
  private $RF;
  /**
   * @var CDrf2Track_frame
   */
  private $AF;
  // -----------------
  /**
   * @var CTrackVars
   */
  var $trackvars;
  
  var $lastangle; // kolik byl uhel mezi minulymi body
  var $trialangle;  // kolik se clovek otocil behem pokusu
  var $trialangleabs;  // kolik se clovek otocil behem pokusu - soucet absolutnich hodnot
  var $distancefromgoal; // tam budu postupne pricitat vzdalesnosti od cile, a pak je vydelim poctem hodnot
  var $cilovykvadrant; // pocet bodu, kdy byl ve stejnem kvadrantu jako cil
  /**
   * sumarni uhlova odchylka od smeru start-cil
   * @since 29.5.2014
   * @var deg
   */
  private $anglefromgoal; // postupne pricitam absolutni uhlouvou chybu vzhledem ke spojnici mezi startem a cilem.
  
  /**
   * kolik bodu je blizko k jedne ze znacek
   * @var int
   */
  private $markproximal;
  private $markstart = false; // true dokud je od startu clovek blizko znacky
  /**
   * @var Kvadranty
   */
  private $CKvadranty; // pole tri kvadrantu kolem cil - 1 je po smeru od cile, 2 je naproti cili a 3 je proti smeru od cile
  //var $fhtxt; // filehandle pro txt file
  public $txttable; // objekt pro vystup tabulky s vysledky
  private $uhlytable; // objekt pro vystup tabulky s uhly
  public $uhlytable2; // objekt pro vystup tabulky s uhly mezi cily
  //var $fhuhly; //docasny pro posouzeni uhlu
  var $cilnalezen; // jestli byt v tomto trialu uz nalezen cil
  var $vstupudocile;// kolikrat v tomto trialu uz vstoupil do cile - 25/7/2013
  var $framemax; //nejvyssi frame v poslednim trialu (nemuzu pouzit trackvars no primo, protoze to se mi nuluju
  private $framestart; // cislo framu na zacatku trialu
  private $frameanglestart; // cislo framu, kdyz clovek odesel ze startu (dist>=ANGLEMINDIST)
  var $timestart; // cas zacatku trialu - pokud je TimeInfo() = true;
  var $timemax; // maximalni cas trialu - pokud je TimeInfo() = true;
  private $targetcorridor = 0; // pocet bodu v target corridoru - 
  /**
   * [track][trial][phase] bod,cil,relto
   *
   * @var array
   */
 
  var $envelope; // array[0-1][a,b,c] - dve obecne rovnice primek
  var $envelopebod; // bod, v kterem se pocitala envelope
  //var $envelopefh; // filehandle pro zapis envelope
  private $envelope_table; // table pro zapis envelope
  var $envelopebody; // seznam vsech bodu envelope - maze se, kdyz zadam referencni bod
  private $htmlembed_data; // pole s udaji pro vytvoreni HTML souboru
  private $trial_groups_arr; // pole se skupinami pokusu, zatim tech, ktere maji max pocet znacek 
  /**
   * @var CTrialNames
   */
  private $trialnames; 
  /**
   * @var ViewAngle
   */
  private $viewangle;
  /**
   * pole s uhlama ukazani na cil; [$phase][$trial]
   * @var array
   */
  private $pointingangle;
  /**
   * pole s casy do ukazani cile; [$phase][$trial]
   * @var array
   */
  private $pointingtime;
  
  /**
   * text ktery se ma na konci kazdeho trialu vypsat do stredu obrazku
   * @since 29.5.2014
   * @var string
   */
  private $texttowrite = "";
  /**
   * text ktery se ma na konci kazdeho trialu vypsat do spodu obrazku
   * @since 2.6.2014
   * @var string
   */
  private $texttowrite2 = "";
  /**
   * pole s nastavenima pro tento soubor
   * zatim jen ktere trialy se maji vynechat - 20.9.2010
   * 
   * @var array
   */
  private $filesettings;
  
  /**
   * prumerna vzdalenost stred tracku posledniho trialu, pocita se po nalezeni cile 
   * @var float
   */
  private $pathcentroiddist;
  
  /**
   * @param CTrackVars $trackvars
   * @param string $filename
   * @param unknown_type $filesetting
   */
  function Drf2Track(&$trackvars,$filename,$filesetting,$fileno=false){
    $this->filename = $filename;
    $this->fileno=($fileno!==false)?$fileno:-1;
    $this->cilearr = array();
    $this->trialnames = new CTrialNames();
    $this->filesettings = $filesetting;
  	if(!file_exists($filename) || $trackvars->error ){
			//dp("  ERR:file not found: $filename!!!");
			$this->error = true;
	 	  return;
		} else {
		  $this->error = false;
		  $this->trackvars = &$trackvars;
		  $this->txttable = new TableFile(SAVETABLE?$this->filename_towrite(TABLESDIR).".xls":false);
		  $this->CKvadranty = new Kvadranty();
		  $this->txttable->AddColumns(array_merge(array(
		    "datum","soubor","track","faze","trial","frame",
		    "name",/*MWM2*/ "markname","markcount","markgroup","goaltype", /**/
		    "cilx","cily","bodx","body","startx","starty",
		    "trialdist","trialangle","trialangleabs","mindist","path_efficiency",
		    "time","angleerr","disterr",
		    "distfromgoal",/* MWM2*/ "distsymmetry", /**/
		    "avgdistfromgoal",/* MWM2 */"disterrfromcenter","angleerrfromcenter","angleerrfromcenter_cm",
		    "distfromreference","angleerrfromreference",/**/
		    "jmeno_cile"),$this->CKvadranty->Kvadranty_names(),
		  	array("pointerr","pointtime","maxtime",
		  	/*25.7.2013*/"vstupudocile",/*29.5.2014*/'avganglefromgoal',/*30.5.2014*/'targetcorridor',
		  	/*10.7.2014*/'markproximity',
		  	/*2.6.2014*/'strategie'
		  	)));
		  $this->txttable->SetPrecision(3,21); // path_efficiency
      $this->viewangle = new ViewAngle(); // vytvorim vzdy, zalezi jestli budu taky ukladat data
      $this->RF = new CDrf2Track_frame('ROOM');
      
      if(ARENAFRAME) $this->AF = new CDrf2Track_frame('ARENA');
		  if(ENVELOPESIZE>0){
		  	$this->uhlytable = new TableFile("uhly.txt");
		  	$this->uhlytable->AddTitle($this->filename);
		  	$this->uhlytable->AddColumns(array("track","phase","trial","no",
		  	   "bodx","body","angle","anglediff"));


		    $this->envelope_table = new TableFile("envelope.txt");
		    $this->envelope_table->AddTitle($this->filename);
		    $this->envelope_table->AddColumns(array(
			      "track","phase","trial","no","bodx","body","primka1-a","primka1-b","primka1-c",
	          "primka2-a","primka2-b","primka2-c"
        ));

		    dp("computing envelope: ".ENVELOPESIZE);
		  }
		}
  }
  function Image() {
    $startpositions = array(); // tam si budu ukladat uhly pri stlaceni c - rozdelene podle faze;
    //unset($klavesa);
    $trackvars = &$this->trackvars; // odkaz kvuli zjednoduseni
    $this->cilnalezen = true; // aby pri zacatku trialu rovnou neukladal
    $this->framemax = 1;
    $img = array();
    while ($trackvars->Next()) {
          // kvuli zjednoduseni:
      $track = $trackvars->track;
      $phase = $trackvars->phase;
      $trial = $trackvars->trial;
      $frame = $trackvars->frame;
      $pausa = $trackvars->pausa;
      $avoid = $trackvars->avoid;
      $klavesa = $trackvars->klavesa;
      $roomxy = $trackvars->roomxy;
      $arenaxy = $trackvars->arenaxy;
      $goalno = $trackvars->goalno;


/* ------ ZACINA DALSI TRACK ------------- */
      if($trackvars->Trackstart()) {
        if($track>0){ // nezacina prvni track
          $this->saveanddeleteimg($img,$trackvars->lasttrack);
        }
        $img = array();
        // kamil 29.4.2008 - v radcich faze, ve sloupcich trialy, jeden obrazek pro room frame, druhy pro arenaframe
        dp($trackvars->line,": novy image track $track (fazi/trialu): ".count($trackvars->counts[$track])."/".max($trackvars->counts[$track])."\n: - ".$this->filename);
        $img = $this->createimg($track);
      }
/* ------ ZACINA DALSI FAZE ------------- */
      if($trackvars->TrialStart()) {
/* ------ prvni bod trialu ------------- */
        if(!$this->cilnalezen){
          $this->UlozCil(false,true);
          $this->NakresliTextToWrite($img[0]);
          unset($this->RF->lastxy);
          unset($this->RF->lasteyexy);
        }
        if(!$trackvars->Trackstart()) $this->PosunVars($this->trackvars->lasttrack,$this->trackvars->lastphase);
        $this->framemax = 1; // abych odstranil pripadne deleni nulou v uloztxt
        $this->timemax = 0;
        $this->framestart = $trackvars->no;
        $this->frameanglestart = $trackvars->no-1;
        $this->vstupudocile = 0;// zkusim to dat sem, aby se nulovalo po kazdem trialu
        if($this->trackvars->TimeInfo()) $this->timestart = $trackvars->time;
        if(!isset($this->trackvars->RF->reltovar[$track][$phase])){
        	//trigger_error("trackvars RF reltovar nedefinovano",E_USER_WARNING); 
        	unset($this->RF->lastxy);
        } elseif($this->trackvars->RF->reltovar[$track][$phase]->current()==0) {
          unset($this->RF->lastxy);
          //pokud cil neni urcovat relativne, musim zrusit posledni lastxy - jinak by se pocitala do dalsiho tracku i vzdalenost od minule posledni pozice
        }
        $startpositions[$phase]=360-rad2deg(angle($roomxy)); // pouziva se pri relativni pozici cile
        echo (MAKEIMG?" image: ":" table: ")."line ".$trackvars->line.", track $track, phase $phase, trial ".$trial."\r";
        $this->NakresliZacatek($img[0],$track,$phase,$trial,$frame,$goalno,$roomxy,$startpositions);
        if(ARENAFRAME) 
          $this->NakresliZacatek($img[1],$track,$phase,$trial,$frame,$goalno,$arenaxy,$startpositions,true);
        $this->viewangle->NextTrial($this->marks_positions($track,$phase), 
		   	     $this->maxtime($track, $phase, $trial),
		   	     $this->roomxylast($track, $phase, $trial), $this->trackvars->RF->r[$track][$phase]/100*ARENAR, //prepocet z procent polomeru
		   	     $this->trackvars->goaltypearr[$track][$phase][$trial]=='e'
		   	     );
		
      } else {
/* ------ JEDEN NORMALNI BOD TRACKU, NE PRVNI ------------- */
        $this->framemax = max($this->framemax,$trackvars->no);
        if($trackvars->PointingToGoal() && empty($this->pointingangle[$phase][$trial])) {
        	// pokud jeste v tomto trialu nebylo ukazano
        	$this->pointingangle[$phase][$trial] = $trackvars->anglesubj; // uz normalizovano na format 0-360, 0 doprava a 90 nahoru
        	$this->pointingtime[$phase][$trial] = $trackvars->time - $this->timestart; // cas do ukazani na cile
        	$this->timestart = $trackvars->time; // resetuju zacatek pocitani casu, kdyz je stlacena klavesa pro ukazovani na cil
        	$this->distancefromgoal = 0; // resetuji soucet vzdalenosti bodu od cile pro pocitani avgdist from goal - 28.5.2014
        	$this->targetcorridor = 0;
        	$this->anglefromgoal = 0; //resetuji soucet uhlovych odchylek
        	$this->markproximal = 0;
        	$this->framestart = $trackvars->no; // resetuji framestart pri ukazani
        	$this->frameanglestart = $trackvars->no-1; // resetuji framestart pri ukazani
        	$this->CKvadranty->Reset();
        }
        if($trackvars->TimeInfo()) $this->timemax = max($this->timemax,$trackvars->time);
        $this->NakresliJedenBod($img[0],$track,$phase,$trial,$roomxy,$avoid,$pausa,$klavesa,"RF");
        if(ARENAFRAME)
          $this->NakresliJedenBod($img[1],$track,$phase,$trial,$arenaxy,$avoid,$pausa,$klavesa,"AF");
          
        if(!$this->cilnalezen && $this->NaselCil($avoid)){
          $this->PathCentroidDistance($img[0], $track, $phase, $trial);
          $this->UlozCil($trial); // lastxy protoze uz predtim byl nakreslen a pritom se last ulozil
          // 11.11.2008 - presunul jsem to do kresleni zacatku - bude to chodit ? 
          $this->NakresliTextToWrite($img[0]);
        }
      }
    }
    
    if(!$this->cilnalezen){ // v teto dobe uz je track = 1 (i kdyz zadny track neexistuje)
      $this->trackvars->track = $this->trackvars->lasttrack;
      $this->trackvars->phase = count($this->trackvars->counts[$this->trackvars->track])-1;
      $this->trackvars->trial = $this->trackvars->counts[$this->trackvars->track][$this->trackvars->phase]-1;
      $this->PathCentroidDistance($img[0], $track, $phase, $trial);
      $this->UlozCil();
      $this->NakresliTextToWrite($img[0]);
      
    }
  	if($track>-1){ //2.6.2014 posunuto za predchozi IF kvuli psani do obrazku. Snad to nevadi
      // konci nejaky track
      $this->saveanddeleteimg($img,$trackvars->lasttrack);
      
    }
    $this->SaveTxt(); // kamil 18.5.2010
    $this->UlozUhly();
    $this->CloseTxt();
    $this->SaveHtml(); // kamil 29.9.2009
    if($this->trackvars->AngleInfo()) // kamil 16.8.2010
        $this->viewangle->SaveTable($this->filename_towrite(TABLESDIR));
    if(TRACKHISTO || TRACKEXPORT) $this->TrackHisto();
  }
  function createimg($track){
     $img = array();
     $img[0]=  new Image((ARENAR+10)*2,(ARENAR+10)*2,$this->velikostobrazkux($track)+(SUMMARYPLOT?1:0),$this->velikostobrazkuy($track));
     if(ARENAFRAME==1){
       $img[1]=new Image((ARENAR+10)*2,(ARENAR+10)*2,$this->velikostobrazkux($track)+(SUMMARYPLOT?1:0),$this->velikostobrazkuy($track));
     }
     return $img;
  }
  function saveanddeleteimg(&$img,$track){
    for($frame = 0; $frame < count($img); $frame++){
        $framename = $frame==0?"ROOM":"ARENA";
        $filename = $this->filename_towrite(IMAGESDIR)."_".$track."_".$framename;
        $imagename = $img[$frame]->SaveImage($filename);
        $this->htmlembed_data[$framename][$track] =
        	array("sizex"=>$img[$frame]->sizex, "sizey"=>$img[$frame]->sizey,"filename"=>IMAGESDIR."/".basename($imagename));

        if(MAKEIMG) dp(basename($filename),": image saved");
        $img[$frame]->Delete();
      }
  }
  /**
   * kolik plotu bude v obrazku ve smeru x
   *
   * @param unknown_type $track
   * @return unknown
   */
  function velikostobrazkux($track){
    if(count($this->trackvars->counts[$track])>1){ // pokud je vice fazi nez 1, bude kazda faze jeden sloupec
      return count($this->trackvars->counts[$track]);
    } else {
      return min(4,max($this->trackvars->counts[$track])); // minimum z poctu trial a cisla 4
    }
    //count($this->counts[$track]).", ".max($this->counts[$track])
  }
  /**
   * kolik plotu bude v obrazku ve smeru y
   *
   * @param unknown_type $track
   * @return unknown
   */
  function velikostobrazkuy($track){
    if(count($this->trackvars->counts[$track])>1){ // pokud je vice fazi nez 1, bude kazdy trial jeden radek
      return max($this->trackvars->counts[$track]);
    } else {
      return intval((max($this->trackvars->counts[$track])-1)/4)+1;
    }
  }
  function subplotxy($track,$phase,$trial){
    if(count($this->trackvars->counts[$track])>1){
      $x= $phase;
      $y=$trial;
    } else {
      $x= $trial%4; // pokud je jen jedna faze
      $y = intval($trial/4);
    }
    return array($x,$y);
  }

  /**
   * @param array $c
   * @param Image $img
   * @param int $track
   * @param int $phase
   * @param string $framename
   * @param string $activeframe
   * @param book $goal_relative
   */
  private function NakresliCil(&$img,$track,$phase,$framename="RF",$activeframe="ROOM",$goal_relative=false) {
  	  if(!isset($this->trackvars->$framename->cvar[$track][$phase])){ //???
  	  	$this->$framename->cilxy = array();
  	  	$this->cilnalezen = false;
  	  	
  	  	return ;
  	  	
  	  }
  	  $c = $this->trackvars->$framename->cvar[$track][$phase]->current();
      $r0 = $this->trackvars->$framename->r0var[$track][$phase]->current();
      $r = $this->trackvars->$framename->r[$track][$phase]; /** @see CTrackVarsFrame */ //polomer cile v procentech areny
      
      if(is_array($c)){ // kamil 18.8.2009 17:17:13 - pridal jsem moznost vice cilu kvuli experimentu s Ivetou
        $r0arr =  $r0;   // kdyz je $c array, tak je $r0 asi taky array
        $is_array = true;
      } else {
        $r0arr = array($r0);
        $c = array($c  - ($goal_relative?90:0));
        $is_array = false;
      }
      foreach ($c as $key=>$cil){
      	  $cxy[$key] = new CPoint();
      	  $cxy[$key]->DefineByAngleDistance($c[$key],$r0arr[$key]/100*ARENAR);
      	  //angledist2xy(deg2rad($c[$key]),$r0arr[$key]/100*ARENAR);// vystup z funkce ma y=+10 nahore a -10 dole
          $cxy[$key]->ReverseY(); //[1]=-$cxy[$key][1];
          $color = CDrf2Track_frame::FrameIsActive($framename,$activeframe)? "red": "gray"; 
          $img->Circle($cxy[$key],ARENAR*$r/100,$color,false); // pozice cile
          if($is_array) $img->Text($cxy[$key],TEXTNAMESIZE,"black","$key");
      }

      $this->$framename->cilxy = $cxy; // bude to array, pokud $c je array
      $this->$framename->cilc = $cil; // bude to posledni z cilu - 17.12.2012
      $this->cilnalezen = false;
  }
  /**
   * @param Image $img
   * @param int $track
   * @param int $phase
   * @param string $framename RF=roomframe, AF = arenaframe
   */
  function NakresliCues(&$img,$track,$phase,$framename="RF") {
    if(isset($this->trackvars->$framename->laservar[$track][$phase])){
	  	for($l=0;$l<count($this->trackvars->$framename->laservar[$track][$phase]);$l++){ // vykresli cue
	       $img->Cue(array(0,0),ARENAR,
	            ($laser = $this->trackvars->$framename->laservar[$track][$phase][$l]->current()), // pokud je laser<=0 nema se znacka zobrazit
	            $this->trackvars->$framename->segmentsvar[$track][$phase][$l]->current(),
	            $this->trackvars->$framename->startPointvar[$track][$phase][$l]->current(),
	            $this->trackvars->$framename->marknamevar[$track][$phase][$l]->current());
	        if($this->trackvars->$framename->markxyvar[$track][$phase][$l]->count()>0 && $laser>=0 && MARKKOULE) 
	        	// znacka kruhem na jeji skutecne pozici - 13.11.2012 - netvori se u BVA dat
	        	$img->Circle($this->trackvars->$framename->markxyvar[$track][$phase][$l]->current(), 
	        		ARENAR*$this->trackvars->markradius/100, // z procent polomeru na polomer areny 140
	        		$img->barvy_segments[$this->trackvars->$framename->segmentsvar[$track][$phase][$l]->current()]
	        	);
    	
	    }
    }
  
  }
  /**
   * nakresli bod startu, kde stal clovek pri 'c'
   *
   * @param Image $img
   * @param [x,y] $bod
   * @param bool $relto
   * @param bool $phase
   * @param bool $sp SUMMARYPLOT
   * @param string $framename
   */
  function NakresliStart(&$img,$bod,$relto=false,$phase=false,$trial=false,$sp=false,$framename = "RF") {
    if($this->angletorotate!=0 && ROTATERELATIVE){ // jestli se maji body rotovat aby start bylo dole
      $bod = rotate(360-($this->angletorotate+90),$bod);
    }
    if($relto == 0 || $relto == $phase+1) {
        // pokud je cil relativne vuci tehle fazi
        if($sp==0) $this->EnvelopeCompute($bod,array(0,0));
    }
    $this->$framename->startxy = $bod;
    $img->Point($bod,'crimson',10); // start position
    $img->LineTrackStart('black','blue',$this->trackvars->BodSize(),"track{$phase}_{$trial}");
    if(SUMMARYPLOT){
       $img->LineTrackStart('black','blue',$this->trackvars->BodSize(),"summaryplot{$phase}_{$trial}");
    }
    if(EYETRACKING){
      $img->LineTrackStart('yellow','yellow',5,"eyetrack{$phase}_{$trial}"); //zacatek cary spojujici mista pohledu = eyetrack
      $img->LineTrackStart('gray','none',0,"eyeconnect{$phase}_{$trial}");   //zacatek cary spojujici track s mistem pohledu = eyeconnect
    }
    $this->trialangle = $this->trialangleabs =  0; 
    $this->$framename->ResetTrial();
    $this->distancefromgoal = 0;
    $this->targetcorridor = 0;
    $this->anglefromgoal = 0;
    $this->cilovykvadrant = 0;
    $this->markproximal = 0;
    $this->CKvadranty->Reset();
    unset($this->lastangle);
  }
  /**
   * @param Image $img
   * @param int $track
   * @param int $phase
   * @param int $trial
   * @param [x,y] $roomxy
   * @param int $avoid
   * @param int $pausa
   * @param char $klavesa
   * @param string $frame
   */
  function NakresliJedenBod(&$img,$track,$phase,$trial,$roomxy,$avoid,$pausa,$klavesa,$frame="RF") {
       $subplot = $this->subplotxy($track,$phase,$trial);
       $img->SubplotActivate($subplot); // ROOM TRACK
       $this->NakresliBod($img,$roomxy,$avoid,$pausa,$klavesa,0,$frame);
	   if(EYETRACKING) $this->NakresliEyeBod($img);
       if(SUMMARYPLOT){
         $subplot2 = array($img->subplotsx-1,$subplot[1]);
         $img->SubplotActivate($subplot2); // ROOM TRACK
         $this->NakresliBod($img,$roomxy,$avoid,$pausa,$klavesa,1,$frame);
       }
  }
  /**
   * nakresli jeden bod, imgno muze byt cislo plotu, lastxy se bere zvlast pro kazdy plot
   * summary plot je $imgno = 1
   *
   * @param Image $img
   * @param [x,y] $bod pozice tracku
   * @param int $avoid jestli je v oblasti cile
   * @param int $pausa
   * @param char $klavesa
   * @param int $imgno
   * @param strign $frame
   */
  function NakresliBod(&$img,$bod,$avoid,$pausa,$klavesa,$imgno = 0,$frame="RF"){
    $bod = $this->BodRotuj($bod);

    if($imgno == 1 && ENVELOPESIZE>0 && is_array($this->envelope) && !bodmeziprimkami($bod,$this->envelope[0],$this->envelope[1])) {
      $color = 'orange'; $size = 8;
      $this->EnvelopeCompute($bod); // spocitam envelope z aktualniho a minuleho bodu
      //$img->Line($this->envelopebody[count($this->envelopebody)-1],$bod,'orange');
      if(is_array($this->envelopebody) && count($this->envelopebody)>2){
           $last_env = count($this->envelopebody)-1;
           $angle = anglediffcenter($bod,$this->envelopebody[$last_env-2],$this->envelopebody[$last_env-1]);
           $anglediff = $last_env>2?($angle>0?-180+$angle:180+$angle/*angle je zaporne*/):$angle; // meri se uhel od primeho smeru - minus je doleva, plus doprava
           $this->trialangle += $anglediff;
           $this->trialangleabs += abs($anglediff);
           $this->uhlytable->SaveRow($this->uhlytable->AddRow(array(
			             $this->trackvars->track,$this->trackvars->phase,$this->trackvars->trial,
			             $this->trackvars->no,$bod[0],$bod[1],
			              $angle,$anglediff
			      )));
      }
    } elseif($avoid==1){
      $color = 'red'; $size = 5;
    } elseif($avoid==-1 || $avoid==10){ //25.7.2013 - kvuli poctu pruchodu cilem= je v miste cile podle jeho velikosti
      $color = 'green'; $size = 5;
    } elseif($pausa>0) {
      $color = 'black'; $size = 2;
    } else {
      $color = false; $size = false;
    }

    $this->UlozKvadranty($imgno,$bod,$frame);

    	//if(empty($this->lastxy[$imgno]) || !pointsame($this->lastxy[$imgno],$bod)){
      // abych nekreslil ten samy bod nekolikrat za sebou
      //$point, $lastupdate = true,$color=false,$diam=false,$name='line'
    $linename = (!empty($imgno)?"summaryplot":"track")."{$this->trackvars->phase}_{$this->trackvars->trial}";
    $img->LineTo($bod,false,$color,$size,$linename);//($bod,'black');
    $this->$frame->lastxy[$imgno] = $bod;
    	//}
    if($this->trackvars->ViewPointInfo()){  // kamil 5.5.2009 17:00 - pokud mam primo view point, tak budu kreslit ten
       $img->Line($this->trackvars->viewxy,$bod,'green'); //orange ne, green je stejna jako v drf3tomatlab
    } elseif($this->trackvars->AngleInfo() && (VIEWANGLE || VIEWANGLEHISTO || $this->trackvars->PointingToGoal())){ // kamil 5.5.2009 16:34 - tohle naopak chci kreslit i nekolikrat v jednom bode
    	// nakreslim caru pohledu
    	if(VIEWANGLE){
    		$bodP = new CPoint($bod);
	    	$bodMove = new CPoint(); // predpokladam format uhlu 0 doprava, 90 nahoru
	    	$delka_cary = $this->trackvars->PointingToGoal() ? 100 : 20; //24.7.2012
	    	$barva_cary = $this->trackvars->PointingToGoal() ? 'orange':'green';
	    	$tloustka_cary = $this->trackvars->PointingToGoal() ? 4:1;
	    	$bodMove->DefineByAngleDistance($this->trackvars->anglesubj,$delka_cary /*celka carky*/)->ReverseY();  // kamil 5.6.2010
	      	$img->Line($bod,$bodP->Move($bodMove)->toArray(),$barva_cary,false,$tloustka_cary);
    	}
      	
      	// ulozim pohled do histogramu 
      	if(VIEWANGLEHISTO && !EYETRACKINGHISTO){ 
      		// 2D histogram uhlu pohledu - nesouvisi s pohledem oci, ale jen s natocenim subjektu
      		$this->saveanglehisto($this->trackvars->anglesubj, $bod);
      	}
      	
    }
    if(KLAVESYKRESLI && !empty($klavesa) && !in_array($klavesa,array('+','-'))) { // + a - se pouzivaji pro zmenu thresholdu pri trackovani
      $img->Point($bod,'orange',$klavesa==PROBETIMEKEY?30:15,false);
      $velikost_textu = $klavesa==PROBETIMEKEY ? TEXTKLAVESASIZE*1.5 : TEXTKLAVESASIZE; //24.7.2012
      $img->Text(array($bod[0]+15,$bod[1]+15),$velikost_textu,"gold",$klavesa); //$center,$size,$color,$text
    }

  }
  /**
   * nakresli bod eyetrackingu a ulozi ho do histogramu
   *
   * @param Image $img
   */
  function NakresliEyeBod(&$img){
    if(isset($this->trackvars->eyexyz) && is_array($this->trackvars->eyexyz)){
      $eyebodarr = array($this->trackvars->eyexyz[0],$this->trackvars->eyexyz[1]); // bod, kam se diva
      $eyebodarr = $this->BodRotuj($eyebodarr);
      $eyebodarr = $this->ChangeDiam($eyebodarr,ARENAR);
      $eyebod = new CPoint($eyebodarr); // kladne y je dolu
      $imgno = 0;
	  $eyebod->ReverseY(); // kvuli vypoctu uhlu - kladne y nahoru
      $trackbod = new CPoint($this->RF->lastxy[$imgno]); // kladne y dolu
	  $angle = $eyebod->Angle($trackbod->ReverseY()); // uhel pohledu
	  $viewbod = ViewAngle::PointInView($trackbod, $angle);// misto na okraji areny, kam se clovek diva - pocitana projekce
      
      if(empty($this->RF->lasteyexy) || !pointsame($this->RF->lasteyexy,$eyebodarr)){
        // abych nekreslil ten samy bod nekolikrat za sebou
        switch ($this->trackvars->eyexyz[2]) { // barva bodu - $ZName
          case 0:
             $color = 'green'; $size = 5; break; //platforma
          case 1:
             $color = 'violet'; $size = 5; break; //pod znackou
          case 2:
             $color = 'red'; $size = 5; break; // vyska znacky
          case 3:
             $color = 'orange'; $size = 5; break; // strop
        }
        
        //$img->Point($bod,$color,$size,true,false);
        
        // zluta cara spojujici mista pohledu = eyetrack
        $img->Lineto($eyebodarr,true,$color,$size,"eyetrack{$this->trackvars->phase}_{$this->trackvars->trial}");
  		
        // seda cara spojujici track s mistem pohledu = eyeconnect
        $img->Lineto($this->RF->lastxy[$imgno],true,false,false,"eyeconnect{$this->trackvars->phase}_{$this->trackvars->trial}",false); //presunuti bodu
        $img->Lineto($eyebodarr,true,false,false,"eyeconnect{$this->trackvars->phase}_{$this->trackvars->trial}"); // cara od tracku do pohledu
        
        
		$img->Point($viewbod->ReverseY(), "gray", 4); // projekce pohledu na okraj areny
		
        // if(isset($this->lasteyexy))
        //  $img->Line($this->lasteyexy,$bod,'yellow',false); // spojeni bodu eyetracku
        //$img->Lineto($bod,'gray',false); // spojeni s bodama tracku
        //$this->lastlastxy[$imgno]=$this->lastxy[$imgno];
        $this->RF->lasteyexy = $eyebodarr;

      }
      if(EYETRACKINGHISTO){
	      $this->saveanglehisto($angle, $this->RF->lastxy[$imgno],EYEVIEWANGLE);
      }
	      
    }
  }
  /**
   * rotuje bod aby byl start dole, pokud se to ma delat
   *
   * @param [x,y] $bod
   * @return [x,y]
   */
  private function BodRotuj($bod){
    if($this->angletorotate!=0 && ROTATERELATIVE){ // jestli se maji body rotovat aby start bylo dole
      $bod = rotate(360-($this->angletorotate+90),$bod);
    }
    return $bod;
  }
  function NakresliStartPoint(&$img,$c){ // nakresli samotnou znacku startu na urcenem uhlu
     $img->Cue(array(0,0),ARENAR, $c, 0,1);
  }
  /**
   * vraci jestli dorazil do cile - u preference vrati avoid==1
   * u avoidance vraci vzdy false
   *
   * @param int $avoid jestli je v oblasti cile
   * @param int $trial
   * @return bool
   */
  function NaselCil($avoid,$trial=false){
    if($trial===false){
      $trial = $this->trackvars->lasttrial;
    }
    if(!isset($this->trackvars->RF->savoidvar[$this->trackvars->lasttrack][$this->trackvars->lastphase])) {
    	// sem se to asi dostane, kdyz neni definovane, jestli je jedna o avoid sektor
    	return $avoid==1; 
    }
    if($this->trackvars->RF->savoidvar[$this->trackvars->lasttrack][$this->trackvars->lastphase]->current()==1)
      return false; // je to avoid sector
    else { // je to preference sektor
      if($avoid>0 && $this->trackvars->lastavoid==0) {
      	$this->vstupudocile++; // avoid bude 1  nebo 10
      }
      return ($avoid==1);//signal ze spanavvars ze vstoupil do cile
    }
  }
  /**
   * @param string $activeframe
   * @param int $trial
   * @param bool $last
   */
  function UlozCil($trial=false,$last=false){
  	$activeframe = ($last)?$this->trackvars->lastframe:$this->trackvars->frame;
  	if(empty($activeframe)) return; // docasne opatreni - u posledniho trialu aapp
  	$framename = CDrf2Track_frame::FrameName($activeframe);
    if($trial===false){
      $trial = ($last)?$this->trackvars->lasttrial:$this->trackvars->trial;// - 10.11. 2008 $this->trackvars->trial; // nebo lasttrial?
    }
    $this->cilnalezen = true; // false ukladat nebudu nikdy protoze tim bych si to zase zrusil
    $track = ($last)?$this->trackvars->lasttrack:$this->trackvars->track; //  - 10.11. 2008 $this->trackvars->track; //lasttrack;
    $phase = ($last)?$this->trackvars->lastphase:$this->trackvars->phase;// 10.11. 2008 - dal jsem tam last phase kvuli UT AVCR - bude fungovat i jiny pokus? $this->trackvars->phase; //nebo lastphase?
    if(!isset($this->trackvars->RF->reltovar[$track][$phase])){
      echo "$track:$phase:$trial";
    }
        
    $zasah = new CZasah(
        new CPoint($this->$framename->lastxy[0]), // bod - zasah
        $this->$framename->cilxy, // muze byt pole nekolika cilu - uz je CPoint
        $this->trackvars->goaltypearr[$track][$phase][$trial],
        $this->GoalName($track,$phase,$trial), // jmeno cile a popr. i skupiny 
        new CPoint($this->$framename->startxy), // start souradnice xy
        $this->marks_positions($track,$phase),
        $this->marks_names_string($track,$phase),
        $this->start_position($track,$phase), // start position 0-360 deg
        $this->trialsetting($phase,$trial)
        );
	  if($this->track_setis($track)){ // pokud se ma tento track zpracovat do wholeimage podle nastaveni ve filelistu - 25.10.2010
	    	if(!isset($this->cilearr[$track][$trial])){ // prvni bod prvni faze
	        $this->cilearr[$track][$trial][-2]= new CZasah(new CPoint(0,0),new CPoint(0,0));
	        $this->cilearr[$track][$trial][-1]= new CZasah(new CPoint($this->$framename->startxy),new CPoint($this->$framename->startxy));
	    }
	    $this->cilearr[$track][$trial][$phase]=  $zasah; // tohle se pak pouzive ve wholeimage
    }
    /* array(
            "cil"=>is_array($this->cilxy)?reset($this->cilxy):$this->cilxy,
            "bod_symetry"=>$this->get_symetry_point($track,$phase,new CPoint($bod)), 
            "bod"=>$bod,
            "relto"=>$this->trackvars->reltovar[$track][$phase]->current(),
            "name"=>$this->trackvars->cnamevar[$track][$phase]->current(),
            "markname"=>$this->marks_names_string($track,$phase),
            "markposition"=>$this->marks_positions($track,$phase),
            "goaltype"=>$this->trackvars->goaltypearr[$track][$phase][$trial]
    );*/

    $this->UlozCilTxt($track,$phase,$trial,$zasah,$activeframe);
    
  }
  
  function UlozUhly(){
    if(ULOZUHLY){
    	//$filename = $this->filename_towrite(TABLESDIR).".uhly.xls"; 
      $filename = (TABLEFILE_MATLAB)?str_replace(" ","_",$this->filename):$this->filename;
      //$uhly_table = new TableFile("",$this->txttable->Handle());
      $this->uhlytable2 = new TableFile($filename."-uhly"); //16.9.2014 - udelam na to specialni tabulku
      $this->uhlytable2->AddColumns(array("datum","filename","subjektno","track","trial","phase","name","cilx","cily","bodx","body","uhel0","uhel1","u1-u0","dist0","dist1","d1-d0","angleerr","disterr"));
//      fwrite($this->fhtxt,"datum".COLUMNDELIM."filename".COLUMNDELIM."track".COLUMNDELIM."trial".COLUMNDELIM."phase".COLUMNDELIM."name".COLUMNDELIM."cilx".COLUMNDELIM."cily".COLUMNDELIM."bodx".COLUMNDELIM."body".COLUMNDELIM."uhel0".COLUMNDELIM."uhel1".COLUMNDELIM."u1-u0".COLUMNDELIM."dist0".COLUMNDELIM."dist1".COLUMNDELIM."d1-d0".COLUMNDELIM."angleerr".COLUMNDELIM."disterr\n");
      foreach ($this->cilearr as $track=>$tracks){
          foreach ($tracks as $trial=>$phases){
              for($phase =-2;$phase<count($this->trackvars->counts[$track]);$phase++){
              	  if(!$this->trialsetting($phase<0?0:$phase,$trial)==EXCLUDEDTRIAL){ // 29.1.2016 - chci vynechat vyrazene trialy/faze
                  if($phase >= -1 && $phase < count($this->trackvars->counts[$track])-1 && isset($phases[$phase-1]) && isset($phases[$phase+1]) && isset($phases[$phase])){
                      $uhel0 = $phases[$phase-1]->goal->AngleDiff($phases[$phase+1]->goal,$phases[$phase]->goal);
                      $uhel1 = $phases[$phase-1]->hit->AngleDiff($phases[$phase+1]->hit,$phases[$phase]->hit);
//                      $uhel0 = anglediffcenter($phases[$phase-1]["cil"],$phases[$phase+1]["cil"],$phases[$phase]["cil"]);
//                      $uhel1 = anglediffcenter($phases[$phase-1]["bod"],$phases[$phase+1]["bod"],$phases[$phase]["bod"]);
                      if($phase>=0) {
                        $uhel0 = ($uhel0<0?-180:180) -$uhel0; // meri se uhel od primeho smeru - minus je doleva, plus doprava
                        $uhel1 = ($uhel1<0?-180:180) -$uhel1;
                      } else {
                        $uhel0 = -$uhel0;
                        $uhel1 = -$uhel1;
                      }
                      $dist0 = $phases[$phase+1]->goal->Distance($phases[$phase]->goal);
                      $dist1 = $phases[$phase+1]->hit->Distance($phases[$phase]->hit);
//                      $dist0 = distance($phases[$phase+1]["cil"],$phases[$phase]["cil"]);
//                      $dist1 = distance($phases[$phase+1]["bod"],$phases[$phase]["bod"]);
                      $angleerr = $phases[$phase+1]->hit->AngleDiff($phases[$phase+1]->goal,$phases[-1]->goal); // uhlova chyba vzhledem ke startu
                      $disterr = $phases[$phase+1]->hit->Distance($phases[-1]->goal) - $phases[$phase+1]->goal->Distance($phases[-1]->goal); // vzdalenosti chyba vzhledem ke startu
//                      $angleerr = anglediffcenter($phases[$phase+1]["bod"],$phases[$phase+1]["cil"],$phases[-1]["cil"]); // uhlova chyba vzhledem ke startu
//                      $disterr = distance($phases[$phase+1]["bod"],$phases[-1]["cil"])-distance($phases[$phase+1]["cil"],$phases[-1]["cil"]); // vzdalenosti chyba vzhledem ke startu
                  } else {
                      $uhel0 =$uhel1= $dist0 = $dist1 = $angleerr =$disterr ="0";
                  }
                  $this->uhlytable2->AddRow(array(date("j.n.Y H:m:i"),
                      $filename,$this->fileno,$track,$trial,$phase,
                      (isset($this->trackvars->RF->cnamevar[$track][$phase])?$this->trackvars->RF->cnamevar[$track][$phase]->current():"-"),
                      $phases[$phase]->goal->x,$phases[$phase]->goal->y,
                      $phases[$phase]->hit->x,$phases[$phase]->hit->y,
                      $uhel0, $uhel1, $uhel1-$uhel0,$dist0,$dist1,$dist1-$dist0,
                      $angleerr,$disterr                 
                    ));
                  /*$output = "$filename".COLUMNDELIM."$track".COLUMNDELIM."$trial".COLUMNDELIM."$i".COLUMNDELIM."".
                    (isset($this->trackvars->cnamevar[$track][$i])?$this->trackvars->cnamevar[$track][$i]->current():"-").COLUMNDELIM. // current - 11.11.2008
                    round($phases[$i]["cil"][0],2).COLUMNDELIM.round($phases[$i]["cil"][1],2).COLUMNDELIM.
                    round($phases[$i]["bod"][0],2).COLUMNDELIM.round($phases[$i]["bod"][1],2).COLUMNDELIM.
                    round($uhel0,2).COLUMNDELIM.
                    round($uhel1,2).COLUMNDELIM.
                    round($uhel1-$uhel0,2).COLUMNDELIM.
                    round($dist0,2).COLUMNDELIM.
                    round($dist1,2).COLUMNDELIM.
                    round($dist1-$dist0,2).COLUMNDELIM.
                    round($angleerr,2).COLUMNDELIM.
                    round($disterr,2).
                    "\n";
                  fwrite($this->fhtxt,date("j.n.Y H:m:i").COLUMNDELIM.$this->setdelim($output));*/
              	  }
          	  }
          }
      }
      //dp($this->cilearr,"cilearr");
      //$this->uhlytable2->SaveHead();
      //$this->uhlytable2->SaveAllRows();
      // 16.9.2014 - kvuli pridani do celkove tabulky - potrebuju do darkevel - proto prevod do promenne tridy
      
      //unset($uhly_table); 
    }
  }
  /**
   * ulozi hlavni vystupni tabulku se vsemi trialy 
   * XLS i TXT pro matlab
   */
  public function SaveTxt(){
  	if(SAVETABLE){ // vetsinou individualni tabulku nebudu ukladat - 14.11.2014
	  	$this->txttable->SaveHead();
	  	$this->txttable->SaveAllRows();
	  	$CTable = new Table();
	  	if($CTable->AppendTable($this->txttable)){
	  		$CTable->SaveAll(true,$this->txttable->FileName().".txt",1); // ulozim tabulku  pro matlab - 13.12.2012
	  		// misto vsech retezcu budou NaN, ale to mi nevadi, ted staci cislo trialu a jmeno souboru
	  	} else {
	  		echo "kopie tabulky $this->filename.txt se nezdarila\n";
	  	}
  	}
  }
  function CloseTxt(){
    //unset($this->txttable); - tu jeste budu potrebovat pro ulozeni do celkove tabulky
//  	fclose($this->fhtxt);

    if(ENVELOPESIZE>0){
    	unset($this->uhlytable);
    	unset($this->envelope_table);
//      fclose($this->fhuhly);
//      fclose($this->envelopefh);
    }
    //unset($this->trackvars);
  }
  function Timeformat($seconds){
    if($seconds>=60){
      $minutes = intval($seconds/60);
      $seconds = $seconds % 60;

    } else {
      $minutes = 0;
    }
    return "00:$minutes:".($seconds<10?"0":"")."$seconds";
  }
/**
 * nakresli pro dany trial: arenu, cil, start, cues, startovni pozici
   * @param Image $img
   * @param int $track
   * @param int $phase
   * @param int $trial
   * @param string $activeframe ARENA/ROOM
   * @param int $goalno
   * @param [x,y] $bodxy room, nebo arena
   * @param unknown_type $startpositions
   * @param bool $arenaframe
   */
  function NakresliZacatek(&$img,$track,$phase,$trial,$activeframe,$goalno,$bodxy,$startpositions,$arenaframe=false) {
        $framename = ($arenaframe?"AF":"RF");
        if( CDrf2Track_frame::FrameIsActive($framename,$activeframe) ) $color = "blue"; else $color = 'gray';
        $subplot = $this->subplotxy($track,$phase,$trial);
        $subplot_arr = array();
        $subplot_arr[] = $subplot;
        if(SUMMARYPLOT) // pokud chci na konci radku vykrestlit sumarni plot vsech fazi
            $subplot_arr[] = array($img->subplotsx-1,$subplot[1]); // posledni subplot v rade je rezervovan pro sumarni

        foreach($subplot_arr as $sp=>$subplot) {
        	   /** @var Image */
            $img->SubplotActivate($subplot); // ROOM TRACK CIRCLE
            $img->Circle(array(0,0),ARENAR,$color,false);
            if(isset($this->trackvars->$framename->cnamevar[$track][$phase])){
            	$img->Text(array(-ARENAR,-ARENAR+10),14,"black","$phase-$trial:$goalno:".$this->trackvars->$framename->cnamevar[$track][$phase]->current());
            	if(FILENAMEKRESLI) $img->Text(array(-ARENAR,-ARENAR+25),14,"black",basename($this->filename));
            }
            if(isset($this->filesettings[$phase][$trial]) && $this->filesettings[$phase][$trial]==EXCLUDEDTRIAL) {
              $img->Text(array(0,0),14,"black","EXCLUDED");
            }
            
            $goal_relative = isset($this->trackvars->RF->reltovar[$track][$phase])?$this->trackvars->RF->reltovar[$track][$phase]->current()>0:false;
            if(!$goal_relative) { //  pozice cile neni urcovana relativne
              $this->angletorotate = 0;
              $this->NakresliCil($img,$track,$phase,$framename,$activeframe);
            } else {
              // pozice cile je urcovana relaticne
              $this->angletorotate = $startpositions[$this->trackvars->RF->reltovar[$track][$phase]->current()-1];
              $this->NakresliCil($img,$track,$phase,$framename,$activeframe,true);
              $this->NakresliStartPoint($img,360-$this->angletorotate); // nevim jestli je absolutni pozice dobre
            }
            $this->NakresliCues($img,$track,$phase,$framename);
            if(isset($this->trackvars->RF->reltovar[$track][$phase])) // ??/
            	$this->NakresliStart($img,$bodxy,$this->trackvars->RF->reltovar[$track][$phase]->current(),$phase,$trial,$sp,$framename);
            $this->NakresliBodMoved($img,$track,$phase,$trial,$framename); // kamil 2.12.2010  otestovat
            if(SECTIONARENA>0) {
            	if(KVADRANTY==SECTIONARENA){ // 3.10.2014 - hranice kvadrantu
            		$cilstred =  $this->$framename->cilc; // nastavuje se v NakresliCil
            		$img->Sections(SECTIONARENA, array(0,0), ARENAR, "blue",true,$cilstred);
            	} else {
            		$img->Sections(SECTIONARENA, array(0,0), ARENAR, "blue",false);
            	}
            }
        }
  }
  /**
   * posune o jeden trial vsechny promenne typu CVarArr
   * volat se bude po kazdem trialu
   * @param int $track
   * @param int $phase
   */
  function PosunVars($track,$phase,$frame="RF"){
          // posunuju v roomframu nebo arenaframu, ktery je posledni
          if(!isset($this->trackvars->$frame->cvar[$track][$phase])) return; //???
  		  $this->trackvars->$frame->cvar[$track][$phase]->next(); // jeste to posunout
          $this->trackvars->$frame->cnamevar[$track][$phase]->next(); // jeste to posunout
          $this->trackvars->$frame->r0var[$track][$phase]->next();
          $this->trackvars->$frame->reltovar[$track][$phase]->next();
          $this->trackvars->$frame->savoidvar[$track][$phase]->next(); //jestli je sector avoid nebo preference
          $this->trackvars->$frame->keyfoundbeepstopvar[$track][$phase]->next(); // akce pri zmacknuti f 12.11.2012 - viz definice
          $this->trackvars->$frame->keyfoundvar[$track][$phase]->next();         // klavesa f 12.11.2012 - viz definice
          
          if(isset($this->trackvars->$frame->laservar[$track][$phase])){ 
          	  // orientacni znacky, taky nemusi byt zacne, treba v darkevel
	          for($l=0;$l<count($this->trackvars->$frame->laservar[$track][$phase]);$l++){
	              $this->trackvars->$frame->laservar[$track][$phase][$l]->next();
	              $this->trackvars->$frame->segmentsvar[$track][$phase][$l]->next();
	              $this->trackvars->$frame->startPointvar[$track][$phase][$l]->next();
	              $this->trackvars->$frame->marknamevar[$track][$phase][$l]->next();
	              if(isset($this->trackvars->$frame->markxyvar[$track][$phase][$l])){ // netvori se u BVA dat
	              	$this->trackvars->$frame->markxyvar[$track][$phase][$l]->next();
	              }
	          }
          }
          if(ARENAFRAME && $frame!="AF"){
          	$this->PosunVars($track,$phase,"AF"); // vola se zamo znovu pro update AF
          }
  }
 
  function setdelim($str){
    return preg_replace("/([0-9])\.([0-9])/","$1".TABLEFILE_DELIM."$2",$str);
  }
  /**
   * spocita envelope - dve rovnobezne primky kolem dvou bodu
   * prvni bod je aktualni
   * druhy bod je referencni - bud zadany, nebo minuly
   *
   * @param [x,y] $bod1
   * @param [x,y] $bod2
   */
  function EnvelopeCompute($bod1,$bod2=false) {
    if(ENVELOPESIZE>0) {

      if($bod2==false) {
        $bod2 = $this->envelopebod;
      } else {
        $this->envelopebody = array($bod2); // zadal jsem referencni bod (asi 0,0), takze mazu seznam bodu envelope
      }
      $this->envelope = primka2rovnobezne(primka2body($bod1,$bod2),ENVELOPESIZE);
      $this->envelopebod = $bod1;
      $this->envelopebody[]=$bod1; // pridam bod do seznamu

      if($this->envelope_table)
          $this->envelope_table->AddSaveRow(array(
                $this->trackvars->track,
			          $this->trackvars->phase,$this->trackvars->trial,$this->trackvars->no,
			          $bod1[0],$bod1[1],
			          $this->envelope[0][0],$this->envelope[0][1],$this->envelope[0][2],
			          $this->envelope[1][0],$this->envelope[1][1],$this->envelope[1][2]
          ));
    /*  fwrite($this->envelopefh,$this->setdelim(
          $this->trackvars->track.COLUMNDELIM
          .$this->trackvars->phase.COLUMNDELIM.$this->trackvars->trial.COLUMNDELIM.$this->trackvars->no.COLUMNDELIM
          .round($bod1[0],4).COLUMNDELIM.round($bod1[1]).COLUMNDELIM
          .round($this->envelope[0][0],4).COLUMNDELIM.round($this->envelope[0][1],4).COLUMNDELIM.round($this->envelope[0][2],4).COLUMNDELIM
          .round($this->envelope[1][0],4).COLUMNDELIM.round($this->envelope[1][1],4).COLUMNDELIM.round($this->envelope[1][2],4).
          "\n"));*/

      if(is_array($this->envelopebody) && count($this->envelopebody)==2) {
        // vytisku si prvni dva body, ze kterych se pocital uhel
        $this->uhlytable->AddSaveRow(array(
            $this->trackvars->track,$this->trackvars->phase,$this->trackvars->trial,
            -1,$this->envelopebody[0][0],$this->envelopebody[0][1],0,0
        ));
        $this->uhlytable->AddSaveRow(array(
              $this->trackvars->track,$this->trackvars->phase,$this->trackvars->trial,
              $this->trackvars->no,$this->envelopebody[1][0],$this->envelopebody[1][1],0,0
        ));
      	/*$output = $this->trackvars->track.COLUMNDELIM.$this->trackvars->phase.COLUMNDELIM.$this->trackvars->trial.COLUMNDELIM
              ."-1".COLUMNDELIM
              .round($this->envelopebody[0][0],4).COLUMNDELIM.round($this->envelopebody[0][1],4).COLUMNDELIM."0".COLUMNDELIM."0\n";
        fwrite($this->fhuhly,$this->setdelim($output));
        $output= $this->trackvars->track.COLUMNDELIM.$this->trackvars->phase.COLUMNDELIM.$this->trackvars->trial.COLUMNDELIM
              .$this->trackvars->no.COLUMNDELIM
              .round($this->envelopebody[1][0],4).COLUMNDELIM.round($this->envelopebody[1][1],4).COLUMNDELIM."0".COLUMNDELIM."0\n";
        fwrite($this->fhuhly,$this->setdelim($output));*/
      }
    } else {
      $this->envelope = false;
      $this->envelopebod = false;
    }
  }

	/**
	 * pokud je bod mimo kruh areny, nastavi novou vzdalenost od stredu
	 *
	 * @param [x,y] $bod
	 * @param float $diam
	 * @return [x,y]
	 */
	function ChangeDiam($bod,$r){
	  if(distance($bod,array(0,0)) > ARENAR){
	    $angle = angle($bod);
	    return angledist2xy($angle,$r);
	  } else {
	   return $bod;
	  }
	}
	/**
	 * @param Image $imgno
	 * @param [x,y] $bod
	 * @param string $frame
	 */
	private function UlozKvadranty($imgno,$bod,$frame="RF"){
	  if($imgno == 0) { // kamil 27.10.2008
	  	  if(is_array($this->$frame->cilxy) && defined('SPOJITCILETRIALS') && SPOJITCILETRIALS!=""){
	  	  	// 21.10.2014 kvuli probetrialum ve vFGN
	  	  	 $goals=$this->$frame->cilxy;
	  	  	 $vzdalesnostodcile = 1000000;
	  	  	 if(!is_array($this->distancefromgoal)) $this->distancefromgoal = array();
	  	  	 foreach($goals as $goalname=>$cilxy){ // nazvy prvku jsou 0 1 2
	  	  	 	$vzdalesnostodcile = min($vzdalesnostodcile,distance($cilxy,$bod)); //  vzdalenost k nejblizsimu cili
	  	  	 	if(isset($this->distancefromgoal[$goalname])){
	  	  	 		$this->distancefromgoal[$goalname] += distance($cilxy,$bod);
	  	  	 	} else {
	  	  	 		$this->distancefromgoal[$goalname] = distance($cilxy,$bod);
	  	  	 	}
	  	  	 	$uhel = anglediff($cilxy,$bod);
	      		$this->CKvadranty->Add($uhel,$goalname);
	  	  	 }
	  	  	 if(isset($this->distancefromgoal['vsechny'])){
	  	  	 		$this->distancefromgoal['vsechny'] += $vzdalesnostodcile;
	  	  	 } else {
	  	  	 		$this->distancefromgoal['vsechny'] = $vzdalesnostodcile;
	  	  	 }
	  	  	 $cilxy = reset($goals);// pro dalsi vypocty pouziju prvni z cilu
	  	  } elseif(is_array($this->$frame->cilxy)){
	  	  	$cilxy = reset($this->$frame->cilxy); // muze byt pole nekolika cílù, už je Cpoint
	  	  	$this->distancefromgoal+=distance($cilxy,$bod);
	  	  	$uhel = anglediff($cilxy,$bod);
	      	$this->CKvadranty->Add($uhel);
	  	  } else {
	  	  	$cilxy = $this->$frame->cilxy;
	  	  	$this->distancefromgoal+=distance($cilxy,$bod);
	  	  	$uhel = anglediff($cilxy,$bod);
	      	$this->CKvadranty->Add($uhel);
	  	  }
		  //$cilxy = is_array($this->$frame->cilxy)?reset($this->$frame->cilxy):$this->$frame->cilxy; 
	      $bodpoint = new CPoint($bod);
	      $startpoint = new CPoint($this->$frame->startxy);
	      if($bodpoint->Distance($startpoint)>=ANGLEMINDIST){ // v tomto pripade ziskavam nesmysle vysoke uhly
	      	if($this->anglefromgoal==0) $this->frameanglestart = $this->trackvars->no-1; // musi to byt -1, protoze tento bod se pocita do targetcorridor
	      	$anglediff = abs($bodpoint->AngleDiff($cilxy,$startpoint));
	      	$this->anglefromgoal += $anglediff;
	      	if($anglediff<=TARGETCORRIDORANGLE) $this->targetcorridor++;
	      }
	      if(isset($this->$frame->lastxy[$imgno])) {
	          $this->$frame->trialdistance+=distance($this->$frame->lastxy[$imgno],$bod);
	          //if($frame=="RF") error_log($this->$frame->trialdistance."\t$bod[0]\t$bod[1]\t".distance($this->$frame->lastxy[$imgno],$bod)."\n",3,"./trialdistance.log");
	      }
	
	      
	      $this->LandmarkProximity($bod,$frame); // 10.7.2014
	    }
	}
	/**
	 * uklada radku s udajema o vstupu do cile do vystupni tabulky
	 * 
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @param CZasah $zasah
	 */
	private function UlozCilTxt($track,$phase,$trial,$zasah,$activeframe){
	  $framename = CDrf2Track_frame::FrameName($activeframe);
	  $output = '';
	  foreach(array_keys($zasah->GoalArray()) as $goalname){
      $zasah->SetGoal($goalname);
	  	//foreach($cilxyarr as $name=>$cilxy){
	    if(MATLAB) {$filename = str_replace(" ","_",$this->filename);} else {$filename = $this->filename; }
      //"datum".COLUMNDELIM."soubor".COLUMNDELIM."track".COLUMNDELIM."faze".COLUMNDELIM."trial".COLUMNDELIM."name".COLUMNDELIM."cilx".COLUMNDELIM."cily".COLUMNDELIM."bodx".COLUMNDELIM."boxy".COLUMNDELIM."trialdist".COLUMNDELIM."trialangle".COLUMNDELIM."trialangleabs".COLUMNDELIM."mindist".COLUMNDELIM."time".COLUMNDELIM."angleerr".COLUMNDELIM."disterr".COLUMNDELIM."distfromgoal".COLUMNDELIM."avgdistfromgoal".COLUMNDELIM."cilovykvadrant".COLUMNDELIM."kvadrant1".COLUMNDELIM."kvadrant2".COLUMNDELIM."kvadrant3\n"
      $this->txttable->AddRow(array_merge(array(
              date("j.n.Y H:m:i"),basename($filename),$track,$phase,$trial,$activeframe,
              $zasah->goalname,
              $this->marks_names_string($track,$phase),//MWM2 
              $this->marks_count($track,$phase), //MWM2 
              $this->marks_group($track,$phase), //MWM2 
              ($this->trialsetting($phase,$trial)==EXCLUDEDTRIAL?"excluded":$zasah->goaltype), //MWM2 
              $zasah->goal->x,$zasah->goal->y,
              $zasah->hit->x,$zasah->hit->y,
              $zasah->start->x,$zasah->start->y,
              $this->$framename->trialdistance,//dist,
              $this->trialangle,//angle,
              $this->trialangleabs,//angleabs,
              $zasah->mindist(),
              ($patheff=$zasah->path_efficiency($this->$framename->trialdistance)),
              $this->trackvars->TimeInfo()?
                  $this->timemax - $this->timestart:
                  $this->trackvars->FramesToSec($this->framemax)   ,//time v sekundach
              //$this->Timeformat($this->framemax/25).COLUMNDELIM.//time
              $zasah->angleerr(), // angleerr
              $zasah->disterr(), // disterr
              $zasah->distfromgoal(), //distfromgoal
              $zasah->distsymmetry(), //distsymmetry //MWM2 
              ($avgdist=$this->Avgdistfromgoal($goalname)), //avgdistfromgoal
              $zasah->distfromcenter(),  //distfromcenter //MWM2 
              $zasah->angleerrfromcenter(), //angleerrfromcenter //MWM2 
              $zasah->angleerrfromcenter_cm(),//angleerrfromcenter_cm  - cast kruhu na urovni odhadu cile //MWM2 
              $zasah->distfromreference(), //16.7.2010 //MWM2 
              $zasah->angleerrfromreference(), //MWM2 
              (is_array($this->RF->cilxy)?$goalname:''), // jmeno cile, pokud je jich vic
              ),
              ($kvadranty=$this->CKvadranty->Podily($goalname)), // tohle je array
              array(
              	$this->PointingError($phase,$trial,$zasah,$framename),// uhlova chyba ukazani na cil ze startu - 26.3.2012
              	empty($this->pointingtime[$phase][$trial]) ?0:$this->pointingtime[$phase][$trial],
              	$this->maxtime($track, $phase, $trial),
              	$this->vstupudocile,
              	($avgangle = $this->anglefromgoal/($this->framemax-$this->frameanglestart)), //avganglefromgoal
              	($koridor=$this->targetcorridor/($this->framemax-$this->frameanglestart)), // podil casu v target corridor
              	($markproxim=$this->markproximal/($this->framemax-$this->framestart)), // podil casu, kdy je clovek blizko ke znacce
              	$this->Strategy($patheff, $avgdist, $avgangle, $kvadranty, $koridor,$markproxim) // strategie hledani cile
              	) 
              )             
      );
      
      if(SHOWPARAMS){ // nastavitelne zobrazeni z filelistu
     	 $this->texttowrite = 
      		"P:".round($patheff,2) // primo to zde pocitat nejde - cisla jsou vzdy vetsi nebo stejne - nejaka zavislost?
      		.", D:".round($avgdist,2)
      		.", Q:".round($kvadranty[0],2)
      		.", A:".round($avgangle,2)
      		.", K:".round($koridor,2)
      		."; M:".round($markproxim,3)
      		.", G:".round($this->pathcentroiddist,2)
      		.", TrialDist:".round($this->$framename->trialdistance,2);
      }
      // zobrazeni strategie hledani do obrazku
      $this->texttowrite2 = $this->Strategy($patheff, $avgdist, $avgangle, $kvadranty, $koridor,$markproxim);
      /*
      $this->txttable->AddRow(array(
              date("j.n.Y H:m:i"),$filename,$track,$phase,$trial,
              $this->trackvars->cnamevar[$track][$phase]->current(),
              $this->marks_names_string($track,$phase),
              $this->marks_count($track,$phase),
              $this->marks_group($track,$phase),
              $this->trackvars->goaltypearr[$track][$phase][$trial],
              $cilxy[0],$cilxy[1],
              $bod[0],$bod[1],
              $this->startxy[0],$this->startxy[1],
              $this->trialdistance,//dist,
              $this->trialangle,//angle,
              $this->trialangleabs,//angleabs,
              distance($this->startxy,$cilxy), //mindist
              $this->trackvars->TimeInfo()?
                  $this->timemax - $this->timestart:
                  $this->trackvars->FramesToSec($this->framemax)
                ,//time v sekundach
              //$this->Timeformat($this->framemax/25).COLUMNDELIM.//time
              anglediffcenter($bod,$cilxy,$this->startxy), // angleerr
              distance($bod,$this->startxy)-distance($cilxy,$this->startxy), // disterr
              ($distfromgoal = distance($cilxy,$bod)), //distfromgoal
              min($distfromgoal,distance($cilxy,$this->get_symetry_point($track,$phase,new CPoint($bod)))), //distsymmetry
              $this->distancefromgoal/$this->framemax, //avgdistfromgoal 
              distance($bod,array(0,0))-distance($cilxy,array(0,0)), //distfromcenter
              anglediffcenter($bod,$cilxy,array(0,0)), //angleerrfromcenter
              distance($bod,array(0,0))*deg2rad(anglediffcenter($bod,$cilxy,array(0,0))),//angleerrfromcenter_cm  - cast kruhu na urovni odhadu cile
              $this->cilovykvadrant/$this->framemax, //cilovykvadrant
              $this->kvadranty[1]/$this->framemax,
              $this->kvadranty[2]/$this->framemax,
              $this->kvadranty[3]/$this->framemax,
              (is_array($this->cilxy)?$name:'') // jmeno cile, pokud je jich vic
              
      )); 
        
        
       */
	  }
	}
	/**
	 * ulozi HTML soubor s odkaze na obrazek SVG
	 */
	private function SaveHtml(){ 
		if(SAVEHTML){ // vetsinou nebudu uklada individualni HTML s odkazem na obrazek  - 14.11.2014
			$html = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
			<html>
	  		<head>
	  			<meta http-equiv=\"content-type\" content=\"text/html; charset=windows-1250\">
	  			<title>{$this->filename}</title>
	  		</head>
	  		<body>";
			foreach($this->htmlembed_data as $frame=>$data){
				$html .="<h1>$frame</h1>\n";
				foreach ($data as $track=>$image){
					$html .="<h2>Track $track - ".basename($image['filename'])."</h2>\n";
					$html .="<embed src=\"../$image[filename]\" width=\"$image[sizex]\" height=\"$image[sizey]\" type=\"image/svg+xml\"
						 pluginspage=\"http://www.adobe.com/svg/viewer/install/\" /><br>\n";
				}
			}
			$html .="</body></html>";
			file_put_contents($this->filename_towrite(HTMLDIR).".html",$html);
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
	/*public function WholeImage(){
	  if(isset($this->cilearr) && is_array($this->cilearr)){
	    foreach($this->cilearr as $track=>$trackdata){
	      $img = new Image()

	    }


	  } else {
	    echo "WholeImage: prazne pole cilearr";
	  }
	}*/
	public function Cile(){
	  if(isset($this->cilearr) && is_array($this->cilearr)){
      return $this->cilearr;
	  } else {
	    echo "Drf2track.Cile: prazne pole cilearr";
	  }
	}
	public function GoalRadius(){
	  if(isset($this->trackvars->RF->r) && is_array($this->trackvars->RF->r)){
      return $this->trackvars->RF->r;
	  } else {
	     echo "Drf2track.GoalRadius: prazne pole trackvars->r";
	  }
	}
	function __destruct(){
	   unset($this->trackvars); // to se predava referenci, takze se nerusi tim dalsim unset
	}
	/**
	 * vrati bod prevraceny podle spojnice stredu areny a znacky/startu
	 * 21.5.2010
	 * 
	 * @param int $track
	 * @param int $phase
	 * @param CPoint $cilxy
	 * @return CPoint
	 */
	private function get_symetry_point($track,$phase,$cilxy){
		// chci ziskat aktualni pozici znacky nebo startu (pokud neni definovana zadna znacka)
		// pokud bude vic znacek, zatim beru jen tu s nejnizsim cislem (v poli trackvars->segmentsvar)
		// vratim bod[x,y]
		$index = 0; // prvni $l se pouzije, pokud bude znacka nekde vyse, tak se pouzije ta
		if(isset($this->trackvars->RF->startPointvar[$track][$phase])){
			foreach($this->trackvars->RF->startPointvar[$track][$phase] as $l=>$startpoint){
				if($startpoint->current()==0){// je to znacka, tu pouziju
	        $index = $l;
				}
			}
					
			$pozice = new CPoint();
			$pozice->DefineByAngleDistance($this->trackvars->RF->laservar[$track][$phase][$index]->current(), ARENAR);
			$line = new CLine();
			$line->DefineByPoints($pozice,new Cpoint(0,0));
			return $line->Symmetry($cilxy);
		} else {
			return $cilxy;
		}
	}
	/**
	 * vrati spojena jmena aktualnich znacek napr L1L5 (u BVA) 
	 * @param int $track
	 * @param int $phase * @param int $j 
	 * @return string
	 */
	private function marks_names_string($track,$phase,$j=false){
		$name = "";
		if(isset($this->trackvars->RF->marknamevar[$track][$phase])){
			foreach($this->trackvars->RF->marknamevar[$track][$phase] as $l=>$data){
				if($j===false)
					$startpoint = $this->trackvars->RF->startPointvar[$track][$phase][$l]->current();
				else 
					$startpoint = $this->trackvars->RF->startPointvar[$track][$phase][$l]->val($j);
				
				if($startpoint==0) // starty nechci
				   $name .= $j===false?$data->current():$data->val($j);
			}
		}
		return $name;
	}
	/**
	 * vrati pocet znacek ve fazi
	 * @param int $track
	 * @param int $phase
	 * @return int
	 */
	private function marks_count($track,$phase){
		if(isset($this->trackvars->RF->laservar[$track][$phase])){
			return count($this->trackvars->RF->laservar[$track][$phase]);
		} else {
			return 0; // taky nemusi byt definovane zadne znacky, jak v dark evel - 5.3.2012
		}
	}
	/**
	 * vytvori a vrati pole se skupinami pokusu - tech ktere maji max pocet znacek (oznacene M)
	 * format pole je [$track][]=name
	 * @param int $track
	 * @xparam int $phase
	 * @return array
	 */
	private function trial_groups($track){
		if(empty($this->trial_groups_arr[$track])){
			$data = array();
			$mark_max = 0;
			$pocet = SPANAVDATA ? $this->trackvars->counts[$track][0]:count($this->counts[$track]); //fazi u BVA, trialu u Spanav
			for($j=0;$j<$pocet;$j++){
				$data[$j]['name'] = $this->marks_names_string($track,SPANAVDATA?0:$j,SPANAVDATA?$j:false);
				$data[$j]['pocet'] = substr_count($data[$j]['name'],"M");
				$mark_max = max($mark_max,$data[$j]['pocet']);
			}
			$this->trial_groups_arr[$track]= array();
			foreach($data as $j=>$d){
				if($d['pocet']==$mark_max && !in_array($d['name'],$this->trial_groups_arr[$track]))	
					$this->trial_groups_arr[$track][]=$d['name'];
			}
		}
		return $this->trial_groups_arr[$track];
	}
	/**
	 * vraci jmeno skupiny trialu pro aktualni sestavu znacek, treba pro M1 vrati M1M2M3
	 * @param int $track
	 * @param int $phase
	 * @return string
	 */
	private function marks_group($track,$phase){
		$name = $this->marks_names_string($track,$phase);
		$groups = $this->trial_groups($track);
		foreach ($groups as $groupname) {
			 $groupname_arr = explode("M", substr($groupname,1));
			 $name_arr = explode("M",substr($name,1));
	     if(count(array_intersect($name_arr,$groupname_arr))>0) 	  return $groupname; // skupina 
		} 
		return ''; // zadna skupina se nenasla
	}
	/**
	 * vrati pole pozic znacek v dane fazi
	 * [markname]=(laser,segment,markname)
	 * laser je ve stupnich 0-360
	 * 
	 * @param int $track
	 * @param int $phase
	 * @return array
	 */
	private function marks_positions($track,$phase){
		$marks = array();
		// 22.11.2012 - virtualnich 16 znacek kolem dokola
		if(isset($this->trackvars->RF->laservar[$track][$phase])){
		  foreach($this->trackvars->RF->laservar[$track][$phase] as $l=>$data){
			  $markname = $this->trackvars->RF->marknamevar[$track][$phase][$l]->current();
			  $startpoint = $this->trackvars->RF->startPointvar[$track][$phase][$l]->current();
		      if($startpoint==0 && strlen(trim($markname))>0 && $data->current()>=0){ // starty nechci
		      	 // a taky nechci lasery, kde je laser -1 - 29.7.2010
		      	 $xy = ($this->trackvars->RF->markxyvar[$track][$phase][$l]->count()>0) // u BVA dat neni
		      	 	?$this->trackvars->RF->markxyvar[$track][$phase][$l]->current():false;
		         $marks[$markname] = array (
		           "laser"=>$data->current(),
		           "segment"=>$this->trackvars->RF->segmentsvar[$track][$phase][$l]->current(),
		           "markname"=>$markname,
		           "xy"=>$xy, //CPoint - xy pozice znacky, pouze u SpaNav dat, kladne y dolu
		           "radius"=>$this->trackvars->markradius/100*ARENAR // polomer znacky v procentech polomeru areny - pouze u SpaNav dat 
		         		// prepoctu na velikost v arena 140
		         );
		      }
	    	}
		}
		if(LASERS!='')	$lasers = array_map('floatval', explode(",",LASERS)); //25.4.2013 - prevedl jsem definice do CONST ve filelistu
		//$lasers = array(0,22.5,45,67.5,90,112.5,135,157.5,180,202.5,225,247.5,270,292.5,315,337.5);  // 16 znacek po 22stupnich kolem dokole
		if(isset($lasers)){
			//nejdriv doplnim pocet znacek do 3 - kvuli matlabu, aby virtualni znacky zacinaly na stejnem sloupci
			for($i=0;count($marks)<3;$i++){
				$chars = array("A","B","C");
				$markname = $chars[$i]."0";
				$marks[$markname]=array ("laser"=>0,"segment"=>1,"markname"=>$markname,"xy"=>new CPoint(),"radius"=>0);
			}
			// vypocitam vzdalesnot znacek od stredu. Vezmu k tomu xy pozici prvni ze skutecnych znacek
			if(isset($this->trackvars->RF->markxyvar[$track][$phase])){
		  		$mark = reset($this->trackvars->RF->markxyvar[$track][$phase]); // pozice prvni znacky
		  		$markxy = $mark->current();
		  		$distance = $markxy->Distance(); // vzdalesnot znacky od stredu 0,0
		  	} else { 
		  		$distance = 0; // pokud nejsou definovany skutecne xy pozice, nevim vzdalenost od stredu
		  	} 
			foreach($lasers as $l){ // vlozim vsech 16 znacek
				$markname="X$l";
				$markxy = new CPoint();
				$markangle = 360-($this->trackvars->RF->cvar[$track][$phase]->current()-$l); //cvar ma kladne y nahoru, takze i tohle kladne y nahoru
				$markxy->DefineByAngleDistance($markangle, $distance); 
			  	$marks[$markname] = array ("laser"=>$markangle,"segment"=>1,"markname"=>$markname,"xy"=>$markxy,"radius"=>$this->trackvars->markradius/100*ARENAR);
			  	// ??? xy musim prevrati na kladne dolu, i ostatni znacky to tak maj
			  	// znacka X0 bude na pozici cile, X180 naproti, X90 vlevo, kdyz se divam od cile
			}
		}
		return $marks;
	}
	/**
	 * vrati pozici startu ve stupnich 0-360
	 * 
	 * @param $track
	 * @param $phase
	 * @return deg
	 * 
	 */
	private function start_position($track,$phase){
		if(isset($this->trackvars->RF->startPointvar[$track][$phase])){
		   foreach($this->trackvars->RF->startPointvar[$track][$phase] as $l=>$startpoint){
		        if($startpoint->current()==1){// je to start
		          return $this->trackvars->RF->laservar[$track][$phase][$l]->current();
		        }
	      }
		} else {
			// vratim pozici zmacknuti c v prvnim trialu
			$trial = 0;
			$frame = array_search('c',$this->trackvars->klavesyarr[$track][$phase][$trial]);
			if($frame===false){
				return -1;
			} else {
	  			$startpoint = new CPoint($this->trackvars->roomxyarr[$track][$phase][$trial][$frame]);
		   		return $startpoint->Angle(); 
			}
		}
	}
	/**
	 * vrati jmeno trialu/goalu
	 * pokud je definovana skupina tak vrati name|group
	 * 
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @return string
	 */
	private function GoalName($track,$phase,$trial){
		// pokud je jmeno definovane v const TRIALNAMES, tak pouziju tu
		if (isset($this->filesettings['trialnames'])){ // pokud mam definovany trial name pro tento file
			$name=$this->filesettings['trialnames']->Name($phase,$trial,1); // jedna se o objekt CTrialNames
		}
		if(empty($name)){
			$name = $this->trialnames->Name($phase,$trial,true);
		}
		if(!empty($name)) {
		  return $name;
		} else { 
		  // jinak pouziju pojmenovani z tracku
		  if(isset($this->trackvars->RF->cnamevar[$track][$phase])) // ???
		  	return $this->trackvars->RF->cnamevar[$track][$phase]->current();
		  else 
		  	return 'not defined';
		}
	}
	/**
	 * vrati settings (exlude) ke konkretnimu trialu
	 * @param int $phase
	 * @param int $trial
	 */
	private function trialsetting($phase,$trial){
		if(isset($this->filesettings[$phase][$trial]))
		  return $this->filesettings[$phase][$trial];
		else 
		  return false;
	}
	/**
	 * vraci true, pokud mam zpracovat track do tabulky
	 * true, pokud neni zadne settings k track nebo pokud v nich je $track
	 * 
	 * @param int $track
	 * @return bool
	 */
	private function track_setis($track){
		if(!isset($this->filesettings['track'])){
			return true;
		} elseif($this->filesettings['track']==$track){
			return true;
		} else {
			return false;
		}
	}
	/**
	 * nakresli krouzek kolem bodu, kde se clovek rozesel 
	 * @param Image $img
	 * @param [x,y] $bod
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @param string $frame RF nebo AF
	 */
	private function NakresliBodMoved(&$img,$track,$phase,$trial,$frame){
			if(!empty($this->trackvars->$frame->movedxyarr[$track][$phase][$trial])) {
				$img->Circle($this->trackvars->$frame->movedxyarr[$track][$phase][$trial],10,"red",false,1);
		  }
	}
	/**
	 * vrati maximalni hodnotu casu v trialu z pole trackvars->timearr; relativne k casu zacatku soucasneho trialu
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 */
	private function maxtime($track,$phase,$trial){
		if($this->trackvars->TimeInfo()){
			$casy = $this->trackvars->timearr[$track][$phase][$trial];
			$minkey = min(array_keys($casy));//indexy casu jsou integery, takze tohle by melo vratit 0
			$maxkey = max(array_keys($casy)); // tohle je posledni index casu
			return $casy[$maxkey]- $casy[$minkey];//$this->timestart;
		} else {
			return 0; // nejsou casove udaje
		}
	}
	/**
	 * vrati chybu ukazani podle $this->pointingangle
	 * @param int $phase
	 * @param int $trial
	 * @param CZasah $zasah
	 * @return deg
	 */
	private function PointingError($phase,$trial,$zasah,$frame){
		if(empty($this->pointingangle[$phase][$trial]))
			return 0;
		else {
			$zasahP = clone $zasah; // cil i start maji Y kladne dolu, musim je prevratit pro pocitani uhlu - 10.4.2012
			if(is_array($this->$frame->cilxy) && defined('SPOJITCILETRIALS') && SPOJITCILETRIALS!=""){
				// vFNG - kategorialni chyba v ukazani v probetrialu
				$goalxy = $this->$frame->cilxy;
				$chybacil = array();
				foreach($goalxy as $key=>$g){ // prvni cil v poradi je ten aktualni spravny
					$uhelcil = $g->ReverseY()->Angle($zasah->start->ReverseY());
					$chybacil[$key] = Angle::Normalize($this->pointingangle[$phase][$trial]- $uhelcil,true);
				}
				if(is_array($goalxy) && count($goalxy)>=3){
					return ( abs($chybacil[0])<abs($chybacil[1]) && abs($chybacil[0])<abs($chybacil[2])) ? 0:1;
				} else {
					return $chybacil[0];
				}
				
			} else {
				$uhelcil =$zasahP->goal->ReverseY()->Angle($zasah->start->ReverseY());
				return Angle::Normalize($this->pointingangle[$phase][$trial]- $uhelcil,true);
			} 
		}
	}
	/**
	 * vrati prumernou vzdalesnot od cile;
	 * vytvoreno hlavne pro pripad, kdy je vice cilu v trialu - probe trial ve vFGN
	 * @param mixed $goalname jmeno cile
	 * @return number
	 * @since 26.11.2014
	 */
	private function Avgdistfromgoal($goalname=0){
		if(is_array($this->distancefromgoal)){
			return $this->distancefromgoal[$goalname]/($this->framemax-$this->framestart);
		} else {
			return $this->distancefromgoal/($this->framemax-$this->framestart);
		}
	}
	/**
	 * ulozi uhel pohledu nebo smeru cloveka pro zpracovani v histogramu
	 * @param deg $angle
	 * @param [x,y] $pozice predpoklada se kladne y dolu
	 * @param deg $angleofview rozpeti uhlu, ktere ukladat do histogramu od angle
	 * 
	 * @since 9.10.2012
	 */
	private function saveanglehisto($angle,$pozice,$angleofview=false){
		$bodP = new CPoint($pozice); // zde ma bod kladne y dolu, tak ho musim obratit
      	$cil = is_array($this->RF->cilxy)?clone $this->RF->cilxy[0]:clone $this->RF->cilxy;
      	$track = $this->trackvars->track;
      	$phase = $this->trackvars->phase;
      	$trial = $this->trackvars->trial;
	    $this->viewangle->AddAngle($angle,$trial,  $phase,
			     $this->trackvars->TimeInTrial(), //->timeFramesToSec($this->trackvars->no) /*time*/, 
		   	     $bodP->ReverseY(),$cil->ReverseY(),
		   	     $angleofview
		   	     );
		   	     // ve vyslednem histogramu je cil 180=dole, 0 nahore, 270 vpravo, 90 vlevo
	}
	/**
	 * vrati souradnice prvniho a posledniho bodu tracku aktualniho trialu
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @return array of CPoint
	 * @since 29.11.2012
	 */
	private function roomxylast($track, $phase, $trial){
		$key_max = max(array_keys($this->trackvars->roomxyarr[$track][$phase][$trial]));
		// 3.12.2012 jsem pridal jeste prvni bod tracku
		$key_min = min(array_keys($this->trackvars->roomxyarr[$track][$phase][$trial]));
		$ret = array(
			new CPoint($this->trackvars->roomxyarr[$track][$phase][$trial][$key_min]),
			new CPoint($this->trackvars->roomxyarr[$track][$phase][$trial][$key_max])
			);
		$ret[0]->ReverseY(); //taky maji kladne y dolu, obratim pro zpracovani ve ViewAngle
		$ret[1]->ReverseY();
		return $ret;
			
	}
	/**
	 * potrebuju zapouzdrit
	 * @since 30.5.2014
	 * @param Image $img
	 */
	private function NakresliTextToWrite(&$img){
	 	if(!empty($this->texttowrite)){
          	// kdyz napisu primo $img[0]->Text hazi to porad chybu o dirty region
           	if(strpos($this->texttowrite,";")!=false){
           		$parts = explode(";",$this->texttowrite);
           		$x = 0;
           		foreach($parts as $part){
           			$img->Text(array(-130,$x),14,"black",$part);
           			$x+=20;
           		}
           	} else {
           		$img->Text(array(-130,0),14,"black",$this->texttowrite);
           	}
          	$this->texttowrite = "";
        }
		if(!empty($this->texttowrite2)){
          	// kdyz napisu primo $img[0]->Text hazi to porad chybu o dirty region
           	$img->Text(array(-50,120),14,"black",$this->texttowrite2);
          	$this->texttowrite2 = "";
         }
	}

	/**
	 * vrati retezec urcujici strategii hledani cile
	 * @param float $patheff
	 * @param float $avgdist
	 * @param float $avgangle
	 * @param array $kvadranty
	 * @param float $koridor
	 * @return string
	 * @since 2.6.2014
	 */
	private function Strategy($patheff,$avgdist,$avgangle,$kvadranty,$koridor,$markproxim){
		if(DSWIM!=0 && $patheff>=DSWIM){ 
			$strategy = "DSwim";
		} elseif(MARKSTRATEGY!=0 && $markproxim>=MARKSTRATEGY && $koridor < 1) {
			// 16.7.2014 - pri cue strategy nesmeruje primo k cili - alespon chvili je mimo koridor			
			$strategy =  "MarkStrategy"; //uz neresim jine strategie
		} else {
			$s = array();
			$fsearch = (FSEARCH!=0) ? explode(",",FSEARCH): array(0,0);   
			if(!empty($fsearch[0]) && $avgdist<=$fsearch[0])  	$s[]="FSearch";
			if(!empty($fsearch[1]) && $kvadranty[0] >= $fsearch[1]) $s[]="FSearch";
			
			if(count($s)==0){ // chci udrzet poradi strategii Fsearch, Fincorrect, Dsearch
				// strategie Focal Incorrect, 3.10.2014
				$fincorrect = (FINCORRECT!=0) ? explode(",",FINCORRECT): array(0,0,0);   //| distance to centroid max non-goal quadrants 
				if(!empty($fincorrect[0]) && !empty($fincorrect[1])){
					if(	$this->pathcentroiddist<= $fincorrect[0] 			/* path centroid*/
						&& max(array_slice($kvadranty, 1)) >$fincorrect[1]/*maximum z necilovych kvadrantu*/
						//&& $avgangle >  $fincorrect[2]	        /*uhlova chyba*/				
					){
						$s[]="FIncorrect"; //$avgangle>$fincorrect[0]
					}
				}
			}
			if(count($s)==0){
				$dsearch = (DSEARCH!=0) ? explode(",",DSEARCH): array(0,0);   	
				if(!empty($dsearch[0]) && $avgangle<=$dsearch[0])  	$s[]="DSearch";
				if(!empty($dsearch[1]) && $koridor >=$dsearch[1] ) 	$s[]="DSearch";
			}
			if(count($s)==0) $s[]="None";
			
			$s=array_unique($s);
			$strategy = implode(",", $s);
		}
		return $strategy;
	}
	/**
	 * zvysuje this->markproximal, pokud se clovek nachazi blizke jakekoliv znacky
	 * @param [x,y] $bod
	 * @param string $frame
	 */
	private function LandmarkProximity($bod,$frame){
		$marks = $this->marks_positions($this->trackvars->track, $this->trackvars->phase);
		$cilxy = is_array($this->$frame->cilxy)?reset($this->$frame->cilxy):$this->$frame->cilxy;
		$distmin = 10000; // minimalni vzdalestno od znacky k cili
		$startxy = $this->$frame->startxy;
		
		foreach($marks as $m){
			$distmin = min($distmin,distance($cilxy,$m['xy']));
		}
		
		foreach($marks as $m){
			if(distance($bod,$m['xy'])<$distmin/2){ 
			// pokud je aktualni vzdalenost k jakekoliv znacce mezi nez polovina vzdalenosti cile k nejblizsi znacce
				   if($bod==$startxy) {
				   	 $this->markstart=true; // je na startu a soucasne blizko u znacky
				     break;
				   } elseif(!$this->markstart) { 
					 $this->markproximal++; // 
					 break;
				   }
			}
		}
		if($bod!=$startxy) $this->markstart=false; // neni blizko u znacky a soucasne neni na startu - resetuji priznak
		
	}
	/**
	 * vraci stred trajektorie z jednoho trialu a ulozi ho do $this->pathcentroiddist
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @return float
	 * @since 6.1.2014
	 */
	private function PathCentroidDistance(&$img,$track,$phase,$trial){
		$xSum = 0; $ySum = 0;
		$pocet = 0;
		foreach($this->trackvars->roomxyarr[$track][$phase][$trial] as $frame=>$xy){
			if($frame>=$this->framestart){ // pokud na zacatku ukazoval na cil, chci to pocitat az odsud
				$xSum += $xy[0];
				$ySum += $xy[1];
				$pocet ++;
			}
		}
		$gravity = array($xSum/$pocet,$ySum/$pocet);
		/*
		$subplot = $this->subplotxy($track,$phase,$trial);
        $img->SubplotActivate($subplot); // ROOM TRACK
        $this->NakresliBod($img,$gravity,0,0,"GRAVITY"); // cisla plotu jsou zatim asi jen 0 a 1
        15.10.2014  - kresleni zasahuje do vypoctu patherr a dela ji mnohem mensi. 
        takze zruseno 
        */
        $distance = 0;
		foreach($this->trackvars->roomxyarr[$track][$phase][$trial] as $frame=>$xy){
			if($frame>=$this->framestart){
				$distance += distance($xy,$gravity);
			}
		}
		$this->pathcentroiddist = $distance/$pocet;
		return $this->pathcentroiddist;
        
	}
	/**
	 * spocita a ulozi do txt souboru histogram z tracku, kvuli probetrialum ve vFGN a pohlavnim rozdilum
	 * 
	 * @param int $track
	 * @param int $phase
	 * @param int $trial
	 * @since 10.11.2014
	 */
	private function TrackHisto(){
		$TrackExp = new TrackExport(); // 2018-05-23 - nova trida na export tracku
		foreach($this->trackvars->roomxyarr as $track=>$trackdata){
			foreach($trackdata as $phase=>$phasedata){
				foreach($phasedata as $trial=>$trialdata){
					$klavesy = $this->trackvars->klavesyarr[$track][$phase][$trial];
					$Histo = new Histogram2D(array('min'=>-140,'max'=>140,'count'=>30), array('min'=>-140,'max'=>140,'count'=>30));
					$time = $this->trackvars->timearr[$track][$phase][$trial];				 
					foreach($trialdata as $n=>$xy){
						if(isset($klavesy[$n]) && $klavesy[$n]=='s'){
							$Histo->Reset();
						}
						$Histo->AddValue($xy[0], $xy[1]);
						if(isset($this->trackvars->roomxyavg[$track][$phase][$trial][$n])){
							$xyavg = $this->trackvars->roomxyavg[$track][$phase][$trial][$n];
						} else {
							$xyavg = array(0,0);
						}
						$TrackExp->AddPoint($xy, $n, $trial, $phase, $track, $xyavg, $time[$n], !empty($klavesy[$n])?$klavesy[$n]:false);
					}
					if(TRACKHISTO){
						$Histotable = $Histo->FreqTable();
						$Histotable->SetPrecision(false);
						$Histotable->setDelims("\t", '.');
						$filename = dirname($this->filename)."/trackhisto/".basename($this->filename);
						$Histotable->SaveAll(true,"{$filename}_trackhisto_{$track}_{$phase}_{$trial}.xls");
					}
					
				}
			}
		}
		if (TRACKEXPORT) {
			$TrackExp->Export($this->filename);
		}
	}
}
?>