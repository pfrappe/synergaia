<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Email : Classe SynerGaia de traitement des adresses mail
**/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Email_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Email_trait.php';
} else {
	trait SG_Email_trait{};
}
class SG_Email extends SG_Texte {
	// Type SynerGaia
	const TYPESG = '@Email';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Valeur interne de l'adresse mail
	public $texte;

	/**
	* Construction de l'objet
	*
	* @param indéfini $pQuelqueChose valeur à partir de laquelle créer l'adresse
	*/
	function __construct($pQuelqueChose = null) {
		$tmpTypeSG = getTypeSG($pQuelqueChose);
		if ($tmpTypeSG === 'string') {
			$this -> texte = $pQuelqueChose;
		} elseif ($tmpTypeSG === '@Formule') {
			$tmpTexte = new SG_Texte($pQuelqueChose -> calculer());
			$this -> texte = $tmpTexte -> texte;
		} else {
			// Si objet SynerGaia
			if (substr($tmpTypeSG, 0, 1) === '@') {
				$this -> texte = $pQuelqueChose -> toString();
			} else {
				$this -> texte = '';
			}
		}
	}
	/** 1.3.0 ajout param et return objet @HTML ; 2.3 sg-email
	* @param titre inséré dans les adresses si n'existe pas déjà
	*/
	function toHTML($pTitre = '') {
		$titre = new SG_Texte($pTitre);
		$titre = $titre -> texte;
		$liste = explode(',', $this -> texte);
		foreach ($liste as &$l) {
			if ($titre !== '' and strstr($l, '<') === false) {
				$l = htmlspecialchars($titre . ' <' . $l . '>');
			}
			$l = '<a href="mailto:' . $l . '" title="écrire un message" class="sg-email">' . $l . '</a>';
		}
		$ret = new SG_HTML(implode($liste, ','));
		return $ret;
	}    
	/** 1.3 param 2
	* Affichage
	*
	* @param string $pOption style d'affichage demandé
	* @param titre (@Texte ou string) pour insérer dans l'adresse mail
	*
	* @return string code HTML
	*/
	function Afficher($pOption = '', $pTitre = '') {
		return $this -> afficherChamp($pOption, $pTitre);
	}    
	/** 1.3 param 2
	* Affichage d'un champ
	*
	* @param string $pOption style d'affichage demandé
	*
	* @return string code HTML
	*/
	function afficherChamp($pOption = '', $pTitre = '') {
		$style = '';
		$class = 'champ_Texte';

		// Lit l'option passée
		if ($pOption !== '') {
			$tmpOption = new SG_Texte($pOption);
			$option = $tmpOption -> texte;

			// Si ":" dans l'option => style sinon classe
			if (strpos($option, ':') !== false) {
				$style .= $option;
			} else {
				$class .= ' ' . $option;
			}
		}
		return $this -> toHTML($pTitre);
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Email_trait;
}
?>
