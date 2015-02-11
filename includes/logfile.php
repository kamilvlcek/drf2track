<?php

if(defined('LOGFILE')){
  $flog = fopen(LOGFILE,"at");
  fwrite($flog,"\n"."\n".date("j.n.Y H:m:i")."\n");
}
$echodp = true;
/**
 * $a muze byt ""
 *
 * @param mixed $a
 * @param string $label
 */
function dp($a,$label=""){
		global $flog, $echodp;
		if(true || (!empty($a) && !empty($label))){
			if($echodp) echo $label.": ";
			fwrite($flog,$label.": ");
		} else {
			if($echodp) echo $label;
			fwrite($flog,$label);
		}
		if(is_array($a)){
			foreach($a as $key => $aa) {
      	if(is_array($aa))
        	dp($aa,"  $key");
        else {
        	if($echodp) echo $key."=>".$aa." ";
        	fwrite($flog,$key."=>".$aa." ");

        }
      }

    } else {
    	if($echodp) echo $a;
    	fwrite($flog,$a);
    }
    if($echodp) echo "\n";
    fwrite($flog,"\n");

}
function dp_r($a,$label){
		global $flog, $echodp;
        ob_start();
        echo $label.": ";
				print_r($a);
				echo "\n";
        fwrite($flog,ob_get_contents());
        if($echodp) echo ob_get_contents();
        ob_end_clean();
}
/**
 * vypise obsah promenne do debux.txt pomoci print_r
 * @param mixed $var
 * @param string $name
 */
function debug_print_r($var,$name='name'){
	ob_start();
  print_r($var);
  file_put_contents("debug.$name.txt",ob_get_clean());
}
?>
