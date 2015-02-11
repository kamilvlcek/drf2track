<?php
if(isset($trackname)){
	TrackGetType($trackname); // 25.2.2010 - pokud mam zpracovat jen jeden track bez filelistu, tak zjistim typ souboru primo z tracku
}
date_default_timezone_set('Europe/Prague');

if(!defined('LOGFILE'))         define('LOGFILE',"drf2track.log");
if(!defined('TXTFILE'))         define('TXTFILE',"drf2track.txt");
if(!defined('IMAGESDIR'))       define('IMAGESDIR',"images");
if(!defined('WHOLEIMAGESDIR'))  define('WHOLEIMAGESDIR',"wholeimages");
if(!defined('TABLESDIR'))       define('TABLESDIR',"tables");
if(!defined('HTMLDIR'))         define('HTMLDIR',"html");
if(!defined('ARENAR'))          define('ARENAR',140);      // polomer areny ve skutecnosti
if(!defined('ARENAIMAGER'))     define('ARENAIMAGER',140); // polomer obrazku areny v svg
if(!defined('ARENAFRAME'))      define('ARENAFRAME',0);
if(!defined('MAKEIMG'))         define('MAKEIMG',1);
if(!defined('SUMMARYPLOT'))     define('SUMMARYPLOT',false); // uz funguje
if(!defined('MATLAB'))          define('MATLAB',0);
if(!defined('UT2004DATA'))      define('UT2004DATA',0); // jaka data se maji prijimat
if(!defined('BVADATA'))         define('BVADATA',0);
if(!defined('SPANAVDATA'))      define('SPANAVDATA',0);
if(!defined('EYETRACKING'))     define('EYETRACKING',0); // jestli kreslit eyetracking
if(!defined('EYETRACKINGHISTO'))define('EYETRACKINGHISTO',0); // jestli kreslit histogram z horizontalniho uhlu eyetracking
if(!defined('VIEWANGLE'))       define('VIEWANGLE',0); // jestli zpracovavat data o pohledu
if(!defined('VIEWANGLEHISTO'))  define('VIEWANGLEHISTO',0); // jestli zpracovavat data o pohledu
if(!defined('WHOLEIMAGE'))      define('WHOLEIMAGE',0); // jestli tvorit wholeimage
if(!defined('BEEP'))            define('BEEP',1); // jestli tvorit wholeimage
if(!defined('WHOLE_MEASURE'))   define('WHOLE_MEASURE',"distfromgoal"); // jake meritko - chyba se ma pouzite ve wholeimage table - 15.7.2010
if(!defined('KEYTOSTART'))      define('KEYTOSTART',""); // klavesa mackana na startu - je mozno zmenit zde, nebo ve filelistu
if(!defined('KEYTOPOINT'))      define('KEYTOPOINT',""); // klavesa mackana pri ukazani na cil ze startu - je mozno zmenit zde, nebo ve filelistu
if(!defined('KEYTONEXT'))       define('KEYTONEXT',"g"); // klavesa mackana pro prechod k dalsimu trialu - cil nalezen
if(!defined('KEYFOUND'))        define('KEYFOUND',""); // klavesa mackana pro oznaceni domnele pozice cile - 17.9.2014 zkousim kvuli darkevel
if(!defined('GOALBYENTRANCE'))  define('GOALBYENTRANCE',"1"); // klavesa mackana pro prechod k dalsimu trialu - cil nalezen
if(!defined('GOALBYAVOID'))  	define('GOALBYAVOID',0); //jestli se ma brat jako vstup do cile kdyz je clovek na jeho pozici - 14.10.2014
if(!defined('PROBETRIALLENGTH'))define('PROBETRIALLENGTH',-1); // pocet vterin probetrialu - prevadi se dal jen do SpanaCFG.class.php 10.4.2012
if(!defined('REFERENCEBYDIST')) define('REFERENCEBYDIST',0); // z jake znacky se ma pocitat distfromreference - 0-z prvni 1-z nejblizsi k cili

