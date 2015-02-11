<?php
require_once '../classes/CPoint.class.php';
require_once '../classes/CLine.class.php';
require_once '../classes/CFileName.class.php';

$filename = "data/startpositions.txt";
if(!file_exists($filename)){
	echo "soubor $filename nenalezen";
	exit(-1);
}
$fc = file($filename);
$out = "";
foreach ($fc as $lineno => $fileline) { 
	// 5 hodnot na kazdem radku - prvni bod [x,y], druhy bod [x,y], vzdalesnot
	// oddelene tabulatorem
	// vysledny bod bude vpravo relativne ke smeru od prvniho k druhemu bodu
	$vals = explode("\t", trim($fileline));
	$bod1 = new CPoint($vals[0],$vals[1]);
	$bod2 = new CPoint($vals[2],$vals[3]);
	$distance = $vals[4];
	
	$cil = CPoint::Middle($bod1, $bod2); // prostredek mezi body 1 a 2
	$line = new CLine();
	$line->DefineByPoints($bod1, $bod2); // primka spojujici body 1 a 2
	if( CPoint::ToRight($bod1, $bod2)){ // pokud je bod1 napravo (nebo nahore) od bodu2
		$cil->MoveAngleDistance($line->Angle()+90, $distance);
	} else {
		$cil->MoveAngleDistance(Angle::Normalize($line->Angle()+90+180), $distance);
	}
	$out .=trim($fileline)."\t".String::setdelim(round($cil->x,4),",")."\t".String::setdelim(round($cil->y,4),",")."\n";
	echo "radka $lineno, cil $cil\n";
}

file_put_contents("data/startpositions.xls", $out);

?>