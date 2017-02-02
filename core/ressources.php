<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.1 (see AUTHORS file)
* Initialise toutes les ressources de programme et de librairies
*/
// l'ordre des "require_once" doit respecter la hiérarchie d'appel des objets
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Log.php';
$GLOBALS['SG_LOG'] = new SG_Log('Console', SG_LOG::LOG_NIVEAU_DEBUG);

// 1.3 Definition initiale des parametres de configuration :
if(!isset($SG_Config['SynerGaia_titre'])) {
	$SG_Config['SynerGaia_titre'] = 'SynerGaïa';
}
$SG_Config['SynerGaia_url'] = '<a href="http://docum.synergaia.eu">Documentation</a>';
$SG_Config['SynerGaia_theme'] = 'defaut';
$SG_Config['CouchDB_port'] = 5984;

// 1.3 Definition des parametres d'environnement :
@set_time_limit(3600);
@ini_set('max_execution_time', 3600);
@ini_set('max_input_time', 3600);
@ini_set('date.timezone', 'Europe/Paris');

// fonction et variables du socle
require_once SYNERGAIA_PATH_TO_ROOT . '/core/socle.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/core/simple_html_dom.php'; // 1.3.1 ajout

// Classes du noyau profond
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Config.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Cache.php';

require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Rien.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Objet.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_ObjetComposite.php';

require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Base.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_BaseCouchDB.php';

require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Document.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_DocumentCouchDB.php';

require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Vue.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_VueCouchDB.php';

require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Texte.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_TexteRiche.php';
require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_TexteParametre.php';

require_once SYNERGAIA_PATH_TO_ROOT . '/lib/sg_Collection.php';

// Liste tous les fichier "sg_*" du dossier lib/ (par ordre alphabétique)
$listeRessources = array();
$dossierRessources = SYNERGAIA_PATH_TO_ROOT . '/lib';
if ($dir = @opendir($dossierRessources)) {
    while (($file = readdir($dir)) !== false) {
        if (substr($file, 0, 3) === 'sg_') {
            $listeRessources[] = $file;
        }
    }
    closedir($dir);
}
sort($listeRessources);
// Charge les fichiers
$nbRessources = sizeof($listeRessources);
for ($i = 0; $i < $nbRessources; $i++) {
    require_once SYNERGAIA_PATH_TO_ROOT . '/lib/' . $listeRessources[$i];
}

// 1.3.0 chargement PHPExcel par défaut /var/lib/phpexcel sinon modifier $config.php
$dirphpexcel = SG_Config::getConfig('phpexcel', '/var/lib/phpexcel/');
if ($dir = @opendir($dirphpexcel)) {
	require($dirphpexcel .'Classes/PHPExcel.php');
}

session_start();
set_error_handler('errorHandler', E_ALL);
set_exception_handler('exceptionHandler');
// Paramètres de débogage
unset( $_SESSION['benchmark']); //efface le tableau des compteurs de benchmark
$_SESSION['timestamp_init'] = microtime(true);
if (!isset($_SESSION['debug']['on'])) {
	$_SESSION['debug']['on'] = false;
}
if ($_SESSION['debug']['on']) {
	$_SESSION['debug']['contenu'] = '<b>===== DEBUG ACTIF ===== </b><br>';
} else {
	$_SESSION['debug']['contenu'] = '';
}
if(!isset($_SESSION['@SynerGaia'])) {
	$_SESSION['@SynerGaia'] = new SG_SynerGaia();
}

SG_Cache::initialiser();
?>
