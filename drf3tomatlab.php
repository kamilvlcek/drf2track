<?php



// seznam poli:
// Time  Frame ArenaLoc.X  ArenaLoc.Y, PlatformAngle PlatformLoc.X  PlatformLoc.Y View.X pauza, 
// cislo aktivniho mista, vstup do mista, attached to platform?, jestli zrovna stlacena klavesa s, 
// pocty vstupu ArenaMarkGoal RoomMarkGoal RoomPlaceGoal ArenaPlaceGoal stred

/*
CeskaJmena['RoomMarkGoal']="MISTNOST 2";
CeskaJmena['RoomPlaceGoal']="MISTNOST 1"; // 1 a 2 znaci pozici v poli Goals (nemusi to tak byt, ale pro citelnost)
CeskaJmena['ArenaMarkGoal']="ARENA 2";
CeskaJmena['ArenaPlaceGoal']="ARENA 1";
*/

/*if(EXPERIMENT == 'KarelKamil') $filelist = 'aapp.txt'; // experiment s Karlem, leden-unor 2011
elseif(EXPERIMENT=='aappII') $filelist = 'aappII.txt';
else $filelist = 'aappDesor.txt'; // experiment v parizi listopad 2011*/

// POUZIVAME FILELIST
require_once('classes/filelist.class.php');
require_once 'classes/TimeEstimate.class.php';
define("FILELISTDIR","filelists/"); // podadresar, kde jsou filelisty a kam se i ukladaji logy

if(isset($_GET['arg'])) { $argc=2; $argv[1]=$_GET['arg']; } // opatreni pro debug ve studiu 7.1.1
if(isset($argc) && $argc >1){
	$filelist = $argv[1];
} 
set_time_limit(0); // muze se delat neomezene dlouho
$CTime = new TimeEstimate();
if(isset($filelist)){
   $CFilelist = new Filelist(FILELISTDIR.$filelist);
   if($CFilelist->OK()){
   	 if(($missing=$CFilelist->MissingFiles())!=false){
	      echo "\n!!chybejici soubory: ".count($missing)."\n".implode("\n",$missing)."\n";
	      exit;
     } elseif(($duplicates=$CFilelist->Duplicates())!=false){
     	  echo "\n!!duplikovane soubory: ".count($duplicates)."\n".implode("\n",$duplicates)."\n";
          exit;
     }
     $CFilelist->RemoveGroups();
     $filename_arr = $CFilelist->GetList();
     $extensions = array('');
     $matlabtrackdir = 'matlab';
     $imagedir = "images";
     $tabledir = $CFilelist->Dir()."/tables";
   } else {
     echo "nemuzu precit filelist $filelist\n";
     exit;
   }
}
define('LOGFILE',FILELISTDIR.$filelist.".log");


include 'drf3matlab_const.php';
require_once('includes/logfile.php');
require_once 'classes/TableFile.class.php';
require_once 'classes/TrialData.class.php';
require_once 'classes/PathData.class.php';
require_once 'classes/Frames.class.php';
require_once 'classes/SpaNavFilename.class.php';
require_once 'classes/CFileName.class.php';
require_once 'classes/TableObject.php';
require_once 'classes/SubjectData.php';
require_once 'classes/UTLog.class.php';
include 'includes/drf3tomatlab.include.php'; // funkce pro tento program

