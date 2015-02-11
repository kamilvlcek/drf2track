<?php
require_once 'classes/CHistogram.class.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/CLine.class.php';
require_once 'classes/CFileName.class.php';

define('VIEWBINS',16); // pocet binu histogramu pohledu 0- 360 stupnu
define('TIMESTEP',0.1); // po kolika vterinach casove biny
define('ANGLEOFVIEW',100); // uhel pohledu
define('MARKCOUNT',3); // pocet znacek v tabulce histogramu - pokud bude vic, nezobrazi se
define('ANGLESHIFT',0); // na jakemu uhlu chci mit cil
define('ANGLEMARK',9); // uhlova velikost znacek pri pohledu ze stredu
define('MARKVIEWS',0); // jestli se ma ukladat tabulka markviews se vsemi pohledy na znacky
define('VIEWS',0); // jestli se ma ukladata tabulka views se vsemi pohledy na stenu
define('TRIALBINS',0); // jestli se ma trial delit na 3 casti - na startu, chuzu, okoli cilove pozice
/**
 * trida ktera zpracovava udaje o pohledech cloveka
 * @author kamil
 *
 */
class ViewAngle {
  /**
   * pole objektu CHistogram pro jednotlive trialy a faze - dvourozmerny - uhel vs cas faze
   * @var array
   */
  private $histogramy = array(); 
  
  /**
   *  pozice znacek pro jednotlive trialy a faze, relativne k pozici cile
   * @var array
   */
  private $marks_positions = array(); 
  /**
   * tabulka pro ulozeni udaju o pohledu v kazdem bodu tracku
   * @var Table
   */
  private $table_views; 
  /**
   * posledni pozice cile ( v minulem volani Addangle)
   * @var CPoint
   */
  private $last_position;
  
  /**
   * @var int
   */
  private $last_trial;
  
  /**
   * @var double
   */
  private $last_time;
  
  /**
   * @var int
   */
  private $last_timebin;
  
  /**
   * rychlost v kazdem  na zacatku kazdeho timebinu
   * @var array
   */
  private $timebin_speed;
  /**
   * pozice na zacatku minuleho binu
   * @var CPoint
   */
  private $last_binposition;
  /**
   * cas ja dlouho se clovek dival na znacku (pozice znacky pouze uhlove, jako cast steny)
   * @var array
   */
  private $marks_time;
  /**
   * vsechny pohledy na znacku ve vsech bodech experimentu
   * @var Table
   */
  private $marks_views;
  
  /**
   * maximalni cas v kazdem trialu
   * @var array
   */
  private $trial_time;
  
  /**
   * pro kazdy trial a fazi, casti trialu - 1=na startu 2=pohnul se 3=vesel do zony cilove pozice
   * @var array
   * @since 30.11.2012
   */
  private $moved;
  
  /**
   * vsechny rychlosti pro fazi a trial - docasne
   * @var array
   */
  private $speedarr;
  
  /**
   * casy zmen hodnoty moved, pro kazdy trial a phase - asi docasne
   * @var array
   * @since 30.11.2012
   */
  private $movedcasy;
  /**
   * uhlove chyby vzhledem k cili v bodu moved=2 - pro kazdy trial a phase
   * @var array
   */
  private $movedangleerr;
   
  // hodnoty specificke pro Trial a ukladane v NextTrial
  private $marks_position_abs;
  private $maxtime;
  private $lastxy;
  private $goalr;
  private $goalbyentrance;
  
