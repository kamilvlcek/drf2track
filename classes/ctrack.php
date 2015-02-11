<?php
require_once("includes/point.php");
require_once("classes/Cimage.php");

define('DIST_OK',30);
define('ARENA_RADIUS',140);
define('PERCENT_ALLOWED',1.1);
define('NASOBEK',2); // zvetseni obrazku proti skutecnym pixelum
define('OLDVERSION5MIN',4200);
define('LIMITTOOLONGFILE',1.8);
define('ARENA_RADIUS_M',1.4);
$variables = array("din"=>"dist_inavoid","dtot"=>"dist_total","tin"=>"time_inavoid",
									 "ttot"=>"time_total","ent"=>"entrances","maxt"=>"maxavoidtime","ttfs"=>"timetofirstshock",
									 "te"=>"timetoentrances","dc"=>"distance from center","ea"=>"entrances_to_outofcentertime");
//define('FRAMES_TO_AVERAGE',12);

class CTrack {
  var $pole = array();
  var $filename;
  var $index = array(); // indexing values in pole
  var $Acenter=false, $Awidth=false, $Afrom=false, $Ato=false;
  var $error = false;
  var $oldfileversion = false;
	var $frame5min = 7500;
	var $frame1min = 1500;
	var $data;

  function CTrack($filename,$x,$y,$frame,$shock){     // GET VALUES
  		$limittoolongfile = LIMITTOOLONGFILE;
  		$this->filename = $filename;
  		if(!file_exists($filename)){
  			dp("  ERR:file not found!!!");
  			$this->error = true;
  	 	  return;
  		}
  		if(filemtime($filename)<1138270000){
  			$this->oldfileversion = true;
  			dp($filename,"old file version - before 26.1.2006");
				$this->frame5min=OLDVERSION5MIN;
				$this->frame1min=OLDVERSION5MIN/5;
				$limittoolongfile = LIMITTOOLONGFILE*7500/OLDVERSION5MIN;
  		}
      $fc = file($filename);
      $fcshift = 1;
      if(count($fc)<5){
      	dp("  ERR:file too short!!") ;
  	 	  $this->error = true;
  	 	  return;
      }


      for($lineno=0;$lineno+$fcshift < count($fc);$lineno++){ // skip first line
  	 	  $vals = preg_split("/\s+/",trim($fc[$lineno+$fcshift])); // in $fc the index should be 1 higher
  	 	  //echo count($vals)." ";
  	 	  if(count($vals)<3 || !is_numeric($vals[0])){
  	 	  	if($vals[0]=="frame"){
  	 	  		unset($this->pole);
  	 	  		$this->pole = array();
  	 	  		$fcshift=$lineno+$fcshift; // shift the reading from $fc this number of lines
  	 	  		dp("  unfinished part with $lineno line discarded");
  	 	  		$lineno = 0;
  	 	  	} else {
	  	 	  	dp("  !!ERR:error in file on line ".($lineno+1)) ;
	  	 	  	$this->error = true;
	  	 	  	return;
  	 	  	}
       	}
        if(	$vals[$frame]>$this->frame5min	) break; // 5 minut

        $this->pole[$lineno]["point"] = array($vals[$x],$vals[$y]);
        $this->pole[$lineno]["frame"] = $vals[$frame];
        $this->pole[$lineno]["shock"] = $vals[$shock];
        if($lineno>0){
          $this->pole[$lineno]["dist"]=distance(
                                          $this->pole[$lineno]["point"],
                                          $this->pole[$lineno-1]["point"]
                                        );
        }
      }
      if(count($fc)>$lineno*$limittoolongfile){
      	dp("  made ".($lineno+1)." lines of ".count($fc));
      } elseif ($lineno < $this->frame5min-50) {
      	dp("  only ".($lineno+1)." lines");
      }
      $this->error=false;
    }
  /**
   * delete same lines?
   *
   * @param bool $empty
   */
  function Filter($empty){  // FILTER OUT ERRORS
      if(MAKEFILTERFILE==1)
      	$out = fopen($this->filename.".filter","wt");
      $output="";
      $stejne_body = array();
      $pocet_cyklu = count($this->pole); // musim si to ulozit, protoze se to meni v prubehu cyklu
      for($lineno=0;$lineno<$pocet_cyklu;$lineno++){
        if($lineno == 0)
          $lastbod = array(1000,1000);
        if(!isset($this->pole[$lineno]["prolozeno"]))
        	$this->pole[$lineno]["prolozeno"]=0;
        // nactu si vsechny stejne body
        $i=0;
        while(  $lineno+$i<$pocet_cyklu
        				&& ( $this->BodMimo($lineno+$i)
        				|| BodySame($this->pole[$lineno+$i]["point"],$lastbod))

        		){
           	if(count($stejne_body)==0)
           		$stejne_body[]=$lastbod;
            $stejne_body[]=$this->pole[$lineno+$i]["point"];
            $i++;
     		}
     		// naplnim je prolozenou primkou
     		if(count($stejne_body)>0){
     				if($lineno+$i>=$pocet_cyklu) {
     					$i--; // to je v situaci, kdy je posledni bod zaznamu mimo
     					$this->pole[$lineno+$i]["point"]=$stejne_body[0];
     				}
     				$nove_body = prolozit_primku($stejne_body,$this->pole[$lineno+$i]["point"]);
						for($j=0;$j<count($stejne_body)-1; $j++){
							$this->pole[$lineno+$j]["point"]=$nove_body[$j+1];
							$this->pole[$lineno+$j]["prolozeno"]=1;
						}
						//$lineno--;
						unset($stejne_body);
						$stejne_body=array();
     		}
     		// $lineno ukazuje na prvni spatny, nyni opraveny bod
     		// zase jedu bod po bodu, nejdriv prolozenyma a pak dalsima
     		if($this->pole[$lineno]["prolozeno"] || ($lineno>0 && $this->pole[$lineno-1]["prolozeno"])){
     			// aby se vypocetla nova vzdalenost i pro ten prvni spravny body
					$this->pole[$lineno]["dist"]=distance(
                                          $this->pole[$lineno]["point"],
                                          $this->pole[$lineno-1]["point"] // !!! tady to obcas hazi chyby - undefined offset -1 - voglmir1.TR4 nebo dusekjo1.TR2
                                        );

     		}
        if(MAKEFILTERFILE==1){
	     		$output .= sprintf("%d\t%d\t%d\t%f\t%f\t%d\n",
	          $this->pole[$lineno]["frame"],
	          $this->pole[$lineno]["point"][0],
	          $this->pole[$lineno]["point"][1],
	          isset($this->pole[$lineno]["dist"])?$this->pole[$lineno]["dist"]:0,
	          distance($this->pole[$lineno]["point"],array(0,0)),
	          $this->pole[$lineno]["prolozeno"]
	        );
        }
        $lastbod = $this->pole[$lineno]["point"];
        $this->index[]=$lineno;
        /*if($this->Acenter){
        	$this->pole[$lineno]["shock_new"]=$this->BodInShock($lineno);
        }*/

      }
	    if(MAKEFILTERFILE==1){
	     	fwrite($out,$output);
	     	fclose($out);
	    }

    }
    function SaveShocks($point="point"){
    	$pocet_cyklu = count($this->pole);
    	for($lineno=0;$lineno<$pocet_cyklu;$lineno++){
    		if($this->Acenter){
        	$this->pole[$lineno]["shock_new"]=$this->BodInShock($lineno,$point);
        }
    	}
    }
    function BodInAvoidedAnnulus($lineno,$point="point"){
    	$point= $this->pole[$lineno]["$point"];
    	$dist = distance($point,array(0,0));
    	if($dist>=ARENA_RADIUS*PERCENT_ALLOWED*$this->Afrom/100
    	   && $dist<=ARENA_RADIUS*PERCENT_ALLOWED*$this->Ato/100
    	   ) return 1;
    	else
    		return 0;
    }
    function BodInShock($lineno,$point="point"){
				$point= $this->pole[$lineno]["$point"];
				$dist = distance($point,array(0,0));
				if(
					angle($point)>deg2rad($this->Acenter-$this->Awidth/2)
					&& angle($point)<deg2rad($this->Acenter+$this->Awidth/2)
					&& $dist>=ARENA_RADIUS*$this->Afrom/100
					&& $dist<=ARENA_RADIUS*PERCENT_ALLOWED*$this->Ato/100
				) return 1;
				else
					return 0;
    }

