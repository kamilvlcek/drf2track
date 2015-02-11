<?php
// skript na zpracovani eyetrackingovych dat od Jirky
// 2009-2010

define('HISTOBIN',4); //pocet binu (4 jsou po 90 stupnich)
define('DELIM',","); // desetinna carka/tecka ve vystupnim souboru
define('TRIALDATA',1); // jestli psat data o pohledu do jednotlivych binu v jednotlivych pokusech
define('SUMMARYDATA',1); // jestli psat sumarni data o pohledech na znacky a dominanci

define('TBLPATH','w:/bondy/prace/mff/data/IvetaKamil/experiment rhodos/exp2/tables/');
//define('TBLPATH','t:/-= lidske pokusy =-/VR/data/IvetaKamil/experiment rhodos/Data/Rhodos2/tables/');
require_once('includes/stat.inc.php'); // soubor se statistickymi funkcemi

set_time_limit(0); // muze se delat neomezene dlouho
$filename= 'data-rotovana4.csv';
if(!$fh = @fopen($filename,"r")){
  // protoze ty soubory jsou giganticke, treba 20MB, tady ho nenacitame cely do jednoho pole, ale budeme ho cist radek po radku
  echo "!! ERR: cant open file '$filename'";
  exit(-1); // koncim program a vracim chybu
}

$lineno = 0;
$binsize = 360/HISTOBIN;
$histo = array(); // pole, do ktereho budu ukladata vsechny cetnosti pohledu do binu, na znacky, na strop a podlahu
$last_subject='';
$last_konfig='';
$subject_no=0;
$pohledy_jmeno = array(); // seznam pohledu v souboru (sloupec "pohled")
while(!feof($fh)){
  $line = trim(fgets($fh,4069)); // nactu jednu radku souboru a oriznu bile znaky na zacatku a na konci (treba konec radku)
  if($lineno>0 && strlen($line)>0){ // prvni radka jsou nazvy sloupcu, takze ji vynechavam. Pripadne prazdne radky taky preskakujem
    $vals = explode(";",$line);
    $subject = bez_uvozovek($vals[0]); // odstranim uvozovky
    $konfigurace = bez_uvozovek($vals[1]);
    $track = $vals[2];
    $trial = $vals[3];
    $isStena = $vals[19]; // sloupec is.stena
    $uhel_eye = rad2deg(to_float($vals[20]));  // sloupec eye.azimut
    $mark_pattern = trim(bez_uvozovek($vals[21])); // sloupec mpresent
    $pohled = bez_uvozovek($vals[26]); // sloupec "pohled" - posledni v souboru, hodnoty "podlaha", "strop", "stena", ""
    $pohled = empty($pohled)?"nezarazene":$pohled; // pokud je $pohled prazdny, nahradi se hodnotou "nezarazene"
    if(!in_array($pohled,$pohledy_jmeno)) $pohledy_jmeno[]=$pohled;

    // pocitani subjektu a konfiguraci - ty konfigurace jsou pozustatek z predchoziho skriptu
    if(empty($last_subject) || $last_subject != $subject){
      echo "\n$subject_no: $subject - $konfigurace";
      $subject_no++;
      $last_subject = $subject;
      $last_konfig = $konfigurace;
    } elseif($last_konfig != $konfigurace){
      echo " - $konfigurace";
      $last_konfig = $konfigurace;
    }


    if($isStena=='TRUE' || $isStena =='FALSE'){ // muze byt ted cokoliv - bereme i pohledy mimo stenu
      $bin = bin($uhel_eye,$binsize); // vypocet binu z uhlu pohledu
      if(empty($histo[$subject][$konfigurace][$track][$trial][$bin])) $histo[$subject][$konfigurace][$track][$trial][$bin]=0;
      $histo[$subject][$konfigurace][$track][$trial][$bin]++; // pocet radku kde byl pohled do tohoto binu

      if(empty($histo[$subject][$konfigurace][$track][$trial]['lines'])) $histo[$subject][$konfigurace][$track][$trial]['lines']=0;
      $histo[$subject][$konfigurace][$track][$trial]['lines']++; // pocet vsech radku (za trial)

      $znacky_pocet = substr_count($mark_pattern,"1"); // pocet pritomnych znacek v trialu
      $znacka_jina = -1; // pro kazdy trial vyplnim specifickou znacku - pokud je jen jedna, tak tu, pokud jsou dve, tak tu ktera chybi, pokud tri, tak zadnou (=-1)
      for($mark = 0; $mark<3;$mark++){ // pro znacky 0 , 1 a 2
        if($mark_pattern{$mark}=='1'){  // $mark_pattern je napriklad "111", zjistuji hodnotu znaku 0 1 nebo 2
          // znacka byla pritomna (znak je 1)
          if(empty($histo[$subject][$konfigurace][$track][$trial]['marks'][$mark])) $histo[$subject][$konfigurace][$track][$trial]['marks'][$mark]=0;
          $uhel_mark = rad2deg(to_float($vals[22+$mark])); // sloupce m1a";"m2a";"m3a - uhel znacky ve stupnich
          $bin_mark=bin($uhel_eye,$binsize,$uhel_mark); // uhel pohledu ok relativne k pozici znacky. Pohled na znacku znamena bin=0
          if($bin_mark==0){ // pohled je ve stejnem binu jako mark
            $histo[$subject][$konfigurace][$track][$trial]['marks'][$mark]++; // pocet radku, kde byl pohled na znacku
          }
          if($znacky_pocet==1) $znacka_jina = $mark; // pokud je jen jedna znacka v trialu, oznacim si, ktera to byla
        } else {
          // znacka nebyla pritomna (znak je 0)
          if($znacky_pocet==2) $znacka_jina = $mark; // pokud jsou dve znacky v trialu, oznacim si, ktera chybi
        }
      }
      $histo[$subject][$konfigurace][$track][$trial]['marks']['znacky_pocet']=$znacky_pocet;
      $histo[$subject][$konfigurace][$track][$trial]['marks']['znacka_jina']=$znacka_jina;
    }

    if(empty($histo[$subject][$konfigurace][$track][$trial]['pohled'][$pohled]))    $histo[$subject][$konfigurace][$track][$trial]['pohled'][$pohled]=0;
    $histo[$subject][$konfigurace][$track][$trial]['pohled'][$pohled]++; // pocet radku s timto pohledem

    if(empty($histo[$subject][$konfigurace][$track][$trial]['pohled']['celkem']))   $histo[$subject][$konfigurace][$track][$trial]['pohled']['celkem']=0;
    $histo[$subject][$konfigurace][$track][$trial]['pohled']['celkem']++; // celkovy pocet radku

    $histo[$subject][$konfigurace][$track][$trial]['marks']['znacky_pritomne']=bez_uvozovek($vals[12]); // sloupec "marks"
  }
  $lineno++;
}

