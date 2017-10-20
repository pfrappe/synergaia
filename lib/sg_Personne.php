<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Personne */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Personne_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Personne_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Personne_trait{};
}

/**
* Classe SynerGaia de gestion d'une personne
* @since 1.1
* @version 2.1.1
*/
class SG_Personne extends SG_Document {
	/** string Type SynerGaia '@Personne' */
	const TYPESG = '@Personne';

	/** string code base par défaut*/
	const CODEBASE = 'synergaia_personnes';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code de la personne */
	public $code;
	
	/**
	 * Construction de l'objet
	 * @since 1.1
	 * @param string $pCode code éventuel de la personne (permet la différenciation des homonymes)
	 * @param indefini $pTableau tableau éventuel des propriétés du document CouchDB ou SG_DocumentCouchDB
	 * @param string $pNom nom de famille
	 * @param string $pPrenom prénom usuel
	 */
	public function __construct($pCode = '', $pTableau= null, $pNom = '', $pPrenom = '') {
		$tmpCode = new SG_Texte($pCode);
		$base = self::CODEBASE;
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($pCode, $pTableau);
		$this -> code = $this -> getValeur('@Code');
		if ($pNom !== '') {
			$nom = new SG_Texte($pNom);
			$this -> setValeur('@Nom', $nom -> texte);
		}
		if ($pPrenom !== '') {
			$prenom = new SG_Texte($pPrenom);
			$this -> setValeur('@Prenom', $prenom -> texte);
		}
	}

	/**
	 * Retourne le titre de la personne (Nom Prénom)
	 * 
	 * @since 1.1
	 * @version 2.6 return SG_Texte
	 * @return SG_Texte
	 */
	public function Titre() {
		return new SG_Texte($this -> getValeur('@Nom') . ' ' . $this -> getValeur('@Prenom'));
	}

	/**
	 * Retourne l'âge de la personne
	 * @since 1.1
	 * @return SG_Nombre
	 */
	public function Age() {
		$ret = new SG_VraiFaux(false);
		$dt = $this -> getValeur('@NaissanceDate', false);
		if ($dt !== false) {
			$ret = $dt -> Age();
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Personne_trait;
}
?>
