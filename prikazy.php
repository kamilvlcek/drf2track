<?php
require_once 'classes/HistoSum.php';
$h = new HistoSum(array("min"=>0.02,"step"=>0.04));
$h->AddValue(0.03,5);
$h->AddValue(0.04,2);
$h->AddValue(0.05,1);
$h->AddValue(0.08,3);
$h->AddValue(0.04,3);
$freq= $h->Frequencies();
$table = $h->FreqTable();
print_r($freq);
exit;

require_once 'classes/CPoint.class.php';
require_once 'classes/CLine.class.php';
$bod1 = new CPoint(-10,-10);
$bod2 = new CPoint(0,0);
$distance = 2;
// pocitam bod, ktery je na kolmici mezi spojnici prochazejici stredem ve vzdalenosti dist

$cil = CPoint::Middle($bod1, $bod2);
echo "stred:".$cil."\n";
$line = new CLine();
$line->DefineByPoints($bod1, $bod2);
if( CPoint::ToRight($bod1, $bod2)){
	$cil->MoveAngleDistance($line->Angle()+90, $distance);
} else {
	$cil->MoveAngleDistance(Angle::Normalize($line->Angle()+90+180), $distance);
}


echo $cil.",".$line->Angle() ."\n";
echo $line."\n";


?>