<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de compilation de requêtes et de formule
* max //069(pour l'indication des lignes)
*/
class SG_Compilateur extends SG_Objet{
	// Type SynerGaia
	const TYPESG = '@Compilateur';
	public $typeSG = self::TYPESG;
	
	public $phrase = '';  // la phrase à traduire ou en cours de traduction
	public $etapes = array(); // fonctions étapes de la phrase
	public $formats;
	public $fonctions = array();
	public $php = '';
	public $erreur = ''; // message d'erreur ou SG_Erreur
	public $contexte = ''; // 'o' formule d'opération, 'm' formule de méthode, 'p' valeurs possibles, 'f' formule directe simple, 'd' valeurs par défaut
	
	private $niveau = 0;//*** pour contrôler les boucles
	private $limiteBoucle = 3000; // nombre maximum de directives traitées avant arrêt
	private $cursor = 0;
	private $position = 0; // indice de l'erreur de syntaxe
	private $positionerreur = 0; // indice aux environs de l'erreur
	private $fin; // longueur de la chaine à traduire
	private $noformule = 0;
	private $noetape = 0;
	private $methode = ''; // méthode utilisée pour le dernier résultat. Elle servira pour le calcul des titres

	/** 1.0.6 ajout
	* Construction de l'objet
	*
	* @param indéfini $pQuelqueChose valeur à partir de laquelle créer la date
	**/
	public function __construct($pPhrase = '') {
		$this -> phrase = SG_Texte::getTexte($pPhrase);
		$this -> fin = strlen($this -> phrase);
		
		// + indique facultatif, * indique répétable
		// testés comme caractères isolés : . , ; = >
		$formats = array();
		$formats['B'] = 'Branche';
		$formats['C'] = 'Collection';
		$formats['D'] = 'Date';
		$formats['F'] = 'Fonction'; //= @ ou T
		$formats['H'] = 'FonctionInitiale'; //= @ ou T
		$formats['I'] = 'Instruction';
		$formats['P'] = 'Parametres';
		$formats['S'] = 'SuiteInstructions';
		$formats['T'] = 'Terme';
		$formats['@'] = 'MotSysteme';
		$formats['9'] = 'Nombre';
		$formats['('] = 'BlocParentheses';
		$formats['{'] = 'BlocAccolades';
		$formats['['] = 'BlocCrochets';
		$formats['"'] = 'ChaineEntreQuotes';
		$formats['>'] = 'Etiquette';
		$formats['#'] = 'Fin';
		$formats['!'] = 'Erreur';
		$formats[','] = 'Virgule';
		$formats[';'] = 'PointVirgule';
		$formats['.'] = 'PointFonction';
		$formats['-'] = 'Espaces';
		$this -> formats = $formats;
		$this -> noformule = 0;
		$this -> initialiser();
	}
	
	function initialiser() {
		$this -> noetape = 1;
		$this -> fonctions = array();
		$this -> niveau = 0;
	}

	function toString() {
		return $this -> phrase;
	}
	
	function Afficher() {
		$ret = '<div id="compilateur" data-role="panel" ><ul data-role="listview"><li data-role="list_divider">Compilateur</li><li>Langage : PHP</li></ul></div>';
		$ret .= '<richtext>';
		$ret .= $this -> toHTML();
		if ($this -> erreur !== '') {
			$ret.= '<pre>'.$this -> phrase . '
			' . str_repeat('-',$this -> position) . '^
			<span style="color:red">'. $this -> erreur . '</span></pre>';
		}
		$ret .= '</richtext>';
		return new SG_HTML($ret);
	}
	
	/** 1.0.6
	* toHTML : Affiche les blocs comme liste UL imbriquées
	**/	
	function toHTML() {
		$v = str_replace(PHP_EOL, '<br>', $this -> php);
		$ret = '<pre>' . $v . '</pre>';
		return $ret;
	}

	/** 1.0.6 @Traduire
	* Traduit la phrase en blocs exécutables dans le langage du compilateur
	* @param (string ou @Texte) : Phrase = Branche | '(' Phrase ')'
	* @param (string) $pOptions : '' : modèle d'opération, 'd' fonctions d'un document 
	**/
	public function Traduire($pPhrase = '', $pOptions = '') {
		$ret = $this;
		$phrase = SG_Texte::getTexte($pPhrase);
		if($phrase !== '') {
			$this -> phrase = $phrase;
		}
		$ifin = strlen($this -> phrase) - 1;
		$erreur = '';
		$i = 0;
		$longueur = 0;
		// démarrage branche principale ($this = opération en cours)
		$p = PHP_EOL . '		';
		$php = $p . '$resultat = array();//001'; // sera pour l'affichage
		$php.= $p . '$this -> etape = $etape;';
		if($pOptions === '') {
			$this -> initialiser();
			$php.= $p . 'if (!property_exists($this, \'objet\') or $this -> objet === null) {';
			$php.= $p . '	$objet = $this -> Principal();//002';
			$php.= $p . '	if (($this -> etape === \'\' or $this -> etape === \'1\') and $objet -> EstVide() -> estVrai()) {$objet = $this;}';
			$php.= $p . '} else {';
			$php.= $p . '	$objet = $this -> objet;';
			$php.= $p . '}';
			$php.= $p . 'if (getTypeSG($objet) === \'@Erreur\') {';
			$php.= $p . '	$resultat[] = $objet;';
			$php.= $p . '	return $resultat;';
			$php.= $p . '}';
			$php.= $p . '$btn = \'\';';
			$php.= $p . '$_SESSION[\'saisie\'] = false;';
			$php.= $p . 'switch ($this -> etape) {' . $p . '	case \'\':';
		} elseif ($pOptions === 'd') {
			$php.= $p . '$objet = $this;//003';
		}
		try {
			while ($i < $ifin) {
				if ($this -> testerBoucle('Traduire', $i)) {
					$ret = new SG_Erreur('0099', substr($this -> phrase, 0, $i+10));
					break;
				}
				//skip spaces
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				$c = $this -> phrase[$i];
				switch ($c) {
					//tester les cas les plus clairs (identifiables sur le premier caractère)
					case '(':
						$mot = $this -> BlocParentheses($i);
						break;
					case '{':
						$mot = $this -> BlocAccolades($i);
						break;
					case '[':
						$mot = $this -> BlocCrochets($i);
						break;
					default :
						// on doit avoir une branche
						$mot = $this -> Branche($i);
				}
				if (isset($mot['Erreur'])) {
					//$detail[] = $mot;
					$erreur = $mot['Erreur'];
					break;
				} else {
					if (isset($mot['php'])) {
						$php.= $mot['php'];
					}
					if (isset($mot['Longueur'])) {
						$longueur += $mot['Longueur'];
						//$detail[] = $mot;
						$i += $mot['Longueur'];
					}
					$this -> position = $i;
				}
			}
			//$phrase['Detail'] = $detail;
			if ($erreur !== '') {
				$this -> erreur = $erreur;
			}
			// par défaut si un lien va plus loin, on utilise Consulter
			if ($this -> noetape > 1) {
				$php.= $p.'	case \''. $this -> noetape . '\'://004';
				$php.= $p.'		if (method_exists($objet, \'FN_Consulter\')){$ret = $objet -> FN_Consulter();';
				$php.= $p.'		} elseif (method_exists($objet, \'Consulter\')){$ret = $objet -> Consulter();};';
				$php.= $p.'		$resultat["operation"] = $ret;';
			}
			$php.= $p.'	default:'.$p.'}//005';
			$this -> php = $php;
		} catch (Exception $e) {
			$ret = $this -> catchErreur($e);
		}
		return $ret;
	}
	
