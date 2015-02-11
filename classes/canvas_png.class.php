<?php
require_once("classes/canvas.interface.php");
class Canvas_PNG implements iCanvas {
	private $colors;
	private $img;
	private $font;
	protected $error = false;
	protected $pathdata = array();
	function __construct($x,$y){
	  $this->img = imagecreate($x,$y) or die("Nemuzu vytvorit obrazek $this->sizex x $this->sizey");
      $this->colors['white'] = imagecolorallocate($this->img, 255, 255, 255);
      $this->colors['gray'] = imagecolorallocate($this->img, 200, 200, 200);
      $this->colors['black'] = imagecolorallocate($this->img, 0, 0, 0);
      $this->colors['blue'] = imagecolorallocate($this->img, 0, 0, 255);
      $this->colors['red'] = imagecolorallocate($this->img, 255, 0,0);
      $this->colors['green'] = imagecolorallocate($this->img, 0, 255,0);
      $this->colors['orange'] = imagecolorallocate($this->img, 255, 165,0);
      $this->colors['yellow'] = imagecolorallocate($this->img, 255, 255,0);
      $this->colors['gold'] = imagecolorallocate($this->img, 255, 215,0);
      $this->colors['crimson'] = imagecolorallocate($this->img, 220, 20,60);
      $this->colors['violet'] = imagecolorallocate($this->img, 238, 130,238);

      $fontpath = dirname($_SERVER['PHP_SELF']).'\\font\\';
      // oprava 25.2.2010 pro spousteni skriptu z mista dat
      //$fontpath = realpath('./font/');
	   putenv('GDFONTPATH='.$fontpath);
	   $this->font = "arialbd.ttf";
	   $this->font = $souborfontu = $fontpath.$this->font;
	   if(!file_exists($souborfontu)){
	      echo "soubor fontu: ".$souborfontu." neexistuje!";
	      exit;
	   } 
      
	}
	public function SetWidth($width){
   	  ; // nedela nic u PNG obrazku
    }
	public function Circle($center,$diam,$color,$filled=false,$width=false){
		if($filled) {
	      if(MAKEIMG) imagefilledarc($this->img, $center[0],$center[1], $diam,$diam, 0, 360, $this->colors[$color],IMG_ARC_PIE);
	    } else {
	      if(MAKEIMG) imagearc($this->img, $center[0],$center[1], $diam,$diam, 0, 360, $this->colors[$color]);
	    }
	}
	public function Line($point1,$point2,$color,$width=false){
		imageline($this->img,$point1[0],$point1[1],$point2[0],$point2[1],$this->colors[$color]);
	}
	public function SaveImg($filename){
	  $this->paths_save();
		imagepng($this->img,$filename.".png");
		return $filename.".png";
	}
	public function ArcAngles($center,$diam,$angle0,$angle1,$color,$filled=false,$width=false,$large = false){
		if($filled){
		  imagefilledarc($this->img, $center[0],$center[1], $diam,$diam, $angle0, $angle1, $this->colors[$color],IMG_ARC_PIE);
		} else {
		  imagearc($this->img, $center[0],$center[1], $diam,$diam, $angle0, $angle1, $this->colors[$color]);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param [x,y] $center
	 * @param int $size
	 * @param string color
	 * @param string $text
	 */
	public function Text($center,$size,$color,$text){
		imagettftext($this->img, $size, 0, $center[0], $center[1], $this->colors[$color],  $this->font, $text);
	}

	public function PathStart($name,$color,$width=false){
    $this->pathdata[$name]['color']=$color;
    $this->pathdata[$name]['width']=$width;
	}
  /* (non-PHPdoc)
   * @see iCanvas::PathAddNode()
   */
  public function PathAddNode($name,$bod,$colormark=false,$diammark=false,$line=true){
    $point = array('xy'=>$bod);
    if($colormark) $point['color']=$colormark;
    if($diammark) $point['diam']=$diammark;
    if(!$line) $point['move']=1;
    $this->pathdata[$name]['points'][]=$point;
  }
  public function Arc($start,$end,$diam,$color,$filled,$left,$large,$with=2){

  }
  public function PathAddCircleMarker($name,$diam,$color,$filled,$width=false){
    $this->pathdata[$name]['marker']=array('diam'=>$diam,'color'=>$color,'filled'=>$filled,'width'=>$width);
  }
  private function paths_save(){
    if(count($this->pathdata)>0){
       foreach($this->pathdata as $name=>$data){
         foreach($data['points'] as $i=>$point){
           if($i==0 || (isset($point['move']) && $point['move'])){
             $last_point = $point;
           } else {
             $this->Line($last_point['xy'],$point['xy'],$data['color']);
             $last_point = $point;
           }
           $pointdiam =  $point['diam'] ?$point['diam'] :$data['marker']['diam'];
           $pointcolor = $point['color']?$point['color']:$data['marker']['color'];
           if(isset($data['marker']))
            $this->Circle($point['xy'],$pointdiam,$pointcolor,$data['marker']['filled'],$data['marker']['width']);
         }
       }
     }
  }


}



?>
