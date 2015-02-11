<?php

//define('EXPERIMENT','KarelKamil'); // leden unor 2011
//define('EXPERIMENT','aappII'); // leden unor 2011
//define(EXPERIMENT,'aappDesor'); // listopad 2011  Pariz

//define('MATLAB',1); 
if(!defined('TRACKS'))				define('TRACKS',0); // tracky do matlabu
if(!defined('IMAGES'))				define('IMAGES',1); // obrazky tracku v SVG
if(!defined('ANGLESPEEDS'))			define('ANGLESPEEDS',0); //rychlostni profily otaceni pro kazdeho cloveka zvlast
if(!defined('ANGLEHISTO'))			define('ANGLEHISTO',0); // celkovy histogram rychlosti otaceni
if(!defined('TIMEHISTO'))			define('TIMEHISTO',0); // jestli delat histogram z casu 
if(!defined('COLUMNDELIM'))			define('COLUMNDELIM',"\t");
if(!defined('DELIM'))				define('DELIM',","); // pro excel, matlab prepisu konkretne

if(!defined('KEYLEFT')) 			define('KEYLEFT','left'); // klavesa pro otoceni doleva
if(!defined('KEYRIGHT')) 			define('KEYRIGHT','right'); // klavesa pro otoceni doprava
if(!defined('KEYTOPOINT'))         	define('KEYTOPOINT',"s");     // klavesa na ukazani na cil, pouzivano s nebo w(=dopredu)
if(!defined('TURNEDBYMOUSE'))      	define('TURNEDBYMOUSE',"0");  // jestli se clovek v pokuse otacel pomoci mysi (jako v prvnim experimentu s Karlem)
if(!defined('TESTPHASES'))      	define('TESTPHASES',"5,6");  // testove faze, ze ve kterych se ma pocitat
if(!defined('TRIALGROUPS'))      	define('TRIALGROUPS',"0");  // jestli se trialy maji rozclenovat do obrazku podle mista cile

if(!defined('VERZE'))      			define('VERZE',"AAPPI");  // verze test - I nebo III (kruhova arena, jina jmena mist ...)
?>