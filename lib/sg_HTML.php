<?php
/** SYNERGAIA fichier contenant la gestin de l'objet @HTML */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_HTML_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_HTML_trait.php';
} else {
	/**
	 * Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1
	 */
	trait SG_HTML_trait{};
}

/**
 * Classe SynerGaia de traitement des textes HTML
 * 
 * @version 2.6 __constuct multiparamètres ; -> Vers
 * @uses simple_html_dom.php
 */
class SG_HTML extends SG_Texte {
	/** string Type SynerGaia '@HTML' */
	const TYPESG = '@HTML';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
   
	/** string page lue traduite en tableau simple_html_dom */
	public $dom;

	/** array form de la page
	 * @todo est-ce encore utile ?
	 */
	public $forms = array();

	/**
	 * @var string cadre où placer le texte (gauche, centre, droite)
	 * @since 1.3.3
	 */
	public $cadre = 'centre';

	/** boolean rupture avec le bloc HTML suivant dans Navigation (affichage)
	 * @since 2.1.1 */
	public $rupture;
	
	/** boolean indicateur de champ en saisie
	 * @since 2.5
	 */
	public $saisie = false;
	
	/**
	 * Construction de l'objet ; si plusieurs paramètres, on concatène les objets.
	 * Si un seul paramètre SG_Collection, on concatene le texte de chaque élément
	 * Si tableau, on concatene les textes
	 * 
	 * @since 2.6
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_HTML est créé. Si plusieurs, ils sont concaténés.
	 */
	function __construct($pQuelqueChose = null) {
		$txt = '';
		if (func_num_args() <= 1) {
			if ($pQuelqueChose instanceof SG_Collection) {
				foreach ($pQuelqueChose -> elements as $elt) {
					$txt.= SG_Texte::getTexte($elt);
				}
				$this -> texte = $txt;
			} else {
				parent::__construct($pQuelqueChose);
			}
		} else {
			$args = func_get_args();
			foreach ($args as $arg) {
				if (is_array($arg)) {
					foreach ($arg as $a) {
						$txt.= SG_Texte::getTexte($a);
					}
				} elseif($arg instanceof SG_Formule) {
					$a = $arg -> calculer();
					$txt.= SG_Texte::getTexte($a);
				} else {
					$txt.= SG_Texte::getTexte($arg);
				}
			}
			$this -> texte = $txt;
		}
	}
	
	/**
	 * Extrait le texte de l'HTML
	 * 
	 * @todo remplcaer par toString() ??
	 * @since 1.1 ajout
	 */
	function toHTML() {
		return $this -> texte;
	}

	/**
	 * Extrait du texte html la première partie html correspondant aux critères fournis
	 * 
	 * @since 1.3.1 ajout
	 * @param string|$pBalise suite de triplets (balise, attribut, valeur)
	 * @return SG_Collection|SG_HTML le noeud demandé ou la collection des noeuds
	 */
	function Extraire($pBalise = '') {
		$ret = new SG_Collection();
		if($this -> texte !== '') {
			$balise = new SG_Texte($pBalise);
			$balise = $balise -> texte;
			if ($balise !== '') {
				$liste = $this -> dom -> find($balise);
				foreach($liste as $element) {
					$h = new SG_HTML($element -> outertext);
					$h -> analyser();
					$ret -> elements[] = $h;
				}
			}
		}
		// si tableau vide ou à un seul élément, SG_HTML au lieu de collec 
		switch (sizeof($ret -> elements)) {
			case 0 : 
				$ret = new SG_HTML('');
				break;
			case 1 : 
				$ret = $ret -> elements[0];
				break;
		}
		return $ret;
	}

	/**
	 * Décompose les composant principaux de la page HTML pour lire les sites internet (@SiteInternet)
	 * 
	 * @since 1.3.1 ajout
	 * @version 2.6 return
	 * @return SG_HTML $this
	 */
	function analyser() {
		$this -> forms = array();
		if ($this -> texte !='') {
			// décoder la page reçue
			$this -> dom = str_get_html($this -> texte);
			// extraire les 'form'
			$forms = $this -> dom -> find('form');
			foreach($forms as $form) {
				// recherche 'action' et 'method'
				$action = $form -> action;
				$method = 'GET';
				if(isset($form -> method)) {
					$method = strtoupper($form -> method);
				}
				// extraire les champs (cachés ou non)
				$champs = $this -> getChamps($form);
				$this -> forms[] = array('action' => $action, 'method' => $method, 'champs' => $champs);
			}
			// recherche de tous les champs
			$this -> proprietes = array_merge($this -> proprietes, $this -> getChamps($this -> dom));
		}
		return $this;
	}

