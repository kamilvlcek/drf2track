<?php

/**
 * trida, ktera mi vyexportuje data tracku pro matlab
 * @author kamil.vlcek
 *
 */
class TrackExport {
	private $data;
	private $klavesy;
	public function AddPoint($xy,$n,$trial,$phase,$track,$xyavg,$time,$klavesa=false){
		$this->data[$track][$phase][$trial][$n] = array("xy"=>$xy,"xyavg"=>$xyavg,"time"=>(double) $time);
		if ($klavesa) {
			$this->klavesy[$track][$phase][$trial][$n] = $klavesa;
		} else {
			$this->klavesy[$track][$phase][$trial][$n] = '';
		}	
		
	}
	public function Export($personfilename){
		$TBL_XLS = new Table();
		$TBL_XLS->AddColumns(array('track','phase','trial','n','x','y','klavesa','xavg','yavg','time'));
		$TBL_TXT = new Table();
		$TBL_TXT->AddColumns(array('track','phase','trial','n','x','y','klavesa','xavg','yavg','time'));
		foreach($this->data as $track=>$trackdata){
			foreach($trackdata as $phase => $phasedata){
				foreach($phasedata as $trial => $trialdata){
					foreach($trialdata as $n=>$xy){
						$TBL_XLS->AddRow(array($track,$phase,$trial,$n,$xy['xy'][0],$xy['xy'][1],
							$this->klavesy[$track][$phase][$trial][$n],$xy['xyavg'][0],$xy['xyavg'][1],$xy['time']));
						$TBL_TXT->AddRow(array($track,$phase,$trial,$n,$xy['xy'][0],$xy['xy'][1],
							ord($this->klavesy[$track][$phase][$trial][$n]),$xy['xyavg'][0],$xy['xyavg'][1],$xy['time']));
					}
				}
			}
		}
		$filename = dirname($personfilename)."/tables/".	basename($personfilename) .".trackExport";
		$TBL_XLS->SaveAll(true,$filename.".xls",0);
		$TBL_TXT->SaveAll(true,$filename.".txt",1);
	}
}

?>