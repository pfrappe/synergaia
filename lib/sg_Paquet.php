<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.1 (see AUTHORS file)
 * SG_Paquet : Classe de traitement d'un pack standard SynerGaïa
 */
class SG_Paquet extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Paquet';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;
	
	// code du paquet
	public $code='';
	// chemin de stockage
	public $chemin='';
	// titre du paquet
	public $titre= '';
		
	// type : privé 'p', standard SynerGaïa 's'
	public $type='s';
	
	/** 1.3.1 ajout
	* Construction d'un paquet
	**/
	function __construct($pCode = '', $pChemin = '') {
		$this -> chemin = SG_Texte::getTexte($pChemin);
		if(substr($this->chemin, -1) !== '/') {
			$this -> chemin .= '/';
		}
		$this -> code = SG_Texte::getTexte($pCode);
		
		// charge le paquet pour trouver le nom (on cherche un fichier ".json")

		$contenuTexte = file_get_contents($this -> chemin . $this -> code . '.json');
		$contenu = json_decode($contenuTexte, true);
		if(is_array($contenu)) {
			if (sizeof($contenu) !== 0) {
				$this -> titre = array_keys($contenu)[0];
			}
		}
	}
	/** 1.3.1 ajout
	* Titre
	**/
	function Titre() {
		return new SG_Texte($this -> toString()) ;
	}
	/** 1.3.1 ajout
	* texte de sortie
	**/
	function toString() {
		return $this -> titre;
	}
	/** 1.3.1 ajout
	* Code
	**/
	function Code() {
		return new SG_Texte($this -> code);
	}
	/** 1.3.1 ajout
	* Importer ou mettre à jour dans SynerGaïa
	**/
	function Importer() {
		$import = new SG_Import($this -> chemin . $this -> code . '.json');
		$import -> Importer(SG_Dictionnaire::CODEBASE);
		return $this;
	}
	/** 1.3.1 ajout
	* Type en code
	**/
	function Type() {
		return new SG_Texte($this -> type);
	}
	/** 1.3.1 ajout
	* Type en clair
	* @return (@Texte) type en clair
	**/
	function TypeEnClair() {
		switch ($this -> type) {
			case 's' :
				$ret = new SG_Texte('Standard SynerGaïa');
				break;
			case 'p' :
				$ret = new SG_Texte('Privé');
				break;
			default :
				$ret = new SG_Erreur('0067', $this -> type);
		}
		return $ret;
	}
}
