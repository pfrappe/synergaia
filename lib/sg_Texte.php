<?php
/** SynerGaia fichier de gestion de l'objet @Texte */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Texte_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Texte_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Texte_trait{};
}

/**
 * Classe SynerGaia de traitement des textes
 * 
 * @version 2.4
 */
class SG_Texte extends SG_Objet {
	/** string Type SynerGaia '@Texte' */
	const TYPESG = '@Texte';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Contenu texte de l'objet */
	public $texte = '';

	/**
	 * Construction de l'objet
	 * 
	 * @version 2.4 correct si $val boolean
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_Texte est créé
	 * @level 0 sauf si objet ou formule en paramètre
	 */
	function __construct($pQuelqueChose = null) {
		switch (getTypeSG($pQuelqueChose)) {
			case 'integer' :
				$this -> texte = (string)($pQuelqueChose);
				break;
			case 'double' :
				$this -> texte = (string)($pQuelqueChose);
				break;
			case 'string' :
				$this -> texte = $pQuelqueChose;
				break;
			case '@Formule' :
				$this -> texte = SG_Texte::getTexte($pQuelqueChose -> calculer());
				break;
			default :
				// Si objet SynerGaia
				if (is_object($pQuelqueChose)) {
					$this -> texte = $pQuelqueChose -> toString();
				} elseif (is_array($pQuelqueChose)) {
					foreach ($pQuelqueChose as $val) {
						if (is_string($val)) {
							$this -> texte .= $val;
						} else {
							$this -> texte .= strval($val);
						}
					}
				} else {
					$this -> texte = '';
				}
		}
		// cas des SG_HTML
		if (property_exists($this, 'saisie') and is_object($pQuelqueChose) and property_exists($pQuelqueChose, 'saisie')) {
			$this -> saisie = $pQuelqueChose -> saisie;
		}
	}

	/**
	 * Conversion en chaine de caractères (=getTexte mais non static)
	 * 
	 * @return string texte
	 */
	function toString() {
		$ret = '';
		if (func_num_args() === 0) {
			$ret = $this -> texte;
		} else {	
			$ret = func_get_arg(0);
			$typesg = getTypeSG($ret);
			if ($typesg === '@Formule') {
				$ret = $ret -> calculer();
				$typesg = getTypeSG($ret);
			}
			if ($ret === null) {
				$ret = 'NULL';
			} elseif (property_exists($ret, 'texte')) {
				$ret = $ret -> texte;
			} elseif ($typesg !== 'string') {
				$ret = $ret -> toString();
			}
		}
		return $ret;
	}

	/**
	 * Conversion valeur numérique
	 *
	 * @return float valeur numérique
	 */
	function toFloat() {
		$tmpNombre = new SG_Nombre($this -> texte);
		return $tmpNombre -> valeur;
	}

	/**
	 * Conversion en code HTML
	 * 
	 * @version 2.1.1 SG_HTML
	 * @return SG_HTML code HTML équivalent
	 */
	function toHTML() {
		return new SG_HTML($this -> texte);
	}

	/**
	 * Affichage
	 *
	 * @param string $pOption style d'affichage demandé
	 * @return string code HTML
	 */
	function Afficher($pOption = '') {
		return $this -> afficherChamp($pOption);
	}

	/**
	 * Affichage d'un champ
	 * 
	 * @version 1.3.1 déplace js vers TexteFormule 
	 * @param string $pOption style d'affichage demandé
	 * @return SG_HTML code HTML
	 */
	function afficherChamp($pOption = '') {
		$style = '';
		$class = 'sg-texte';
		$ret = '';
		$option = '';

		// Lit l'option passée
		if ($pOption !== '') {
			$option = SG_Texte::getTexte($pOption);

			// Si ":" dans l'option => style sinon classe
			if (strpos($option, ':') !== false) {
				$style .= $option;
			} else {
				$class .= ' ' . $option;
			}
		}
		$ret .= '<span class="' . $class . '" style="' . $style . '">' . htmlentities($this -> texte, ENT_QUOTES, 'UTF-8') . '</span>';
		return new SG_HTML($ret);
	}