if(VERZE=='aappIII-IIb'){ // 13.2.2014 - nastavuje se ve filelistu
	// AAPPIII
	$cislamist = array("stred"=>-1,"ArenaGoal1"=>1,"ArenaGoal2"=>2,"RoomGoal1"=>3,"RoomGoal2"=>4);
	$souradnicemist = array("ArenaGoal1"=>array(-649,375),"ArenaGoal2"=>array(649,375), // funkce goalpositions
	                        "RoomGoal1"=>array(-649,-375),"RoomGoal2"=>array(649,-375));
	$ceskajmena = array("stred"=>"stred","ArenaGoal1"=>"ARENA1","ArenaGoal2"=>"ARENA2","RoomGoal1"=>"ROOM1","RoomGoal2"=>"ROOM2");
	$stredareny = new CPoint(0,0);
	$polomerareny = 858;
	$polomer_cilu = 148;
	$CFrames = new Frames(array("RoomGoal1","RoomGoal2"),array("ArenaGoal1","ArenaGoal2"));
	$faze_pouzeukaz = array(8,9,0); //0=10; faze, kde se jen ukazuje na cil,nechodi se do cile - 16.4.2013
	$trials_faze = array(0=>49,9=>49,8=>49);// v kterych fazi se ma kontrolovat pocet pokusu
} elseif(VERZE=='aappIII'){ // 7.2.2013 - nastavuje se ve filelistu
	// AAPPIII
	$cislamist = array("stred"=>-1,"ArenaGoal1"=>1,"ArenaGoal2"=>2,"RoomGoal1"=>3,"RoomGoal2"=>4);
	$souradnicemist = array("ArenaGoal1"=>array(-745,85),"ArenaGoal2"=>array(745,85), // funkce goalpositions
	                        "RoomGoal1"=>array(-745,85),"RoomGoal2"=>array(745,85));
	$ceskajmena = array("stred"=>"stred","ArenaGoal1"=>"ARENA1","ArenaGoal2"=>"ARENA2","RoomGoal1"=>"ROOM1","RoomGoal2"=>"ROOM2");
	$stredareny = new CPoint(0,0);
	$polomerareny = 858;
	$polomer_cilu = 148;
	$CFrames = new Frames(array("RoomGoal1","RoomGoal2"),array("ArenaGoal1","ArenaGoal2"));
	$faze_pouzeukaz = array(8,9,0); //0=10; faze, kde se jen ukazuje na cil,nechodi se do cile - 16.4.2013
	$trials_faze = array(0=>49,9=>49,8=>49);// v kterych fazi se ma kontrolovat pocet pokusu
} elseif(VERZE=='aappOCD'){ // 7.2.2013 - nastavuje se ve filelistu
	// AAPPIII
	$cislamist = array("stred"=>-1,"ArenaGoal1"=>1,"ArenaGoal2"=>2,"RoomGoal1"=>3,"RoomGoal2"=>4);
	$souradnicemist = array("ArenaGoal1"=>array(-420,-384),"ArenaGoal2"=>array(628,-344), // funkce goalpositions
	                        "RoomGoal1"=>array(-282,-522),"RoomGoal2"=>array(606,240));
	$ceskajmena = array("stred"=>"stred","ArenaGoal1"=>"ARENA1","ArenaGoal2"=>"ARENA2","RoomGoal1"=>"ROOM1","RoomGoal2"=>"ROOM2");
	$stredareny = new CPoint(0,0);
	$polomerareny = 858;
	$polomer_cilu = 148;
	$CFrames = new Frames(array("RoomGoal1","RoomGoal2"),array("ArenaGoal1","ArenaGoal2"));
	$faze_pouzeukaz = array(8,9,0); //0=10; faze, kde se jen ukazuje na cil,nechodi se do cile - 16.4.2013
	$trials_faze = array(0=>49,9=>49,8=>49);// v kterych fazi se ma kontrolovat pocet pokusu
}else { //defaultni hodnota VERZE aappI
	//puvodni AAPP
	$cislamist = array("stred"=>-1,"ArenaMarkGoal"=>1,"ArenaPlaceGoal"=>2,"RoomMarkGoal"=>3,"RoomPlaceGoal"=>4);
	$souradnicemist = array("ArenaPlaceGoal"=>array(607, -160),"ArenaMarkGoal"=>array(-521, -332), 
	                        "RoomPlaceGoal"=>array(-152, 632),"RoomMarkGoal"=>array(-320, -564));
	$ceskajmena = array("stred"=>"stred","ArenaPlaceGoal"=>"ARENA1","ArenaMarkGoal"=>"ARENA2","RoomPlaceGoal"=>"ROOM1","RoomMarkGoal"=>"ROOM2");
	$stredareny = new CPoint(20,-4);
	$CFrames = new Frames(array("RoomMarkGoal","RoomPlaceGoal"),array("ArenaMarkGoal","ArenaPlaceGoal"));
	$polomerareny = 858;
	$polomer_cilu = 148;
	$faze_pouzeukaz = array();
	$trials_faze = array();
}