	/**
	 * indique si l'élément est inclus dans une 'form'
	 * 
	 * @since 1.3.1 ajout
	 * @param simple_html_dom_node $pNode élément à analyser
	 * @return boolean true si un 'parent' est un '<form>'
	 */
	function estInclusDansForm($pNode) {
		if(is_null($pNode)) {
			$node = $this -> dom;
		} else {
			$node = $pNode;
		}
		$ret = false;
		while (!is_null($node)) {
			$node = $node -> parent();
			if(is_null($node)) {
				break;
			} else {
				if($node -> tag == 'form') {
					$ret = true;
					break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Recherche de tous les champs de l'element
	 * 
	 * @since 1.3.1 ajout
	 * @param simple_html_dom_node $node élément à examiner
	 * @return array tableau des champs
	 */
	function getChamps($node) {
		$nodechamps = $node -> find('input');
		$nodechamps = array_merge($nodechamps, $node -> find('textarea'));
		$nodechamps = array_merge($nodechamps, $node -> find('select'));
		$champs = array();
		foreach($nodechamps as $nodechamp) {
			if(is_null($nodechamp -> name)) {
				echo '<br>ID : |'. $nodechamp -> id .'|<br>';
			}
			if ($nodechamp -> type === 'submit' or $nodechamp -> disabled === 'disabled') {
			} elseif ($nodechamp -> tag === 'select') { // cases à cocher ou choix de valeurs imposées
				$options = $nodechamp -> find('option[selected=selected]');
				$c = array();
				foreach($options as $option) {
					$c[] = new SG_Texte(htmlspecialchars_decode($option -> value));
				}
				switch (sizeof($c)) {
					case 0 : 
						$champs[$nodechamp -> name] = new SG_Texte('');
						break;
					case 1 : 
						$champs[$nodechamp -> name] = new SG_Texte($c[0]);
						break;
					default :
						$champs[$nodechamp -> name] = $c;
				}
			} elseif ($nodechamp -> type === 'checkbox') {
				if ($nodechamp -> checked === 'checked') {
					$champs[$nodechamp -> name] = new SG_VraiFaux(true);
				} else {
					$champs[$nodechamp -> name] = new SG_VraiFaux(false);
				}
			} else {
				if(is_null($nodechamp -> value)) {
					$value = '';
				} else {
					$value = htmlspecialchars_decode($nodechamp -> value);
				}
				$champs[$nodechamp -> name] = new SG_Texte($value);
			}
		}
		return $champs;
	}

	/**
	 * Enlève un bloc (pas opérationnel)
	 * 
	 * @todo à terminer si nécessaire
	 * @since 1.3.1 ajout
	 * @param string $pNode Identifiant
	 */
	function Enlever($pNode) {
	}

	/**
	 * Compare les champs fournis entre l'@HTML et le @Document. Le modèle d'objet est fourni par le type de la formule côté @Document
	 * 
	 * @since 1.3.1 ajout
	 * @param string $pDocument Document de référence
	 * @param SG_Formule : formule donnant un texte nom de champ de @HTML, formule sur @Document)*, etc
	 * @param idem, etc
	 * @return SG_Collection collection des SG_Texte des champs HTML différents
	 */
	function Comparer($pDocument) {
		if(getTypeSG($pDocument) === '@Formule') {
			$doc = $pDocument -> calculer();
		} else {
			$doc = $pDocument;
		}
		$table = get_html_translation_table();
		if($doc -> DeriveDeDocument() -> estVrai()) {
			$ret = new SG_Collection();
			$args = func_get_args();
			for($i=1; $i<sizeof($args) - 1; $i=$i+2) {
				$res = null;
				$arghtml = $args[$i];
				$reshtml = $this -> Champ($arghtml);
				if (sizeof($args) < $i) {
					$res = new SG_Erreur('0055');
				} else {
					$argdoc = $args[$i + 1];
					$resdoc = $argdoc -> calculerSur($doc);
					if(is_null($resdoc)) {
						$resdoc = new SG_Texte('');
					} elseif (!is_object($resdoc)) {
						$resdoc = new SG_Texte('');
					}
						
					// n tente progressivement de trouver l'égalité ...
					$egal = true;
					$rd = trim($resdoc -> toString());
					$rh = trim($reshtml -> toString());
					if (($rd === '' and $rh !== '') or ($rd !== '' and $rh === '')) {
						$egal = false;
					} elseif($rd !== $rh) {
						$rd = str_replace(array(chr(13),chr(10)),array(' ', ' '), trim($resdoc -> toString()));
						if($rd !== $rh) {
							$rd = htmlspecialchars($rd, ENT_QUOTES, 'UTF-8', false);
							if($rd !== $rh) { 
								$egal = false;
							}
						}
					}
					// décidément inégal...
					if (!$egal) {
						$res = new SG_Texte($arghtml -> formule . '=|' . $rh . '| : ' . $argdoc -> texte . '=|' . $rd . '|');
					}
				}
				if($res !== null) {
					$ret -> elements[] = $res;
				}
			}
		} else {
			$ret = new SG_Erreur('0057');
		}
		return $ret;
	}

	/**
	 * Recherche un champ précis (permet de résoudre le problème des noms de champs non réglementaires)
	 * 
	 * @since 1.3.1 ajout
	 * @param string|SG_Texte $pNom : nom du champ
	 * @param any $pDefaut valeur par défaut
	 * @return SG_Texte valeur du champ
	 */
	function Champ($pNom = '', $pDefaut = '') {
		$nom = SG_Texte::getTexte($pNom);
		if(isset($this -> proprietes[$nom])) {
			$ret = $this -> proprietes[$nom];
		} else {
			$ret = new SG_Texte($pDefaut);
		}
		return $ret;
	}

	/**
	 * met à jour un champ du texte HTML
	 * 
	 * @since 1.3.1 ajout
	 * @param SG_Texte $pNomChamp nom du champ
	 * @param any $pValeur valeur (qui sera transformée en texte)
	 * @return SG_HTML ceci
	 */
	function MettreValeur($pNomChamp = '', $pValeur = '') {
		if(getTypeSG($pValeur) === '@Formule') {
			$valeur = $pValeur -> calculer();
		} else {
			$valeur = $pValeur;
		}
		$this -> proprietes[SG_Texte::getTexte($pNomChamp)] = $valeur;
		return $this;
	}

	/**
	 * extraire les options d'un champ select sous forme d'une formule SynerGaïa
	 * S'il y a une traduction, la valeur retournée sera xxxxx|X
	 * 
	 * @since 1.3.1 ajout
	 * @param string $pBalise balise du champ à extraire
	 * @return string formule donnant la collection des valeurs
	 */
	function ExtraireOptions ($pBalise = '') {
		$champ = $this -> Extraire($pBalise);
		if(getTypeSG($champ) !== '@HTML') {
			$ret = new SG_Erreur('0058');
		} elseif ($champ -> texte === '') {
			$ret = new SG_Erreur('0059');
		} else {
			$nodechamp = $champ -> dom -> firstChild();
			if ($nodechamp -> tag === 'select') { // cases à cocher ou choix de valeurs imposées
				$nodeoptions = $nodechamp -> find('option');
				if (sizeof($nodeoptions) === 0) {
					$ret = new SG_Erreur('0060');
				} else {
					$valeurs = array();
					foreach($nodeoptions as $nodeoption) {
						if($nodeoption -> value === $nodeoption -> innertext) {
							$valeurs[] = new SG_Texte($nodeoption -> value);
						} else {
							$valeurs[] = new SG_Texte($nodeoption -> innertext . '|' . $nodeoption -> value);
						}
					}
					$ret = new SG_Collection();
					$ret -> elements = $valeurs;
				}
			} else {
				$ret = new SG_Erreur('0061' . $nodechamp -> tag);
			}
		}
		return $ret;
	}

	/**
	 * Affiche le teste HTML brut
	 *
	 * @since 1.3.1 Ajout
	 * @version 2.1 return $this
	 * @param string $pOption inutilisé
	 * @return (string) html
	 */
	function Afficher($pOption = '') {
		return $this;
	}

	/**
	 * Ajoute une classe pour un effet de décoration. L'action se fait sur l'objet @HTML lui-même
	 * 
	 * @since 1.3.1 ajout
	 * @param string|SG_Texte|SG_Formule $pClasse classe d'effet à ajouter
	 * @param boolean|SG_VraiFaux|SG_Formule $pAutour entourer par une nouvelle <span> (défaut : true)
	 * @return SG_HTML l'objet après modification
	 */
	function Effet($pClasse = '', $pAutour = true) {
		$classe = SG_Texte::getTexte($pClasse);
		if($classe !== '') {
			$autour = SG_VraiFaux::getBooleen($pAutour);
			if ($autour) {
				$this -> texte = '<span class="' . $classe . '">' . $this -> texte . '</span>';
			}
		}
		return $this;
	}

	/**
	 * Extrait la valeur d'un attribut du premier neoud (-> dom)
	 * 
	 * @since 1.3.1 ajout
	 * @version 2.4 met la valeur
	 * @param string|SG_Texte|SG_Formule code de l'attribut
	 * @param string|SG_Texte|SG_Formule valeur de l'attribut
	 * @return SG_Texte valeur de l'attribut ou vide si interro ; $this si maj
	 */
	function Attribut($pCode = '', $pValeur = '') {
		$code = SG_Texte::getTexte($pCode);
		if(func_num_args <= 1) {
			$ret = '';
			if($code !== '') {
				if(is_object($this -> dom)) {
					$ret = new SG_Texte($this -> dom -> $code);
				}
			}
		} else {
			$pval = SG_Texte::getTexte($pValeur);
			$ret = $this;
			if($code !== '') {
				if(is_object($this -> dom)) {
					$ret = new SG_Texte($this -> dom -> $code);
				}
			}
		}
		return $ret;
	}

	/**
	 * Permet de remplir la partie 'adroite'. Cadre est précisé
	 * @since 1.3.1 ajout
	 * @version 2.3 classe
	 * @return SG_HTML $this
	 */
	function ADroite () {
		$this -> cadre = 'droite';
		$this -> texte = '<div class="adroite noprint">' . $this->texte . '</div>';
		return $this;
	}

	/**
	 * Permet de remplir la partie 'agauche'. Le Cadre est précisé
	 * @since 1.3.3 ajout
	 * @version 2.6 sup encadrement par div agauche
	 * @return SG_HTML $this
	 */
	function AGauche () {
		$this -> cadre = 'gauche';
		return $this;
	}

	/**
	 * Mettre en forme un lien Internet
	 * 
	 * @since 2.0 ajout
	 * @param SG_Texte $pLien lien visé
	 * @param SG_Texte $pCible cible : par défaut, même onglet ; 
	 * @return SG_HTML html balise <a> href
	 * @uses SynerGaia.ouvrirLien()
	 */
	function LienVers($pLien = '', $pCible = '') {
		$lien = SG_Texte::getTexte($pLien);
		$cible = strtolower(SG_Texte::getTexte($pCible));
		$target = 'target="_blank"';
		if ($cible === '') {
			$this -> texte = '<a ' . $target . ' href="' . $lien . '" class="sg-lien">' . $this -> texte . '</a>';
		} else {	
			switch ($cible) {
				case 'c' :
					$target = 'onclick=SynerGaia.ouvrirLien(event,"' . $lien . '","centre")';
					break;
				case 'd' :
					$target = 'onclick=SynerGaia.ouvrirLien(event,"' . $lien . '","droite")';
					break;
				case 'g' :
					$target = 'onclick=SynerGaia.ouvrirLien(event,"' . $lien . '","gauche")';
					break;
				case 'p' :
					$target = 'onclick="function (e) {window.open(\''. $lien . '\', \'Popup\', \'scrollbars=1,resizable=1,height=560,width=770\');
						if(e.stopPropagation) {
							e.stopPropagation();
						}
						e.cancelBubble = true;
						return false;}"';
					break;
				default :
					$target = 'target="' . $cible . '"';
					break;
			}
			$this -> texte = '<span class="sg-lien" title="' . $lien . '" ' . $target .'>' . $this -> texte . '</span>';
		}
		return $this;
	}

	/**
	 * Ajoute un cadre autour du texte
	 * 
	 * @since 2.3 ajout
	 * @version 2.6 param code ; css => classe(s)
	 * @param string|SG_Texte|SG_Formule $pTitre : titre du cadre
	 * @param string|SG_Texte|SG_Formule $pClasse : classes à ajouter à mettre
	 * @param string|SG_Texte|SG_Formule $pCode : code du cadre (pour servir de cible)
	 * @return SG_HTML ceci
	 */
	function Cadre ($pTitre = '', $pClasse = '', $pCode = '') {
		$titre = SG_Texte::getTexte($pTitre);
		$classe = SG_Texte::getTexte($pClasse);
		$id = SG_Texte::getTexte($pCode);
		$txt = '<div ';
		if ($id !== '') {
			$txt.= 'id="' . $id . '" ';
		}
		$txt.= 'class="sg-cadre ' . $classe . '">';
		if ($titre !== '') {
			$txt.= '<div class="sg-cadre-titre">' . $titre . '</div>';
		}
		$txt.= $this -> texte . '</div>';
		$this -> texte = $txt;
		return $this;
	}

	/**
	 * fonction utilitaire pour compacter les tableaux html à raison d'un objet html par cadre.
	 * Cette fonction est appelée à la fin de chaque instruction.
	 * si le résultat n'est que dans un seul cadre, le retur est un html sinon c'est encore un tableau par cadre
	 * 
	 * @since 2.3 ajout
	 * @param array|SG_HTML $pResultat : résultat déjà calculé auquel on agrège éventuellement le dernier calculé
	 * @return SG_HTML|array soit un SG_HTML pour un seul cadre, soit un tableau d'HTML pour plusieurs cadres
	 */
	static function condenserResultat($pResultat) {
		if (is_array($pResultat)) {
			$ret = array();
			foreach ($pEntree as $html) {
				if (getTypeSG($html) === '@HTML') {
					if ($html -> cadre === '') {
						$html -> cadre = 'centre';
					}
					if (!isset($ret[$html -> cadre])) {
						$ret[$html -> cadre] = $html;
					} else {
						$ret[$html -> cadre] = $ret[$html -> cadre] -> Concatener($html);
					}	
				}
			}
			if (sizeof($ret) === 1) {
				$ret = array_pop($ret);
			}
		} else {
			$ret = $pResultat;
		}
		return $ret;	
	}

	/**
	 * permet d'ajouter du texte javascript provenant d'une page stockée en couchdb. 
	 * Le texte du javascript est ajouté dans le data d'une div <script>
	 * 
	 * @since 2.5 ajout
	 * @version 2.6 test si document erroné
	 * @param SG_Texte $pDoc : référence d'un champ d'un document contenant le texte du javascript ("objet/code/champ")
	 * @return SG_Erreur|SG_HTML erreur ou $this
	 */
	function Script($pDoc = null) {
		$ret = $this;
		if ($pDoc !== null) {
			$url = SG_Texte::getTexte($pDoc);
			$cle = explode('/', $url);
			if(! isset($cle[2])) {
				$js = new SG_Erreur('0273');
			} else {
				$tr = new SG_TexteRiche();
				$res = $tr -> getURLInterne($url);
				if (getTypeSG($res) === SG_Erreur::TYPESG) {
					$js = $res;
				} elseif (!isset($res['doc'])) {
					$js = new SG_Erreur('0274');
				} else {
					$js = $res['doc'] -> getValeur($cle[2],'');
				}
			}
		}
		if (getTypeSG($js) === '@Erreur') {
			$ret = $js;
		} else {
			$this -> texte.= '<script type="text/javascript">//<![CDATA[';
			$this -> texte.= $js;
			$this -> texte.= '//]]></script>';
		}
		return $ret;
	}

	/**
	 * Place le code HTML dans une fenêtre popup
	 * 
	 * @since 2.6
	 * @return SG_HTML cet objet
	 */
	function Popup() {
		$this -> cadre = 'popup';
		$this -> texte = '<div class="sg-popup-data noprint">' . $this->texte . '</div>';
		return $this;
	}

	/**
	 * Place le code HTML dans le cadre passé en paramètre
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pCible id du cadre ou de la box HTML ou l'html devra être affiché
	 * @return SG_HTML cet objet
	 */
	function Vers($pCible) {
		$this -> cadre = SG_Texte::getTexte($pCible);
		return $this;
	}

	/**
	 * Insère un bouton avec le javascript pour copier le contenu de l'html
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pTitre titre du bouton (sinon "Copier")
	 * @return string l'html modifié associé
	 * @uses JS SynerGaia.copy()
	 */
	function BoutonCopier($pTitre = 'Copier') {
		$id = SG_SynerGaia::idRandom();
		$titre = SG_Texte::getTexte($pTitre);
		$txt = '<button type="button" class="sg-bouton" onclick="SynerGaia.copy(event, null, \'' . $id . '\')" title="Copier dans le presse papier">' . $titre . '</button>';
		$txt.= '<span id="' . $id . '" class="sg-copiable">' . $this -> texte . '</span>';
		$this -> texte = $txt;
		return $this;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_HTML_trait;
}
?>
