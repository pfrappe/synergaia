<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Objet : Classe de gestion d'un objet de base
*/
// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Objet_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Objet_trait.php';
} else {
	trait SG_Objet_trait{};
}
class SG_Objet { //extends SG_Rien 
	// Type SynerGaia
	const TYPESG = '@Objet';
	public $typeSG = self::TYPESG;
	
	// 1.1 indicateur de composition 
	const COMPOSITE = false;
	
	// 1.1 nom du champ de l'objet
	public $reference;
	
	// Objet contenant notre objet
	public $contenant = null;
	
	// 1.1 : $index formule permettant de retrouver l'élément à partir du principal
	public $index;
	
	// 2.1 titre de l'objet pour l'affichage
	public $titre;
	
	/** 1.0.4
	* propriétés de l'objet
	*/
	public $proprietes = array();
	
	/** 1.0.4
	* @param array Array(propriété => valeur, etc)
	*/
	public function __construct($pQuelqueChose = null) {
		if(!is_null($pQuelqueChose)) {
			$this -> proprietes = $pQuelqueChose;
		}
	}
	/**
	* Lecture de l'UUID de l'objet
	*
	* @return string UUID de l'objet
	*/
	public function getUUID() {
		$ret = '';
		if (isset($this -> doc)) {
			$ret = $this -> doc -> getUUID();
		}
		return $ret;
	}

	/**
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString() {
		$ret = '';
		if (isset($this -> doc)) {
			$ret = $this -> doc -> toString();
		}
		return $ret;
	}

	/**
	* Conversion valeur numérique
	*
	* @return float valeur numérique
	*/
	function toFloat() {
		$ret = (double)0;
		if (isset($this -> doc)) {
			$ret = $this -> doc -> toFloat();
		}
		return $ret;
	}

	/**
	* Conversion valeur numérique
	*
	* @return integer valeur numérique
	*/
	function toInteger() {
		$ret = (integer)0;
		if (isset($this -> doc)) {
			$ret = $this -> doc -> toInteger();
		}
		return $ret;
	}

	/** 1.0.4 ; 2.1 php, valeur par défaut
	* Lecture de la valeur d'une propriété (du document ou de l'objet)
	*
	* @param string $pChamp code de la propriété
	* @param indéfini $pValeurDefaut valeur de la propriété si le champ n'existe pas
	* @param string $pModele modele si on le connait
	* @return indéfini valeur de la propriete
	*/
	public function getValeurPropriete($pChamp = null, $pValeurDefaut = null, $pModele = '') {
		$ret = $pValeurDefaut;
		$champs = explode('.', $pChamp);
		$champ = $champs[sizeof($champs) -1];
		if ($pModele === '') {
			$modele = SG_Dictionnaire::getCodeModele(getTypeSG($this) . '.' . $champ);
		} else {
			$modele = $pModele;
		}
		if (isset($this -> proprietes[$champ])) {
			if ($modele !== '') {
				$ret = SG_Rien::creerObjet($modele, $this -> proprietes[$champ]);
			} else {
				$ret = $this -> proprietes[$champ];
			}
		}
		if (is_object($ret)) {
			$ret -> index = $this -> index . '.' . $champ;
		}
		return $ret;
	}

	/** 1.1 cas des code xxx.yyy (objets composites) ; 2.0 erreur 0111
	* Lecture de la valeur d'un champ de l'objet
	*
	* @param string $pChamp code du champ
	* @param indéfini $pValeurDefaut valeur si le champ n'existe pas
	*
	* @return indéfini valeur du champ
	*/
	public function getValeur($pChamp = null, $pValeurDefaut = null) {
		$ret = null;
		$champ = $pChamp;
		$i = strpos($champ, '.');
		if($i !== false) {
			$champ = substr($champ, $i + 1);
		}
		if (isset($this -> doc)) {
			if(is_object($this->doc)) {
				$ret = $this -> doc -> getValeur($champ, $pValeurDefaut);
			} else {
				$ret = new SG_Erreur('0111', getTypeSG($this));
			}
		} else {
			$ret = $pValeurDefaut;
			if (isset($this -> proprietes[$champ])) {
				$ret = $this -> proprietes[$champ];
			}
		}
		return $ret;
	}
	/**
	* Lecture de la valeur d'un champ de l'objet
	*
	* @param string $pChamp code du champ
	* @param indéfini $pValeurDefaut valeur si le champ n'existe pas
	*
	* @return indéfini valeur du champ
	*/
	public function setValeur($pChamp = null, $pValeur) {
		$ret = $pValeur;
		if (isset($this -> doc)) {
			$this -> doc -> setValeur($pChamp, $pValeur);
		} else {
			$this -> proprietes[$pChamp] = $pValeur;
		}
		return $ret;
	}

