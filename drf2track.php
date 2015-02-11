<?php
/*
 * DRF2TRACK
 * program pro vyhodnoceni tracku z experimentu v Blue Velvet Arena a jeji virtualni analogie
 * (c) Fyziologicky ustav AVCR, 2008-2012, Kamil Vlcek, kamil@biomed.cas.cz
 * 
 */
//$filelist = 'ksirova.txt';
//$filelist = 'rhodosExp2.txt';n
//$filelist = 'lenka.txt';
//$filelist = 'egoallo.txt';

/*
 * Schizofrenici - detekce nenalezeni cile, - visible nebo time
 * kolik casu pak do vstupu do cile
 * 
 * */
//////////////////////////////////////////////////////////////////////////////////


//$argc = 2;
//$argv[1]='d:\prace\programovani\qbasic\vestib\data\PB100212.TR1';
//$argv[1]='SchHGTII.txt';
require_once 'classes/CFileName.class.php';
define("FILELISTDIR","filelists/"); // podadresar, kde jsou filelisty a kam se i ukladaji logy

if(isset($_GET['arg'])) { $argc=2; $argv[1]=$_GET['arg']; } // opatreni pro debug ve studiu 7.1.1
if(isset($argc) && $argc >1){
	// 25.2.2010 - jako argument se da dat primo i track, pak se typ souboru (BVA/SPANAV) urci z prvniho radku souboru
	$ext = strtolower(substr($argv[1],strpos($argv[1],".")+1));
	if(empty($ext)) $ext='txt';
	if($ext=='txt'){
		$filelist = $argv[1];
		$logid = CFileName::Filename($filelist);
	} elseif(substr($ext,0,2)=='tr'){
		$trackname = $argv[1];
		$logid = $trackname;
	} else {
		echo "neznama pripona souboru ke zpracovani: txt pro filelist, tr? pro track";
		$logid = false;
		exit;
	}
}

require_once('classes/filelist.class.php');
require_once 'classes/logout.class.php';

// funguje s chybami ale funguje: 
//$CErrorBuffer = new ErrorBuffer($logid);
//$old_error_handler = set_error_handler("myErrorHandler",E_NOTICE | E_ERROR | E_WARNING | E_RECOVERABLE_ERROR | E_USER_ERROR | E_USER_WARNING); // 6.9.2012

if(isset($filelist)) {
  $CFilelist = new Filelist(FILELISTDIR.$filelist);
} elseif(isset($trackname)){
  $CFilelist = new Filelist();
  $CFilelist->AddFile($trackname);
}
if(!defined('LOGFILE')) define('LOGFILE',FILELISTDIR.$filelist.".log");


// const se definuji az po nacteni filelistu, protoze muzou byt definovany jiz v nem

require_once("defineconst.php"); // tady se definuji defaultni hodnoty konstant
require_once('classes/Image.class.php');
require_once('classes/drf2track.class.php');
require_once('includes/logfile.php');
require_once('classes/CTrackVars.php');
require_once 'classes/utbvavars.class.php';
require_once 'classes/spanavvars.class.php';
require_once 'classes/wholeimage.class.php';
require_once 'classes/TimeEstimate.class.php';


dp("--- Drf2Track, verze z 14.11.2012, ");
dp("(c) Fyziologicky ustav AVCR, 2008-2012, Kamil Vlcek ---");
if(!isset($CFilelist) ) {
  dp ("pouziti: drf2track filelist/trackname"); exit;
}

if(UT2004DATA + BVADATA + SPANAVDATA > 1 || UT2004DATA + BVADATA + SPANAVDATA ==0 ) {
	dp("Ktery typ dat se ma zpracovat? - UT, BVA, SpaNav ...");
	dp("UT2004DATA:".UT2004DATA. ", BVADATA: ".BVADATA.",SPANAVDATA: ".SPANAVDATA);
	exit;
}

$nobeep = false;

set_time_limit(0); // muze se delat neomezene dlouho