	/**
	 * Affiche le texte entre des balises hi
	 * 
	 * @since 1.2 ajout
	 * @version 1.3.1 SG_HTML
	 * @param string|SG_Texte|SG_Formule $pBalise balise à utiliser (par défaut 'h1')
	 * @param string|SG_Texte|SG_Formule $pOption options CSS complémentaires
	 * @return SG_HTML
	 */
	function AfficherCommeTitre($pBalise = 'h1', $pOption = '') {
		$balise = new SG_Texte($pBalise);
		$balise = $balise -> texte;
		$texte = $this -> afficherChamp($pOption);
		if(getTypeSG($texte) === '@HTML') {
			$texte = $texte -> texte;
		}
		if ($balise === '') {
			$ret = $texte;
		} else {
			$ret = '<' . $balise . '>' . $texte . '</' . $balise . '>';
		}
		return new SG_HTML($ret);
	}

	/**
	 * Affiche comm un titre rouge
	 * @since 2.1 ajout
	 * @formula : .@Afficher("font-size:18pt;color:#A00;")
	 **/
	function AfficherCommeTitreRouge() {
		return $this -> Afficher("font-size:18pt;color:#A00;");
	}

	/**
	 * Calcul le texte HTML de la modification du champ
	 * 
	 * @version 2.0 stoppropagation dblclick
	 * @param string $pRefChamp référence du champ HTML
	 * @param SG_Collection $pValeursPossibles collection des valeurs proposées
	 * @return string code HTML
	 * @uses SynerGaia.stopPropagation()
	 */
	function modifierChamp($pRefChamp = '', $pValeursPossibles = null) {
		$ret = '';
		// Si on a passé une liste de valeurs proposées
		$valActuelle = $this -> toString();
		if (getTypeSG($pValeursPossibles) === '@Collection') {
			$ret = '<select class="sg-texte" type="text" name="' . $pRefChamp . '">';

			$nbValeursPossibles = sizeof($pValeursPossibles -> elements);
			for ($i = 0; $i < $nbValeursPossibles; $i++) {
				$valeurProposee = $pValeursPossibles -> elements[$i];
				if (is_object($valeurProposee)) {// and SG_Dictionnaire::isObjetExiste(getTypeSG($valeurProposee), 0, 1)) { 1.3.2
					$valeurProposee = $valeurProposee -> toString();
				}
				$valeurAffichee = '';
				// Eclate si un "|" est présent : ValeurAffichée|ValeurEnregistrée
				if (strpos($valeurProposee, '|') === false) {
					// Pas de '|'
					$valeurAffichee = $valeurProposee;
				} else {
					$elements = explode('|', $valeurProposee);
					$valeurAffichee = $elements[0];
					$valeurProposee = $elements[1];
				}
				$selected = '';

				if ($valeurProposee === $valActuelle) {
					$selected = ' selected="selected"';
				}
				$ret .= '<option value="' . $valeurProposee . '"' . $selected . '>' . $valeurAffichee . '</option>';
			}

			$ret .= '</select>';
		} else {
			$ret .= '<textarea class="sg-texte" name="' . $pRefChamp . '" ondblclick="SynerGaia.stopPropagation(event);">' . $valActuelle . '</textarea>';
		}

		return $ret;
	}

