<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.2 (see AUTHORS file) : INUTILISE ACTUELLEMENT
* Classe SynerGaia représentant une branche php d'une phrase SynerGaïa
**/
class SG_Branche extends SG_Objet{
	// Type SynerGaia
	const TYPESG = '@Branche';
	public $typeSG = self::TYPESG;
	
	public $texte = '';
	public $php='';
	public $index;
	public $operation;
	public $etape;
	public $objet;
	public $resultat;
	public $etiquette;
	public $suite;
	
	/** 2.1 ajout
	* Construction de l'objet
	* @param (string ou objet) $pPHP : texte à exécuter
	* @param indéfini $pIndex : n° de la branche dans la liste des branches
	*/
	public function __construct($pIndex = 0, $pPHP = '', $pTexte = '') {
		$this -> php = SG_Texte::getTexte($pPHP);
		$this -> index = $pIndex;
		$this -> texte = $pTexte;
	}
	/** 2.1 ajout
	* Exécute le texte PHP avec les paramètres fournis
	* @param (SG_Objet) $pObjet : objet sur lequel porte la branche. Par défaut SG_Rien
	* @param (SG_Operation) $pOperation : opération en cours dans laquelle se trouvent les variables et le tableau des branches
	**/
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
	/** 2.1 ajout
	* Crée un bouton submit avec le texte proposé et renvoie vers l'étape passée en paramètre
	* @param (string) $pTexte : texte du bouton
	* @param (integer) $pNoBranche : n° de la branche à initialiser au retour (paramètre e= de l'url)
	**/
	public function Submit($pTexte = '', $pNoBranche = 0) {
		$ret = '';
		return $ret;
	}
	/** 2.1 ajout
	* Affiche en html le texte php
	**/
	public function Afficher() {
		return new SG_HTML($this -> php);
	}
}