fclose($fh);
//  --------------------------------------------------------------------------------
// TED budu nacitat data z dalsich tabulek, pocitat dominance a ukladat vystupni soubor:

// hlavicky vystupniho souboru
// out je retezec, do ktereho to nejdriv budu vsechno psat a pak ho najednou ulozim do souboru
$out = "soubor\tkonfigurace\ttrack\ttrial\tznacky\tpocet_znacek\tznacka_jina\tuceni"; // \t je tabelator
for($bin = 0;$bin<HISTOBIN;$bin++)        $out.="\tbin$bin"; // promenny pocet sloupcu, podle poctu binu
$out .="\tmark1\tmark2\tmark3";
foreach ($pohledy_jmeno as $pohled_jmeno) $out .= "\t$pohled_jmeno"; // promenny pocet sloupcu podle poctu pohledu v souboru
$out .="\tMark1Dist\tMark2Dist\tMark3Dist"; //\tMarkD\tMarkD2\tMarkDominant \tDominantDist\tShodaDistEye\tShodaDist12
$out.="\tDominantEye\tH Eye\tp eye\tDominantDist\tH dist\tp dist";
$out .="\n"; // \n je konec radku

// data:
$out_trialdata = ""; // data TRIALDATA viz vyse
$out_sumdata = ""; // SUMMARYDATA viz vyse
foreach($histo as $subjekt=>$subjekt_data){
  foreach($subjekt_data as $konfigurace=>$konfigurace_data){
    $dist_errors=gettbldata($subjekt,$konfigurace); // nacita data z tabulky ze souboru (produkovane programem drf2track)
    if(empty($dist_errors)) {echo "\n $subjekt $konfigurace no TBL data"; continue;} // pokud se nanasla zadna data pro tento subjekt a kofiguraci, jde se na dalsi subjekt/konfiguraci


    $markA_key = false; // dominantni znacka - nastavi se v track 0 = single mark a pouzije se v track2 = delece
    foreach($konfigurace_data as $track=>$trackdata){
      $znacky_data = array(); // tam budu ukladat data o podilech pohledu na jednotlive znacky
      //$trialmax = max(array_keys($trackdata)); // maximalni cislo trialu. 3 v konf 1 a 3, 5 v konf 2 a 4
      $marksAA_dominant_array = array(); // do toho pole si ulozim dve dominantni znacky podle pohledu pri deleci
      foreach($trackdata as $trial=>$trialdata) {
      	//$lines0 = false;
        $uceni = ($trial%10==0)?1:0; // 1 pokud ucici pokus, jinak 0 - ucici pokusy jsou 0,10,20,30 ... cili delitelne deseti; '%' je zbytek po deleni
      	// 1. PRVNI SLOUPCE TABULKY
        $out_trialdata.="$subjekt\t$konfigurace\t$track\t$trial\t{$trialdata[marks][znacky_pritomne]}\t{$trialdata[marks][znacky_pocet]}\t".($trialdata['marks']['znacka_jina']+1)."\t$uceni"; //+1 je tam aby byly znacky od 1

        // 2. VYPISU BINY A PODILY POHLEDU DO NICH (4 az 16 sloupcu 'bin0' - 'bin3')
        // z $trialdata z jirkovych dat
        for($bin = 0;$bin<HISTOBIN;$bin++){
        	if($trialdata['lines']==0){ // pokud nebyly zadne radky
        		//$lines0 = true;
        		if(TRIALDATA) $out_trialdata .= "\t"; // zadna data
        	} else {
            if(TRIALDATA) $out_trialdata.="\t".round($trialdata[$bin]/$trialdata['lines'],4); // podil poctu pohledu do binu ke vsem pohledum
        	}
        }

        // 3. VYPISU ZNACKY A PODILY POHLEDU NA NE (3 sloupce 'mark1' - 'mark3')
        for($mark=0;$mark<3;$mark++){
			    if($trialdata['lines']==0){ // nebyly zadne radky pohledu na zadnou znacku - ve vysledne tabulce je prazdna bunka
        		//$lines0 = true;
        		$out_trialdata .="\t";
        	} else {
		          if(!isset($trialdata['marks'][$mark])) { // znacka v tom trialu nebyla
		            $out_trialdata .="\t";
		          } else {
		            // znacka v tomto trialu byla
		          	$m = (round($trialdata['marks'][$mark]/$trialdata['lines'],4)); // podil pohledu na znacku z poctu radku
		            $out_trialdata.="\t$m";
		            if($uceni==0) // pro uceni nebudu pocitat dominanci
		              $znacky_data[$trialdata['marks']['znacky_pritomne']][$trialdata['marks']['znacky_pocet']]['eyedata'][$mark][]=$m; // index [$trialdata['marks']['znacka_jina']]  zatim vynecham. K uvaze je pouze u dvou znacek
		            // podil si ulozim pro pozdejsi vypocet dominance - a rozdelim to podle poctu znacek a podle chybejici/pritomne znacky

		          }
        	}
        }
        // 4. VYPISU PODILY POHLEDU NA STENU, STROP, PODLAHU A NEZARAZENE (sloupce: stena | nezarazene | strop | podlaha  )
        foreach ($pohledy_jmeno as $pohled_jmeno) { //stena | nezarazene | strop | podlaha
        	$out_trialdata.="\t".(round($trialdata['pohled'][$pohled_jmeno]/$trialdata['pohled']['celkem'],4));
        	// podil pohledu na tyto 4 kategorie z poctu radku
        }

        // 5. VYPISU CHYBY VZDALESNOSTI Z TABULEK DRF2TRACK
        if($trialdata['marks']['znacky_pocet']==1) {// single mark
            //$out_trialdata.="\t-\t-\t-"; // dominance z eyedata - zatim zadna
            for($mark=0;$mark<3;$mark++) {
                if(!isset($trialdata['marks'][$mark])) {// znacka v tom trialu nebyla
		                $out_trialdata .="\t";
                } else {
                    // znacka v tomto trialu byla
                    $out_trialdata .="\t".$dist_errors[$track][$trial];
                    $znacky_data[$trialdata['marks']['znacky_pritomne']][$trialdata['marks']['znacky_pocet']]['distdata'][$mark][]=$dist_errors[$track][$trial];
                }
            }
        } elseif($trialdata['marks']['znacky_pocet']==2) { // delece znacky
            //$out_trialdata.="\t-\t-\t-"; // dominance z eyedata - zatim zadna
            for($mark=0;$mark<3;$mark++) {
                if(!empty($trialdata['marks'][$mark])) {
		                $out_trialdata .="\t"; // znacka v tom trialu byla
                } else {
                    // znacka v tom trialu byla - tak umistim chybu vzdalenosti, protoze se jedna o deleci
                    $out_trialdata .="\t".$dist_errors[$track][$trial];
                    $znacky_data[$trialdata['marks']['znacky_pritomne']][$trialdata['marks']['znacky_pocet']]['distdata'][$mark][]=$dist_errors[$track][$trial];
                }
            }
        } else {
          $out_trialdata .="\t".$dist_errors[$track][$trial];
          if($uceni==0){
            $znacky_data[$trialdata['marks']['znacky_pritomne']][$trialdata['marks']['znacky_pocet']]['distdata'][0][]=$dist_errors[$track][$trial];
          }
        }
        $out_trialdata.="\n";
      }
      foreach($znacky_data as $znacky_pritomne => $znacky_pritomne_data) {
        // $znacky_pritomne_data obsahuji data pro jednu trojici znacek (treba M123)
        foreach ($znacky_pritomne_data as $znacky_pocet=>$znacky_pocet_data) {
          // $znacky_pocet_data obsahuji data pro jeden pocet znacek (1, 2 nebo 3)

          // POCITANI DOMINANCE - significance se udela ve statistice
          // dominance podle eyedata
          $eyedata_averages = average($znacky_pocet_data['eyedata']);
          $eyedata_max = max($eyedata_averages);
          $eyedata_H = round(kruskal_wallis(ranks($znacky_pocet_data['eyedata'])),2);
          $eyedata_p = chisquare($eyedata_H,degrees_of_freedom($znacky_pocet_data['eyedata']));
          $eyedata_dominant_mark = array_search($eyedata_max,$eyedata_averages)+1;

          $distdata_averages = average($znacky_pocet_data['distdata']);
          if($znacky_pocet==1) {
            $distdata_max = min($distdata_averages);
            $distdata_H = round(kruskal_wallis(ranks($znacky_pocet_data['distdata'])),2);
            $distdata_p = chisquare($distdata_H,degrees_of_freedom($znacky_pocet_data['distdata']));
            $distdata_dominant_mark = array_search($distdata_max,$distdata_averages)+1;
          } elseif($znacky_pocet==2){
            $distdata_max = max($distdata_averages);
            $distdata_H = round(kruskal_wallis(ranks($znacky_pocet_data['distdata'])),2);
            $distdata_p = chisquare($distdata_H,degrees_of_freedom($znacky_pocet_data['distdata']));
            $distdata_dominant_mark = array_search($distdata_max,$distdata_averages)+1;
          } else {
            $distdata_dominant_mark = ""; // prazdna bunka
            $distdata_H = "";
            $distdata_p = "";
          }


          foreach ($znacky_pocet_data['eyedata'][0] as $key=>$data){
            // key je 0-8 - opakovani dat, $data jsou podily pohledu

            // SOUHRNNE VYPISY POHLEDU A DISTANCE
            $out_sumdata.="$subjekt\t$konfigurace\t$track\t$trial\t$znacky_pritomne\t$znacky_pocet\t$key\t-1"; // uceni = -1 bude znamenat prumery
            for($bin = 0;$bin<HISTOBIN;$bin++)  $out_sumdata.="\t0"; // promenny pocet prazdnych sloupcu, podle poctu binu
            for($mark=0;$mark<3;$mark++) {
              $out_sumdata.="\t".$znacky_pocet_data['eyedata'][$mark][$key]; // pohledy na znacky
            }
            $out_sumdata.="\t\t\t\t"; // 4 volne sloupce
            for($mark=0;$mark<3;$mark++) {
              // pro pocet znacek = 3 bude definovana jen mark 0
              if(isset($znacky_pocet_data['distdata'][$mark][$key])) { // vzdalenosti od cile
                $out_sumdata.="\t".$znacky_pocet_data['distdata'][$mark][$key];
              } else {
                $out_sumdata.="\t";
              }
            }
            $out_sumdata .="\t$eyedata_dominant_mark\t$eyedata_H\t$eyedata_p\t$distdata_dominant_mark\t$distdata_H\t$distdata_p\t";
            $out_sumdata.="\n";
          }
        }
      }
    }
  }
}
if( ($fh = fopen($filename.".xls","w"))!=false){
  fwrite($fh,setdelim($out));
  if(TRIALDATA) fwrite($fh,setdelim($out_trialdata));
  if(SUMMARYDATA) fwrite($fh,setdelim($out_sumdata));
  fclose($fh);
}

