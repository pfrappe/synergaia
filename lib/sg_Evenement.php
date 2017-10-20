<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Evenement */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');


if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Evenement_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Evenement_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Evenement_trait{};
}

/**
 * Classe SynerGaia de traitement des événements d'agenda
 * @since 1.1
 * @version 2.1.1
 */
class SG_Evenement extends SG_Document {
	/** string Type SynerGaia '@Evenement' */
	const TYPESG = '@Evenement';
	/** string code de la base des événements */
	const CODEBASE = 'synergaia_evenements';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** 1.1 ajout
	 * Construction de l'évènement
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pCodeDocument
	 * @param null|array $pTableau tableau des propriété
	 */
	public function __construct ($pCodeDocument = '', $pTableau = null) {
		$tmpCode = new SG_Texte($pCodeDocument);
		$base = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code', '');
		$this -> setValeur('@Type', '@DictionnaireMethode');
	}

	/**
	 * Déplace un événément d'une certaine quantité de temps
	 * @since 1.1
	 * @param nombre|SG_Nombre|SG_Formule $pQuantite quantité du déplacement (peut-être négatif)
	 * @param string|SG_Texte|SG_Formule $pUnite 'heure', 'jour' , 'mois', 'année'
	 * @return SG_Evenement l'événement en cours
	 */
	function DeplacerDe ($pQuantite = 0, $pUnite = '') {
		$qte = new SG_Nombre($pQuantite);
		$qte = $qte -> valeur;
		if ($qte != 0) {
			$debut = $this -> getValeur('@Debut', '');
			if ($debut !== '') {
				$debut -> Ajouter($qte, $pUnite);
			}
			$fin =  $this -> getValeur('@Fin', '');
			if ($fin !== '') {
				$fin -> Ajouter($qte, $pUnite);
			}
		}
		return $this;	
	}

	/**
	 * déplace un événément d'une certaine quantité de temps
	 * @since 1.1
	 * @param SG_DateHeure|SG_Date|SG_Formule $pDateHeure date (et heure) du déplacement
	 * @return SG_Evenement l'événement en cours
	 */
	function DeplacerAu ($pDateHeure = '' ) {
		if ($pDateHeure !== '') {
			$dt = new SG_DateHeure($pDateHeure);
			$int = $this -> debut -> Intervalle ($dt);
			$this -> setValeur('@Debut', $debut -> Ajouter($int, 'seconde'));
			$fin = $this -> getValeur('@Fin','');
			if ($fin !== '') {
				$this -> setValeur('@Fin', $fin -> AJouter($int, 'seconde'));
			}
			return $this;
		}	
	}

	/**
	 * Durée de l'événement dans l'unité fournie
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pUnite unité de la durée
	 * @return SG_Nombre
	 */
	function Duree ($pUnite = 'heure') {
		$ret = 0;
		$unite = new SG_Texte($pUnite);
		$unite = strtolower($unite -> texte);
		$debut = $this -> getValeur('@Debut','');
		if ($debut !== '') {
			$fin = $this -> getValeur('@Fin','');
			if ($fin !== '') {
				$duree = $debut -> Intervalle($fin);
			}
		}
		return new SG_Nombre($duree);		
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Evenement_trait;
}
?>
