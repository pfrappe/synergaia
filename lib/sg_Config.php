<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de gestion de la configuration de SynerGaia
*/
class SG_Config {
	// Type SynerGaia
	const TYPESG = '@Config';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Emplacement du fichier de configuration
	const FICHIER = 'config/config.php';

	/**
	* Détermine la valeur du paramètre de configuration
	*
	* @param string $cle clé du paramètre de configuration
	* @param indéfini $valeurParDefaut valeur par défaut du paramètre
	* @return indéfini valeur du paramètre de configuration
	* @level 0
	*/
	static function getConfig($cle, $valeurParDefaut = '') {
		global $SG_Config;
		$ret = $valeurParDefaut;
		if (array_key_exists($cle, $SG_Config)) {
			$ret = $SG_Config[$cle];
		}
		return $ret;
	}

	/** 1.3.0 SYNERGAIA_PATH_TO_APPLI ;
	* Définit la valeur du paramètre de configuration
	*
	* @param string $cle clé du paramètre de configuration
	* @param indéfini $valeur valeur du paramètre
	* @return boolean modification ok
	* level 0
	*/
	static function setConfig($cle, $valeur = '', $comment = '') {
		global $SG_Config;

		// Chemin complet du fichier de configuration
		$cheminFichierConfig = SYNERGAIA_PATH_TO_APPLI . '/' . SG_Config::FICHIER;

		$ret = false;

		// Définit la valeur
		$SG_Config[$cle] = $valeur;

		// Enregistre la valeur dans le fichier config/config.php

		// Supprime l'ancien contenu si besoin
		// TODO Fichier de config : ne supprimer que si le nouveau fichier est bien écrit
		if (file_exists($cheminFichierConfig)) {
			$file = @fopen($cheminFichierConfig, 'r+');
			if ($file !== false) {
				ftruncate($file, 0);
				fclose($file);
			}
		}

		// Fabrique un nouveau fichier de config avec toutes les valeurs :
		// TODO Fichier de config : Vérifier la bonne écriture du fichier modifié
		$file = @fopen($cheminFichierConfig, 'w');
		if ($file !== false) {
			fwrite($file, '<?php defined("SYNERGAIA_PATH_TO_APPLI") or die("403.14 - Directory listing denied.");' . PHP_EOL);
			fwrite($file, PHP_EOL);

			foreach ($SG_Config as $key => $value) {
				$ligne = '$SG_Config[\'' . $key . '\'] = ';
				switch (getTypeSG($value)) {
					case 'string' :
						$ligne .= '\'' . $value . '\';';
						break;
					case 'integer' :
					case 'float' :
					case 'double' :
						$ligne .= $value . ';';
						break;
					default :
						$ligne = '';
						break;
				}
				// ajout éventuel d'un commentaire
				if($key === $cle and $comment !== '') {
					$ligne.= '// ' . $comment;
				}
				fwrite($file, $ligne . PHP_EOL);
			}

			fwrite($file, PHP_EOL);
			fwrite($file, '?>' . PHP_EOL);
			$ret = fclose($file);
		} else {
			journaliser('Problème d\'écriture du fichier config.php');
		}
		return $ret;
	}
	/** 2.3 ajout
	* retourne le code application (il provient de la configuration ou de l'utilisateur)
	* @return string : code interne de l'application
	**/
	static function getCodeAppli() {
		$ret = self::getConfig('CouchDB_prefix', '');
		return $ret;
	}
	/** 2.3 ajout
	* retourne le code de base avec le bon préfixe pour l'accès par CouchDB.
	* Par défaut le préfixe est le code de l'application.
	* Mais il peut être remplacé parce qu'on partage une base entre applications
	* on ne fait rien s'il s'agit d'une base système de CouchDB (elle commence par _)
	* @param string : code de la base à chercher
	* @return string : code de base complet pour accès CouchDB
	**/
	static function getCodeBaseComplet($pCodeBase) {
		$ret = $pCodeBase;
		// si base non système CouchDB, et si on n'a pas un cas particulier dans la config, on met le prefixe
		if (! $_SESSION['@SynerGaia'] -> sgbd -> isBaseSysteme($pCodeBase)) {
			$ret = self::getConfig('CouchDB_' . $pCodeBase,'');
			if ($ret === '') {
				$ret = self::getCodeAppli() . $pCodeBase;
			}
			$ret = $_SESSION['@SynerGaia'] -> sgbd -> NormaliserNomBase($ret);
		}
		return $ret;
	}
}
?>