echo "\n\n$subject_no subjects";
echo chr(7); // beep

// --- KONEC PROGRAMU, ZACINAJI FUNKCE




/**
 * udela z retezce desetinne cislo
 *
 * @param unknown_type $string
 * @return unknown
 */
function to_float($string){
  return floatval(str_replace(",",".",$string));
}
/**
 * odstrani vsechny uvozovky z retezce
 *
 * @param string $string
 * @return string
 */
function bez_uvozovek($string){
  return str_replace("\"",'',$string); // odstranim uvozovky
}
/**
 * vypocita bin
 *
 * @param deg $uhel
 * @param double $binsize
 * @param double $binstred uhel stredu prvniho binu
 * @return int
 */
function bin($uhel,$binsize,$binstred=0){
  $uhel = to_float($uhel);
  while($uhel >= 360) $uhel-= 360; //vyrobim uhel < 360
  if($uhel<$binstred-$binsize/2) // cisla pred zacatkem prvniho binu prevedu o otacku dal
      $uhel += 360;
  $uhel -= $binstred; // srovnam si uhel podle stredu binu
  if($uhel-360>-$binsize/2) $uhel -= 360; // uhly pred pulku binu 0 prepocitam - aby nebyl bin navic
  $bin = intval( ($uhel+$binsize/2)/$binsize);
  return $bin;
}
/**
 * do desetinnych cisel v retezci vlozi pozadovany odddelovac: desetinnou carku/tecku
 *
 * @param string $str
 * @return string
 */
