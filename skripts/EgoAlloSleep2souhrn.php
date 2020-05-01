<?php
// program na prehled souboru od Stefi Markove z vysetreni v  BVA v NUDZ testem EgoAllo Sleep
// 1.5.2020

$dir    = "d:\\prace\\programovani\\qbasic\\vestib\\dataEgoAlloSleep\\unzip";
$files = scandir($dir);
$output = '';

$filetypes = array();  // chci jmena roztridit i podle pripon do slopucu
$filetypesn = array('tr1'=>0,'tr2'=>0,'tr3'=>0,'tr4'=>0,'tr5'=>0,'tr6'=>0,'tr7'=>0,'tr8'=>0);

foreach($files as $f){
	if(!is_dir($dir."\\".$f)){
		$path_parts = pathinfo($f);
 		$ext =	$path_parts['extension'];
 		$name = $path_parts['filename'];
 		$pripony = array('tr1','tr2','tr3','tr4','tr5','tr6','tr7','tr8');
 		if (in_array(strtolower($ext), $pripony)){
			$fc = file($dir."\\".$f);
			
			$parts = preg_split('/(-|,|:)/',$fc[1]);
			$expname = trim($parts[1]); 
			$cfgname = trim($parts[3]);
		    
			$parts = preg_split('/( )/',$fc[2],-1, PREG_SPLIT_NO_EMPTY);
			$datum = trim($parts[1]);
			list($m,$d,$r) = explode("-",$datum);
			$cas = trim($parts[2]);
			
			$output.= "$name\t$ext\t$expname\t$cfgname\t$d.$m.$r\t$cas\n";			
			
			$filetypes[$filetypesn[strtolower($ext)]][strtolower($ext)]=$name; //roztridene podle names
			$filetypesn[strtolower($ext)]++;
 		} 				
	}
}
file_put_contents($dir."\\EgoAlloSleep2souhrn.xls", $output); // seznam souboru

$out2 = 'tr1'."\t".'tr2'."\t".'tr3'."\t".'tr4'."\t".'tr5'."\t".'tr6'."\t".'tr7'."\t".'tr8'."\n";
foreach($filetypes as $ft ){
	foreach($filetypesn as $ext=>$n){
		$out2 .= isset($ft[$ext]) ? $ft[$ext].'.'.strtoupper($ext) ."\t" : "";	
	}
	$out2.= "\n";		
}
file_put_contents($dir."\\EgoAlloSleep2souhrn2.xls", $out2); // tabulka podle pripon ve sloupcich
?>