$CTable = new TableFile("$tabledir/aapptable.xls");
$CTable_matlab = new TableFile("$tabledir/aapptable.txt");
if($CTable->isError() || $CTable_matlab->isError()) exit(-1);
$CTable_matlab->setMatlab();

$CTable->AddColumns(
    array("filename","personno","phase","phaserepeat","trial","trialgoal","goalname","hidden",
          "frame","framechange","placechange","placecodelast",
          "trialtime","keytime","otoceni","angleerr","angleerrabs","angletogoal",
          "trial_starttime","keytimepress",
    	  "path_length","path_efficiency","distmin", "arenaspeed","anglespeedmax",
    	  /*"timetoturn_pul","timetoturn_tretina","timetoturn_ 23deg",*/ "timetoturn",
    	  "turntogoal",
    	  "angleturn","viewangle0","smer_cile","smer_cloveka","timeToGoal","timeFromGoal","timeStay",
          "timetocenter","arenaanglechange", "startattime","logMisto","logTime",
          "logTimestamp", "logTimeText", "logTimeKey", "markNum",
    	  "StejnychFramu"
    	//    	  "keyangle","keygoalangle","keygoalx","keygoaly"
     	));
$CTable_matlab->AddColumns($CTable->columnames);
$CTable->SetPrecision(3,38); $CTable->SetPrecision(3,40); $CTable->SetPrecision(3,41);

$trial_count = array(); // tam budu sbirat pocty trialy pro lidi a mista

$cislosouboru = 0;
$CSubjects = new SubjectData;
// $cislocloveka=0; //$clovek_kod = "";

if(ANGLESPEEDS) $AngleSpeed = new Table(); // tam budu ukladat rychlosti otaceni 
if(ANGLESPEEDS) $AngleChange = new Table();

// histogramy z rychlosti otaceni, podle faze, cisla mista a framechange
if(ANGLEHISTO) $AngleHistoT = new TableObject(array("faze","placecode","framechange"),"HistoSum",array("min"=>0.02,"step"=>0.04));
if(TIMEHISTO)  $TimeHistoT = new TableObject(array("faze","placecode","framechange"),"Histogram",array("min"=>0,"step"=>0.1));
$testphases = explode(",",TESTPHASES);

