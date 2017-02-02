<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de traitement des textes HTML
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_HTML_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_HTML_trait.php';
} else {
	trait SG_HTML_trait{};
}
class SG_HTML extends SG_Texte {
    // Type SynerGaia
    const TYPESG = '@HTML';
    public $typeSG = self::TYPESG;
    
	// page lue traduite en tableau simple_html_dom
	public $dom;
	// form de la page
	public $forms = array();
	// 1.3.3 cadre où placer le texte (gauche, centre, droite)
	public $cadre = 'centre';
	// 2.1.1 rupture avec le bloc HTML suivant dans Navigation (affichage)
	public $rupture;
    
    /** 1.1 ajout
    */
    function toHTML() {
		return $this -> texte;
	}
	/** 1.3.1 ajout
	* extrait du texte html la première partie html correspondant aux critères fournis
	* @param suite de triplets (balise, attribut, valeur)
	* @return (SG_HTML) le noeud demandé ou la collection
	**/
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
	/** 1.3.1 ajout pour lire les sites internet (@SiteInternet)
	* décompose les composant principaux de la page HTML
	**/
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
	}
	/** 1.3.1 ajout
	* indique si l'élément est inclus dans une 'form'
	* @param (simple_html_dom_node) élément à analyser
	* @return (boolean) true si un 'parent' est un '<form>'
	**/
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
	/** 1.3.1 ajout
	* Recherche de tous les champs de l'element
	* @param (simple_html_dom_node) élément à examiner
	* @return (array) tableau des champs
	**/
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
	/** 1.3.1 ajout
	* Enlève un bloc
	* @param (string) Identifiant
	**/
	function Enlever($pNode) {
	}
	/** 1.3.1 ajout
	* Compare les champs fournis entre l'@HTML et le @Document. Le modèle d'objet est fourni par le type de la formule côté @Document
	* @param (string) Document de référence
	* @param (suite de @formules) (formule donnant un texte nom de champ de @HTML, formule sur @Document)*, etc
	* @return @Collection(@Texte) la collection des champs HTML différents
	**/
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
						$res = new SG_Texte($arghtml -> formule . '=|' . $rh . '| : ' . $argdoc -> formule . '=|' . $rd . '|');
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
	/** 1.3.1 ajout
	* recherche un champ précis (permet de résoudre le problème des noms de champs non réglementaires)
	* @param (string ou @Texte) $pNom : nom du champ
	* @return (@Texte) valeur du champ
	**/
	function Champ($pNom = '', $pDefaut = '') {
		$nom = SG_Texte::getTexte($pNom);
		if(isset($this -> proprietes[$nom])) {
			$ret = $this -> proprietes[$nom];
		} else {
			$ret = new SG_Texte($pDefaut);
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* met à jour un champ du texte HTML
	* @param (@Texte) nom du champ
	* @param (any) valeur (qui sera transformée en texte
	* @return (@HTML) ceci
	**/
	function MettreValeur($pNomChamp = '', $pValeur = '') {
		if(getTypeSG($pValeur) === '@Formule') {
			$valeur = $pValeur -> calculer();
		} else {
			$valeur = $pValeur;
		}
		$this -> proprietes[SG_Texte::getTexte($pNomChamp)] = $valeur;
		return $this;
	}
	/** 1.3.1 ajout
	* extraire les options d'un champ select sous forme d'une formule SynerGaïa
	* S'il y a une traduction, la valeur retournée sera xxxxx|X
	* @param (string) balise du champ à extraire
	* @return (string) formule donnant la collection des valeurs
	**/
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
	/** 1.3.1 Ajout ; 2.0 parm ; 2.1 $this
	* Affiche le teste HTML brut
	* @return (string) html
	**/
	function Afficher($pOption = '') {
		return $this;
	}
	/** 1.3.1 ajout
	* Ajoute une classe pour un effet de décoration. L'action se fait sur l'objet @HTML lui-même
	* @param (@Texte) $pClasse classe d'effet à ajouter
	* @param (@VraiFaux) $pAutour entourer par une nouvelle <span> (défaut : @Vrai)
	* @return (@HTML) l'objet après modification
	**/
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
	/** 1.3.1 ajout
	* Extrait la valeur d'un attribut du premier neoud (-> dom)
	* @param (@Texte) code de l'attribut
	* @return (@Texte) valeur de l'attribut ou vide
	**/
	function Attribut($pCode = '') {
		$code = SG_Texte::getTexte($pCode);
		$ret = '';
		if($code !== '') {
			if(is_object($this -> dom)) {
				$ret = $this -> dom -> $code;
			}
		}
		return new SG_Texte($ret);
	}
	/** 1.3.1 ajout ; 1.3.3 $cadre ; 2.3 classe
	* Permet de remplir la partie 'adroite'. Cadre est précisé
	* @param (SG_Formule) formule donnant ce qu'il faut placer à droite
	**/
	function ADroite () {
		$this -> cadre = 'droite';
		$this -> texte = '<div class="adroite noprint">' . $this->texte . '</div>';
		return $this;
	}
	/** 1.3.3 ajout
	* Permet de remplir la partie 'agauche'. Le Cadre est précisé
	* @param (SG_Formule) formule donnant ce qu'il faut placer à gauche
	**/
	function AGauche () {
		$this -> cadre = 'gauche';
		$this -> texte = '<div class="agauche noprint">' . $this->texte . '</div>';
		return $this;
	}
	/* 2.00 ajout
	* Mettre en forme un lien Internet
	* @param (SG_Texte) lien visé
	* @param (SG_Texte) cible : par défaut, même onglet ; 
	* @return (SG_HTML) balise <a> href
	**/
	function LienVers($pLien = '', $pCible = '') {
		$lien = SG_Texte::getTexte($pLien);
		$cible = strtolower(SG_Texte::getTexte($pCible));
		$target = 'target="_blank"';
		if ($cible === '') {
			$this -> texte = '<a ' . $target . ' href="' . $lien . '" class="lien">' . $this -> texte . '</a>';
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
			$this -> texte = '<span class="lien" title="' . $lien . '" ' . $target .'>' . $this -> texte . '</span>';
		}
		return $this;
	}
	/** 2.3 ajout
	* ajoute un cadre autour du texte
	* @param $pCSS : phrase CSS style à mettre
	* @retrun @HTML : ceci
	**/
	function Cadre ($pCSS = '') {
		$css = SG_Texte::getTexte($pCSS);
		if ($css !== '') {
			$css = 'style="' . $css . '"';
		}
		$this -> texte = '<div class="cadre" ' . $css . '>' . $this -> texte . '</div>';
		return $this;
	}
	/** 2.3 ajout
	* fonction utilitaire pour compacter les tableaux html à raison d'un objet html par cadre.
	* Cette fonction est appelée à la fin de chaque instruction.
	* si le résultat n'est que dans un seul cadre, le retur est un html sinon c'est encore un tableau par cadre
	* @param $pResultat : résultat déjà calculé auquel on agrège éventuellement le dernier calculé
	* @param $pEntree : soit un objet SG_Texte qui devient html, soit SG_HTML qui reste tel quel, soit un tableau d'HTML.
	* @return : soit un SG_HTML pour un seul cadre, soit un tableau d'HTML pour plusieurs cadres
	**/
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
	// 2.1.1. complément de classe créée par compilation
	use SG_HTML_trait;
}
?>
