<?php
/**
* SynerGaia 2.3 (see AUTHORS file)
* page principale d'ouverture de SynerGaïa (Main page)
* Ce fichier doit impérativement se trouver dans le répertoire de l'application car c'est ici
* qu'on lit le fichier ../config/config.php qui contient le renvoi vers les répertoires de programmes (SYNERGAIA_PATH_TO_ROOT)
*/
if (!defined("SYNERGAIA_PATH_TO_APPLI")) {
    define('SYNERGAIA_PATH_TO_APPLI', realpath(dirname(__FILE__)));
}
ob_start();
header('P3P: CP="CAO PSA OUR"'); // 1.3.0 spécif IE... pour garder $_SESSION
// 1.3.0 Definition initiale des paramètres standards de configuration : chargement du fichier de config
global $SG_Config;
$SG_Config = array();
if (!file_exists(SYNERGAIA_PATH_TO_APPLI . '/config/config.php')) {
	// sans doute installation initiale
	@mkdir(SYNERGAIA_PATH_TO_APPLI . '/config');
	@mkdir(SYNERGAIA_PATH_TO_APPLI . '/var');
	@define('SYNERGAIA_PATH_TO_ROOT', '/var/lib/synergaia');
	$ipos = strripos(SYNERGAIA_PATH_TO_APPLI,'/');
	$newappli = substr(SYNERGAIA_PATH_TO_APPLI, $ipos + 1);
	@symlink(SYNERGAIA_PATH_TO_ROOT . '/nav', $newappli . '/nav');
} else {
    require_once SYNERGAIA_PATH_TO_APPLI . '/config/config.php';
}
// 1.3.0 définition du répertoire des programmes SynerGaïa
if (!defined("SYNERGAIA_PATH_TO_ROOT")) {
	if (isset($SG_Config['SynerGaia_path_to_root'])) {		
		define('SYNERGAIA_PATH_TO_ROOT', $SG_Config['SynerGaia_path_to_root']);
	} else {
		define('SYNERGAIA_PATH_TO_ROOT', SYNERGAIA_PATH_TO_APPLI);
	}
}
// chargement des ressources initiales ; 2.0 autoloader
$file = SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Autoloader.php';
if (!file_exists($file)) {
	echo 'SynerGaïa : accès au fichier ' . $file . ' impossible. Voir si le fichier config/config.php est présent ?';
} else {
	require_once $file;
	// initialisations
	SG_Autoloader::enregistrer();
	SG_SynerGaia::initialiser();
	// FIN DU CHARGEMENT DES RESSOURCES : à partir d'ici toutes les librairies sont disponibles et la session est chargée
	// Traitement
	SG_Pilote::Traiter();
}
?>
