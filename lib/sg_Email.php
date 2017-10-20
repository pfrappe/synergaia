<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Email */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Email_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Email_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Email_trait{};
}

/**
 * SG_Email : Classe SynerGaia de traitement des adresses mail
 * @version 2.3
 */
class SG_Email extends SG_Texte {
	/** string Type SynerGaia '@Email' */
	const TYPESG = '@Email';
	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Valeur interne de l'adresse mail */
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

	/**
	 * Calcule le code html pour l'affichage d'une adresse email
	 * 
	 * @since 1.3.0 ajout param et return objet SG_HTML 
	 * @version 2.3 classe css sg-email
	 * @param string|SG_Texte|SG_Formule titre inséré dans les adresses si n'existe pas déjà
	 * @return SG_HTML
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
	
	/**
	 * Calcule le code html pour l'affichage comme champ
	 * @since 1.3.0 param 2
	 * @version 2.6 return SG_HTML
	 * @param string|SG_Texte|SG_Formule $pOption style d'affichage demandé
	 * @param string|SG_Texte|SG_Formule $pTitre titre pour insérer dans l'adresse mail
	 * @return SG_HTML code HTML
	 */
	function Afficher($pOption = '', $pTitre = '') {
		return new SG_HTML($this -> afficherChamp($pOption, $pTitre));
	}

	/**
	 * Affichage d'un champ
	 * @version 1.3 param 2
	 * @param string|SG_Texte|SG_Formule $pOption style d'affichage demandé
	 * @param string|SG_Texte|SG_Formule $pTitre titre pour insérer dans l'adresse mail
	 * @param string $pOption style d'affichage demandé
	 *
	 * @return string code HTML
	 */
	function afficherChamp($pOption = '', $pTitre = '') {
		$style = '';
		$class = 'sg-texte';

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
