<?php

require_once ('classes/PsychopyData.class.php');

class PsychopyDataPPA extends PsychopyData {
	private $opakovani = array();
	/* pretizena funkce, ktera specialne resi nektere faktory
	 * @see PsychopyData::ColFactors()
	 */
	protected function ColFactors($factor_names, $vals){
		foreach($factor_names as $fn){ //fn = factor name
			/*if($fn=="kategorie" || $fn=="opakovani_obrazku"){ // kategorie zatim v datech neni, opakovani obrazku je spatne - 31.3.2016
				$fn_search = "obrazek"; // chci ulozit sloupec, kde je jmeno obrazku
			} else {
				$fn_search = $fn;
			}
			*/
 			if( ($col = array_search($fn, $vals)) !==false){
 				$col_factors[$fn]= $col;
 			} else {
 				$col_factors[$fn]= -1; // pokud sloupec nenajdu, chci predat jmeno obrazku, ale zaporne aby se to dalo poznat - 15.2.2017
 			}
 		}
 		$this->col_factors = $col_factors;
	}
	/* pretizena funkce, ktera specialne resi nektere faktory
	 * @see PsychopyData::GetFactorValue()
	 */
	protected function GetFactorValue($matlab, $fn, $vals, $cl){
		if($fn=="kategorie"){ 
			if($cl<0){ // kategorie zatim v datech neni - 31.3.2016, zaporna hodnota sloupce se nastavi v ColFactors
				return $this->kategorie($vals[-$cl],!$matlab);
			} else {   // kategorie v novych datech uz je
				return $this->kategorie_translate($vals[$cl],!$matlab); 
			}
		} elseif($fn=="opakovani_obrazku"){
			if($cl<0) $cl = -$cl; // pokud se opakovani nenaslo ve sloupcich
			return $this->opakovaniObrazku($vals[$cl]);
		} elseif(is_numeric($vals[$cl])){
			return (int) $vals[$cl];
		} elseif($matlab && isset($this->factors[$fn][$vals[$cl]])) {
			return (int) $this->factors[$fn][$vals[$cl]];
		} else {
			return $vals[$cl];
		}
	}
	/* pretizena funkce, ktera specialne resi zmacknuti mezerniku
	 * @see PsychopyData::GetCorr()
	 */
	protected function GetCorr($vals, $col_corr, $col_keys){
		$kategorie = $this->kategorie($vals[$this->col_factors['kategorie']]);
		if($kategorie == 'Ovoce' && $vals[$col_keys]=='space'){
			return 1; // spravne je, kdyz zmacknul mezenik pri ovoci
		} elseif($kategorie != 'Ovoce' && $vals[$col_keys]=='None'){
			return 1;
		} else {
			return 0;
		}
	}
	/**
	 * pocita pokolikate je obrazek
	 * @param string $obrazek
	 * @return int
	 */
	private function opakovaniObrazku($obrazek){
		if(!isset($this->opakovani[$obrazek])){
			$this->opakovani[$obrazek]=1; // obrazek je poprve
		} else {
			$this->opakovani[$obrazek] = $this->opakovani[$obrazek] + 1;
		}
		return (int) $this->opakovani[$obrazek];
	}
	/**
	 * smaze pocty opakovani
	 */
   public function opakovaniReset(){
		$this->opakovani = array(); 
	}
	/**
	 * vrati puvodni jmeno kategorie obrazku z jeho noveho jmena (od p110)
	 * @param string $katnova
	 * @param bool $string
	 * @return string/int
	 */
	private function kategorie_translate($katnova,$string=true){
		$katint = array("Ovoce"=>0,"Scene"=>1,"Face"=>2,"Object"=>3);
		$kat = array (
			"objects"=>"Object",
			"faces"=>"Face",
			"scenes"=>"Scene",
			"fruit_vegetables"=>"Ovoce"
		);
		if(isset($kat[$katnova])){
			return $string ? $kat[$katnova] : (int) $katint[$kat[$katnova]];
		} else {
			return $string ? "" : -1;
		}	
	}
	/**
	 * vrati kategorii obrazku podle jeho jmena;
	 * protoze ve starych datech z psychopy, nejsou kategori uvedene;
	 * vraci prazdny retezec nebo -1, pokud obrazek nenalezen
	 * @param string $obrazek
	 * @param bool $string jestli se ma vracet odpoved jako string nebo int
	 * @return string/int
	 */
	private function kategorie($obrazek,$string=true){
		$katint = array("Ovoce"=>0,"Scene"=>1,"Face"=>2,"Object"=>3);
		$kat = array(
			"ovoce-banany.jpg"=>"Ovoce",
			"ovoce-jahoda.jpg"=>"Ovoce",
			"ovoce-pomeranc.jpg"=>"Ovoce",
			"ovoce-tresne.jpg"=>"Ovoce",
			"ovoce-jablko.jpg"=>"Ovoce",
			"m4068.jpg"=>"Face",
			"f4017.jpg"=>"Face",
			"m4048.jpg"=>"Face",
			"m4061.jpg"=>"Face",
			"f4027.jpg"=>"Face",
			"m4001.jpg"=>"Face",
			"f4030.jpg"=>"Face",
			"f4016.jpg"=>"Face",
			"m4029.jpg"=>"Face",
			"f4008.jpg"=>"Face",
			"f4018.jpg"=>"Face",
			"m4006.jpg"=>"Face",
			"m4031.jpg"=>"Face",
			"f4009.jpg"=>"Face",
			"f4007_1.jpg"=>"Face",
			"m4042.jpg"=>"Face",
			"m4017.jpg"=>"Face",
			"m4079.jpg"=>"Face",
			"f4011.jpg"=>"Face",
			"f4029.jpg"=>"Face",
			"P2181326.jpg"=>"Object",
			"P2181371.JPG"=>"Object",
			"P2181341.JPG"=>"Object",
			"P2181315.JPG"=>"Object",
			"P2181321.jpg"=>"Object",
			"P2181369.JPG"=>"Object",
			"P2181358.jpg"=>"Object",
			"P2181340.jpg"=>"Object",
			"P2181312.JPG"=>"Object",
			"P2181355.JPG"=>"Object",
			"P2181373.JPG"=>"Object",
			"P2181349.JPG"=>"Object",
			"P2181327.jpg"=>"Object",
			"P2181346.JPG"=>"Object",
			"P2181335.JPG"=>"Object",
			"P2181363.JPG"=>"Object",
			"P2181376.JPG"=>"Object",
			"P2181325.jpg"=>"Object",
			"P2181360.JPG"=>"Object",
			"P2181334.jpg"=>"Object",
			"venezia_fake_1000.jpg"=>"Scene",
			"karlstejn_fake_1000.jpg"=>"Scene",
			"vatican_fake_1000.JPG"=>"Scene",
			"prague_castle_fake_1000.jpg"=>"Scene",
			"bratislava_castle_1000.jpg"=>"Scene",
			"rio_de_janiero_jesus_view_1000.jpg"=>"Scene",
			"ceska-krajina-03-hory-lesy-priroda.jpg"=>"Scene",
			"isle-of-wight-190616_1280.jpg"=>"Scene",
			"white_house_washington_fake_1000.jpg"=>"Scene",
			"tower_bridge_fake_1000.jpg"=>"Scene",
			"rio_de_janiero_jesus_view_fake_1000.jpg"=>"Scene",
			"parthenon_fake_1000.JPG"=>"Scene",
			"pyramids_sphinx_fake_1000.jpg"=>"Scene",
			"great_wall_of_china_fake_1000.JPG"=>"Scene",
			"vatican_1000.jpg"=>"Scene",
			"ceska-krajina-06-voda-stromy-5625000_print.jpg"=>"Scene",
			"wetland-scene-in-the-catskills-mountains.jpg"=>"Scene",
			"sky12_1280.jpg"=>"Scene",
			"charles_bridge_fake_1000.jpg"=>"Scene",
			"sydney_opera_house_fake_1000.jpg"=>"Scene"
		);
		if(isset($kat[$obrazek])){
			return $string ? $kat[$obrazek] : (int) $katint[$kat[$obrazek]];
		} else {
			return $string ? "" : -1;
		}
	}
}

?>