if(!defined('IMAGETYPE'))       define('IMAGETYPE','SVG');
if(!defined('ALLOWDUPLICATES')) define('ALLOWDUPLICATES',0); // jestli ma projit filelist, kde jsou duplicitni soubory
if(!defined('ALLOWMISSING')) 	define('ALLOWMISSING',0); // jestli ma projit filelist, kde jsou chybejici soubory
if(!defined('PLOTAFTERCIL')) 	define('PLOTAFTERCIL',0); 
if(!defined('PLOTTOSTART')) 	define('PLOTTOSTART',0); // jestli se ma kresli i cast tracku nez bylo zmacknuto c
if(!defined('KEYTOSTART_AFTERMARK')) 	define('KEYTOSTART_AFTERMARK',0); 
	// jestli jsou klavesy C v tracku az po zmene pozice znacek nebo cile (cili po radku Position changed: )
if(!defined('SECTIONARENA')) 	define('SECTIONARENA',0); //17.10.2012
if(!defined('LASERS')) 			define('LASERS',''); //25.4.2013 - pozive virtualnich znacek
if(!defined('GOALRADIUS'))      define('GOALRADIUS',"125"); // 25.7.2013 polomer cile v unreal jednotkach
if(!defined('SHOWPARAMS'))      define('SHOWPARAMS',0); // 2.6.2014 - jestli ukazovat parametry tracku v obrazku
// strategie hledani cile - 2.6.2014
if(!defined('DSWIM')) 			define('DSWIM',0); // DSWIM|0.95 path eff 
if(!defined('FSEARCH'))      	define('FSEARCH',0); // FSEARCH|60,0.75 D,Q
if(!defined('DSEARCH'))      	define('DSEARCH',0); // DSEARCH|15,0.8  A,K
if(!defined('FINCORRECT'))     	define('FINCORRECT',0); // FINCORRECT|15,0.5  A,Qo 3.10.2014
if(!defined('MARKSTRATEGY'))    define('MARKSTRATEGY',0); // DSEARCH|15,0.8  A,K

if(!defined('MWM2'))            define('MWM2',0); // jsou to data z mwm2 - tim se urcuje ULOZUHLY a ENVELOPESIZE
if(!defined('ULOZUHLY'))        define('ULOZUHLY',0); // 16.9.2014 - potrebuju uhly i pro darkevel, takze vyclenuju sem

if(BVADATA==1){
	define('AVERAGEPOINTS',24); // pouzivam 24
	define('LINEPOINTS',0);
	define('MEDIANPOINTS',5); // pouzivam 5
	define('AVERAGEDIST',0);
	define('EXPDELIMITER',"."); // oddelovac od konce nazvu experimentu - pro kazdou priponu whole image zvlast
} else {
	define('AVERAGEPOINTS',0); // pouzivam 24
	define('LINEPOINTS',0);
	define('MEDIANPOINTS',0); // pouzivam 5
	define('AVERAGEDIST',0);
	define('EXPDELIMITER',"_"); // jmeno experimentu je oddeleno _, pripony jsou porad tr
	// plati jen pro spanavdata
}

// jeste ULOZUHLY a ENVELOPESIZE v drf2track.class.php
if(MATLAB==1){
	define('DELIM','.');
	define('COLUMNDELIM',"\t");
} else {
	define('DELIM',',');
	define('COLUMNDELIM',"\t");
}
function TrackGetType($filename){
	$fh = fopen($filename,'r');
	if(!$fh) {echo "nemohu otevrit soubor $filename"; exit;}
    $line = fgets($fh);
	if(substr($line,0,5)=='*****'){
		if(!defined('BVADATA'))         define('BVADATA',1);
		dp("detekovana BVADATA");
	} elseif (substr($line,0,5)=='Date/'){
		if(!defined('SPANAVDATA'))      define('SPANAVDATA',1);
		dp("detekovana SPANAVDATA");
	} else {
		dp("typ dat nedetekovan");
	}
	fclose($fh);
}

function versiontime(){
		$files = scandir("./classes");
		$maxtime = 0;
		foreach($files as $filename){
			$maxtime = max($maxtime,filemtime($filename));
		}
		return date("d.m.Y",$maxtime);
}
?>
