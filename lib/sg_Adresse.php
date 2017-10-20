<?php
/** SynerGaïa Fichier contenant la classe SG_Adresse */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Adresse_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Adresse_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_Adresse_trait{};
}

/** SynerGaia
 * SG_Adresse : Classe SynerGaia de gestion d'une adresse géographique
 * @since 1.1
 */
class SG_Adresse extends SG_ObjetComposite {
	/** string Type SynerGaia */
	const TYPESG = '@Adresse';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;	

	/** string classe css associée */
	public $classeCSS = 'sg-adresse';

	/** 1.1 ajout
	* Construction de l'objet
	* @since 1.1
	* @param SG_Formule|any $pQuelqueChose
	* @param string $pUUId
	*/
	function __construct($pQuelqueChose = null, $pUUId = '') {
		$this -> initObjetComposite($pQuelqueChose, $pUUId);
		$this -> champs = array('@Numero' => null, '@Rue' => null, '@Complement' => null, '@BoitePostale' => null, '@CodePostal' => null, '@Ville' => null, '@Pays' => null);
	}

	/** 
	 * affichage du champ Adresse
	 * @since 1.2
	 * @return SG_HTML
	 */
	function afficherChamp() {
		$ville = $this -> getValeurPropriete('@Ville', '');
		if(getTypeSG($ville) === '@Ville') {
			$ret = $ville->LienGeographique($this -> getValeur('@Numero', '') . ' ' . $this -> getValeur('@Rue', '') . ' ' . $ville -> toString() . ' ' . $this -> getValeur('@Pays', ''));
		} else {
			$ret = '';
		}
		$ret = new SG_HTML($ret);
		return $ret -> Concatener($this -> Afficher());
	}

	/**
	 * transforme l'objet en chaine de caractères
	 * @since 1.2
	 * @return string
	 */
	function toString() {
		return implode(', ', $this -> tableau());
	}

	/**
	 * transforme l'adresse en tableau
	 * @since 1.2
	 * return array
	 */
	function tableau() {
		$ret = array($this -> getValeur('@Numero', '') . ' ' . $this -> getValeur('@Rue', ''));
		$champ = $this -> getValeur('@Complement', '');
		if ($champ !== '') {
			$ret[] = $champ;
		}
		$champ = $this -> getValeur('@BoitePostale', '');
		if ($champ !== '') {
			$ret[] = 'B.P. ' . $champ;
		}
		$champ = $this -> getValeur('@Localite', '');
		if ($champ !== '') {
			$ret[] = $champ;
		}
		$champ = $this -> ligneVille();
		if ($champ !== '') {
			$ret[] = $champ;
		}
		$champ = $this -> getValeur('@Pays', '');
		if ($champ !== '') {
			$ret[] = $champ;
		}
		return $ret;
	}

	/**
	 * Transforme l'objet en texte HTML
	 * @since 1.2
	 * @return string
	 */
	function toHTML() {
		return '<span class="sg-composite">' . implode('<br>', $this -> tableau()) . '</span>';
	}

	/**
	 * compose la ligne code postal + ville
	 * @since 1.2
	 * @return string
	 */
	function ligneVille() {
		$cedex = $this -> getValeur('@Cedex', '');
		if ($cedex !== '') {
			$cedex = new SG_Texte(strtoupper($cedex -> texte));
			if (! $cedex -> CommencePar('CEDEX') -> estVrai()) {
				$cedex -> texte = 'CEDEX ' . $cedex -> texte;
			}
			$cedex -> texte = ' ' . $cedex -> texte;
		}
		$ville = $this -> getTexte('@Ville', '') . $cedex;
		$ret = $this -> getTexte('@CodePostal', '');
		if ($ville !== '') {
			if ($ret !== '') {
				$ret .= ' ';
			}
			$ret .= $ville;
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Adresse_trait;
}
?>
