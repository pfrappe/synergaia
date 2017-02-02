<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'une icone
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Icone_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Icone_trait.php';
} else {
	trait SG_Icone_trait{};
}
class SG_Icone extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Icone';
	public $typeSG = self::TYPESG;

	/**
	 * Contenu texte de l'objet
	 */
	public $code = '';
	
	// 1.1 categorie de l'icône
	public $categ = '';

	/** 1.1
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_Icone est créé
	 * @param string $pCateg categorie de l'icone
	 */
	function __construct($pQuelqueChose = null, $pCateg = '16x16/silkicons') {
		$tmpCode = new SG_Texte($pQuelqueChose);
		$this -> code = $tmpCode -> toString();
		$this -> categ = new SG_Texte($pCateg);
		$this -> categ = $this -> categ -> texte;
	}

	/**
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString() {
		return $this -> code;
	}

	/**
	* Conversion en code HTML
	*
	* @return string code HTML
	*/
	function toHTML() {
		return '<img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/' . $this -> categ . '/' . $this -> code . '" alt="' . htmlentities($this -> code, ENT_QUOTES, 'UTF-8') . '" title="' . htmlentities($this -> code, ENT_QUOTES, 'UTF-8') . '"/>';
	}

	/**
	* Affichage
	*
	* @return string code HTML
	*/
	function afficherChamp() {
		return '<span class="champ_Icone">' . $this -> toHTML() . '</span>';
	}
	/** 1.1 ajout
	*/
	function Existe() {
		$ok = file_exists(SG_Navigation::URL_THEMES . 'defaut/img/icons/' . $this -> categ . '/' . $this -> code);
		return new SG_VraiFaux($ok);
	}

	/** 1.1 categ ; 1.3.1 param 2
	* Modification
	* @param $pRefChamp référence du champ HTML
	* @param $pListeElements (@Collection) : liste des valeurs possibles (par défaut toutes)
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pListeElements = null) {
		$ret = '<select class="champ_Icone" type="text" name="' . $pRefChamp . '">';

		// Propose le choix par défaut (vide)
		$ret .= '<option value="">(aucune)</option>';

		// Calcule la liste des icones
		$listeIcones = array();
		if (is_null($pListeElements)) {
			$dossierIconesHTML = SG_Navigation::URL_THEMES . 'defaut/img/icons/' . $this -> categ;
			$dossierIcones = SYNERGAIA_PATH_TO_ROOT . '/' . $dossierIconesHTML;
			if ($dir = @opendir($dossierIcones)) {
				while (($file = readdir($dir)) !== false) {
					if (substr($file, 0, 1) != ".") {
						$listeIcones[] = $file;
					}
				}
				closedir($dir);
			}
		} else {
			if (is_array($pListeElements)) {
				$listeIcones = $pListeElements;
			} else {
				if (getTypeSG($pListeElements) === '@Formule') {
					$listeIcones = $pListeElements -> calculer();
				} else {
					$listeIcones = $pListeElements;
				}
				if (getTypeSG($listeIcones) === '@Collection') {
					$listeIcones = $pListeElements -> elements;
				} else {
					$listeIcones = array();
				}
			}
		}
		natcasesort($listeIcones);

		foreach ($listeIcones as $icone) {
			$selected = '';
			if ($icone === $this -> code) {
				$selected = ' selected="selected"';
			}
			$ret .= '<option value="' . $icone . '"' . $selected . ' style="padding-left:20px; background:url(\'' . $dossierIconesHTML . '/' . $icone . '\') 2px 50% no-repeat;">' . $icone . '</option>';
		}

		$ret .= '</select>';
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Icone_trait;
}
?>
