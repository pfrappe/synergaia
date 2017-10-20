<?php
/** fichier contenant la gestion du compilateur traduisant du langage SynerGaïa en PHP */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// n° libres pour l'indication des lignes : 059 062 067 068 070 071 082+
/**
 * Classe SynerGaia de compilation de requêtes et de formule
 * @since 1.0.6
 * @version 2.6
 */
class SG_Compilateur extends SG_Objet{
	/** string Type SynerGaia '@Compilateur' */
	const TYPESG = '@Compilateur';
	/** string préfixe des fonctions d'étapes - peut être nécessaire si ambiguité dans les noms ? */
	const PREFIXE_FONCTION = '';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	/** string la phrase à traduire ou en cours de traduction */
	public $phrase = '';
	/** array appels des étapes de la phrase */
	public $appels = array();
	/** array fonctions étapes de la phrase */
	public $etapes = array();
	/** string formats possibles après une objet */ 
	public $formats;
	/** array liste des fonctions php créées */
	public $fonctions = array();
	/** string texte php généré */
	public $php = '';
	/** string|SG_Erreur  message d'erreur ou SG_Erreur */
	public $erreur = '';
	/** array liste des étapes suivantes
	 * @since 2.6 */
	public $aliascodesetapes = array();
	/** array texte des clauses "case" du switch principal
	 * @since 2.6 */
	public $cases = array();
	/**
	 * string Contexte de la demande de compilation
	 * 'o' formule d'opération, 'm' formule de méthode, 'p' valeurs possibles, 'f' formule directe simple, 'd' valeurs par défaut
	 */
	public $contexte = '';
	/** integer Niveau de profondeur d'appel pour contrôler les boucles */
	private $niveau = 0;
	/** integer nombre maximum de directives traitées avant arrêt (3000)*/
	private $limiteBoucle = 3000;
	/** integer cursor dans la phrase à compiler */
	private $cursor = 0;
	/** integer indice de l'erreur de syntaxe */
	private $position = 0;
	/** integer indice aux environs de l'erreur */
	private $positionerreur = 0;
	/** integer longueur de la chaine à traduire */
	private $fin;
	/** integer numéro de la formule en cours */
	private $noformule = 0;
	/** integer numéro de la branche en cours */
	private $nobranche = 0;
	/** integer numéro de l'étape en cours */
	private $noetape = 0;
	/** string méthode utilisée pour le dernier résultat. Elle servira pour le calcul des titres */
	private $methode = '';

	/**
	 * Construction de l'objet
	 * @since 1.0.6 ajout
	 * @param string $pPhrase texte de la phrase à traduire
	 */
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

	/**
	 * Initialise diverses variables de l'obejt SG_Compilateur
	 * @since 1.0.6 ajout
	 */	
	function initialiser() {
		$this -> noetape = 1;
		$this -> fonctions = array();
		$this -> niveau = 0;
	}

	/**
	 * Retourne la phrase à compiler
	 * @since 1.0.6 ajout
	 * @return string texte de la pĥrase
	 */	
	function toString() {
		return $this -> phrase;
	}

	/**
	 * Afficher dans un navigateur
	 * @since 1.0.6 ajout
	 * @return SG_HTML texte de la pĥrase et du php
	 */	
	function Afficher() {
		$ret = '<div id="compilateur" data-role="panel" ><ul data-role="listview"><li data-role="list_divider">Compilateur</li><li>Langage : PHP</li></ul></div>';
		$ret .= '<richtext class="sg-richtext">';
		$ret .= $this -> toHTML();
		if ($this -> erreur !== '') {
			$ret.= '<pre>'.$this -> phrase . '
			' . str_repeat('-',$this -> position) . '^
			<span style="color:red">'. $this -> erreur . '</span></pre>';
		}
		$ret .= '</richtext>';
		return new SG_HTML($ret);
	}
	
	/**
	 * toHTML : Affiche les blocs comme liste UL imbriquées
	 * @since 1.0.6
	 * @return string html du php obtenu
	 */	
	function toHTML() {
		$v = str_replace(PHP_EOL, '<br>', $this -> php);
		$ret = '<pre>' . $v . '</pre>';
		return $ret;
	}

	/**
	 * Traduit la phrase en blocs exécutables dans le langage du compilateur
	 * @since 1.0.6 @Traduire
	 * @version 2.6 sup $btn
	 * @param string|SG_Texte|SG_Formule $pPhrase = Branche | '(' Phrase ')'
	 * @param string|SG_Texte|SG_Formule $pOptions : '' : modèle d'opération, 'd' fonctions d'un document 
	 * @return SG_Compilateur|SG_Erreur
	 */
	public function Traduire($pPhrase = '', $pOptions = '') {
		$ret = $this;
		$this -> erreur = '';
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
		if($pOptions === '') {
			$this -> initialiser();
			$php.= $p . 'if ($this -> prochainPrincipal !== null) {//002';
			$php.= $p . '	$this -> setPrincipal($this -> prochainPrincipal);';
			$php.= $p . '	$this -> prochainPrincipal = null;';
			$php.= $p . '}';
			$php.= $p . 'if (!property_exists($this, \'objet\') or $this -> objet === null) {';
			$php.= $p . '	$objet = $this -> Principal();';
			$php.= $p . '	if (($this -> etape === \'br00_01\') and $objet -> EstVide() -> estVrai()) {$objet = $this;}';
			$php.= $p . '} else {';
			$php.= $p . '	$objet = $this -> objet;';
			$php.= $p . '}';
			$php.= $p . 'if ($objet instanceof SG_Erreur) {';
			$php.= $p . '	$resultat[] = $objet;';
			$php.= $p . '	return $resultat;';
			$php.= $p . '}';
			$php.= $p . '$_SESSION[\'saisie\'] = false;';
			$php.= $p . 'switch ($this -> etape) {';
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
					$this -> erreur = $mot['Erreur'];
					$this -> STOP($i, $mot['Erreur']);
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
			foreach($this -> appels as $btnphp) {
				$php.= $btnphp;
			}
			/**** étapes standards ****/
			// par défaut, en fin de branche, si un lien va plus loin, on utilise Consulter
			$php.= $p.'	case \'consulter\'://004';
			$php.= $p.'		if (method_exists($objet, \'FN_Consulter\')){$ret = $objet -> FN_Consulter();';
			$php.= $p.'		} elseif (method_exists($objet, \'Consulter\')){$ret = $objet -> Consulter();};';
			$php.= $p.'		$resultat[\'operation\'] = $ret;';
			$php.= $p.'		break;';
			// si code étape inconnu
			$php.= $p.'	default:'.$p.'		$resultat = new SG_Erreur(\'0295\',$this -> etape);'.$p.'}';

			// on ajoute au début du php la correspondance entre les alias de codes étapes et le code réel (br99_99)
			$txt = $p . 'if ($etape === \'\') {//044' . $p . '	$this -> etape = \'br00_01\';';
			foreach ($this -> aliascodesetapes as $key => $val) {
				$txt.=  $p . '} elseif ($etape === \'' . $key . '\') {' . $p . '	$this -> etape = \'' . $val . '\';';
			}
			$txt.= $p . '} else {' . $p . '	$this -> etape = $etape;' . $p . '}';
			// on le place au début
			$php = $txt . $php;
			$this -> php = $php;
		} catch (Exception $e) {
			$cpl = $this -> catchErreur($e);
			if (! $this -> erreur instanceof SG_Erreur) {
				$this -> erreur = new SG_Erreur('0161', $cpl);
			} else {
				$this -> erreur -> trace = $cpl;
			}
			$ret = $this -> erreur;
		}
		return $ret;
	}
	