  function __construct() {
    $this->table_views = new Table();
    $this->table_views->AddColumns(array("trial","phase","time","timepart","angle","x","y",
        "viewx","viewy","angle_view0","angle_view1","speed"));
    $this->marks_views = new Table();
    $this->marks_views->AddColumns(array("trial","phase","time","timepart","x","y","angle_view0","angle_view1","markname","markx","marky","markangle","viewanglemark","pohled"));
	}
	/**
	 * ulozi hodnoty pro aktualni trial, abych je nemusel predavat v AddAngle
	 * @param array $marks_positions
	 * @param float $maxtime maximalni cas trialu
	 * @param array of CPoint $lastxy prvni a posledni bod tracku v aktualnim trialu
	 * @param float $goalr polomer cile
	 * 
	 * @since 10.12.2012
	 */
	public function NextTrial($marks_position,$maxtime, $lastxy,$goalr,$goalbyentrance){
		$this->marks_position_abs = $marks_position;
		$this->maxtime = $maxtime;
		$this->lastxy = $lastxy;
		$this->goalr = $goalr;
		$this->goalbyentrance = $goalbyentrance;
	}
	/**
	 * prida uhel pohledu do 2D Histogramu - cas vs viewangle
	 * @param deg $angle aktualni natoceni subjektu
	 * @param int $trial
	 * @param int $phase
	 * @param float $time 0=zacatek trialu
	 * @param CPoint $position aktualni pozice subjektu, kladne y nahoru
	 * @param CPoint $cil
	 * @param deg $angleofview rozpeti uhlu, ktere ukladat do histogramu od angle
	 */
	public function AddAngle($angle,$trial,$phase,$time,$position,$cil,$angleofview=false){
     if(!isset($this->histogramy[$trial][$phase])){
     	 $this->histogramy[$trial][$phase] = new Histogram2D(
     	      array("min"=>0,"max"=>360,"count"=>VIEWBINS, "circular"=>1,"middle"=>1,'limitup'=>0), // x - sloupce bude uhel, protoze tech je vzdy stejne
     	      array("min"=>0,"step"=>TIMESTEP,"pocethodnotY"=>1,'limitupY'=>1) // y - radky budou sekundy, protoze tech bude ruzne
     	      );
     }
	 
     if($position->Distance(new CPoint(0,0))>ARENAR){ // pokud je pozice subjektu kousek za hranici areny, posunu ho do areny
     	  $position->MoveAngleDistance($position->Angle()+180,$position->Distance(new CPoint(0,0))-ARENAR);
     }
     $view = self::PointInView($position,$angle); // cilovy bod stredu obrazovky
     
     if($angleofview==false) $angleofview = ANGLEOFVIEW; //100 deg
     $angle0 = Angle::Normalize($angle-$angleofview/2); // hranice pohledu vlevo
     $view0 = self::PointInView($position,$angle0); // cilovy bod pohledu

     $angle1 = Angle::Normalize($angle+$angleofview/2); // hranice pohledu vpravo
     $view1 = self::PointInView($position,$angle1);
	   
     $speed = $this->save_speed($trial,$position,$time,$phase,$cil); // rychlosti se ukladaji po binech histogramu
     
     if($view0 && $view1){ // pokud neni existuje pohled - pozice neni mimo arenu
        $angle_view0 = Angle::Normalize($view0->AngleDiff($cil)); //relativne k cili -  chci mit cil uprostred histogramu na 180deg - nebo na kraji histogramu na 0deg
        $angle_view1 = Angle::Normalize($view1->AngleDiff($cil)); //relativne k cili -  chci mit cil uprostred histogramu na 180deg   
	     /* @var $this->histogramy[$trial][$phase] Histogram2D */
	     
	     $this->histogramy[$trial][$phase]->AddRange(array($angle_view0,$angle_view1),$time/$this->maxtime);
	     	//26.11.2012 - casova osa bude podil s casu trialu 0-1
	     
	     $this->save_marks_positions($trial,$phase,$cil);
	     
	     // uhly pohledu i znacek ve vyslednem histogramu jsou kodovany relativne vzhledem k cili - 0 je nahore, 90 je vpravo, cil je dole na 180
	     // 8.10.2012 
	     // 19.10.2012 - cile je na 0 deg, 180 je naproti cili
	     $this->table_views->AddRow(array($trial,$phase,$time,$time/$this->maxtime,$angle,$position->x,$position->y,
	        $view->x,$view->y,$angle_view0,$angle_view1,$speed));
	     $this->save_marktime($trial, $phase, $time,$position, $angle0, $angle1); 
	     	//26.11.2012 - casova osa bude podil s casu trialu 0-1
     } else {
     	 $this->table_views->AddRow(array($trial,$phase,$time,$angle,$position->x,$position->y,
          $view->x,$view->y,0,0,$speed));
     }
     $this->last_time = $time;
	}
	/**
	 * spocita frekvenci tabulky a ulozi 3 tabulky: viewhistogram, jednotlivych pohledu - views a marktime 
	 * @param string $filename
	 */
	public function SaveTable($filename){
		if(count($this->histogramy)>0){ 
				$table = new Table();
				foreach($this->histogramy as $trial => $trialdata){
					foreach($trialdata as $phase => /* @var $histo Histogram2D */ $histo){
						$histotable = new Table();
						$histotable = $histo->FreqTable(); 
						//19.10.2012 CHistogram bere vzdy 90deg protismeru rucicek od 0deg, takze hodnoty z leva od tabulky jdou protismeru rucicek

						$histotable->AddColumnData(array_fill(0,$histotable->RowCount()-1,$trial),"trial");
						$histotable->AddColumnData(array_fill(0,$histotable->RowCount()-1,$phase),"phase");
						$this->timebin_speed[$trial][$phase][]=0; // posledni bin, ktery nebyl v tracku dokonceny
						$histotable->AddColumnData($this->timebin_speed[$trial][$phase],"speed");
						
						$mark_i = 0; // uhlove pozice znacek na konec tabulky pohledu - kvuli kresleni do matlabu
						foreach($this->marks_positions[$trial][$phase] as $markname=>$markdata){
						  if(++$mark_i>MARKCOUNT) break; // hodnoty 1, 2, 3
						  $histotable->AddColumnData(array_fill(0,$histotable->RowCount()-1,String::intval($markname)),"MarkName$mark_i");
						  $histotable->AddColumnData(array_fill(0,$histotable->RowCount()-1,$markdata['angle']),"MarkAngle$mark_i");
						} 
						while(++$mark_i<=MARKCOUNT){ // pokud v tomto trial min znacek, doplnim, aby ve vysledke tabulce bylo porad stejne
							$histotable->AddColumnData(array_fill(0,$histotable->RowCount()-1,""),"MarkName$mark_i"); 
							$histotable->AddColumnData(array_fill(0,$histotable->RowCount()-1,""),"MarkAngle$mark_i");
						}
						$table->AppendTable($histotable);
					}
				}
				$table->SetPrecision(5);
				$table->SaveAll(true,CFileName::ChangeExtension($filename,"viewhisto.xls"));
				$table->SaveAll(true,CFileName::ChangeExtension($filename,"viewhisto.txt"),1); // matlab
				unset($table);
		}
    	if(VIEWS && $this->table_views->RowCount()>0){
		  $this->table_views->OpenFile(CFileName::ChangeExtension($filename,"views.xls"));
		  $this->table_views->SaveAll();
	  	}
		if(MARKVIEWS && $this->marks_views->RowCount()>0){
		  $this->marks_views->OpenFile(CFileName::ChangeExtension($filename,"markviews.xls"));
		  $this->marks_views->SaveAll();
	  	}
	  	
	  	// 5.11.2012 - casy pohledu na znacku
	  	if(count($this->marks_time)>0){
	  		$table = new Table();
	  		$table->AddColumns(array('trial','phase','timebin','timepart','angleerr'));
	  		$pocetznacek = count($this->marks_time[0][0][1]); // bin1 je tam vzdy
	  		for($cisloznacky = 1;$cisloznacky<=$pocetznacek;$cisloznacky++){
	  			$table->AddColumns(array("MarkName$cisloznacky","MarkTime$cisloznacky","MarkTimeDil$cisloznacky"));
	  		}
	  		foreach($this->marks_time as $trial=>$trialdata){
	  			foreach ($trialdata as $phase=>$phasedata){
	  				foreach($phasedata as $timebin=>$data){
	  					if(TRIALBINS){
	  						$timeofTimebin = $this->movedcasy[$phase][$trial][$timebin] - $this->movedcasy[$phase][$trial][$timebin-1]; //11.12.2012 -  kvuli tomu jsem v movedcasy udelal timebin0
	  					} else {
	  						$timeofTimebin = $this->trial_time[$trial][$phase]; // cas celeho trialu
	  					}
	  					$movedratio = $timeofTimebin/$this->movedcasy[$phase][$trial][3]; // 11.12.2012 cas timebinu uz nebude kumulativni
		  				$table->AddToRow(array($trial,$phase,$timebin,$movedratio,$this->movedangleerr[$phase][$trial][$timebin]));
		  				$cisloznacky = 0;
		  				foreach($data as $markname => $time){
		  					//cas znacky relativne k casu timebinu
		  					$table->AddToRow(array( (int) substr($markname,1),$time,$timeofTimebin>0 ? $time/$timeofTimebin : 0));// stara verze:$time/$this->trial_time[$trial][$phase]
		  					$cisloznacky++;
		  				}
		  				while($cisloznacky++<$pocetznacek) $table->AddToRow(array(0,0,0));
		  				$table->AddRow();
		  			}
	  			}
	  		}
	  		$table->SetPrecision(5);
			$table->SaveAll(true,CFileName::ChangeExtension($filename,"marktime.xls"));
			$table->SaveAll(true,CFileName::ChangeExtension($filename,"marktime.txt"),1);
	  	}
	  	if(!empty($this->speedarr)){
	  		$output= "";
	  		foreach($this->speedarr as $phase =>$phasedata)
	  			foreach($phasedata as $trial=>$trialdata){
	  				$speedline = "$phase\t$trial";
	  				foreach($trialdata as $data){
	  					$speedline .= "\t".$data[0]; // speed; index 1 = distance
	  				} 
	  				$output .= $speedline."\n";
	  			}
	  		file_put_contents(CFileName::ChangeExtension($filename,"speed.txt"), $output);
	  	}
	}
	/**
	 * vraci bod na okraji areny, na ktery miri pohled z position 
	 * @param CPoint $position
	 * @param deg $angle
	 * @return CPoint
	 */
	static function PointInView($position,$angle){
		 $line = new CLine();
	     $line->DefineByPointAngle($position,$angle);
	     $intersection = $line->CircleIntersection(new CPoint(0,0),ARENAR);
	     if(count($intersection)>1){
					if(   ($angle > 180 && $intersection[0]->y < $intersection[1]->y) // dolu
					   || ($angle < 180 && $intersection[0]->y > $intersection[1]->y) // nahoru
					){ 
					  // uhel > 180 miri dolu a proto vybiram prusecik s mensim y nez puvodni bod
					  return $intersection[0];
					} else {
					  return $intersection[1];
					}
	     } elseif (count($intersection)==1) {
	     	  return $intersection[0];
	     } else {
	     	 return false; // zadny prusecik primky a kruznice
	     	 // napriklad tehdy, kdyz je bod mimo arenu - chyba trackovani
	     }
	}
	/**
	 * ulozi prvky laser z pole pozic znacek do $this->marks_positions
	 * @param int $trial
	 * @param int $phase
	 * @param CPoint $cil
	 * @param array $positions
	 */
	private function save_marks_positions($trial,$phase,$cil){
		 $cil_angle = 360-$cil->Angle(); // potrebuju mit 90 dolu,0 vpravo, jako jsou ulozene znacky
		 foreach($this->marks_position_abs as $markname => $mark){ //laser: M1=314, M2=65,M3=226
		 	$angle = Angle::Normalize(360-($mark['laser']-$cil_angle)); // uhel znacky vzhledem k cili - 0= cil, 90 je vpravo od cile, kdyz se divam od cile
		 	$markxy = clone $mark['xy']; // potrebuju kopii, abych neotocil i puvodni souradnice 
		 	$this->marks_positions[$trial][$phase][$markname]=array("angle"=>$angle,"xy"=>$markxy->reverseY(),"radius"=>$mark['radius']);
		 		// radius je uz prepocteny na ARENAR=140
		 		// cil chci mit na 180 a 90 vpravo, 0 nahore
		 		// 19.10.2012 - cil je na 0deg, 180 je naproti cili ANGLE: M1=135deg,M2=25deg,M3=223deg
		 		//Angle::Normalize($mark['laser']-$cil_angle+180);
		 	// ukladam uhlove pozice znacek relativne k cili. Cil se pocita na pozici 180
		 }
	}
	/**
	 * @param int $trial
	 * @param CPoint $position
	 * @param double $time
	 * @param int $phase
	 * @param float $maxtime maximalni cas trialu
	 * @param array of CPoint $lastxy prvni a posledni bod tracku v aktualnim trialu
	 * @param float $goalr polomer cile (kde polomer areny = 140)
	 * @param CPoint $goalxy
	 * @return double
	 */
	private function save_speed($trial,$position,$time,$phase,$goalxy){
	  if(!isset($this->last_trial) || $trial != $this->last_trial){
        // Zacina novy trial
        unset($this->last_position);
        unset($this->last_timebin);
        unset($this->last_binposition);
        $this->last_time = 0; //5.11.2012 - cas se pocita zvlast v kazdem trialu od 0
    }
    
	  if(isset($this->last_position) && isset($this->last_time) && $time > $this->last_time){
       $speed = $position->Distance($this->last_position)/($time-$this->last_time); 
     } else {
       $speed = 0;
     }
     
     $timebin = $this->histogramy[$trial][$phase]->Bin(1,$time/$this->maxtime);
     if(!isset($this->last_timebin) || $this->last_timebin != $timebin){
     	 // zacatek binu
     	 if(isset($this->last_binposition)){
     	   $this->timebin_speed[$trial][$phase][$timebin]=$position->Distance($this->last_binposition);
     	 } else {
     	 	 // prvni bin v trialu
     	 	 $this->timebin_speed[$trial][$phase][$timebin] = 0;
     	 	 $this->timebin_speed[$trial][$phase][0] = 0; // hypoteticky nulty bin, kde jsou casy mensi nez 0
     	 }
     	 $this->last_binposition = clone $position;
     	 
     }
     
     
     $this->last_trial = $trial;
     $this->last_timebin = $timebin;
     $this->trial_time[$trial][$phase]=$time; //predpokladam, ze se postupne zvysuje :-) - je to doufam stejne cislo jako this->maxtime
     
     $distance = $position->Distance($this->lastxy[1] /*posledni bod tracku*/); 
	 if(!isset($this->moved[$phase][$trial])){
     	$this->moved[$phase][$trial]=1;
     	$this->movedcasy[$phase][$trial][0]=0; //nulovy time bin, jen docasne pro pocitani rozdilu casu
     } elseif($this->moved[$phase][$trial]==1 && $speed>=19){ // pokud se pohnul, ulozim to; - kvuli oddeleni pohledu na startu od dalsi casti trialu
     	//3.12.2012 - jako hranici pohnuti budu brat polovinu maximalni rychlosti = 19, viz graf z matlabu v markeyetime II.cdr
     	$this->moved[$phase][$trial]=2; // cislo 3 se uklada v this->trialbin
     		// prvni bin je odchod ze startu - cas i uhlova chyba
     	$this->movedcasy[$phase][$trial][1]=$time;
     	$this->movedangleerr[$phase][$trial][1] = $position->AngleDiff($goalxy,$this->lastxy[0]/*prvni bod tracku*/);
     } elseif($this->moved[$phase][$trial]<3 && $distance<=$this->goalr && ($speed==0 || $this->goalbyentrance)){
     	$this->moved[$phase][$trial]=3; // dostal se do cilove pozice
			//druhy bin je prichod do okoli cilove pozice
     	$this->movedcasy[$phase][$trial][2]=$time;
     	$this->movedangleerr[$phase][$trial][2] = $position->AngleDiff($goalxy,$this->lastxy[0]/*prvni bod tracku*/);
     		// pokud nebyl trialbin 1
     	if(!isset($this->movedcasy[$phase][$trial][1])) $this->movedcasy[$phase][$trial][1]=$time;  //pokud start byl rovnou v okoli cilove pozice
     	if(!isset($this->movedangleerr[$phase][$trial][1])) $this->movedangleerr[$phase][$trial][1]=$this->movedangleerr[$phase][$trial][2];
			//bin3 je oznacuje konec trialu - i cas i uhlova chyba
     	$this->movedcasy[$phase][$trial][3]= $this->maxtime; // maximalni cas aktualniho trialu, plni se v NextTrial
     	$this->movedangleerr[$phase][$trial][3] = $this->lastxy[1]->AngleDiff($goalxy,$this->lastxy[0]/*prvni bod tracku*/);
     }
	 // budu pouzivat jen na pozadani: $this->speedarr[$phase][$trial][]=array($speed,$distance);
	 // uklada se v SaveTable, pokud se plni
     $this->last_position = clone $position;
     return $speed;
	}
	/**
	 * zvysi celkovy cas pohledu na znacky; 
	 * clovek se diva na znacku pokud se rozmezi uhlu jeho pohledu prekryva s uhlovou velikosti znacky
	 * @param int $trial
	 * @param int $phase
	 * @param double $time
	 * @param CPoint $pozice aktualni pozice subjektu, kladne y nahoru
	 * @param deg $angle_view0 uhel pohledu z pozice cloveka!
	 * @param deg $angle_view1
	 * @since 5.11.2012
	 */
	private function save_marktime($trial,$phase,$time,$pozice,$angle_view0,$angle_view1){
		foreach($this->marks_positions[$trial][$phase] as $markname=>$markdata ){
			// $markangle = $markdata['angle']; - pouzito pokud pocitam pohled na znacku z casti steny areny
			$trialbin = $this->trialbin($trial, $phase, $time, $this->maxtime);
			
			if(!isset($this->marks_time[$trial][$phase][$trialbin][$markname])) $this->marks_time[$trial][$phase][$trialbin][$markname] = 0;
			if($trialbin>1 && !isset($this->marks_time[$trial][$phase][$trialbin-1][$markname])) $this->marks_time[$trial][$phase][$trialbin-1][$markname] = 0;
			// 3.12.2012 - vyjimecne se stava,ze se objevi v cilove pozici hneda na start - pak by nenastal bin 2; proto ta predchozi radka
			
			$markangle = $markdata['xy']->Angle($pozice); // uhel znacky vzhledem k pozici cloveka // 13.11.2012 - pohled na znacku pocitan ze skutecne pozice znacky
			$viewanglemark = Angle::ViewAngle($markdata['xy']->Distance($pozice), $markdata['radius']);
			$markangle0 = $markangle-$viewanglemark/2;
			$markangle1 = $markangle+$viewanglemark/2;
			if(Angle::Intersection($angle_view0, $angle_view1, $markangle0, $markangle1) && $viewanglemark>0){
				$pohled = $time-$this->last_time; // do tohohle binu chci ukladat skutecny cas v sec
				$this->marks_time[$trial][$phase][$trialbin][$markname] += $pohled; 
				//26.11.2012 - casova osa bude podil z casu trialu 0-1
			} else {
				$pohled = 0;
			}
			$this->marks_views->AddRow(array($trial,$phase,$time,$time/$this->maxtime,$pozice->x,$pozice->y,$angle_view0,$angle_view1,
				$markname,$markdata["xy"]->x,$markdata["xy"]->y,$markangle,$viewanglemark,$pohled));
		}
		
		//[$trial][$phase][$time]=$markviews; //shromazduju vsechny pohledy na znacky
	} 
	/**
	 * vrati bin v ramci trialu (cast trialu, bud podle casu nebo podle zacatku chuze a blizkosti cilove pozice)
	 * @param int $trial
	 * @param int $phase
	 * @param float $time
	 * @param float $maxtime
	 * @return int
	 * @since 30.11.2012
	 */
	private function trialbin($trial,$phase,$time,$maxtime){
		//$timebin = $this->histogramy[$trial][$phase]->Bin(1,$time/$maxtime);	//26.11.2012 - pohledy na znacky chci uklada po desetinach trialu
		$trialbin = $this->moved[$phase][$trial];
		return TRIALBINS?$trialbin:1; 
	}
}


?>