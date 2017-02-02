<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* SG_Adresse : Classe SynerGaia de gestion d'une adresse géographique
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Adresse_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Adresse_trait.php';
} else {
	trait SG_Adresse_trait{};
}
class SG_Adresse extends SG_ObjetComposite {
	// Type SynerGaia
	const TYPESG = '@Adresse';
	public $typeSG = self::TYPESG;	

	public $classeCSS = 'adresse';

	/** 1.1 ajout
	* Construction de l'objet
	*/
	function __construct($pQuelqueChose = null, $pUUId = '') {
		$this -> initObjetComposite($pQuelqueChose, $pUUId);
		$this -> champs = array('@Numero' => null, '@Rue' => null, '@Complement' => null, '@BoitePostale' => null, '@CodePostal' => null, '@Ville' => null, '@Pays' => null);
	}	
	/** 1.2 ajout
	*/
	function afficherChamp() {
		$ville = $this -> getValeurPropriete('@Ville', '');
		if(getTypeSG($ville) === '@Ville') {
			$ret = $ville->LienGeographique($this -> getValeur('@Numero', '') . ' ' . $this -> getValeur('@Rue', '') . ' ' . $ville -> toString() . ' ' . $this -> getValeur('@Pays', ''));
		} else {
			$ret = '';
		}
		return $ret . $this -> Afficher();
	}
	/**1.2 ajout
	*/
	function toString() {
		return implode(', ', $this -> tableau());
	}
	/** 1.2 ajout
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
	/**1.2 ajout
	*/
	function toHTML() {
		return '<span class="adresse">' . implode('<br>', $this -> tableau()) . '</span>';
	}
	/** 1.2 : ajout
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
