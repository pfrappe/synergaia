<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Fichier */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Fichier_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Fichier_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Fichier_trait{};
}

/**
 * Classe SynerGaia de gestion d'un fichier joint
 * @version 2.2
 */
class SG_Fichier extends SG_Objet {
	/** string Type SynerGaia '@Fichier' */
	const TYPESG = '@Fichier';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Nom du fichier */
	public $reference = '';
	
	/** array Contenu (['content-type'], ['data']) éventuel */
	public $contenu;
	
	/** integer Position courante de lecture */
	public $position = 0;
	
	/** string Séparateur du CSV */
	public $separateur = ',';
	
	/** string Format du fichier */
	public $format = '';
	
	/** boolean le fichier est-il multiple ?
	 * @since 2.2
	 */
	public $multiple = false;

	/**
	 * Construction de l'objet
	 * 
	 * @version 1.3.4 contenu
	 * @version 2.0 libellé 0110
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle créer le fichier
	 */
	public function __construct($pQuelqueChose = null) {
		if (!is_null($pQuelqueChose)) {
			$tmpTypeSG = getTypeSG($pQuelqueChose);

			switch ($tmpTypeSG) {
				case 'string' :
					$this -> reference = $pQuelqueChose;
					break;
				case '@Texte':
					$this -> reference = SG_Texte::getTexte($pQuelqueChose);
					break;
				case 'array' :
					foreach ($pQuelqueChose as $fichier_nom => $fichier_contenu) {
						$this -> reference = $fichier_nom;
						$this -> contenu = $fichier_contenu;
					}
					break;
				case self::TYPESG :
					$this -> reference = $pQuelqueChose -> reference;
					$this -> contenu = $pQuelqueChose -> contenu;
					break;
				default :
					if (substr($tmpTypeSG, 0, 1) === '@') {
						// Si objet SynerGaia
						if ($tmpTypeSG === '@Formule') {
							$tmp = $pQuelqueChose -> calculer();
							$tmpFichier = new SG_Fichier($tmp);
							$this -> reference = $tmpFichier -> reference;
						} else {
							journaliser(SG_Libelle::getLibelle('0110', true, $tmpTypeSG));
						}
					} else {
						journaliser(SG_Libelle::getLibelle('0110', true, $tmpTypeSG));
					}
			}
		}
	}

	/**
	 * Conversion en chaine de caractères
	 * @return string texte
	 */
	function toString() {
		return $this -> reference;
	}

	/**
	 * Conversion en code HTML
	 * since 1.3.1
	 * @return string code HTML
	 */
	function toHTML() {
		if ($this -> index !== '') {
			$refChamp = substr($this -> index, strrpos($this -> index, '/') + 1);
			$ret = $this -> afficherChamp($refChamp);
		} else {
			$ret = $this -> toString();
		}
		return $ret;
	}

	/**
	 * Affichage du fichier
	 * 
	 * @version 2.0 dbclick
	 * @version 2.1 fichiers
	 * @param $pRefChamp (string) référence complète du champ pour le cas d'un fichier à télécharger. si '' : vient de _attachments
	 * @return string code HTML
	 */
	function afficherChamp($pRefChamp = '') {
		// Création d'un id aléatoire pour ce fichier
		$tmpNomFic = $this -> toString();
		$tmpID = '' . rand(1000,1000000);
		$operation = SG_Pilote::OperationEnCours();
		$formule = array('fic', $pRefChamp, $tmpNomFic); // 2.1 

		$code = sha1(implode($formule));
		if (!isset($operation -> boutons[$code])) {
			$this -> code = $code;
			$operation -> boutons[$code] = $formule;
			$operation -> setValeur('@Boutons', $operation -> boutons);
		}
		// Préparation de l'url à mettre autour du nom de fichier
		$url = $operation -> url() . '&' . SG_Navigation::URL_VARIABLE_BOUTON . '=' . $code;
		$refdoc = $this -> getRefDocument();
		if ($refdoc !== '') {
			$url .= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' . $refdoc;
		}
		$ret = '<span class="champ_Fichier" ondblclick="SynerGaia.stopPropagation(event);"><a href="' . $url . '"';
		if(SG_ThemeGraphique::ThemeGraphique() === 'mobilex') {
			$ret .= ' target="_blank"';
		}
		$ret .= '>' . $this -> toString() . '</a></span>';
		return $ret;
	}