foreach ($filename_arr as $filename_name) {
  foreach ($extensions as $ext) {
	  $filename = (!empty($ext))?$filename_name.".".$ext:$filename_name;
	  dp($filename,"$cislosouboru / ".count($filename_arr).", ".$CTime->remains($cislosouboru/count($filename_arr)));
	  $CSpanavName = new SpaNavFilename(basename($filename));
	  $CSubjects->Add($CSpanavName->Person(), $CSpanavName->Faze());
	  /*if($CSpanavName->Person()!=$clovek_kod){
	  	$clovek_kod = $CSpanavName->Person();
	    $cislocloveka++;
	  }*/
	  if(!file_exists($filename)){
	  	dp("... soubor neexistuje");
	  	continue;
	  }
		$fc = file($filename);
		$intrack = false;
		$mistolast = "";
		if(TRACKS) $fh = fopen(CFileName::ChangePath($filename, $matlabtrackdir).".txt","w");
		$trial_cil = 0;
		$CFrames->Reset();
		$CPathData = new PathData($CSubjects->SubjectNo(),$CSpanavName->Faze(),$stredareny);
		$UTLog = new UT2004Log(CFileName::ChangeExtension($filename, "log"), array('A','B','C','D'), "S"); // 10.4.2013
		$ukazano_trial = false; // jestli uz v tomto trialu clovek ukazal na cil 
		$otoceno_trial = false; // jestli uz se clovek v tomto trialu zacal otacet
		$first_line = true;
		$trial_time_zero = true;
		$vstup_do_mista = 0;
		foreach ($fc as $lineno=>$line){
			if(strncmp($line," Time",4)==0) {
				$intrack = true;
			} elseif($intrack){
				$ukazano_radka = false;
				$fields = explode("\t",$line);
				if(count($fields)>=12 && in_array($fields[12],array_keys($cislamist))){ // tim vynecham prvni radek, kde je aktivni misto '-'
					$time = (double) trim($fields[0]);
					$arenaangle = $fields[4];
					$viewangle = (double) $fields[7];
					$aktivnimisto = $fields[12]; // pokud je misto -, tak se neuklada 
					$klavesa =  strtolower(trim($fields[9]));
					$bod = new CPoint($fields[2],$fields[3]); // pozice cloveka
					
					if($first_line) { // tohle se vykona jen jednu po prvni radce souboru, pak uz ne
						 $CTrialData = new TrialData($time,$bod,$viewangle);
						 $CPathData->NextTrial($time,misto2matlab($aktivnimisto),$CFrames->PlaceChange($aktivnimisto)/*$CFrames->FrameChange($aktivnimisto)*/,
						 	arenaframe($aktivnimisto,$CFrames), //frame
						 	$aktivnimisto!='stred'?array('stred'=>goalposition($aktivnimisto,$CFrames),'polomer'=>$polomer_cilu):false //pozice cile
						 	);//"Vsechno",misto2cz($aktivnimisto);
						 $first_line = false; 
					}
					
					$CPathData->AddPoint($bod,$viewangle,$time,$ukazano_radka?$klavesa:false,$arenaangle); // musi to byt pred testovanim klavesy, pokud byla klavesa stlacena jiz pri aktivaci cile
					
					// mam tu dva problemy - zmacknul W driv nez se otocil. Nepohnul se ale zaznamenane to je
					// zmacknul sipku tesne pred aktivaci mista - takze se mu rovnou odblokoval pohyb dopredu a on se uz neotacel
					// jeden clovek delal, ze se otacel kdyz vchazel do cile, takze si rovnou odblokoval pohyb a pak po zobrazeni cile uz se neotocil. 
					if(!empty($mistolast) && $mistolast!="stred" ) { //v okamziku zmeny mista nechci stlacenou klavesu && !($vstup_do_mista || $trial_time_zero)
						if( ($klavesa==strtolower(KEYLEFT) || $klavesa==strtolower(KEYRIGHT)) && !$otoceno_trial  /* v okamziku zmeny mista nechci zacatek otaceni && !($vstup_do_mista || $trial_time_zero) */) {
							$otoceno_trial = true;
							// ted se zacal otacet
							$CPathData->TurnedNow($time, $klavesa==strtolower(KEYRIGHT)?1:-1, goalposition($mistolast,$CFrames), arenaframe($mistolast,$CFrames));
						} elseif($klavesa==strtolower(KEYTOPOINT) && !$ukazano_trial && ($otoceno_trial || TURNEDBYMOUSE /* v prvni experimenu se nepouzivaly sipky*/ )) { //
							 $ukazano_radka = true; // stlacena klavesa ukazani na cil
							 $ukazano_trial = true;
							 $CTrialData->AddKey($time,$bod,$viewangle,goalposition($mistolast,$CFrames,$arenaangle));
							 $CPathData->AddKey($time);
						} 
					}
					if($vstup_do_mista >= -10) {/*vstup do mista hned po minulem vstupu, -1 znamena stred -15 az -10 znamena odpocitavani framue*/
						if(!empty($mistolast) && $mistolast!=$aktivnimisto ){ // pokus se zmenilo aktivni misto
							$vstup_do_mista=$cislamist[$mistolast];
						} elseif($ukazano_trial && !$ukazano_radka && in_array($CSpanavName->Faze(), $faze_pouzeukaz) ){ 
							// ne ve framu (radce) kdy ukazano, ale az v nasledujicim
							//16.4.2013 - pri ukazovani se mohou opakovat mist - pak je aktivni stala stejne misto a metoda zmeny aktivniho misto nefunguje 
							$vstup_do_mista=$cislamist[$mistolast]; // taky to muzu udelat v trialu, kdy se zmeni jmeno misto az v nasledujicim framu
						} elseif($ukazano_radka && (count($fc)-$lineno < 4) && in_array($CSpanavName->Faze(), $faze_pouzeukaz) ){ // pokud posledni radka tracku a v tom stlacena klavesa ukazani 
							//31.7.2013 - po ukazani pomoci S se casto jste objevuje radka Avatar platform rotation enabled, takze davam rezervu radsi <4, uvidime jak bude fungovat
							$vstup_do_mista=$cislamist[$mistolast];
						} else {
							$vstup_do_mista = 0; //z -10 se udela 0
						}
					} else {
						$vstup_do_mista++;
					}
				     
					//TISK DAT DO TABULKY
					if($vstup_do_mista>0 || $vstup_do_mista==-1) { //$vstup_do_mista==-1 znamena vstup do stredu
						$CTrialData->End($time,$mistolast);
						if($mistolast!="stred"){
							$frame = $CFrames->AddGoal($mistolast);
							//20.7.2012 - vklada se v NextTrial $CPathData->AddGoal(goalposition($mistolast,$CFrames),$polomer_cilu); // 7.9.2011 - duplikovane se vklada jeste nahore, ale to snad nevadi
							$rowvalues = array(basename($filename),$CSubjects->SubjectNo(),$CSpanavName->Faze(),$CSubjects->PhaseRepeat(), // od 13.4.2012 indexy + 1!!!
							       $CTrialData->trial,$trial_cil, //(4 5) = 5 6 
							       $mistolast,$mistolast!="stred"?1:0, //6 7 
							       $frame,$CFrames->FrameChange(),$CFrames->PlaceChange(), // 8 9 10 
							       $CFrames->PlaceCodeLast(), // 11
							       $CTrialData->Time(),$CTrialData->Keytime(),
							       $CPathData->Angle(), //14 otoceni - o kolik se clovek v pokusu otocil
							       $CTrialData->KeyAngleError(), //15 - uhlova chyba vzhlede k cili pri stlaceni klavesy
							       abs($CTrialData->KeyAngleError()),
							       abs($CTrialData->AngleToGoal()),
							       $CTrialData->StartTime(),
							       $CTrialData->KeyTimePress(),
							       $CPathData->PathLength(),//20
							       $CPathData->PathEfficiency(),
							       $CPathData->MinimalDistance(),
							       $CPathData->ArenaSpeed(),
							       $CPathData->AngleSpeedMax($CTrialData->KeyTime()), //24
							       //$CPathData->TimeToTurn(false,"pul"),
							       //$CPathData->TimeToTurn(false,"tretina"),
							       //$CPathData->TimeToTurn(false,1), // 99 kvantil uhlu v prvnich 250ms
							       $CPathData->TimeToTurn(false,5) /*26*/,  // col27 - 99 kvantil uhlu - pro vsechny dohromady
							       $CPathData->TurnToGoal(goalposition($mistolast,$CFrames),arenaframe($mistolast,$CFrames)), //27
							       $CPathData->AngleTurn() /*28*/,$CPathData->angletostarturn[$CTrialData->trial]["viewangle0"]/*29*/,
							       $CPathData->angletostarturn[$CTrialData->trial]["smer_cile"]/*30*/,$CPathData->angletostarturn[$CTrialData->trial]["smer_cloveka"]/*31*/,
							       $CPathData->TimeToGoal(1),/*32*/$CPathData->TimeToGoal(-1),$CPathData->TimeToGoal(0)/*34*/,
							       $CPathData->TimeToStred()/*35*/, $CPathData->AngleChange($arenaangle) /*36*/,
							       $CPathData->StartTime(), /*37*/
							       $UTLog->Text($trial_cil), $UTLog->Time($trial_cil),
							       $UTLog->Timestamp($trial_cil), 
							       $UTLog->TimeText($trial_cil),($mistoname = $UTLog->TimeKey($trial_cil)) /*41-42*/,
							       $CPathData->LPTbit()%4, // abych mel cisla jen 1-3. Cislo bitu je 5-7 pro RoomFrame
							       $CFrames->StejnychFramu() // po kolikate je stejny frame /*col 44*/
//							       $CTrialData->key_angle_error_data['keyangle'], $CTrialData->key_angle_error_data['anglecil'],
//							       $CTrialData->key_angle_error_data['goalxy']->x,$CTrialData->key_angle_error_data['goalxy']->y
							       
							);
							$CTable->AddRow($rowvalues);
							// hodnoty jine pro matlab
							$rowvalues[0]=$cislosouboru; $rowvalues[6]=misto2matlab($mistolast); $rowvalues[37]=mistoname2matlab($mistoname);
							$CTable_matlab->AddRow($rowvalues);
							if(TIMEHISTO && in_array($CSpanavName->Faze(), $testphases)){ 
									$TimeHistoT->AddVal(array($CSpanavName->Faze(),$CFrames->PlaceCodeLast(),$CFrames->FrameChange()),
										$CPathData->TimeToTurn());
							} 
							if(!isset($trial_count[$CSubjects->SubjectNo()][$CSpanavName->Faze()][basename($filename)][misto2matlab($mistolast)]))
								$trial_count[$CSubjects->SubjectNo()][$CSpanavName->Faze()][basename($filename)][misto2matlab($mistolast)]=1;
							else $trial_count[$CSubjects->SubjectNo()][$CSpanavName->Faze()][basename($filename)][misto2matlab($mistolast)]++;  
						}
						$ukazano_trial = false;
						$otoceno_trial = false;
						$CTrialData->NextTrial($time,$bod,$viewangle);
						
						$CPathData->NextTrial($time,misto2matlab($aktivnimisto),$CFrames->PlaceChange($aktivnimisto)/*$CFrames->FrameChange($aktivnimisto)*/,
							arenaframe($aktivnimisto,$CFrames),
							$aktivnimisto!='stred'?array('stred'=>goalposition($aktivnimisto,$CFrames),'polomer'=>$polomer_cilu):false //pozice cile
							);//"Vsechno",misto2cz($aktivnimisto)
						if($mistolast!="stred") $trial_cil++;
						$trial_time_zero = true; //  pristi bude prvni radka trialu, s casem 0
						$vstup_do_mista=-15; // po dobu peti framu neni mozne, aby clovek znovu vstoupil do mista nebo zmacknul klavesu
					} else {
						$trial_time_zero = false; // pristi nebude prvni radka trialu
					}
					
					// TISK TRACKU PRO MATLAB
					if(TRACKS){
						$out=$time.TABLEFILES_COLUMNDELIM.$fields[1]. // Time  Frame
						   TABLEFILES_COLUMNDELIM.$fields[2].TABLEFILES_COLUMNDELIM.$fields[3]. TABLEFILES_COLUMNDELIM.$arenaangle. //ArenaLoc.X  ArenaLoc.Y, PlatformAngle
						   TABLEFILES_COLUMNDELIM.$fields[5].TABLEFILES_COLUMNDELIM.$fields[6].TABLEFILES_COLUMNDELIM.$viewangle. //PlatformLoc.X  PlatformLoc.Y View.X
							 TABLEFILES_COLUMNDELIM.$fields[10]. // pauza
							 TABLEFILES_COLUMNDELIM.$cislamist[$aktivnimisto]. // cislo aktivniho mista
							 TABLEFILES_COLUMNDELIM.$vstup_do_mista. // vstup do mista
							 TABLEFILES_COLUMNDELIM.(strtoupper($fields[13])=='TRUE'?1:0). // attached to platform?
							 TABLEFILES_COLUMNDELIM.($ukazano_radka?1:0). // jestli zrovna stlacena klavesa s
						   TABLEFILES_COLUMNDELIM.$fields[14].TABLEFILES_COLUMNDELIM.$fields[15]. // pocty vstupu  ArenaMarkGoal   RoomMarkGoal 
						   TABLEFILES_COLUMNDELIM.$fields[16].TABLEFILES_COLUMNDELIM.trim($fields[17]). // pocty vstupu RoomPlaceGoal   ArenaPlaceGoal
						   TABLEFILES_COLUMNDELIM.trim($fields[18]); // pocty vstupu stred
						fwrite($fh,$out."\n");
					}
					
					$mistolast = $aktivnimisto; 
					
					
				} else {
					$CPathData->ReadLPT($line,true);
				}
			}
		}
		if(TRACKS) fclose($fh);
		// OBRAZEK
		if(IMAGES) $CPathData->Plot(CFileName::ChangePath($filename, $imagedir),$stredareny,$polomerareny);
		// HISTOGRAMY
		if( (ANGLESPEEDS || ANGLEHISTO) && in_array($CSpanavName->Faze(),$testphases)) {
			//$CPathData->AngleSpeedSave(CFileName::ChangePath($filename, $matlabtrackdir));
			if(ANGLESPEEDS) {
				$AngleSpeed->AppendTable($CPathData->AngleSpeedProfile(array(-1)));
				$AngleChange->AppendTable($CPathData->TurnProfile(array(-1)));
			} elseif(ANGLEHISTO) {
				$CPathData->AngleSpeedProfile(array(-1));
			}
			if(ANGLEHISTO) $AngleHistoT->ImportArray($CPathData->angle_speed_histo, array($CSpanavName->Faze()));
			
		}
		unset($CPathData);
		$cislosouboru++;
		if(isset($trials_faze[$CSpanavName->Faze()]) && ($CTrialData->trial-1)<$trials_faze[$CSpanavName->Faze()]){ // pokud malo trialu ve fazi
			dp("malo trialu:".($CTrialData->trial-1));
			beep(4);
			sleep(2); // pauza 3 sec
		}
  }
}

$CTable_matlab->SaveAllRows();
$CTable->SaveAll(true);
if(TIMEHISTO) $TimeHistoT->TableData()->SaveAll(true,"$tabledir/timehisto.txt",1);
if(ANGLESPEEDS){
	$AngleSpeed->setMatlab();
	$AngleSpeed->OpenFile("$tabledir/aappanglespeed.txt");
	$AngleSpeed->SaveAllRows(true);
	
	$AngleChange->setMatlab();
	$AngleChange->OpenFile("$tabledir/aappanglechange.txt");
	$AngleChange->SaveAllRows(true);
}
if(ANGLEHISTO){	
	$tableT = $AngleHistoT->TableData();
	$tableT->setMatlab(false);
	$tableT->OpenFile("$tabledir/tablehistoT40.xls");
	$tableT->SaveAll();
	$tableT->setMatlab(true);
	$tableT->OpenFile("$tabledir/tablehistoT40.txt");
	$tableT->SaveAll(true);
	
	//histogramy();
}

trial_count_save($trial_count,$tabledir); // ulozi pocty trialu do dalsi tabulky
echo $CTime->time();







 
?>
