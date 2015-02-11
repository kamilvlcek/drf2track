<?php

//define('EXPERIMENT','KarelKamil'); // leden unor 2011
//define('EXPERIMENT','aappII'); // leden unor 2011
//define(EXPERIMENT,'aappDesor'); // listopad 2011  Pariz

//define('MATLAB',1); 
define('TRACKS',0); // tracky do matlabu
define('IMAGES',1); // obrazky tracku v SVG
define('ANGLESPEEDS',0); //rychlostni profily otaceni pro kazdeho cloveka zvlast
define('ANGLEHISTO',0); // celkovy histogram rychlosti otaceni
define('TIMEHISTO',0); // jestli delat histogram z casu 
define('COLUMNDELIM',"\t");
define('DELIM',","); // pro excel, matlab prepisu konkretne

if(!defined('KEYLEFT')) 			define('KEYLEFT','left'); // klavesa pro otoceni doleva
if(!defined('KEYRIGHT')) 			define('KEYRIGHT','right'); // klavesa pro otoceni doprava
if(!defined('KEYTOPOINT'))         	define('KEYTOPOINT',"s");     // klavesa na ukazani na cil, pouzivano s nebo w(=dopredu)
if(!defined('TURNEDBYMOUSE'))      	define('TURNEDBYMOUSE',"0");  // jestli se clovek v pokuse otacel pomoci mysi (jako v prvnim experimentu s Karlem)

?>