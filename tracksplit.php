<?php
// skript na rozdeleni jednotlivych tracku v souboru na samostatne souboru - 
//kvuli datum Australie, kde jsou HGT a EgoALlo smichane dohromady
//druhy soubor se nevyhodnocuje do tabulky.
// 5.11.2012

require_once('classes/filelist.class.php');

if(isset($_GET['arg'])) { $argc=2; $argv[1]=$_GET['arg']; } // opatreni pro debug ve studiu 7.1.1
if(isset($argc) && $argc >1){
	$filelist = $argv[1];
} else {
	$filelist = "egoalloAustralia.txt";
}

if(file_exists($filelist)) {
	$CFilelist = new Filelist($filelist);
	if($CFilelist->Ok()){
	     $CFilelist->RemoveGroups();
	     if(($missing=$CFilelist->MissingFiles())!=false){
		     	echo "\n!!chybejici soubory: ".count($missing)."\n".implode("\n",$missing)."\n";
		     	exit;
	     } elseif(($duplicates=$CFilelist->Duplicates())!=false){
	     	  echo "\n!!duplikovane soubory: ".count($duplicates)."\n".implode("\n",$duplicates)."\n";
	          exit;
	     }
	     $filename_arr = $CFilelist->GetList();
   } else {
     echo "nemuzu precit filelist $filelist\n";
     exit;
   }
   $pripony = array("a","b","c","d"); // pismena pridavana za nazvy tracku
   foreach($filename_arr as $filename){
   	  $fc = file($filename);
   	  $output = "";
   	  $trackno = 0;
   	  foreach($fc as $lineno=>$line){
   	  	if($lineno>0 && substr($line,0,10)=="**********"){
   	  		file_put_contents($filename.$pripony[$trackno], $output);
   	  		$trackno++;
   	  		$output = $line;
   	  	} else {
   	  		$output .= $line;
   	  	}
   	  }
   	  file_put_contents($filename.$pripony[$trackno], $output);
   	  $trackno++;
   	  echo basename($filename)." - $trackno tracku saved\n";
   }
} else {
	echo "soubor $filelist neexistuje";
}
?>