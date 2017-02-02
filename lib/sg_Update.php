<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.2 (see AUTHORS file)
 * SG_Update : Classe de gestion des mises à jour de version
 */
class SG_Update extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Update';
	public $typeSG = self::TYPESG;

	// Clé de config de la version du dictionnaire
	const CLE_CONFIG_HASH_DICTIONNAIRE = 'HashDictionnaireDernierImport';

	// 1.1 Clé de config de la version du dictionnaire
	const CLE_CONFIG_HASH_LIBELLES = 'HashLibellesDernierImport';

	// 1.1 Clé de config de la version des villes
	const CLE_CONFIG_HASH_VILLES = 'HashVillesDernierImport';

	/** 1.1 ajout
	 * Mise à jour nécessaire du dictionnaire ?
	 *
	 * @return boolean
	 */
	static function updateLibellesNecessaire() {
		$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::LIBELLES_REFERENCE_FICHIER);
		$hash_dernier = SG_Config::getConfig(SG_Update::CLE_CONFIG_HASH_LIBELLES, '');
		return ($hash_actuel !== $hash_dernier);
	}
	/**
	 * Mise à jour nécessaire du dictionnaire ?
	 *
	 * @return boolean
	 */
	static function updateDictionnaireNecessaire() {
		$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::DICTIONNAIRE_REFERENCE_FICHIER);
		$hash_dernier = SG_Config::getConfig(SG_Update::CLE_CONFIG_HASH_DICTIONNAIRE, '');
		return ($hash_actuel !== $hash_dernier);
	}
	/** 1.1 ajout
	* Mise à jour nécessaire des villes ?
	* @return boolean
	*/
	static function updateVillesNecessaire() {
		$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::VILLES_REFERENCE_FICHIER);
		$hash_dernier = SG_Config::getConfig(SG_Update::CLE_CONFIG_HASH_VILLES, '');
		return ($hash_actuel !== $hash_dernier);
	}

	/** 1.1 : test retour mis dans Installation ; 1.2 journalisation
	 * Mise à jour des objets/méthodes/propriétés du dictionnaire
	 */
	static function updateDictionnaire() {
		journaliser('Mise a jour du dictionnaire : debut', false);
		// Vide le cache
		SG_Cache::viderCache();
		// Installe / met à jour le dictionnaire par défaut
		$importDictionnaire = new SG_Import(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::DICTIONNAIRE_REFERENCE_FICHIER);
		$importDictionnaire -> appelEnregistrer = false;
		$ret = $importDictionnaire -> Importer(SG_Dictionnaire::CODEBASE);
		if (getTypeSG($ret) !== '@Erreur') {
			if ($ret -> estVrai() === true) {
				// Enregistre le hash du dictionnaire importé
				$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::DICTIONNAIRE_REFERENCE_FICHIER);
				$ret = SG_Config::setConfig(SG_Update::CLE_CONFIG_HASH_DICTIONNAIRE, $hash_actuel);
			}
		}
		journaliser('Mise a jour du dictionnaire : fin', false);
		return $ret;
	}
	/** 1.1 : ajout ; 1.2 journalisation ; 1.3.2 supp vidercache
	 * Mise à jour des libellés des messages
	 */
	static function updateLibelles() {
		journaliser('Mise a jour des libelles : debut', false);
		// Installe / met à jour les libellés par défaut
		$importLibelles = new SG_Import(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::LIBELLES_REFERENCE_FICHIER);
		$importLibelles -> appelEnregistrer = false;
		$ret = $importLibelles -> Importer(SG_Libelle::CODEBASE);
		if (getTypeSG($ret) !== '@Erreur') {
			if ($ret -> estVrai() === true) {
				// Enregistre le hash des libellés importés
				$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::LIBELLES_REFERENCE_FICHIER);
				$ret = SG_Config::setConfig(SG_Update::CLE_CONFIG_HASH_LIBELLES, $hash_actuel);
			}
		}
		journaliser('Mise a jour des libelles : fin', false);
		return $ret;
	}
	/** 1.1 : ajout ; 1.2 journalisation ; 1.3.2 supp vidercache
	* Mise à jour des villes françaises
	*/
	static function updateVilles() {
		journaliser('Mise a jour des villes : debut', false);
		// Installe / met à jour les viles
		$importVilles = new SG_Import(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::VILLES_REFERENCE_FICHIER);
		$importVilles -> appelEnregistrer = false;
		$ret = $importVilles -> Importer(SG_Ville::CODEBASE);
		if (getTypeSG($ret) !== '@Erreur') {
			if ($ret -> estVrai() === true) {
				// Enregistre le hash des villes importées
				$hash_nouveau = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . SG_Installation::VILLES_REFERENCE_FICHIER);
				$ret = SG_Config::setConfig(SG_Update::CLE_CONFIG_HASH_VILLES, $hash_nouveau);
			}
		}
		journaliser('Mise a jour des villes : fin', false);
		return $ret;
	}
	/** 1.0.6 ; 1.2 journalisation
	 * Méthode de migration d'une version précédente
	 * Cette méthode peut être exécutée plusieurs fois sans risque
	 * Elle supprime et recalcule les vues dont la sélection a été modifiée mais pas le nom.
	 */
	static function updateVuesAllDocuments() {
		journaliser('Recalcul des vues : debut', false);
		$ret = '';
		// recalcul des vues tous documents (retour d'objets)
		$listeObjets = SG_Dictionnaire::ObjetsDocument(false);
		$sgbd = $_SESSION['@SynerGaia'] -> sgbd;
		foreach($listeObjets -> elements as $objet) {
			$code = $objet -> getValeur('@Code');
			$nomvue = $objet -> getValeur('@Base') . '/all_' . strtolower($code) . '_list';
			$vue = new SG_Vue($nomvue, '', '', true);
			if ($vue -> Existe() -> estVrai() === true) {
				$sgbd -> getAllDocuments($code, true);
				$ret .= $vue -> code . ' recalculée' . PHP_EOL;
			}
		}
		journaliser('Recalcul des vues : fin', false);
		return $ret;
	}
}
?>