	/**
	 * Conversion en chaine de majuscules
	 * 
	 * @since 1.0.7
	 * @version 2.2 test exist mb_strtoupper
	 * @param $pAccents boolean : garde les accents (par défaut)
	 * @return SG_Texte texte en majuscules
	 * @level 0 sauf si formule en paramètre
	 */
	function Majuscules($pAccents = true) {
		$accents = $pAccents;
		if(is_object($pAccents)) {
			$accents = new SG_VraiFaux($pAccents);
			$accents = $accents -> estVrai();
		}
		$convert_minuscules = array("à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý");
		if ($accents) {
			$convert_majuscules = array("À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý");
		} else {
			$convert_majuscules = array("A", "A", "A", "A", "A", "A", "AE", "C", "E", "E", "E", "E", "I", "I", "I", "I", "D", "N", "O", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y");
		}
		if (function_exists('mb_strtoupper')) {
			$ret = new SG_Texte(str_replace($convert_minuscules, $convert_majuscules, mb_strtoupper($this -> texte)));
		} else {
			$ret = new SG_Texte(str_replace($convert_minuscules, $convert_majuscules, strtoupper($this -> texte)));
		}
		return $ret;
	}

	/**
	 * Conversion en chaine de minuscules
	 * @since 1.0.7
	 * @version 2.3 correction minuscules > majuscules...
	 * @return SG_Texte texte en minuscules
	 * @level 0 sauf si formule en paramètre
	 */
	function Minuscules() {
		$convert_minuscules = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý");
		$convert_majuscules = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý");

		if (function_exists('mb_strtolower')) {
			$ret = new SG_Texte(str_replace($convert_majuscules, $convert_minuscules, mb_strtolower($this -> texte)));
		} else {
			$ret = new SG_Texte(str_replace($convert_majuscules, $convert_minuscules, strtolower($this -> texte)));
		}
		return $ret;
	}

	/**
	 * Comparaison à une autre chaine
	 *
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux chaines sont identiques
	 * @level 0 sauf si formule en paramètre
	 */
	function Egale($pQuelqueChose) {
		$autreTexte = new SG_Texte($pQuelqueChose);
		$ret = new SG_VraiFaux($this -> texte === $autreTexte -> texte);
		return $ret;
	}

	/**
	 * Comparaison à une chaine vide
	 *
	 * @return SG_VraiFaux vrai si la chaine est vide
	 */
	function EstVide() {
		$ret = $this -> Egale('');
		return $ret;
	}

	/**
	 * Comparaison à une liste de chaines. 
	 * On travaille uniquement à partir de func_get_args()
	 * 
	 * @since 1.0.4
	 * @version 2.1.1 test $elt nul
	 * @param indéfini $pElements éléments
	 * @return SG_VraiFaux vrai si la chaine est présente dans la liste
	 */
	function EstParmi($pElements = null) {
		// constitution d'un tableau d'éléments à partir des paramètres
		$elements = array();
		$nbElements = func_num_args();
		if (func_num_args() == 1) {
			$arg = func_get_arg(0);
			if(getTypeSG($arg) === '@Formule') {
				$elements = $arg -> calculer();
			}
			if(getTypeSG($elements) === '@Collection') {
				$elements = $elements -> elements;
			}
			foreach( $elements as &$elt) {
				if (is_object($elt)) {
					$elt = $elt -> toString();
				} else {
				}
			}
		} else {
			for ($i = 0; $i < $nbElements; $i++) {
				$elements[] = SG_Texte::getTexte(func_get_arg($i));
			}
		}
		$retBool = false;
		if (in_array($this -> toString(), $elements)) {
			$retBool = true;
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/**
	 * Concaténation à une autre chaine
	 * 
	 * @version 2.3 get_class
	 * @param indéfini suite des textes à concaténer
	 * @return SG_Texte concaténation des chaines
	 */
	function Concatener() {
		$modele = get_class($this);
		$ret = new $modele($this -> texte);
		$args = func_get_args();
		foreach ($args as $arg) {
			$ret -> texte.= SG_Texte::getTexte($arg);
		}
		return $ret;
	}
	
	/**
	 * Contient permet de rechercher si une chaine de caractère appartient ou non au texte. 
	 * Cette recherche ne tient compte ni de la casse ni des accents.
	 * 
	 * @since 1.0.4 ajout
	 * @version 1.2 modification 2nd paramètre
	 * @param string $pQuelqueChose la chaine à rechercher
	 * @param string $pMot délimiteur du mot
	 * @return @VraiFaux selon que la chaine appartient ou non au texte
	 */
	function Contient($pQuelqueChose = '', $pMot = '') {
		$ret = new SG_VraiFaux(-1); //faux
		$chaine = SG_Texte::getTexte($pQuelqueChose);
		if (strlen($this -> texte) >= strlen($chaine)) {
			$mot = SG_Texte::getTexte($pMot);
			if(strtolower($this->texte) == strtolower($chaine)) { // c'est la chaine
				$ret = new SG_VraiFaux(1); // vrai
			} elseif (stripos($this->texte, $chaine . $mot) === 0) { // au début
				$ret = new SG_VraiFaux(1); // vrai
			} elseif (stripos($this->texte, $mot . $chaine) === strlen($this->texte) - strlen($chaine) - strlen($mot)) {// à la fin
				$ret = new SG_VraiFaux(1); // vrai
			} elseif (stripos($this->texte, $mot . $chaine . $mot) !== false) {// dedans
				$ret = new SG_VraiFaux(1); // vrai
			}
		}
		return $ret;
	 }

	/**
	 * transforme en nombre un texte numérique
	 * @since 1.1 : AJout
	 * @param boolean|SG_VraiFaux|SG_Formule $pForcer0
	 * @return SG_Nombre|SG_Erreur
	 */
	function EnNombre($pForcer0 = false) {
		$forcer0 = SG_VraiFaux::getBooleen($pForcer0);
		if (is_numeric($this -> texte)) {
			$ret = new SG_Nombre($this -> toFloat());
		} elseif ($forcer0) {
			$ret = 0;
		} else {
			$ret = new SG_Erreur('Non numérique');
		}
		return $ret;
	}

	/**
	 * Normalise le nom en enlevant les caractères hors nomes et en compactant (restent lettres chiffres et _
	 * 
	 * @param string|SG_Texte|SG_Formule $pTexte texte à normaliser
	 * @return string
	 */
	function Normaliser($pTexte = '') {
		$texte = SG_Texte::getTexte($pTexte);
		if ($texte  === '') {
			$texte = $this -> texte;
		}
		$orig = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ &~#{}()[]|`^@=°¨£¤%+-?!,;.:§/$µ*\\\'';
		$dest = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby___________________________________';
		$nomNormalise = strtr(strtolower(utf8_decode($texte)), utf8_decode($orig), $dest);
		// Si commence par un chiffre : ajout un préfixe
		$p = substr($nomNormalise, 0, 1);
		if (($p >= '0') and ($p <= '9')) {
			$nomNormalise = 'sg_' . $nomNormalise;
		}
		return $nomNormalise;
	}

	/**
	 * Indique si le SG_Texte commence par une chaine passée en paramètre
	 * 
	 * @since 1.2 ajout
	 * @param string|SG_Texte|SG_Formule $pDebut
	 * @return SG_VraiFaux : si $pDebut = '' : indéfini, sinon selon la comparaison
	 */
	function CommencePar($pDebut = '') {
		$ret = new SG_VraiFaux(false);
		$debut = SG_Texte::getTexte($pDebut);
		if ($debut === '') {
			$ret = new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_INDEF);
		} else {
			if (strlen($this -> texte) >= strlen($debut)) {
				if (substr_compare($this -> texte, $debut, 0, strlen($debut)) === 0) {
					$ret = new SG_VraiFaux(true);
				}
			}
		}
		return $ret;
	}

	/**
	 * Remplace une chaine dans un SG_Texte
	 * 
	 * @since 1.2 ajout
	 * @param string|SG_Texte|SG_Formule $pChaine
	 * @param string|SG_Texte|SG_Formule $pValeur
	 */
	function Remplacer($pChaine = '', $pValeur = '') {
		$this -> texte = str_replace(SG_Texte::getTexte($pChaine), SG_Texte::getTexte($pValeur), $this -> texte);
		return $this;
	}

	/**
	 * Affiche le texte dans un arbre
	 * @since 1.2 ajout
	 * @version 2.6 SG_HTML
	 * @return SG_HTML
	 */
	function AfficherArbre() {
		// Identifiant unique du graphique
		$idGraphique = 'arbre_' . SG_SynerGaia::idRandom();

		$ret = '';
		$ret .= '<div id="' . $idGraphique . '" class="arbre"></div>' . PHP_EOL;
		$ret .= '<script>' . PHP_EOL;
		$ret .= ' var data_' . $idGraphique . ' = ' . $this -> texte . ';' . PHP_EOL;
		$ret .= ' afficherArbre("#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
		$ret .= '</script>' . PHP_EOL;
		return new SG_HTML($ret);
	}

	/**
	 * Extrait les n premiers caractère du texte
	 * 
	 * @since 1.2 ajout
	 * @version 2.3 mb_substr
	 * @param integer|SG_Nombre|SG_Formule $pLongueur
	 * @return SG_Texte
	 */
	function Debut($pLongueur = 1) {
		$longueur = new SG_Nombre($pLongueur);
		if (function_exists('mb_substr')) {
			$ret = new SG_Texte(mb_substr($this -> texte, 0, $longueur->toInteger(),'UTF-8'));
		} else {
			$ret = new SG_Texte(substr($this -> texte, 0, $longueur->toInteger()));
		}
		return $ret;
	}

	/**
	 * Extrait le texte d'un objet
	 * @since 1.3.0 ajout
	 * @version 2.6 traite les objets et les tableaux d'objets même composites
	 * @todo voir d'où vient le traitement de fichier ?
	 * @param any $pTexte l'objet dont il faut extraire le texte
	 * @return string
	 */
	static function getTexte($pTexte = '') {
		$txt = $pTexte;
		$typesg = getTypeSG($txt);
		if ($typesg === '@Formule') {
			$txt = $txt -> calculer();
			$typesg = getTypeSG($txt);
		}
		if ($txt === null) {
			$txt = 'NULL';
		} elseif (is_array($txt)) {
			$ok = false;
			$t = '';
			$sep = '';
			foreach($txt as $nom => $fic) {
				if (is_object($fic)) {
					$t.= self::getTexte($fic);
					$ok = true;
				} elseif (isset($fic['content_type']) and isset($fic['data'])) {// en fait c'est un fichier
					$fichier = new SG_Fichier($fic);
					$t = $fichier -> afficherChamp();
					$ok = true;
				}
			}
			$txt = $t;
			/**
			if($ok === false) {
				$ret = var_dump($ret); // pas fichier : on affiche tel quel
			}
			* */
		} elseif (is_object($txt)) {
			if (property_exists($txt, 'texte')) {
				$txt = $txt -> texte;
			} elseif ($typesg !== 'string') {
				$txt = $txt -> toString();
			}
		}
		$ret = $txt;
		return $ret;
	}

	/**
	 * Retourne la longueur brute du texte
	 * 
	 * @since 1.3.1 ajout
	 * @return SG_Nombre la longueur de @Texte->texte
	 */
	function Longueur() {
		$ret = new SG_Nombre(strlen($this -> texte));
		return $ret;
	}

	/**
	 * Jusqua : Extrait la partie du texte jusqu'à la balise indiquée.
	 * 
	 * @since 1.3.1 ajout
	 * @version 1.3.4 correction new SG_Texte
	 * @param string|SG_Texte $pBalise balise limite
	 * @param boolean|SG_VraiFaux $pIncluse indique si la balise doit être incluse dans la réponse (par défaut : false)
	 * @return SG_Texte le texte extrait 
	 */
	function Jusqua ($pBalise = '', $pIncluse = false) {
		$balise = SG_Texte::getTexte($pBalise);
		$incluse = SG_VraiFaux::getBooleen($pIncluse);
		$ret = $this;
		if($balise !== '') {
			$needle = strpos($this -> texte, $balise);
			if($needle === false) {
				$texte = '';
			} else {
				if($incluse) {
					$needle += strlen($balise);
				}
				$texte = substr($this -> texte, 0, $needle);
			}
			$ret = new SG_Texte($texte);
		}
		return $ret;
	}

	/**
	 * Depuis : Extrait la partie du texte à partir de la balise indiquée.
	 * 
	 * @since 1.3.1 ajout
	 * @version 2.4 paramètre $pSiPas
	 * @param SG_Texte $pBalise balise limite
	 * @param SG_VraiFaux $pIncluse indique si la balise doit être incluse dans la réponse (par défaut : false)
	 * @param SG_VraiFaux $pIdemSiPas : indique ce que l'on fait si pas trouvé la balise (par défaut on ne change rien)
	 * @return SG_Texte : le texte extrait 
	 */
	function Depuis ($pBalise = '', $pIncluse = false, $pIdemSiPas = true) {
		$balise = SG_Texte::getTexte($pBalise);
		$incluse = SG_VraiFaux::getBooleen($pIncluse);
		$idemsipas = SG_VraiFaux::getBooleen($pIdemSiPas);
		$ret = $this;
		if($balise !== '') {
			$needle = strpos($this -> texte, $balise);
			if($needle === false) {
				if ($idemsipas === true) {
					$texte = $this -> texte;
				} else {
					$texte = '';
				}
			} else {
				if(!$incluse) {
					$needle += strlen($balise);
				}
				$texte = substr($this -> texte, $needle);
			}
			$ret = SG_Rien::Nouveau(getTypeSG($this));
			$ret -> texte = $texte;
		}
		return $ret;
	}

	/**
	* Eclate le texte en collection de textes selon le séparateur fourni
	* 
	* @since 1.3.2 ajout
	* @version 2.3 initialise SG_Texte
	* @param SG_Texte $pSep séparateur (par défaut virgule)
	* @return SGCollection
	**/
	function Eclater($pSep = ',') {
		$sep = SG_Texte::getTexte($pSep);
		$ret = new SG_Collection();
		$func = function($txt) {return new SG_Texte($txt);};
		$ret -> elements = array_map($func, explode($sep, $this -> texte));
		return $ret;
	}

	/**
	 * transforme en SG_HTML.AGauche
	 * @since 1.3.3 ajout
	 * @return SG_HTML
	 */
	function AGauche() {
		$ret = new SG_HTML($this -> texte);
		$ret -> AGauche();
		return $ret;
	}

	/**
	 * transforme en SG_HTML.ADroite
	 * @since 1.3.3 ajout
	 * @return SG_HTML
	 */
	function ADroite() {
		$ret = new SG_HTML($this -> texte);
		$ret -> ADroite();
		return $ret;
	}

	/**
	 * Mettre en forme un lien Internet
	 * @since 2.0 ajout
	 * @param SG_Texte $pLien lien visé
	 * @param SG_Texte $pCible cadre cible visé
	 * @return SG_HTML balise <a> href
	 **/
	function LienVers($pLien = '', $pCible = '') {		
		$ret = new SG_HTML($this -> texte);
		$ret -> LienVers($pLien, $pCible);
		return $ret;
	}

	/** 
	 * Supprime les accents et signes diacritiques des caractères alphabétiques
	 * 
	 * @since 2.1 ajout
	 * @version 2.3 return $this
	 * @param null|SG_Texte $pTexte eventuellement texte à traduire
	 * @return SG_Texte $this
	 */
	function SansAccents($pTexte = null) {
		if ($pTexte  === null) {
			$texte = $this -> texte;
		} else {
			$texte = SG_Texte::getTexte($pTexte);
		}
		$orig = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ';
		$dest = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby';
		$this -> texte = strtr(strtolower(utf8_decode($texte)), utf8_decode($orig), $dest);
		return $this;
	}

	/**
	 * met l'initiale en majuscule
	 * @since 2.1 ajout
	 * @param null|SG_Texte $pTexte eventuellement texte à traduire
	 * @return SG_Texte $this
	 */
	function NomPropre($pTexte = null) {
		if ($pTexte  === null) {
			$this -> texte = ucwords($this -> Minuscules() -> texte);
			$ret = $this;
		} else {
			$ret = new SG_Texte($pTexte);
			$ret -> texte = ucwords($ret -> Minuscules() -> texte);
		}
		return $ret;
	}

	/**
	 * ce texte est-il inférieur ou égal au texte paramètre ?
	 * 
	 * @since 2.2 ajout
	 * @param SG_Texte $pTexte : texte à comparer
	 * @return SG_VraiFaux : la réponse
	 */
	function InferieurA ($pTexte = '') {
		$ret = new SG_VraiFaux(false);
		$texte = self::getTexte($pTexte);
		if ($this -> texte === '') {
			$ret = new SG_VraiFaux(true);
		} else {
			if ($this -> texte === $texte) {
				$ret = new SG_VraiFaux(true);
			} else {
				$ret = new SG_VraiFaux($this -> texte <= $texte);
			}
		}
		return $ret;
	}

	/**
	 * ce texte est-il supérieur ou égal au texte paramètre ?
	 * 
	 * @since 2.2 ajout
	 * @param SG_Texte $pTexte : texte à comparer
	 * @return SG_VraiFaux : la réponse
	 */
	function SuperieurA ($pTexte = '') {
		$ret = new SG_VraiFaux(false);
		$texte = self::getTexte($pTexte);
		if ($this -> texte === $texte) {
			$ret = new SG_VraiFaux(true);
		} else {
			if ($texte === '') {
				$ret = new SG_VraiFaux(true);
			} else {
				$ret = new SG_VraiFaux($texte <= $this -> texte);
			}
		}
		return $ret;
	}

	/**
	 * Enlève les blancs excédentaires (début fin et redoublés)
	 * 
	 * @since 2.3 ajout
	 * @return SG_Texte simplifié
	 */
	function Simplifier() {
		$ret = $this;
		$ret -> texte = str_replace('  ', ' ', trim($ret -> texte), $i);
		while($i > 0) {
			$ret -> texte = str_replace('  ', ' ', $ret -> texte, $i);
		}
		return $ret;
	}

	/**
	 * DepuisDernier : Extrait la partie du texte à partir de la dernière balise indiquée.
	 * 
	 * @since 2.3 ajout
	 * @param string|SG_Texte $pBalise balise limite
	 * @param boolean|SG_VraiFaux $pIncluse indique si la balise doit être incluse dans la réponse (par défaut : false)
	 * @return SG_Texte : le texte extrait 
	 */
	function DepuisDernier ($pBalise = '', $pIncluse = false) {
		$balise = SG_Texte::getTexte($pBalise);
		$incluse = SG_VraiFaux::getBooleen($pIncluse);
		$ret = $this;
		if($balise !== '') {
			$ipos = strrpos($this -> texte, $balise);
			if($ipos === false) {
				$texte = '';
			} else {
				if(!$incluse) {
					$ipos += strlen($balise);
				}
				$texte = substr($this -> texte, $ipos);
			}
			$ret = SG_Rien::Nouveau(getTypeSG($this));
			$ret -> texte = $texte;
		}
		return $ret;
	}

	/**
	 * JusquaDernier : Extrait la partie du texte jusqu'à la balise indiquée.
	 * 
	 * @since 2.3 ajout
	 * @param SG_Texte $pBalise : balise limite
	 * @param SG_VraiFaux $pIncluse : indique si la balise doit être incluse dans la réponse (par défaut : false)
	 * @return SG_Texte : le texte extrait 
	 */
	function JusquaDernier ($pBalise = '', $pIncluse = false) {
		$balise = SG_Texte::getTexte($pBalise);
		$incluse = SG_VraiFaux::getBooleen($pIncluse);
		$ret = $this;
		if($balise !== '') {
			$needle = strpos($this -> texte, $balise);
			if($needle === false) {
				$texte = '';
			} else {
				if($incluse) {
					$needle += strlen($balise);
				}
				$texte = substr($this -> texte, 0, $needle);
			}
			$ret = new SG_Texte($texte);
		}
		return $ret;
	}

	/**
	 * ce texte est-il entre les bornes paramètres (incluses) ?
	 * 
	 * @since 2.4 ajout
	 * @param SG_Texte $pDebut : texte inférieur ou égal à comparer
	 * @param SG_Texte $pFin : texte supérieur ou égal à comparer
	 * @return SG_VraiFaux : la réponse
	 */
	function Entre ($pDebut = '', $pFin = '') {
		$ret = new SG_VraiFaux(false);
		$debut = self::getTexte($pDebut);
		$fin = self::getTexte($pFin);
		if ($this -> texte === $debut or $this -> texte === $fin) {
			$ret = new SG_VraiFaux(true);
		} else {
			if ($debut === '') {
				$ret = new SG_VraiFaux(true);
			} else {
				$ret = new SG_VraiFaux($debut <= $this -> texte and $this -> texte <= $fin);
			}
		}
		return $ret;
	}

	/**
	 * Retourne ou met à jour le titre du texte (ou dérivés)
	 * 
	 * @since 2.6
	 * @param string|G_Texte|SG_Formule $pTitre formule ou valeur du titre
	 * @return string|SG_Texte valeur du titre ou l'objet lui-même si maj
	 */
	function Titre($pTitre = null){
		if($pTitre !== null) {
			$res = self::getTexte($pTitre);
			$this -> titre = $res;
			$ret = $this;
		} else {
			$ret = new SG_Texte($this -> titre);
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Texte_trait;	
}
?>
