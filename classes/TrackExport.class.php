<?php

/**
 * trida, ktera mi vyexportuje data tracku pro matlab
 * @author kamil.vlcek
 *
 */
class TrackExport {
	private $data;
	private $klavesy;
	public function AddPoint($xy,$n,$trial,$phase,$track,$klavesa=false){
		$this->data[$track][$phase][$trial][$n] = $xy;
		if ($klavesa) {
			$this->klavesy[$track][$phase][$trial][$n] = $klavesa;
		} else {
			$this->klavesy[$track][$phase][$trial][$n] = '';
		}	
		
	}
	public function Export($personfilename){
		$TBL_XLS = new Table();
		$TBL_XLS->AddColumns(array('track','phase','trial','n','x','y','klavesa'));
		$TBL_TXT = new Table();
		$TBL_TXT->AddColumns(array('track','phase','trial','n','x','y','klavesa'));
		foreach($this->data as $track=>$trackdata){
			foreach($trackdata as $phase => $phasedata){
				foreach($phasedata as $trial => $trialdata){
					foreach($trialdata as $n=>$xy){
						$TBL_XLS->AddRow(array($track,$phase,$trial,$n,$xy[0],$xy[1],
							$this->klavesy[$track][$phase][$trial][$n]));
						$TBL_TXT->AddRow(array($track,$phase,$trial,$n,$xy[0],$xy[1],
							ord($this->klavesy[$track][$phase][$trial][$n])));
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