    /**
     * Volana z filtru
     * @return bool
     * @param int $lineno
     */
    function BodMimo($lineno){
    	if( isset($this->pole[$lineno])
    			&& isset($this->pole[$lineno+1])
    			&&
          ( distance($this->pole[$lineno]["point"],array(0,0))>ARENA_RADIUS*PERCENT_ALLOWED
	          || (
	              isset($this->pole[$lineno+1]["dist"])
	              && isset($this->pole[$lineno]["dist"])
	              && $this->pole[$lineno]["dist"]>DIST_OK
	              && $this->pole[$lineno+1]["dist"]>DIST_OK
	            )
	           )
          )
          return true;
       else
       		return false;
    }


    // ODTUD BYCH UZ MOH ZRUSIT INDEX
    // vytvori pole [i][avegerage][], ktere obsahuje zprumerovane body
    function Average($frames){ //COMPUTE RUNNING AVERAGE
      $pocet_cyklu = count($this->index);
      for($i = 0; $i<$pocet_cyklu;$i++){
        $hodnoty = array();
        $hodnoty[]=$this->pole[$this->index[$i]]["point"];
        $j = $i-1;
        while(isset($this->index[$j])
            && ($this->pole[$this->index[$i]]["frame"]-$this->pole[$this->index[$j]]["frame"])<=$frames){
          $hodnoty[]= $this->pole[$this->index[$j]]["point"];
          $j--;
        }
        $j = $i+1;
        while(isset($this->index[$j])
            && ($this->pole[$this->index[$j]]["frame"]-$this->pole[$this->index[$i]]["frame"])<=$frames ){
          $hodnoty[]= $this->pole[$this->index[$j]]["point"];
          $j++;
        }
        $this->pole[$this->index[$i]]["average"]=gravity($hodnoty);
        if($i>0)
          $this->pole[$this->index[$i]]["dist_a"]=distance(
                                                      $this->pole[$this->index[$i]]["average"],
                                                      $this->pole[$this->index[$i-1]]["average"]
                                                  );
        //echo $this->pole[$this->index[$i]]["average"][0]." ";
      }

    }
    function OutputFile($shocknew,$posun=0){
    	// zjistit jestli funguje maxtime avoidance - asi debug
    	// naprogramovat $distance_from_center, $time_outofcenter - HOTOVO - dalsi zpracovani?
    	// a pak jeste vypis jednotlivych lidi
    	global $variables;
    	if($shocknew)
    		$shock = "shock_new";
    	else
    		$shock = "shock";
			$dist_celkem = $dist_room = $frames_celkem = $frames_room = $entrances=$max_avoidance_time = 0;
			$first_no_shock_time =0;// frame, when the no-shock state begun
			$distance_from_center=0; // average distance from center
			$time_outofcenter = 0; // time the person was aside from 30% center

			//"te"=>"timetoentrances","dc"=>"distance from center","ea"=>"entrances_to_avoidtime");
			$shockbefore = false; // false je no shock
			$output = "";
			$entrance_arr = array();
			$time_to_first_shock=false;
			$dist_min_celkem = $dist_min_frame = $frames_min_celkem = $frames_min_frame = $entrances_min = $max_avoid_time_min=0;
      $first_no_shock_time_min =0;// frame, when the last no-shock state begun

      foreach($this->index as $i=>$lineno){
        if($lineno>0) {
          $dist_celkem +=  $this->pole[$lineno]["dist_a"];
          $frames_celkem += $this->pole[$lineno]["frame"]-$this->pole[$this->index[$i-1]]["frame"];
          $dist_min_celkem +=  $this->pole[$lineno]["dist_a"];
          $frames_min_celkem += $this->pole[$lineno]["frame"]-$this->pole[$this->index[$i-1]]["frame"];
        }

        if($this->BodInSavedShock($lineno,$shock)){
        	if(!$shockbefore){
        		$entrances++;
        		$entrances_min++;
        		$entrance_arr[]=$this->pole[$lineno]["frame"];
        		$max_avoidance_time=max($max_avoidance_time,$this->pole[$lineno]["frame"]-$first_no_shock_time);
        		$max_avoid_time_min=max($max_avoid_time_min,$this->pole[$lineno]["frame"]-$first_no_shock_time_min);
        		$shockbefore=true; // je v soku
        		if($time_to_first_shock===false){
        			$time_to_first_shock = $this->pole[$lineno]["frame"];
        		}
           }
        	if($lineno>0) {
        		$dist_room +=  $this->pole[$lineno]["dist_a"];
        		$frames_room += $this->pole[$lineno]["frame"]-$this->pole[$this->index[$i-1]]["frame"];
        		$dist_min_frame +=  $this->pole[$lineno]["dist_a"];
        		$frames_min_frame += $this->pole[$lineno]["frame"]-$this->pole[$this->index[$i-1]]["frame"];
           }
        } else {
        	if($shockbefore){
	        	$first_no_shock_time=$this->pole[$lineno]["frame"];
	        	$first_no_shock_time_min=$this->pole[$lineno]["frame"];
        	}
          $shockbefore=false; // neni soku
        }
        $distance_from_center += distance($this->pole[$lineno]["average"],array(0,0));
				if($this->BodInAvoidedAnnulus($lineno,"average")){
					$time_outofcenter++;
				}
        if($this->pole[$lineno]["frame"]%$this->frame1min == 0){ // cela jedna minuta
      		if($this->pole[$lineno]["frame"]>0){
      			if(!isset($max_avoid_time_min) || $max_avoid_time_min == 0) $max_avoid_time_min=$this->frame1min;
  					//$output.=$this->OutputLine($name,$group, $shock,$lineno,$dist_min_frame,$dist_min_celkem,
  	  			//	$frames_min_frame,$frames_min_celkem,$entrances_min,$max_avoid_time_min);
		    		$dist_min_celkem = $dist_min_frame = $frames_min_celkem = $frames_min_frame = $entrances_min = $max_avoid_time_min=0;
		    		$first_no_shock_time_min =$this->pole[$lineno]["frame"];// frame, when the no-shock state begun
      		}
      	}
  	  }
  	  if($dist_min_celkem>0){ // pokud treba posledni minuta nebyla udelana uplne do konce
  	  	if(!isset($max_avoid_time_min) || $max_avoid_time_min == 0) $max_avoid_time_min=$this->frame1min;
  	  	//$output.=$this->OutputLine($name,$group, $shock,"5",$dist_min_frame,$dist_min_celkem,
  	  	//	$frames_min_frame,$frames_min_celkem,$entrances_min,$max_avoid_time_min);
  	  }
			$max_avoidance_time=max($max_avoidance_time,$this->pole[$lineno]["frame"]-$first_no_shock_time);
			if($time_to_first_shock==0) $time_to_first_shock=$this->pole[$lineno]["frame"];
  	  //if(!isset($max_avoid_time) || $max_avoid_time == 0) $max_avoid_time=$this->frame5min;
  	  /*$output.=$this->OutputLine($name,$group, $shock,"celkem",
									  	  	$dist_room,$dist_celkem,
									  	  	$frames_room,$frames_celkem,
									  	  	$entrances,
									  	  	$max_avoidance_time,
									  	  	$time_to_first_shock,
									  	  	$entrances/frames2sec($frames_room),
									  	  	$distance_from_center/count($this->index),
									  	  	$entrances/frames2sec($time_outofcenter));*/

  	  /*if($posun==0){
	  	  $out = fopen($filename,"at");
	  	  $output = preg_replace("/([0-9])\.([0-9])/","$1".DELIM."$2",$output);
	  	 	fwrite($out,$output);
	  	 	fclose($out);
      }*/
      //$screen = $this->OutputLine($name,$group, $shock,$lineno,$dist_room,$dist_celkem,
  	  //	$frames_room,$frames_celkem,$entrances,$max_avoidance_time);
  	  //dp(str_replace(array("\t","\n")," ",$screen),"");

			// data na pocitani prumeru skupin
			$this->data = array($variables["din"]=>$this->disttometer($dist_room),
													$variables["dtot"]=>$this->disttometer($dist_celkem),
													$variables["tin"]=>frames2sec($frames_room),
													$variables["ttot"]=>frames2sec($frames_celkem),
													$variables["ent"]=>$entrances,
													$variables["maxt"]=>frames2sec($max_avoidance_time),
													$variables["ttfs"]=>frames2sec($time_to_first_shock),
													$variables["te"]=>($entrances==0)?0:frames2sec($frames_room)/$entrances,
													$variables["dc"]=>$this->disttometer($distance_from_center)/count($this->index),
													$variables["ea"]=>($time_outofcenter==0)?"-":$entrances/frames2sec($time_outofcenter)*60);

  	  $entrance_arr[]=$this->frame5min;
  	  return $entrance_arr;
    }

