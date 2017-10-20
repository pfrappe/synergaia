<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Branche */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/** INUTILISE ACTUELLEMENT
 * Classe SynerGaia représentant une branche php d'une phrase SynerGaïa
 * @since 2.1
 */
class SG_Branche extends SG_Objet{
	/** string Type SynerGaia '@Branche' */
	const TYPESG = '@Branche';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string  */
	public $texte = '';
	/** string  */
	public $php='';
	/** string  */
	public $index;
	/** string Opération appelante */
	public $operation;
	/** string Etape en cours d'exécution */
	public $etape;
	/** string Objet principal actuel pour cette étape de cette branche */
	public $objet;
	/** string Résultat général en cours de constitution */
	public $resultat;
	/** string  */
	public $etiquette;
	/** string Code étape suivante de la branche */
	public $suite;
	
	/**
	 * Construction de l'objet
	 * @since 2.1 ajout
	 * @param indéfini $pIndex : n° de la branche dans la liste des branches
	 * @param string|SG_Texte|SG_Formule $pPHP : texte à exécuter
	 * @param string|SG_Texte|SG_Formule $pTexte : texte à exécuter
	 */
	public function __construct($pIndex = 0, $pPHP = '', $pTexte = '') {
		$this -> php = SG_Texte::getTexte($pPHP);
		$this -> index = $pIndex;
		$this -> texte = $pTexte;
	}
	/**
	 * Exécute le texte PHP avec les paramètres fournis
	 * @since 2.1 ajout
	 * @param SG_Objet $pObjet : objet sur lequel porte la branche. Par défaut SG_Rien
	 * @param SG_Operation $pOperation : opération en cours dans laquelle se trouvent les variables et le tableau des branches
	 * @return SG_Obet|SG_Erreur
	 */
	public function Executer($pObjet, $pOperation) {
		$ret = false;
		$objet = $pObjet;
		$operation = $pOperation;
		$resultat = array();
		try {
			eval($this -> php . ' $resultat[] = $ret;');
		} catch (Exception $e) {
			$resultat = new SG_Erreur($e -> getMessage());
		}
		return $resultat;
	}

	/**
	 * Crée un bouton submit avec le texte proposé et renvoie vers l'étape passée en paramètre
	 * @since 2.1 ajout
	 * @param (string) $pTexte : texte du bouton
	 * @param (integer) $pNoBranche : n° de la branche à initialiser au retour (paramètre e= de l'url)
	 * @return string
	 */
	public function Submit($pTexte = '', $pNoBranche = 0) {
		$ret = '';
		return $ret;
	}

	/** 2.1 ajout
	 * Affiche en html le texte php
	 * @since 2.1 ajout
	 * @return SG_HTML
	 */
	public function Afficher() {
		return new SG_HTML($this -> php);
	}
}
