<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Formulaire */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Formulaire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Formulaire_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1
	 */
	trait SG_Formulaire_trait{};
}

/**
 * Classe SynerGaia de traitement des textes riches avancés
 * @version 2.1.1
 */
class SG_Formulaire extends SG_Document {
	/** string Type SynerGaia '@Formulaire' */
	const TYPESG = '@Formulaire';

	/** string Type d'objet SynerGaïa */
	public $typeSG = self::TYPESG;

	/** string Code de la base */
	const CODEBASE = 'synergaia_formulaires';

	/**
	 * Construction de l'objet
	 *
	 * @since 1.3.1
	 * @param string $pCode code du theme
	 * @param array $pTableau tableau éventuel des propriétés
	 */
	public function __construct($pCode = '', $pTableau = null) {
		$tmpCode = new SG_Texte($pCode);
		$base = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code');
		$this -> setValeur('@Type', self::TYPESG);
	}

	/**
	 * Affichage du texte paramétré dans lequel des formules SynerGaïa sont intégrées entre {{formula}}
	 *
	 * @since 1.3.1
	 * @version 2.6 rerour SG_HTML
	 * @param string|SG_Texte|SG_Formule $pSurObjet
	 * @return SG_HTML code HTML
	 */
	function Afficher($pSurObjet = '') {
		$ret = new SG_HTML();
		if($pSurObjet !== '') {
			if(getTypeSG($pSurObjet) === '@Formule') {
				$principal = $pSurObjet -> calculer();
			} else {
				$principal = $pSurObjet;
			}
		} elseif ($this -> getValeur('@Principal', '') !== '') {
			$formule = new SG_Formule($this -> getValeur('@Principal', ''));
			$principal = $formule -> calculer();
		} else {
			$principal = SG_Rien::Principal();
		}
		$corps = $this -> getValeurPropriete('@Corps','');
		if ($corps !== '') {
			$corps -> contenant = $principal;
			$ret = $corps -> Afficher();
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Formulaire_trait;
}
?>