    function OutputLine($name,$group, $shock,$lineno,$dist_frame,$dist,$frames_frame,$frames,$entrances,$max_avoid_time,$timetofirst=false){
    	//$framenow = $this->pole[$lineno]["frame"];
    	if($this->oldfileversion){
    		$ratio = 7500/OLDVERSION5MIN;
    		//$framenow *= $ratio;
//    		$dist_frame *= $ratio;
//    		$dist *= $ratio;
    		$frames *= $ratio;
    		$frames_frame *= $ratio;
//    		$entrances *= $ratio;
    		$max_avoid_time *= $ratio;
    	}
    	if(is_string($lineno))
    		$min = $lineno;
    	else
    		$min = intval($this->pole[$lineno]["frame"]/$this->frame1min);

    	return $this->filename."\t".
    						$group."\t".
  	  					$name."\t".
  	  					$shock."\t".
  	  					$min."\t".
  	  					round($dist_frame/100,2)."\t".
  	 						round($dist/100,2)."\t".
  	 						frames2time($frames_frame)."\t".
  	 						frames2time($frames)."\t".
  	 						$entrances."\t".
  	 						frames2time($max_avoid_time)."\t".
  	 						(($timetofirst!==false)?frames2time($timetofirst):" ")."\t".
  	 						(($this->oldfileversion)?"old version":"")."\n";
    }
    function NoOutputFile($name,$outfilename,$group){
    	$output = filename_namepart($this->filename)."\t".
    						$group."\t".
  	  					$name."\t".
  	  					"-\t".
  	  					"-\t".
  	  					"-\t".
  	 						"-\t".
  	 						"-\t".
  	 						"-\t".
  	 						"-\t".
  	 						"-\t".
  	 						"-\n";
  	 	$out = fopen($outfilename,"at");
  	  //$output = preg_replace("/([0-9])\.([0-9])/","$1".DELIM."$2",$output);
  	 	fwrite($out,$output);
  	 	fclose($out);
    }

