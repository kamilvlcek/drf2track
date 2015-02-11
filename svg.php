<?php
require_once('classes/canvas_svg.class.php');
$C = new Canvas_SVG(500,500);
$C->Circle(array(100,100),50,"red",true);
$C->Circle(array(200,100),50,"yellow",true);
$C->Circle(array(200,200),50,"green",true);
$C->Circle(array(100,200),50,"blue",true);

$C->Circle(array(50,300),50,"red",true);
$C->Circle(array(100,300),50,"violet",true);
$C->Circle(array(150,300),50,"purple",true);
$C->Circle(array(200,300),50,"yellow",true);
$C->Circle(array(250,300),50,"violet",true);
$C->Circle(array(300,300),50,"red",true);

$C->SaveImg("hrani");

?>