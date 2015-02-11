<?php
require_once('classes/image.class.php');
require_once('classes/cvararr.class.php');
require_once 'includes/stat.inc.php';
require_once 'classes/TableFile.class.php';
require_once 'classes/czasah.class.php';

if(!defined('PERSONNAMES')) define('PERSONNAMES',0);

class WholeImage {
  private $zasahy; // array [$track][$trial][$phase][]
  private $track; // cislo tracku, ktere se zpracovava - pro kazdy soubor jeden track, ten posledni
  private $phases;
  private $trials;
  private $arena_radius;
  private $goal_radius; // polomer cile [$phase]
  private $subjects; // pocet lidi
  private $goal_position; // pozice cile [$exp][$trial][$phase]
  private $mark_position; // pozice znacek [$exp][$trial][$phase][$l] - napr [kontrola][C1][0][L3]
  private $start_positions; // pozice startu [$exp][$trial][$phase] - napr [kontrola][C1][0]
  private $previous_goal_positions; // predchozi pozice cile - vyplnuje se u fazi, kde neni definovan start [$exp][$trial][$phase]
  private $exp_delimiter;
  private $names_order = array(); // pole urcujici poradi jmen pokusu (napr 
  private $namegroups = array();// preddefinovane skupiny trialu
  /**
   * [exp][track][trial][phase]dist
   *
   * @var array
   */
  private $distfromgoal;
  /**
   * [exp]filename - seznam souboru v jednotlivych exp
   * @var array
   */
  private $filenames;
  /**
   * tvoreny obrazek
   *
   * @var image
   */
  private $img;
  /**
   * pole poctu trialu se stejnym nazvem (nebo konfiguraci znacek); napr C1=4,C2=4,A1=4 atd
   * @var array
   */
  private $namecount; 
  private $cilstd; // poloha cile uhlove stejna pro cely wholeimg
  private $group_data; // sumarni data pro skupinu // prumer, stderr, pocet
  /**
   * seznam souboru podle skupina s hodnotu 1 pro soubory v poradku a 0 pro chybejici nebo chybne souboru
   * @var array[group][filename]=0/1
   */
  private $listOfFiles; 
  function __construct($arena_radius,$exp_delimiter='.'){
    $this->arena_radius = $arena_radius;
    $this->exp_delimiter = $exp_delimiter;
    //$this->goal_radius = $goal_radius;
  }
  /**
   * pridata data z jednoho cloveka (=jedne polozky filelistu)
   *
   * @param array $cilearr [track][trial][phase] goal,hit,goaltype,goalname,,markname ... bod_symetry,rel_to,name
   * @param string $filename souboru
   * @param array $goal_r prumery cile v procentech polomeru areny - pole podle faze
   * @param string $exp jmeno skupiny (napr virtual/real, tr3, tr4)
   */
  public function Add($cilearr,$filename,$goal_r,$exp){
    if(WHOLEIMAGE){
        //if(!$exp) $exp = $this->Exp_name($filename); // bud pripona souboru ,nebo cast mezi _ a teckou
        if(isset($cilearr) && is_array($cilearr)){
            if(SPANAVDATA)
               $namecount = $this->AddSinglePhase($cilearr,$exp,$filename); // spanav data
            else
               $namecount = $this->AddMaxTrack($cilearr,$exp,$filename); // bva data
            
            // prekopiruju pocty do promenne pro celou tridu. ta se pak pouziva pri generovani obrazku
			      if(empty($this->namecount)) {
			        $this->namecount = $namecount;
			      } else {
			        foreach ($namecount as $name => $count){
			          if( empty($this->namecount[$name]) || $this->namecount[$name]<$count){
			            $this->namecount[$name] = $count;
			          }
			        }
			      }
			      
		        if(!isset($this->filenames[$exp]) || !in_array(basename($filename),$this->filenames[$exp])){
	              $this->filenames[$exp][]=basename($filename); // seznam souboru od kazdeho exp (napr. tr1)
            }
            $this->SetCilRadius($exp,$goal_r);
            if(isset($this->subjects[$exp])) $this->subjects[$exp]++; else $this->subjects[$exp]=1;
            $this->listOfFiles[$exp][basename($filename)]=1; // soubor v poradku ulozen
        }
    }
  }
  /**
   * vlozi udaje o chybejicim nebo chybnem souboru
   * @param string $filename
   * @param string $exp
   * @since 4.2.2013
   */
  public function AddMissing($filename,$exp){
  		$this->listOfFiles[$exp][basename($filename)]=0; // soubor chybi
  }
  /**
   * data z BVA, bere se jen posledni track se souboru, protoze ten je asi spravny
   * exp je napriklad tr2, pokud nejsou oznaceny skupiny subjektu ve filelistu
   *
   * @param array $cilearr
   * @param string $exp 
   * @param string $filename
   * @return array
   */
  private function AddMaxTrack($cilearr,$exp,$filename){
   // data z BVA
   $track = max(array_keys($cilearr)); // budu zpracovavat jen posledni track - ten je nejspis dobre
   $this->track = $track;
   $namecount = array(); // cislo trialu v ramci name, pro kazde name zvlast
    foreach ($cilearr[$track] as $trial => $trialdata) {
    	  // TRIALY
        $this->trials[$exp] = isset($this->trials[$exp])?max($this->trials[$exp],$trial+1):$trial+1;
        $previous_goal = false; // predchozi pozice cile, naplnim na konci nasledujiciho cyklu
      	foreach ($trialdata as $phase => /* @var $zasah CZasah */ $zasah) {// pro vsechny faze
      		// PHASE
      	  if($phase >=0) {
        	  $this->phases[$exp] = isset($this->phases[$exp])?max($this->phases[$exp],$phase+1):$phase+1;
            
            if(!isset($namecount[$zasah->goalname])) $namecount[$zasah->goalname]=0;
            
            if($zasah->goaltype=='g' && !$zasah->Excluded()) { // beru jen cile, ktere byly oznacene pomoci g
            	$this->zasahy[$exp]
            	      [$zasah->goalname]
            	      [$namecount[$zasah->goalname]]
            	      [basename($filename)]=$zasah->hit;
            	      
	            
	            $this->goal_position[$exp]
	                  [$zasah->goalname]
                    [$namecount[$zasah->goalname]]
                    [basename($filename)] = $zasah->goal;
	            
              //hodnoty do sumarnich tabulek
              $this->add_measures($zasah,$exp,$zasah->goalname,$namecount[$zasah->goalname],$filename,$trial,$phase);
	            
	            // pozice znacek do souhrnneho obrazku
              if(!isset($this->mark_position[$exp] [$zasah->goalname] [$namecount[$zasah->goalname]]))
              $this->mark_position[$exp]
                    [$zasah->goalname] // napriklad M1M2
                    [$namecount[$zasah->goalname]] // cislo opakovani goalname;
                     = $zasah->markpositions;

              // pozice startu do souhrnneho obrazku - 29.7.2010
              if(!isset($this->start_positions[$exp] [$zasah->goalname] [$namecount[$zasah->goalname]])){
              	if($zasah->startposition<0 && $previous_goal) $this->add_previous_goal($previous_goal,$exp,$zasah->goalname,$namecount[$zasah->goalname]);
	              while($zasah->startposition<0 && $phase >0){ // to se stane, pokud je start z predchozi faze - nezobrazuje se v teto fazi
	              	$phase--; 
	              	$zasah->startposition = $trialdata[$phase]->startposition;
	              } 
	              $this->start_positions[$exp]
	                    [$zasah->goalname] // napriklad M1M2 nebo C1
	                    [$namecount[$zasah->goalname]] // cislo opakovani goalname;
	                     = $zasah->startposition;      
              } 
	            $namecount[$zasah->goalname]++; 
	            $this->namegroups[$zasah->goalname] = $zasah->namegroup;
            }
            if($zasah->Excluded()) $namecount[$zasah->goalname]++;  // vyrazeny trial nezvysil namecount vyse - 21.9.2010
      	  }
      	  $previous_goal = $zasah->goal;
        }
    }
    return $namecount; // pocty pokusu v kazdem name (C1C2)
          // nemelo by to byt i podle exp ?
  }
  /**
   * data SPANAV
   * v jednom souboru je vice tracku, ktere jsou vsechny spravne
   * track zde zastupuje trial, a trial zastupuje fazi. $cili co radek to track s jednotlivymi trialy
   * phase je jen 0;
   *
   * @param array $cilearr [track][trial][phase] cil,bod,bod_symetry,rel_to,name,markname
   * @param string $exp skupina subjektu oznacena ve filelistu 
   * @param string $filename
   * @return array
   */
  private function AddSinglePhase($cilearr,$exp,$filename){
      // data ze spanavu
      $namecount = array(); // cislo trialu v ramci name, pro kazde name zvlast
      foreach ($cilearr as $track => $trackdata) {
         $this->trials[$exp] = isset($this->trials[$exp])?max($this->trials[$exp],$track+1):$track+1; // $track zde zastupuje trial
         foreach ($trackdata as $trial => $trialdata) {
         	  // TRIALY
        	  $this->phases[$exp] = isset($this->phases[$exp])?max($this->phases[$exp],$trial+1):$trial+1;
        	  /* @var $zasah CZasah */
        	  $zasah = $trialdata[0]; // jen faze 0
        	  if(!isset($namecount[$zasah->markname])) $namecount[$zasah->markname]=0;
        	  
        	  if($zasah->goaltype=='g') { // beru jen cile, ktere byly oznacene pomoci g
	            $this->zasahy[$exp]
	                [$zasah->markname]
	                [$namecount[$zasah->markname]]
	                [basename($filename)] = $zasah->hit;
	                
	            $this->goal_position[$exp] // tyhle pozice cile pak musim otocit aby se prekryvaly 
	                [$zasah->markname]     // a spolecne s nima i zasahy
	                [$namecount[$zasah->markname]]
	                [basename($filename)] = $zasah->goal;
	            
	            $this->add_measures($zasah,$exp,$zasah->markname,$namecount[$zasah->markname],$filename,$trial,0);
              
	            // pozice znacek do souhrnneho obrazku
	            if(!isset($this->mark_position[$exp][$zasah->markname]  [$namecount[$zasah->markname]]))
		          $this->mark_position[$exp]
		                [$zasah->markname] // napriklad M1M2
		                [$namecount[$zasah->markname]] // cislo opakovani markname;
		                 = $zasah->markpositions;
	            
		          // pozice startu do souhrnneho obrazku - 29.7.2010
              if(!isset($this->start_positions[$exp] [$zasah->goalname] [$namecount[$zasah->goalname]])){
              	while($zasah->startposition<0 && $trial >0){ // to se stane, pokud je start z predchozi faze - nezobrazuje se v teto fazi
                  $trial--; 
                  $zasah->startposition = $trialdata[$trial]->startposition;
                } 
                $this->start_position[$exp]
                    [$zasah->goalname] // napriklad M1M2 nebo C1
                    [$namecount[$zasah->goalname]] // cislo opakovani goalname;
                     = $zasah->startposition; 
              }
	            //$this->distfromgoal[$exp][$track][$trial]['name']=$zasah['name'];
	            //$this->distfromgoal[$exp][$track][$trial]['files'][basename($filename)]=distance($zasah['bod'],$zasah['cil']);
	            $namecount[$zasah->markname]++; 
        	  }
	          
          }
      
      }

      return $namecount;
  }
  /**
   * vytvori a ulozi souhrnny obrazek
   * vola ostatni dilci funkce tridy
   * pouziva pole zasahy [exp][name][n][subject]=[x,y]
   * 
   * @param string $filename
   */
  public function SaveImg($filename){
    if(WHOLEIMAGE) {
        $size = ($this->arena_radius+10) * 2;
        foreach($this->zasahy as $exp=>$trackdata){ // u BVA dat je exp napriklad tr1
        	$this->sort_table_marks(array_keys($this->namecount));
        	$posledni_mark = end($this->names_order);
//        	print_r($this->names_order); exit;
          $this->img = new Image($size,$size,$posledni_mark['poradi']+1);
          $this->Areny($exp);
          $this->Cile($exp);
          $this->Marks($exp);
          $this->Zasahy($trackdata,$exp);
          $fileoutname = $this->outdir."/".$filename."_".$exp;
          if(!file_exists(dirname($fileoutname))) mkdir(dirname($fileoutname));
          if($this->img->SaveImage($fileoutname))
              dp("whole image saved: ".basename($fileoutname));
          $this->SaveHtml($fileoutname);
          
    //      ob_start();
    //      print_r($this->zasahy);
    //      error_log(ob_get_clean(),3,"zasahy.txt");
        }
    }
  }
  /**
   * do souhrnneho obrazku nakresli areny
   * @param string $exp
   */
  private function Areny($exp){
    foreach($this->namecount as $name=>$count){
        if(!$this->img->SubplotActivate($this->names_order[$name]['poradi'])){ // tim budu radit
          echo "areny"; exit;
        }
        $this->img->Circle(array(0,0),$this->arena_radius,"black",false);
        $this->img->Text(array(-$this->arena_radius,-$this->arena_radius+10),12,'black',$name);
    }
  }
  /**
   * do souhrnneho obrazku nakresli cile
   * @param string $exp
   */
  private function Cile($exp){
  	$skupina0 = reset($this->names_order); // chci mit cil ve vsech obrazcich na jednom miste
//  	print_r($skupina0);
//  	print_r(array_keys($this->goal_position[$exp]));
//  	exit;
    /* @var $cil0 CPoint */
    $cil0 = reset($this->goal_position[$exp][$skupina0['name']][0]); // cil je porad na stejne miste
        // do 14.9.2010 tu bylo $skupina0['skupina'], ale u mwm2 se jmeno skupiny neshoduje s zadnym jmenem cile
        // cil je treba M1a a skupina M1
    $dist0 = $cil0->Distance();
    $this->cilstd = new CPoint();
    $this->cilstd->DefineByAngleDistance(180,$dist0); // chci aby cil byl pokazde vlevo uprostred
    
    foreach($this->goal_position[$exp] as $name => $goals){
        if(!$this->img->SubplotActivate($this->names_order[$name]['poradi'])){
          echo "cile ";
          exit;
        }
        
      	$this->img->Circle($this->cilstd,$this->goal_radius[$exp][0]*$this->arena_radius/100,"red",false);
      	                                                      // [0] znamena prvni faze/track
      	                                                      // bere se prvni pozice cile prvniho cloveka
	    
		    if(isset($this->previous_goal_positions[$exp][$name])){
		    	$prev_goal_arr = array();
	      	foreach($this->previous_goal_positions[$exp][$name] as /* @var $previous_goal CPoint */ $n=>$previous_goal){
			    	  $cil1 = reset($goals[$n]);  // reset znamena prvniho cloveka ze skupin, 0 je prvni opakovani
			    	  $angle = $this->cilstd->AngleDiff($cil1);
			    	  $prev_goal = clone $previous_goal;
			    	  $prev_goal->Rotate($angle);
			    	  if(!in_array($prev_goal,$prev_goal_arr)){
				    	  $this->img->Circle($prev_goal,10,"green",false,3);
				    	  $this->img->Text($prev_goal,10,"blue",$n);
				    	  $prev_goal_arr[]=$prev_goal;
			    	  }
			    }
		    }
    }
  }
  /**
   * do souhrnneho obrazku nakresli pozice znacek
   * exp je napriklad 'kontrola'
   * @param string $exp
   */
  private function Marks($exp){
    foreach($this->mark_position[$exp] as $name => $marks){
    	  // $name je treba C1, A1 aj
        if(!$this->img->SubplotActivate($this->names_order[$name]['poradi'])){
          echo "cile ";
          exit;
        }
        // MARKS pro prvni opakovani toho $name
			        //$skupina0 = reset($this->names_order);
			        //$cil0 = reset($this->goal_position[$exp][$skupina0['skupina']][0]);
			        // do 14.9.2010 dale $this->names_order[$name]['skupina']
        $cil1 = reset($this->goal_position[$exp][$name][0]); // reset znamena prvniho cloveka ze skupin, 0 je prvni opakovani
        $angle = $this->cilstd->AngleDiff($cil1);
        foreach ($marks[0] as $l=>$mark){ // jen znacky z prvniho trialu
        	$laser =  $angle + $this->mark_position[$exp][$name/*$this->names_order[$name]['skupina']*/][0][$l]['laser']; // pozice znacky stejna jako tak, kde jsou 3 znacky
        	while($laser < 0)   $laser += 360;
        	while($laser > 360) $laser -= 360;
          $this->img->Cue(array(0,0),ARENAR,$laser,$mark['segment'],0,$mark['markname']);
                                                              // [0] znamena prvni faze/track
        }
        // STARTY pro vsechny opakovani toho $name 
        if(isset($this->start_positions[$exp][$name])){ // ve spanav datech zatim nejsou starty
	        foreach($this->start_positions[$exp][$name] as $opak=>$pozice){
	        	if($pozice>0){ // nekdy je pozice -1 - zatim, kdyz nebyla mackana klavesa c
		        	$cil1 = reset($this->goal_position[$exp][$name/*$this->names_order[$name]['skupina']*/][$opak]);
		          $angle = $this->cilstd->AngleDiff($cil1);
		        	$laser = $angle + $pozice;
		        	while($laser < 0)   $laser += 360;
		          while($laser > 360) $laser -= 360;
		          $this->img->Cue(array(0,0),ARENAR,$laser,0,1);
	        	}
	        }
        }
    }
  }
  /**
   * do souhrnneho obrazku nakresli zasahy
   * @param array $trackdata [trial][phase][subjectname] = [x,y]
   */
  private function Zasahy($trackdata,$exp){
    foreach($trackdata as $name=>$body) { // body jsou collapsed pres marknames = body pro ruzne lidi a opakovani
      	if(!$this->img->SubplotActivate($this->names_order[$name]['poradi'])){
          echo "zasahy"; exit;
        }
        $skupina0 = reset($this->names_order); // chci mit cil ve vsech obrazcich na jednom miste
//        $cil0 = reset(reset($this->goal_position[$exp][$skupina0['skupina']]));
//        $cil0 = new CPoint($cil0); // cil ktery se zobrazuje na obrazku, rotace bude k nemu
        
      	foreach($body as $n=>$subjectdata){ // jednotlivi lidi
      		foreach($subjectdata as $subject => $zasah){
      			$cil1 = new CPoint($this->goal_position[$exp][$name][$n][$subject]);
            $angle = $this->cilstd->AngleDiff($cil1);
      			$zasah = new CPoint($zasah);
	      	  $this->img->Circle($zasah->Rotate($angle),2,"black",true);
	      	  if(PERSONNAMES) $this->img->Text(bodadd($zasah,array(5,5)),9,'blue',$subject.':'.$this->distfromgoal[$exp][$name][$n][$subject]['trial']);
      		}
      	}
    }
  }
  /**
   * nastavi adresar na ulozeni vysledneho obrazku
   *
   * @param string $dir
   */
  public function SetDir($dir){
    $this->outdir = $dir;
  }
  /**
   * vrati pocet lidi v souhrnem obrazku
   * @return int
   */
  public function Persons(){
    return !empty($this->subjects) && is_array($this->subjects)?max($this->subjects):0;
  }