if(isset($CFilelist)){
   if($CFilelist->Ok()){
     //$CFilelist->RemoveGroups();
     if(($missing=$CFilelist->MissingFiles())!=false){
	    echo "\n!!chybejici soubory: ".count($missing)."\n".implode("\n",$missing)."\n";
	    if(!ALLOWMISSING) exit;
     } elseif(($duplicates=$CFilelist->Duplicates())!=false){
     	echo "\n!!duplikovane soubory: ".count($duplicates)."\n".implode("\n",$duplicates)."\n";
        if(!ALLOWDUPLICATES) exit;
     }
     $filename_arr = $CFilelist->GetList();
     $extensions = array('');
   } else {
     echo "nemuzu precit filelist $filelist\n";
     exit;
   }
} else {
  echo "neni zadany zadny filelist!";
  exit;
}
$Ctime = new TimeEstimate();
$WImage = new WholeImage(ARENAR,EXPDELIMITER);
$WTable = new TableFile($CFilelist->Dir()."/tables/".$filelist.".total.xls");
if(ULOZUHLY) $WTableUhly = new TableFile($CFilelist->Dir()."/tables/".$filelist.".uhly.xls"); //16.9.2014 - potrebuju do darkevel
if($WTable->isError()) exit(-1);
$fileno = 0;
$chybne_soubory = array(); // seznam souboru, ve kterych nastala chyba
foreach ($filename_arr as $groupname=>$groupdata) {
	if($groupname==Filelist::$group_not_set) $group = false; else $group = $groupname;
	foreach ($groupdata as $no=>$filename) {
	    $WImage->SetDir(dirname($filename));
	  	dp("\n------- file ".(++$fileno)."/".$CFilelist->Count()." ----------");
			dp($filename);
	
	    
	    if(BVADATA){
	  	  $TrackData =   new CTrackVars($filename);
	    	//$CDrf2Track = new Drf2Track(new CTrackVars($filename),$filename,$CFilelist->GetFileSettings($group,$filename));
	    } elseif (UT2004DATA){
	    	$TrackData =  new UTBvaVars($filename);
	  	  //$CDrf2Track = new Drf2Track(new UTBvaVars($filename),$filename,$CFilelist->GetFileSettings($group,$filename));
	  	} elseif(SPANAVDATA){
	  		
	  	  /*if(isset($cfgfilename_arr[$no])){
	  	    $cfgfilename = $cfgfilename_arr[$no];
	  	  }
	  	  if(isset($markaim_arr[$no])) $param = $markaim_arr[$no];
	  	  elseif(isset($startaim_arr[$no])) $param = $startaim_arr[$no];
	  	  else*/
	  	  $param = false;
	  	  $cfgfile = defined('CFGFILE')?CFGFILE.".class.php":false;
	  	  $TrackData = new SpaNavVars($filename,$cfgfile,$param);
	  		//$CDrf2Track = new Drf2Track(new SpaNavVars($filename,$cfgfile,$param),
	  		//     $filename,$CFilelist->GetFileSettings($group,$filename));
	  	}
	  	$CDrf2Track = new Drf2Track($TrackData,$filename,$CFilelist->GetFileSettings($groupname,$filename));
	    if(!$CDrf2Track->error) { // chyba napriklad, kdyz soubor nebyl nalezen
	    	dp($CDrf2Track->counts);
	
	    	$CDrf2Track->Image();
	    	if(!$group) $group = $CDrf2Track->trackvars->ExpName(); // pokud nejsou skupiny ve filelistu, tak pouziju 
	    	$WImage->Add($CDrf2Track->cilearr,$filename,$CDrf2Track->GoalRadius(),$group);
	    	$WTable->AppendTable($CDrf2Track->txttable);
	    	if(ULOZUHLY) $WTableUhly->AppendTable($CDrf2Track->uhlytable2);
	    	//dp("whole image persons: ".$WImage->Persons());
	    	//dp("memory",intval(memory_get_usage(true)/1000). " kB");
	      
	    } else {
	    	$WImage->AddMissing($filename, $group);
	    	$chybne_soubory[]=basename($filename);
	    }
	    unset($CDrf2Track);
	    echo "* cas ".$Ctime->time()." z asi ".$Ctime->estimate($fileno/$CFilelist->Count())."\n";
	}
}
if($WImage->Persons()>0 && !empty($filelist)){
  $WImage->SaveImg(WHOLEIMAGESDIR."/".$filelist);
  $WImage->TableExport($filelist,$CFilelist->Dir()); // tabulka subjektu pro statistiku a tabulka prumeru, stderr a count za skupiny
}
$WTable->SaveAll();
if(ULOZUHLY) $WTableUhly->SaveAll();
dp("running time",$Ctime->time());

if(BEEP) $WImage->Beep(BEEP);
unset($WImage);
unset($WTable);
if(count($chybne_soubory) >0){
	echo "chybnych souboru ".count($chybne_soubory).": ".implode(", ",$chybne_soubory)."\n";
}

restore_error_handler();
//dp("final memory",intval(memory_get_usage(true)/1000). " kB");


?>
