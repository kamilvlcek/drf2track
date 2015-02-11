<?php
// opravi avoid >0 pred stlacenou kravesou f - mezi c a f bude vzdy 0
// kamil 30.11.2009 kvuli chybe v drf2ff.bas pri keyfoundbeepstop=2
define("FOLDER","./data/");
require_once("includes/stat.inc.php");
$filename_arr = array("DY081205.TR2", "CH081216.TR2", "JV092029.TR2", 
    "LV081008.tr2", "ZH080926.TR2", "JV092029.TR3", "MD090903.TR3", 
    "SH260309.TR3", "SH260309.TR4");
   
foreach ($filename_arr as $filename){
    echo $filename."\n";
    $fc = file(FOLDER.$filename);
    $track = -1;
    $intrack = false;
    $startbyl = false;
    $out = "";
    $opraveno =0;
    foreach($fc as $line){
        if(strncmp($line,"****",4)==0){
            $track++;
            echo "TRACK $track\n";
            $intrack = false;
            $out .=$line;
        } elseif(strncmp($line,"frame",5)==0){
            $intrack = true;
            $trial = -1;
            $names = preg_split("/\s{2,13}/",trim($line));
            $klavesaposition = array_search('klavesa',$names);// na jake pozici v poli hodnot tracku je stlacena klavesa
            $sectors = arr_series(array_search('sector',$names)+1,$klavesaposition-1);
            $out.=$line;
        } elseif($intrack){
           $vals = preg_split("/\s{8,13}/",trim($line));
           $klavesa = trim($vals[$klavesaposition]);
           if($klavesa=='c'){
               $startbyl  = true;
               if($trial>=0) echo "- opraveno $opraveno\n";
               $opraveno = 0; 
               $trial++;
               echo "trial $trial ";
               $out.=$line;
           } elseif ($klavesa=='f'){
               $startbyl = false;
               $out.=$line;
           } elseif($startbyl){
             foreach ($sectors as $s) {
                  if($vals[$s]!=99){
                    if((int) $vals[$s] !=0){
                        $vals[$s] = 0;
                        $opraveno++;
                        $out .= " ".implode(str_repeat(" ",12),$vals)."\n";
                    } else {
                        $out.=$line;
                    }
                     break;
                  }
             }
             
           } else {
               $out.=$line;
           }
           
        } else{
            $out .=$line;
        }
    }
    $outname = str_replace(".","x.",$filename);
    file_put_contents(FOLDER.$outname,$out);
}
    


?>