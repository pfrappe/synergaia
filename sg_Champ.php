<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
 * Classe SynerGaia de traitement des champs des documents
 */
class SG_Champ extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Champ';
	public $typeSG = self::TYPESG;

	// Préfixe des noms HTML des champs
	const PREFIXE_HTML = 'sg_field_';

	// Référence du champ
	public $refChamp;

	// CodeBase associé
	public $codeBase;

	// CodeDocument associé
	public $codeDocument;

	// CodeChamp associé
	public $codeChamp;

	// Le champ est-il un lien
	public $typeLien = '';

	// Objet @Document associé dans lequel se trouve le champ. 
	public $document;

	// typeSG du document
	public $typeDoc;
	
	// Type d'objet du document associé
	public $typeObjet;
	// Modele (type SG) de la valeur du champ
	public $modele;

	// Valeur brute du champ
	public $valeur;

	// Contenu associé
	public $contenu;

	// Libellé associé
	public $libelle;

	// Champ multiple ?
	public $multiple = false;

	/** 2.1 
	* 1.1 : uuid ; 1.3.0 ; 1.3.2 correction si $pRefChamp en 1 seul morceau ; 2.0 parm3, ajout modele (= typeObjet), typeDoc
	* Construction de l'objet
	*
	* @param string $pRefChamp référence complète du champ (base/doc/champ) ou (doc/champ) ou (champ)
	* @param @indéfini $pObjet : objet @Document ou système : si fourni, on prend directement les informations de ce document (sans lecture de la base)
	* @param string $pModele : modele du champ (typeobjet.champ)
	*/
	public function __construct($pRefChamp = '', $pObjet = null, $pModele = '') {
		$refChamp = SG_Texte::getTexte($pRefChamp);
		$this -> refChamp = $refChamp;
		$this -> typeObjet = '@Texte';
		$this -> typeDoc = '@Document';
		$this -> modele = SG_Texte::getTexte($pModele);
		if ($refChamp === '') {
			if ($this -> modele === '') {
				$this -> modele = $this -> typeDoc . '.' . '@Texte';
			}
		} else {
			$tmpInfos = explode('/', $refChamp);
			$codeBase = '';
			$codeDocument = '';
			// Premier morceau si 3 : code de base
			if(sizeof($tmpInfos) >= 3) {
				$codeBase = $tmpInfos[0];
			}
			if(sizeof($tmpInfos) >= 2) {
				// Avant-dernier morceau si au moins 2 : code de document
				$codeDocument = $tmpInfos[sizeof($tmpInfos) - 2];
			}
			// Dernier morceau : code de champ
			$codeChamp = $tmpInfos[sizeof($tmpInfos) - 1];

			$this -> codeBase = $codeBase;
			$this -> codeDocument = $codeDocument;
			$this -> codeChamp = $codeChamp;

			// Si on a un @Champ(.Propriete)
			if (substr($codeChamp, 0, 7) === '@Champ(') {
				// Ne garde que le nom du champ (enleve le "@Champ(" et ")")
				$codeChamp = substr($codeChamp, 7, -1);
				// Si on avait un "." au début, on l'enleve aussi
				if (substr($codeChamp, 0, 1) === '.') {
					$codeChamp = substr($codeChamp, 1);
					$this -> codeChamp = $codeChamp;
				}
				// Renormalise la reference du champ
				$this -> refChamp = $this -> codeBase . '/' . $this -> codeDocument . '/' . $this -> codeChamp;
			}

			// Cherche le type d'objet du contenant associé
			if($pObjet !== null) {
				$this -> document = $pObjet;
			} else {
				$this -> document = $_SESSION['@SynerGaia']->getObjet($codeBase . '/' . $codeDocument);
			}
			// Cherche le modele (type) de contenu du champ, le libellé du champ et s'il peut être multiple
			$ccs = $this -> codeChampStrict();
			$this -> typeDoc = getTypeSG($this -> document);
			if ($this -> modele === '') {
				$this -> modele = $this -> typeDoc . '.' . $ccs;
			}
			$tof = SG_Dictionnaire::getObjetFonction($this -> modele); // remonte la hiérarchie des objets
			if (isset($tof['objet'])) {
				$this -> modele = $tof['objet'] . '.' . $ccs;
				$this -> typeLien = SG_Dictionnaire::isLien($this -> modele);
				$this -> libelle = SG_Dictionnaire::getLibelle($this -> modele);
				$this -> typeObjet = SG_Dictionnaire::getCodeModele($this -> modele);
				$this -> multiple = SG_Dictionnaire::isMultiple($this -> modele);
			} else {
				if (isset($this -> document -> proprietes[$ccs]['modele'])) {
					$this -> typeObjet = $this -> document -> proprietes[$ccs]['modele'];
				}
			}
			// Cherche la valeur du champ;
			$valeurChamp = $this -> document -> getValeur($codeChamp,'');
			$this -> valeur = $valeurChamp;
			// Crée le contenu
			// - Si on a un champ multiple
			if ($this -> multiple === true) {
				if (getTypeSG($this -> valeur) !== 'array') {
					if ($this -> valeur !== '') {
						$this -> valeur = array($this -> valeur);
					}
				}
				if (getTypeSG($this -> valeur) === 'array') {
					if (sizeof($this -> valeur) !== 0) {
						$this -> contenu = new SG_Collection();
						$this -> contenu -> UUId = $this -> refChamp;
						// Pour chaque élément
						$nbValeurs = sizeof($this -> valeur);
						for ($i = 0; $i < $nbValeurs; $i++) {
							$tmpElement = null;
							// Si on a un modèle SynerGaïa
							if (SG_Dictionnaire::isObjetSysteme($this -> typeObjet)) {
								// On le crée directement
								$codeObjet = SG_Dictionnaire::getClasseObjet($this -> typeObjet);
								$tmpElement = new $codeObjet($this -> valeur[$i]);
							} else {
								// Sinon on cherche l'objet associé
								if ($valeurChamp !== '') {
									$codeBase = SG_Dictionnaire::getCodeBase($this -> typeObjet);
									if ($codeBase !== '') {
										$tmpElement = new SG_Document($this -> valeur[$i]);
									} else {
										$tmpElement = new SG_Texte('');
									}
								} else {
									$tmpElement = new SG_Texte('');
								}
							}
							$tmpElement -> contenant = $this;
							$tmpElement -> UUId = $this -> contenu -> UUId . '.' . $i;
							$this -> contenu -> Ajouter($tmpElement);
						}
					}
				} else { // contenu vide
					$this -> initContenu();
				}
			} else {
				$this -> initContenu();
			}
		}
	}
	/** 1.1 ajout ; 1.3.4 test erreur ; 2.3 test contenu
	* initialise les propriétés 'contenu'
	* Si c'est un objet système SynerGaïa, on crée cet objet directement à partir de la classe SG_xxx
	* Sinon, on cherche la classe SynerGaïa support que l'on crée, et on ajoute une propriété @Type
	* Dans le cas d'un document, 
	*/
	function initContenu() {
		$this -> contenu = $this -> document -> getValeurPropriete($this -> codeChamp, $this -> valeur, $this -> typeObjet);
		if(! is_null($this -> contenu)) {
			if (! is_object($this -> contenu)) $this -> contenu = new stdClass();
			$this -> contenu -> contenant = $this;
			$this -> contenu -> UUId = $this -> refChamp;
		} else {
			$this -> contenu = new SG_Erreur('0096',$this -> codeChamp);
		}
	} 
	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		return $this -> contenu -> toString();
	}

	/**
	 * Conversion en code HTML
	 *
	 * @return string code HTML
	 */
	function toHTML() {
		return $this -> contenu -> toHTML();
	}
	// 1.1 ajout
	function titreChampHTML ($sep = '') {
		return '<span class="sg-titrechamp">' . $this -> libelle . $sep . ' </span>';
	}

	/** 
	 * Code le nom du champ pour passer en POST (permet de garder les . dans les noms de champs)
	 * @param string $pNomChamp nom du champ
	 * @return string nom du champ codé
	 */
	static function nomChampEncode($pNomChamp = '') {
		$hex = unpack('H*', $pNomChamp);
		$ret = array_shift($hex);
		return $ret;
	}

	/** 
	 * Décode le nom du champ pour passer en POST
	 * @param string $pNomChamp nom du champ
	 * @return string nom du champ décodé
	 */
	static function nomChampDecode($pNomChamp = '') {
		$ret = pack('H*', $pNomChamp);
		return $ret;
	}

	/** 1.0.7 ; 1.3.1 prise en compte de la traduction via les valeurs possibles ("texte en clair | code")
	 * Affichage du champ
	 *
	 * @return string contenu HTML affichable
	 */
	public function Afficher() {
		$ret = '';
		if (getTypeSG($this -> contenu) === '@Collection') {
			if ($this -> contenu -> Compter() -> toInteger() !== 0) {
				$ret .= $this -> contenu -> Lister(null, true, '') -> texte;
			}
		} else {
			$contenu = $this -> contenu;
			$formuleValeursPossibles = SG_Dictionnaire::getFormuleValeursPossibles($this -> typeDoc . '.' . $this -> codeChamp);
			if ($formuleValeursPossibles !== '') {
				// traduction éventuelle des valeurs brutes
				$tmpFormule = new SG_Formule($formuleValeursPossibles, $this -> document);
				$listeElements = $tmpFormule -> calculer();
				if (getTypeSG($listeElements) === '@Collection') {
					foreach($listeElements -> elements as $elt) {
						if (is_object($elt)) {
							$elt = $elt -> toString();
						}
						$element = explode('|', $elt);
						if(sizeof($element) > 1) {
							if($contenu -> texte === $element[1]) {
								$contenu -> texte = $element[0];
								break;
							}
						}
					}
				}
			}
			$texte = $contenu -> afficherChamp($this -> codeChamp);
			if (is_object($texte)) {
				$texte = $texte -> toString();
			}
			$ret .= $texte;
		}
		if ($ret !== '') {
			$ret = $this -> titreChampHTML(' : ') . $ret;
		}
		return $ret;
	}
	// 2.1 appel txtModifier
	function Modifier($formuleValeursPossibles = null) {
		return new SG_HTML($this -> txtModifier($formuleValeursPossibles));
	}

	/** 1.1 paramètre, natcasesort ; 1.3.1 correction sur calcul $i ; 2.0 affichage des valeurs sélectées ; 2.1 test erreur, => txtModifier
	* Modification du champ
	* @param (SG_Collection) formuleValeursPossibles
	* @return (string) contenu HTML affichable / modifiable
	*/
	function txtModifier($formuleValeursPossibles = null) {
		$ret = '';
		// Calcule le code du champ HTML
		$codeChampHTML = $this -> monCodeChampHTML();
		$idChamp = SG_Champ::idRandom();

		// Détermine la liste des valeurs proposées
		$listeElements = null;
		// on privilégie la formule de valeurs possibles
		if ($formuleValeursPossibles !== null) {
			$listeElements = $formuleValeursPossibles -> calculer();
		} else {
			$methode = $this -> codeChamp . '_possibles';
			if (substr($methode, 0, 1) === '@') {
				$methode = substr($methode, 1);
			}
			if (method_exists($this -> document, $methode)) {
				$listeElements = $this -> document -> $methode ($this -> document);
			} else {
				$nomVP = $this -> codeChamp . '_possibles';
				if (method_exists($this -> document, $nomVP)) {
					$listeElements = $this -> document -> $nomVP();
				} else {
					$formuleValeursPossibles = SG_Dictionnaire::getFormuleValeursPossibles($this -> typeDoc . '.' . $this -> codeChamp);
					if ($formuleValeursPossibles !== '') {
						$tmpFormule = new SG_Formule($formuleValeursPossibles, $this -> document);
						$listeElements = $tmpFormule -> calculer();
					} else {
						// sinon si c'est un lien et qu'on n'a pas une méthode spécifique, on récupère la liste des documents
						if ($this -> typeLien !== '' and ! method_exists($this -> contenu, 'modifierChamp')) {
							// code de la base du lien
							$codeBase = SG_Dictionnaire::getCodeBase($this -> typeObjet);
							// Calcule la liste des documents du modèle et trie le tableau
							$listeElements = $_SESSION['@SynerGaia']->getAllDocuments($this -> typeObjet) -> elements;
							natcasesort($listeElements);
						}
					}
				}
			}
		}

		// Si on a un champ multiple
		$js = ' onclick="SynerGaia.changeSelected(event, \'' . $idChamp . '\', \'f\');"';
		if ($this -> multiple === true) {
			if ($this -> typeObjet === '@Date') {
				$contenu = SG_Date::modifierChampMultiple($codeChampHTML, $listeElements);
			} elseif ($this -> typeObjet === '@Categorie') {
				$champ = new SG_Categorie();
				$champ -> multiple = true;
				$champ -> valeurs = $this -> valeur;
				$champ -> contenant = $this;
				$contenu = $champ -> modifierChamp($codeChampHTML, $listeElements);
			} else {
				if(getTypeSG($listeElements) === '@Collection') {
					$listeElements = $listeElements -> elements;
				}
				if(!is_array($listeElements)) {
					$listeElements = array();
				}
				$nbElements = sizeof($listeElements);

				// Genere la liste des documents proposés (selon taille de liste)
				if ($nbElements <= 25) {
					$listeSelection = '<fieldset class="sg-fieldset"><ul class="sg-checkbox" id="' . $idChamp . '_ul">' . PHP_EOL;
				} else {
					$listeSelection = '<fieldset class="sg-fieldset"><ul class="sg-checkbox-long" id="' . $idChamp . '_ul">' . PHP_EOL;
				}
				// Valeur actuelle du champ
				$valeurActuelle = $this -> valeur;
				if (getTypeSG($valeurActuelle) !== 'array') {
					$valeurActuelle = array($valeurActuelle);
				}

				// Ajoute un champ caché pour gérer le cas où aucune case n'est cochée
				$listeSelection .= '<li style="display: none"><input name="' . $codeChampHTML . '" value=""/></li>';

				// Met tout en liste déroulante
				$i = 0;
				$selected = array();
				foreach ($listeElements as $elt) {
					if(is_object($elt)) {
						$element = $elt -> toString();
						$element = explode('|', $element);
					} else {
						$element = explode('|', $elt);
					}
					$texte = $element[0];
					$refDocument = $element[sizeof($element) - 1];
					if ($this -> typeLien !== '') {
						$refDocument = $codeBase . '/' . $refDocument;
					}
					$idHTML = $codeChampHTML . '-' . $i;

					$listeSelection .= '<li><input type="checkbox" name="' . $codeChampHTML . '[]" id="' . $idHTML . '" value="' . $refDocument . '"' ;
					if (in_array($refDocument, $valeurActuelle)) {
						$listeSelection.= ' checked="checked"';
						$selected[] = $texte;
					}
					$listeSelection .= $js . '/><label for="' . $idHTML . '">' . $texte . '</label></li>' . PHP_EOL;
					$i++;
				}
				$listeSelection .= '</ul></fieldset>';
				$contenu = '<span id="' . $idChamp . '_val" class="selectedvalues">' . implode(', ', $selected) . '</span>';
				$contenu.= $listeSelection;
			}
		} else {
			// on a un champ à choix simple
			if (method_exists($this -> contenu, 'modifierChamp')) {// on a une méthode .ModifierChamp spécifique à l'objet
				$contenu = $this -> contenu -> modifierChamp($codeChampHTML, $listeElements, $this -> valeur);
			} else {
				// Si on n'a pas un champ de type lien
				if ($this -> typeLien === '') {
					$t = getTypeSG($this -> contenu);
					if($t === '@Erreur') {
						$contenu = $this -> contenu -> toHTML();
					} else {
						$contenu = SG_Libelle::getLibelle('0020');
					}
				} else {
					// Genere la liste des documents proposés
					$listeSelection = '<select class="champ_Lien" type="text" name="' . $codeChampHTML . '"> onclick="SynerGaia.changeSelected(event, \'' . $idChamp . '\', \'s\');"';

					// Propose le choix par défaut (vide)
					$listeSelection .= '<option value="">(aucun)</option>';

					// Valeur actuelle du champ
					$valeurActuelle = $this -> valeur;

					// Met tout en liste déroulante
					$nbElements = sizeof($listeElements);
					$selected = array();
					for ($i = 0; $i < $nbElements; $i++) {
						$element = explode('|', $listeElements[$i]);
						$texte = $element[0];
						$refDocument = $element[sizeof($element) - 1];
						$selected = '';
						$listeSelection .= '<option value="' . $codeBase . '/' . $refDocument . '"';
						if (SG_Champ::idIdentiques($refDocument, $valeurActuelle)) {
							$listeSelection.= ' selected="selected"';
							$selected[].= $texte;
						}
						$listeSelection .= '>' . $texte . '</option>';
					}
					$listeSelection .= '</select>';
					$contenu = '<span id="' . $idChamp . '_val" class="selectedvalues">' . implode(', ', $selected) . '"</span>"';
					$contenu.= $listeSelection;
				}
			}
		}
		// prépare le champ final
		$ret = $this -> titreChampHTML('') . '<div id="' . $idChamp .'">' . SG_Texte::getTexte($contenu) . '</div>';
		return $ret;
	}
	/** 1.0.7 ; 1.3.0 ; 2.2 test si setChamp ; return doc
	 * Modification de la valeur du champ
	 *
	 * @param indéfini $pValeur nouvelle valeur
	 * @param boolean $save forcer l'enregistrement du document
	 *
	 * @return : document sauf si erreur
	 */
	public function Definir($pValeur = '', $save = false) {
		$ret = $this -> document;
		// Si on a un mot de passe
		$classe = SG_Dictionnaire::getClasseObjet($this -> typeObjet);
		if (method_exists($classe, 'setChamp')) {
			$classe::setChamp($this -> document, $this -> codeChamp, $pValeur);
		} else {
			$r = $this -> document -> setValeur($this -> codeChamp, $pValeur);
			if (getTypeSG($r) === '@Erreur') {
				$ret = $r;
			}
		}
		if ($save) {
			$r = $this -> document -> Enregistrer();
			if (getTypeSG($r) === '@Erreur') {
				$ret = $r;
			}
		}
		return $ret;
	}

	/** 1.1 parm type ; 1.3.4 plus @Enregistrer (fait dans @Navigation.traitementParametres_HTTP_FILES)
	* Modification de la valeur du champ de type "Fichier"
	*
	* @param array $pFichier nouveau fichier (issu de $_FILES)
	* @return
	*/
	public function DefinirFichier($pFichier = '') {
		$ret = null;
		if ($pFichier !== '') {
			$fichier_Emplacement = $pFichier['tmp_name'];
			$fichier_Nom = $pFichier['name'];
			$fichier_Type = $pFichier['type'];
			$ret = $this -> document -> setFichier($this -> codeChamp, $fichier_Emplacement, $fichier_Nom, $fichier_Type);
		}
		return $ret;
	}

	/** 1.0.7
	* extractCodeDocument : récupère la partie code de la fin d'un lien document
	*/
	static function extractCodeDocument ($pUUID = '') {
		$ret = $pUUID;
		$i = strpos($pUUID, '/');
		if ($i !== false) {
			$ret = substr($pUUID, $i + 1);
		}
		return $ret;
	}
	/** 1.0.7
	 * idIdentiques : recherche si les liens pointent sur le même objet
	*/
	static function idIdentiques ($pUid1, $pUid2) {
		return SG_Champ::extractCodeDocument($pUid1) === SG_Champ::extractCodeDocument($pUid2);
	}
	/** 1.1
	*/
	static function codeChampHTML($refChamp) {
		return self::PREFIXE_HTML . self::nomChampEncode($refChamp);
	}
	/** 1.1
	 */
	function monCodeChampHTML() {
		return self::codeChampHTML($this -> refChamp);
	}
	/** 1.1 ajout
	*/
	function codeChampStrict() {
		$noms = explode('.', $this -> codeChamp);
		return $noms[sizeof($noms) - 1];
	}
	/** 1.1 ajout ; 1.3.1 estVide => isEmpty pour éviter conflit
	*/
	function isEmpty() {
		return $this -> contenu -> EstVide() -> estVrai();
	}
	/** 1.2 ajout
	*/
	function ValeurInterne() {
		$ret = new SG_Texte($this -> valeur);
		return $ret;
	}
	/** 1.3.2 ajout
	* donne un id aléatoire
	**/
	static function idRandom() {
		return substr(sha1(mt_rand()), 0, 8);
	}
}
?>