function setdelim($str){
    return preg_replace("/([0-9])\.([0-9])/","$1".DELIM."$2",$str);
}
/**
 * nacte data z tabulky vytvorene v drf2track
 *
 * @param string $subject
 * @param string $konf
 * @return array
 */
function gettbldata($subject,$konf){
  // ktere znacky jsou pritomne v konfiguracich a trialech
  //$marks_single = array("konf1"=>array(1=>1,2=>2,3=>3),'konf2'=>array(3=>3,4=>2,5=>1),'konf3'=>array(1=>1,2=>2,3=>3),'konf4'=>array(3=>3,4=>2,5=>1));
  // ktere znacku jsou schovane
  //$marks_delece = array("konf1"=>array(0=>2,1=>1,2=>3),'konf2'=>array(0=>3,1=>2,2=>1),'konf3'=>array(0=>3,1=>2,2=>1),'konf4'=>array(0=>3,1=>1,2=>2));


  // MASKY PRO JMENO TABLE
  $filename = $subject."-".$konf.".tr.txt"; // PRVNI
  if(!file_exists(TBLPATH.$filename)){
    $filename = $subject."-".$konf."x.tr.txt"; // DRUHA moznost - opraveny soubor
    if(!file_exists(TBLPATH.$filename)){
      echo "\nnemohu najit TBL DATA pro $subject - $konf:\n ".TBLPATH.$filename;
      return false;
    }
  }
  $fc = file(TBLPATH.$filename);
  $marks_errors=array();
  foreach($fc as $lineno => $line){
    if($lineno>0){ // prvni radka jsou popisky sloupcu, tak ji vynecham
      $vals = explode("\t",$line);
      $track = $vals[2];    $trial = $vals[4];    $distfromgoal = to_float($vals[19]);
      $marks_errors[$track][$trial]=$distfromgoal;
      /*if($track==0 && isset($marks_single[$konf][$trial])){
        $marks_errors[$track][$marks_single[$konf][$trial]]=$distfromgoal;
      } elseif($track == 2 && isset($marks_delece[$konf][$trial])){
        $marks_errors[$track][$marks_delece[$konf][$trial]]=$distfromgoal;
      }*/
    }
  }
  return $marks_errors;
}

?>
