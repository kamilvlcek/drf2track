<?php
$fc = file('data-rotovana3x.csv'); //nacita obsah suboru
$lidi = array();
foreach($fc as $lineno => $line){
    if($lineno>0){  
       $vals = explode(";",$line); //rozseka data podle stredniku
       $jmeno = $vals[0]; 
       if(!in_array($jmeno,$lidi)){
            $lidi[]=$jmeno; 
            echo 'jmeno: $jmeno !!';
       }
    }
}
echo $lidi[0];
?>