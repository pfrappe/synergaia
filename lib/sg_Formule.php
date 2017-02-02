<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Formule : Classe de gestion et d'exécution d'une formule SynerGaia
*/
class SG_Formule extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Formule';
	public $typeSG = self::TYPESG;

	// Texte de la formule SynerGaia
	public $formule = '';
	// 2.1 traduction php
	public $php = '';
	// 2.1 titre du résultat de la formule (pour les affichages)
	public $titre = null;
	// 2.1 méthode du dernier résultat
	public $methode = '';
	// 2.2 fonction de l'opération à exécuter
	public $fonction = '';
	// 2.3 contexte de la formule (tableau des paramètres de la formule de départ)
	public $contexte = array();

	// Objet sur lequel appliquer la formule
	public $objet;
	// Objet "principal", lié à une formule parente
	public $objetPrincipal;
	// @Formule complète appelante de plus haut niveau pour la gestion des variables temporaires
	public $formuleparent;
	// Identifiant unique de la formule (2.1 supprimé)
	//public $id = '';
	public $operation = null;

	// Liste des variables (seulement si je suis la formule apelante)
	public $variables = array();
	// Liste des erreurs relevées (inutilisé)
	public $erreurs = array();
	// 1.0.7 permet d'activer un debug pour une seule formule
	public $debug = false;

	/** 1.0.7 ; 2.1 suppr id , setParent
	* __construct : Construction de l'objet
	*
	* @param string $pFormule texte de la formule SynerGaia
	* @param indéfini $pObjet objet sur lequel appliquer la formule
	* @param indéfini $pObjetPrincipal objet sur lequel appliquer la formule
	* @param indéfini $pParent formule (ou opération) portant le texte de la formule d'origine et où sont stockées les variables
	* @param indéfini $pParametres tableau des id des valeurs de paramètres complémentaires
	*/
	function __construct($pFormule = null, $pObjet = null, $pObjetPrincipal = null, $pParent = null, $pParametres = null) {
		$this -> setFormule($pFormule);
		
		if(is_array($pObjet)) {
			$this -> proprietes = $pObjet;
		} else {
			$this -> objet = $pObjet;
		}					

		if ($pObjetPrincipal !== null) {
			$this -> objetPrincipal = $pObjetPrincipal;
		} else {
			$this -> objetPrincipal = $this -> objet;
		}
		
		if ($pParametres != null) {
			// s'il y a des paramètres, c'est qu'on est une formule parente de plus haut niveau.
			foreach($pParametres as $i => $parametre) {
				if (getTypeSG($parametre) === '@Formule') {
					$this -> proprietes['$' . ($i + 1)] = $parametre -> calculer();
				} else {
					$this -> proprietes['$' . ($i + 1)] = $parametre;
				}
			}
		}
		$this -> setParent($pParent);
	}
	/** 1.0.7
	 * initialise correctement la formule en calculant les blocs
	 */
	function setFormule($pFormule) {
		$tmpTypeSG = getTypeSG($pFormule);
		switch ($tmpTypeSG) {
			case '@Formule' :
				$tmpTexte = new SG_Texte($pFormule -> calculer());
				$this -> formule = $tmpTexte -> texte;
				break;
			case 'string' :
				$this -> formule = $pFormule;
				break;
			default :
				$texte = new SG_Texte($pFormule);
				$this -> formule = $texte -> toString();
		}
		// Détermine les étapes et blocs composants la formule
		$this -> blocs = $this -> Blocs($this -> formule);
	}
	/* 1.0.7
	 * toString : la formule en texte
	 */
	public function toString() {
		return $this -> formule;
	}

	/** 0.1 ajout; 1.3.1 boucle suppr parenthèses englobantes ; 1.3.2 coorection pour '}'
	 * Eclate la formule par un caratère de séparation (ex : ";", ">", ...)
	 *
	 * @param string $pTexte texte de la formule
	 * @param string $pSeparateur caractère séparateur
	 *
	 * @return array Liste des sections
	 */
	static function eclater($pTexte = '', $pSeparateur = ';') {
		$formule = $pTexte;
		$separateur = $pSeparateur;

		$longueurFormule = strlen($formule);
		$longueurSeparateur = strlen($separateur);

		$sections = array();
		$positions = array();
		$positions[0] = -$longueurSeparateur;

		$pile = '';
		$typechaine = '';
		for ($i = 0; $i < $longueurFormule; $i++) {
			$caractere = substr($formule, $i, $longueurSeparateur);
			// test sur fermeture de chaine
			if($caractere === $typechaine) {
				$typechaine = '';
			} elseif ($typechaine === '') {// on n'est pas dans un chaine
				// on ouvre une chaine ?
				if ($caractere === '"') {
					$typechaine = '"';
				} elseif ($caractere === '{') {
					$typechaine = '}';
				} else {
					// on est dans une partie de chaine standard
					// on trouve un separateur et la pile est vide
					if ($pile === '' and $caractere === $separateur) {
						array_push($positions, $i); // on garde la position
					} else {
						$finpile = substr($pile, -1);
						if ($caractere === $finpile) {
							$pile = substr($pile, 0, -1); // depiler
						} else {
							// empiler un caractere spécial			
							switch($caractere) {
								case '(' : 
									$pile = $pile . ')';
									break;
								case '[' : 
									$pile = $pile . ']';
									break;
							}
						}
					}
				}
			}
		}

		$nbPositions = count($positions);
		for ($i = 0; $i < $nbPositions; $i++) {
			$positionDebut = $positions[$i] + $longueurSeparateur;
			if ($i === (count($positions) - 1)) {
				$positionFin = strlen($formule);
			} else {
				$positionFin = $positions[$i + 1];
			}

			$longueurSection = $positionFin - $positionDebut;
			$section = trim(substr($formule, $positionDebut, $longueurSection));

			// Supprime les parenthèses englobantes, si besoin ex : '(a>b)' '[a>b]'
			while ( (substr($section, 0, 1) === '(' and substr($section, -1) === ')')
			or (substr($section, 0, 1) === '[' and substr($section, -1) === ']')) {
					// TODO vérifier qu'on a pas une formule du type '(a)>(b)'
					$section = substr($section, 1, -1);
			}

			if ($longueurSection !== 0)
				array_push($sections, $section);
		}
		return $sections;
	}

	/**
	* Eclate la formule en blocs normalisés
	*
	* @param string $pPhrase phrase SynerGaia à analyse
	* @param string $pPrefixe prefixe des blocs (pour récursivité)
	*
	* @return array liste des blocs : array(code => contenu)
	*/
	static function Blocs($pPhrase = '', $pPrefixe = '') {
		$phrase = $pPhrase;
		$prefixe = $pPrefixe;

		$retBlocs = array();

		// Cherche les étapes de la phrase séparées par '>'
		$etapes = self::Eclater($pPhrase, '>');
		// Pour chaque étape, cherche les blocs séparés par ';'
		$nbEtapes = sizeof($etapes);
		for ($numEtape = 0; $numEtape < $nbEtapes; $numEtape++) {
			$etape = $etapes[$numEtape];
			$blocs = self::Eclater($etape, ';');

			// Pour chaque bloc trouvé
			$nbBlocs = sizeof($blocs);
			for ($numBloc = 0; $numBloc < $nbBlocs; $numBloc++) {
				$bloc = $blocs[$numBloc];

				// Doit-on poursuivre les éclatements ?
				$tmpEtapes = self::Eclater($bloc, '>');
				if (sizeof($tmpEtapes) === 1) {
					// Le bloc ne contient pas de "sous-étape"
					if ($pPrefixe === '') {
						if ($prefixe !== '') {
							$codeBloc = $prefixe . '-' . ($numBloc + 1);
						} else {
							$codeBloc = '' . ($numBloc + 1);
						}
					} else {
						// supprime le dernier morceau du prefixe
						if (strpos($prefixe, '-') !== false) {
							$tmpPrefixe = substr($prefixe, 0, -2) . '-' . ($numBloc + 1);
						} else {
							$tmpPrefixe = $prefixe;
						}
						$codeBloc = $tmpPrefixe;
					}
					$bloc = trim($bloc);
					$retBlocs[$codeBloc] = $bloc;
				} else {
					// Le bloc contient d'autres étapes
					$prefixeBloc = '' . ($numBloc + 1);
					$listeBlocsSuivants = self::Blocs($bloc, $prefixeBloc);

					foreach ($listeBlocsSuivants as $codeBlocSuivant => $contenuBlocSuivant) {
						$contenuBlocSuivant = trim($contenuBlocSuivant);

						if ($prefixe !== '') {
							$codeBloc = $prefixe . '-' . $codeBlocSuivant;
						} else {
							$codeBloc = '' . $codeBlocSuivant;
						}

						$retBlocs[$codeBloc] = $contenuBlocSuivant;
					}
				}
			}

			if ($prefixe === '') {
				// Etape initiale
				$prefixe = '1';
			} else {
				// Etape suivante
				$prefixe = $prefixe . '-1';
			}
		}
		return $retBlocs;
	}
	/** 1.1 ; 1.3.1 utilisation de separerCollection, test sur nom de propriété affectée ; 2.3 tient compte de fonction et contexte ; test @erreur, err 0198
	* Calcule / Interprète une formule complete
	* @return (any) résultat du calcul ou SG_Erreur
	*/
	function calculer($pEtape = '') {
		if ($this -> fonction !== '') {
			// 2.2 à partir de fonction
			if (! method_exists($this -> operation, $this -> fonction)) {
				$ret = new SG_Erreur('0198', get_class($this -> operation) . '.' . $this -> fonction);
			} else {
				$ret = call_user_func_array(array($this -> operation, $this -> fonction) , array($this -> objet, $this -> contexte));
			}
		} elseif ($this -> php !== '') {
			// 2.1 calcul à partir de php
			$ret = false;
			$objet = $this -> objet;
			if (is_string($objet)) {
				$objet = new SG_Texte($objet);
			}
			$rien = new SG_Rien();
			$operation = $this -> operation;
			$etape = $pEtape;
			$resultat = array();
			try {
				eval($this -> php);
			} catch (Exception $e) {
				$ret = new SG_Erreur($e -> getMessage() . ' (ligne ' . $e -> getLine() . ')');
			}
		} else { // faire la traduction à la volée
			$nom = sha1($this -> formule);
			$classe = 'FO_' . $nom;
			$ok = true;
			if (SG_Autoloader::verifier($classe) === false) {
				$ok = false;
				$compil = new SG_Compilateur();
				$ret = $compil -> Traduire($this -> formule);
				if (getTypeSG($ret) !== '@Erreur') {
					$ret = $compil -> compilerOperation($nom, $this -> formule, $compil -> php, 'FO_');
					if ($ret !== false and getTypeSG($ret) !== '@Erreur' ) {
						$ok = true;
					}
				}
			}
			if ($ok === true ) {
				$formule = new $classe();
				$formule -> php = 'oui';
				$formule -> objet = $this -> objet;
				$ret = $formule -> traiterSpecifique($pEtape, 'f');
			}
		}
		return $ret;
	}

	/**1.3.1 supp @ devant call_user_func_array ; getObjetFonction ; 1.3.2 err 0077, $_SESSION['formule'] même si erreur
	*  1.1 traitement des erreurs et amélioration performance ($_SESSION) ; 1.3.0 correctif pour SG_Document natif ; 
	* Calcule / Interprète une portion de formule
	* @param string $pFonctionComplete fonction complete avec parametres
	* @param indéfini $pObjet objet sur lequel appliquer la fonction
	* @return indéfini résultat de la fonction sur l'objet
	*/
	function calculerFonction($pFonctionComplete, $pObjet) {
		$ret = null;
		// Sépare la fonction de ses parametres
		$fonctionEclatee = self::separerParametres($pFonctionComplete);
		$fonction = $fonctionEclatee['fonction'];
		$parametres = $fonctionEclatee['parametres'];
		$formuleEval_Type = 'val'; // 'val' ou 'méthode' ou 'champ' ou 'doc' ou 'erreur'
		$formuleEval_Code = ''; // code fonction php
		$tmpObjet = $pObjet;
		$typeObjet = getTypeSG($tmpObjet);
		$typeObjetInitial = $typeObjet;
		// (1.0.4) Cherche s'il ne s'agit pas d'une propriété locale construite à la volée
		if (isset($tmpObjet -> proprietes[$fonction])) {
			$ret = $tmpObjet -> proprietes[$fonction];
		} elseif (substr($fonction, 0, 1) === '"') { // chaine de caractères "..."?							
			$fonctionTexte = substr($fonction, 1, -1);
			$ret = new SG_Texte($fonctionTexte);
		} elseif (substr($fonction, 0, 1) === '{') { // chaine de caractères {...}?							
			$fonctionTexte = substr($fonction, 1, -1);
			$ret = new SG_Texte($fonctionTexte);
		} elseif (substr($fonction, 0, 1) === '$') { // un paramètre de la méthode ?
			if($this -> isProprieteExiste($fonction)) {
				$ret = $this -> getValeur($fonction);
			} else {
				$ret = new SG_Erreur('paramètre non initialisé');
			}
		} elseif (is_numeric($fonction)) {// nombre ?
				$ret = new SG_Nombre($fonction);
		} else {
			$dt = new SG_DateHeure($fonction);
			if (isset($dt -> _instant )) {// une date ?
				$formuleEval_Type ='val';
				$ret = $dt;
			} else {
				// on regarde s'il s'agit d'une variable (le type d'objet intermédiaire est alors SG_Rien)
				if ($typeObjet === '@Rien' and $this -> isProprieteExiste($fonction)) {
					$ret = $this -> getValeur($fonction);
				} else { // c'est donc un objet fondamental ou méthode ou propriété (ou erreur)
					// Si on a un @Document c'est qu'on a un objet défini par l'utilisateur (càd pas une classe PHP)
					$typeObjetUtilisateur = '';
					if ($typeObjet === '@Document') {
						$typeObjetUtilisateur = $tmpObjet -> getValeur('@Type', '');
						if ($typeObjetUtilisateur !== '') { // 1.3
							$typeObjet = $typeObjetUtilisateur;
							$typeObjetInitial = $typeObjetUtilisateur;
						}
					}
					// on regarde si on a déjà recherché cette fonction
					if(isset($_SESSION['formule'][$typeObjetInitial.'.'.$fonction])) {
						$deja = $_SESSION['formule'][$typeObjetInitial.'.'.$fonction];
						$formuleEval_Type = $deja['type'];
						$formuleEval_Code = $deja['code'];
						if (isset($deja['val'])) {
							$ret = $deja['val'];
						}
					} else {
						$res = SG_Dictionnaire::getObjetFonction($typeObjet, $fonction);
						$formuleEval_Type = $res['type'];
						$formuleEval_Code = $res['code'];
						$typeObjet = $res['objet'];

						// Si type @Rien alors on n'a rien trouvé comme méthode ou propriété => objet fondamental ou @Erreur
						if ($typeObjet === '') {
							// Si la fonction est un objet fondamental non @Document accessible par un New sur une classe programmée
							if (SG_Dictionnaire::isObjetSysteme($fonction) and !SG_Dictionnaire::isObjetDocument($fonction)) {
								$codeFonction = SG_Dictionnaire::getClasseObjet($fonction);
								$formuleEval_Type = 'new';
								$formuleEval_Code = $codeFonction;
							} else {
								if ($typeObjetInitial === '@Rien') {
									if (SG_Dictionnaire::isObjetDocument($fonction)) {
										$formuleEval_Type = 'doc';
										$formuleEval_Code = $fonction;
									} else {
										// On n'a trouvé ni méthode, ni propriété, ni classe PHP : essayer un @Chercher("$fonction", paramètres fonction)
										$ret = new SG_Erreur('0009', $typeObjetInitial.'.'.$fonction);
										$formuleEval_Type = 'erreur';
										$formuleEval_Code = $fonction;
									}
								} else {
									//on tente d'en faire une valeur immédiate
									$formuleEval_Type ='val';
									if (is_object($tmpObjet)) {
										if(method_exists($tmpObjet, 'getValeur')) {
											$ret = new SG_Texte($tmpObjet -> getValeur($fonction));
										} else {
											$ret = new SG_Erreur('0077', getTypeSG($tmpObjet) . '->getValeur()');
										}
									} elseif(is_null($tmpObjet) or $tmpObjet === '') {
										$ret = '(vide)';
									} else {
										$formuleEval_Type ='erreur';
										$ret = new SG_Erreur('0031', getTypeSG($tmpObjet) . ':' . $fonction);
									}
								}
							}
						}
					//	if ($formuleEval_Type !== 'erreur' and $formuleEval_Type !== 'prov') { 1.3.2
						if ($formuleEval_Type !== 'prov') {
							$_SESSION['formule'][$typeObjetInitial.'.'.$fonction]['type'] = $formuleEval_Type;
							$_SESSION['formule'][$typeObjetInitial.'.'.$fonction]['code'] = $formuleEval_Code;
						}
					}
				}
			}
		}
		// traite les parametres
		$formuleEval_Parametres = array();
		foreach ($parametres as $parametre) {
				$formuleEval_Parametres[] = new SG_Formule($parametre, $this -> objet, null, $this);
		}
		if ($formuleEval_Type !== 'val' and $formuleEval_Type !== 'erreur' and $formuleEval_Type !== 'prov') { // vide = valeur immédiate
			if ($formuleEval_Type === 'champ') {// La propriété existe : on récupère directement sa valeur avec le bon type
				$ret = $tmpObjet -> getValeurPropriete($fonction);
			} elseif ($formuleEval_Type === 'new') {
				$tmpObjet = new ReflectionClass($formuleEval_Code);
				$ret = $tmpObjet -> newInstanceArgs($formuleEval_Parametres);
			} elseif ($formuleEval_Type === 'methode') {
				if(method_exists($tmpObjet, $formuleEval_Code)) {
					$ret = call_user_func_array(array($tmpObjet, $formuleEval_Code), $formuleEval_Parametres);
				} else {
					$ret = new SG_Erreur('0056',getTypeSG($tmpObjet) . '.' . $formuleEval_Code);
				}
			} elseif ($formuleEval_Type === 'action') {
				$ret = SG_Formule::executer($formuleEval_Code, $tmpObjet, $this -> objetPrincipal, null, $formuleEval_Parametres);
			} elseif ($formuleEval_Type === 'doc') {
				$tmpFormule = new SG_Texte($formuleEval_Code);
				if (sizeof($formuleEval_Parametres) >= 1) {
					$ret = SG_Rien::Chercher($tmpFormule, $formuleEval_Parametres[0]);
				} elseif (sizeof($formuleEval_Parametres) === 1) {
					$ret = SG_Rien::Chercher($tmpFormule);
				} else {
					$ret = SG_Rien::creerObjet($formuleEval_Code);
				}
				if (getTypeSG($ret) === '@Collection') {
					if (sizeof($ret -> elements) === 1) {
						$ret = $ret -> elements[0];
					} elseif (sizeof($ret -> elements) === 0) {
						$ret = new SG_Rien();
					}
				}
				/** else {
					$ret = new SG_Erreur('0007', $tmpFormule . ' ' . $formuleEval_Parametres[0]);
				}*/
			} else {
				$ret = new SG_Erreur('0008', $formuleEval_Type);
			}
		}
		if (is_object($ret) and $ret -> estErreur()) {
			$formule = $this -> getFormuleOrigine();
			if (! isset($formule -> erreurs[$ret -> code])) {
				$formule -> erreurs[$ret -> code] = $ret;
			}
		}
		return $ret;
	}

	/** 1.3.1 complètement réécrit pour permettre {...}
	* Sépare la fonction de ses parametres
	* @param string $pfonctionComplete fonction complète ex : @Test(123,'ABC')	 *
	* @return array tableau ['fonction' => fonction , 'parametres' => ['123,'ABC']  ]
	*/
	static function separerParametres($pfonctionComplete) {
		$pile = '';
		$param = '';
		$parametres = array();
		$fonction = $pfonctionComplete;
		$partieparam = false;
		for ($i = 0;$i < strlen($pfonctionComplete) ; $i++){
			$c = $pfonctionComplete[$i];
			$finpile = substr($pile, -1);
			if ($pile === '') { // tout début avant les paramètres
				if($c === '(') {
					$pile = ')'; // ouverture ( générale
					$fonction = trim(substr($pfonctionComplete, 0, $i));
				} elseif ($c === '"') { //on ouvre une chaine de caractère
					$pile = '"';
				} elseif ($c === '{') {
					$pile = '}';
				}
			} elseif ($c === ')' and $pile === ')') {
				$parametres[] = $param; // dernier paramètre et fermeture générale
				break;
			/** à partir d'ici, on est dans la parenthèse générale des paramètres
			} elseif ($c !== $finpile) {
				if ($finpile === '"' or $finpile === '}') { // on est dans une chaine de caractères
					$param .= $c; // on empile les caractères y compris les , et les ( ) si dans une chaine de caractère ".." ou {..} 
					$pile = substr($pile, 0, -1); // dépiler une chaine x..x
				} else { // on garde le caractère et on continue la boucle
				}
			*/
			} elseif ($c === ',' and $pile === ')') {
				$parametres[] = $param; // fin d'un parametre
				$param = '';
			} else {
				$param .= $c; // on empile les caractères y compris les , et les ( ) si dans une chaine de caractère ".." ou {..} 
				if ($c === $finpile) { // on va fermer une partie enclose (..)
					$pile = substr($pile, 0, -1); // dépiler une chaine x..x
				} elseif ($finpile === '"' or $finpile === '}') { 
					// on est dans une chaine de caractères ".." ou {..} 
					// donc on empile les caractères du paramètre tels quels y compris () [] {} ou "" 
				} else {
					// voir si on ouvre une nouvelle parenthese pour empiler le caractère de fin
					switch ($c) {
						case '"' :
							$pile .= '"';
							break;
						case '(' :
							$pile .= ')';
							break;
						case '{' :
							$pile .= '}';
							break;
						case '[' :
							$pile .= ']';
							break;
						default :
							break;
					}
				}
			}
		}

		$ret = array('fonction' => $fonction, 'parametres' => $parametres);
		return $ret;
	}
	/** 1.3.1 ajouté pour remplacer eclater(x, ',') qui pourrait ne pas fonctionner dans des structures de phrases complexes
	* Sépare la phrase en morceaux de fonction séparés par des virgules
	* @param string $pfonctionComplete fonction complète ex : @Test(123,"ABC,etc"), "ceci et cela", 23, etc
	* @return array ('@Test(123,"ABC,etc")', "ceci et cela", 23, etc)
	*/
	static function separerInstructions($pFonctionComplete) {
		$pile = '';
		$morceaux = array();
		$morceau = '';
		for ($i = 0;$i < strlen($pFonctionComplete) ; $i++){
			$c = $pFonctionComplete[$i];
			if ($pile === '' and $c === ',') { // fin d'un morceau intermédiaire
				$morceaux[] = trim($morceau);// dernier paramètre et fermeture générale
				$morceau = '';
			} else {
				$finpile = substr($pile, -1);
				$morceau .= $c;
				if ($finpile !== '"' and $finpile !== '}') {
					// on n'est pas dans une chaine de caractères, on empile ou dépile normalement les parenthèses
					if($c === '(') {
						$pile .= ')';
					} elseif ($c === '"') {
						$pile .= '"';
					} elseif ($c === '{') {
						$pile .= '}';
					} elseif ($c === '[') {
						$pile .= ']';
					} elseif ($c === $finpile) { // on va fermer une partie enclose
						$pile = substr($pile, 0, -1); // dépiler une chaine x..x
					}
				} else {
					// on est dans une chaine de caractères : 
					// on n'empile pas : on dépile si on tombe sur le caractère de fin de chaine
					if ($c === $finpile) {
						$pile = substr($pile, 0, -1);
					}
				}
			}
		}
		$morceau = trim($morceau);
		if ($morceau !== '') {
			$morceaux[] = $morceau; // dernier morceau calculé
		}
		// suppression d'une parenthèse inutile (..) autour d'un résultat
		$ret = array();
		foreach($morceaux as $morceau) {			
			while ((substr($morceau,0,1) === '(' and substr($morceau,-1) === ')')
			or (substr($morceau,0,1) === '[' and substr($morceau,-1) === ']')) {
				$morceau = substr($morceau, 1, -1);
			}
			$r = $morceau;
			if(sizeof($morceaux) > 1) {
				$r = self::separerInstructions($morceau);
				if (is_array($r) and sizeof($r) === 1) {
					$r = $r[0];
				}
			}
			$ret[] = $r;
		}
		return $ret;
	}
	/** 1.0.4
	*/
	function isProprieteExiste($pNom = '') {
		$nom = SG_Texte::getTexte($pNom);
		$ret = false;
		if (isset($this -> getFormuleOrigine() -> proprietes[$nom])) {
			$ret = true;
		}
		return $ret;
	}
	/** 1.0.4 ; 2.0 parm2
	*/
	function getValeur($pNom = '', $pValeurDefaut = null) {
		$nom = SG_Texte::getTexte($pNom);
		$ret = null;
		if ($this -> isProprieteExiste($nom)) {
			$ret = $this -> getFormuleOrigine() -> proprietes[$nom];
		}
		return $ret;
	}
	/** 1.0.4
	*/
	function setValeur($pNom = '', $pValeur) {
		$nom = SG_Texte::getTexte($pNom);
		$this -> getFormuleOrigine() -> proprietes[$nom] = $pValeur;
	}
	/** 1.0.4
	*/
	function getFormuleOrigine() {
		if ($this -> formuleparent === null) {
			$ret = $this;
		} else {
			$ret = $this -> formuleparent -> getFormuleOrigine();
		}
		return $ret;
	}
	/**1.1 ajout (ancien socle.php -> formule)
	* Execute une formule, sans contexte particulier
	*
	* @param string $pFormule formule à exécuter
	* @param indéfini $pObjet objet sur lequel appliquer la formule
	* @param indefini $pObjetPrincipal : principal de la formule si différent de l'objer
	* @param indefini $pParent formule parente si nécessaire
	*
	* @return indéfini retour de la formule
	*/
	static function executer($pFormule = '', $pObjet = null, $pObjetPrincipal = null, $pParent = null, $pParametres = null) {
/// ATTENTION ne pas mettre de tracer() dans cette fonction : plantage
		$tmpFormule = new SG_Formule($pFormule, $pObjet, $pObjetPrincipal, $pParent, $pParametres);
		$ret = $tmpFormule -> calculer();
		return $ret;
	}
	/** 1.3  ajout pour amélioration des performances
	* @param $pObjet
	* @param $pObjetPrincipal
	*/
	function calculerSur($pObjet = null, $pObjetPrincipal = null) {
		if(is_array($pObjet)) {
			$this -> proprietes = $pObjet;
		} else {
			$this -> objet = $pObjet;
		}
		if ($pObjetPrincipal !== null) {
			$this -> objetPrincipal = $pObjetPrincipal;
		} else {
			$this -> objetPrincipal = $this -> objet;
		}
		return $this -> calculer();
	}
	/** 1.3.1 ajout repris de ->calculer
	*
	*/
	function calculerInstruction($pInstructions = '', $pObjet) {
		if(is_array($pInstructions) and sizeof($pInstructions) === 1) {
			$pInstructions = $pInstructions[0];
		}
		if (is_array($pInstructions)) {
			$ret = new SG_Collection();
			foreach($pInstructions as $instruction) {
				$ret -> elements[] = $this -> calculerInstruction($instruction, $pObjet);
			}
		} else {
			// Si on a passé un objet et que la formule commence par un ., la formule porte sur lui sinon sur @Rien
			$resultatIntermediaire = new SG_Rien();
			if (!is_null($pObjet)) {
				if (substr($pInstructions, 0, 1) === '.') {
					$resultatIntermediaire = $pObjet;
				}
			}
			// décomposition de l'instruction en fonctions
			$fonctions = self::eclater($pInstructions, '.');
			// boucle sur les fonctions
			foreach($fonctions as $fonction) {
				if ($fonction !== '') {
					$resultatIntermediaire = $this -> calculerFonction($fonction, $resultatIntermediaire);
				}
			}
			$ret = $resultatIntermediaire;
		}
		return $ret;
	}
	/** 2.1 ajout
	* Affiche la formule (sans l'exécuter)
	* @param
	* @return SG_HTML : le texte de la formule et éventuellement du php
	**/
	function Afficher() {
		$ret = '<p><i>' . $this -> formule . '</i>';
		if ($this -> php !== '') {
			$ret.= '<br><pre>' . $this -> php . '</pre>'; 
		} elseif ($this -> fonction !== '') {
			$ret.= '<br><pre>' . $this -> fonction . '</pre>'; 
		}
		$ret.= '</p>';
		return new SG_HTML($ret);
	}
	/** 2.1 ajout
	* Initialise la formule parente
	* @param (SG_Formule) $pParent : sg_formule parente
	**/
	function setParent($pParent) {
		if (getTypeSG($pParent) === '@Formule') {
			$this -> formuleparent = $pParent;
			$this -> operation = $pParent -> operation;
		}
	}
	/** 2.1 ajout
	* appelle la fonction au niveau parent
	* @param : fonction appelée
	* @param : tableau des arguments
	**/
	function callParent ($pFonction, $pArgs) {
		$ret = call_user_func_array(array(parent, $pFonction), $pArgs);
		return $ret;
	}
}
?>
