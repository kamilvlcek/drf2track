<?php
define('LOGFILE',"drf2track.log");
define('IMAGESDIR',"images");
//define('INPUTDIR',"d:/prace/programovani/qbasic/mwm2/data");

require_once('classes/cimage.php');
require_once('classes/drf2track.class.php');
require_once('classes/utbvavars.class.php');
require_once('includes/logfile.php');

if(!file_exists(IMAGESDIR)) mkdir(IMAGESDIR);

if(isset($argc) && $argc >1){
	$filename = $argv[1];
} else {
	//echo "neni zadano vstupni jmeno";
	$filename = "MISALEDV.TR6";
	//$filename = "MAGDA.TR1";
	//$filename = "04280308.TR2";
	//exit;
}
//$dir = "d:/prace/mff/UT2004_EXPERIMENT/CAVEUT 3 steny/zbynek";
$maindir = "d:/prace/mff/UT2004_EXPERIMENT/CaveUT_Experiment (1 platno)/";
$dirs = array("cyril","iveta",/*"jakub",*/"kamil","michal","zbynek");
foreach ($dirs as $dir){
  if(is_dir($maindir.$dir)){
    $CDrf2Track = new Drf2Track($maindir.$dir);
    //$CDrf2Track = new Drf2Track($filename);
    if($CDrf2Track->error) exit;
    $CDrf2Track -> GetCounts();
    dp($CDrf2Track->counts);
    $CDrf2Track->Image();
    unset($CDrf2Track);
  }
}
exit;
dp($dir);
$CUTBvaVars = new UTBvaVars("d:/prace/mff/UT2004_EXPERIMENT/CAVEUT 3 steny/cyril");
if($CUTBvaVars->error) exit;
$CUTBvaVars->GetCounts();
while($CUTBvaVars->Next()){
  //dp($CUTBvaVars->roomxy,"roomxy");
  ;
}


?>