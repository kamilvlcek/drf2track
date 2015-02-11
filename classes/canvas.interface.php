<?php
interface iCanvas
{
   /**
    * nastavi sirku cary, ktera se pak bude pouzivat pro Circle, Line, Arc
    * @param unknown_type $width
    */
   public function SetWidth($width);
   public function Circle($center,$diam,$color,$filled=false,$width=false);
   public function Line($point1,$point2,$color,$width=false);
   public function SaveImg($filename);
   /**
    * circle arc defined by center and angles
    *
    * @param [x,y] $center
    * @param int $diam
    * @param deg $angle0
    * @param deg $angle1
    * @param string $color
    * @param bool $filled
    * @param int $width
    * @param bool $large
    */
   public function ArcAngles($center,$diam,$angle0,$angle1,$color,$filled=false,$width=false,$large=false);
   /**
    * circle arc defined by start, end and diameter
    *
    * @param [x,y] $start
    * @param [x,y] $end
    * @param int $diam
    * @param string $color
    * @param bool $filled
    * @param bool $left the direction between start and end
    * @param bool $large is center of circle is in the arc
    * @param int $with
    */
   public function Arc($start,$end,$diam,$color,$filled,$left,$large,$with=false);
   public function Text($center,$size,$color,$text);
   public function PathStart($name,$color,$width=false);
   public function PathAddNode($name,$bod,$colormark=false,$diammark=false,$line=true);
   public function PathAddCircleMarker($name,$diam,$color,$filled,$width=false);
}

?>