<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Fichiers
 * @todo voir si encore utile ? */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Fichiers_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Fichiers_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Fichiers_trait{};
}

/**
 * Classe SynerGaia de gestion des fichiers joints dans _attachments d'un document
 * Cet objet est à la fois un champ affichable et un réservoir de @Fichier
 * Depuis la version 2.6, il gère les fichiers en externe (à la place de _attachments) via le paramètre dir.
 * @version 2.2
 */
class SG_Fichiers extends SG_Objet {
	/** string Type SynerGaia '@Fichiers' */
	const TYPESG = '@Fichiers';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** array tableau des fichiers : la clé est le nom du fichier, 
	 * chaque élément contient un [name], un [data], un [type], et éventuellement un [vignette]
	 */
	public $elements = array();
	
	/**
	 * Construction de l'objet : mettre @DocumentCouchDB dans $contenant
	 * @param SG_DocumentCouchDB|SG_Document $pQuelqueChose : le document qui contient les fichiers 
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

	/**
	 * Calcule le code html pour l'affichage comme champ
	 * @version 2.1.1 $url au lieu de vide pour traduction formule dans texte riche
	 * @param string|SG_Texte|SG_Formule $pNomFichier
	 * @return string code HTML
	 */
	function afficherChamp($pNomFichier = '') {
		$ret = '<div id="fichiers" class="fichiers"><ul class="sg-composite">';
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

	/**
	 * Calcule le code html pour la modification dans un champ
	 * 
	 * @version 2.2 multiple ; msg 0179
	 * @param string $pRefChamp
	 * @return string code HTML
	 * @uses JS SynerGaia.ajouterfichier()
	 * @todo mettre libellé en fichier
	 */
	function modifierChamp($pRefChamp = '') {
		$ret = '<ul id="attachments" class="sg-composite">';
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
		// bouton pour ajouter un nouveau fichier
		$objetfic = new SG_Fichier();
		$objetfic -> multiple = true;
		$ret.= '<div id="newfic" style="display:none">' . $objetfic -> modifierChamp($tmpChamp,'idnewfic') . '</div>';
		$ret.= '<span id="newfic-btn" class="sg-newfic-btn" onclick="SynerGaia.ajouterfichier(event, \'newfic\', false, null)">Ajouter un fichier</span>';
		return $ret;
	}

	/**
	 * Calcule le code HTML pourla modification
	 * 
	 * @since 2.1 ajout
	 * @return SG_HTML
	 */
	function Modifier() {
		return new SG_HTML($this -> txtModifier());
	}

	/**
	 * Modifier dans un document
	 * 
	 * @version 2.1 devient txtModifier
	 * @version 2.6 .sg-fichiers, .sg-upload
	 * @return string
	 */
	function txtModifier() {
		$ret = '<div id="fichiers" class="sg-fichiers">';
		$ret.= '<span class="sg-titrechamp">' . SG_Libelle::getLibelle('0095', false) . ' </span>';
		$ret.= $this -> modifierChamp();
		$ret.= '<span class="sg-upload" id="reponse"></span>';
		$ret.= '</div>';
		return $ret;
	}

	/**
	 * calcule une chaine HTML pour insertion d'un ou plusieurs nouveau fichier en attachement
	 * @version 2.2 multiple
	 * @return string code html
	 */
	function getNouveauFichier() {
		$id = $this -> contenant -> getUUID() .'/_attachments/';
		$objetfic = new SG_Fichier('');
		$objetfic -> multiple = true;
		$ret = '<li>' . $objetfic -> modifierChamp(SG_Champ::codeChampHTML($id)) . '</li>';
		return $ret;
	}

	/**
	 * exécute une formule sur chaque fichier de la collection
	 * @since 2.2 ajout
	 * @param SG_Formule $pFormule
	 * @return SG_Collection collection des résultats de chaque exécution
	 */
	function PourChaque($pFormule = '') {
		$formule = $pFormule;
		if (! $pFormule instanceof SG_Formule) {
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