  /**
   * Enter description here...
   *
   * @param array $goal_r
   */
  private function SetCilRadius($exp,$goal_r){
    if(SPANAVDATA){
        foreach($goal_r as $track=>$trackdata){
          if(!isset($this->goal_radius[$exp][$track]))
            $this->goal_radius[$exp][$track]=$trackdata[0]; // jen jedna phase
        }
    } else {
        foreach($goal_r[$this->track] as $phase=>$r){// jen jeden track
          if(!isset($this->goal_radius[$exp][$phase]))
            $this->goal_radius[$exp][$phase]=$r; // [$track][$phase] -  v procentech polomeru areny
        }
    }
  }

  /**
   * vytvori html, ktere odkazuje na obrazek svg
   * @param string $fileoutname
   */
  private function SaveHtml($fileoutname){
		$html = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
		<html>
  		<head>
  			<meta http-equiv=\"content-type\" content=\"text/html; charset=windows-1250\">
  			<title>".basename($fileoutname)."</title>
  		</head>
  		<body>";
	    $html .="<embed src=\"./".basename($fileoutname).".svg\" width=\"".($this->img->sizex)."\" height=\"".($this->img->sizey)."\" type=\"image/svg+xml\"
					 pluginspage=\"http://www.adobe.com/svg/viewer/install/\" /><br>\n";


		$html .="</body></html>";
		if(!@file_put_contents($fileoutname.".html",$html)){
			echo "\n!!nemohu zapsat do souboru $fileoutname.html\n";
		}
	}
	public function Beep($n){
	  for($i=0;$i<$n;$i++){
	      echo chr(7); // beep
	      usleep(300000); // 0.3 s
	  }
	}
	/**
	 * ulozi tabulky s chybami do nekolika souboru xls
	 * @param string $outname
	 * @param string $dir
	 */
	public function TableExport($outname,$dir="./"){
		// tabulka dat z kazdeho cloveka
		//$this->table_persongroups_export($outname,$dir);
		
		//tabulka prumeru, stderr a count za skupiny
		$this->table_avg_export($outname,$dir);
		
		//tabulka subjektu pro statistiku
		$this->table_stat_export($outname,$dir);
		
	}
	private function setdelim($str){
    return preg_replace("/([0-9])\.([0-9])/","$1".TABLEFILE_DELIM."$2",$str);
  }
  /**
   * vytvori pole names_order, podle ktereho se pak budou radi jmena pokusu
   * zatim jen pro ivetin pokus ve spanavu, kde jsou jmena pokusu jako M1M2M3 atd
   * 
   * @param array $marknames
   * @return bool jestli pole naplneno
   */
  private function sort_table_marks($marknames){
  	// funkce ktera seradi tabulku distfromgoal
  	// kroky 
  	// 1. zjistim maximum poctu znacek
  	// 2. zjistim unikatni hodnoty markname z timto max poctem - napr M1M2M3 a M7M8M9
  	// 3. pokud se znacky v nich prekryvaji, neradim nic a koncim - ?? funkce na prunik dvou poli?
  	// 4. pokud se neprekryvaji vytvorim cisla skupin podle techto unikatnich hodnot A a B
  	// 5. pujdu po vsech trialech a budu je radit do jedne nebo druhe skupiny, nebo mimo
  	// 6. seradim podle skupin a klesajicim poctem znacek
  	$pole = array(); // tam budu radit znacky
    $pocetmax = 0;
    
    $jsou_skupiny = false;
  	foreach($marknames as $name) {
  		$pole[$name]['pocet']= substr_count($name,"M"); // jen pro ivetiny data, u ostatnich neni jak zjistit pocet znacek
  		$pole[$name]['name']=$name;
  		$pocetmax=max($pocetmax,$pole[$name]['pocet']);
  		if(isset($this->namegroups[$name])){
  			$pole[$name]['skupina']=$this->namegroups[$name]; // skupiny definovane ve filelistu
  			$jsou_skupiny = true;
  		}
  	}
  	if(!$jsou_skupiny) { // pokud skupiny nebyly definovane ve filelistu
	  	if($pocetmax == 0) {
	  		$jsou_skupiny = false; // zadne M ve jmenech znacek, takze neradim
	  		foreach($pole as $name=>&$data)
	         $data['skupina']=$data['name']; // zadne skupiny nebudu pouzivat a pole nebudu radit
	  	} else {
		  	$skupiny_znacek = array(); // napr M1M2M3 a M7M8M9, ale reprezentovane jako pole 1 2 3 a 7 8 9
		    $jsou_skupiny = true;
		    foreach($pole as $name=>&$data){
		      if($data['pocet']==$pocetmax && !isset($skupiny_znacek[$name])){
		        if($this->table_marks_intersect($name,$skupiny_znacek)) $jsou_skupiny = false; // nebudu nijak radit
		        $skupiny_znacek[$name]= explode("M",substr($name,1));
		      }
		    }
		  	if($jsou_skupiny){
		      foreach ($pole as $name=>&$data) {
		        if(($data['skupina']=$this->table_marks_intersect($name,$skupiny_znacek))==false) {
		           $jsou_skupiny = false; // nejake skupina znacek nepatri do zadne skupiny s maximalnim poctem znacek
		           // pro BVA data zatim nebudou zadne skupiny 
		        }
		      }
		    }
	  	}
  	}
    
    if($jsou_skupiny) uasort($pole,"cmp_wholeimage_skupiny_znacek");
    // seradim pomoci usort a pak ji postupne priradim poradi $data['poradi']
    // jak podle toho vzdy seradim ostatni pole pred jejich pouzitim
    
    $poradi = 0;
    $skupina = '';
    foreach ($pole as $name => &$data) {
    	if($jsou_skupiny && !empty($skupina) && $skupina != $data['skupina'] && $poradi%IMAGELINEARCOLUMNS!=0){
    		$poradi = IMAGELINEARCOLUMNS *(intval($poradi/IMAGELINEARCOLUMNS) + 1); // zacatek dalsi radky na celem obrazku
    	}
    	$data['poradi']=$poradi++;
    	$skupina = $data['skupina'];
    }
    
    $this->names_order = $pole;
    //print_r($pole);
    //exit;
    return true;
  }
  
