<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Autoloader : Classe de chargement des classes
**/

class SG_Autoloader {
	/** 2.0
	* Enregister l'Autoloader avec SPL
	*/
	public static function enregistrer() {
		// Enregister tous les autoloader existants avec SPL pour ne pas avoir de conflit
		if (function_exists('__autoload')) {
			spl_autoload_register('__autoload');
		}
		// Enregister l'autoloader SynerGaïa avec SPL
		return spl_autoload_register(array('SG_Autoloader', 'charger'));
	}
	/** 2.1 php
	* Autoload d'une classe par son nom. Les classes qui commencent par SG_ viennent de SynerGaïa, sinon de l'appli.
	* @param (string) $pClassName : nom de la classe à charger
	* @return boolean : true si chargement fait, false si déjà chargée
	*/
	public static function charger($pClassName){
		if ((class_exists($pClassName, FALSE))) {
			$ret = FALSE;
		} else {
			if (strpos($pClassName, 'SG_') === 0) {
				// classes SynerGaïa
				$pClassFilePath = SYNERGAIA_PATH_TO_ROOT . '/lib/' . str_replace('SG_', 'sg_', $pClassName) . '.php';
			} else {
				// classe créée par la compilation
				$pClassFilePath = SYNERGAIA_PATH_TO_APPLI . '/var/' . $pClassName . '.php';
			}
			// peut-on charger ?
			if ((file_exists($pClassFilePath) === FALSE) or (is_readable($pClassFilePath) === FALSE)) {
				$ret = FALSE;
			} else {
				try {
					require ($pClassFilePath);
				} catch (Exception $e) {
					error_log($pClassFilePath . ' (ligne ' . $e -> getLine() . ' : ' . $e -> getMessage());
				}
				$ret = TRUE;
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* vérifie l'existence d'un fichier php dans appli/var
	**/
	public static function verifier($pClassName) {
		if ((class_exists($pClassName, FALSE))) {
			$ret = true;
		} else {
			$pClassFilePath = SYNERGAIA_PATH_TO_APPLI . '/var/' . $pClassName . '.php';
			$ret = file_exists($pClassFilePath);
		}
		return $ret;
	}
}
