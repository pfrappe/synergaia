<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.2 (see AUTHORS file)
* Classe SynerGaia de gestion des fichiers joints dans _attachments d'un document
* Cet objet est à la fois un champ affichable et un réservoir de @Fichier
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Fichiers_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Fichiers_trait.php';
} else {
	trait SG_Fichiers_trait{};
}
class SG_Fichiers extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Fichiers';
	public $typeSG = self::TYPESG;
	// tableau des fichiers : la clé est le nom du fichier, chaque élément contient un [name], un [data], un [type], et éventuellement un [vignette]
	public $elements = array();
	
	/**
	* Construction de l'objet : 
	* mettre @DocumentCouchDB dans $contenant
	* @param $pQuelqueChose (@DocumentCouchDB ou @Document) : le document qui contient les fichiers 
	*/
	public function __construct($pQuelqueChose = null) {
		$this -> contenant = $pQuelqueChose;
		$type = getTypeSG($pQuelqueChose);
		if ($type === '@Document') {
			$this -> contenant = $pQuelqueChose -> doc;
		} elseif ($type === '@DocumentCouchDB') {
			$this -> contenant = $pQuelqueChose;
		}
	}
	/**
	* Conversion en chaine de caractères
	* @return string texte
	*/
	function toString() {
		$ret = '';
		if (isset($this -> contenant -> proprietes['_attachments'])) {
			foreach ($this -> contenant -> proprietes['_attachments'] as $key => $fic) {
				if($ret !== '') {
					$ret.= ', ';
				}
				$ret.= $key;
			}
		}
		return $ret;
	}
	/**
	* Conversion en code HTML
	* @return string code HTML
	*/
	function toHTML() {
		return $this -> toString();
	}
	/** 2.1.1 $url au lieu de vide pour traduction formule dans texte riche
	* Affichage
	* @return string code HTML
	*/
	function afficherChamp($pNomFichier = '') {
		$ret = '<div id="fichiers" class="fichiers"><ul class="adresse">';
		$nom = SG_Texte::getTexte($pNomFichier);
		if (isset($this -> contenant -> proprietes['_attachments'])) {
			$url = $this -> contenant -> getUUID() . '/_attachments';
			if ($nom === '') {
				foreach ($this -> contenant -> proprietes['_attachments'] as $key => $fic) {
					$objetfic = new SG_Fichier($key);
					$ret .= '<li>' . $objetfic -> afficherChamp($url) . '</li>';
				}
			} else {
				$objetfic = new SG_Fichier($nom);
				$ret .= '<li>' . $objetfic -> afficherChamp($url) . '</li>';
			}
		}
		$ret .= '</ul></div>';
		return $ret;
	}
	/** 2.2 multiple ; msg 0179
	* Modification
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '') {
		$ret = '<ul id="attachments" class="adresse">';
		if ($pRefChamp === '') {
			$tmpChamp = SG_Champ::codeChampHTML($this -> contenant -> getUUID() .'/_attachments/');
		} else {
			$tmpChamp = $pRefChamp;
		}
		if (isset($this -> contenant -> proprietes['_attachments'])) {
			foreach ($this -> contenant -> proprietes['_attachments'] as $key => $fic) {
				$objetfic = new SG_Fichier($key);
				$objetfic -> multiple = true;
				$ret.= '<li>' . $objetfic -> modifierChamp($tmpChamp) . '</li>';
			}
		} else {
			$objetfic = new SG_Fichier();
			$objetfic -> multiple = true;
			$ret.= '<li>' . $objetfic -> modifierChamp($tmpChamp) . '</li>';
		}
		$ret.= '</ul>';
		return $ret;
	}
	// 2.1 ajout
	function Modifier() {
		return new SG_HTML($this -> txtModifier());
	}
	/** 2.1 devient txtModifier
	* Modifier dans un document
	**/
	function txtModifier() {
		$ret = '<div id="fichiers" class="fichiers">';
		$ret.= '<span class="sg-titrechamp">' . SG_Libelle::getLibelle('0095', false) . ' </span>';
		$ret.= $this -> modifierChamp();
		$ret.= '<span class="fileupload-reponse" id="reponse"></span>';
		$ret.= '</div>';
		return $ret;
	}
	/** 2.2 multiple
	* calcule une chaine HTML pour insertion d'un ou plusieurs nouveau fichier en attachement
	**/
	function getNouveauFichier() {
		$id = $this -> contenant -> getUUID() .'/_attachments/';
		$objetfic = new SG_Fichier('');
		$objetfic -> multiple = true;
		$ret = '<li>' . $objetfic -> modifierChamp(SG_Champ::codeChampHTML($id)) . '</li>';
		return $ret;
	}
	/** 2.2 ajout
	* exécute une formule sur chaque fichier de la collection
	**/
	function PourChaque($pFormule = '') {
		$formule = $pFormule;
		if (getTypeSG($pFormule) !== '@Formule') {
			$formule = new SG_Formule($pFormule);
		}
		$ret = new SG_Collection();
		foreach ($this -> elements as $key => $element) {
			$fic = new SG_Fichier();
			$fic -> reference = $key;
			$fic -> contenu = $element;
			$ret -> elements[] = $formule -> calculerSur($fic, null);
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Fichiers_trait;
}
?>