	/**
	 * Branche : [codeetape,titre] suite linéaire d'instructions séparées par des chevrons
	 * elle est codée dans la fonction etape_n (2.4 avec éventuellement des controles en ctrl_etape_n)
	 * 
	 * @since 1.0.6
	 * @version 2.4 controles
	 * @version 2.6 code branche 'br99' ; simplification test fin étape (vers 008) ; sup $btn ; code étape
	 * @param integer $pDebut début de la partie de phrase à analyser
	 * @param integer $pFin caractère où arrêter la traduction de la branche (par défaut '')
	 * @param string $pCode code de la branche par défaut 'etape'
	 * @return array : ['php'] la première fonction reconnue ou erreur ; ['ctl'] la fonction de controle associée ; ['code'] 1ere étape
	 * @todo voir si c'est dans cette fonction qu'on doit faire $this -> noformule++; (plutôt SuiteInstruction ?)
	 */
	function Branche($pDebut, $pFin = '') {
		if ($this -> testerBoucle('Branche', $pDebut)) {
			$ret = new SG_Erreur('0099');
		} else {
			// calcul du code de la branche
			$codebr = 'br' . $this -> format99($this -> nobranche) . '_';
			$this -> nobranche++;
			$noetape = 0;
			$ifin = strlen($this -> phrase) - 1;
			$i = $pDebut;
			$longueur=0;
			$this -> noformule++;
			$php = '';
			$p = PHP_EOL . '			';			
			$ret = Array('Type'=>'Branche', 'Mot'=> '', 'Longueur'=> 0, 'debut' => $codebr . '01');
			$break = false; // faut-il ajouter un break (fin de case) ?
			while ($i < $ifin) {
				// boucle sur les suites d'instructions
				// skip spaces
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				// fin de la phrase ?
				if($i >= $ifin) {
					break;
				}
				$c = $this -> phrase[$i];
				// fin de branche ? (fin de parenthèse ou fin du paramètre)
				if($c === ')' or ($pFin !== '' and $c === $pFin)) {
					break;
				}
				if ($c === ']' or $c === '}') {
					$this -> STOP($i, '0171');// pas possible (testé dans Traduire)
				}
				// nouvelle étape
				$noetape++;
				$codeetape = $codebr . $this -> format99($noetape); // code étape calculé
				$codemanuel = '';
				// y a-t-il un code d'étape manuel : [xxx ]...
				if ($c === '[') {
					// code étape différent fourni
					$mot = $this -> BlocCrochets($i);
					if ($mot === false) {
					} elseif ($mot instanceof SG_Erreur) {
						$this -> STOP($i, $mot); 
					} elseif($mot['Longueur'] === 0) {
						break;
					} else {
						$longueur+= $mot['Longueur'];
						$i += $mot['Longueur'];
					}
					// sauter les espaces
					$n = $this -> sauterEspaces($i);
					$longueur+= $n;
					$i += $n;
					if($i >= $ifin) {
						break;
					}
					$codemanuel = $mot['Mot'];
					$this -> aliascodesetapes[$codemanuel] = $codeetape;
				}
				// ajout du code de la première étape pour l'appel du début de branche via url
				if(! isset($ret['code']) or $ret['code'] === '') {
					$ret['code'] = $codeetape;
				}
				// décodage de la suite des instructions de l'étape
				$this -> noformule++;
				$mot = $this -> SuiteInstructions($i);
				// calcul du code de l'étape suivante
				$suivant = $codebr . $this -> format99($noetape + 1);
				// suite instructions erronée ou vide
				if($mot === false) {
					$this -> STOP($i, '0122');
				} elseif ($mot instanceof SG_Erreur) {
					$this -> STOP($i, $mot); 
				} else {
					// on a	 bien quelque chose
					$longueur+= $mot['Longueur'];
					// comme ce n'était pas la dernière étape de la branche, 
					// terminer le case de l'étape précédente (on est dans le switch)
					if ($break === true) {
						$php.= $p.'	break;//009';
						$break = false;
					}
					if(isset($mot['php'])) {
						// $php : rédaction appel de la fonction d'étape
						$php.= $p . 'case \'' . $codeetape . '\'://006';
						if ($codemanuel !== '') {
							// on ajoute l'alias
							$php.= $p . 'case \'' . $codemanuel . '\':';
						}
						$break = true;
						if ($mot['Longueur'] === 0) {
							// étape vide : on ne fait rien mais on renvoie quand même vers la suivante
							$php.= $p . '	$resultat = $this -> traiterEtape(\'rien\',$objet, $typeres, \'' . $suivant . '\');//055';
						} else {
							$php.= $p . '	$resultat = $this -> traiterEtape(\'' . $codeetape . '\',$objet, $typeres, \'' . $suivant . '\');';
							// $fonction : rédaction et stockage de la fonction d'étape
							$fonction = PHP_EOL . '	function ' . self::PREFIXE_FONCTION . $codeetape . ' ($objet, &$dernierresultat) {//007';
							$fonction.= PHP_EOL . '		$resultat = array();';
							$fonction.= $mot['php'];
							/* si à la fin on a aucun résultat on retourne le résultat de la dernière fonction
							$fonction.= PHP_EOL . '			$resultat[] = new SG_HTML($ret);';*/
							$fonction.= PHP_EOL . '		$dernierresultat = $ret;//057';
							$fonction.= PHP_EOL . '		return $resultat;';
							$fonction.= PHP_EOL . '	}';
							$this -> etapes[] = $fonction;
						}
						$this -> noetape++;
					}
					$i+= $mot['Longueur'];
				}		
				// Etiquette ?
				$mot = $this -> Etiquette($i);
				if($mot === false) {
					$this -> STOP($i, '0121'); // étiquette erronée
				} elseif($mot['Longueur'] === 0) {// sortie
					break;
				} elseif (isset($mot['ctl'])) {// y a-t-il des contrôles
					$ret['ctl'] = $mot['ctl'];
				}
				// oui
				$longueur += $mot['Longueur'];
				if($mot['Mot'] !== '') {
					// ajout de l'envoi du bouton vers l'étape suivante
					$php.= $p . '	if (! $resultat instanceof SG_Erreur) {//008';
					if ($mot['Mot'] === '>') {
						$php.= $p . '			if (isset($_SESSION[\'saisie\']) and $_SESSION[\'saisie\'] === true){';
						$php.= $p . '				$resultat[\'submit\'] = SG_Libelle::getLibelle(\'0116\', false);'.$p.'		} else {';
						$php.= $p . '				$resultat[\'submit\'] = SG_Libelle::getLibelle(\'0117\', false);'.$p.'			}';
					} else {
						$php.= $p . '		$resultat[\'submit\'] = \''. $mot['Mot'] . '\';';
					}
					$php.= $p . '	}';
				}
				$i += $mot['Longueur'];
			}
			// on est dans la dernière étape de la branche
			if (! $ret instanceof SG_Erreur) {
				// si saisie sur dernière étape, ajouter un bouton submit pour enregistrer les données saisies
				$php.= $p.'	if (isset($_SESSION[\'saisie\']) and $_SESSION[\'saisie\'] === true) {//010';
				$php.= $p.'		if (! is_array($resultat)) {$resultat = array($resultat);}';
				$php.= $p.'		$resultat[\'submit\'] = SG_Libelle::getLibelle(\'0118\',false);'.$p.'	}';
				$php.= $p.'	break;';
				// par défaut, en fin de branche, si un lien va plus loin, on utilise Consulter
				$this -> aliascodesetapes[$codebr . $this -> format99($noetape + 1)] = 'consulter';
				$ret['php'] = $php;
				$ret['Mot'] = substr($this -> phrase, $pDebut, $longueur);
				$ret['Longueur'] = $longueur;
			}
		}
		return $ret;
	}

