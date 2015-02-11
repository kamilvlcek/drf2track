<?php

/*
 //kod do menrot_bva.pxd
 $obrazky = array(   "bva-60-l",
					"bva-60-r",
					"bva-90-l-ex",
					"bva-90-r-ex",
					"bva-135-l",
					"bva-135-r",
					"bva-180-l",
					"bva-180-r",
					"bva-225-l",
					"bva-225-r",
					"bva-270-l-ex",
					"bva-270-r-ex",
					"bva-300-l",
					"bva-300-r");
					*/
$obrazky = array("rotace1a",
					"rotace1b",
					"rotace1c",
					"rotace1d",
					"rotace2a",
					"rotace2b",
					"rotace2c",
					"rotace2d",
					"rotace3a",
					"rotace3b",
					"rotace3c",
					"rotace3d");					


$out = "";
$pocet = 0;
$pocet_stejne= 0;
foreach($obrazky as $obr_left){
	foreach($obrazky as $obr_right){
		$cislo_left = $obr_left{6};
		$cislo_right = $obr_right{6};
		$rotace_left = $obr_left{7};
		$rotace_right = $obr_right{7};
		$rotace1 = array("a","b");
		$rotace2 = array("c","d");
		if($cislo_left==$cislo_right /*&& $rotace_left!=$rotace_right*/){
			if( (in_array($rotace_left,$rotace1) && in_array($rotace_right,$rotace1)) // napr a c nebo b d
			   || (in_array($rotace_left,$rotace2) && in_array($rotace_right,$rotace2)) // napr c a nebo d b
			   ) {
			   	$ruzne = 0;
			   	$pocet_stejne++; 
			   } else  {
			   	$ruzne = 1;
			   }
			$out .= "Trial(\"$obr_left.jpg\",\"$obr_right.jpg\"".
        ",$ruzne,?,?);\n";
			$pocet++;
		}
		
		
		/*
		 //kod do menrot_bva.pxd
		  
		$vals = explode("-",$obr_left);
		$uhel_left = $vals[1];
		$strana_left = $vals[2];
		$vals = explode("-",$obr_right);
		$uhel_right = $vals[1];
		$strana_right = $vals[2];
		
		if($uhel_left!=$uhel_right){
			$out .= "Trial(\"$obr_left.jpg\",\"$obr_right.jpg\",".(abs($uhel_left-$uhel_right)).
		
				",".($strana_left==$strana_right?0:1).",?,?);\n";
		
			$pocet++;
		}*/
	}
}
file_put_contents("bva_recognition.txt",$out);
echo "$pocet - $pocet_stejne";

?>