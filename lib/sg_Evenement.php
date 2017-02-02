<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de traitement des événements d'agenda
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Evenement_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Evenement_trait.php';
} else {
	trait SG_Evenement_trait{};
}
class SG_Evenement extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Evenement';
	public $typeSG = self::TYPESG;
	
	const CODEBASE = 'synergaia_evenements';
	
	/** 1.1 ajout
	* Construction de l'évènement
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
	/** 1.1
	* déplace un événément d'une certaine quantité de temps
	* @param $pQuantite @Nombre quantité du déplacement (peut-être négatif)
	* @param $pUnite @Texte ('heure', 'jour' , 'mois', 'année')
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
	/** 1.1
	* déplace un événément d'une certaine quantité de temps
	* @param $pQuantite @Nombre quantité du déplacement (peut-être négatif)
	* @param $pUnite @Texte ('heure', 'jour' , 'mois', 'année')
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
	/** 1.1 ajout
	* Durée de l'événement dans l'unité fournie
	* @param $pUnite @Texte unité de durée
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
