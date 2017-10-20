<?php
/** SynerGaia fichier pour le traitement de l'objet @Objet */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
 
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Objet_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Objet_trait.php';
} else {
	/** trait vide par défaut : pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Objet_trait{};
}

/**
 * SG_Objet : Classe de gestion d'un objet de base
 * @version 2.4
 */
class SG_Objet {
	/** string Type SynerGaia '@Objet' */
	const TYPESG = '@Objet';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** boolean indicateur de composition
	 * @since 1.1 
	 */
	const COMPOSITE = false;
	
	/** string nom du champ de l'objet
	 * @since 1.1 
	 */
	public $reference;
	
	/** SG_Objet Objet contenant notre objet */
	public $contenant = null;
	
	/** string formule permettant de retrouver l'élément à partir du principal
	 * @since 1.1 
	 */
	public $index;
	
	/** string titre de l'objet pour l'affichage
	 * @since 2.1
	 */
	public $titre;
	
	/**
	 * array propriétés de l'objet
	 * @since 1.0.4
	 */
	public $proprietes = array();
	
	/**
	 * Initialisation de l'objet
	 * 
	 * @since 1.0.4
	 * @param array $pQuelqueChose Array(propriété => valeur, etc)
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
	* @version 2.4 valeur titre si pas doc
	* @return string texte
	*/
	function toString() {
		$ret = '';
		if (isset($this -> doc)) {
			$ret = $this -> doc -> toString();
		} else {
			$ret = $this -> getTitre();
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

	/**
	 * Lecture de la valeur d'une propriété (du document ou de l'objet)
	 * 
	 * @since 1.0.4
	 * @version 2.1 php, valeur par défaut
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

	/**
	 * Lecture de la valeur d'un champ de l'objet
	 * 
	 * @version 2.0 erreur 0111
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeurDefaut valeur si le champ n'existe pas
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
	 * @param indéfini $pValeur valeur si le champ n'existe pas
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

	/**
	 * Conversion en code HTML
	 * 
	 * @version 2.1.1 SG_HTML
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
	 * @version 2.6 prise en compte titre
	 * @param string|SG_Texte|SG_Formule $pClasse éventuelle classe d'affichage
	 *
	 * @return string code HTML
	 */
	function Afficher() {
		$ret = $this -> toHTML();
		if (isset($this -> titre)) {
			$ret -> texte = '<span class="sg-titrechamp">' . $this -> titre . ' : </span>' . $ret -> texte;
		}
		$args = func_get_args();
		if (isset($args[0])) {
			$classe = SG_Texte::getTexte($args[0]);
			$ret -> texte = '<div class="' . $classe . '">' . $ret -> texte . '</div>';
		}
		return $ret;
	}

	/**
	 * Modification
	 * @param string|SG_Texte|SG_Formule nom du champ de saisie
	 * @return string code HTML
	 */
	function Modifier() {
		$pRefChamp = '';
		$arg_number = func_num_args();
		if ($arg_number === 1) {
			$arg_list = func_get_args();
			$pRefChamp = SG_Texte::getTexte($arg_list[0]);
		}
		$ret = '<input type="text" name="' . $pRefChamp . '" value="' . str_replace('"', '&quot;', $this -> toString()) . '"/>';
		$ret = new SG_HTML($ret);
		$ret -> saisie = true;
		return $ret;
	}

	/**
	* Teste si l'objet est du type demandé ou de sa hiérarchi
	* 
	* @version 1.3.1 => type si pas param ; teste bouclee
	* @param  string|SG_Texte|SG_Formule $pType Type demandé
	* @return SG_VraiFaux est ou non du modèle demandé ou de sa hiérarchie, (@Texte) modèle si pas de paramètre
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

	/**
	 * Si : exécute un @Si sur l'objet en cours. Les paramètres sont ceux de SG_Rien->Si()
	 * 
	 * @since 1.0.7
	 * @param SG_Formule $pCondition
	 * @param SG_Formule $pValeurSiVrai
	 * @param SG_Formule $pValeurSiFaux
	 * @param SG_Formule $pValeurSiIndefini
	 * @return SG_Objet php (string, number, boolean, temps)
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

	/**
	 * Ceci : cet objet
	 * @since 1.0.7
	 * @return SG_Objet ceci
	 */
	function Ceci() {
		return $this;
	}

	/**
	 * Indique si l'objet dérive de @Document
	 * 
	 * @since 1.1 
	 * @version 1.3.1 simplifié (voir @Document)
	 * return SG_VraiFaux
	 */
	function DeriveDeDocument () {
		$ret = new SG_VraiFaux(false);
		return $ret;
	}

	/**
	 * Met la valeur dans une peopriété
	 * 
	 * @since 1.1 AJout
	 * @version 2.1.1 si uid @Texte
	 * @param string|SG_Texte|SG_Formule $pChamp nom du champ
	 * @param any $pValeur valeur à mettre
	 * @return SG_Objet ceci
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

	/**
	 * Sort la description de l'objet en format json
	 * @since 1.2 ajout
	 * @return string
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

	/**
	 * Transforme l'objjet dans une autre classe
	 * 
	 * @todo contrôler qu'il s'agit d'un objet dérivé (ne marche pas toujours)
	 * @since 1.2 ajout
	 * @param string|SG_Texte|SG_Formule $pType le nouveau type de l'objet
	 * @return SG_Objet
	 */
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

	/**
	 * Teste si la valeur est vide
	 * 
	 * @since 1.3 vient de SG_Rien
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
	
	/**
	 * Debug de l'objet
	 * 
	 * @since 1.1 ajout dans SG_Rien)
	 * @version 1.3 : déplacé de SG_Rien
	 * @param string message à ajouter
	 * @return SG_Objet this
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

	/**
	 * Récupère le principal (vient de SG_Rien)
	 * permet de se passer de gérer la compatibilité avec .@Principal dans les anciennes versions)
	 * 
	 * @since 1.3.1 ajout (vient de @Rien
	 * @todo voir à supprimer
	 * @return SG_Objet
	 */
	function Principal() {
		return SG_Pilote::OperationEnCours() -> Principal();
	}

	/**
	 * Teste si deux objets sont égaux
	 * @since 1.3.1 ajout
	 * @param SG_Objet $pQuelqueChose
	 * @return SG_VraiFaux
	 */
	function Egale($pQuelqueChose) {
		if(getTypeSG($pQuelqueChose) === self::TYPESG) {
			$ret = new SG_VraiFaux($pQuelqueChose === $this -> toString());
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}

	/**
	 * Teste si 'objet est un SG_Erreur
	 * pour tous les objets : false sauf SG_Erreur et dérivés
	 * 
	 * @since 1.3.4 ajout
	 * @return boolean
	 */
	function estErreur() {
		return false;
	}

	/**
	* fournit un titre à l'objet (getValeur 'Titre', puis méthode 'Titre', puis getValeur @Titre )
	* 
	* @since 2.1 ajout
	* @return string : le titre trouvé sinon ''
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

	/**
	 * recherche soit une méthode soit une propriété de l'objet
	 * 
	 * @since 2.1 ajout
	 * @version correction method_exists($this,$nom) au lieu de method_exists($nom,$nom)
	 * @param (string) $pNom : nom de la valeur recherchée
	 * @param (any) $pDefaut : valeur par défaut si aucune méthode ni propriété (par défaut : null)
	 * @return : soit la valeur réelle, soit la valeur par défaut
	 */
	function get($pNom = '', $pDefaut = null) {
		if(substr($pNom,0,1) === '@') {
			$nom = substr($pNom, 1);
		} else {
			$nom = $pNom;
		}
		if (isset($this -> proprietes[$nom])) {
			$ret = $this -> proprietes[$nom];
		} elseif (method_exists($this,$nom)) {
			$ret = $this -> $nom();
		} else {
			$ret = $this -> getValeurPropriete($pNom, $pDefaut);
		}
		return $ret;
	}

	/**
	 * est ou dérive de SG_HTML
	 * 
	 * @since 2.1 ajout
	 * @return boolean : vrai ou faux
	 */
	function estHTML() {
		$ret = (get_class($this) === 'SG_HTML' or is_subclass_of($this, 'SG_HTML'));
		return $ret;
	}

	/**
	 * Code l'html pour demander une ou plusieurs propriétés temporaires sur un objet ou un document
	 * 
	 * @since 2.5 ajout
	 * @param string|SG_Texte|SG_Formule chaine de caractères : "code,modele,titre"
	 * @return SG_HTML|SG_Erreur) : champ à saisir
	 */
	function Demander() {
		$ret = '';
		$args = func_get_args();
		if (isset($args[0])) {
			$_SESSION['saisie'] = true;
			$doc = $this;
			$opEnCours = SG_Pilote::OperationEnCours();
			// les données seront conservées dans mon tableau des propriétés
			// crée les propriétés (une par paramètre)
			$ret .= '<ul data-role="listview" data-uidoc="' . $this -> getUUID() . '">';
			$docs = SG_Dictionnaire::ObjetsDocument(true);
			for($i = 0; $i < sizeof($args); $i++) {
				$proprietes = explode(',',SG_Texte::getTexte($args[$i]));
				// selon le format de la propriété
				if (isset($proprietes[1]) and $proprietes[1] !== '' and strpos($proprietes[1], '.') !== false) {
					// le type est du genre modele.propriété
					$ipos = strpos($proprietes[1], '.');
					$type = substr($proprietes[1], 0, $ipos);
					$tmpdoc = SG_Rien::Nouveau($type);
					$champ = new SG_Champ(substr($proprietes[1], $ipos + 1), $tmpdoc);
					//$champ -> codeBase = SG_Dictionnaire::getCodeBase($type);
					$champ -> document = $doc;
					$champ -> codeDocument = $doc -> doc -> codeDocument;
					$champ -> codeBase = $doc -> doc -> codeBase;
					$champ -> refChamp = $champ -> codeBase . '/' . $champ -> codeDocument . '/' . $proprietes[0];
					// bind the field to the current operation
					//$champ -> initContenu();
					$doc -> proprietes['@Type_' . $proprietes[0]] = SG_Dictionnaire::getCodeModele($proprietes[1]);
				} else {
					// c'est une propriété directe de l'objet
					$champ = new SG_Champ('');
					if (isset($proprietes[1]) and $proprietes[1] !== '') {
						// on a un type de champ
						$champ -> typeObjet = $proprietes[1];
						// si c'est un @Document, on crée un champ de type lien
						if(array_key_exists($proprietes[1], $docs -> elements)) {
							$champ -> typeLien = $proprietes[1];
						}
					}
					$champ -> libelle = $proprietes[0];
					$champ -> codeChamp = $proprietes[0];
					//$champ -> typeLien = '';
					$champ -> multiple = false;
					$champ -> valeur = '';
					$doc -> proprietes['@Type_' . $champ -> codeChamp] = $champ -> typeObjet;
					$champ -> codeBase = $doc -> doc -> codeBase;
					$champ -> document = $doc;
					$champ -> codeDocument = $doc -> doc -> codeDocument;
					$champ -> refChamp = $champ -> codeBase . '/' . $champ -> codeDocument . '/' . $champ -> codeChamp;
					// bind the field to the current operation
					$champ -> initContenu();
				}
				if (isset($proprietes[2])) {
					$champ -> libelle = $proprietes[2];
				}
				$ret .= '<li>' . $champ -> txtModifier() . '</li>';
			}
			$ret .= '</ul>';
		}
		$ret = new SG_HTML($ret);
		$ret -> saisie = true;
		return $ret;
	}

	/**
	 * met l'objet comme principal de l'opération, éventuellement en modif.
	 * Quand c'est un document, c'est sa référence que l'on met dans principal
	 * 
	 * @since 2.5 ajout
	 * @version 2.6 ne traite plus le cas SG_Document et plus (voir dans ces classes)
	 * @return SG_Objet $this
	 */
	function setPrincipal() {
		$operation = SG_Pilote::OperationEnCours();
		$operation -> setPrincipal($this);
		return $this;
	}

	/**
	 * ajoute une liste de valeurs possibles pour une saisie ultérieure
	 * 
	 * @since 2.5 ajout
	 * @param SG_Formule $pFormule donne la liste des valeurs
	 * @return $this
	 */
	function ValeursPossibles ($pFormule = '') {
		$this -> proprietes['@vp'] = $pFormule;
		return $this;
	}

	/**
	 * Calcule une valeur d'un objet selon sa valeur actuelle.
	 * Chaque couple de paramètre représente (une valeur, un résultat)
	 * L'appel est donc objet->SiVaut(val1, res1, val2, res2, etc);
	 * La comparaison est faite à partir du résultat de toString() ou  Titre()
	 * Si la valeur n'est pas trouvée dans la liste, on retourne une erreur
	 * 
	 * @since 2.6
	 * @param any $pValeur valeur
	 * @param any $pResultat valeur tranformée
	 * @return any le résultat
	 */
	function SiVaut($pValeur = '', $pResultat = '') {
		$args = func_get_args();
		$n = func_num_args() - 1;
		$mavaleur = $this -> toString();
		$ret = new SG_Erreur('0301',$mavaleur);
		for ($i = 0; $i < $n; $i = $i + 2)  {
			$oldval = SG_Texte::getTexte($args[$i]);
			if ($mavaleur == $oldval) {
				if ($arg[$i + 1] instanceof SG_Formule) {
					$ret = $args[$i + 1] -> calculer();
				} else {
					$ret = $args[$i + 1];
				}
				break;
			}
		}
		return $ret;
	}		

	// 2.3 complément de classe créée par compilation
	use SG_Objet_trait;
}
?>