    function ImageAverage($name,$group, $shocknew){ // MAKE IMAGE
      if($shocknew)
      	$shock = "shock_new";
      else
      	$shock = "shock";
    	$img = new Cimage(ARENA_RADIUS*(2*NASOBEK+1),ARENA_RADIUS*(2*NASOBEK+1));
  	 	$img->Circle($img->makecenter(array(0,0)),ARENA_RADIUS*NASOBEK,"black",false);
  	 	/*if($this->Acenter){
  	 		$img->Sector($img->makecenter(array(0,0)),ARENA_RADIUS*NASOBEK,$this->Acenter,$this->Awidth,$this->Afrom,"red");
  	 	}*/
      foreach($this->index as $i=>$lineno){
        $room = $img->makecenter(nasobit($this->pole[$lineno]["average"],NASOBEK));
  	 	  if($lineno==0){
  	 	   $img->Point($room,"blue",10,true);
        } else {
        	if($this->BodInSavedShock($lineno,$shock)){
  	 	      //$img->Point($room,"red",5,true);
  	 	      $img->Lineto($room,"red");
          } else {
            //$img->Point($room,"blue",5,true);
            $img->Lineto($room,"blue");
          }
        }
  	 	}

  	 	$img->SavePng(IMAGESDIR."/$group.".filename_namepart($this->filename).".$name.$shock.png");
      $img->Delete();
    }
    function BodInSavedShock($lineno,$shock){
    	return isset($this->pole[$lineno]["$shock"]) && $this->pole[$lineno]["$shock"]==1;
    }
    function SetAvoidanceArea($center, $width, $from, $to){
    	$this->Acenter=$center;
    	$this->Awidth=$width;
    	$this->Afrom=$from;
    	$this->Ato=$to;

    }
    function disttometer($dist){
    	return $dist*ARENA_RADIUS_M/ARENA_RADIUS;
    }


}
?>