	/**
	 * SuiteInstructions : instructions séparées par des virgules ou des points-virdules donnant une collection de résultats
	 * La fin d'une suite d'instruction est soit une parenthèqe fermante ou une étiquette, ou la fin de la phrase.
	 * 
	 * @since 1.0.6
	 * @version 2.4 supp collection à partir de virgules car trop ambigu
	 * @param integer $pDebut début de la partie de phrase à analyser
	 * @param $cSep string : caractère séparateur de suite d'instructions (cas des paramètres par exemple)
	 * @return array la première fonction reconnue ou erreur
	 */
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
		$p = PHP_EOL . '		';
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
			} elseif ($c === ';') {
				$mot = array('Type' => 'PointVirgule', 'Mot' => ';', 'Longueur' => 1 );
				$ponctuation = true;
			} elseif ($c === ':') { // titre de colonne
				$titre = '';
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
				// titre pour les colonnes (après le ':')
				if(isset($mot['titre'])) {
					$ret['titre'] = $mot['titre'];
				}
				if(isset($mot['php'])){
					$php.= $mot['php'];
				}
				

				if(!$ponctuation and $cSep !== ',') {
					// si resultat HTML, saut de ligne
					$php.= $p . 'if ($ret instanceof SG_HTML) {//058'.$p.'	$ret -> rupture = \'p\';';
					$php.= $p . '	$resultat[] = $ret;' . $p . '} elseif (is_array($ret)){';
					$php.= $p . '	$resultat = array_merge($resultat,$ret);';
					$php.= $p.'}';
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

	/**
	 * Instruction : recherche une fonction ou valeur puis suite de pointfonction
	 * @since 1.0.6
	 * @todo récupérer le cas où on n'est pas une PointFonction et qu'on devrait (cas lorsque sauterespace ne sautait pas ord(9))
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return array extrait (avec les bornes) ou false si pas trouvé
	 */	
	function Instruction($pDebut = 0) {
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
								$php.=$p.'$o = $objet;//011' .$mot['php'];
							} else {
								$mot = $this -> FonctionInitiale($i);
								if (!isset($mot['Erreur'])) {
									$php.= $p.'$o = $objet;//017' . $mot['php'];
									$variable = true;
								} else {
									$ret['Erreur'] = $mot['Erreur'];// attendu " ou 9 ou . ou H'
									break;
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
						$this -> STOP($i, '0124');
					} elseif ($mot['Type'] === 'ChaineEntreQuotes' or $mot['Type'] === 'Nombre'){	
						$this -> STOP($i, '0125', $mot['Type']);
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

	/**
	 * tester : essaie si l'une des posibilités est exacte. S'arrête dès la première rencontrée.
	 * @since 1.0.6
	 * @param integer $pDebut début de la partie de phrase à analyser
	 * @param string : liste de codes de cas à tester
	 * @return array la première fonction reconnue ou erreur
	 */	
	function tester($pDebut = 0, $cas = '') {
		$mot = false;
		for($i = 0; $i < strlen($cas); $i++) {
			$c = $cas[$i];
			if(isset($this->formats[$c])) {
				$attendu = $this->formats[$c];
				$mot = $this -> $attendu($pDebut);
				if($mot instanceof SG_Erreur or !isset($mot['Erreur'])) { // arrêt si trouvé ou erreur grave
					break;
				}
			} else {
				$this -> STOP($i, '0126', $c);
			}
		}
		return $mot;
	}

	/**
	 * PointFonction : recherche un point et une fonction
	 * @since 1.0.6
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return string extrait (avec le point) ou false si pas trouvé
	 */
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
				$ret['Erreur'] = new SG_Erreur('0127', substr($this -> phrase, $i, 15));
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
						$ret['Erreur'] = new SG_Erreur('0128');
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

	/**
	 * Fonction : recherche soit un motsystème ou terme seul, avec paramètres (c'est à dire pas de valeur directe)
	 * 
	 * @since 1.0.6
	 * @version 2.4 $txtvide
	 * @version 2.6 partie de texte mis dans SG_Operation::execFonction... ; @Clic
	 * @todo vérifier que le $nom est acceptable pour PHP
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return array|SG_Erreur extrait (avec les bornes) ou false si pas trouvé
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
			$this -> STOP($i, '0129');
		} elseif (isset($mot['Erreur'])) {
			$this -> STOP($i, $mot['Erreur']);
		} else {
			$longueur += $mot['Longueur'];
			$i += $mot['Longueur'];
			$nom = $mot['Mot']; // terme ou mot système
			$ret['Mot'] = $nom;
			// il faut repérer si on doit chercher la méthode sur l'objet ou sur le parent SynerGaïa (si @)
			// cette façon n'est pas très sûre ici car on ne connait pas encore l'objet sur lequel on travaille...
			if ($mot['Type'] === 'MotSysteme') {
				$nomp = '@' . $nom;
				$nomf = $nom;// méthode sysème ? (methode)
			} else {
				$nomp = $nom;
				$nomf = 'FN_' . $nom;// méthode spécifique de l'application ? (FN_methode)
			}
			$this -> methode = $mot['Methode']; // garder la méthode du dernier résultat
			// sauter les espaces
			$n = $this -> sauterEspaces($i);
			$longueur+= $n;
			$i += $n;
			// traiter les paramètres éventuels
			$prm = '';
			if ($i < $ifin) {
				// fonctions particulières où un paramètre peut-être une branche
				if ($nomp === '@Clic') {
					$noparam = 1;
				} else {
					$noparam = 0;
				}
				// traduire les paramètres
				$param = $this -> Parametres($i,$noparam);
				$btn = '';
				if ($param !== false) {
					$noformule = $this -> noformule;
					if ($param === false) {
						$this -> STOP($i, '0130');
					} elseif (isset($param['Erreur'])) {
						$this -> STOP($i, '0131', $param['Erreur']);
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
						$this -> STOP($i, '0132');
						$longueur += $param['Longueur'];
					}
				}
			}
			if ($prm === '') {$prm = '()';}
			// @todo vérifier que le $nom est acceptable pour PHP
			if($prm === '()') {
				$php.= $p.'	$ret = SG_Operation::execFonctionSansParametre($this, $o,\'' . $nomp . '\',\'' . $nomf . '\',\'' . $nom . '\');//014';
			} else {
				// avec paramètres sous la forme $prm = '($p14,$p16,$p19,$p24,$p27)';
				$php.= $param['php'];
				if(isset($param['code'])) {
					// si on a un paramètre qui peut être une branche, il faut mettre à jour son code pour l'exécution
					$php.= $p.'	$p' . $param['Detail'][$noparam - 1] . ' -> code = \'' . $param['code'] . '\';';
				}
				$php.= $p.'	$prm = array' . $prm . ';//015';
				$php.= $p.'	$ret = SG_Operation::execFonctionAvecParametres($o,\''.$nom.'\',\''.$nomf.'\',\''.$nomp.'\',$prm);';
			}
			$ret['Longueur'] = $longueur;
		}
		$ret['php'] = $php;
		$this -> position = $pDebut + $longueur;
		return $ret;
	}

	/**
	 * FonctionInitiale : début d'instruction
	 * recherche soit un motsystème ou terme seul, avec paramètres (c'est à dire pas de valeur directe)
	 * si mot n'est pas une méthode de SG_Rien, c'est un new d'objet
	 * 
	 * @since 1.0.6
	 * @version 2.6 test gravité 0234 ; test SG_Parametre ; si @Bouton, parm 2 peut être une branche
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return array extrait (avec les bornes) ou false si pas trouvé
	 */	
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
			$this -> STOP($i, '0133');
		} elseif (isset($mot['Erreur'])) {
			$this -> STOP($i, $mot['Erreur']);
		} else {
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
			$nbprm = 0;
			// dans le cas d'un bouton, le paramètre 2 peut être une branche, donc o traite à part
			$param = array('php' => '');
			// traitement des paramètres éventuels
			// résultat php dans $param['php'], liste des paramètres dans $prm sous la forme 'p1,p2,etc'
			if ($i < $ifin) {
				$prmbranche = 0;
				// cas où un paramètre peut être une branche
				if ($nomsyst === '@Bouton') {
					$prmbranche = 2;
				}
				$param = $this -> Parametres($i, $prmbranche);
				if ($param !== false) {
					if (isset($param['Erreur'])) {
						$ret['Erreur'] = new SG_Erreur('0135', $param['Erreur']);
					} elseif (isset($param['Detail'])) {
						foreach ($param['Detail'] as $noformule) {
							if(strlen($prm) > 1) {
								$prm .= ',';
							}
							$prm.= '$p' . $noformule;
							$nbprm++;
						}
						$longueur += $param['Longueur'];
					} else {
						$ret['Erreur'] = new SG_Erreur('0136');
						$longueur += $param['Longueur'];
					}
				}
			}
			$php.= $param['php'];
			// calcul de la fonction initiale (php pour fonction dans $f)
			if ($nom === 'Arreter') { // demander d'interruption d'un processus
				$php.= $p . self::Arreter($i);
			} elseif ($nom === 'EtapeEnCours') { // cas particulier de @EtapeEnCours ?
				$php.= $p.'$ret = new SG_Texte($this -> etape);//016';
			} elseif (method_exists('SG_Rien',$nom)){ // soit c'est une méthode synergaia de SG_Rien ?
				$php.= $p.'$ret = SG_Rien::' . $nom . '(' . $prm . ');//018';
			} elseif (method_exists('SG_Rien','FN_' . $nom)){ // soit c'est une méthode applicative de SG_Rien ?
				$php.= $p.'$rien = new SG_Rien();$ret = $rien -> FN_' . $nom . '(' . $prm . ');//060';
			} else {
				$f = '';
				if($nom === '') { // cas impossible
					$this -> STOP($i, '0137');
				} else {
					if($mot['Type']==='MotSysteme') { // soit new d'une classe d'objet SynerGaïa
						$c = 'SG_' . $nom; // SG_Dictionnaire::getClasseObjet('@' . $nom);
						$nom = '@' . $nom;
					} else { // soit c'est un type d'objet de l'application
						$c = $nom;
					}
					if (class_exists($c)) { // une classe existe : on la privilégie car c'est l'initialisation d'un objet standard
						if ($prm === '' or SG_Dictionnaire::isObjetDocument($nom) === false) {
							$php.= $p.'$ret = new ' . $c . '(' . $prm . ');//021';
							if (isset($param['code'])) {
								$php.= $p.'$ret -> code = \'' . $param['code'] . '\';';
							}
						} else { // si c'est un document, le ou les paramètres sont des codes
							if ($nbprm === 1) { // recherche d'un document par code
								// si SG_Parametre, on crée si pas trouvé
								if ($nom === SG_Parametre::TYPESG) {
									$creer = 'true';
								} else {
									$creer = 'false';
								}
								$php.= $p.'$ret = $_SESSION[\'@SynerGaia\'] -> sgbd -> getObjetParCode(\'\', \'' . $nom . '\', ' . $prm . ', true, ' . $creer . ');//019';
							} elseif ($nbprm === 2) { // recherche d'une collection entre 2 codes
								$php.= $p.'$ret = $_SESSION[\'@SynerGaia\'] -> sgbd -> getCollectionObjetsParCode(\'' . $nom . '\', ' . $prm . ');//077';
							} else {
								$this -> STOP($i, '0240', $nom); // Trop de paramètres
							}
						}
					} else {
						if ($prm === '') { // pas de paramètres : sans doute propriété locale de la formule (variable)
							if (substr($nomsyst,0,1) === '$') { // paramètre de la formule
								$novariable = intval(substr($nomsyst,1)) - 1;
								$php.= $p.'$ret = new SG_Texte();//079';
								$php.= $p.'if (isset($contexte[' . $novariable . '])) {';
								$php.= $p.'	$ret = $contexte[' . $novariable . '];';
								$php.= $p.'} elseif (isset($o -> contexte[' . $novariable . '])) {';
								$php.= $p.'	$ret = $o -> contexte[' . $novariable . '];';
								$php.= $p.'} elseif (isset($this -> proprietes[\'' . $nomsyst . '\'])) {';
							} else {
								$php.= $p.'$ret = new SG_Erreur(\'0251\', \'' . $nomsyst .'\');//078';
								$php.= $p.'if (isset($this -> proprietes[\'' . $nomsyst . '\'])) {';
							}
							$php.= $p.'	$ret = $this -> proprietes[\'' . $nomsyst . '\'];';
							if ($nomsyst !== $nom) {
								$php.= $p.'} elseif (isset($this -> proprietes[\'' . $nom . '\'])) {';
								$php.= $p.'	$ret = $this -> proprietes[\'' . $nom . '\'];';
							}
							// fin
							$php.= $p.'}';// else {' . $f .$p.'}'.PHP_EOL;	
						} else {
							$this -> STOP($i, '0174', $nom); // classe inexistante.. on ne voit pas
						}
					}
					$php.= $p.'if (is_null($ret) or $ret === \'\') {$ret = new SG_Texte();} //069';
					$php.= $p.'if (getTypeSG($ret) === \'@Erreur\' and $ret -> gravite >= SG_Erreur::ERREUR_STOP){$this->STOP($ret);}';
				}
			}
			$ret['Longueur'] = $longueur;
			$ret['php'] = $php;
		}
		$this -> position = $pDebut + $longueur;
		return $ret;
	}

	/**
	 * Parametres : instructions séparées par des virgules donnant un tableau de formules
	 * Le n° de paramètre indique un paramètre qui peut être une branche (@Bouton, @Clic, etc)
	 * 
	 * @since 2.1 ajout
	 * @version 2.6 SG_Formule->preparer ; $pNoParm
	 * @param integer $pDebut début de la partie de phrase à analyser
	 * @param integer $pNoParm n° du paramètre qui peut être une branche (par défaut 0 : aucun)
	 * @return array la première fonction reconnue ou erreur
	 */
	function Parametres($pDebut, $pNoParm = 0) {
		$this -> testerBoucle('Parametres', $pDebut);
		$i = $pDebut + $this -> sauterEspaces($pDebut);
		$ret = false;
		$longueur = 0;
		if ($this -> phrase[$i] === '(') {
			$i++;
			$ifin = strlen($this -> phrase) - 1;
			$detail = array();
			$erreur = '';
			$ret = array('Type' => 'Parametres', 'Mot' => '', 'php'=>'', 'Longueur' => 99999);
			$php = '';
			$noparm = 1;
			while ($i <= $ifin) {
				// skip spaces
				$n = $this -> sauterEspaces($i);
				$longueur+= $n;
				$i += $n;
				$c = $this -> phrase[$i];
				if ($c === ')') {
					break; //fin des paramètres
				} elseif ($c === '|' or $c === '>') {
					break; // fin de bloc : on laisse traiter par la fonction appelante (normalement c'est une erreur !!)
				} elseif ($c === ',') {
					$mot = array('Type' => 'Virgule', 'Mot' => ',', 'Longueur' => 1 );
					$noparm++;
				} elseif ($c === ';') {
					$mot = array('Type' => 'PointVirgule', 'Mot' => ';', 'Longueur' => 1 );
				} else {
					if ($noparm === $pNoParm) {
						// le paramètre peut être une branche à exécuter
						$mot = $this -> Branche($i,',');
						if ($mot instanceof SG_Erreur) {
							break;
						} elseif (isset($mot['Erreur'])) {
							$erreur = $mot['Erreur'];
							break;				
						}
						if (isset($mot['php'])) {
							$this -> appels[] = $mot['php'];
						}
						if (isset($mot['code']) and $mot['code'] !== '') {
							$ret['code'] = $mot['code'];
						}
					} else {
						$mot = $this -> SuiteInstructions($i, ','); // chaque instruction s'arrête sur une virgule
						if (getTypeSG($mot) === '@Erreur') {
							break;
						} elseif (isset($mot['Erreur'])) {
							$erreur = $mot['Erreur'];
							break;				
						}
						// prépare la fonction elle-même
						if ($mot['php'] !== '') {
							$txt = $mot['php'];
							$this -> fonctions['fn' . $this -> noformule] = $txt;
						}
					}
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
						$php.= PHP_EOL . '		if (isset($contexte)) {//020';
						$php.= $p. ' = SG_Formule::preparer(\'' . $this -> noformule . '\',\'' . $this -> methode . '\', $this, $objet, $contexte);';
						$php.= PHP_EOL . '		} else {';
						$php.= $p. ' = SG_Formule::preparer(\'' . $this -> noformule . '\',\'' . $this -> methode . '\', $this, $objet);';
						$php.= PHP_EOL . '		}';
						if (isset($mot['titre'])) {
							$php.= $p .' -> titre = \''.self::addslashes($mot['titre']).'\';';
						}
					}
					$detail[] = $this -> noformule;
					$this -> noformule++;
				}
				$longueur += $mot['Longueur'];
				$i += $mot['Longueur'];
			}
			if ($c !== ')') { // mal terminé
				$this -> STOP($i, '0169', strval($pDebut) . ' : trouvé ' . $c);
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

	/**
	 * BlocParentheses : recherche la phrase entre parenthèses
	 * @since 1.0.6
	 * @version 2.1 param 2
	 * @param integer $pDebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	 * @param string $pContenu type de contenu ('B' branche 'C'
	 * @return array|boolean le test ou false si pas atrouvé de parenthèse fermante
	 */
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

	/** 
	 * Crée une classe SG_Operation à partir d'un modèle opération compilé
	 * Le résultrat est stocké dans ../var de l'application
	 * @since 2.1
	 * @version 2.3 addslashes -> str_replace ; try
	 * @param string $pNom
	 * @param string $pFormule
	 * @param string $pPHP
	 * @param string $pPrefixe
	 * @return boolean|SG_Erreur le retour de l'enregistrement du modèle d'opération
	 */
	function compilerOperation($pNom = '', $pFormule = '', $pPHP = '', $pPrefixe = 'MO_') {
		$this -> erreur = '';
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
			$php.= '	function traiterSpecifique($etape = \'\', $typeres=\'\') {//023';
			$php.= $p . '$this -> rien = new SG_Rien();';
			$php.= $p . '$ret = false;';
			$php.= $pPHP;
			if( $this -> erreur !== '') {
				$php.= $p.'$resultat = new SG_Erreur(\'' . str_replace('\'', '\\\'', SG_Texte::getTexte($this -> erreur)) . '\');//027';
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

	/**
	 * Crée une classe spécifique à l'objet
	 * @since 2.1 ajout
	 * @version 2.3 try
	 * @version 2.6 err 0294
	 * @param (string) $pNom : nom de l'objet SynerGaïa à compiler
	 */
	function compilerObjet($pNom = '') {
		$ret = false;
		$this -> erreur = '';
		$lieuerreur = 'objet';
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
				$lieuerreur = $nompropriete;
				if ($propriete === '') {
					$ret = new SG_Erreur('0162', $nompropriete);
				} else {
					$type = $propriete -> getValeur('@Modele', '');
					$titre = $propriete -> getValeur('@Titre', '');
					$vd = $propriete -> getValeur('@ValeurDefaut', '');
					if($vd !== '') {
						$txt = $this -> compilerPhrase($vd, $nompropriete . '_defaut');
						if ($this -> erreur === '') {
							$php.= PHP_EOL . '	/** //032' . PHP_EOL;
							$php.= '	* @formula : ' . $vd . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur(SG_Texte::getTexte($this -> erreur) . ' sur ' . $nompropriete);
						}
					}
					$vp = $propriete -> getValeur('@ValeursPossibles', '');
					if($vp !== '') {
						$txt = $this -> compilerPhrase($vp, $nompropriete . '_possibles');
						if ($this -> erreur === '') {
							$php.= PHP_EOL . '	/** //033' . PHP_EOL;
							$php.= '	* @formula : ' . $vp . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur(SG_Texte::getTexte($this -> erreur) . ' sur ' . $nompropriete);
						}
					}
				}
			}
			// lire les méthodes et chercher les formules d'action
			$methodes = $_SESSION['@SynerGaia'] -> sgbd -> getMethodesObjet($nom);
			foreach($methodes -> elements as $methode) {
				if (! isset($methode['nom'])) {
					$this -> STOP(0,$nom,'méthode incomplète sur ' . $nom);
				} else {
					$action = SG_Dictionnaire::getActionMethode($nom, $methode['nom'], true);
					$lieuerreur = $methode['nom'];
					if($action !== '') {
						$txt = $this -> compilerPhrase($action, 'FN_' . $methode['nom']);
						if (getTypeSG($txt) === '@Erreur') {
							$ret = $txt;
						} elseif ($this -> erreur === '') {
							$php.= PHP_EOL . '	/** //034' . PHP_EOL;
							$php.= '	* @formula : ' . $action . PHP_EOL;
							$php.= '	**/' . PHP_EOL;
							$php.= $txt;
						} else {
							$ret = new SG_Erreur(SG_Texte::getTexte($this -> erreur) . ' sur ' . $methode['nom']);
						}
					}
				}
			}
			// fonctions associées
			$p = PHP_EOL . '		';
			foreach ($this -> fonctions as $id => $texte) {
				$php.= PHP_EOL.'	function ' . $id .'($objet, $contexte = null) {//046';
				$php.= $texte;
				$php.= $p . 'return	$ret;//035'.PHP_EOL.'	}' . PHP_EOL;
			}
			$php.= PHP_EOL . '}//036' . PHP_EOL . '?>';
			$ret = file_put_contents(SYNERGAIA_PATH_TO_APPLI . '/var/' . $nom . '.php', $php);
		} catch (Exception $e) {
			$cpl = $this -> catchErreur($e);
			if (! $this -> erreur instanceof SG_Erreur) {
				$this -> erreur = new SG_Erreur('0294', $lieuerreur . ' : ' . $cpl);
			} else {
				$this -> erreur -> trace = $cpl;
			}
			$ret = $this -> erreur;
		}
		return $ret;
	}

	/**
	* BlocAccolades : recherche la phrase entre parenthèses
	* @since 1.0.6
	* @param integer $pDebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	* @return array|SG_Erreur le test ou false si pas trouvé de parenthèse fermante
	**/
	function BlocAccolades($pDebut = 0) {
		return $this -> blocFerme($pDebut, '{', '}');
	}

	/**
	 * BlocCrochets : recherche la phrase entre crochets
	 * @since 1.0.6
	 * @param integer $pDebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	 * @return array|SG_Erreur le test ou false si pas truvé de parenthèse fermante
	 */
	function BlocCrochets($pDebut = 0) {
		return $this -> blocFerme($pDebut, '[', ']');
	}

	/**
	 * ChaineEntreQuotes : extrait une chaine entre quotes
	 * @since 1.0.6
	 * @version 2.6 escape \"
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return array extrait ou null si pas trouvé du tout, ou Erreur si pas 2 double quotes
	 */
	function ChaineEntreQuotes($pDebut = 0) {
		$this -> testerBoucle('ChaineEntreQuotes', $pDebut);
		$ret = false;
		$i = $pDebut;
		if ($this -> phrase[$pDebut] === '"') {
			$ret = array('Type'=>'ChaineEntreQuotes', 'Mot' => '', 'Longueur' => 0);
			$ok = false;
			$ipos = $i + 1;
			while (! $ok) {
				$ipos = strpos($this -> phrase, '"', $ipos);
				if ($ipos === false) {
					break;
				}
				if (substr($this -> phrase, $ipos - 1, 1) === '\\') {
					$ipos++;
				} else {
					$ok = true;
				}
			}
			if ($ipos !== false) {
				$txt = substr($this -> phrase, $i + 1, $ipos - $i - 1);
				$ret['Mot'] = $txt;
				$ret['Longueur'] = strlen($txt) + 2;
				$ret['php'] = 'new SG_Texte(\'' . self::addslashes(str_replace('\\"', '"', $txt)) . '\');//038';
			} else {
				$this -> STOP($i, '0138');
			}
			$this -> position += $ret['Longueur'];
		}
		return $ret;
	}
	
	/**
	 * Etiquette : recherche soit un chevron seul soit |étiquette>
	 * @since 1.0.6
	 * @version 2.4 contrôles avant enregistrement
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
				// fin de l'étiquette
				$ipos = strpos($this -> phrase, '>', $i);
				if ($ipos !== false) {
					$txt = substr($this -> phrase, $i+1, $ipos - $i-1);
					$ret['Longueur'] = strlen($txt) + 2;
					$ret['Mot'] = $txt;
					// rechercher si contrôle avant enregistrement
					$ictl = strrpos($txt, ':');
					if ($ictl !== false) {
						$ret['Mot'] = substr($txt,$ictl+1);
						$ctl = $this -> Controles($pDebut + 1); // fonction d'exécution des contrôles
						$this -> fonctions['ctrl_etape_' . ($this -> noetape - 1)] = $ctl['php'];
					}
				} else {
					$this -> STOP($i, '0139');
				}
			}
			$this -> position += $ret['Longueur'];
		}
		return $ret;
	}

	/**
	 * blocFerme : recherche la phrase entre parenthèses
	 * @since 1.0.6
	 * @param integer $pDebut début dans la phrase du compilateur (1er caractère au-delà du 1er quote)
	 * @param string $pCaracOuvrant : caractère ouvrant le bloc
	 * @param string $pCaracFermant : caractère fermant le bloc
	 * @param string $pTypeContenu : type de contenu (si vide, tout type jusqu'au caractère fermant)
	 * 			'B' branche, 'P' paramètres, 'C' collection
	 * @return null si pas trouvé, le mot si ok,  ou Erreur si pas trouvé de caractère fermant ou intérieur invalide
	 */
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
					$ret['Erreur'] = new SG_Erreur('0140', $pCaracFermant);
				}
			} elseif ($pTypeContenu === 'B' or $pTypeContenu === 'P' or $pTypeContenu === 'C') {
				$mot = $this -> tester($i, $pTypeContenu);
				$ret = $mot;
			} else {
				$ret['Erreur'] = new SG_Erreur('0141', $pTypeContenu);
			}
			// est-il correct ?
			if($mot !== null) {
				if(getTypeSG($mot) === '@Erreur') {
					$this -> STOP($i, $mot);
				} elseif (isset($mot['Erreur'])) {
					$this -> STOP($i, $mot['Erreur']);
				} else {
					$ret['Mot'] = $mot['Mot'];
					$ret['Longueur'] = $mot['Longueur'] + 2; // ajouter les caractères de parenthèse
					if(isset($mot['php'])){
						$ret['php']=$mot['php'];
					}
				}
			}
		} else {
			$ret['Erreur'] = new SG_Erreur('0142', $pCaracOuvrant);
		}
		return $ret;
	}

	/**
	 * Terme : extrait un nom (suite de caractères alphanumériques)
	 * @since 1.0.6
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return string terme extrait ou boolean false si pas trouvé
	 */
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
			$this -> STOP($idebut, '0143', substr($this -> phrase,$idebut,$ifin));
		} else {
			$ret['Mot'] = $mot;
			$ret['Longueur'] = strlen($mot);
			$ret['Methode'] = $mot;
		}
		$this -> position = $pDebut + strlen($mot);
		return $ret;
	}
	
	/**
	 * Nombre : extrait un nombre (suite de chiffres)
	 * @since 1.0.6
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return array|SG_Erreur nombre extrait ou boolean false si pas trouvé
	 */
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
			$ret['Erreur'] = new SG_Erreur('0144');
		} else {
			$ret = array('Type'=>'Nombre', 'Mot'=>$signe . $mot, 'Valeur'=>$mot, 'Signe'=>$signe, 'Longueur' => $longueur);
			$ret['php'] = 'new SG_Nombre(' . $signe . $mot . ');//039';
		}
		return $ret;
	}

	/**
	 * Traite un mot du langage de base de SynerGaïa qui doit commencer par @, puis alphanumérique
	 * @since 1.0.6
	 * @version 2.1.1 isMotSimple
	 * @param integer $pDebut début dans la phrase du compilateur
	 * @return array|SG_Erreur le mot ou vide
	 */
	function MotSysteme($pDebut = 0) {
		$this -> testerBoucle('MotSysteme', $pDebut);
		$mot = '';
		$idebut = $pDebut;
		$ifin = strlen($this -> phrase) - 1;
		$ret = array('Type'=>'MotSysteme', 'Mot' => $mot, 'Longueur' => 0);
		if ($this -> phrase[$idebut] === '@') {
			for ($i = $idebut + 1; $i <= $ifin; $i++) {
				$c = $this -> phrase[$i];
				if ($this -> isMotSimple($c) === false) {
					break;
				} else {
					$mot .= $c;
				}
			}
			if ($mot === '') {
				$this -> STOP($idebut,'0145', substr($this -> phrase,$idebut,$ifin));
			} else {
				$ret['Longueur'] = strlen($mot) + 1;
				$ret['Methode'] = '@' . $mot;
				$ret['Mot'] = $mot;
			}
		} else {
			$ret['Erreur'] = '@';
		}
		$this -> position += $ret['Longueur'];
		return $ret;
	}

	/**
	 * isAlphameric : Le caractère est une lettre, un chiffre, tiret bas, dollar, arrobase
	 * @since 1.0.6
	 * @param string $c le caractè_re à analyser
	 * @return boolean True or False
	 */
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

	/**
	 * Teste si le caractère $c est bien $cas.
	 * @since 1.0.6
	 * @param string $c caractère à tester
	 * @param string $cas caractère de référence
	 * @param string $type 
	 * @return array si ok tableau composé de $type, $c, sinon Erreur
	 */	
	function Caractere($c, $cas, $type) {
		$mot = array('Type' => $type, 'Mot' => $c, 'Longueur' => 1);
		if($c !== $cas) {
			$ret['Erreur'] = new SG_Erreur('0146', $cas);
		}
		return $mot;
	}

	/**
	 * dans la phrase du compilateur, sauter les espaces, les tabulations et les retours de ligne
	 * @since 1.0.6
	 * @version 2.6 sauter tab (ord(9))
	 * @param integer $pDebut début de la phrase à étudier
	 * @return integer la longueur des espaces trouvés
	 */	
	function sauterEspaces($pDebut = 0) {
		$ret = 0;
		$ifin = strlen($this -> phrase);
		for ($i = $pDebut; $i < $ifin; $i++) {
			$c = $this -> phrase[$i];
			if ($c === ' ' or $c === '\n' or ord($c) === 13 or $c === '\t' or $c === PHP_EOL or ord($c) === 0 or ord($c) === 12 or ord($c) === 9) {
				$ret++;
			} else {
				break;
			}
		}
		return $ret;
	}

	/**
	 * pour éviter les boucles en compilation
	 * @todo faire disparaitre ce besoin...
	 * @since 2.1
	 * @param string $ou lieu où est fait le test
	 * @param integer $pDebut indice où on en est dans la phrase
	 * @throws SG_Erreur 0147
	 * @return boolean
	 */
	function testerBoucle($ou = '', $pDebut) {
		$ret = false;
		$this -> niveau ++;
		if ($this -> niveau > $this -> limiteBoucle) {
			$this -> STOP(0, new SG_Erreur('0147', ': vers ' . $ou)); 
		}
		return $ret;
	}

	/**
	 * Compile une phrase pour les formules de valeurs possibles, valeurs par défaut ou méthodes
	 * @since 2.1 ajout
	 * @version 2.3 $contexte
	 * @param string $pPhrase la phrase à compiler
	 * @param string $pNom le nom de la fonction qui exécute le php
	 * @return array|SG_Erreur
	 */
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
			$ret = $mot['Erreur'];
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
				$ret.= PHP_EOL.'		return SG_Operation::controlerResultat($resultat,$ret);//041';
				$ret.= PHP_EOL.'	}';
			}
		}
		return $ret;
	}

	/**
	 * recherche un titre à l'instruction (commence par un ':'). 
	 * -> position est mis à jour sur le caractère suivant
	 * @since 2.1 ajout
	 * @param integer $pDebut indice où démarrer dans la phrase
	 * @return boolean|string soit null (pas de titre), soit titre (sans le ':')
	 */
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

	/**
	 * détecte une collection dans une parenthèse (suite d'instructions séparées par des virgules) : ce ne sont pas des paramètres Parametres()
	 * @since 2.1 ajout
	 * @param integer $pDebut : la position du curseur
	 * @param null|array $pPremierResultat : premier résultat à entrer dans la collection
	 * @return : la création d'une collection d'objets sinon false
	 */
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
				$this -> STOP($i, $mot);
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

	/**
	 * Compile un obejt système (au moment des changement de version notamment ou de l'ajout de méthodes spécifiques
	 * Crée un fichier Php complémentaire spécifique à l'objet stocké dans ../var
	 * @since 2.1 ajout
	 * @version 2.3 $contexte ; try
	 * @param string $pNom : nom de l'objet SynerGaïa à compiler
	 * @return string|SG_Erreur 
	 **/
	function compilerObjetSysteme($pNom = '') {
		$this -> erreur = '';
		try {
			// si nécessaire, lire l'objet
			if (is_string($pNom)) {
				$objet = SG_Dictionnaire::getDictionnaireObjet($pNom);
				$nom = $pNom;
			} else {
				$objet = $pNom;
				$nom = $objet -> getValeur('@Code','inconnu');
			}
			$this -> titre = 'Objet système : ' . $nom;
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
							$ret = new SG_Erreur(SG_Texte::getTexte($this -> erreur) . ' sur ' . $nompropriete);
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
							$ret = new SG_Erreur(SG_Texte::getTexte($this -> erreur) . ' sur ' . $nompropriete);
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
						$ret = new SG_Erreur(SG_Texte::getTexte($this -> erreur) . ' sur ' . $methode['nom']);
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

	/**
	 * isMotSimple : Le caractère est une lettre, un chiffre, tiret bas
	 * @since 2.1.1 ajout
	 * @param string $c le caractère à analyser
	 * @return boolean True or False
	 */
	function isMotSimple($c) {
		$c = strtolower($c);
		if (($c >= 'a' and $c <= 'z') or ($c >= '0' and $c <= '9') or ($c === '_')) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * récupère une exception de compliation et la prépare pour l'affichage
	 * @since 2.3 ajout
	 * @version 2.6 correction test fin de phrase
	 * @param $e : exception récupérée
	 * @return string html à afficher pour documenter l'erreur
	 * @todo tester erreur de dépassement de fin de phrase
	 */
	function catchErreur ($e) {
		if (get_class($e) === 'Exception') {
			$erreur = $e -> erreur;
		} else {
			$erreur = @unserialize($e -> getMessage());
		}
		$ret = '';
		if ($this -> positionerreur > 0) {
			$ipos = $this -> positionerreur;
		} else {
			$ipos = $this -> position;
		}
		if($ipos > strlen($this->phrase)) {
			$ipos = strlen($this->phrase) - 1;
		}
		$ideb = $ipos - 15;
		if($ideb < 0) {
			$ideb = 0;
		}
		$ifin = $ipos + 10;
		if($ifin >= strlen($this->phrase)) {
			$ifin = strlen($this->phrase) - 1;
		}
		$ret .= 'dans ' . $this -> titre;
		if (isset($this ->phrase[$ipos])) {
			$c = $this ->phrase[$ipos];
		} else {
			$c = '???';
		}
		$ret .= '<br> vers ' . $ipos . ' :  ...' . substr($this -> phrase, $ideb, $ipos - $ideb) . '<span style="font-weight:bold;color:#000;">' . $c . '</span>'. substr($this -> phrase, $ipos + 1, $ifin - $ideb) . '...';
		$ret .= '<br> dans la phrase :' . $this -> phrase;
		$ret .= '<br> Erreur lancée par le compilateur à la ligne ' . $e -> getLine();
		tracer();
		if(isset($_SESSION['debug'])) {
			$ret .= '<br><br> === TRACE ===<br>' . implode('<br>',$_SESSION['debug']);
		}
		return $ret;
	}

	/**
	 * ajout des formules de controle de l'étape. Chaque test se fait en deux parties séparées par une virgule :
	 * la fonction de test, le message envoyé si test est vrai
	 * @since 2.4 ajout
	 * @param integer $pDebut indice du début du contrôle dans la phrase
	 * @return array|SG_Erreur
	 */
	function Controles ($pDebut) {
		$this -> testerBoucle('Controles', $pDebut);
		$i = $pDebut + $this -> sauterEspaces($pDebut);
		$ret = false;
		$ifin = strlen($this -> phrase) - 1;
		$detail = array();
		$erreur = '';
		$longueur = 0;
		$ret = array('Type' => 'Controles', 'Mot' => '', 'php'=>'', 'Longueur' => 99999);
		$php = '';
		$phpAppel = ''; // phrase de l'appel du test
		$phase = 'test';
		while ($i <= $ifin) {// on alterne sur test, erreur, test, erreur, etc. jusqu'au ':' ou '>' ou fin
			// skip spaces
			$n = $this -> sauterEspaces($i);
			$longueur+= $n;
			$i += $n;
			$c = $this -> phrase[$i];
			if ($c === ':' or $c === '>') { // Suite Instruction 'avale' le ':' comme titre de colonnes @TODO corriger
				break; //fin des tests
			} elseif ($c === ',') {
				$mot = array('Type' => 'Virgule', 'Mot' => ',', 'Longueur' => 1 );
			} elseif ($c === ';') {
				$mot = array('Type' => 'PointVirgule', 'Mot' => ';', 'Longueur' => 1 );
			} elseif($phase === 'test') {
				// compilation du test
				$no = '$p' . $this -> noformule;
				$mot = $this -> SuiteInstructions($i, ','); // chaque instruction s'arrête sur une virgule
				if (getTypeSG($mot) === '@Erreur') {
					$this -> STOP($i, $mot);
				} elseif (isset($mot['Erreur'])) {
					$this -> STOP($i, $mot['Erreur']);
				} elseif (isset($mot['Longueur']) and $mot['Longueur'] === 0) {
					$this -> STOP($i, '0209');
				} else {
					// écriture de la fonction de test
					$fn = true;
					$txt = '';
					$no = '$p' . $this -> noformule;
					$p = PHP_EOL . '		' . $no;
					$phpAppel.= PHP_EOL . '		$res = $this -> fn' . $this -> noformule . '($objet);//073';
					$phpAppel.= PHP_EOL . '		if ($res -> estErreur()) {' . PHP_EOL . '			return $res;';
					$phpAppel.= PHP_EOL . '		} elseif ($res -> estVrai()) {' . PHP_EOL . '			return new SG_Erreur("", ';// sans code, seule info
					if(isset($mot['Erreur'])){
						$ret['Erreur'] = $mot['Erreur'];
						break;
					} else {
						// prépa de la @Formule du paramètre pour la fonction de test
						$php.= PHP_EOL.$p. ' = new SG_Formule();//072';
						$php.= $p . ' -> fonction = \'fn' . $this -> noformule . '\';'; // fonction d'exécution
						$php.= $p . ' -> methode = \'.' . $this -> methode . '\';';
						$php.= $p . ' -> objet = $objet;' . $p . ' -> setParent($this);';
						$php.= $p . ' -> operation = $this;';
						$php.= PHP_EOL . '		if (isset($contexte)) {'. $p . ' -> contexte = $contexte;}';
						// stocke la fonction elle-même
						$txt = $mot['php'];
						$this -> fonctions['fn' . $this -> noformule] = $txt;
					}
				}
				$detail[] = $this -> noformule;
				$this -> noformule++;
				$phase = 'msg';			
			} elseif($phase === 'msg') {
				// compilation du message d'erreur
				$no = '$p' . $this -> noformule;
				$mot = $this -> SuiteInstructions($i, ','); // chaque instruction s'arrête sur une virgule
				if (getTypeSG($mot) === '@Erreur') {
					break;
				} elseif (isset($mot['Erreur'])) {
					$this -> STOP($i, $mot['Erreur']);
				} elseif (isset($mot['Longueur']) and $mot['Longueur'] === 0) {
					$this -> STOP($i, '0210');
				} else {
					// écriture de la fonction de message
					$fn = true;
					$txt = '';
					$no = '$p' . $this -> noformule;
					$p = PHP_EOL . '		' . $no;
					$phpAppel.= '$this -> fn' . $this -> noformule . '(' . $no . '));//074' . PHP_EOL . '		} else {'; // fin de la formule d'appel
					$phpAppel.= PHP_EOL . '			$ret = \'\';' . PHP_EOL . '		}';
					if(isset($mot['Erreur'])){
						$ret['Erreur'] = $mot['Erreur'];
						break;
					} else {
						// préparation de la @Formule du paramètre pour la fonction de message
						$php.= PHP_EOL.'	'.$p. ' = new SG_Formule();//075';
						$php.= $p . ' -> fonction = \'fn' . $this -> noformule . '\';'; // fonction d'exécution
						$php.= $p . ' -> methode = \'.' . $this -> methode . '\';';
						$php.= $p . ' -> objet = $objet;' . $p . ' -> setParent($this);';
						$php.= $p . ' -> operation = $this;';
						$php.= PHP_EOL . '		if (isset($contexte)) {';
						$php.= $p . ' -> contexte = $contexte;';
						$php.= PHP_EOL . '		}';
						// stocke la fonction elle-même
						$txt = $mot['php'];
						$this -> fonctions['fn' . $this -> noformule] = $txt;
					}
					$detail[] = $this -> noformule;
					$this -> noformule++;
					$phase = 'test';
				}
			}
			$longueur += $mot['Longueur'];
			$i += $mot['Longueur'];
		}
		if ($c !== ':' and $c !== '>') { // mal terminé
			$this -> STOP($i, '0207');
		} elseif ($phase === 'msg') {// manque message
			$this -> STOP($i, '0208');
		} else {
			$i++; // sauter la parenthèse fermante
		}
		$ret['Detail'] = $detail;
		$ret['php'] = $php . $phpAppel;//$php;
		if (isset($mot['Erreur'])) {
			$ret['Erreur'] = $mot['Erreur'];
		}
		if ($ret !== false) {
			$ret['Longueur'] = $i - $pDebut;
		}
		$this -> position = $i;
		return $ret;
	}

	/**
	 * Arrete la compilation à cet endroit
	 * @since 2.4 ajout
	 * @param integer $pPosition
	 * @param string $pCode
	 * @param string $pInfos
	 * @throws Exception
	 */
	function STOP($pPosition, $pCode, $pInfos = '') {
		tracer();
		$this -> positionerreur = $pPosition;
		if ($pCode instanceof SG_Erreur) {
			$this -> erreur = $pCode;
			$code = $pCode -> no;
		} else {
			$this -> erreur = new SG_Erreur($pCode, $pInfos);
			$code = $pCode;
		}
		$e = new Exception($code);
		$e -> erreur = $this -> erreur;
		throw $e;
	}
	
	/**
	 * Phrase pour la FonctionInitiale
	 * @since 2.4 ajout
	 * @version 2.6 cas de SG_Parametre
	 * @param string $pref
	 * @param integer $i
	 * @param string $classe
	 * @param string $nom
	 * @param integer $nbprm
	 * @param string $prm
	 * @return string le paragraphe de php
	 */
	function getFonctionInitiale ($pref, $i, $classe, $nom, $nbprm, $prm) {
		$ret = '';
		if (class_exists($classe)) {
			if ($prm === '' or SG_Dictionnaire::isObjetDocument($nom) === false) {
				$ret = $pref.' $ret = new ' . $classe . '(' . $prm . ');//076';
			} else { // si c'est un document, le ou les paramètres sont des codes
				if ($nbprm === 1) { // recherche d'un document par code
					// si SG_Parametre, on crée si pas trouvé
					if ($nom === SG_Parametre::TYPESG) {
						$creer = 'true';
					} else {
						$creer = 'false';
					}
					$ret = $pref.'$ret = $_SESSION[\'@SynerGaia\'] -> sgbd -> getObjetParCode(\'\', \'' . $nom . '\', ' . $prm . ', true, ' . $creer . ');//080';
				} elseif ($nbprm === 2) { // recherche d'une collection entre 2 codes
					$ret = $pref.'$ret = $_SESSION[\'@SynerGaia\'] -> sgbd -> getCollectionObjetsParCode(\'' . $nom . '\', ' . $prm . ');//081';
				} else {
					$this -> STOP($i, '0240', $nom); // Trop de paramètres
				}
				$ret.= $pref.'if (getTypeSG($ret) === \'@Erreur\'){SG_Operation::STOP($ret);}';
			}
		} else {
			$this -> STOP($i, '0174', $nom); // classe inexistante
		}
		return $ret;
	}

	/** 
	 * Calcule un code d'étape (br99_99)
	 * 
	 * @since 2.6
	 * @param integer $pN indice dans la phrase où commencer la traduction
	 * @return false
	 * @todo à terminer
	 **/
	function format99($pN) {
		return substr('00' . strval($pN), -2); 
	}

	/**
	 * Ajoute un slash devant ' si pas déjà fait
	 * 
	 * @since 2.6
	 * @param string $pPhrase la phrase à traiter
	 * @return string la phrase après traitement
	 */
	static function addslashes($pPhrase = '') {
		$ret = str_replace("'", "\'", $pPhrase);
		return $ret;
	}

	/**
	 * Exécuté si on rencontre le mot @Arreter dans une branche
	 * Permet d'arrêter à cet endroit l'étape en cours (éventuellement la boucle en cours)
	 * Si un paramêtre est fourni, il constituera la valeur retournée et affichée
	 * 
	 * @since 2.6
	 * @param any|SG_Formule
	 * @return
	 */
	static function Arreter($pDebut) {
		$ret = 'return SG_Rien::ARRET;//056';
		return $ret;
	}
}
?>