	/** 1.0.6 ; 2.0 test sur Branche
	* Branche : suite linéaire d'instructions séparées par des chevrons 
	* @param integer $pDebut début de la partie de phrase à analyser
	* @param string : caractère fermant un bloc attendu (sinon jusqu'à la fni de phrase
	* @return array la première fonction reconnue ou erreur
	**/
	function Branche($pDebut) {
		if ($this -> testerBoucle('Branche', $pDebut)) {
			$ret = new SG_Erreur('0099');
		} else {
			$ifin = strlen($this -> phrase) - 1;
			$i = $pDebut;
			$longueur=0;
			$noformuleprincipale = $this -> noformule;
			$this -> noformule++;
			$php = '';
			$p = PHP_EOL . '			';			
			$ret = Array('Type'=>'Branche', 'Mot'=> '', 'Longueur'=> 0);
			while ($i < $ifin) {
				// boucle sur les suites d'instructions
				// skip spaces
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				$c = $this -> phrase[$i];
				// fin de branche ? pas possible (testé dans Traduire)
				if($c === ')' or $c === ']' or $c === '}') {
					throw new Exception (SG_Libelle::getLibelle('0171')); // ferme sans ouvrir
				}
				// SuiteInstructions
				$this -> noformule++;
				$mot = $this -> tester($i, 'S');
				if($mot === false) {
					throw new Exception (SG_Libelle::getLibelle('0122')); // suite instructions erronée
				} elseif($mot['Longueur'] === 0) {
					break;
				} else {
					$longueur+= $mot['Longueur'];
					if(isset($mot['php'])) {
						$php.= $p . 'case \'' . $this -> noetape . '\'://006';
						// préparer envoi vers l'étape suivante (peut être modifié dans l'étape)
						$php.= $p.'	if($typeres === \'\') {$_SESSION[\'page\'][\'etape_prochaine\'] = \'' . ($this -> noetape + 1) . '\';}';
						// appel de la fonction d'étape
						$php.= $p . '	$resultat = $this -> etape_' . $this -> noetape . ' ($objet, $typeres);';//$mot['php'];
						// écriture de la fonction d'étape
						$fonction = PHP_EOL . '	function etape_' . $this -> noetape . ' ($objet, $typeres = \'\') {//007';
						$fonction.= PHP_EOL . '		$ret = false;';
						$fonction.= PHP_EOL . '		$resultat = array();';
						$fonction.= $mot['php'];
						// si formule, retourner le dernier résultat obtenu
						$fonction.= PHP_EOL . '		if ($typeres === \'f\') {//063';
						$fonction.= PHP_EOL . '			$resultat = $ret;'; 
						// si aucun résultat HTML à afficher, lister le dernier résultat obtenu
						$fonction.= PHP_EOL . '		} elseif ($resultat === array()) {' . PHP_EOL . '			$resultat[] = new SG_HTML($ret);' . PHP_EOL . '		}'; 
						$fonction.= PHP_EOL . '		return $resultat;';
						$fonction.= PHP_EOL . '	}' . PHP_EOL;
						$this -> etapes[] = $fonction;
						$this -> noetape++;
					}
					$i+= $mot['Longueur'];
				}
				
				// Etiquette ?
				$mot = $this -> Etiquette($i);
				if($mot === false) {
					throw new Exception (SG_Libelle::getLibelle('0121')); // étiquette erronée
				} elseif($mot['Longueur'] === 0) {// sortie
					break;
				}
				$longueur += $mot['Longueur'];
				if($mot['Mot'] !== '') {
					// ajout de l'envoi du bouton vers l'étape suivante
					$php.= $p . '	$btn = \'' . $mot['Mot'] .'\';//008';
					$php.= $p . '	if ($btn === \'>\'){'.$p.'		if (isset($_SESSION[\'saisie\']) and $_SESSION[\'saisie\'] === true){';
					$php.= $p . '			$btn = SG_Libelle::getLibelle(\'0116\', false);'.$p.'		} else {';
					$php.= $p . '			$btn = SG_Libelle::getLibelle(\'0117\', false);'.$p.'		}'.$p.'	}';
					$php.= $p . '	$resultat[\'submit\'] = $btn;'.PHP_EOL;
				}
				$php.= $p.'	break;//009';
				$i += $mot['Longueur'];
			}
			if (getTypeSG($ret) !== '@Erreur') {
				// si saisie sur dernière étape, ajouter un bouton
				$php.= $p.'	if (($btn === \'>\' or $btn === \'\') and isset($_SESSION[\'saisie\']) and $_SESSION[\'saisie\'] === true) {//010';
				$php.= $p.' 	if (! is_array($resultat)) {'. $p.' 	$resultat = array($resultat);' . $p.'		}';
				$php.= $p.'		$resultat[\'submit\'] = SG_Libelle::getLibelle(\'0118\',false);'.$p.'	}';
				$php.= $p.'	break;';
				$ret = Array('Type'=>'Branche', 'Mot'=> substr($this -> phrase, $pDebut, $longueur), 'Longueur'=> $longueur);
				$ret['php'] = $php;
			}
		}
		return $ret;
	}
	/** 1.0.6
	* SuiteInstructions : instructions séparées par des virgules donnant une collection de résultats
	* @param integer $pDebut début de la partie de phrase à analyser
	* @return array la première fonction reconnue ou erreur
	**/
	function SuiteInstructions($pDebut, $cSep = null) {
		$ifin = strlen($this -> phrase) - 1;
		$detail = array();
		$erreur = '';
		$longueur = 0; 
		$i = $pDebut;
		$php = '';
		$ret = array('Type' => 'SuiteInstructions', 'Mot' => '', 'php'=>'');
		$premier = true;
		$ponctuation = false;
		$collection = false;
		while ($i < $ifin) {
			$this -> testerBoucle('SuiteInstructions', $i);
			// skip spaces
			$n = $this -> sauterEspaces($i);
			$longueur+= $n;
			$i += $n;
			if ($i >= $ifin) {break;} // fini
			$c = $this -> phrase[$i];
			// regarder si c'est une ponctuation
			if ($cSep !== null and $c === $cSep) {
				break; // fin de paramètre
			} elseif ($c === ')' or $c === '|' or $c === '>') {
				break; // fin de bloc
			} elseif ($c === ',') {
				$i++;
				$mot = $this -> Collection($i, $mot);
				$php.= $mot['php'];
				$ponctuation = true;
			} elseif ($c === ';') {
				$mot = array('Type' => 'PointVirgule', 'Mot' => ';', 'Longueur' => 1 );
				$ponctuation = true;
			} elseif ($c === ':') { // titre de colonne
				$titre = 
				$mot = array('Type' => 'DeuxPoints', 'Mot' => ':', 'Longueur' => 1 );
				$ponctuation = true;
			} else {
				$mot = $this -> Instruction($i);
			}
			$detail[] = $mot;
			if (getTypeSG($mot) === '@Erreur') {
				break;
			} elseif (isset($mot['Erreur'])) {
				$ret['Erreur'] = $mot['Erreur'];
				break;		
			} else {
				if ($premier) {
					$ret['Type'] = $mot['Type'];
					$premier = false;
				} else {
					$ret['Type'] = 'SuiteInstructions';
				}
				// titre pour les colonnes (après le :)
				if(isset($mot['titre'])) {
					$ret['titre'] = $mot['titre'];
				}
				if(isset($mot['php'])){
					$php.= $mot['php'];
				}
				if(!$ponctuation and $cSep !== ',') {
					// si resultat HTML, saut de ligne
					$php.= PHP_EOL . '		if (is_object($ret) and $ret -> estHTML()) {//058'.PHP_EOL.'			$ret -> rupture = \'p\';'.PHP_EOL.'		}';
					// stockage dans $resultat
					$php.= PHP_EOL . '		if (is_array($ret)){$resultat = array_merge($resultat,$ret);}//011';
					$php.= PHP_EOL . '		elseif (is_object($ret) and $ret -> estHTML()){$resultat[] = $ret;}'.PHP_EOL;
				} else {
					$ponctuation = false;
				}
				$longueur += $mot['Longueur'];
				$i += $mot['Longueur'];
			}
		}
		$ret['Longueur'] = $longueur;
		$ret['Detail'] = $detail;
		$ret['php'] = $php;
		$this -> position = $pDebut + $longueur;
		return $ret;
	}
	/** 1.0.6
	* Instruction : recherche une fonction ou valeur puis suite de pointfonction
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string extrait (avec les bornes) ou false si pas trouvé
	**/	
	function Instruction($pDebut = 0, $pCible = '') {
		$ret = array('Type' => 'Instruction', 'Mot' => '', 'Longueur' => 0);
		$ifin = strlen($this -> phrase) - 1;
		$affectation = true;
		$dejaaffectation = false;
		$longueur = 0;
		$i = $pDebut;
		$affect = '';
		$php = '';
		$phpprec = '';
		$variable = false; // a priori ce n'est pas une variable locale
		$premier = true; // c'est le début de l'instruction
		$titre = null;
		$p = PHP_EOL . '		';
		while ($i < $ifin) {
			$this -> testerBoucle('Instruction', $i);
			// skip spaces
			$n = $this -> sauterEspaces($i);
			$longueur+= $n;
			$i += $n;
			if ($i < $ifin) {
				$c = $this -> phrase[$i];
				if ($c === '(') {
					$mot = $this -> BlocParentheses($i, 'C');
					$ret['Type'] = $mot['Type'];
					$longueur += $mot['Longueur'];
					$i += $mot['Longueur'];
					$php.= $mot['php'];
					$premier = false;
				} elseif ($premier) {// début d'instruction : tester ChaineEntreQuotes , Nombre, PointFonction, FonctionInitiale
					if($c === '"') {
						$mot = $this -> ChaineEntreQuotes($i);
						if (!isset($mot['Erreur'])) {
							$php = $p.'$ret = ' . $mot['php'];
						}
					} else {
						$mot = $this -> Nombre($i);
						if (!isset($mot['Erreur'])) {
							$php = $p.'$ret = ' . $mot['php'];
						} else {
							$mot = $this -> PointFonction($i);
							if (!isset($mot['Erreur'])) {
								$php.=$p.'$o = $objet;' .$mot['php'];
							} else {
								$mot = $this -> FonctionInitiale($i);
								if (!isset($mot['Erreur'])) {
									$php.= $p.'$o = $objet;' . $mot['php'];
									$variable = true;
								} else {
									$ret['Erreur'] = SG_Libelle::getLibelle('0172');// attendu " ou 9 ou . ou H'
								}
							}
						}
					}
					$ret['Type'] = $mot['Type'];
					$longueur += $mot['Longueur'];
					$i += $mot['Longueur'];
					$premier = false; // on a passé le premier terme
				} elseif ($c === '=') {
					$ret['Type'] = 'Affectation';
					// affectation
					if ($dejaaffectation or ! $affectation) {	
						$ret['Erreur'] = SG_Libelle::getLibelle('0124');
						break;
					} elseif ($mot['Type'] === 'ChaineEntreQuotes' or $mot['Type'] === 'Nombre'){	
						$ret['Erreur'] = SG_Libelle::getLibelle('0125', true, $mot['Type']);
						break;
					} else {
						if($variable) {
							$affect = $p.'$this -> proprietes[\''.$mot['Mot'] . '\'] = $ret;//012';
							$affect.= $p.'$this -> proprietes[\'@Type_'.$mot['Mot'].'\'] = getTypeSG($ret);';
						} else { // TODO récupérer objet à affecter dans $ret
							$affect = $phpprec.$p.'if (method_exists($objet,\'setValeur\')) {//013';
							$affect.= $p.'	$objet -> setValeur(\''.$mot['Mot'].'\', $ret);' . $p. '	$ret = $objet;' .$p.'} else {';
							$affect.= $p.'	$ret = new SG_Erreur(\'0158\', \''.$mot['Mot'].'\');'.$p.'}';
						}
						$affectation = false;
						$i++;
						$longueur ++;
						$php = '';
					}
					$dejaaffectation = true;
					$variable = false;
					$premier = true; // on a à nouveau un premier terme
				} elseif ($c === ')') { // fin de parenthèse
					break;
				} elseif ($c === ':') { // titre
					break;
				} else {
					// suite de l'instruction : que des pointfonction
					$mot = $this -> tester($i, '.');
					if ($mot === false) {
						break;
					} elseif (isset($mot['Erreur'])) {
						break;
					} else {
						$longueur += $mot['Longueur'];
						$i += $mot['Longueur'];
						if(isset($mot['php'])){
							$phpprec = $php;
							$php.=$p.'$o = $ret;//066' . $mot['php'];
						}
						$ret['Type'] = 'Instruction';
					}
					$variable = false;
				}
			}
		}
		// sauter les espaces
		$n = $this -> sauterEspaces($i);
		$longueur+= $n;
		$i+= $n;
		$titre = $this -> TitreInstruction($i);
		// titre éventuel du résultat de l'instruction (surtout pour les colonnes de vue)
		if ($titre !== false) {
			$longueur+= strlen($titre) + 1;
			$ret['titre'] = $titre;
		}
		$ret['Mot'] = substr($this -> phrase, $pDebut, $longueur);
		$ret['Longueur'] = $longueur;
		// s'il y a une affectation on ne rend pas de résultat
		if($affect !== '') {
			$php.= $affect;
			$ret['='] = true;
		}
		// ajout de commentaires
		$ret['php'] = $php; // $p . '/** Instruction : ' . substr($this -> phrase,$pDebut,$longueur) . ' **/'. $php;
		$this -> position = $pDebut + $longueur;
		return $ret;
	}
	/** 1.0.6
	* tester : essaie si l'une des posibilités est exacte. S'arrête dès la première rencontrée.
	* @param integer $pDebut début de la partie de phrase à analyser
	* @return array la première fonction reconnue ou erreur
	**/	
	function tester($pDebut = 0, $cas = '') {
		$mot = false;
		for($i = 0; $i < strlen($cas); $i++) {
			$c = $cas[$i];
			if(isset($this->formats[$c])) {
				$attendu = $this->formats[$c];
				$mot = $this -> $attendu($pDebut);
				if(getTypeSG($mot) === '@Erreur' or !isset($mot['Erreur'])) { // arrêt si trouvé ou erreur grave
					break;
				}
			} else {
				$mot = new SG_Erreur('0126', $c);
				break;
			}
		}
		return $mot;
	}
	/** 1.0.6
	* PointFonction : recherche un point et une fonction
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string extrait (avec le point) ou false si pas trouvé
	**/
	function PointFonction($pDebut = 0) {
		$this -> testerBoucle('PointFonction', $pDebut);
		$ret = array('Type'=>'PointFonction', 'Mot'=>'', 'Longueur' => 0);
		$ifin = strlen($this -> phrase) - 1;
		$longueur = 0;
		$i = $pDebut;
		$n = $this -> sauterEspaces($i);
		$longueur+= $n;
		$i += $n;
		if ($i < $ifin) {
			if ($this -> phrase[$i] !== '.') {
				$ret['Erreur'] = SG_Libelle::getLibelle('0127',true, $this -> phrase[$i]);
			} else {
				$i ++;
				$longueur++;
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				if ($i < $ifin) {
					$mot = $this -> tester($i, 'F'); // tester si fonction
					if (getTypeSG($mot) === '@Erreur') {
						$ret = $mot;
					} elseif ($mot === false) {
						$ret['Erreur'] = SG_Libelle::getLibelle('0128');
					} elseif (isset($mot['Erreur'])) {
						$ret = $mot;
					} else {
						$longueur += $mot['Longueur'];
						$ret = $mot;
					}
				}
			}
		}
		$this -> position = $pDebut + $longueur;
		$ret['Longueur'] = $longueur;
		return $ret;
	}
	/** 2.3 test $nomf != $nom
	* 1.0.6 ; 2.1.1 si ni terme ni motsysteme : mieux récupéré ; 2.2 .@Propriete("") ; test @Erreur ; //014 : recherche dictionnaire
	* Fonction : recherche soit un motsystème ou terme seul, avec paramètres (c'est à dire pas de valeur directe)
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string extrait (avec les bornes) ou false si pas trouvé
	**/	
	function Fonction($pDebut = 0) {
		$this -> testerBoucle('Fonction', $pDebut);
		$ret = array('Type'=>'Fonction', 'Mot'=>'', 'Longueur' => 0);
		$i = $pDebut;
		$longueur = 0;
		$ifin = strlen($this -> phrase) - 1;
		$mot = $this -> tester($i, '@T');
		$php = '';
		$p = PHP_EOL . '	';
		if ($mot === false) {
			throw new Exception (SG_Libelle::getLibelle('0129'));
		} elseif (isset($mot['Erreur'])) {
			throw new Exception ($mot['Erreur']);
		} else {
			$longueur += $mot['Longueur'];
			$i += $mot['Longueur'];
			$nom = $mot['Mot']; // terme ou mot système
			$ret['Mot'] = $nom;
			$this -> methode = $mot['Methode']; // garder la méthode du dernier résultat
			// sauter les espaces
			$n = $this -> sauterEspaces($i);
			$longueur+= $n;
			$i += $n;
			$prm = '';
			if ($i < $ifin) {
				// traiter les paramètres
				$param = $this -> Parametres($i);
				if ($param !== false) {
					$noformule = $this -> noformule;
					if ($param === false) {
						$ret['Erreur'] = SG_Libelle::getLibelle('0130');
					} elseif (isset($param['Erreur'])) {
						$ret['Erreur'] = SG_Libelle::getLibelle('0131', true, $param['Erreur']);
					} elseif (isset($param['php'])) {
						$prm = '(';
						if (isset($param['Detail'])) {
							foreach ($param['Detail'] as $noformule) {
								if(strlen($prm) > 1) {
									$prm .= ',';
								}
								$prm.= '$p' . $noformule;
							}
						}
						$prm.= ')';
						$longueur += $param['Longueur'];
					} else {
						$ret['Erreur'] = SG_Libelle::getLibelle('0132');
						$longueur += $param['Longueur'];
					}
				}
			}
			if ($prm === '') {$prm = '()';}
			// il faut repérer si on doit chercher la méthode sur l'objet ou sur le parent SynerGaïa (si @)
			// cette façon n'est pas très sûre ici car on ne connait pas encore l'objet sur lequel on travaille...
			if ($mot['Type'] === 'MotSysteme') {
				$nomp = '@' . $nom;
				$nomf = $nom;// méthode sysème ? (methode)
			} else {
				$nomp = $nom;
				$nomf = 'FN_' . $nom;// méthode spécifique de l'application ? (FN_methode)
			}
			if($prm === '()') {
				// TODO traitement des preenregistrer et postenregistrer pas clair... à revoir et généraliser dans fonctioninitiale
				$php.= $p.'	if (!is_object($o)) {//014';
				$php.= $p.'		$ret = new SG_Erreur(\'0166\',\'' . $nomp .  ' \' . SG_Texte::getTexte($o));';
				$php.= $p.'	} elseif (isset($o -> proprietes[\'' . $nomp . '\'])) {';//propriete locale; 
				$php.= $p.'		$ret = $o -> getValeurPropriete(\'' . $nomp . '\', \'\');';
				$php.= $p.'	} elseif (SG_Dictionnaire::isProprieteExiste(getTypeSG($o),\'' . $nomp . '\')) {';// propriété au dictionnaire
				$php.= $p.'		$ret = $o -> getValeurPropriete(\'' . $nomp . '\', \'\');';
				$php.= $p.'	} elseif (isset($this -> proprietes[\'' . $nomp . '\'])) {'; // propriété de l'objet en cours d'exécution : opération (ou document)
				$php.= $p.'		$ret = $this -> proprietes[\'' . $nomp . '\'];';
				$php.= $p.'	} elseif (method_exists($o,\'' . $nomf . '\')){'. $p.'		$ret = $o ->' . $nomf . '();';// méthode spécifique FN_
				if ($nomf !== $nom) {
					$php.= $p.'	} elseif (method_exists($o,\'' . $nom . '\')){'. $p.'		$ret = $o ->' . $nom . '();';// méthode spécifique telle quelle
				}
				$php.= $p.'	} else {'.$p.'		$ret = $o -> getValeurPropriete(\'' . $nomp . '\', \'\');'. $p.'	}';// sinon propriété du document
			} else {
				// avec paramètres
				$php.= $param['php'];
				$php.= $p.'	if (SG_Dictionnaire::isProprieteExiste(getTypeSG($o), \'' . $nomp . '\')) {//015';
				$php.= $p.'		$ret = $o -> MettreValeur(\''.$nomp.'\',' .substr($prm, 1) . ';';
				$php.= $p.'	} elseif (method_exists($o,\'' . $nomf . '\')){'. $p.'		$ret = $o -> ' . $nomf . $prm.';';
				if ($nomf !== $nom) {
					$php.= $p.'	} elseif (method_exists($o,\'' . $nom . '\')){'. $p.'		$ret = $o -> ' . $nom . $prm.';';
				}
				$php.= $p.'	} elseif (getTypeSG($o) === \'@Erreur\') {'. $p.'		$ret = $o;';
				$php.= $p.'	} else {'.$p.'		$ret = new SG_Erreur(\'0150\',getTypeSG($o) .\'.' . $nomf . '\');'. $p.'	}';
			}
			$ret['Longueur'] = $longueur;
		}
		$ret['php'] = $php;
		$this -> position = $pDebut + $longueur;
		return $ret;
	}
	/** 1.0.6 ; 2.3 init $contexte ; traite $nomsyst=$nom ; correct si $1 vide
	* FonctionInitiale : début d'instruction
	* recherche soit un motsystème ou terme seul, avec paramètres (c'est à dire pas de valeur directe)
	* si mot n'est pas une méthode de SG_Rien, c'est un new d'objet
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string extrait (avec les bornes) ou false si pas trouvé
	**/	
	function FonctionInitiale($pDebut = 0) {
		$this -> testerBoucle('FonctionInitiale',$pDebut);
		$ret = array('Type'=>'FonctionInitiale', 'Mot'=>'', 'Longueur' => 0);
		$i = $pDebut;
		$longueur = 0;
		$ifin = strlen($this -> phrase) - 1;
		$mot = $this -> tester($i, '@T');
		$php = '';
		$p = PHP_EOL . '		';
		if ($mot === false) {
			$ret['Erreur'] = SG_Libelle::getLibelle('0133');
		} else {
			if (! isset($mot['Erreur'])) {
				$longueur += $mot['Longueur'];
				$i += $mot['Longueur'];
				$nom = $mot['Mot']; // terme ou mot système
				$nomsyst = $nom;
				if($mot['Type']==='MotSysteme') {
					$nomsyst = '@' . $nom;
				}
				$ret['Mot'] = $nom;
				$this -> methode = $mot['Methode']; // garder la méthode du dernier résultat
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				$prm = '';
				// traitement des paramètres éventuels
				$param = array('php' => '');
				if ($i < $ifin) {
					$param = $this -> Parametres($i);
					if ($param !== false) {
						$prm = '(';
						if (isset($param['Erreur'])) {
							$ret['Erreur'] = SG_Libelle::getLibelle('0135', true, $param['Erreur']);
						} elseif (isset($param['Detail'])) {
							foreach ($param['Detail'] as $noformule) {
								if(strlen($prm) > 1) {
									$prm .= ',';
								}
								$prm.= '$p' . $noformule;
							}
							$longueur += $param['Longueur'];
						} else {
							$ret['Erreur'] = SG_Libelle::getLibelle('0136');
							$longueur += $param['Longueur'];
						}
						$prm.= ')';
					}
				}
				// calcul de la fonction initiale
				$f = '';
				if ($nom === 'EtapeEnCours') { // cas particulier de @EtapeEnCours ?
					$f = $p.'$ret = new SG_Texte($this -> etape);//016';
				} elseif (method_exists('SG_Rien',$nom)){ // soit c'est une méthode synergaia de SG_Rien ?
					if ($prm === '') {
						$f = $p.'$ret = SG_Rien::' . $nom . '();//017';
					} else {
						$f = $p.'$ret = SG_Rien::' . $nom . $prm . ';//018';
					}
				} elseif (method_exists('SG_Rien','FN_' . $nom)){ // soit c'est une méthode applicative de SG_Rien ?
					if ($prm === '') {
						$f = $p.'$rien = new SG_Rien();$ret = $rien -> FN_' . $nom . '();//059';
					} else {
						$f = $p.'$rien = new SG_Rien();$ret = $rien -> FN_' . $nom . $prm . ';//060';
					}
				} else {
					if($mot['Type']==='MotSysteme') { // soit new d'une classe d'objet SynerGaïa
						$c = 'SG_' . $nom; // SG_Dictionnaire::getClasseObjet('@' . $nom);
						$nom = '@' . $nom;
						if (class_exists($c)) {
							if ($prm === '') {
								$prm = '()';
							}
							$f = $p.'$ret = new ' . $c . $prm . ';//019';
						} else {
							$this -> positionerreur = $i;
							$erreur = new SG_Erreur('0174', $nom); // TODO
							throw new Exception ($erreur -> getMessage());
						}
					} else { // soit c'est un type d'objet de l'application
						$c = $nom; //SG_Dictionnaire::getClasseObjet($nom);
						if (SG_Dictionnaire::isObjetDocument($nom) === true) {
							if ($prm === '') {
								$f = $p.'	$ret = new ' . $c . '();//068';
							} else {
								$f = $p.'	$ret = SG_Rien::Chercher(\'' . $c . '\', ' . $prm . ');//020';
								$f.= $p.'	if (sizeof($ret -> elements) === 1) {'.$p.'	$ret = $ret -> elements[0];'.$p.'}';
							}
						} else {
							if (substr($c, 0, 1) === '$') {
								$f = $p.'	$ret = new SG_Erreur(\'0160\',\''. $c . '\');//021';
							} else {
								$f = $p.'	if (class_exists(\'' . $c . '\')) {//056';
								if ($prm === '') {
									$f.= $p.'		$ret = new ' . $c . '();//022';
								} else {
									$f.= $p.'		$ret = new ' . $c . $prm . ';//055';
								}
								$f.= $p.'	} else {//057';
								$f.= $p.'		$ret = new SG_Erreur(\'0170\',\''. $c . '\');';
								$f.= $p.'	}';
							}
						}
					}
					if($c === '') { // cas impossible
						$ret['Erreur'] = SG_Libelle::getLibelle('0137', true, $nom);
					}
				}
				if ($prm === '') {
					// propriété locale de la formule (variable)
					if (substr($nomsyst,0,1) === '$') { // paramètre de la formule
						$novariable = intval(substr($nomsyst,1)) - 1;
						$php.= $p.'if (isset($contexte[' . $novariable . '])) {//067';
						$php.= $p.'	$ret = $contexte[' . $novariable . '];';
						$php.= $p.'} elseif (isset($this -> proprietes[\'' . $nomsyst . '\'])) {';
					} else {
						$php.= $p.'if (isset($this -> proprietes[\'' . $nomsyst . '\'])) {//023';
					}
					$php.= $p.'	$ret = $this -> proprietes[\'' . $nomsyst . '\'];';
					if ($nomsyst !== $nom) {
						$php.= $p.'} elseif (isset($this -> proprietes[\'' . $nom . '\'])) {';
						$php.= $p.'	$ret = $this -> proprietes[\'' . $nom . '\'];';
					}
					// méthode de la classe d'objet
					$php.= $p.'} else {' . $f .$p.'}'.PHP_EOL;					
					$php.= $p.'if ($ret === \'\' or $ret === null) {$ret = new SG_Texte();} //069';
				} else {
					$php.= $param['php'] . $f;
				}
				$ret['Longueur'] = $longueur;
			}
		}
		$ret['php'] = $php;
		$this -> position = $pDebut + $longueur;
		return $ret;
	}
	/** 2.1 ajout
	* Parametres : instructions séparées par des virgules donnant un tableau de formules
	* @param integer $pDebut début de la partie de phrase à analyser
	* @return array la première fonction reconnue ou erreur
	**/
	function Parametres($pDebut) {
		$this -> testerBoucle('Parametres', $pDebut);
		$i = $pDebut + $this -> sauterEspaces($pDebut);
		$ret = false;
		if ($this -> phrase[$i] === '(') {
			$i++;
			$ifin = strlen($this -> phrase) - 1;
			$detail = array();
			$erreur = '';
			$longueur = 0;
			$ret = array('Type' => 'Parametres', 'Mot' => '', 'php'=>'', 'Longueur' => 99999);
			$php = '';
			while ($i <= $ifin) {
				// skip spaces
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				$c = $this -> phrase[$i];
				if ($c === ')') {
					break; //fin des paramètres
				} elseif ($c === '|' or $c === '>') {
					break; // fin de bloc : on laisse traiter par la fonction appelante
				} elseif ($c === ',') {
					$mot = array('Type' => 'Virgule', 'Mot' => ',', 'Longueur' => 1 );
				} elseif ($c === ';') {
					$mot = array('Type' => 'PointVirgule', 'Mot' => ';', 'Longueur' => 1 );
				} else {
					$no = '$p' . $this -> noformule;
					$mot = $this -> SuiteInstructions($i, ','); // chaque instruction s'arrête sur une virgule
					if (getTypeSG($mot) === '@Erreur') {
						break;
					} elseif (isset($mot['Erreur'])) {
						$erreur = $mot['Erreur'];
						break;				
					} else {
						// écriture de la fonction du paramètre
						$fn = true;
						$txt = '';
						$no = '$p' . $this -> noformule;
						$p = PHP_EOL . '		' . $no;
						// écritre du titre (pour les entête de colonnes notamment)
						if(isset($mot['Erreur'])){
							$ret['Erreur'] = $mot['Erreur'];
							break;
						} elseif (!isset($mot['titre']) and ($mot['Type'] === 'Nombre' or $mot['Type'] === 'ChaineEntreQuotes')) {
							// résultat direct Nombre ou Chaine
							// TODO traiter les dates
							$php.= $mot['php'];
							$php.= PHP_EOL . '		' . $no . ' = $ret;//065';
						} else {
							// prépa de la @Formule du paramètre pour la fonction
							$php.= PHP_EOL.$p. ' = new SG_Formule();//024';
							$php.= $p . ' -> fonction = \'fn' . $this -> noformule . '\';'; // fonction d'exécution du paramètre
							$php.= $p . ' -> methode = \'.' . $this -> methode . '\';';
							$php.= $p . ' -> objet = $objet;' . $p . ' -> setParent($this);';
							$php.= $p . ' -> operation = $this;';
							$php.= PHP_EOL . '		if (isset($contexte)) {';
							$php.= $p . ' 	-> contexte = $contexte;';
							$php.= PHP_EOL . '		}';
							if (isset($mot['titre'])) {
								$php.= $p .' -> titre = \''.addslashes($mot['titre']).'\';//025';
							}
							// prépare la fonction elle-même
							$txt = $mot['php']; //addslashes($mot['php']);
							$this -> fonctions['fn' . $this -> noformule] = $txt; //$php;
						}
						$detail[] = $this -> noformule;
						$this -> noformule++;
					}
				}
				$longueur += $mot['Longueur'];
				$i += $mot['Longueur'];
			}
			if ($c !== ')') { // mal terminé
				$erreur = new SG_Erreur('0169', $pDebut);
				throw new Exception ($erreur -> getMessage());
			} else {
				$i++; // sauter la parenthèse fermante
			}
			$ret['Detail'] = $detail;
			$ret['php'] = $php;//$php;
			if (isset($mot['Erreur'])) {
				$ret['Erreur'] = $mot['Erreur'];
			}
		}
		if ($ret !== false) {
			$ret['Longueur'] = $i - $pDebut;
		}
		$this -> position = $i;
		return $ret;
	}
	/** 1.0.6 ; 2.1 param 2
	* BlocParentheses : recherche la phrase entre parenthèses
	* @param integer $idebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	* @return le test ou false si pas trouvé de parenthèse fermante
	**/
	function BlocParentheses($pDebut = 0, $pContenu = 'B') {
		$ret = $this -> blocFerme($pDebut, '(', ')', $pContenu);
		if(!isset($ret['Erreur'])) {
			$this -> noformule++;
			if ($pContenu !== 'C') {
				$ret['php'] = '(' . $ret['php'] . ')';
			}
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.3 addslashes -> str_replace ; try
	* Crée une classe SG_Operation à partir d'un modèle opération compilé
	**/
	function compilerOperation($pNom = '', $pFormule = '', $pPHP = '', $pPrefixe = 'MO_') {
		try {
			$ret = false;
			$php = '<?php defined("SYNERGAIA_PATH_TO_ROOT") or die(\'403.14 - Directory listing denied.\');//026' . PHP_EOL;
			$php.= '/** SynerGaia ' . SG_SynerGaia::VERSION . ' (see AUTHORS file)' . PHP_EOL;
			$php.= '* Classe SynerGaia compilée le ' . date('Y-m-d H:i:s') . PHP_EOL . '**/' . PHP_EOL;
			$php.= 'class ' . $pPrefixe . $pNom . ' extends SG_Operation {' . PHP_EOL;
			$php.= '	const DATE_COMPILATION = \'' . date('Y-m-d H:i:s') .'\';' . PHP_EOL;
			$php.= '	const VERSION = \'' . SG_SynerGaia::VERSION . '\';' . PHP_EOL;
			$php.= '	private $rien;';
			$p = PHP_EOL . '		';
			$php.= PHP_EOL . '	function Formule() {//064'.$p.'return new SG_Texte(\'' . str_replace('\'', '\\\'', $pFormule) . '\');'.PHP_EOL.'	}'.PHP_EOL;
			//$php.= '	/**' . PHP_EOL . $pFormule . PHP_EOL . '	**/'.PHP_EOL;
			$php.= '	function traiterSpecifique($etape = \'\', $typeres=\'\') {';
			$php.= $p . '$this -> rien = new SG_Rien();';
			$php.= $p . '$ret = false;';
			$php.= $pPHP;
			if( $this -> erreur !== '') {
				$php.= $p.'$resultat = new SG_Erreur(\'' . str_replace('\'', '\\\'', $this -> erreur) . '\');//027';
			}
			$php.= $p . 'return $resultat;' . PHP_EOL . '	}//028';
			// etapes
			foreach ($this -> etapes as $texte) {
				$php.= PHP_EOL . $texte;
			}
			// fonctions
			foreach ($this -> fonctions as $nom => $texte) {
				$php.= PHP_EOL.'	function ' . $nom .'($objet) {//029' . $texte . $p . 'return	$ret;//045'.PHP_EOL.'	}' . PHP_EOL;
			}
			$php.= PHP_EOL . '}//037' . PHP_EOL . '?>';
			$ret = file_put_contents(SYNERGAIA_PATH_TO_APPLI . '/var/' . $pPrefixe . $pNom . '.php', $php);	
		} catch (Exception $e) {
			$ret = $this -> catchErreur($e);
		}											
		return $ret;
	}
	/** 2.1 ajout ; 2.3 try
	* Crée une classe spécifique à l'objet
	* @param (string) $pNom : nom de l'objet SynerGaïa à compiler
	**/
	function compilerObjet($pNom = '') {
		$ret = false;
		try {
			// si nécessaire, lire l'objet
			if (is_string($pNom)) {
				$objet = SG_Dictionnaire::getDictionnaireObjet($pNom);
				$nom = $pNom;
			} else {
				$objet = $pNom;
				$nom = $objet -> getValeur('@Code','inconnu');
			}
			
			// créer l'entête de la classe (déduire la class Extends et le type général)
			$php = '<?php defined("SYNERGAIA_PATH_TO_ROOT") or die(\'403.14 - Directory listing denied.\');//030' . PHP_EOL;
			$php.= '/** SynerGaia ' . SG_SynerGaia::VERSION . ' (see AUTHORS file)' . PHP_EOL;
			$php.= '* Classe SynerGaia compilée le ' . date('Y-m-d H:i:s') . PHP_EOL . '**/' . PHP_EOL;
			$modele = $objet -> getValeurPropriete('@Modele');
			if (getTypeSG($modele) === '@Rien') {
				$modele = '@Objet';
			} else {
				$modele = sg_Texte::getTexte($modele -> getValeur('@Code'));
			}
			$classe = SG_Dictionnaire::getClasseObjet($modele);
			$php.= 'class ' . $nom . ' extends ' . $classe . ' {//031' . PHP_EOL;
			$php.= '	const DATE_COMPILATION = \'' . date('Y-m-d H:i:s') .'\';' . PHP_EOL;
			$php.= '	const VERSION = \'' . SG_SynerGaia::VERSION . '\';' . PHP_EOL;
			$php.= '	const TYPESG = \'' . $nom . '\';' . PHP_EOL;
			$php.= '	public $typeSG = self::TYPESG;' . PHP_EOL;
			$php.= '	public $operation;' . PHP_EOL;
			
			// lire les propriétés et chercher les formules de valeurs possibles
			$proprietes = SG_Dictionnaire::getProprietesObjet($nom,'', true);
			foreach($proprietes as $nompropriete => $modele) {
				$propriete = SG_Dictionnaire::getPropriete($nom, $nompropriete);
				if ($propriete === '') {
					$ret = new SG_Erreur('0162', $nompropriete);
				} else {
					$type = $propriete -> getValeur('@Modele', '');
					$titre = $propriete -> getValeur('@Titre', '');
					$vd = $propriete -> getValeur('@ValeurDefaut', '');
					if($vd !== '') {
						$txt = $this -> compilerPhrase($vd, $nompropriete . '_defaut');
						if ($this -> erreur === '') {
							$php.= '	/** //032' . PHP_EOL;
							$php.= '	* @formula : ' . $vd . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur($this -> erreur . ' sur ' . $nompropriete);
						}
					}
					$vp = $propriete -> getValeur('@ValeursPossibles', '');
					if($vp !== '') {
						$txt = $this -> compilerPhrase($vp, $nompropriete . '_possibles');
						if ($this -> erreur === '') {
							$php.= '	/** //033' . PHP_EOL;
							$php.= '	* @formula : ' . $vp . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur($this -> erreur . ' sur ' . $nompropriete);
						}
					}
				}
			}
			// lire les méthodes et chercher les formules d'action
			$methodes = $_SESSION['@SynerGaia'] -> sgbd -> getMethodesObjet($nom);	
			foreach($methodes -> elements as $methode) {
				$action = SG_Dictionnaire::getActionMethode($nom, $methode['nom'], true);
				if($action !== '') {
					$txt = $this -> compilerPhrase($action, 'FN_' . $methode['nom']);
					if (getTypeSG($txt) === '@Erreur') {
						$ret = $txt;
					} elseif ($this -> erreur === '') {
						$php.= '	/** //034' . PHP_EOL;
						$php.= '	* @formula : ' . $action . PHP_EOL;
						$php.= '	**/' . PHP_EOL;
						$php.= $txt;
					} else {
						$ret = new SG_Erreur($this -> erreur . ' sur ' . $methode['nom']);
					}
				}
			}
			// fonctions associées
			$p = PHP_EOL . '		';
			foreach ($this -> fonctions as $id => $texte) {
				$php.= PHP_EOL.'	function ' . $id .'($objet) {//046' . $texte . $p . 'return	$ret;'.PHP_EOL.'	}//035' . PHP_EOL;
			}
			$php.= PHP_EOL . '}//036' . PHP_EOL . '?>';
			$ret = file_put_contents(SYNERGAIA_PATH_TO_APPLI . '/var/' . $nom . '.php', $php);
		} catch (Exception $e) {
			$ret = $this -> catchErreur($e);
		}
		return $ret;
	}
	/** 1.0.6
	* BlocAccolades : recherche la phrase entre parenthèses
	* @param integer $idebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	* @return le test ou false si pas truvé de parenthèse fermante
	**/
	function BlocAccolades($pDebut = 0) {
		return $this -> blocFerme($pDebut, '{', '}');
	}
	/** 1.0.6
	* BlocCrochets : recherche la phrase entre crochets
	* @param integer $idebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	* @return le test ou false si pas truvé de parenthèse fermante
	**/
	function BlocCrochets($pDebut = 0) {
		return $this -> blocFerme($pDebut, '[', ']');
	}
	/** 1.0.6
	* ChaineEntreQuotes : extrait une chaine entre quotes
	* @param integer $pDebut début dans la phrase du compilateur
	* @return array extrait ou null si pas trouvé du tout, ou Erreur si pas 2 double quotes
	**/
	function ChaineEntreQuotes($pDebut = 0) {
		$this -> testerBoucle('ChaineEntreQuotes', $pDebut);
		$ret = false;
		$i = $pDebut;
		if ($this -> phrase[$pDebut] === '"') {
			$ret = array('Type'=>'ChaineEntreQuotes', 'Mot' => '', 'Longueur' => 0);
			$ipos = strpos($this -> phrase, '"', $i + 1);
			if ($ipos !== false) {
				$txt = substr($this -> phrase, $i + 1, $ipos - $i - 1);
				$ret['Mot'] = $txt;
				$ret['Longueur'] = strlen($txt) + 2;
				$ret['php'] = 'new SG_Texte(\'' . addslashes($txt) . '\');//038';
			} else {
				$ret = new SG_Erreur('0138');
				$this -> position = $pDebut;
				throw new Exception($ret -> getMessage());
			}
			$this -> position += $ret['Longueur'];
		}
		return $ret;
	}
	
	/** 1.0.6
	* Etiquette : recherche soit un chevron seul soit |étiquette>
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string extrait (avec les bornes),  ou null si pas trouvé, ou erreur si pas trouvé la borne fin
	**/
	function Etiquette($pDebut = 0) { 
		$this -> testerBoucle('Etiquette', $pDebut);
		$ret = array('Type'=>'Etiquette', 'Mot' => '', 'Longueur' => 0);
		$i = $pDebut;
		if ($i < strlen($this -> phrase) - 1) {
			if ($this -> phrase[$i] === '>') {
				$ret['Longueur'] = 1;
				$ret['Mot'] = '>';
			} elseif ($this -> phrase[$i] === '|') {
				$ipos = strpos($this -> phrase, '>', $i);
				if ($ipos !== false) {
					$txt = substr($this -> phrase, $i+1, $ipos - $i-1);
					$ret['Mot'] = $txt;
					$ret['Longueur'] = strlen($txt) + 2;
				} else {
					$ret['Erreur'] = SG_Libelle::getLibelle('0139');
				}
			}
			$this -> position += $ret['Longueur'];
		}
		return $ret;
	}

	/** 1.0.6
	* blocFerme : recherche la phrase entre parenthèses
	* @param integer $idebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	* @param string $pCaracOuvrant : caractère ouvrant le bloc
	* @param string $pCaracFermant : caractère fermant le bloc
	* @param string $pTypeContenu : type de contenu (si vide, tout type jusqu'au caractère fermant)
	* 			'B' branche, 'P' paramètres, 'C' collection
	* @return null si pas trouvé, le mot si ok,  ou Erreur si pas trouvé de caractère fermant ou intérieur invalide
	**/
	function blocFerme($pDebut = 0, $pCaracOuvrant = '(', $pCaracFermant = ')', $pTypeContenu = '') {
		$this -> testerBoucle('blocFerme', $pDebut);
		$ret = array('Type' => $this -> formats[$pCaracOuvrant], 'Mot' => '', 'Longueur' => 0);
		$ifin = strlen($this -> phrase) - 1;
		$trouvefin = false;
		if ($this -> phrase[$pDebut] === $pCaracOuvrant) {
			$ret = array('Type' => $this -> formats[$pCaracOuvrant], 'Mot' => '', 'Longueur' => 2);
			$i = $pDebut + 1;
			$mot = null;
			// calcul du $mot
			if ($pTypeContenu === '') {
				$ipos = strpos($this -> phrase, $pCaracFermant, $i);
				if ($ipos !== false) {
					$txt = substr($this -> phrase, $i, $ipos - $i);
					$mot = array();
					$mot['Mot'] = $txt;
					$mot['Longueur'] = strlen($txt);
					if(isset($mot['php'])){
						$ret['php']=$mot['php'];
					}
				} else {
					$ret['Erreur'] = SG_Libelle::getLibelle('0140', true, $pCaracFermant);
				}
			} elseif ($pTypeContenu === 'B' or $pTypeContenu === 'P' or $pTypeContenu === 'C') {
				$mot = $this -> tester($i, $pTypeContenu);
				$ret = $mot;
			} else {
				$ret['Erreur'] = SG_Libelle::getLibelle('0141' , true, $pTypeContenu);
			}
			// est-il correct ?
			if($mot !== null) {
				if(getTypeSG($mot) === '@Erreur') {
					throw new Exception($mot -> getMessage());
				} elseif (isset($mot['Erreur'])) {
					$ret['Erreur'] = $mot['Erreur'];
				} else {
					$ret['Mot'] = $mot['Mot'];
					$ret['Longueur'] = $mot['Longueur'] + 2; // ajouter les caractères de parenthèse
					if(isset($mot['php'])){
						$ret['php']=$mot['php'];
					}
				}
			}
		} else {
			$ret['Erreur'] = SG_Libelle::getLibelle('0142', true, $pCaracOuvrant);
		}
		return $ret;
	}
	/** 1.0.6
	* Terme : extrait un nom (suite de caractères alphanumériques)
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string terme extrait ou boolean false si pas trouvé
	**/
	function Terme($pDebut = 0) {
		$this -> testerBoucle('Terme', $pDebut);
		$ret = array('Type'=> 'Terme', 'Mot'=> '');
		$idebut = $pDebut;
		$ifin = strlen($this -> phrase) - 1;
		$mot = '';
		for ($i = $idebut; $i <= $ifin; $i++) {
			$c = $this -> phrase[$i];
			if ( $this -> isAlphameric($c) === false) {
				break;
			} else {
				$mot .= $c;
			}
		}
		if ($mot === '') {
			$ret['Erreur'] = SG_Libelle::getLibelle('0143') . ':'.substr($this -> phrase,$idebut,$ifin);
		} else {
			$ret['Mot'] = $mot;
			$ret['Longueur'] = strlen($mot);
			$ret['Methode'] = $mot;
		}
		$this -> position = $pDebut + strlen($mot);
		return $ret;
	}
	
	/** 1.0.6
	* Nombre : extrait un nombre (suite de chiffres)
	* @param integer $pDebut début dans la phrase du compilateur
	* @return string nombre extrait ou boolean false si pas trouvé
	**/
	function Nombre($pDebut = 0) {
		$this -> testerBoucle('Nombre', $pDebut);
		$ret = array('Type'=>'Nombre', 'Mot'=>'', 'Longueur' => 0);
		$mot = '';
		$ifin = strlen($this -> phrase);
		$signe = '';
		$longueur = 0;
		$virgule = false;// TODO la virgule pose problème car c'est un signe de collection ; idem pour le point
		for ($i = $pDebut ; $i < $ifin; $i++) {
			$c = $this -> phrase[$i];
			if ($c === '+' or $c === '-') {
				if ($signe === '') {
					$signe = $c;
				} else {
					break;
				}
			} elseif ($c < '0' or $c > '9') {
				break;
			} else {
				$mot .= $c;
			}
			$longueur++;
		}
		if ($mot === '') {
			$ret['Erreur'] = '0144';
		} else {
			$ret = array('Type'=>'Nombre', 'Mot'=>$signe . $mot, 'Valeur'=>$mot, 'Signe'=>$signe, 'Longueur' => $longueur);
			$ret['php'] = 'new SG_Nombre(' . $signe . $mot . ');//039';
		}
		return $ret;
	}
	/** 2.1.1 isMotSimple
	* Doit commencer par @, puis alaphanumérique
	* @return le mot ou vide
	**/
	function MotSysteme($pDebut = 0) {
		$this -> testerBoucle('MotSysteme', $pDebut);
		$mot = '';
		$idebut = $pDebut;
		$ifin = strlen($this -> phrase) - 1;
		if ($this -> phrase[$idebut] === '@') {
			for ($i = $idebut + 1; $i <= $ifin; $i++) {
				$c = $this -> phrase[$i];
				if ($this -> isMotSimple($c) === false) {
					break;
				} else {
					$mot .= $c;
				}
			}
		}
		$ret = array('Type'=>'MotSysteme', 'Mot' => $mot, 'Longueur' => 0);
		if ($mot === '') {
			$ret['Erreur'] = SG_Libelle::getLibelle('0145');
		} else {
			$ret['Longueur'] = strlen($mot) + 1;
			$ret['Methode'] = '@' . $mot;
		}
		$this -> position += $ret['Longueur'];
		return $ret;
	}
	/** 1.0.6
	* isAlphameric : Le caractère est une lettre, un chiffre, tiret bas, dollar, arrobase
	* @param string $c le caractè_re à analyser
	* @return boolean True or False
	**/
	function isAlphameric($c) {
		$orig = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ';
		$dest = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby';
		$c = strtr(strtolower(utf8_decode($c)), utf8_decode($orig), $dest);;
		if (($c >= 'a' and $c <= 'z') or ($c >= '0' and $c <= '9') or ($c === '_') or ($c === '$') or ($c === '@')) {
			return true;
		} else {
			return false;
		}
	}
	/** 1.0.6
	* Teste si le caractère $c est bien $cas.
	* @param string $c caractère à tester
	* @param string $cas caractère de référence
	* @return array si ok tableau composé de $type, $c, sinon Erreur
	**/	
	function Caractere($c, $cas, $type) {
		$mot = array('Type' => $type, 'Mot' => $c, 'Longueur' => 1);
		if($c !== $cas) {
			$ret['Erreur'] = SG_Libelle::getLibelle('0146', true, $cas);
		}
		return $mot;
	}
	/** 1.0.6
	* dans la phrase du compilateur, sauter les espaces, les tabulations et les retours de ligne
	* @param integer $idebut début de la phrase à étudier
	* @param integer $ifin fin de la phrase
	* @return integer la longueur des espaces trouvés
	**/	
	function sauterEspaces($pDebut = 0) {
		$ret = 0;
		$ifin = strlen($this -> phrase);
		for ($i = $pDebut; $i < $ifin; $i++) {
			$c = $this -> phrase[$i];
			if ($c === ' ' or $c === '\n' or ord($c) === 13 or $c === '\t' or $c === PHP_EOL or ord($c) === 0 or ord($c) === 12) {
				$ret++;
			} else {
				break;
			}
		}
		return $ret;
	}
	// pour éviter les boucles en compilation (//TODO faire disparaitre ce besoin...
	function testerBoucle($ou = '', $pDebut) {
		$ret = false;
		$this -> niveau ++;
		if ($this -> niveau > $this -> limiteBoucle) {
			throw new Exception(SG_Libelle::getLibelle('0147', true, $ou . ' à ' . $pDebut . ' sur ' . strlen($this -> phrase) . ' : ' . $this -> phrase));
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.3 $contexte
	* Compile une phrase pour les formules de valeurs possibles, valeurs par défaut ou méthodes
	* @param $pPhrase : la phrase à compiler
	* @param $pNom : le nom de la fonction qui exécute le php
	* @param $pStatic (string) : mot 'static ' si la fonction est ainsi (avec un espace au bout !)
	**/
	function compilerPhrase($pPhrase = '', $pNom) {
		$this -> niveau = 0;
		$ret = '';
		$this -> erreur = '';
		$phrase = SG_Texte::getTexte($pPhrase);
		if($phrase !== '') {
			$this -> phrase = $phrase;
		}
		$ifin = strlen($this -> phrase) - 1;
		$this -> noformule++;
		$i = 0;
		$mot = $this -> tester($i, 'S');
		if($mot === false) {
			$ret = new SG_Erreur('0122'); // suite instructions erronée
		} elseif($mot['Longueur'] === 0) {
			$ret = new SG_Erreur('0157'); // résultat nul
		} elseif (isset($mot['Erreur'])) {
			$ret = new SG_Erreur('0159', $mot['Erreur']);
		} else {
			if(isset($mot['php'])) {
				$nom = $pNom;
				if (substr($nom, 0, 1) === '@') {
					$nom = substr($nom, 1);
				}
				// écriture de la fonction d'étape
				$ret = PHP_EOL.'	function ' . $nom . ' () {//040' . PHP_EOL;
				$ret.= '		$contexte = func_get_args();'.PHP_EOL;
				$ret.= '		$ret = false;'.PHP_EOL;
				$ret.= '		$objet = $this;' . PHP_EOL;
				$ret.= '		$resultat = array();' . PHP_EOL;
				$ret.= $mot['php'];
				// si formule, retourner le dernier résultat obtenu
				$ret.= PHP_EOL.'		if ($resultat === array()) {$resultat = $ret;}//041'.PHP_EOL; 
				$ret.= '		return $resultat;'.PHP_EOL;
				$ret.= '	}' . PHP_EOL;
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* recherche un titre à l'instruction (commence par un ':'). 
	* -> position est mis à jour sur le caractère suivant
	* @return : soit null (pas de titre), soit titre (sans le ':')
	**/
	function TitreInstruction($pDebut) {
		$i = $pDebut;
		$ret = false;
		$ifin = strlen($this -> phrase) - 1;
		if ($i < $ifin) {
			$c = $this -> phrase[$i];
			if ($c === ':') {
				$ret = '';
				$i++;
				while ($i < $ifin) {
					$c = $this -> phrase[$i];
					if ($c === ',' or $c === ';' or $c === ')' or $c === '|' or $c === '>' or $c === '}' or $c === ']') {
						break;
					}
					$ret.= $c;
					$i++;
				}
				$this -> position = $i;
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* détecte une collection dans une parenthèse (suite d'instructions séparées par des virgules) : ce ne sont pas des paramètres Parametres()
	* @param $pDebut : la position du curseur
	* @param $pPremierResultat : premier résultat à entrer dans la collection
	* @return : la création d'une collection d'objets sinon false
	**/
	function Collection($pDebut, $pPremierResultat = null) {
		$this -> testerBoucle('Collection', $pDebut);
		$ret = false;
		$i = $pDebut;
		$ifin = strlen($this -> phrase) - 1;
		$i+= $this -> sauterEspaces($i);
		$p = PHP_EOL . '	';
		$ret = array('Type' => 'Collection', 'Mot' => '', 'Longueur' => 0);
		// si on reçoit un premier résultat, on garde sa longueur + la virgule
		$longueur = 0;
		$php = $p.'	$collec = array();//042';
		if (is_array($pPremierResultat)) {
			$longueur = $pPremierResultat['Longueur'] + 1;
			$php.= $pPremierResultat['php'];
			$php = $p.'	$collec[] = $ret;//061';
		}
		while ($i < $ifin) {
			$i+= $this -> sauterEspaces($i);
			$this -> testerBoucle('Collection', $i);
			$mot = $this -> Instruction($i);
			if (getTypeSG($mot) === '@Erreur') {
				throw new Exception($mot -> getMessage());
			}
			// calcul de la phrase PHP
			$php.= $mot['php'] . $p.'	$collec[] = $ret;//043';
			$i+= $mot['Longueur'];
			if ($i < $ifin) {
				$c = $this -> phrase[$i];
				if ($c === ',') {
					$i++;
				} else { // sortie sur fin de la suite d'instructions
					break;
				}
			}
		}
		$php.= $p.'	$ret = new SG_Collection($collec);//047';
		$ret['php'] = $php;
		$ret['Longueur'] = $i - $pDebut + $longueur;
		$ret['Mot'] = substr($this -> phrase, $pDebut, $ret['Longueur']);
		$this -> position = $i + $longueur;
		return $ret;
	}
	/** 2.1 ajout ; 2.3 $contexte ; try
	* Crée un fichier Php complémentaire spécifique à l'objet stocké dans ../vars
	* @param (string) $pNom : nom de l'objet SynerGaïa à compiler
	**/
	function compilerObjetSysteme($pNom = '') {
		try {
			// si nécessaire, lire l'objet
			if (is_string($pNom)) {
				$objet = SG_Dictionnaire::getDictionnaireObjet($pNom);
				$nom = $pNom;
			} else {
				$objet = $pNom;
				$nom = $objet -> getValeur('@Code','inconnu');
			}
			// créer l'entête du fichier
			$php = '<?php defined("SYNERGAIA_PATH_TO_ROOT") or die(\'403.14 - Directory listing denied.\');//048' . PHP_EOL;
			$php.= '/** SynerGaia ' . SG_SynerGaia::VERSION . ' (see AUTHORS file)' . PHP_EOL;
			$php.= '* compilé le ' . date('Y-m-d H:i:s') . PHP_EOL . '**/' . PHP_EOL;
			$php.= 'trait SG_' . substr($nom,1) . '_trait {';
			$modele = $objet -> getValeurPropriete('@Modele');
			if (getTypeSG($modele) === '@Rien') {
				$modele = '@Objet';
			} else {
				$modele = sg_Texte::getTexte($modele -> getValeur('@Code'));
			}		
			// lire les propriétés et chercher les formules de valeurs possibles
			$proprietes = SG_Dictionnaire::getProprietesObjet($nom,'', true);
			foreach($proprietes as $nompropriete => $modele) {
				$propriete = SG_Dictionnaire::getPropriete($nom, $nompropriete);
				if ($propriete === '') {
					$ret = new SG_Erreur('0162', $nompropriete);
				} else {
					$type = $propriete -> getValeur('@Modele', '');
					$titre = $propriete -> getValeur('@Titre', '');
					$vd = $propriete -> getValeur('@ValeurDefaut', '');
					if($vd !== '') {
						$txt = $this -> compilerPhrase($vd, $nompropriete . '_defaut');
						if ($this -> erreur === '') {
							$php.= '	/** //049' . PHP_EOL;
							$php.= '	* @formula : ' . $vd . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur($this -> erreur . ' sur ' . $nompropriete);
						}
					}
					$vp = $propriete -> getValeur('@ValeursPossibles', '');
					if($vp !== '') {
						$txt = $this -> compilerPhrase($vp, $nompropriete . '_possibles');
						if ($this -> erreur === '') {
							$php.= '	/** //050' . PHP_EOL;
							$php.= '	* @formula : ' . $vp . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur($this -> erreur . ' sur ' . $nompropriete);
						}
					}
				}
			}
			// lire les méthodes et chercher les formules d'action
			$methodes = $_SESSION['@SynerGaia'] -> sgbd -> getMethodesObjet($nom);	
			foreach($methodes -> elements as $methode) {
				$action = SG_Dictionnaire::getActionMethode($nom, $methode['nom'], true);
				if($action !== '') {
					$txt = $this -> compilerPhrase($action, 'FN_' . $methode['nom']);
					if (getTypeSG($txt) === '@Erreur') {
						$ret = $txt;
					} elseif ($this -> erreur === '') {
						$php.= '	/** //051' . PHP_EOL;
						$php.= '	* @formula : ' . $action . PHP_EOL;
						$php.= '	**/' . PHP_EOL;
						$php.= $txt;
					} else {
						$ret = new SG_Erreur($this -> erreur . ' sur ' . $methode['nom']);
					}
				}
			}
			// fonctions associées stockées dans un SG_nom_trait.php
			$nom = 'SG_' . substr($nom,1);
			$p = PHP_EOL . '		';
			foreach ($this -> fonctions as $id => $texte) {
				$php.= PHP_EOL.'	function ' . $id .'($objet, $contexte) {//053' . $texte . $p . 'return	$ret;'.PHP_EOL.'	}//052' . PHP_EOL;
			}
			$php.= PHP_EOL . '}//054' . PHP_EOL . '?>';
			$ret = file_put_contents(SYNERGAIA_PATH_TO_APPLI . '/var/' . $nom . '_trait.php', $php);
		} catch (Exception $e) {
			$ret = $this -> catchErreur($e);
		}
		return $ret;
	}
	/** 2.1.1 ajout
	* isMotSimple : Le caractère est une lettre, un chiffre, tiret bas
	* @param string $c le caractère à analyser
	* @return boolean True or False
	**/
	function isMotSimple($c) {
		$c = strtolower($c);
		if (($c >= 'a' and $c <= 'z') or ($c >= '0' and $c <= '9') or ($c === '_')) {
			return true;
		} else {
			return false;
		}
	}
	/** 2.3 ajout
	* récupère une exception de compliation et la prépare pour l'affichage
	* @param $e : exception récupérée
	**/
	function catchErreur ($e) {
		if ($this -> positionerreur > 0) {
			$ipos = $this -> positionerreur;
		} else {
			$ipos = $this -> position;
		}
		$this -> erreur .= '<br>ligne ' . $e -> getLine() . ' : ' . $e -> getMessage();
		$ideb = $ipos - 15;
		if($ideb < 0) {
			$ideb = 0;
		}
		$ifin = $ipos + 10;
		if($ifin > strlen($this->phrase)) {
			$ifin = strlen($this->phrase);
		}
		$this -> erreur .= ',<br> vers ' . $ipos . ' :  ...' . substr($this -> phrase, $ideb, $ifin - $ideb) . '...';
		$this -> erreur .= ',<br> dans la phrase :' . $this -> phrase;
		$this -> erreur .= ',<br>Compilateur : voir ligne ' . $e -> getLine();
		$ret = new SG_Erreur('\'' . addslashes($this -> erreur) . '\''); 
		return $ret;
	} 
}
?>
