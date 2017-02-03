<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de traitement des textes
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Texte_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Texte_trait.php';
} else {
	trait SG_Texte_trait{};
}
class SG_Texte extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Texte';
	public $typeSG = self::TYPESG;

	// Contenu texte de l'objet
	public $texte = '';

	/** 2.3 traite si parm est array
	* Construction de l'objet
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
							$this -> texte .= $val -> toString();
						}
					}
				} else {
					$this -> texte = '';
				}
		}
	}

	/**
	* Conversion en chaine de caractères (=getTexte mais non static)
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

	/** 2.1.1 SG_HTML
	* Conversion en code HTML
	*
	* @return string code HTML
	*/
	function toHTML() {
		return new SG_HTML($this -> texte);
	}

	/**
	 * Affichage
	 *
	 * @param string $pOption style d'affichage demandé
	 *
	 * @return string code HTML
	 */
	function Afficher($pOption = '') {
		return $this -> afficherChamp($pOption);
	}

	/** 1.3.1 déplace js vers TexteFormule 
	 * Affichage d'un champ
	 *
	 * @param string $pOption style d'affichage demandé
	 *
	 * @return string code HTML
	 */
	function afficherChamp($pOption = '') {
		$style = '';
		$class = 'champ_Texte';
		$ret = '';
		$option = '';

		// Lit l'option passée
		if ($pOption !== '') {
			$tmpOption = new SG_Texte($pOption);
			$option = $tmpOption -> texte;

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
	/** 1.2 : ajout ; 1.3.1 SG_HTML
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
	/** 2.1 ajout
	* @formula : .@Afficher("font-size:18pt;color:#A00;")
	**/
	function AfficherCommeTitreRouge() {
		return $this -> Afficher("font-size:18pt;color:#A00;");
	}
	/** 1.3.2 test is_object au lieu de isObjetExiste (gain de plusieurs secondes) ; 2.0 stoppropagation dblclick
	* Modification
	*
	* @param string $pRefChamp référence du champ HTML
	* @param SG_Collection $pValeursPossibles collection des valeurs proposées
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pValeursPossibles = null) {
		$ret = '';
		// Si on a passé une liste de valeurs proposées
		$valActuelle = $this -> toString();
		if (getTypeSG($pValeursPossibles) === '@Collection') {
			$ret = '<select class="champ_Texte" type="text" name="' . $pRefChamp . '">';

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
			$ret .= '<textarea class="champ_Texte" name="' . $pRefChamp . '" ondblclick="SynerGaia.stopPropagation(event);">' . $valActuelle . '</textarea>';
		}

		return $ret;
	}

	/** 1.0.7 ; 2.2 test exist mb_strtoupper
	* Conversion en chaine de majuscules
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

	/** 1.0.7 ; 2.2 test exist mb_strtoloower ; 2.3 correction minuscules > majuscules...
	 * Conversion en chaine de minuscules
	 *
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

	/** 1.0.4 ajout ; 2.0 fonctionne sur des tableaux et des collections ; 2.1.1 test $elt nul
	 * Comparaison à une liste de chaines
	 *
	 * @param indéfini $pElements éléments
	 *
	 * @return SG_VraiFaux vrai si la chaine est présente dans la liste
	 */
	function EstParmi($pElements = null) {
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
	/** (1.3.1) garde le type de $this ; 2.3 get_class
	 * Concaténation à une autre chaine
	 * @param indéfini suite des textes à concaténer
	 * @return (SG_Texte) concaténation des chaines
	 */
	function Concatener() {
		$modele = get_class($this);
		$ret = new $modele($this -> texte);
		$args = func_get_args();
		foreach ($args as $arg) {
			$ret -> texte .= SG_Texte::getTexte($arg);
		}
		return $ret;
	}
	
	/** 1.0.4 ajout ; 1.2 modification 2nd paramètre
	 * Contient permet de rechercher si une chaine de caractère appartient ou non au texte. 
	 * Cette recherche ne tient compte ni de la casse ni des accents.
	 * 
	 * @param string la chaine à rechercher
	 * @param string délimiteur du mot
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
	/** 1.1 : AJout
	 * transforme en nombre un texte numérique
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
	/** 1.1 ; 2.3 suppression
	function Tracer($pMsg = '') {
		$msg = SG_Texte::getTexte($pMsg);
		echo '<b>' . $msg . ' : (' . $this -> typeSG . ') </b>' . $this;
		return $this;
	}
	*/
	// normalise le nom en enlevant les caractères hors nomes et en compactant (restent lettres chiffres et _
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
	/** 1.2 ajout
	* @param any $pDebut
	* @return @VraiFaux : si $pDebut = '' : indéfini, sinon selon la comparaison
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
	/** 1.2 ajout
	*/
	function Remplacer($pChaine = '', $pValeur = '') {
		$this -> texte = str_replace(SG_Texte::getTexte($pChaine), SG_Texte::getTexte($pValeur), $this -> texte);
		return $this;
	}
	/** 1.2 ajout ; 1.3.4 idRandom
	*/
	function AfficherArbre() {
		// Identifiant unique du graphique
		$idGraphique = 'arbre_' . SG_Champ::idRandom();

		$ret = '';
		$ret .= '<div id="' . $idGraphique . '" class="arbre"></div>' . PHP_EOL;
		$ret .= '<script>' . PHP_EOL;
		$ret .= ' var data_' . $idGraphique . ' = ' . $this -> texte . ';' . PHP_EOL;
		$ret .= ' afficherArbre("#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
		$ret .= '</script>' . PHP_EOL;
		return $ret;
	}
	// 1.2 ajout ; 2.3 mb_substr
	function Debut($pLongueur = 1) {
		$longueur = new SG_Nombre($pLongueur);
		if (function_exists('mb_substr')) {
			$ret = new SG_Texte(mb_substr($this -> texte, 0, $longueur->toInteger(),'UTF-8'));
		} else {
			$ret = new SG_Texte(substr($this -> texte, 0, $longueur->toInteger()));
		}
		return $ret;
	}
	//1.2 ajout
	function ContientMotCle ($pMotCle = '') {
		
	}
	//1.3.0 ajout ; 1.3.4 cas fichier et json_encode
	static function getTexte($pTexte = '') {
		$ret = $pTexte;
		$typesg = getTypeSG($ret);
		if ($typesg === '@Formule') {
			$ret = $ret -> calculer();
			$typesg = getTypeSG($ret);
		}
		if ($ret === null) {
			$ret = 'NULL';
		} elseif (is_string($ret)) {
			$ret = $ret;
		} elseif (is_array($ret)) {
			try {
				$ret = (string) implode(',', $ret);
			} catch (Exception $e) {
				$ok = false;
				foreach($ret as $nom => $fic) {
					if (is_object($fic)) {
						$ret = SG_Texte::getTexte($fic);
						$ok = true;
					}elseif (isset($fic['content_type']) and isset($fic['data'])) {// en fait c'est un fichier
						$fichier = new SG_Fichier($fic);
						$ret = $fichier -> afficherChamp();
						$ok = true;
					}
				}
				if($ok === false) {
					$ret = var_dump($ret); // pas fichier : on affiche tel quel
				}
			}
		} elseif (is_object($ret)) {
			if (property_exists($ret, 'texte')) {
				$ret = $ret -> texte;
			} elseif ($typesg !== 'string') {
				$ret = $ret -> toString();
			}
		} else {
			
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* Retourne la longueur brute du texte
	* @return (@Nombre) la longueur de @Texte->texte
	**/
	function Longueur() {
		$ret = new SG_Nombre(strlen($this -> texte));
		return $ret;
	}
	/** 1.3.1 ajout ; 1.3.4 correction new SG_Texte
	* Jusqua : Extrait la partie du texte jusqu'à la balise indiquée.
	* @param (@Texte) : balise limite
	* @param (@VraiFaux) : indique si la balise doit être incluse dans la réponse (par défaut : false)
	* @return (@Texte) : le texte extrait 
	**/
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
	/** 1.3.1 ajout
	* Depuis : Extrait la partie du texte à partir de la balise indiquée.
	* @param (@Texte) : balise limite
	* @param (@VraiFaux) : indique si la balise doit être incluse dans la réponse (par défaut : false)
	* @return (@Texte) : le texte extrait 
	**/
	function Depuis ($pBalise = '', $pIncluse = false) {
		$balise = SG_Texte::getTexte($pBalise);
		$incluse = SG_VraiFaux::getBooleen($pIncluse);
		$ret = $this;
		if($balise !== '') {
			$needle = strpos($this -> texte, $balise);
			if($needle === false) {
				$texte = '';
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
	/** 1.3.2 ajout ; 2.0 correction ; 2.3 initialise SG_Texte
	* Eclate le texte en collection de textes selon le séparateur fourni
	* @param (@Texte) $pSep : séparateur (par défaut virgule)
	* @return (@Collection)
	**/
	function Eclater($pSep = ',') {
		$sep = SG_Texte::getTexte($pSep);
		$ret = new SG_Collection();
		$func = function($txt) {return new SG_Texte($txt);};
		$ret -> elements = array_map($func, explode($sep, $this -> texte));
		return $ret;
	}
	/** 1.3.3 ajout
	* devient @HTML.AGauche
	**/
	function AGauche() {
		$ret = new SG_HTML($this -> texte);
		$ret -> AGauche();
		return $ret;
	}
	/** 1.3.3 ajout
	* devient @HTML.ADroite
	**/
	function ADroite() {
		$ret = new SG_HTML($this -> texte);
		$ret -> ADroite();
		return $ret;
	}
	/* 2.0 ajout
	* Mettre en forme un lien Internet
	* @param (SG_Texte) lien visé
	* @return (SG_HTML) balise <a> href
	**/
	function LienVers($pLien = '', $pCible = '') {		
		$ret = new SG_HTML($this -> texte);
		$ret -> LienVers($pLien, $pCible);
		return $ret;
	}
	/** 2.1 ajout ; 2.3 return $this
	* Supprime les accents et signes diacritiques des caractères alphabétiques
	**/
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
	/** 2.1 ajout
	* met l'initiale en majuscule
	**/
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
	/** 2.2 ajout
	* ce texte est-il inférieur ou égal au texte paramètre ?
	* @param (@Texte) $pTexte : texte à comparer
	* @return @VraiFaux : la réponse
	**/
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
	/** 2.2 ajout
	* ce texte est-il supérieur ou égal au texte paramètre ?
	* @param (@Texte) $pTexte : texte à comparer
	* @return @VraiFaux : la réponse
	**/
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
	/** 2.3 ajout
	* Enlève les blancs excédentaires (début fin et redoublés)
	* @return : @Texte simplifié
	**/
	function Simplifier() {
		$ret = $this;
		$ret -> texte = str_replace('  ', ' ', trim($ret -> texte), $i);
		while($i > 0) {
			$ret -> texte = str_replace('  ', ' ', $ret -> texte, $i);
		}
		return $ret;
	}
	/** 2.3 ajout
	* DepuisDernier : Extrait la partie du texte à partir de la dernière balise indiquée.
	* @param (@Texte) : balise limite
	* @param (@VraiFaux) : indique si la balise doit être incluse dans la réponse (par défaut : false)
	* @return (@Texte) : le texte extrait 
	**/
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
	/** 2.3 ajout
	* Jusqua : Extrait la partie du texte jusqu'à la balise indiquée.
	* @param (@Texte) : balise limite
	* @param (@VraiFaux) : indique si la balise doit être incluse dans la réponse (par défaut : false)
	* @return (@Texte) : le texte extrait 
	**/
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
	// 2.1.1. complément de classe créée par compilation
	use SG_Texte_trait;	
}
?>
