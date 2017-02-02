<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de traitement des textes riches avancés
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Formulaire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Formulaire_trait.php';
} else {
	trait SG_Formulaire_trait{};
}
class SG_Formulaire extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Formulaire';
	public $typeSG = self::TYPESG;
	
	// Code de la base
	const CODEBASE = 'synergaia_formulaires';

	/** 1.3.1
	* Construction de l'objet
	*
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
	/** 1.3.1
	* Affichage du texte paramétré dans lequel des formules SynerGaïa sont intégrées entre {{formula}}
	*
	* @return string code HTML
	*/
	function Afficher($pSurObjet = '') {
		$ret = '';
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
