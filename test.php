<?php

// 9.4.2013
require_once 'classes/UTLog.class.php';
$ut = new UT2004Log("d:/prace/mff/data/aappIII/skriptyII/output/pokusLog_6_0.log", array('A','B','C','D'), "S");
exit;
//require_once 'CFileName.class.php';
//echo CFileName::ChangeExtension("DF100107-2_2_0.tr.viewangle.xlsviews.xls","xls",1);
//exit;
/*
require_once 'classes/CLine.class.php';
$a = new CPoint(10,10);
echo $a->Angle(new CPoint(20,0));

exit;*/
/*
$line = new CLine();
$bod = new CPoint(-20,30);
$uhel = 135;
$line->DefineByPointAngle($bod,$uhel);
$y = $line->CircleIntersection(new CPoint(0,0),100);
if($y[0]->y < $bod->y && $uhel > 180){ 
	// uhel > 180 miri dolu a proto vybiram prusecik s mensim y nez puvodni bod
	echo $y[0]->y;
} else {
	echo $y[1]->y;
}
exit;
*/
//require_once("stat.inc.php");
//$vals = array("C"=>array(15,13,14,16),"A"=>array(9,10,11,12),"B"=>array(5,6,7,8));
//$ranks = ranks($vals);
//var_dump($ranks);
//echo chisquare(kruskal_wallis($ranks),degrees_of_freedom($ranks));
require 'classes/CHistogram.class.php';
$histo = new Histogram(array("min"=>0,"max"=>360,"count"=>4,"circular"=>1,"middle"=>1)); // -45:45, 45:135 135:225...
$histo->AddRange(array(85,100));
$histo->AddRange(array(40,170));
$freq = $histo->Frequencies();
exit;

$histo = new Histogram2D(array("min"=>0,"step"=>1),array("min"=>0,"max"=>360,"count"=>8));
$histo->AddRange(array(0.1,1.5),array(35,135));
exit;
$histo->AddValue(0.1,10);
$histo->AddValue(0.4,45);
$histo->AddValue(0.8,185);
$histo->AddValue(1.2,225);
$histo->AddValue(1.6,15);

$histo->AddValue(2.0,134);
$histo->AddValue(2.4,115);
$histo->AddValue(2.6,16);
$histo->AddValue(2.8,20);
$histo->AddValue(3.0,355);

$freq = $histo->Frequencies();
print_r($freq);

/*require_once 'CKeySequence.class.php';
$k = new CKeySequence(array("a","b","b"));
$resp = $k->AddKey("a"); var_dump($resp);
$resp = $k->AddKey("b"); var_dump($resp);
$resp = $k->AddKey("b"); var_dump($resp);
$resp = $k->AddKey("a"); var_dump($resp);*/


/*
 
require_once 'CLine.class.php';
require 'point.php';
$line = new CLine();
$bod0 = new CPoint(-1,1);
$bod1 = new CPoint(1,1);
echo anglediff(array(1,1),array(-1,1))."x";
echo $bod1->AngleDiff($bod0);
$bod1->Rotate(-135);
$bod3 = $bod0;
$line->DefineByPoints(new CPoint(0,0),$bod0);
$prusecik = $line->CircleIntersection(new CPoint(0,0),1);
print_r($prusecik);
exit;

echo "slope, angle: ".$line->Slope().",".$line->Angle() ."\n<br>";
$bod = new CPoint(2,-2);
echo $bod->Angle()."\n<br>";
$distance = $line->DistancePoint($bod) ;
$angle = $line->AngleFromPoint($bod);
echo "distance, anglefrom point: ".$distance.",$angle"."\n<br>";
echo $line."\n<br>";
$bod_s = $line->Symmetry($bod);
echo $bod_s;
echo $bod_s[0].",".$bod_s[1];
*/
/*require_once("canvas_svg.class.php");
require_once("canvas_png.class.php");

$CCanvas = new Canvas_PNG(100,100);
//$CCanvas->Circle(array(50,50),10,"red",false);
//$CCanvas->Line(array(0,0),array(100,100),"green");
//$CCanvas->Text(array(20,50),20,"blue","ahoj");
//$CCanvas->Arc(array(50,20),array(50,70),30,'orange',false,true,false);
$CCanvas->ArcAngles(array(50,50),40,180,0,"violet",false,2,true);
$CCanvas->PathStart("track",'blue',1);
$CCanvas->PathAddCircleMarker('track',10,'orange',false,1);
$CCanvas->PathAddNode('track',array(0,0));
$CCanvas->PathAddNode('track',array(10,10));
$CCanvas->PathAddNode('track',array(50,30));
$CCanvas->PathAddNode('track',array(100,30),'green',15,false);
$CCanvas->PathAddNode('track',array(95,60));
$CCanvas->SaveImg("circle");*/
?>