	/** 2.1.1 SG_HTML
	 * Conversion en code HTML
	 *
	 * @return string code HTML
	 */
	function toHTML() {
		return new SG_HTML($this -> toString());
	}

	/**
	 * Conversion en @Texte
	 *
	 * @return SG_Texte texte
	 */
	function Texte() {
		return new SG_Texte($this -> toString());
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function Afficher() {
		return $this -> toHTML();
	}

	/**
	 * Modification
	 *
	 * @return string code HTML
	 */
	function Modifier() {
		$pRefChamp = '';
		$arg_number = func_num_args();
		if ($arg_number === 1) {
			$arg_list = func_get_args();
			$pRefChamp = $arg_list[0];
		}
		$ret = '<input type="text" name="' . $pRefChamp . '" value="' . str_replace('"', '&quot;', $this -> toString()) . '"/>';
		return $ret;
	}

	/** 1.3.1 => type si pas param ; teste boucle
	* Teste si l'objet est du type demandé ou de sa hiérarchie
	* @param quelquechose $pType Type demandé
	* @return (SG_VraiFaux) est ou non du modèle demandé ou de sa hiérarchie, (@Texte) modèle si pas de paramètre
	*/
	function EstUn($pType = '') {
		$type = SG_Texte::getTexte($pType);

		$typeObjet = getTypeSG($this);
		if ($type === '') {
			$ret = new SG_Texte($typeObjet);
		} else {
			$n = 0;
			while ($n < 50 and ($typeObjet !== '') and ($typeObjet !== $type)) {
				$typeObjet = SG_Dictionnaire::getCodeModele($typeObjet);
				$n++;
			}
			if ($n > 49) {
				$ret = new SG_Erreur('Une hiérarchie d\'objet erronnée dans le dictionnaire entraine une boucle !');
			} else {
				$ret = new SG_VraiFaux($typeObjet === $type);
			}
		}
		return $ret;
	}
	/** 1.0.7
	* Si : exécute un @Si sur l'objet en cours. Les paramètres sont ceux de SG_Rien->Si()
	* @return objet php (string, number, boolean, temps)
	*/
	function Si ($pCondition = '', $pValeurSiVrai = null, $pValeurSiFaux = null, $pValeurSiIndefini = null) {
		if (getTypeSG($pCondition) === '@Formule') {
			$pCondition -> objet = $this;
		}
		if (getTypeSG($pValeurSiVrai) === '@Formule') {
			$pValeurSiVrai -> objet = $this;
		}		 
		if (getTypeSG($pValeurSiFaux) === '@Formule') {
			$pValeurSiFaux -> objet = $this;
		}
		if (getTypeSG($pValeurSiIndefini) === '@Formule') {
			$pValeurSiIndefini -> objet = $this;
		}
		$ret = SG_Rien::Si($pCondition, $pValeurSiVrai, $pValeurSiFaux, $pValeurSiIndefini);
		return $ret;
	}
	/** 1.0.7
	* Ceci : cet objet
	*/
	function Ceci() {
		return $this;
	}
	/** 1.1 ; 1.3.1 simplifié (voir @Document)
	* Indique si l'objet dérive de @Document
	*/
	function DeriveDeDocument () {
		$ret = new SG_VraiFaux(false);
		return $ret;
	}
	/** 1.1 AJout ; 1.3.4 Marche si collection de liens ; 2.1.1 si uid @Texte
	*/
	function MettreValeur($pChamp = '', $pValeur = '') {
		$champ = SG_Texte::getTexte($pChamp);
		if (getTypeSG($pValeur) === '@Formule') {
			$valeur = $pValeur -> calculer();
		} else {
			$valeur = $pValeur;
		}
		if ($champ !== '') {
			if (SG_Dictionnaire::isLien(getTypeSG($this) . '.' . $champ)) {
				if(get_class($valeur) === 'SG_Collection') {
					$valeurmultiple = array();
					foreach($valeur -> elements as $val) {
						if (method_exists($val, 'getUUID')) {
							$valeurmultiple[] = $val -> getUUID();
						} else {
							$valeurmultiple[] = $val;
						}
					}
					$this -> setValeur($champ, $valeurmultiple);
				} else {
					if (getTypeSG($valeur) === '@Texte') { // on suppose que l'on fournit directement l'uid de l'objet
						$this -> setValeur($champ, $valeur -> texte);
					} elseif (method_exists($valeur, 'getUUID')) {
						$this -> setValeur($champ, $valeur -> getUUID());
					} else {
						$this -> setValeur($champ, $valeur);
					}
				}
			} else {
				$this -> setValeur($champ, $valeur);
			}
		}
		return $this;
	}
	/** 1.2 ajout
	*/
	function JSON() {
		$ret = $this -> toString();
		if($ret !== '') {
			// échapper les caractères
			if(strlen($ret) > 2) {
				$txt = substr($ret,1,sizeof($ret) - 2);
				$txt = str_replace(array('"', '[', '{', '}', ']'), array('\"', '\[', '\{', '\}', '\]'), $txt);
				$ret = substr($ret,0,1) . $txt . substr($ret, -1);
			}
			// entourer si nécessaire
			$braket = '"';
			$c = substr($ret,0,1);
			if ($c !== $braket and $c !== '{' and $c !== '[') {
				$ret = '"' . $ret . '"';
			}
		}
		
		$ret = new SG_Texte($ret);
		return $ret;
	}
	// 1.2 ajout
	function Devient($pType) {
		$typeactuel = getTypeSG($this);
		$typenew = new SG_Texte($pType);
		$typenew = $typenew->texte;
		$classe = SG_Dictionnaire::getClasseObjet($typenew);
		$typeObjet = $this -> getValeur('@Type','');
		if($typeObjet = '@Document') { // c'est un @Document : changer la propriété @Type
			$this -> setValeur('@Type', $typenew);
		} else {
			if (!class_exists($classe)) {
				$ret = new SG_Erreur('');
			} else {
				$ret = unserialize(sprintf('O:%d:"%s"%s', strlen($classe), $classe, strstr(strstr(serialize($instance), '"'), ':')));
			}
		}
		return $ret;
	}
	/** 1.3 vient de SG_Rien
	 * Teste si la valeur est vide
	 *
	 * @return SG_VraiFaux est vide
	 */
	function EstVide() {
		$retBool = true;
		if ($this -> toString() !== '') {
			$retBool = false;
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}
	/** 1.3 : déplacé de @Rien (1.1 ajout)
	* Debug de l'objet
	*/
	function Tracer($pMsg = '') {
		$msg = new SG_Texte($pMsg);
		if($msg -> texte === '') {
			tracer($this);
		} else {
			tracer($msg, $this);
		}
		return $this;
	}
	//1.3.1 ajout (vient de @Rien : permet de se passer de gérer la compatibilité avec .@Principal dans les anciennes versions)
	function Principal() {
		return SG_Navigation::OperationEnCours() -> Principal();
	}
	/** 1.3.1 ajout
	* @return (SG_VraiFaux) vide
	*/
	function Egale($pQuelqueChose) {
		if(getTypeSG($pQuelqueChose) === self::TYPESG) {
			$ret = new SG_VraiFaux($pQuelqueChose === $this -> toString());
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}
	/** 1.3.4 ajout
	* pour tous les objets : false sauf SG_Erreur et dérivés
	**/
	function estErreur() {
		return false;
	}
	/** 2.1 ajout
	* fournit un titre à l'objet (getValeur 'Titre', puis méthode 'Titre', puis getValeur @Titre )
	* @return (string) : le titre trouvé sinon ''
	**/
	function getTitre() {
		$ret = $this -> getValeur('Titre', null);
		if (is_null($ret)) {
			if (method_exists($this, 'Titre')) {
				$ret = $this -> Titre();
			} else {
				$ret =  $this -> getValeur('@Titre', '');
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* recherche soit une méthode soit une propriété de l'objet
	* @param (string) $pNom : nom de la valeur recherchée
	* @param (any) $pDefaut : valeur par défaut si aucune méthode ni propriété (par défaut : null)
	* @return : soit la valeur réelle, soit la valeur par défaut
	**/
	function get($pNom = '', $pDefaut = null) {
		if(substr($pNom,0,1) === '@') {
			$nom = substr($pNom, 1);
		} else {
			$nom = $pNom;
		}
		if (isset($this -> proprietes[$nom])) {
			$ret = $this -> proprietes[$nom];
		} elseif (method_exists($nom,$nom)){
			$ret = $this -> $nom();
		} else {
			$ret = $this -> getValeurPropriete($pNom, $pDefaut);
		}
		return $ret;
	}
	/** 2.1 ajout
	* est ou dérive de SG_HTML
	* @return boolean : vrai ou faux
	**/
	function estHTML() {
		$ret = (get_class($this) === 'SG_HTML' or is_subclass_of($this, 'SG_HTML'));
		return $ret;
	}
	// 2.3 complément de classe créée par compilation
	use SG_Objet_trait;
}
?>
