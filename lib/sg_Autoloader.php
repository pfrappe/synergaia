<?php
/**  SynerGaia fichier pour la gestion de l'objet @Autoloader */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Autoloader : Classe de chargement des classes SynerGaïa et PHPExcel si existe
 * @since 2.0
 * @version 2.4
 */
class SG_Autoloader {

	/**
	 * Enregister l'Autoloader avec SPL
	 * 
	 * @since 2.0
	 * @version 2.4 autorise url couchdb
	 */
	public static function enregistrer() {
		// Enregister tous les autoloader existants avec SPL pour ne pas avoir de conflit
		if (function_exists('__autoload')) {
			spl_autoload_register('__autoload');
		}
		// 2.4 aussi url
		$original = ini_get('allow_url_include');
		if($original !== true) {
			ini_set('allow_url_include', true);
		}
		// Enregister l'autoloader SynerGaïa avec SPL
		return spl_autoload_register(array('SG_Autoloader', 'charger'));
	}

	/**
	 * Autoload d'une classe par son nom. Les classes qui commencent par SG_ viennent de SynerGaïa, sinon de l'appli.
	 * DEBUG : journaliser mais pas de tracer()
	 * 
	 * @since 2.0
	 * @version 2.4 msg erreur ; phpexcel ; getClassFileName()
	 * @version 2.6 pas msg si pas fichier
	 * @param string $pClassName : nom de la classe à charger
	 * @return boolean true si chargement fait, false si déjà chargée
	 * @throws Exception
	 */
	public static function charger($pClassName){
		$erreur = '';
		if ((class_exists($pClassName, FALSE))) {
			$ret = FALSE;
		} else {
			$pClassFilePath = self::getClassFileName($pClassName);
			// peut-on charger ?
			if (!file_exists($pClassFilePath)) {
				$ret = FALSE;
			//	$erreur = 'ERREUR FATALE : La classe ' . $pClassFilePath . ' n\'existe pas !';
			} elseif (!is_readable($pClassFilePath)) {
				$ret = FALSE;
				$erreur = 'ERREUR FATALE : La classe ' . $pClassFilePath . ' n\'est pas accessible ou lisible !';
			} else {
				try {
					$ret = include ($pClassFilePath);
					if (strpos($pClassName, 'PHPExcel') === 0) {
						if (! class_exists('ZipArchive')) {
journaliser('PHPExcel : ZIPARCHIVE intouvable');
							PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
						}
					}
					if ($ret === false) {
						throw new Exception('ERREUR GRAVE sur ' . $pClassFilePath . ' : le fichier ne se charge pas ou provoque une erreur de compilation');
					}
				} catch (Exception $e) {
					$erreur = 'ERREUR FATALE sur ' . $pClassFilePath . ' (ligne ' . $e -> getLine() . ' : ' . $e -> getMessage();
				}
				$ret = TRUE;
			}
		}
		if ($erreur !== '') {
			journaliser('SG_Autoloader->charger(' . $pClassName . ' : ' . $erreur);
		}
		return $ret;
	}

	/**
	 * Vérifie l'existence d'un fichier php dans appli/var
	 * 
	 * @since 2.1 ajout
	 * @param string $pClassName nom de la classe à charger
	 * @return boolean
	 */
	public static function verifier($pClassName) {
		if ((class_exists($pClassName, FALSE))) {
			$ret = true;
		} else {
			$pClassFilePath = SYNERGAIA_PATH_TO_APPLI . '/var/' . $pClassName . '.php';
			$ret = file_exists($pClassFilePath);
		}
		return $ret;
	}

	/**
	 * calcule le nom du fichier ou l'url correspondant au fichier PHP de la classe
	 * 
	 * @since 2.4 ajout
	 * @param string $pClassName nom de la classe cherchée
	 * @return string nom du fichier ou url couchdb
	 */
	public static function getClassFileName($pClassName) {
		if (strpos($pClassName, 'SG_') === 0) {
			// classes SynerGaïa
			$ret = SYNERGAIA_PATH_TO_ROOT . '/lib/' . str_replace('SG_', 'sg_', $pClassName) . '.php';
		} elseif (strpos($pClassName, 'PHPExcel') === 0) {
			// clase PHPExcel
			$dirphpexcel = SG_Config::getConfig('phpexcel', '/var/lib/phpexcel/');
			$ret = $dirphpexcel . 'Classes/' . str_replace('_', '/' ,$pClassName) . '.php';
		} else {
			// classe créée par la compilation
			if (strpos($pClassName, 'var/') === 0) {
				$ret = SYNERGAIA_PATH_TO_APPLI . '/' . $pClassName . '.php';
			} else {
				$ret = SYNERGAIA_PATH_TO_APPLI . '/var/' . $pClassName . '.php';
			}
		}
		return $ret;
	}
}
?>