	/**
	 * Modification d'un champ upload de fichier(s)
	 * 
	 * @version 1.3.4 présentation, suppression
	 * @version 2.2 multiple
	 * @version 2.4 parm id
	 * @param string $pRefChamp référence du champ HTML
	 * @param string $pID id de la division affichée
	 * @return string code HTML
	 * @uses SynerGaia.effacerfichier(), SynerGaia.inputFileOnChange()
	 */
	function modifierChamp($pRefChamp = '', $pID = '') {
		$ret = '';
		if ($pID === '') {
			$id = sha1(microtime(true) . mt_rand(0, 900000));
		} else {
			$id = $pID;
		}
		if ($this -> reference === '') {
			$display = "none";
		} else {
			$display = "inline";
		}
		$ret.= '<div id="' . $id . '_nom" style="display:' . $display . '">' . $this -> reference;
		$ret.= ' <span class="instructions">(supprimer <a type="button" name="clear" title="Effacer ce fichier ?" onclick="SynerGaia.effacerfichier(event,\''.$id.'\', true)">';
		$ret.= '<img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/cancel.png"></img></a>';
		$ret.= '<input id="' . $id . '_sup" name="_sup_' . $pRefChamp . '" type="text" style="display:none" value=""/>, remplacer par </span>';
		$ret.= '</div>'; // fin div clr_
		$ret.= '<div id="' . $id . '_fic" style="display:inline"><input class="dropzone" type="file" id="'.$id.'" name="' . $pRefChamp;
		if ($this -> multiple) {
			$ret.= '[]" multiple="multiple" onchange="SynerGaia.inputFileOnChange(this.files)"';
		} else {
			$ret.= '"';
		}
		$ret.= '/></div>';
		$ret.= '<span class="instructions">, annuler <a type="button" name="clear" title="Annuler le remplacement ?" onclick="SynerGaia.effacerfichier(event,\''.$id.'\', false)">';
		$ret.= '<img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/cancel.png"></img></a>)</span>';
		$ret.= '<span class="fileupload-reponse" id="' . $id . '_rep"></span>';
		$_SESSION['script']['dropzone'] = 'dropzone_init();' . PHP_EOL;
		return $ret;
	}

	/**
	 * Récupère le code du document contenbant le fichier - si possible...
	 * @version 1.2 $ret vide si erreur
	 * @return string
	 */
	function getRefDocument() {
		$ret = '';
		if ($this -> contenant !== null) {
			if (getTypeSG($this -> contenant) === '@Champ') {
				$ret = $this -> contenant -> document -> getUUID();
			} 
		}
		return $ret;
	}

	/**
	 * Tente de transformer le fichier en objet SynerGaïa
	 * @since 1.2 ajout
	 * @return SG_Collection
	 */
	function Charger() {
		$ret = new SG_Collection();
		if($this->format === 'csv') {
			if(isset($this->proprietes[$this -> reference]['data'])) {
				$data = base64_decode($this->proprietes[$this -> reference]['data']);
				$tableau = $this->parse_csv($data);
				$ret -> elements = $tableau;
			}
		}
		return $ret;
	}

	/**
	 * source initiale Ryan Rubley sur http://fr2.php.net/manual/fr/function.str-getcsv.php
	 * str_getcsv ne traite pas correctement tous les cas rencontrés notamment les retours de ligne entre doublequote
	 * parse a CSV file into a two-dimensional array
	 * @param string $str
	 * @return array
	 */
	function parse_csv($str) {
		$str = preg_replace_callback('/([^"]*)("((""|[^"])*)"|$)/s',
			function ($matches) {
				$str = str_replace("\r", "\rR", $matches[3]);
				$str = str_replace("\n", "\rN", $str);
				$str = str_replace('""', "\rQ", $str);
				$str = str_replace(',', "\rC", $str);
				$str = preg_replace('/\r\n?/', "\n", $matches[1]) . $str;
				return $str;
			},
			$str);
		$str = preg_replace('/\n$/', '', $str);
		$tableau = explode("\n", $str);
		$ret = array_map(
			function ($line) {
				return array_map(
					function ($field) {
						$field = str_replace("\rC", ',', $field);
						$field = str_replace("\rQ", '"', $field);
						$field = str_replace("\rN", "\n", $field);
						$field = str_replace("\rR", "\r", $field);
						return $field;
					},
					explode(',', $line));
			},
			$tableau);
		return $ret;
	}

	/**
	 * Afficher pour download via echo direct
	 * @since Afficher
	 * @version 1.3.4 -> contenu
	 * @return SG_Fichier $this
	 */
	function Afficher() {
		if(is_null($this -> contenu)) {
			$this -> contenu = file_get_contents($this -> reference);
		}
		header("Content-Type: image; name=\"" . $this -> reference."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . strlen($this -> contenu));
		header("Content-Disposition: attachment; filename=\"".$this -> reference."\"");
		header("Expires: 0");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		echo $this -> contenu;
		return $this;
	}

	/**
	 * Donne ou met à jour le titre du fichier
	 * @since 2.2 ajout
	 * @param string|SG_Texte|SG_Formule $pTitre titre du fichier
	 * @return SG_Texte|SG_Fichier titre ou le fichier selon le paramètre
	 */
	function Titre ($pTitre = null) {
		if ($pTitre === null) {
			$ret = new SG_Texte($this -> reference);
		} else {
			$this -> reference = SG_Texte::getTexte($pTitre);
			$ret = $this;
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Fichier_trait;
}
?>