  private function table_marks_intersect($name,$skupiny_znacek){
      if(count($skupiny_znacek)>0){
          foreach($skupiny_znacek as $skupina_name=>$skupina){
            if(count(array_intersect($skupina,explode("M",substr($name,1))))>0){
              return $skupina_name; // nejaka  spolecna mezi $name a existujicimi skupinami
            }
          }
        } 
        return false; // dalsi skupina znacek $name nema zadnou podobnou ve skupine
  }
  /**
   * vrati groupdata array predelany takze skupina je na konci
   * @return array
   */
  private function groupdata($measure='data'){
  	$group_data = array();
  	//debug_print_r($group_data,"group_data");
  	foreach($this->group_data as $exp=>$expdata){ // group je pripad exp
  		foreach($this->names_order as $name => $namedata)
  		  foreach($expdata[$name] as $n=>$ndata){
  		  	$group_data[$name][$n][$exp]=$ndata[$measure];
  		  }
  	}
  	
  	return $group_data;
  }
  /**
   * vrati data usporadana podle skupin a subjektu
   * @param string $measure
   * @return array
   */
  private function statdata($measure='data'){
    $statdata= array();  	
  	foreach ($this->distfromgoal as $exp => $expdata) { // exp je group, napriklad tr3
  		foreach ($expdata as $name => $namedata) {   // jmeno faze napriklad C1,C2,A1A2
  			foreach ($namedata as $n => $ndata) {   // opakovani faze 
  				foreach ($ndata as $filename => $data) { // filename je jmeno subjektu
  					$statdata[$exp][$filename][$name][$n]=$data['distance'][$measure];
  				}
  			}
  		}
  	}
  	return $statdata;
  }
  /**
   * vlozi do pole cil. Zkontroluje jestli tam uz neni
   * @param CPoint $goal
   * @param string $exp
   * @param string $name
   * @param int $count
   */
  private function add_previous_goal($goal,$exp,$goalname,$namecount){
  	if(!isset($this->previous_goal_positions[$exp][$goalname][$namecount])){
  		$this->previous_goal_positions[$exp][$goalname][$namecount]=$goal;
  	} 
  }
  /**
   * vlozi hodnoty meritek, ktere se pak vkladaji do sumarnich tabulek
   * napr disterr, distfromgoal atd
   * @param CZasah $zasah
   * @param string $exp napr tr3
   * @param string $goalname napr C1
   * @param int $namecount napr 0
   * @param string $filename plna cesta k souboru
   * @param int $trial
   * @param int $phase
   */
  private function add_measures($zasah,$exp,$goalname,$namecount,$filename,$trial,$phase) {
              $errors = array(); // vice druhu measures - 28.7.2010
              foreach (CZasah::MeasureArr(WHOLE_MEASURE) as $measure)
                $errors[$measure]=$zasah->measure($measure);
                
                
              $this->distfromgoal[$exp] // exp je napriklad tr2, tr3 
                    [$goalname] // name je C1, C2, A1, A1 .... skupina trialu
                    [$namecount] // cislo trialu v ramci name 0-n 
                    [basename($filename)]= // jmeno subjektu 
                      array( 
                        'distance'=>$errors,
                        //'distance_symetry'=>min($distance,distance($zasah['bod_symetry'],$zasah['cil'])),
                        'trial'=>$trial,
                        'phase'=>$phase
                      );
              
              // skupinova data, ze kterych pak spocitam prumery za skupinu
              // uprava pro vice measures 28.7.2010
              foreach (CZasah::MeasureArr(WHOLE_MEASURE) as $measure){
                 $this->group_data[$exp][$goalname][$namecount][$measure][]=$zasah->measure($measure);
              }
	}
	/** 
	 * asi zase smazat
	 * prida prazndna data chybejici osoby
  	 * @param string $exp napr tr3
     * @param string $goalname napr C1
     * @param int $namecount napr 0
     * @param string $filename plna cesta k souboru
     * @param int $trial
     * @param int $phase
  	 */
	private function add_missing_person($exp,$goalname,$namecount,$filename,$trial,$phase){
		foreach (CZasah::MeasureArr(WHOLE_MEASURE) as $measure)
                $errors[$measure]=false; // misto skutecne hodnoty budu vkladat false
		$this->distfromgoal[$exp] // exp je napriklad tr2, tr3 
                    [$goalname] // name je C1, C2, A1, A1 .... skupina trialu
                    [$namecount] // cislo trialu v ramci name 0-n 
                    [basename($filename)]= // jmeno subjektu 
                      array( 
                        'distance'=>$errors,
                        //'distance_symetry'=>min($distance,distance($zasah['bod_symetry'],$zasah['cil'])),
                        'trial'=>$trial,
                        'phase'=>$phase
                      );
	}
	/**
	 * tabulka prumeru, stderr a count za skupiny
	 * @param string $outname
	 * @param string $dir
	 */
	private function table_avg_export($outname,$dir){
    foreach(CZasah::MeasureArr(WHOLE_MEASURE) as $measure){
      // pro vice measures - 28.7.2010
      $table_avg = new TableFile($dir.TABLESDIR."/".$outname.'-avg-'.$measure.".xls");
      $group_data = $this->groupdata($measure);
      $table_avg->AddColumns(array("n","name"));
      $groups = array_keys($this->group_data);
      foreach($groups as $group)  $table_avg->AddColumns(array($group));
      $table_avg->AddColumns(array("stderr"));
      foreach($groups as $group)  $table_avg->AddColumns(array($group));
      $table_avg->AddColumns(array("count"));
      foreach($groups as $group)  $table_avg->AddColumns(array($group));
      
      // skupinove hodnoty pro jednotlive trialy
      $prumery = array(); // prumerne hodnoty za C1, C2, A1, A2 ... 3.11.2010
      foreach ($group_data as $name => $namedata){
        foreach($namedata as $n=>$ndata){
          $table_avg->AddToRow(array($n,$name));  
          //averages
          foreach($groups as $group)  {
          	if(isset($ndata[$group])){
          		$prumery[$name][$group][]=average($ndata[$group]);
          		$table_avg->AddToRow(array(average($ndata[$group]) ));
          	} else {
          		$table_avg->AddToRow(array( "" ));
          	}
          }
          //stderr
          $table_avg->AddToRow(array(""));  // prazdny sloupec za nadpis stderr
          foreach($groups as $group) $table_avg->AddToRow(array(  isset($ndata[$group])?stderr($ndata[$group]):""  ));
          //pocety subjektu
          $table_avg->AddToRow(array(""));  // prazdny sloupec za nadpis count
          foreach($groups as $group) $table_avg->AddToRow(array(  isset($ndata[$group])?count($ndata[$group]):""   ));
          
          $table_avg->AddRow(); // ukoncim radku
        }
      }
      
      //PRUMERY za skupiny trialu
      foreach($prumery as $name=>$namedata){
      	  $table_avg->AddToRow(array('AVG',$name));  
      	  //averages
      	  foreach($groups as $group) $table_avg->AddToRow(array(  isset($namedata[$group])?average($namedata[$group]):""  ));
      	  //stderr
          $table_avg->AddToRow(array(""));  // prazdny sloupec za nadpis stderr
          foreach($groups as $group) $table_avg->AddToRow(array(  isset($namedata[$group])?stderr($namedata[$group]):""  ));
          //pocety subjektu
          $table_avg->AddToRow(array(""));  // prazdny sloupec za nadpis count
          foreach($groups as $group) $table_avg->AddToRow(array(  isset($namedata[$group])?count($namedata[$group]):""   ));
          
          $table_avg->AddRow(); // ukoncim radku
      }
      $table_avg->SaveAll();
    }
	}
	/**
	 * tabulka dat z kazdeho cloveka
	 * jednotlivi subjekty jsou ve sloupcich vedle sebe
	 * 
	 * @param string $outname
	 * @param string $dir
	 */
	private function table_persongroups_export($outname,$dir){
    foreach(CZasah::MeasureArr(WHOLE_MEASURE) as $measure){
      $table = new TableFile($dir.TABLESDIR."/".$outname."-".$measure.".xls");
      foreach($this->distfromgoal as $exp=>$expdata){
        // TITULNI RADKA KE KAZDEMU EXP
        // exp je napriklad tr2
        $table->AddColumns(array("exp","name","n"));
        //$table->AddColumns(array("avg","stderr","count"));
        
        foreach($this->filenames[$exp] as $filename)
          $table->AddColumns(array($filename));
        /*foreach($this->filenames[$exp] as $filename)
          $table->AddColumns(array("sym $filename"));*/
        
        foreach($this->filenames[$exp] as $filename){
          $table->AddColumns(array($filename.'-trial',$filename.'-phase'));
        }
        
        // DATA KE KAZDE FAZI A TRIALU
        // skupiny lidi nebo souboru, pripadne tracky
        foreach($this->names_order as $name => $data){ // TableExport se dela az po SaveImg, takze to muzu pouzit
        //foreach($expdata as $name =>$namedata){
          // jeden nazev mista napri C1
          foreach($expdata[$name] as $n=>$ndata){
              // jedno opakovani stejneho nazvu mista napr C1
              $table->AddToRow(array($exp,$name,$n));
              // souhrnna data pro skupinu
              /*$table->AddToRow(array(
                      average($this->group_data[$exp][$name][$n]['data']),
                      stderr($this->group_data[$exp][$name][$n]['data']),
                      pocet($this->group_data[$exp][$name][$n]['data'])
                  ));*/
              // sloupce distance = vzdalenostni chyby
              foreach($this->filenames[$exp] as $filename){
                if(isset($ndata[$filename])) $table->AddToRow(array($ndata[$filename]['distance'][$measure]));
                else $table->AddToRow(array(''));
              }
              /*foreach($this->filenames[$exp] as $filename){
                if(isset($ndata[$filename])) $table->AddToRow(array($ndata[$filename]['distance_symetry']));
                else $table->AddToRow(array(''));
              }*/
              
              // sloupce trial a phase
              foreach($this->filenames[$exp] as $filename){
                if(isset($ndata[$filename])) {
                  $table->AddToRow(array($ndata[$filename]['trial'],$ndata[$filename]['phase']));
                } else {
                  $table->AddToRow(array('',''));
                }
              }
              $table->AddRow();
          }
        }
        $table->AddRowString(""); //prazdna radka mezi skupinami
        $table->SaveAll();
        $table->Erase();
      }
    }
	}
	private function table_stat_export($outname,$dir){
		foreach(CZasah::MeasureArr(WHOLE_MEASURE) as $measure){
      // pro vice measures - 28.7.2010
      $table_stat= new TableFile($dir.TABLESDIR."/".$outname.'-stat-'.$measure.".xls");
      $statdata = $this->statdata($measure); // pole [group][filename][phasename][trial] = hodnota float
      
      // TITULKY TABULKY
      $table_stat->AddColumns(array("subject","group"));
      foreach($this->namecount as $name=>$pocet){
      	for($i=0;$i<$pocet;$i++){
      		$table_stat->AddColumns(array("$name-$i"));
      	}
      }
      foreach($this->namecount as $name=>$pocet){ // prumery za C1, C2, A1, A2 ... 3.11.2010
      	$table_stat->AddColumns(array("$name-avg"));
      }
      
      // DATA TABULKY
      foreach($this->listOfFiles as $exp=>$expdata){ // smycka kvuli chybejicim souborum, aby se dostali do vysledne tabulky jako prazny radek
      	foreach($expdata as $filename=>$valid){
      			$filename_data = ($valid)?$statdata[$exp][$filename] : false;
      			$prumery = array(); // prumerne hodnody za C1, C2, A1, A2 ... 3.11.2010
	      		// jedna radka- data z jednoho cloveka
	      		$table_stat->AddToRow(array($filename,$exp));
	      		foreach ($this->namecount as $name => $pocet) {
		      		for($i=0;$i<$pocet;$i++){
		      			if(isset($filename_data[$name][$i])){
		      				$table_stat->AddToRow(array($filename_data[$name][$i]));
		      			} else {
		      				$table_stat->AddToRow(array(''));
		      			}
		      		}
		      		$prumery[$name]= isset($filename_data[$name]) ? average($filename_data[$name]) : "";
	      		}
	      		$table_stat->AddToRow($prumery);
	      		$table_stat->AddRow();
      		
      	}
      }
      /* foreach ($statdata as $exp => $expdata) {
      	foreach ($expdata as $filename => $filename_data) {
      
      	}
      }*/
      $table_stat->SaveAll();
      $table_stat->Erase();
		}
	}
  
}

/** 
 * porovna dva prvky pole podle skupiny, poctu znacek a jmena
 * vola se pomoci uasort
 *  
 * @param array $a
 * @param array $b
 * @return int
 */
function cmp_wholeimage_skupiny_znacek($a,$b){
	if($a['skupina']==$b['skupina']){
		if($a['pocet']==$b['pocet']){
			return strcmp($a['name'],$b['name']);
		} else {
			return $b['pocet']-$a['pocet']; // chci mit nejdriv vic znacek
		}
	} else {
		return strcmp($a['skupina'],$b['skupina']);
	}
	
}

?>
