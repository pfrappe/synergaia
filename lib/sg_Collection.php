<?php
/** SynerGaia 2.6 Contient la classe SG_Collection */ 
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Collection_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Collection_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_Collection_trait{};
}

/** SG_Collection : Classe de traitement des collections
 * @version 2.6
 * @since 0.0
 */
class SG_Collection extends SG_Objet {
	/** string Type SynerGaia */
	const TYPESG = '@Collection';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** array Liste interne des éléments de la collection */
	public $elements = array();

	/** string SG_Formule bouton à exécuter si clic sur telle ou telle zone (traité comme @Bouton) */
	public $clic;

	/** string SG_Formule pour le choix du style de chaque ligne */
	public $style;
	
	/** string titre de la collection */
	public $titre = '';
	
	/** string curseur dans la collection (voir Premier, Dernier, Suivant, Precedent, Actuel)
	 * @since 2.4
	 */
	public $cursor;
	
	/** boolean indique si la collection est réduite, c'est à dire que les élement représentent des documents réduit à SG_IDDoc
	 * sert notamment pour réduire le @principal dans la sauvegarde de la $_SESSION
	 * @since 2.5
	 */
	public $reduit = false;
	
	/** string id permettant d'avoir plusieurs collections dans la même page (sert pour les liens url)
	 * @since 2.5
	 */
	public $id;

	/** string type des éléments s'il est uniforme
	 * @since 2.6
	 */
	public $type;

	/**
	 * Construction de l'objet : charge un éventuel tableau passé en paramètre puis effectue une initClasseDerive si existe
	 * @since 0.0
	 * @version 2.1 arg non @Formule, array : +simple
	 * @param any $pQuelqueChose valeur à partir de laquelle le SG_Collection est créé
	 */
	public function __construct($pQuelqueChose = null) {
		$this -> id = SG_SynerGaia::idRandom();
		if (!is_null($pQuelqueChose)) {
			if(func_num_args() > 1) {
				$args = func_get_args();
				foreach($args as $arg) {
					if(getTypeSG($arg) === '@Formule') {
						$this -> elements[] = $arg -> calculer();
					} else {
						$this -> elements[]= $arg;
					}
				}
			} else {
				$tmpTypeSG = getTypeSG($pQuelqueChose);
				switch ($tmpTypeSG) {
					case 'integer' :
					case 'double' :
					case 'string' :
						$this -> Ajouter($pQuelqueChose);
						break;
					case 'array':
						// cas où on reconstruit l'objet à partir de sa sauvegarde dans un champ
						$this -> elements = $pQuelqueChose; // 2.1 SG_Rien::creerObjetSynerGaia($pQuelqueChose, -1);
						break;
					case '@Collection' :
						$this -> Concatener($pQuelqueChose);
						break;
					case '@Formule' :
						$this -> Ajouter($pQuelqueChose -> calculer());
						break;
					default :
						$this -> Ajouter($pQuelqueChose);
				}
			}
		}
		// poursuit éventuellement l'initialisation pour les classes dérivées
		if(method_exists($this, 'initClasseDerive')) {
			$this -> initClasseDerive(func_get_args());
		}
		$this -> cursor = key($this -> elements);
	}
		
	/** 1.0.7 ; 1.3.1 vide si tableau plein d'éléments vides ; 1.3.4 $key0
	 * EstVide : @Vrai si aucun élément, @Faux sinon
	 * @return SG_VraiFaux 
	 * @formula : .@Compter.@Egale(0)
	 * level 0 sauf si contient un élément vide
	 **/
	public function EstVide() {
		$vide = false;
		if(sizeof($this -> elements) === 0) {
			$vide = true;
		} else {
			reset($this -> elements);
			$elt0 = $this -> elements[key($this -> elements)];
			if (sizeof($this -> elements) === 1 and ($elt0 === '' or (is_object($elt0) and $elt0-> EstVide() -> estVrai()))){
				$vide = true;
			}
		}
		$ret = new SG_VraiFaux($vide);
		return $ret;
	}
	
	/** 0.1 ; 2.0 plusieurs objets ajoutés à la collection
	 * Ajouter : Ajoute un ou plusieurs objets à la collection
	 * @param indéfini $pQuelqueChose
	 * @return SG_Collection
	 */
	public function Ajouter($pQuelqueChose = null) {
		if(func_num_args() > 1) {
			foreach(func_get_args() as $arg) {
				$this -> Ajouter($arg -> calculer());
			}
		} elseif (!is_null($pQuelqueChose)) {
			if (getTypeSG($pQuelqueChose) === '@Formule') {
				$this -> Ajouter($pQuelqueChose -> calculer());
			} else {
				array_push($this -> elements, $pQuelqueChose);
			}
		}
		return $this;
	}

	/** 0.1
	 * Concatène une autre collection
	 *
	 * @param indéfini $pQuelqueChose
	 * @return SG_Collection
	 */
	public function Concatener($pQuelqueChose = null) {
		if (!is_null($pQuelqueChose)) {
			$tmpType = getTypeSG($pQuelqueChose);
			if ($tmpType === '@Formule') {
				$collection = $pQuelqueChose -> calculer();
			} else {
				$collection = $pQuelqueChose;
			}
			if (getTypeSG($collection) === '@Collection') {
				if (sizeof($this -> elements) === 0) {
					$this->elements = $collection-> elements;
				} else {
					foreach($collection-> elements as $element) {
						$this -> elements[] = $element;
					}
				}
			}
		}
		return $this;
	}

	/** 
	 * Calcule le nombre d'éléments de la collection
	 * @since 0.2
	 * @return SG_Nombre
	 */
	public function Compter() {
		return new SG_Nombre(sizeof($this -> elements));
	}

	/**
	 * Extrait un élément donné
	 * @version 2.5 index peut être un document
	 * @param SG_Nombre|SG_Formule $pNombre numéro de l'élément ou @Texte
	 * @return SG_Objet
	 */
	public function Element($pNombre = null) {
		$ret = null;
		$index = $pNombre;
		// si formule, la traduire
		$type = getTypeSG($index);
		if ($type === '@Formule') {
			$index = $pNombre -> calculer();
			$type = getTypeSG($index);
		}
		// si pas numérique, on essaie un objet
		if (!is_numeric($index)) {
			if ($type === '@Nombre') {
				$index = SG_Nombre::getNombre($index) - 1;
			} elseif ($index -> DeriveDeDocument() -> estVrai()) {
				$index = $index -> getUUID();
			} else {
				$index = SG_Texte::getTexte($index);
			}
		}
		if (isset($this -> elements[$index])) {
			$ret = $this -> elements[$index];
		} else {
			$ret = new SG_Erreur('0006', strval($index + 1)); // index invalide
		}
		return $ret;
	}

	/**
	 * Unique : Supprime les doublons de la collection
	 * @since 1.2
	 * @param any : formule sur chaque élément pour fournir la clé
	 * @return SG_Collection la collection réduite
	 */
	public function Unique() {
		$ret = new SG_Collection();
		$nbElements = $this -> Compter() -> toInteger();
		$uniques = array();
		foreach ($this -> elements as $element) {
			$elementStr = '';
			if(func_num_args() === 0) {
				if (gettype($element) === 'object') {
					$elementStr = $element -> toString();
				} else {
					$elementStr = strval($element);
				}
			} else {
				$formule = func_get_arg(0);
				$formule -> objet = $element;
				$elementStr = $formule -> calculer() -> toString();
			}
			if(!isset($uniques[$elementStr])) {
				$uniques[$elementStr] = $element;
			}
		}
		foreach($uniques as $element) {
			$ret -> elements[] = $element;
		}
		return $ret;
	}

	/**
	 * Extrait les valeurs différentes d'une colonne ou des documents
	 * 
	 * @since 1.1 ajout
	 * @version 2.6 getTexte, getBooleen, calculer formule
	 * @param SG_Texte|SG_Formule $pPropriete champ ou colonne à catégoriser, ou formule à exécuter sur l'élement
	 * @param SG_VraiFaux $pAvecLignes éclater la collection en sous-collections
	 * @return SG_Collection (valeurs trouvées) triées
	 */
	function CategoriserSur($pPropriete = '', $pAvecLignes = false) {
		// préparation des paramètres
		$nom = SG_Texte::getTexte($pPropriete);
		if (is_bool($pAvecLignes)) {
			$avec = $pAvecLignes;
		} else {
			$avec = SG_VraiFaux::getBooleen($pAvecLignes);
		}
		// analyse de la collection d'origine
		$elements = array();
		foreach($this -> elements as $key => $element) {
			// recherche de la categorie
			if (is_array($element)) {
				if (array_key_exists($nom, $element)) {
					$categ = $element[$nom];
				} else {
					$categ = '';
				}
			} else {
				if (getTypeSG($pPropriete) === SG_Formule::TYPESG) {
					$categ = $pPropriete -> calculerSur($element);
				} else {
					$categ = $element -> getValeur($nom, '');
				}
				if (gettype($categ) !== 'string') {
					$categ = SG_Texte::getTexte($categ);
				}
			}
			// mise à jour de la ligne
			if ($avec) {
				if (! array_key_exists($categ, $elements)) {
					$elements[$categ] = new SG_Collection();
				}
				$collecs[$categ] -> elements[$key] = $element;
			} else {
				$elements[$categ] = new SG_Texte($categ);
			}
		}
		ksort($elements);
		$ret = new SG_Collection();
		$ret -> elements = $elements;
		return $ret;
	}
	/** 2.3 simplifie ; 2.4 cursor
	 * Extrait le premier élément de la collection
	 *
	 * @return SG_Objet
	 */
	public function Premier() {
		reset($this -> elements);
		$this -> cursor = key($this -> elements);
		return current($this -> elements);
	}

	/**
	 * Extrait les premiers éléments de la collection
	 *
	 * @param indéfini $pNombre nombre d'éléments
	 * @return SG_Collection
	 */
	public function Premiers($pNombre = 1) {
		$tmpNombre = new SG_Nombre($pNombre);
		$nombre = $tmpNombre -> toInteger();
		$ret = new SG_Collection();
		$i=1;
		foreach ($this -> elements as $key => $element) {
			$ret -> elements[$key] = $element;
			$i++;
			if($i > $nombre) {
				break;
			}
		}
		return $ret;
	}

	/** 2.4 cursor
	 * Extrait le dernier élément de la collection
	 *
	 * @return indéfini
	 */
	public function Dernier() {
		end($this -> elements);
		$this -> cursor = key($this -> elements);
		return current($this -> elements);
	}

	/** 2.4 correction
	 * Extrait les derniers éléments de la collection
	 *
	 * @param indéfini $pNombre nombre d'éléments
	 * @return SG_Collection
	 */
	public function Derniers($pNombre) {
		$tmpNombre = new SG_Nombre($pNombre);
		$nombre = $tmpNombre -> toInteger();
		if ($nombre > sizeof($this -> elements)) {
			$nombre = sizeof($this -> elements);
		}
		$ret = new SG_Collection();
		end($this -> elements);
		for ($i = 0; $i < $nombre; $i++) {
			array_unshift($ret -> elements, current($this -> elements));
			prev($this -> elements);
		}
		return $ret;
	}

	/**
	 * Génère une liste HTML simple des objets de la collection
	 * 
	 * @version 1.3.3 param 1,2
	 * @param string $pID id html du bloc (pour les menus notamment)
	 * @param string $pClasse classe CSS à ajouter
	 * @return string code HTML de la liste (<ul>)
	 */
	public function toHTML($pID = '', $pClasse = '') {
		$ret = $this -> toListeHTML();
		$id = '';
		if ($pID !== '') {
			$id = 'id="'. $pID . '" ';
		}
		$classe = '';
		if ($pClasse !== '') {
			$classe = ' class="' . $pClasse . '"';
		}
		if (SG_ThemeGraphique::ThemeGraphique() !== 'mobilex') {
			$ret = '<ul ' . $id . $classe . '>' . $ret . '</ul>';
		}
		return $ret;
	}

	/**
	 * toListeHTML : liste le texte de chaque élément (sinon la clé)
	 * 
	 * @since 1.0.7
	 * @param boolean $memeVide force l'affichage d'une ligne si elle est vide
	 * @return string html intérieur de liste de texte
	 */	
	function toListeHTML( $memeVide = false) {
		$ret = '';
		$nb = sizeof($this -> elements);
		foreach($this -> elements as $key => $element) {
			$tmpType = getTypeSG($element);
			if (SG_Dictionnaire::isObjetExiste($tmpType) === true) {
				$tmpTexte = $element -> toHTML();
			} elseif ($tmpType === 'array') {
				$tmpTexte = '';
				foreach ($element as $k => $e) {
					$tmpTexte .= $e -> toHTML() . ', ';
				}
			} else {
				$tmpTexte = $element;
			}
			if(($tmpTexte === '' or $tmpTexte === null) and $memeVide) {
				$ret .= '<li style="color:grey;">' . $key . ' : ' . $tmpType . '</li>';
			} elseif (is_object($tmpTexte)) {
				$ret .= '<li>' . $tmpTexte -> toHTML() . '</li>';
			} else {
				$ret .= '<li>' . $tmpTexte . '</li>';
			}
		} 
		return $ret;
	}

	/**
	 * Génère une liste texte simple des UUID des objets de la collection
	 * 
	 * @since 1.0.6
	 * @return string éléments de la liste
	 */
	public function toString() {
		$ret = '';
		$nb = sizeof($this -> elements);
		$i = 0;
		foreach ($this -> elements as &$element) {
			$tmpType = getTypeSG($element);
			if (SG_Dictionnaire::isObjetExiste($tmpType) === true) {
				$tmpTexte = $element -> toString();
			} else {
				if(is_array($element)) {
					$tmp = new SG_Collection($element);
					$tmpTexte = $tmp -> toString();
				} elseif (is_object($element)) {
					$tmpTexte = $element -> toString();
				} else {
					$tmpTexte = $element;
				}
			}
			$ret .= $tmpTexte;
			if ($i < ($nb - 1)) {
				$ret .= ', ';
			}
			$i++;
		}
		return $ret;
	}

	/** 
	 * Récupère les résultats d'une demande d'affichage
	 * @version 2.0 libellé 0108
	 * @param string $format format d'affichage 'preparerTableauSimple'
	 * @param boolean $checkbox cases à cocher
	 * @param boolean $pFiltre avec filtre de recherche
	 * @param array $args liste des formules des colonnes
	 * @return string HTML
	 **/
	function getResultats($format, $checkbox, $pFiltre, $args) {	
		if (sizeof($this -> elements) === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			$parms = $this -> preparerParametres($args);
			$donnees = $this -> preparerDonnees($parms, $checkbox);
			$ret = $this -> $format($donnees, $pFiltre, $checkbox);
		}
		return $ret;
	}

	/** 1.3.0 : preparerTableauSimple (abandon de editablegrid) ; 2.0 libelle 0108
	 * Genere un tableau HTML avec liens des objets de la collection
	 *
	 * @param SG_Formule $pParametres suite de formules des colonnes à afficher
	 *
	 * @return SG_HTML code HTML du tableau
	 */
	public function Afficher($pParametres = null) {
		if (sizeof($this -> elements) === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			$args = func_get_args();
			$ret = $this -> getResultats('preparerTableauSimple', false, true, $args);
		}
		if(getTypeSG($ret) !== '@HTML') {
			$ret = new SG_HTML($ret);
		}
		if ($this -> titre != '') {
			$ret -> texte = '<div class="sg-coll-titre">' . $this -> titre .'</div>' . $ret -> texte;
		}
		$ret -> texte = '<div class="sg-collection">' . $ret -> texte .'</div>';
		if ($ret -> saisie === true) {
			// met à jour la référence au document principal
			$this -> setPrincipal();
		}
		return $ret;
	}

	/**
	 * Fabrique un tableau HTML pour choisir un ou plusieurs éléments
	 * 
	 * @version 2.5 sup preparer tableau pagine
	 * @version 2.6 declarerOperationActive
	 * @todo voir si affectation opération en cours est utile ? mais sinon il y a un bug. A supprimer
	 * @param indefini $pParametres formules des colonnes à afficher
	 * @return SG_HTML code HTML du tableau
	 */
	public function Choisir($pParametres = null) {
		if (sizeof($this -> elements) === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			$args = func_get_args();
			$ret = $this -> getResultats('preparerTableauSimple', true, true, $args, true);
		}
		// met à jour la référence au document principal
		$opEnCours = SG_Pilote::OperationEnCours();
		$opEnCours -> setPrincipal($this);
		SG_Pilote::declarerOperationActive($opEnCours);
		return new SG_HTML($ret);
	}

	/** 
	 * Affiche la collection sous forme de calendrier
	 * 
	 * @since 1.0.7
	 * @version 2.6 clicvide
	 * @param any $pParametres formules des valeurs à utiliser. Dans l'ordre : titre, debut, fin, classe CSS
	 * @return SG_HTML code HTML du tableau (ou SG_Erreur)
	 * @uses JS SynerGaia.initCalendar()
	 */
	public function AfficherCalendrier($pParametres = null) {
		$ret = '';
		// Vérifie que l'on a quelquechose à afficher
		$nbLignes = sizeof($this -> elements);
		if ($nbLignes === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			if (getTypeSG($pParametres) !== '@Formule') {
				$php = false;
				// Traite les parametres passés
				$paramTitre = '.@toHTML';
				$paramDateDebut = '';
				$paramDateFin = '';
				$paramClasse = '';

				$nbParametres = func_num_args();
				for ($i = 0; $i < $nbParametres; $i++) {
					$formule = '';
					$parametre = func_get_arg($i);
					if (getTypeSG($parametre) === '@Formule') {
						$formule = $parametre -> formule;
					} else {
						$formule = new SG_Formule(SG_Texte::getTexte($parametre));
					}
					switch($i) {
						case 0 :
							$paramTitre = $formule -> texte;
							break;
						case 1 :
							$paramDateDebut = $formule -> texte;
							break;
						case 2 :
							$paramDateFin = $formule -> texte;
							break;
						case 3 :
							$paramClasse = $formule -> texte;
							break;
					}
				}

				if ($paramDateFin === '') {
					if ($paramDateDebut === '') {
						$paramDateDebut = '.DateDebut';
						$paramDateFin = '.DateFin';
					} else {
						$paramDateFin = $paramDateDebut;
					}
				}
				$formule = '@Collection('.$paramTitre . ',' . $paramDateDebut . ',' . $paramDateFin.',' . $paramClasse . ')';
			} else {
				$php = true;
				$formule = func_get_args();
				if(sizeof($formule) < 1) {
					$formule[] = '$resultat[]=$objet->Titre;';
				}
				if(sizeof($formule) < 2) {
					$formule[] = '$resultat[]=$objet->Titre;';
				}
				if(sizeof($formule) < 3) {
					$formule[] = '$resultat=$objet->Titre;';
				}
				if(sizeof($formule) < 4) {
					$formule[] = '$resultat=$objet->Titre;';
				}
			}
			$nbColonnes = 4;

			// Identifiant unique du tableau
			$idCalendrier = SG_SynerGaia::idRandom();

			// Encapsule tout le tableau dans un div (le contenu sera rempli par le code javascript
			$ret .= '<div id="calendrier_' . $idCalendrier . '" class="sg-calendrier"></div>' . PHP_EOL;

			// Si on a un contexte avec une étape suivante, propose un lien cliquable sur les entrées
			$lien = false;
			$opencours = SG_Pilote::OperationEnCours();
			if ($opencours -> prochaineEtape != '') {
				$lien = true;
				$debutURL = SG_Navigation::URL_VARIABLE_OPERATION . '=' . $opencours -> reference;
				$debutURL.= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $opencours -> prochaineEtape;
				$debutURL.= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=';
			}
			// début d'url pour un simple clic
			$urlclic = SG_Navigation::URL_VARIABLE_OPERATION . '=' . $opencours -> reference;
			if (is_object($this -> clic)) {
				$urlclic.= '&' . SG_Navigation::URL_VARIABLE_BOUTON . '=' . $this -> clic -> code;
			}
			$urlclic.= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=';

			// code javascript pour chacun des événements :
			$codeJS = '<script type="text/javascript">' . PHP_EOL . 'evenements_' . $idCalendrier . '=[' . PHP_EOL;

			$nbLignes = sizeof($this -> elements);
			foreach ($this -> elements as &$element) {
				// Si on a un élément 'simple' on en fait un élément SynerGaïa
				if (getTypeSG($element) === 'string') {
					$element = new SG_Texte($element);
				}

				// Exécute la formule sur l'élément de la ligne
				if (!$php) {
					$tmpResultatFormule = SG_Formule::executer($formule, $element);
					// On met tous les résultats de la ligne dans un tableau
					$valeursColonnes = array();
					if (getTypeSG($tmpResultatFormule) === '@Collection') {
						$valeursColonnes = $tmpResultatFormule -> elements;
					}
					$nbColonnes = sizeof($valeursColonnes);
				}

				$evnmtTitre = '';
				$evnmtDebut = '';
				$evnmtFin = '';
				$evnmtURL = '';
				$evnmtClasse = '';
				// Et on prend chaque colonne
				for ($numColonne = 0; $numColonne < $nbColonnes; $numColonne++) {
					$tmpContenuCellule = '';
					if (!$php) {
						$valeurColonne = $valeursColonnes[$numColonne];
					} else {
						if ($formule[$numColonne] !== null) {
							$f = $formule[$numColonne];
							if (getTypeSG($f) === '@Formule') {
								$res = $f -> calculerSur($element);
							} else {
								$res = $f;
							}
							if (is_array($res)) {
								$valeurColonne = $res[0];
							} else {
								$valeurColonne = $res;
							}
						} else {
							$valeurColonne = '';
						}
					}

					$tmpType = getTypeSG($valeurColonne);
					switch($numColonne) {
						case 0 :
							// Premiere "colonne" : titre de l'événement
							// Essai d'appel du .toHTML
							if (SG_Dictionnaire::isObjetExiste($tmpType)) {
								$evnmtTitre = $valeurColonne -> toString();
							} else {
								// Sinon ajout directement
								$evnmtTitre = $valeurColonne;
							}
							break;
						case 1 :
							// Deuxième "colonne" : date de début de l'événement
							if (($tmpType !== '@Date') and ($tmpType !== '@DateHeure')) {
								$evnmtDebut = new SG_DateHeure($valeurColonne);
							} else {
								$evnmtDebut = $valeurColonne;
							}
							break;
						case 2 :
							// Troisième "colonne" : date de fin de l'événement
							if (($tmpType !== '@Date') and ($tmpType !== '@DateHeure')) {
								$evnmtFin = new SG_DateHeure($valeurColonne);
							} else {
								$evnmtFin = $valeurColonne;
							}
							break;
						case 3 :
							// 4eme colonne : classe CSS
							$style = SG_Texte::getTexte($valeurColonne);
							if ($style !== 'NULL') {
								$evnmtClasse = $style;
							}
							break;
					}
				}

				$codeJS .= '  {' . PHP_EOL;
				// Si on a un clic sur l'évenement, on propose un submit
				if (! is_null($this -> clic)) {
					$evnmtURL = SG_Navigation::calculerURL($urlclic . $element -> getUUID(), true);
				} elseif ($lien === true) {
					// Si on a un contexte avec une étape suivante, propose un lien cliquable sur le document
					// Empeche un contenu vide (impossible à cliquer)
					if (trim($evnmtTitre) === '') {
						$evnmtTitre = '(vide)';
					}
					// calcul l'url de renvoi de chaque ligne
					$evnmtURL = SG_Navigation::calculerURL( $debutURL . $element -> getUUID());
				}

				$codeJS .= '	  title: \'' . trim(preg_replace('/\s+/', ' ', addslashes($evnmtTitre))) . '\',' . PHP_EOL;
				if ($evnmtURL !== '') {
					$codeJS .= '	  url: \'' . $evnmtURL . '\',' . PHP_EOL;
				}
				$journeeComplete = false;
				if ($evnmtDebut !== '') {
					if (getTypeSG($evnmtDebut) !== '@DateHeure') {
						$journeeComplete = true;
					}
					$annee = date('Y', $evnmtDebut -> getTimestamp());
					$mois = (integer) date('m', $evnmtDebut -> getTimestamp()) ;
					$jour = (integer) date('d', $evnmtDebut -> getTimestamp());
					$heures = (integer) date('H', $evnmtDebut -> getTimestamp());
					$minutes = (integer) date('i', $evnmtDebut -> getTimestamp());
					$codeJS .= '	  start: new Date(' . $annee . ', ' . ($mois - 1) . ', ' . $jour . ', ' . $heures . ', ' . $minutes . '),' . PHP_EOL;
				}
				if ($evnmtFin !== '') {
					if (getTypeSG($evnmtFin) !== '@DateHeure') {
						$journeeComplete = true;
					}
					$annee = date('Y', $evnmtFin -> getTimestamp());
					$mois = (integer) date('m', $evnmtFin -> getTimestamp());
					$jour = (integer) date('d', $evnmtFin -> getTimestamp());
					$heures = (integer) date('H', $evnmtFin -> getTimestamp());
					$minutes = (integer) date('i', $evnmtFin -> getTimestamp());
					$codeJS .= '	  end: new Date(' . $annee . ', ' . ($mois - 1) . ', ' . $jour . ', ' . $heures . ', ' . $minutes . ')';
				}
				if ($journeeComplete === true) {
					$codeJS .= ',allDay: true';
				} else {
					$codeJS .= ',allDay: false';
				}
				if ($evnmtClasse !== '') {
					$codeJS .= ', className:"' . $evnmtClasse.'"';
				} else {
					$codeJS .= ', className:"sg-bleu"';
				}
				$codeJS .= '  },' . PHP_EOL;

			}
			$codeJS .= '];' . PHP_EOL;

			// url de lancement d'un clic sur case vide ($this -> clicvide est rempli dans @Calendrier.@ClicVide): 
			$url = '';
			if ($this -> clicvide !== null) {
				$url = SG_Navigation::URL_VARIABLE_OPERATION . '=' . $opencours -> reference;
				$url.= '&' . SG_Navigation::URL_VARIABLE_BOUTON . '=' . $this -> clicvide -> code;
				$url.= '&' . SG_Navigation::URL_VARIABLE_PARM1 . '=';
				$url = SG_Navigation::calculerURL($url, true);
			}
			// js d'initialisation du calendrier à l'écran
			$codeJS.= 'SynerGaia.initCalendar(\'' . $idCalendrier . '\', \'' . $url . '\',evenements_' . $idCalendrier. ');';
			$codeJS.= '</script>' . PHP_EOL;
			$ret.= $codeJS;
		}
		return new SG_HTML($ret);
	}

	/** 2.1 return SG_HTML
	 * 1.1 SG_Pilote::OperationEnCours() ; 1.3.0 tenir compte @HTML ; 1.3.1 correction sur HTML -> texte, param 2
	 * Genere une liste HTML avec liens des objets de la collection
	 * @param SG_Formule $pFormule : formule de laligne
	 * @param SG_VraiFaux $pAvecURL : avec url ou non
	 * @param SG_Formule|SG_Texte $pURL formule d'url ou url à appliquer sur les lignes
	 * @return string code HTML de la liste (<ul>)
	 */
	public function Lister($pFormule = null, $pAvecURL = true, $pURL = null) {
		$avecurl = SG_VraiFaux::getBooleen($pAvecURL);
		if ($pFormule === null) {
			$formule = null;
		} else {
			if (getTypeSG($pFormule) == 'string') {
				$formule = new SG_Formule($pFormule);
			} else {
				$formule = $pFormule;
			}
		}
		$ret = '<ul>';
		$url = '';
		if ($avecurl) {
			if (is_null($pURL)) {
				$op = SG_Pilote::OperationEnCours();
				if ($op -> prochaineEtape != '') {
					$url = SG_Navigation::URL_PRINCIPALE;
					$url .= '?' . SG_Navigation::URL_VARIABLE_OPERATION . '=' . $op -> reference;
					$url .= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $op -> prochaineEtape;
					$url .= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=';
				}
			} else {
				$url = SG_Texte::getTexte($pURL);
				if ($url === '') {
					$url = 'index.php?m=DocumentConsulter&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=';
				}
			}
		}
		$virg = '';
		foreach ($this -> elements as &$element) {
			$texteLigne = '';
			if (SG_Dictionnaire::isObjetExiste(getTypeSG($element)) === true) {
				// pour permettre un copié / collé propre avec des virgules
				$texteLigne = $virg . $texteLigne;
				if ($virg == '') {
					$virg = '<span style="display:none">,</span>';
				}
				// s'il y a une url...
				if ($url !== '') {
					$texteLigne .= '<a href="' . htmlentities($url . $element -> getUUID(), ENT_QUOTES, 'UTF-8') . '">';
				}
				if ($formule === null) {
					$result = $element;
				} else {
					$result = $formule -> calculerSur($element);
				}
				if ($result instanceof SG_HTML) {
					$texteLigne .= $result -> texte;
				} elseif (is_object($result)) {
					$tmp = new SG_HTML($result -> toHTML());
					$texteLigne .= htmlentities($tmp -> texte);
				} else {
					$texteLigne .= htmlentities($result, ENT_QUOTES, 'UTF-8');
				}
				if ($url !== '') {
					$texteLigne .= '</a>';
				}
			} elseif(is_array($element)) {
				foreach($element as $key => $el) {
					$texteLigne .= '(' . $key . ') ' . $el -> toString() . ', ';
				}
			} else {
				$texteLigne = strval($element);
			}
			$ret .= '<li>' . $texteLigne . '</li>';
		}
		$ret .= '</ul>';
		return new SG_HTML($ret);
	}

	/**
	 * Applique une formule SynerGaia à chacun des éléments de la collection
	 * @since 1.0.6
	 * @version 2.4 @parms
	 * @param string $pFormule formule à appliquer
	 * @return SG_Collection collection après application de la formule
	 */
	public function PourChaque($pFormule = '') {
		$ret = new SG_Collection();
		if (func_num_args() <= 1) {
			$formule = $pFormule;
			if (getTypeSG($pFormule) !== '@Formule') {
				$formule = new SG_Formule($pFormule);
			}
			foreach ($this -> elements as &$element) {
				$ret -> elements[] = $formule -> calculerSur($element, null);
			}
		} else {
			$args = func_get_args();
			$formules = array();
			foreach ($args as $arg) {
				$formule = $arg;
				if (getTypeSG($formule) !== '@Formule') {
					$formule = new SG_Formule($formule);
				}
				$formules[] = $formule;
			}
			foreach ($this -> elements as &$element) {
				$objet = new SG_Objet();
				foreach ($formules as $formule) {
					$res = $formule -> calculerSur($element, null);
					if ($formule -> titre !== '') {
						$objet -> proprietes [$formule -> titre] = $res;
					} else {
						$objet -> proprietes [] = $res;
					}
				}
				$ret -> elements[] = $objet;
			}
		}
		return $ret;
	}

	/** 
	 * Filtre les éléments de la collection selon une formule
	 * @since 1.0.6
	 * @param string $pFormule formule à appliquer
	 * @return SG_Collection collection après filtrage
	 */
	public function Filtrer($pFormule = '') {
		if (getTypeSG($pFormule) === '@Formule') {
			$formule = $pFormule;
		} else {
			$formule = new SG_Formule($pFormule);
		}
		$ret = new SG_Collection();
		$nbElements = sizeof($this -> elements);
		foreach ($this -> elements as &$element) {
			$tmpResultat = $formule -> calculerSur($element);
			$resultat = new SG_VraiFaux($tmpResultat);
			if ($resultat -> estVrai()) {
				$ret -> Ajouter($element);
			}
		}
		return $ret;
	}

	/**
	 * 1.1 test si méthode toString existe ; 1.3.0 $pSens ; 1.3.1 strtolower
	 * Trier les éléments de la collection selon une formule
	 * @since 1.0.6
	 * @version 2.3 améliore sort pour éviter loop
	 * @param string $pFormule formule à appliquer
	 * @param string ou boolean : formule donnant une chaine commençant par "c" ou "d" ou true ou false pour respectivement croissant (defaut) ou descendant
	 * @return SG_Collection collection après tri
	 */
	public function Trier($pFormule = '', $pSens = true) {
		// formule de tri
		$formule = '';
		if ($pFormule instanceof SG_Formule) {
			$formule = $pFormule;
		} else {
			$formule = new SG_Formule($pFormule);
		}
		// sens du tri
		$sens = $pSens;
		$type = getTypeSG($sens);
		if ($type !== 'boolean') {
			if ($type === '@Formule') {
				$sens = $pSens -> calculer();
				$type = getTypeSG($sens);
			}
			if ($type === '@VraiFaux') {
				$sens = $sens -> estVrai();
			} else {
				if ($type !== 'string') {
					$sens = $sens -> toString();
				}
				if ($sens === '') {
					$sens = true;
				} else {
					$sens = (strtolower(substr($sens, 0, 1)) === 'c');
				}
			}
		}
		$sens = $sens ? SORT_ASC : SORT_DESC;
		// Tableau d'index : critères de tri
		$index = array();
		foreach ($this -> elements as $key => &$element) {
			$val = null;
			$type = getTypeSG($element);
			// si un objet ou un tableau de propriétés
			if (SG_Dictionnaire::isObjetExiste($type) or is_array($element)) {
				if ($formule !== '') {
					$tmpVal = $formule -> calculerSur($element);
					$tmpType = getTypeSG($tmpVal);
					switch ($tmpType) {
						case 'string' :
							$val = strtolower(strval($tmpVal));
							break;
						case 'double' :
						case 'integer' :
							$val = floatval($tmpVal);
							break;
						case '@Nombre' :
						case '@Date' :
						case '@DateHeure' :
						case '@Heure' :
							$val = $tmpVal -> toFloat();
							break;
						default :
							if (method_exists($tmpVal, 'toString')) {
								$val = strtolower($tmpVal -> toString());
							} else {
								$info = getTypeSG($tmpVal);
								$err = new SG_Erreur('0012', $info);
								$val = strtolower($err -> getMessage());
							}
							break;
					}
				} else {
					switch ($type) {
						case '@Nombre' :
						case '@Date' :
						case '@DateHeure' :
						case '@Heure' :
							$val = $element -> toFloat();
							break;
						case 'array' :
							$val = 'nombre';//strtolower((string) implode(',', $element));
							break;
						default :
							$val = strtolower($element -> toString());
							break;
					}
				}
			} else {
				switch ($type) {
					case 'string' :
						$val = strtolower(strval($element));
						break;
					default :
						$val = floatval($element);
						break;
				}
			}
			$index[$key] = $val;
		}
		// Tableau de données à trier
		$donnees = $this -> elements;

		// Tri
		$keys = array_keys($index);
		array_multisort($index, $sens, SORT_NATURAL, $keys);

		// Restitution
		$ret = $this -> cloner();
		$ret -> elements = [];
		foreach($keys as $k=> $v) {
			$ret -> elements[] = $donnees[$v];
		}
		return $ret;
	}

	/** 
	 * Exporte une collection dans un fichier JSON
	 * @since 1.0.7
	 * @param indéfini $pNomFichier nom du fichier d'export
	 * @return SG_Texte message de retour
	 */
	public function Exporter($pNomFichier = '') {
		$tmpNomFichier = new SG_Texte($pNomFichier);
		$nomFichier = $tmpNomFichier -> toString();

		if ($nomFichier === '') {
			$nomFichier = 'synergaia_export_' . date('Y_m_d_H_i_s');
		}
		$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nomFichier . '.json';

		// Fabrique le fichier
		$fOut = @fopen($tmpFile, 'w');
		if ($fOut === false) {
			$ret = new SG_Erreur('Le fichier ' . $tmpFile .' n\'a pas pu être ouvert pour l\'écriture!');
		} else {
			// Exporte la collection
			$entete = '{' . PHP_EOL;
			$entete .= ' "' . $nomFichier . '": [' . PHP_EOL;
			fwrite($fOut, $entete);
			$nbElements = $this -> Compter() -> toInteger();
			$i = 0;
			foreach ($this -> elements as &$element) {
				$type = $element -> getValeur('@Type');

				$listeChamps_systeme = array('_id', '@DateModification', '@DateCreation', '@AuteurModification', '@AuteurCreation');
				$listeChamps_dictionnaire = SG_Dictionnaire::getListeChamps($type);
				// Transforme la liste des champs en proprietes
				$listeProprietes = array();
				foreach($listeChamps_dictionnaire as $keyc => $modele) {
					$listeProprietes[] = $keyc;
				}
				$listeChamps = array_merge($listeChamps_systeme, $listeProprietes);

				$tabChamps = array();
				$nbListeChamps = sizeof($listeChamps);
				for ($j = 0; $j < $nbListeChamps; $j++) {
					$codeChamp = $listeChamps[$j];
					$valeurChamp = $element -> getValeur($codeChamp, null);
					if ($valeurChamp !== null) {
						$tabChamps[$codeChamp] = $valeurChamp;
					}
				}

				$contenuObjet = '  ' . json_encode($tabChamps);

				// Ajoute une "," sauf pour le dernier
				if ($i < ($nbElements - 1)) {
					$contenuObjet .= ',';
				}
				$i++;
				
				fwrite($fOut, $contenuObjet . PHP_EOL);
			}
			$pied = ' ]' . PHP_EOL . '}';
			fwrite($fOut, $pied);
			fclose($fOut);

			$info = '';
			switch($nbElements) {
				case 0 :
					$info = '(aucun document)';
					break;
				case 1 :
					$info = '(1 document)';
					break;
				default :
					$info = '(' . strval($nbElements) . ' documents)';
					break;
			}
			$ret = new SG_Texte('L\'export ' . $info . ' a été enregistré dans le fichier : ' . $tmpFile);
		}
		return $ret;
	}

	/**
	 * Détermine si un élément est contenu dans la collection (les _id doivent être identiques)
	 * @since 1.0.7
	 * @version 1.2 : opère si collec de n'importe quoi
	 * @param any $pElement élément à chercher
	 * @return SG_VraiFaux si l'élément est contenu dans la collection
	 */
	public function Contient($pElement = null) {
		$demande = $pElement;
		$typeDemande = getTypeSG($demande);
		if ($typeDemande === '@Formule') {
			$demande = $pElement -> Calculer();
			$typeDemande = getTypeSG($demande);
		}
		// Cherche dans la collection
		$retBool = false;
		$estundoc = method_exists($demande, 'getCodeDocument');
		foreach ($this -> elements as &$element) {
			if (getTypeSG($element) === $typeDemande) {
				if($estundoc) {
					if ($element -> getCodeDocument() === $demande -> getCodeDocument()) {
						$retBool = true;
					}
				} else {
					$retBool = $element -> Egale($demande);
					$retBool = $retBool-> estVrai();
				}
			}
			if($retBool === true) {
				break;
			}
		}
		return new SG_VraiFaux($retBool);
	}

	/**
	 * Calcul de la somme de la collection selon, la formule passée en paramètre.
	 * Si aucun paramètre, on suppose que chaque terme est un @nombre dont on fait la somme
	 * @since 1.0.6
	 * @version 2.2 test formule
	 * @param indéfini $pFormule formule a appliquer à chaque élément.
	 * @return SG_Nombre ou SG_Erreur
	 **/
	public function Somme($pFormule = '.@toFloat') {
		$somme = (double)0;

		$formule = '';
		// pas d'arguments : somme directe des élement
		if(func_num_args() === 0) {
			foreach ($this -> elements as &$elt) {
				if (is_numeric($elt)) {
					$somme+= $elt;
				} elseif (getTypeSG($elt) === '@Nombre') {
					$somme+= $elt -> toFloat();
				}
			}
		} else {
			// somme des résultats de formule
			if (!$pFormule instanceof SG_Formule) {
				$ret = new SG_Erreur('0302', getTypeSG($pFormule));
			} else {
				$formule = $pFormule;
				$nbElements = sizeof($this -> elements);
				foreach ($this -> elements as &$element) {
					if (SG_Dictionnaire::isObjetExiste(getTypeSG($element)) === true) {
						$valeur = $formule -> calculerSur($element);
						if (SG_Dictionnaire::isObjetExiste(getTypeSG($valeur))) {
							$valeur = $valeur -> toFloat();
						} elseif (is_string($valeur)) {
							$valeur = strval($valeur);
						} elseif ($valeur instanceof SG_Erreur) {
							$somme = $valeur;
							break;
						} else {
							$somme = new SG_Erreur('0303');
							break;
						}
						$somme += $valeur;
					} else {
						$somme += floatval($element);
					}
				}
			}
		}
		if (! $somme instanceof SG_Erreur) {
			$ret = new SG_Nombre($somme);
		} else {
			$ret = $somme;
		}
		return $ret;
	}

	/**
	 * Calcul des données pour préparer les graphiques
	 * @since 1.1
	 * @version 2.6 traite si pas args
	 * @param array $args tableau de paramètres SG_Formule par deux : $pFormuleValeur formule a appliquer à chaque élément pour obtenir la valeur
	 *	puis $pFormuleLibelle formule a appliquer à chaque élément pour obtenir le libellé
	 * @return string tableauJSON des données
	 */
	private function calculDonneesPourGraphiques($args) {
		$formuleValeur = '';
		$formuleLibelle = '';
		
		$nbargs = sizeof($args);
		if (isset($args[1]) and $args[1] !== '') {
			if (getTypeSG($args[1]) === '@Formule') {
				$formuleLibelle = $args[1];
			} else {
				$formuleLibelle = new SG_Formule(SG_Texte::getTexte($args[1]));
			}
		}
		// Récupérer la formule de calcul
		$formuleValeur = array();
		for($n = 2; $n < $nbargs; $n++) {
			$pFormuleValeur = $args[$n];
			if (getTypeSG($pFormuleValeur) === '@Formule') {
				$formuleValeur[] = $pFormuleValeur;
			} else {
				$formuleValeur[] = new SG_Formule(SG_Texte::getTexte($pFormuleValeur));
			}
		}
		// Fabrique le tableau des valeurs
		$tableauDonnees = array();
		$i = 0;
		foreach ($this -> elements as $key => &$element) {
			$i++;
			if (is_object($element) or is_array($element)) {
				$nmax = $nbargs - 2;
				// Calcul du libellé de l'abcisse
				if ($formuleLibelle === '') {
					$libelle = '' . $key;
				} else {
					$libelle = $formuleLibelle -> calculerSur($element);
					$type = getTypeSG($libelle);
					if (SG_Dictionnaire::isObjetExiste($type)) {
						if ($type === '@Collection') {
							$libelle = $libelle -> elements[$i - 1] -> toString();
						} else {
							$libelle = $libelle -> toString();
						}
					} elseif (is_array($libelle)) {
						$libelle = strval($libelle[$i - 1]);
					} else {
						$libelle = strval($libelle);
					}
					if($libelle === '') {
						$libelle = strval($i);
					}
				}
				$ligne = array($libelle);
				if(is_array($element)) {
					$nmax = max($nmax, sizeof($element));
					while(sizeof($formuleValeur) < $nmax) {
						$formuleValeur[] = end($formuleValeur);
					}
					$elt = new SG_Document();
					$elt -> proprietes = $element;
				} else {
					$elt = $element;
				}
				// Traitement des paramètres de valeur
				if ($nmax < 0) {
					if (is_object($elt)) {
						$ligne[] = $elt -> toFloat();
					} else {
						$ligne[] = floatval($elt);
					}
				} else {
					for($n = 0; $n < $nmax; $n++) {
						// Calcul de la valeur
						$valeur = $formuleValeur[$n] -> calculerSur($elt);
						if (is_object($valeur)) {
							$ligne[] = $valeur -> toFloat();
						} else {
							$ligne[] = floatval($valeur);
						}
					}
				}
				$tableauDonnees[] = $ligne;
			} else {
				$tableauDonnees[] = array(strval($element), floatval($element));
			}
		}
		// JSON correspondant
		return json_encode($tableauDonnees);
	}

	/** 2.1.1 si $args vide
	 * 1.3.0 transmission correcte des paramètres par défaut ; 1.3.2 @param 1 seule 1ere lettre utilisée, idRandom ; 2.0 libellé 0108
	 * Génération d'un graphique en "histogramme"
	 * @param indéfini de type string, $pFormat : type de courbe à tracer
	 * @param indéfini $pFormuleLibelle formule a appliquer à chaque élément pour obtenir le libellé
	 * @param indéfini $pFormuleValeur formule a appliquer à chaque élément pour obtenir la valeur
	 * @param autres formules pour autres courbes etc.
	 *
	 * @return HTML
	 */
	public function Graphique($pFormat = 'c', $pFormuleLibelle = '', $pFormuleValeur = '.@toFloat') {
		$ret = '';
		// préparer les paramètres
		$format = SG_Texte::getTexte($pFormat);
		$args= func_get_args();//array($format, $pFormuleLibelle, $pFormuleValeur);
		if(!isset($args[0])) {
			$args[0] = $format;
		}
		if(!isset($args[1])) {
			$args[1] = $pFormuleLibelle;
		}
		if(!isset($args[2])) {
			$args[2] = $pFormuleValeur;
		}
		// Vérifie que l'on a quelquechose à afficher
		$nbLignes = sizeof($this -> elements);
		if ($nbLignes === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			// Calcul des données
			$tableauJSON = $this -> calculDonneesPourGraphiques($args);

			// Identifiant unique du graphique
			$idGraphique = 'graphique_' . SG_SynerGaia::idRandom();
			switch (strtolower(substr($format,0,1))) {
				case 'h':
					$classe = 'Histogramme';
					break;
				case 'c':
					$classe = 'Courbes';
					break;
				case 's':
					$classe = 'Secteurs';
					break;
				default:
					$classe = 'Courbes';
					break;
			}
			$ret = '';
			$ret .= '<div id="' . $idGraphique . '" class="graphique graphique' . $classe .'"></div>' . PHP_EOL;
			$ret .= '<script>' . PHP_EOL;
			$ret .= ' var data_' . $idGraphique . ' = ' . $tableauJSON . ';' . PHP_EOL;
			$ret .= ' graphique' . $classe . '("div#' . $idGraphique . '",data_' . $idGraphique . ', true);' . PHP_EOL;
			$ret .= '</script>' . PHP_EOL;
			$_SESSION['libs']['graphiques'] = true;
		}
		$ret = new SG_Graphique($ret);
		return $ret;
	}

	/** 2.0 libellé 0108
	* Génération d'un graphique en "histogramme"
	*
	* @param indéfini $pFormuleValeur formule a appliquer à chaque élément pour obtenir la valeur
	* @param indéfini $pFormuleLibelle formule a appliquer à chaque élément pour obtenir le libellé
	*
	* @return SG_HTML
	*/
	public function GraphiqueHistogramme($pFormuleValeur = '.@toFloat', $pFormuleLibelle = '.@toString') {
		$ret = '';

		// Vérifie que l'on a quelquechose à afficher
		$nbLignes = sizeof($this -> elements);
		if ($nbLignes === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			// Calcul des données
			$tableauJSON = $this -> calculDonneesPourGraphiques(func_get_args());

			// Identifiant unique du graphique
			$idGraphique = 'graphique_' . SG_SynerGaia::idRandom();

			$ret = '';
			$ret .= '<div id="' . $idGraphique . '" class="graphique graphiqueHistogramme"></div>' . PHP_EOL;
			//$ret .= '<script src="js/synergaia-graphiques.js"></script>';
			$ret .= '<script>' . PHP_EOL;
			$ret .= ' var data_' . $idGraphique . ' = ' . $tableauJSON . ';' . PHP_EOL;
			$ret .= ' graphiqueHistogramme("div#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
			$ret .= '</script>' . PHP_EOL;
			$_SESSION['libs']['graphiques'] = true;
		}
		return new SG_HTML($ret);
	}

	/** 2.0 libellé 0108
	 * Génération d'un graphique en "secteurs"
	 *
	 * @param indéfini $pFormuleValeur formule a appliquer à chaque élément pour obtenir la valeur
	 * @param indéfini $pFormuleLibelle formule a appliquer à chaque élément pour obtenir le libellé
	 *
	 * @return SG_Nombre
	 */
	public function GraphiqueSecteurs($pFormuleValeur = '.@toFloat', $pFormuleLibelle = '.@toString') {
		$ret = '';

		// Vérifie que l'on a quelquechose à afficher
		$nbLignes = sizeof($this -> elements);
		if ($nbLignes === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			// Calcul des données
			$tableauJSON = $this -> calculDonneesPourGraphiques(func_get_args());

			// Identifiant unique du graphique
			$idGraphique = 'graphique_' . SG_SynerGaia::idRandom();

			$ret = '';
			$ret .= '<div id="' . $idGraphique . '" class="graphique graphiqueSecteurs"></div>' . PHP_EOL;
			//$ret .= '<script src="js/synergaia-graphiques.js"></script>';
			$ret .= '<script>' . PHP_EOL;
			$ret .= ' var data_' . $idGraphique . ' = ' . $tableauJSON . ';' . PHP_EOL;
			$ret .= ' graphiqueSecteurs("div#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
			$ret .= '</script>' . PHP_EOL;
			$_SESSION['libs']['graphiques'] = true;
		}
		return new SG_HTML($ret);
	}
	/** 1.3.2 idRandom ; 2.0 libellé 0108
	 * Génération d'un graphique en "secteurs"
	 *
	 * @param indéfini $pFormuleValeur formule a appliquer à chaque élément pour obtenir la valeur
	 * @param indéfini $pFormuleLibelle formule a appliquer à chaque élément pour obtenir le libellé
	 *
	 * @return SG_Nombre
	 */
	public function GraphiqueCourbes($pFormuleValeur = '.@toFloat', $pFormuleLibelle = '.@toString') {
		$ret = '';

		// Vérifie que l'on a quelquechose à afficher
		$nbLignes = sizeof($this -> elements);
		if ($nbLignes === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			// Calcul des données
			$json = $this -> calculDonneesPourGraphiques(func_get_args());
			// Identifiant unique du graphique
			$id='graphique_' . SG_SynerGaia::idRandom();
			$ret='<div id="' . $id . '" class="graphique graphiqueCourbes"></div>';
			//$ret.='<script src="js/synergaia-graphiques.js"></script>';
			$ret.='<script>var data_' . $id . '=' . $json . ';graphiqueCourbes("div#' . $id . '",data_' . $id . ');</script>';
		}
		return new SG_HTML($ret);
	}

	/** 1.0.7 ; 2.1 traiter formule php ; 2.4 formules -> texte != '' ; @Effectif => @Nombre ; @param $pLiens, @Docs @Doc
	 * Grouper la collection et calculer un cumul par catégorie
	 *
	 * @param indéfini $pFormuleCategorie
	 * @param indéfini $pFormuleValeur
	 * @param indéfini $pFormuleLigne : formule à exécuter à chaque ligne de catégorie (sur les variables 'c2', 'c3', 'nb' et 'total')
	 * @param SG_VraiFaux $pLiens : tableau des liens vers les documents d'origine (si documents) (par défaut : true)
	 * @return SG_Collection non triée
	 */
	public function GrouperCumuler($pFormuleCategorie = '', $pFormuleValeur = '', $pFormuleLigne = '', $pLiens = true) {
		$ret = new SG_Collection();

		$formuleCategorie = '';
		$formuleValeur = '';

		// Traitement des paramètes
		if (getTypeSG($pFormuleCategorie) === '@Formule') {
			$formuleCategorie = $pFormuleCategorie;
		} else {
			$formuleCategorie = new SG_Formule($pFormuleCategorie);
		}
		if (getTypeSG($pFormuleValeur) === '@Formule') {
			$formuleValeur = $pFormuleValeur;
		} else {
			$formuleValeur = new SG_Formule($pFormuleValeur);
		}
		// liens ?
		if ($pLiens === false) {
			$liens = false;
		} else {
			$liens = SG_VraiFaux::getBooleen($pLiens);
		}
		// si liens, un clic renverra la collection de documents
		if ($liens) {
			$ret -> Clic('.@Docs.@Afficher');
		}
		// Parcourt de la collection pour constituer les catégories
		$cumul = array();
		$nb = sizeof($this -> elements);
		$total = 0;
		$nbcat = 0;
		foreach ($this -> elements as &$element) {
			$tmpType = getTypeSG($element);

			$tmpTitre = '';
			$tmpValeur = 0;
			$tmpDoc = null;
			$tmpCle = '';

			if (SG_Dictionnaire::isObjetExiste($tmpType) or is_array($element)) {
				// Calcul du titre de la catégorie de l'élément en cours
				if ($formuleCategorie -> fonction !== '') {
					$doc = $formuleCategorie -> calculerSur($element);
					$tmpTitre = $doc -> toString();
					$tmpCle = $tmpTitre;
					if ($doc -> DeriveDeDocument() -> estVrai() and !is_null($doc -> doc -> codeDocument)) {
						$tmpDoc = new SG_IDDoc($doc);
						$tmpCle = $doc -> getUUID();
					}
				} else {
					$tmpTitre = $element -> toString();
					$tmpCle = $tmpTitre;
				}
				// Calcul de la valeur de l'élément en cours
				if ($formuleValeur -> fonction !== '') {
					$tmpValeur = $formuleValeur -> calculerSur($element);
					if(getTypeSG($tmpValeur) === '@Nombre') {
						$tmpValeur = $tmpValeur -> toFloat();
					} else {
						$tmpValeur = 0;
					}
				} else {
					$tmpValeur = 1;
				}
			} else {
				$tmpTitre = (string) $element;
				$tmpCle = $tmpTitre;
				$tmpValeur = 1;
			}
			if(isset($cumul[$tmpCle])) {
				$cumul[$tmpCle][0] ++;
				$cumul[$tmpCle][1] += $tmpValeur;
			} else {
				$nbcat ++; 
				$cumul[$tmpCle][0] = 1;
				$cumul[$tmpCle][1] = $tmpValeur;
				$cumul[$tmpCle][4] = $tmpTitre;
			}
			if (! is_null($tmpDoc)) {
				$cumul[$tmpCle][3] = $tmpDoc;
			}
			// cumuler les liens
			if ($liens === true and $element -> DeriveDeDocument() -> estVrai() === true) {
				$id = new SG_IDDoc($element);
				if(!isset($cumul[$tmpCle][2])) {
					$cumul[$tmpCle][2] = array($id);
				} else {
					$cumul[$tmpCle][2][] = $id;
				}
			}
			$total += $tmpValeur;
		}


		$ret = new SG_Collection();
		if (getTypeSG($pFormuleLigne) === 'string') {
			$formuleLigne = new SG_Formule($pFormuleLigne);
		} else {
			$formuleLigne = $pFormuleLigne;
		}
		// calcul des minimum et maximum de catégorie
		$i = 0;
		$mincat = 0;
		$maxcat = 0;
		foreach($cumul as $c) {
			if($i === 0) {
				$mincat = $c[1];
				$maxcat = $c[1];
			} else {
				if($mincat > $c[1]) {
					$mincat = $c[1];
				}
				if($maxcat < $c[1]) {
					$maxcat = $c[1];
				}
			}
		}
		$var = array('@NombreTotal' => new SG_Nombre($nb), '@NombreCategories' => new SG_Nombre($nbcat), '@TotalGeneral' => new SG_Nombre($total), '@MinimumCategories' => new SG_Nombre($mincat), '@MaximumCategories' => new SG_Nombre($maxcat));

		foreach($cumul as $key => $c) {
			if ($formuleLigne -> texte !== '') {
				$tmpFormule = new SG_Formule($formuleLigne -> texte);
				$tmpFormule -> php = $formuleLigne -> php;
				$tmpFormule -> proprietes = $var;
				$tmpFormule -> proprietes['@Nombre'] =  new SG_Nombre($c[0]);
				$tmpFormule -> proprietes['@Total'] =  new SG_Nombre($c[1]);
				$tmpFormule -> proprietes['@Calcul'] = $tmpFormule -> calculer();
				$ligne = $tmpFormule -> proprietes;
			} else {
				$ligne = $var;
				$ligne['@Nombre'] =  new SG_Nombre($c[0]);
				$ligne['@Total'] = new SG_Nombre($c[1]);
				$ligne['@Calcul'] = new SG_Nombre($c[1]);
			}
			$ligne['@Titre'] = $c[4];
			if ($liens === true) {
				$ligne['@Docs'] = new SG_Collection();
				if (isset($c[2])) {
					$ligne['@Docs'] -> elements = $c[2];
				}
			}
			if (isset($c[3])) {
				$objet = $c[3] -> Document();
				$objet -> proprietes = array_merge($objet -> proprietes, $ligne);
			} else {
				$objet = new SG_Objet();
				$objet -> proprietes = $ligne;
			}
			$ret -> elements[$key] = $objet;
		}
		return $ret;
	}
	/**
	* AjouterColonne : ajoute une colonne à une collection de type tableau
	* @since 1.0.7
	* @version 1.3.1 si $pFormule déjà formule garder telle quelle
	* @param string|SG_Texte|SG_Formule $pCode code de l'élément à ajouter
	* @param string|SG_Formule $pFormule formule portant sur l'élément ou suite de formules
	* @return @Collection moi-même
	*/
	function AjouterColonne($pCode = null, $pFormule = '') {
		$code = new SG_Texte($pCode);
		$code = $code -> texte;
		$nbargs = func_num_args();
		$n = 1;
		foreach($this->elements as $key => $element) {
			// préparer l'argument n
			if ($nbargs <= 2) {
				if(getTypeSG($pFormule) === '@Formule') {
					$tmpFormule = $pFormule;
				} else {
					$tmpFormule = new SG_Formule($pFormule -> toString());
				}
			} else {
				if ($n > $nbargs - 1) {
					break;
				}
				if(getTypeSG(func_get_arg($n)) === '@Formule') {
					$tmpFormule = func_get_arg($n);
				} else {
					$tmpFormule = new SG_Formule(func_get_arg($n) -> toString());
				}
				$n++;
			}
			// calculer et ajouter
			if($code === '') {
				if (!is_array($element)) {
					$res = array($element);
				} else {
					$res = $element;
				}
				$res[] = $tmpFormule -> calculerSur($element);
				$this -> elements[$key] = $res;
			} else {
				if ($element -> DeriveDeDocument() -> estVrai()) {
					$this -> elements[$key] -> proprietes[$code] = $tmpFormule -> calculerSur($element);
				} else {
					if (!is_array($element)) {
						$res = array($element);
					} else {
						$res = $element;
					}
					$tmpFormule -> proprietes = $element;
					$res[$code] = $tmpFormule -> calculer();
					$this -> elements[$key] = $res;
				}
			}
		}
		return $this;
	}
	/**
	* MoyenneMobile : ajoute une colonne moyenne mobile à une collection de type tableau
	* @since 1.0.7
	* @param SG_Formule $pFormule : la formule donnant le nombre à moyenner
	* @param string|SG_Formule $pCode code de l'élément à ajouter
	* @param integer|SG_Nombre|SG_Formule $pNombre nombre d'éléments à moyenner (par défaut 3)
	* @return SG_Collection moi-même
	*/  
	function MoyenneMobile($pFormule = '', $pCode = '', $pNombre = 3) {
		$code = new SG_Texte($pCode);
		$code = $code -> texte;
		if ($code === '') {
			$code = '@MoyenneMobile';
		}
		$nb = new SG_Nombre($pNombre);
		$nb = $nb -> toInteger();
		$tab = array();
		$i = 0;
		$tmpFormule = $pFormule;
		if (getTypeSG($tmpFormule) !== '@Formule') {
			$tmpFormule = new SG_Formule($pFormule -> toString());
		}
		foreach($this->elements as $key => &$element) {
			// calcul de la moyenne
			if (is_array($element)) {
				$tmpFormule -> proprietes = $element;
			} else {
				$tmpFormule -> objet = $element;
			}
			$val = $tmpFormule -> calculer();
			if(method_exists($val, 'toFloat')) {
				if(sizeof($tab) < $nb) {
					$tab[] = $val -> toFloat();
				} else {
					$tab[$i] =  $val -> toFloat();
					$i++;
					if($i >= $nb) {$i = 0;}
				}
			}
			if (sizeof($tab) !== 0) {
				$moy = array_sum($tab) / sizeof($tab);
			} else {
				$moy = 0;
			}
			// rangement
			if (is_array($element)) {
				$this -> elements[$key][$code] = new SG_Nombre($moy);
			} else {
				$this -> elements[$key] -> proprietes[$code] = new SG_Nombre($moy);
			}
		}
		return $this;
	}
	/**
	* SoldeProgressif : ajoute une colonne de solde progressif en fonction du tri de la colection à une collection de type tableau
	* @since 1.1
	* @param string|SG_Formule $pCode code de l'élément à ajouter
	* @param integer|SG_Nombre|SG_Formule $pFormule champ ou formule à cumuler
	* @return SG_Collection moi-même
	*/  
	function SoldeProgressif($pCode = '', $pFormule = '') {
		$code = SG_Texte::getTexte($pCode);
		if ($code === '') {
			$code = '@SoldeProgressif';
		}
		$solde = 0;
		foreach($this -> elements as $key => &$element) {
			// calcul de la moyenne
			$valeur = $pFormule -> calculerSur($element);
			if(method_exists($valeur, 'toFloat')) {
				$solde += $valeur -> toFloat();
			}
			// rangement
			if (is_array($element)) {
				$this -> elements[$key][$code] = new SG_Nombre($solde);
			} else {
				$this -> elements[$key] -> proprietes[$code] =  new SG_Nombre($solde);
			}
		}
		return $this;
	}
	
	/**
	* met en forme le tableau des données d'une vue pour un affichage
	* @version 2.5 $index
	* @since 1.0.2
	* @param SG_Formule $pParms : formule des paramètres ou rien
	* @param boolean $pCheckBox : ajouter des checkbox
	* @param boolean $lien : mettre sur la première colonne un lien verds l'étape suivante.
	* @return string html à afficher
	*/  
	function preparerDonnees($pParms, $pCheckBox = false, $lien = true) {
		$ret = '';
		// Vérifie que l'on a quelquechose à afficher
		$nbLignes = sizeof($this -> elements);
		if ($this -> EstVide() -> estVrai()) {
			$ret .= SG_Libelle::getLibelle('0080', false);
		} else {
			$titres = $pParms[0];
			$formules = $pParms[1];
			$nbColonnes = sizeof($formules);
			$lienSurPremiereColonne = false;
			$codeChampHTML = '';
			
			// Si on a un contexte avec une étape suivante, propose un lien cliquable sur la première colonne
			if($lien === true) {
				$op = SG_Pilote::OperationEnCours();
				if ($op -> prochaineEtape != '') {
					$refChamp = $op -> reference . '/@Principal';
					$codeChampHTML = SG_Champ::codeChampHTML($refChamp);
					$lienSurPremiereColonne = true;
				}
			}
			// Fabrication des données du tableau
			// 1. Données des lignes
			$tableauData = array();
			$typesDonnees = array();
			$numLigne = 0;
			foreach ($this -> elements as $key => &$element) {
				$ligne = $this -> getLigne($formules, $element, $pCheckBox, $lienSurPremiereColonne, $numLigne > 0, $codeChampHTML, $key); // 1.3.2
				if ($numLigne === 0) {
					$typesDonnees = $ligne[1];
				}			
				$numLigne++;
				$tableauData[] = array('id' => $numLigne, 'values' => $ligne[0], 'clic' => $ligne[2], 'style' => $ligne[3]);	
			}
			// 2. metadata
			// - Cherche les libellé des colonnes
			$premierElement = $this -> Premier();
			$typePremierElement = getTypeSG($premierElement);
			for ($i = 0; $i < $nbColonnes; $i++) {
				if (!isset($titres[$i])) {
					$titres[$i] = '';
				}
				if ($titres[$i] === '') {
					if (sizeof($formules) > $i) {
						// libellé du dernier élément de la formule
						$tmpCodeColonne = $typePremierElement . $formules[$i] -> methode;
					} else {
						$tmpCodeColonne = $typePremierElement;
					}
					$tmpTitre = SG_Dictionnaire::getLibelle($tmpCodeColonne);
					// Si le titre commence par "@Rien" c'est qu'on n'a pas trouvé de libellé
					if (substr($tmpTitre, 0, strlen('@Rien')) !== '@Rien') {
						if ($tmpTitre !== $tmpCodeColonne) {
							$titres[$i] = $tmpTitre;
						}
					}
				}
			}
			// - case à cocher initiale
			$tableauMetadata = array();
			if ($pCheckBox === true) {
				$tableauMetadata[] = array('name' => 'c0', 'label' => ' ', 'datatype' => 'html', 'editable' => false);
			}
			// - mise en tableau
			if ($typesDonnees !== null) {
				for ($i = 0; $i < $nbColonnes; $i++) {
					if(isset($typesDonnees[$i])) {
						$td = $typesDonnees[$i];
					} else {
						$td = 'html';
					}
					$tableauMetadata[] = array('name' => 'c' . ($i + 1), 'label' => $titres[$i], 'datatype' => $td, 'editable' => false);
				}
			}
			// fin
			$ret = array('metadata' => $tableauMetadata, 'data' => $tableauData);
		}
		// mettre la collection dans les variables de l'opération puisqu'elle peut resservir
		$operation = SG_Pilote::OperationEnCours() -> proprietes[$this -> id] = $this;
		return $ret;
	}

	/**
	* transforme en formule unique le tableau des paramètres formule en entrée
	* @since 1.0.2
	* @version 2.1
	* @version 2.6 instanceof au lieu de getTypeSG
	* @param array $pArgs : tableau des formules (SG_Formule) de paramètres
	* @return array liste des paramètres mis en forme
	*/
	function preparerParametres($pArgs) {
		$formules = array();
		$titres = array();
		$methodes = array();
		$n = 0;
		$php = '';
		if(is_array($pArgs) and isset($pArgs[0]) and $pArgs[0] instanceof SG_Formule and ($pArgs[0] -> php !== '' or $pArgs[0] -> fonction !== '')) {
			// 2.1 interprétation à la volée
			foreach ($pArgs as $parametre) {
				if ($parametre instanceof SG_Formule) {
					$titres[] = $parametre -> titre;
					$methodes[] = $parametre -> methode;
				}
			}
			$formules = $pArgs;
		} else {
			if (sizeof($pArgs) === 0) {
				$f = new SG_Formule();
				$f -> php = '$ret = $objet -> toHTML();';
				$formules[] = $f;
				$titres[] = 'Titre';
				$methodes[] = '.Titre';
			}
			foreach ($pArgs as $parametre) {
				if ($parametre instanceof SG_Formule) {
					$formules[] = $parametre;
				} else {
					$i = strrpos($texte, ':');
					$texte = SG_Texte::getTexte($parametre);
					if ($i !== false) {
						$titres[] = substr($texte, $i + 1);
						$formules[]= substr($texte, 0, $i);
					} else {
						$titres[] = '';
						$formules[]= $texte;
					}
				}
			}
		}
		$ret = array($titres, $formules, $methodes);
		return $ret;
	}

	/**
	 * Etiquettes sur page A4
	 * @since 1.0.3
	 * @version 2.1
	 * @param SG_Nombre|integer $pLignes : nombre de lignes d'étiquettes dans la page
	 * @param SGNombre|integer $pColonnes : nombre de colonnes d'étiquettes dans la page
	 * @param string|SG_Texte $pStyle : style pour le texte
	 * @return string page HTML
	 * @uses JS SynerGaia.print()
	 */
	function AfficherEtiquettes($pLignes = 7, $pColonnes = 2, $pStyle = '') {		
		$ret = '';

		// paramètres
		$args = func_get_args();
		$newArgs = array();
		if (getTypeSG($pLignes) === '@Nombre') {
			$nbLignes = $pLignes -> toInteger();
		} else {
			$nbLignes = new SG_Nombre($pLignes);
			$nbLignes = $nbLignes -> toInteger();
		}
		if ($nbLignes <= 0) { $nbLignes = 7;}
		
		if (getTypeSG($pColonnes) === '@Nombre') {
			$nbColonnes = $pColonnes -> toInteger();
		} else {
			$nbColonnes = new SG_Nombre($pColonnes);
			$nbColonnes = $nbColonnes -> toInteger();
		}
		if ($nbColonnes <= 0) { $nbColonnes = 2;}
		
		$style = new SG_Texte($pStyle);
		$style = $style -> texte;

		// réglage de la page d'étiquettes
		$largeur = 'width:' . (20.5 / $nbColonnes) . 'cm; '; // largeur de page
		$newligne = '<div style="display:table-row;height:' . (32 / $nbLignes) . 'cm;">';

		// boucle sur les étiquettes
		if(sizeof($args) > 2) {
			for ($i = 3; $i < sizeof($args); $i++) {
				$newArgs[] = $args[$i];
			}
			$parms = $this -> preparerParametres($newArgs);
			$donnees = $this -> preparerDonnees($parms, false, false);
			$valeurs = $donnees['data'];
		} else {
			$valeurs = $this -> elements;
		}
		// Identifiant unique du tableau
		$idTable = SG_SynerGaia::idRandom();
		// boucle d'édition
		if (sizeof($valeurs) === 0) {
			$ret .= self::libelle('0108'); // rien à afficher
		} else {
			$ret = '<button class="noprint" onclick="SynerGaia.print()">Imprimer</button>';
			$iLigne = 0;
			$iColonne = 0;
			// création du tableau
			//$ret = '<div style="padding-top:1.5cm;"> </div>'; 
			$ret .= $newligne;
			foreach ($valeurs as $ligne) {
				// fin de ligne ?
				if ($iColonne >= $nbColonnes) {
					$ret .= '</div>';
					$iLigne ++;
					$iColonne = 0;
					// fin de page ?
					if ($iLigne >= $nbLignes) {
						$ret .= '<span class="break"></span><div style="padding-top:1pt;"> </div>';
						$iLigne = 0;
					}
					if ($iLigne < $nbLignes - 1) {
						$ret .= $newligne;
					} else {
						$ret .= '<div style="display:table-row;">';
					}
				}
				// traiter une cellule
				$ret .= '<div style="display:table-cell;' . $largeur . ' border-width:1px; border-style:solid; border-color:#eeeeee;padding: 5pt;';
				$ret .= $style;
				$ret .= '">';
				if (getTypeSG($ligne) === '@Collection') {
					$etiquette = $ligne -> elements;
				} else {
					$etiquette = $ligne['values'];
				}
				foreach ($etiquette as $ic => $valeur) {
					if ($valeur !== '') {
						$texte = new SG_Texte($valeur);
						$texte = $texte -> texte;
						if ($ic === 'c1' or $ic === 0) {
							$ret .= '<b>' . $texte . '</b><br>';
						} else {
							$ret .= $texte . '<br>';
						}
					}
				}
				// fin de cellule
				$ret .= '</div>';
				$iColonne ++;
			}
			// fin de ligne
			$ret .= '</div>';
		}
		return $ret; 
	}
	/** 1.0.7 ajout ; 2.0 libellé 0108 ; 2.1 return SG_HTML
	 * Afficher la collection sous forme d'une suite de tableaux séparés par des sauts de pages
	 * @param @Nombre ou integer $pLignes : nombre de lignes d'étiquettes dans la page
	 * @return SG_HTML
	 */
	function AfficherTableau() {		
		if (sizeof($this -> elements) === 0) {
			$ret = self::libelle('0108'); // rien à afficher
		} else {
			$args = func_get_args();
			if (SG_ThemeGraphique::ThemeGraphique() === 'mobilex') {
				$ret = $this -> getResultats('preparerTableauSimple', false, false, $args);
			} else {
				$ret = $this -> getResultats('preparerTableauSimple', false, false, $args);
			}
		}
		return new SG_HTML($ret);
	}
	/**
	* Tableau simple de type table.
	* @since 1.1
	* @param array $donnees données à afficher
	* @param boolean $pFiltre faut-il afficher avec zone de recherche ?
	* @param boolean $pCheckbox faut-il ajouter une colonne checkbox (choisir dans la liste) ?
	* @return SG_HTML options de tableau + <style> + <table> + propriété 'saisie'
	* @uses JS SynerGaia.stopPropagation()
	*/
	function preparerTableauSimple($donnees, $pFiltre = true, $pCheckbox = false) {
		$ret = '';
		if (isset($donnees['data'])) {
			$saisie = false;
			$valeurs = $donnees['data'];
			// Identifiant unique du tableau
			$idTable = SG_SynerGaia::idRandom();
			//tableau
			$ret .= $this -> optionsDeTableau($idTable, $pFiltre, true, sizeof($valeurs), $pCheckbox);
			$ret .= '<style id="hidden-' . $idTable . '">.hidden-' . $idTable . ' { display: table-row;}</style>';
			$ret .= '<table id="' . $idTable . '" data-role="table" data-mode="columntoggle" class="sg-collection">';
			if (isset($donnees['metadata'])) {
				// panneau d'entête
				$ret .= '<thead><tr>'; // entête
				$titres = $donnees['metadata'];
				for($i = 0; $i < sizeof($titres); $i++) {
					if ($i === 0) {
						$ret .= '<th class="sg-th-cell">';
					} else {
						$ret .= '<th class="sg-th-cell" data-priority="' . $i . '">';
					}
					$value = $titres[$i];
					if(isset($value['label'])) {
						$ret .= $value['label'];
					}
					$ret .= '</th>';
				}
				$ret .= '</tr></thead>';
			}
			// on retourne rien si pas de lignes
			$n = sizeof($valeurs);
			if ($n > 0) {
				// création du tableau
				$ret .= '<tbody>';
				$tr = '<tr class="hidden-' . $idTable . '"';
				// chaque ligne
				for($i = 0; $i < $n; $i++) {
					$l = $valeurs[$i];
					$ret .= $tr;
					if ($this -> style !== null) {
						$ret.= ' style="' . SG_Texte::getTexte($l['style']) . '"';
					}
					$ret.= $l['clic'] . '>';
					// chaque cellule de la ligne
					foreach ($l['values'] as $valeur) {
						if (is_array($valeur)) {
							$tmpcol = new SG_Collection($valeur);
							$txt = $tmpcol -> Lister() -> texte . '</td>';
						} else {
							$txt = SG_Texte::getTexte($valeur);
						}
						if (getTypeSG($valeur) === '@HTML' and $valeur -> saisie === true) {
							// il y a de la saisie sur la ligne, donc on remonte l'info et on arrête la propagation du clic sur la ligne
							$ret .= '<td class="sg-td-cell" onClick="SynerGaia.stopPropagation(event)">' . $txt . '</td>';
							$saisie = true;
						} else {
							$ret .= '<td class="sg-td-cell">' . $txt . '</td>';
						}
					}
					$ret .= '</tr>';
				}
				$ret .= '</tbody>';
			}
			$ret .= '</table>';
			$ret = new SG_HTML($ret);
			$ret -> saisie = $saisie;
		}
		return $ret;
	}

	/**
	* Egale : contient l'objet en paramètre
	* @since 1.1
	* @param any $pQuelqueChose objet avec lequel comparer
	* @return SG_VraiFaux vrai si les deux documents sont identiques
	*/
	function Egale ($pQuelqueChose) {
		if(getTypeSG($pQuelqueChose) === '@Formule') {
			$objet = $pQuelqueChose -> calculer();
		} else {
			$objet = $pQuelqueChose;
		}
		$ret = new SG_VraiFaux(false);
		if (getTypeSG($objet) === '@Document') {
			$ret = $this -> Contient($objet);
		} elseif (getTypeSG($objet) === '@Collection') {
			if ($this -> Compter() -> toInteger() === $objet -> Compter() -> toInteger()) {
				$ret = $ret = new SG_VraiFaux(true);
				foreach($objet -> elements as $element) {
					if (! $this -> Contient($element)) {
						$ret = new SG_VraiFaux(false);
						break;
					}
				}
			}
		} else {
			$ret = $this -> Contient($objet);
		}
		if (getTypeSG($ret) === '@Erreur') {
			$ret = new SG_VraiFaux();
		}
		return $ret;
	}

	/**
	 * Calcul l'html pour les options de calcul de l'affichage en tableau
	* @version 2.4 checkbox
	* @since 1.0.2
	* @param string $idTable
	* @param boolean $pFiltre
	* @param boolean $pTri
	* @param integer $nb
	* @param boolean $pCheckbox
	* @return string html calculé
	* @uses JS SynerGaia.filtrertable(), SynerGaia.selectAll(), SynerGaia.unselectAll(), SynerGaia.tritable()
	*/
	function optionsDeTableau($idTable= '', $pFiltre = true, $pTri = true, $nb = 0, $pCheckbox = false) {
		// création d'un filtre
		$urljs = SG_Navigation::URL_JS;
		$ret = '';
		if ($pFiltre === true) { // todo "recherche" en base libellé
			$ret.= '<div class="sg-search noprint">
			<input id="' . $idTable .'-filtre" placeholder="Rechercher..." type="text" class="sg-coll-filtre"></input>' . PHP_EOL;
			$ret.= '<img id="' . $idTable .'-loader" src="' . $urljs . 'loader.gif" style="display:none"></img>';
			$ret.= '<script>$("#' . $idTable .'-filtre").keyup(function() {SynerGaia.filtrertable("' . $idTable . '", this.value);});</script>' . PHP_EOL;
			$ret.= '<div  class="sg-coll-infos"><span id="infostableau">(' . $nb . ' lignes)</span>';
			$ret.= '<span id="' . $idTable .'-nb"></span></div>';
		} else {
			$ret.= '<div>';
		}
		if ($pCheckbox === true) {
			$ret.= '<div class="sg-selectunselect">';
			$ret.= '<img id="' . $idTable .'-selectall" class="sg-img-selectall" onclick="SynerGaia.selectAll(\'' . $idTable . '\')" title="Tout sélectionner"></img>';
			$ret.= '<img id="' . $idTable .'-unselectall" class="sg-img-unselectall" onclick="SynerGaia.unselectAll(\'' . $idTable . '\')" title="Tout dé-sélectionner"></img>';
			$ret.= '</div>';
		}
		if ($pTri === true) {
			$ret.= '<script src="' . $urljs . 'jquery/tablesorter/tablesorter.js"></script>' . PHP_EOL;
			$ret.= '<script src="' . $urljs . 'jquery/tablesorter/tablesorter.widget.js"></script>' . PHP_EOL;
			$ret.= '<script>SynerGaia.tritable("'  . $idTable . '")</script>' . PHP_EOL;
			// 2.2 tablesorter dans synergaia.js
		}
		$ret.='</div>' . PHP_EOL;
		return $ret;
	}

	/** 1.1 ajout ; 2.0 correction + parm 2
	* Permet d'éclater des lignes selon l'explosion d'une cellule. Une colonne est ajoutée ou remplacée avec la nouvelle valeur calculée.
	* @param formule donnant une collection pour éclater chaque ligne
	* @param (string ou @Texte) : code de l'élément remplacé ou ajouté
	* @return une nouvelle collection avec une colonne supplémentaire
	*/
	function Eclater($pFormule = '', $pCode = '') {
		if (getTypeSG($pFormule) === '@Formule') {
			$formule = $pFormule;
		} else {
			$formule = new SG_Formule(SG_Texte::getTexte($pFormule));
		}
		$code = SG_Texte::getTexte($pCode);

		$ret = $this -> cloner();
		foreach ($this -> elements as $element) {
			$resultat = $formule -> calculerSur($element);
			if ($code === '') {
				if (getTypeSG($resultat) === '@Collection') {
					foreach($resultat -> elements as $result) {
						$ret -> elements[] = $result;
					}
				} elseif (is_array($resultat)) {
					foreach($resultat as $result) {
						$ret -> elements[] = $result;
					}
				} else {
					$ret -> elements[] = $resultat;
				}
			} else {
				if (getTypeSG($resultat) === '@Collection') {
					foreach($resultat -> elements as $result) {
						$ligne = $ret -> elements[] = clone $element;
						$ligne -> setValeur($code, $result);
					}
				} elseif (is_array($resultat)) {
					foreach($resultat as $result) {
						$ligne = $ret -> elements[] = clone $element;
						$ligne -> setValeur($code, $result);
					}
				} else {
					$ligne = $ret -> elements[] = $element;
					$ligne -> setValeur($code, $resultat);
				}
			}
		}
		return $ret;
	}

	/**
	 * Crée l'url pour le clic de la ligne
	 * 
	 * @since 1.1 ajout vient de preparerDonnees
	 * @version 1.3.4 $cible
	 * @version 2.1 $element peut être string, static
	 * @version 2.5 $pIndex
	 * @param SG_Objet $element visé
	 * @param string $texte texte à cliquer
	 * @param string $cible si renvoi via ajax (la cible peut être "centre" pour n'avoir que le onClick)
	 * @param string $idinfo
	 * @param string $pIndex : index dans la collection
	 * @return string html résultat pour la ligne
	 **/
	static function creerLienCliquable($element, $texte, $cible = '', $idinfo = '', $pIndex = '') {
		$ret = $texte;
		$uid = '';
		if (is_string($element)) {
			$url = $element;
		} elseif (method_exists($element,'getUUID')) {
			$uid = $element -> getUUID();
		}
		// Empeche un contenu vide (impossible à cliquer)
		if (trim($texte) === '') {
			$texte = '(vide)';
		}
		// si lien réel
		if ($cible !== '') {
			$ret = self::onClick($element, $cible, false, $idinfo, $pIndex); // 2.1
		} else {
			$url = SG_Navigation::getUrlEtape($element);
			if ($url !== '') {
				$ret = '<a href="' . htmlentities($url, ENT_QUOTES, 'UTF-8') . '" title="vers l\'étape suivante" class="sg-lien">' . $texte . '</a>';
			}
		}
		return $ret;
	}

	/**
	 * Affiche en format pellicule la collection (d'images...)
	 * @since 1.1
	 * @param string|SG_Formule $pPhoto
	 * @param string|SG_Formule $pTitre
	 * @param string|SG_Formule $pClic
	 * @param string|SG_Nombre|SG_Formule $pTaille
	 **/
	function AfficherPellicule($pPhoto = '', $pTitre = '', $pClic = '', $pTaille = 0) {
		// préparer les paramètres
		$photo = new SG_Texte($pPhoto);
		$photo = $photo -> texte;
		if ($photo = '') {
			$photo = '.Photo';
		}
		$titre = new SG_Texte($pTitre);
		$titre = $titre -> texte;
		
		$clic = new SG_Texte($pClic);
		$clic = $clic -> texte;
		
		$taille = new SG_Nombre($pTaille);
		$taille = $taille -> texte;
		// préparation de la bande de photos
		$ret = '<div id="gallerie" class="folioGallery"><div class="numPerPage" title="6"></div></div>';
		$i = 0;
		foreach ($this -> elements as $key => &$element) {
			$image = SG_Formule::executer($photo, $element, null, $pPhoto);
			$ret .= '';
			$i++;
			if ($i > 10) {break;}
		}
	}

	/**
	 * Calcul le JSON correspondant à la collection
	 * @since 1.2
	 */
	function JSON() {
		$ret = '';
		$nbargs = func_num_args();
		if ( sizeof($this -> elements)!== 0) {
			if($nbargs !== 0) {
				$titres = array();
				$formules = array();
				$args = func_get_args();
				// si tableau de paramètres passée dans le 1er argument :
				$args = func_get_args();
				if (func_num_args() == 1 and is_array($args[0])) {
					$args = $args[0];
				}
				foreach ($args as $parametre) {
					if (getTypeSG($parametre) === '@Formule') {
						$texte = $parametre -> formule;
					} else {
						$tmpFormule = new SG_Texte($parametre);
						$texte = $tmpFormule -> toString();
					}
					//traiter le titre
					$i = strpos($texte, ':');
					if ($i !== false) {
						$titres[] = substr($texte, $i + 1);
						$formules[] = substr($texte, 0, $i);
					} else {
						$titres[] = '';
						$formules[] = $texte;
					}
				}
			}
			$ret .= '[';
			foreach ($this -> elements as $key => $element) {
				if($ret !== '[') {
					$ret .= ',';
				}
				if($nbargs == 0) {
					$ret .= $element -> JSON() -> texte;
				} else {
					$ret .= '{';
					for ($i = 0; $i < $nbargs; $i++) {
						$txtjson = '';
						//traiter la valeur
						$valeur = SG_Formule::executer($formules[i], $element, null, $args[i]->formuleparent);
						if(getTypeSG($valeur) === '@Collection') {
							$valeur = $valeur -> elements;
						}
						if (is_array($valeur)) {
							$txtjson = '[';
							foreach($valeur as $val) {
								if ($txtjson !== '[') {
									$txtjson .= ',';
								}
								$txtjson .= $val -> JSON($args) -> texte;
							}
							$txtjson .= ']';
							if ($txtjson === '[]') {
								$txtjson = '';
							}
						} else {
							$txtjson = $valeur -> JSON($args) -> texte;
						}
						if ($txtjson !== '') {
							if ($titres[$i] !== '') {
								$ret .= '"' . $titres[$i] . '":';
							}
							$ret .= $txtjson;
						}
					}
					$ret .= '}';
				}
			}
			$ret .= ']';
		}
		if ($ret === '[]') {
			$ret = '';
		}
		$ret = new SG_Texte($ret);
		return $ret;
	}

	/**
	* Affichage en arbre d'une colection arborescente, par exemple document.Voisinage()
	* @since 1.2
	*/
	function AfficherArbre() {
		$texteJSON = '';
		$args = func_get_args();
		if (sizeof($args) <= 0) {
			$args[] = new SG_Formule('.Titre:titre');
		}
		if (sizeof($args) <= 1) {
			$args[] = new SG_Formule('.Resume:resume');
		}
		if (sizeof($args) <= 2) {
			$args[] = new SG_Formule('.Voisinage:parents');
		}
		if (sizeof($args) <= 3) {
			$args[] = new SG_Formule('.@URL:lien');
		}
		if (sizeof($args) <= 4) {
			$args[] = new SG_Formule('.@Type:modele');
		}
		$json = array();
		foreach ($this -> elements as $element) {
			$json[] = $element -> JSON($args) -> texte;
		}
		$texteJSON = implode(',', $json);
		if (sizeof($json) > 1) {
			$texteJSON = '{"parents":[' . $texteJSON . ']}';
		}
		// Identifiant unique du graphique
		$idGraphique = 'arbre_' . SG_SynerGaia::idRandom();

		$ret = '';
		$ret .= '<div id="' . $idGraphique . '" class="arbre"></div>' . PHP_EOL;
		$ret .= '<script>' . PHP_EOL;
		$ret .= ' var data_' . $idGraphique . ' = ' . $texteJSON . ';' . PHP_EOL;
		$ret .= ' afficherArbre("#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
		$ret .= '</script>' . PHP_EOL;
		return $ret;
	}

	/** 
	 * Transforme la collection en SG_Calendrier
	 * 
	 * @since 1.2
	 * @version 2.3 classe
	 * @param string|SG_Formule|SG_Texte $pTitre
	 * @param SG_Date|SG_DateHeur|SG_Formule $pDebut
	 * @param SG_Date|SG_DateHeur|SG_Formule $pFin = null
	 * @param string|SG_Texte|SG_Formule $pClasse class CSS pour les cases du calendrier
	 * @return SG_Calendrier
	 */
	function Calendrier($pTitre = null, $pDebut = null, $pFin = null, $pClasse = null) {
		/* 3 lignes : ne semble pas fonctionner et n'est pas sûr
		$ret = $this -> Devient('@Calendrier');
		$ret -> initClasseDerive(array(
		return $ret;
		*/
		$ret = new SG_Calendrier();
		// récupérer les anciennes propriétés (attention ne reprend pas les propriétés cachées)
		foreach (get_object_vars($this) as $key => $name) {
			$ret->$key = $name;
		}
		$ret -> typeSG = '@Calendrier';
		// ajouter celles du calendrier
		$ret -> initClasseDerive(array($this->elements, $pTitre, $pDebut, $pFin, $pClasse));
		return $ret;
	}

	/**
	 * Choisit un document au hasard
	 * @since 1.3.0
	 * @return SG_Document|SG_Objet|SG_Erreur l'élément choisi de la collection (ou erreur si collection vide)
	 */
	function AuHasard() {
		$n = sizeof($this -> elements);
		if ($n == 0) {
			$ret = new SG_Erreur('0055');
		} else {
			$ret = $this -> Element(SG_Rien::AuHasard($n));
		}
		return $ret;
	}

	/**
	 * Réduit la collection aux documents passés en paramètres.
	 * Sert quand la collection contient des documents modifiés non enregistrés.
	 * @since 1.3.0
	 * @param array $pListeAGarder liste des UUID des documents à conserver
	 * @return SG_Collection) moi-même
	 */
	function reduire($pListeAGarder) {
		foreach($this -> elements as $key => $element) {
			if(!in_array($element -> getUUID(), $pListeAGarder)) {
				unset($this -> elements[$key]);
			}
		}
		return $this;
	}

	/**
	 * retourne la liste des textes de chaque éléments avec un séparateur (donné en paramètre)
	 * 
	 * @since 1.3.1
	 * @version 2.3 ajout du paramètre $pSep
	 * @param SG_Texte|SG_Formule $pSep : si rempli, indique qu'on veut le résultat comme un texte par ce séparateur. Sinon, c'est pour une collection de textes
	 * @formula result="";.@PourChaque(result=result.@Concatener({,"},.@toString,{"});result;
	 * @return : la chaine résultante (@Texte) ou la collection des textes (@Collection)
	 */
	function Texte($pSep = false) {
		// quel mode choisi ?
		if ($pSep != false) {
			$txt = SG_Texte::getTexte($pSep);
			$ret = '';
		} else {
			$ret = [];
		}
		// mettre en tableau
		foreach($this -> elements as $element) {
			$ret[] = $element -> toString();
		}
		// retourner @Collection ou @Texte
		if ($pSep === false) {
			$ret = new SG_Collection($ret);
		} else {
			$txt = implode($txt, $ret);
			$ret = new SG_Texte($txt);
		}
		return $ret;
	}

	/**
	 * Calcule une ligne du tableau de sortie et le passe comme objet HTML
	 * 
	 * @since 1.3.2
	 * @version 2.4 style @Texte
	 * @version 2.6 $clic vers étape
	 * @param array $formules
	 * @param any $element objet de la ligne
	 * @param boolean $pCheckBox
	 * @param boolean $lienSurPremiereColonne
	 * @param boolean $sanstype
	 * @param string $codeChampHTML
	 * @param string $pIndex
	 * @return array éléments de la ligne à afficher
	 * @uses JS SynerGaia.submit()
	 **/
	function getLigne($formules, $element, $pCheckBox = false, $lienSurPremiereColonne = false, $sanstype = false, $codeChampHTML= '', $pIndex = '') {
		$tmpContenuLigne = array();
		$typesDonnees = null;
		if (is_string($element)) {
			$element = new SG_Texte($element);
		}
		// calcul des clauses de style
		$style = '';
		if (! is_null($this -> style)) {
			for ($i = 0; $i < sizeof($this -> style) ; $i+=2) {
				$si = $this -> style[$i] -> calculerSur($element);
				if ($si instanceof SG_Texte) {
					$style = $si;
				} elseif ( ! method_exists($si, 'estVrai')) {
					$style = SG_Libelle::getLibelle('0177',true, getTypeSG($si));
					break;
				} else {
					if ($si -> estVrai()) {
						if ($i + 1 < sizeof($this -> style)) {
							if(getTypeSG($this -> style[$i + 1]) === '@Formule') {
								$s = $this -> style[$i + 1] -> calculerSur($element);
							} else {
								$s = SG_Texte::getTexte($this -> style[$i + 1]);
							}
							if ($s === null or getTypeSG($s) === '@Erreur') {
								$style = '';
							} else {
								$style = SG_Texte::getTexte($s);
							}
						}
						break;
					}
				}
			}
		}
		// on sépare le cas d'une formule à appliquer ou d'un résultat direct.
		$valeursColonnes = SG_Operation::calculerSur($element, $formules);
		$nbColonnes = sizeof($valeursColonnes);
		// créer un lien cliquable
		$clic = '';
		if (!$pCheckBox and $lienSurPremiereColonne and is_object($element)) {
			$index = urlencode($this -> id . ':' . $pIndex);
			if (is_object($this -> clic)) {
				// on renvoie sur une étape d'une branche
				$url = SG_Navigation::URL_VARIABLE_OPERATION . '=' . SG_Pilote::OperationEnCours() -> reference;
				$url.= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' .  $element -> getUUID();
				$url.= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $this -> clic -> code;
				$clic = 'onclick="SynerGaia.submit(event, null,\'' . SG_Navigation::calculerURL($url) . '\')"';
			} else {				
				$clic = $this -> creerLienCliquable($element, '', 'centre', '', $index);
			}
		}
		// Et on prend chaque colonne
		for ($numColonne = 0; $numColonne < $nbColonnes; $numColonne++) {
			$tmpContenuCellule = '';

			$valeurColonne = $valeursColonnes[$numColonne];
			// Essai d'appel du .toHTML
			if ($valeurColonne instanceof SG_HTML) {
				$tmpContenuCellule = $valeurColonne;
			} elseif (is_object($valeurColonne)) {
				if (method_exists($valeurColonne, 'toHTML')) { // 1.3.3
					$tmpContenuCellule = $valeurColonne -> toHTML();
				} else {
					$tmpContenuCellule = $valeurColonne -> toString();
				}
			} else {
				$tmpContenuCellule = $valeurColonne;
			}
			// calcul du type de données
			if (!$sanstype) {
				if (! isset($typesDonnees[$numColonne])) {
					if ($lienSurPremiereColonne === true) {
						$typesDonnees[$numColonne] = 'html';
					} else {
						$typeValeurColonne = getTypeSG($valeurColonne);
						if (SG_Dictionnaire::isObjetExiste($typeValeurColonne)) {
							// Cherche le type de donnée à afficher
							switch($typeValeurColonne) {
								case '@Texte' :
									$typesDonnees[$numColonne] = 'string';
									break;
								case '@Nombre' :
									$typesDonnees[$numColonne] = 'double';
									break;
								case '@Date' :
								case '@DateHeure' :
								case '@Heure' :
									$typesDonnees[$numColonne] = 'date';
									break;
								case '@HTML' :
								case '@Collection' :
								case '@Email' :
								case '@Bouton' :
								case '@Icone' :
								case '@Utilisateur' :
									$typesDonnees[$numColonne] = 'html';
									break;
								default :
									$typesDonnees[$numColonne] = 'string';
									break;
							}
						} else {
							// Sinon ajoute directement
							$typesDonnees[$numColonne] = 'string';
						}
					}
				}
			}
			// Si on a un contexte avec une étape suivante, propose un lien cliquable sur la premiere colonne
			if ($numColonne === 0) {
				if ($pCheckBox){
					$refDocument = $element -> getUUID();
					$tmpContenuLigne['c0'] = '<input type="checkbox" name="' .  $codeChampHTML . '[]" value="' . $refDocument . '"></input>';
				}
			}
			$tmpContenuLigne['c' . ($numColonne + 1)] = $tmpContenuCellule;
		}
		$ret = array($tmpContenuLigne, $typesDonnees, $clic, $style);
		return $ret;
	}

	/**
	 * calcule la différence des valeurs successives et l'ajoute comme colonne
	 * 
	 * @since 1.3.2
	 * @param SG_Formule $pFormule : la formule donnant le nombre à différencier
	 * @param string|SG_Texte|SG_Formule $pCode code de l'élément à ajouter
	 * @return SG_Collection moi-même
	 */
	function Progression ($pFormule = '',$pCode = '') {
		$code = new SG_Texte($pCode);
		$code = $code -> texte;
		if ($code === '') {
			$code = '@Progression';
		}
		$n = 0;
		$valprec = 0;
		foreach($this->elements as $key => &$element) {
			$tmpFormule = new SG_Formule($pFormule -> toString());
			if (is_array($element)) {
				$tmpFormule -> proprietes = $element;
			} else {
				$tmpFormule -> objet = $element;
			}
			// calcul de la moyenne
			$val = $tmpFormule -> calculer();
			if(method_exists($val, 'toFloat')) {
				$val = $val -> toFloat();
			}
			if($n === 0) {
				$delta = 0;
			} else {
				$delta = $val - $valprec;
			}
			$n++;
			// rangement
			if (is_array($element)) {
				$this -> elements[$key][$code] = new SG_Nombre($delta);
			} else {
				$this -> elements[$key] -> proprietes[$code] = new SG_Nombre($delta);
			}
			$valprec = $val;
		}
		return $this;
	}

	/**
	* Retourne une liste de liens de la collection vers d'autres documents de la collection ou externes
	* @since 1.3.2 ajout
	* @param string|SG_Texte|SG_Formule $pTitre formule donnant le titre des documents
	* @param string|SG_Texte|SG_Formule $pLiens formule donnant la collection des documents liés
	* @param string|SG_Texte|SG_Formule liste de formule supplémentaires pour des argmuants en plus (formule:code)
	* @return string texte JSON pour la mise en graphique
	**/
	function getLiensJSON($pTitre = '', $pLiens = '') {
		$formuletitre = $pTitre;
		if (! $pTitre instanceof SG_Formule) {
			$formuletitre = new SG_Formule($pTitre);
		}
		$ret = array();
		$formuleliens = $pLiens;
		if (! $formuleliens instanceof SG_Formule) {
			$formuleliens = new SG_Formule(SG_Texte::getTexte($formuleliens));
		}
		if ($formuleliens -> texte === '') {
			$ret = new SG_Erreur('0081');
		} else {
			$args = func_get_args();
			$r = array_shift($args);
			$r = array_shift($args);
			$n = 0;
			foreach ($this -> elements as $element) {
				$docslies = $formuleliens -> calculerSur($element);
				if (getTypeSG($docslies) === '@Collection') {
					$titres = array();
					foreach ($docslies -> elements as $doclie) {
						$doctitre = $formuletitre-> calculerSur($doclie) -> toString();
						$titres[] = array('name'=> $doctitre, 'uid' => $doclie -> getUUID());
					}
					$elttitre = $formuletitre -> calculerSur($element) -> toString();
					// argmuents supplémentaires
					$plus = array();
					if (sizeof ($args) > 0) {
						$valeurs = $element -> getValeurs($args);
						foreach($valeurs as $key => $valeur) {
							$plus[$key] = $valeur -> toString();
						}							
					}
					$ret[$elttitre]= array_merge(array('name'=>$elttitre,'key'=>$elttitre,'children' =>$titres, 'uid' => $element -> getUUID()), $plus);
					$n++;
				}
			}
			$ret = json_encode($ret);
		}
		return $ret;
	}

	/** 1.3.2 ajout ; 2.0 imageLoader
	* Affiche une carte circulaire des liens entre documents
	* @param string|SG_Texte|SG_Formule $pTitre formule donnant le titre des documents
	* @param string|SG_Texte|SG_Formule $pLiens formule donnant la collection des documents liés
	* @return string texte JSON pour la mise en graphique
	* @uses JS SynerGaia.imageLoader(), cercleDeRelations(), searchRelations()
	**/
	function AfficherEnCercle($pTitre = '', $pLiens = '') {
		$json = $this -> getLiensJSON($pTitre, $pLiens);
		if (getTypeSG($json) === '@Erreur') {
			$ret = $json -> Afficher();
		} else {
			$url = 'index.php?m=DocumentConsulter&d=';
			$ret = '<script src="js/synergaia-cercle.js"></script>';
			$ret.= '<input id="cercle_search" placeholder="rechercher" onkeyup="searchRelations(\'#cercle\')"></input><br><div id="cercle"></div>';
			$ret.= '<script type="text/javascript">SynerGaia.imageLoader("#cercle");</script>';
			$ret.= '<script>var data=' . $json .';cercleDeRelations("#cercle",data,"' . $url . '");</script>';
		}
		return $ret;
	}

	/**
	 * Calcule la matrice de proximité des documents de la collection et les classes de regroupement
	 * 1. laisser tomber les feuilles isolées (sans lien vers ou de)
	 * 2. regrouper les feuilles avec un seul lien de ou vers (petits groupes)
	 * 3. trier les plus populaires (plus signifiants)
	 * 	3.1 repérer le seuil (grand delta avec le suivant)
	 * 	3.2 prendre le plus populaires selon seuil comme tête de regroupement
	 * 	3.3 étiqueter le groupe selon le(s) mot(s) le plus populaire des titres des feuilles rattachées (et de la 1ere phrase du texte ?)
	 * 4. leur adjoindre les feuilles liées les plus discriminantes
	 * 	4.1 repérer le seuil selon + grand delta
	 * 	4.2 adjoindre au groupe (selon seuil)
	 * 
	 * @since 1.3.2
	 * @param SG_Formule $pTitre formule donnant le titre des documents
	 * @param SG_Formule $pLiens formule donnant la collection des documents liés
	 * @param SG_Formule $pGras
	 * @return string texte JSON pour la mise en graphique
	 * @uses JS proximites.optimize(), proximites.initialize(), proximites.chercher()
	 * @todo mettre libellés en fichiers
	 **/
	function Proximites($pTitre = '', $pLiens = '', $pGras = '') {
		$ret = new SG_Collection();
		$json = call_user_func_array(array($this, 'getLiensJSON'), func_get_args());
		if (getTypeSG($json) === '@Erreur') {
			$ret = $json -> Afficher();
		} else {
			$url = 'index.php?m=DocumentConsulter&d=';
			$ret = '';
			$ret.= '<input id="proximites_search" placeholder="rechercher" onkeyup="proximites.chercher(\'#proximites_search\')"></input>';
			$ret.= '<span id="prox-opt" onclick="proximites.optimize()" style="font:8pt blue underline"> (essayer une autre optimisation)</span>';
			$ret.= '<br><div id="proximites"></div>';
			$ret.= '<script>var data=' . $json .';proximites.initialize("#proximites",data,"' . $url . '");</script>';	
		}
		return $ret;
	}

	/**
	 * Affichage de la collection comme Doodle sur des options (ou des dates)
	 * @since 1.3.4
	 * @version 2.1.1 test date vide
	 * @param string|SG_Texte|SG_Formule $pTitre
	 * @param string|SG_Texte|SG_Formule $pChoix
	 * @todo mettre libellés en fichiers
	 */
	function AfficherDoodle ($pTitre = '.@Titre', $pChoix = '.@Dates') {
		// extraire la formule du titre (param 1)
		if (getTypeSG($pTitre) === '@Formule') {
			$formuleTitre = $pTitre;
		} else {
			$formuleTitre = new SG_Formule($pTitre);
		}
		$titre = '';
		$dp = strpos($formuleTitre -> texte, ':');
		if ($dp !== false) {
			$titre = substr($formuleTitre -> texte, $dp + 1);
			$formuleTitre -> setFormule(substr($formuleTitre -> texte, 0, $dp));
		}
		if (getTypeSG($pChoix) == '@Formule') {
			$formuleChoix = $pChoix;
		} else {
			$formuleChoix = new SG_Formule($pChoix);
		}
		// constitution du tableau de valeurs 
		$totaux = array();
		$noms = array();
		foreach($this -> elements as $personne) {
			$nom = SG_Texte::getTexte($formuleTitre -> calculerSur($personne));
			$dates = $formuleChoix -> calculerSur($personne);
			$type = getTypeSG($dates);
			if($type === '@Dates' or $type === '@Collection') {
				$valeurs = array();
				foreach($dates -> elements as $elt) {
					if (! $elt -> EstVide() -> estVrai()) {
						$valeur = $elt -> AnMoisJour() -> texte;
						if(!isset($totaux[$valeur])) {
							$totaux[$valeur] = 1;
						} else {
							$totaux[$valeur]++;
						}
						$valeurs[$valeur] = true;
					}
				}
				$noms[$nom] = $valeurs;
			} else {
				$noms[$nom] = null;
			}
		}
		// @todo tri des tableaux des choix
		ksort($totaux);
		// formatage du résultat
		$ret = '<table class="doodle"><thead><tr class="doodle-titres"><th>' . $titre . '</th>';
		foreach ($totaux as $key => $val) {
			$ret.= '<th>' . $key . '</th>';
		}
		$ret.= '</tr></thead>';
		$ret.= '<tbody>';
		foreach ($noms as $nom => $valeurs) {
			$ret.= '<tr><td>' . $nom . '</td>';
			if (sizeof($totaux) === 0) {
				$ret.= '<td class="doodle-vide">Disponibilités non fournies</td>';
			} else {
				foreach ($totaux as $key => $val) {
					if (is_null($valeurs)) {
						$ret.= '<td class="doodle-vide">Disponibilités non fournies</td>';
					} elseif(isset($valeurs[$key])) {
						$ret.= '<td class="doodle-ok"></td>';
					} else {
						$ret.= '<td class="doodle-non"></td>';
					}
				}
			}
			$ret.= '</tr>';			
		}
		$ret.= '<tbody>';
		$ret.= '<tfoot><tr><td class="doodle-total">Total</td>';
		foreach ($totaux as $key => $val) {
			$ret.= '<td>' . $val . '</td>';
		}
		$ret.= '</tr></tfoot><table>';
		return new SG_HTML($ret);
	}

	/**
	 * retourne le libellé correspondant à un numéro de message (SG_Libelle)
	 * @since 2.0
	 * @param string $no numéro du libellé recherché
	 * @return string
	 */
	private function libelle($no) {
		$ret = SG_Libelle::getLibelle($no, false);
		return $ret;
	}

	/**
	 * place une collection d'@HTML dans la gauche
	 * @since 2.0
	 * @return (SH_HTML) : la collection dans le cadre gauche
	 **/
	function AGauche() {
		$html = new SG_HTML('');
		foreach($this -> elements as $elt) {
			$html -> texte .= $elt -> Afficher();
		}
		$html -> AGauche();
		return $html;
	}

	/**
	 * enlève une entrée ou des entrées d'une collection
	 * @since 2.1
	 * @param : document ou collection de ceux à enlever
	 * @return : collection modifiée
	 **/
	function Enlever($pQuelqueChose = null) {
		$ret = $this;
		$type = getTypeSG($pQuelqueChose);
		if ($type === '@Formule') {
			$collec = $pQuelqueChose -> Calculer();
			$type = getTypeSG($collec);
		} else {
			$collec = $pQuelqueChose;
		}
		if (! (is_subclass_of($collec, 'SG_Collection') or $type === '@Collection')) {
			$collec = new SG_Collection();
			$collec -> elements[] = $collec;
		}
		foreach ($collec -> elements as $elt) {
			$type = getTypeSG($elt);
			$estundoc = method_exists($elt, 'getCodeDocument');
			foreach ($this -> elements as $key => $element) {
				if (getTypeSG($element) === $type) {
					if($estundoc) {
						if ($element -> getCodeDocument() === $elt -> getCodeDocument()) {
							unset($this -> elements[$key]);
							break;
						}
					}
				}
			}
		}
		return $this;
	}

	/** 2.1 ajout ; 2.2 tests objets
	* Affiche une collection de documents sous forme d'une liste de news (vignette, titre, chapeau, date de publication)
	* @param SG_Formule $pTitre
	* @param SG_Formule $pPhoto
	* @param SG_Formule $pResume
	* @param SG_Formule $pPublication
	* @return SG_HTML
	**/
	function AfficherNews($pTitre = '', $pPhoto = '', $pResume = '', $pPublication = "") {
		$ret = '<div class="news">';
		if ($this -> titre != '') {
			$ret.= '<div class="sg-coll-titre">' . $this -> titre .'</div>';
		}
		foreach ($this -> elements as $key => $elt) {
			$ret.= '<div class="news-ligne" ' . self::onClick($elt,'centre') . '>';
			$titre = sg_Texte::getTexte($pTitre -> calculerSur($elt));
			// photo
			if (getTypeSG($pPhoto) === '@Formule') {
				$ret.= '<div class="news-photo">';
				$photo = $pPhoto -> calculerSur($elt);
				if (method_exists($photo, 'getValeur') and $photo -> getValeur('@Fichier','') !== '') {
					$src = $photo -> getSrc(false);
					$ret.= '<img class="news-img" src="' . $src . '" alt="' . $titre . '"/>';
				}
				$ret.= '</div>';
			}
			// partie droite
			$ret.= '<div class="news-droite">';
			// titre
			$ret.= '<div class="news-titre"><strong>' . $this -> creerLienCliquable($elt, $titre) . '</strong></div>';
			// chapeau résumé
			if (is_object($pResume)) {
				$resume = SG_Texte::getTexte($pResume -> calculerSur($elt));
				$ret.= '<p class="news-chapeau">' . $resume . '</p>';
			}
			// publication
			if (is_object($pPublication)) {
				$publication = sg_Texte::getTexte($pPublication -> calculerSur($elt));
				$ret.= '<p class="news-publi"><i>Article publié le ' . $publication . '</i></p>';
			}
			$ret.='</div>'; // droite
			// fin ligne
			$ret.=	'</div>'; //news-ligne
		}
		$ret.= '</div>'; // news
		return new SG_HTML($ret);
	}

	/** 2.1 ajout ; 2.5 index
	 * ajoute un événement onclick vers lancement opération ou étape suivante
	 * @param SG_Objet $element : objet visé par le clic
	 * @param string $cible : cadre cible pour l'affichage du clic
	 * @param boolean $pEfface : faut-il effacer les autres cadres ? (défaut false)
	 * @param string $idinfo : code d'une div où puiser un complément d'url (sert pour @Repertoire)
	 * @param string $pIndex : si objet d'une ligne de collection, id collection et indice dans la collection
	 * @return string
	 * @uses JS SynerGaia.champs(), SynerGaia.launchOperation()
	 **/
	static function onClick($element, $cible = '', $pEfface = false, $idinfo = '', $pIndex = '') {
		$ret = '';
		$url = SG_Navigation::getUrlEtape($element, false, $pIndex);
		if ($url !== '') {
		/*	if ($pIndex !== '') {
				$url.= '&' . SG_Navigation::URL_VARIABLE_INDEX . '=' . $pIndex;
			}*/
			// données à joindre en provenance d'une div dont on a passé l'id
			$data = 'null';
			if ($idinfo != '') {
				$data = 'SynerGaia.champs(\'' . $idinfo . '\')';
			}
			$cible = '';
			$ret = 'onclick="SynerGaia.launchOperation(event,\'' . htmlentities($url, ENT_QUOTES, 'UTF-8') . '\',' . $data . ', ' . ($pEfface ? 'true' : 'false') . $cible . ')"';
		}
		return $ret;
	}

	/**
	* équivalent d'un Trim sur filtre on garde ceux qui répondent @Vrai au filtre, sinon ceux qui ne sont pas vides
	* @since 2.1
	* @version 2.6 test si $elt pas objet
	* @param SG_Formule $pFiltre
	* @return SG_Collection
	**/
	function Condenser($pFiltre = '') {
		$ret = array();
		if ($pFiltre !== '') {
			$filtre = $pFiltre;
		}
		foreach($this -> elements as $key => $elt) {
			if ($pFiltre === '') {
				if (is_object($elt)) {
					$tmp = ($elt -> EstVide() -> estVrai() !== true);
				} else {
					$tmp = ($elt !== '' and !is_null($elt));
				}
			} else {
				$tmp = $filtre -> calculerSur($elt) -> estVrai();
			}
			if($tmp === true) {
				$ret[$key] = $elt;
			}
		}
		return new SG_Collection($ret);
	}

	/**
	* gere le titre de la collection
	* @since 2.2
	* @param $pTitre : titre a donner à la collection
	* @return : si pas de paramètre, le titre. Sinon, $this
	**/
	function Titre ($pTitre = null) {
		if ($pTitre === null) {
			$ret = $this -> titre;
		} else {
			$this -> titre = SG_Texte::getTexte($pTitre);
			$ret = $this;
		}
		return $ret;
	}
	/**
	* gere le style des lignes de la collection. Sera appliquée à chaque ligne
	* (l'exécution de la formule se fait dans -> getLigne
	* @since 2.2
	* @param $pStyle : formule donnant le style de la ligne
	* @return : si pas de paramètre, la collection des arguments de style. Sinon, la collection elle-même
	**/
	function Style ($pStyle = null) {
		if ($pStyle === null) {
			$ret = $this -> style;
		} else {
			$this -> style = func_get_args();
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * clone une collection vide avec les même propriétés sauf les éléments (par exemple en sortie de tri)
	 * 
	 * @since 2.2
	 * @return la nouvelle collection
	 */
	function cloner () {
		$ret = new SG_Collection;
		$ret -> clic = $this -> clic;
		$ret -> titre = $this -> titre;
		$ret -> style = $this -> style;
		$ret -> proprietes = $this -> proprietes;
		return $ret;
	}

	/**
	 * éclater un éléments en fonction de périodes annuelles. Si le début ou la fin manquent dans le résultat calculé, on prend @Maintenant
	 * 
	 * @since 2.2
	 * @param (@Date ou @DateHeure) $pDebut : début de l'intervalle
	 * @param (@Date ou @DateHeure) $pFin : fin de l'intervalle
	 * @param $pCodeDebut (par défaut @Debut) : nom de la propriété qui contiendra la valeur du début de la période pour chaque élément
	 * @param $pCodeFin (par défaut @Fin) : nom de la propriété qui contiendra la valeur de fin de la période pour chaque élément
	 * @return @Collection éclatée : périodes 
	 */
	function EclaterAnnees($pDebut='', $pFin = '', $pCodeDebut = '@Debut', $pCodeFin = '@Fin') {
		$ret = $this -> cloner();
		$cdebut = SG_Texte::getTexte($pCodeDebut);
		$cfin = SG_Texte::getTexte($pCodeFin);
		if (getTypeSG($pDebut) !== '@Formule') { 
			$ret = new SG_Erreur('Il manque la formule du début');
		} elseif (getTypeSG($pFin) !== '@Formule') { 
			$ret = new SG_Erreur('Il manque la formule de fin');
		} else {
			foreach ($this -> elements as $element) {
				$debut = $pDebut -> calculerSur($element);
				$fin = $pFin -> calculerSur($element);
				if(getTypeSG($fin) === '@Rien') {
					$fin = SG_Rien::Maintenant();
				}
				if ($debut -> Annee() === $fin -> Annee()) {
					$ligne = $ret -> elements[] = clone $element;
					$ligne -> proprietes[$cdebut] = $debut;
					$ligne -> proprietes[$cfin] = $fin;
				} else {
					$periodes = $debut -> EclaterAnnees($fin);
					foreach($periodes -> elements as $p) {
						$ligne = $ret -> elements[] = clone $element;
						$ligne -> proprietes[$cdebut] = $p -> proprietes[$cdebut];
						$ligne -> proprietes[$cfin] = $p -> proprietes[$cfin];
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Prépare la formule à exécuter en cas de clic
	 * 
	 * @since 2.3
	 * @param SG_Formule $pAction action à exécuter quand on clique sur la ligne
	 * @return SG_Collection la collection en cours
 	 */
	function Clic($pAction = null) {
		$this -> clic = $pAction; // new SG_Bouton('clic', $pAction);
		return $this;
	}

	/**
	 * Chercher éventuellement un élement indexé par le nom de champ
	 * @since 2.3
	 * @param string $pChamp nom du champ clé dans la collection
	 * @param any $pValeurDefaut valeur par défaut du champ
	 * @param string $pModele nom du modèle du champ
	 * @return la valeur du champ correspondant
	 **/
	public function getValeurPropriete($pChamp = null, $pValeurDefaut = null, $pModele = '') {
		if (isset($this -> elements[$pChamp])) {
			$ret = $this -> elements[$pChamp];
		} else {
			$ret = parent::getValeurPropriete($pChamp, $pValeurDefaut, $pModele);
		}
		return $ret;
	}

	/**
	* La collection est censée ne comprendre que des adresses mail
	* la fonction reprend la liste et l'affiche dans une zone cliquable avec une url mailto:
	* 
	* @since 2.3
	* @version 2.4 ajout parm $pNombre
	* @version 2.6 copy
	* @param string|SG_Texte|SG_Formule $pMode type de champ destinataire (to, cc, bcc par défaut)
	* @param integer|SG_Nombre|SG_Formule $pNombre le nombre maximum d'adresses dans un bloc (défaut 50)
	* @return SG_HTML : la liste entourée d'une url mailto complète.
	* @uses JS SynerGaia.copy()
	**/
	public function AfficherMailTo($pMode = 'bcc', $pNombre = 50) {
		$mode = SG_Texte::getTexte($pMode);
		$emet = SG_Rien::Moi() -> getValeur('Email',''); // créer un from à partir de mon adresse
		$max = SG_Nombre::getNombre($pNombre);
		$ret = '';
		$dest = '';
		$i=99999999;
		$n=0;
		$msg = SG_Libelle::getLibelle('0268', false);
		$ta = '<textarea id="copyTarget" onclick="SynerGaia.copy(event)" class="sg-coll-mailto-ta">';
		foreach ($this -> elements as $adr) {
			if ($i > $max) {
				$n++;
				if ($n > 1) {
					$ret.= $msg . '<a href="mailto:' . $emet . '?' . $mode . '=' . $dest . '">' . $ta;
					// fin de la liste, de la balise, de la div cliquable, de la div du bloc mailto
					$ret.= $dest . '</textarea></a></div>';
					$ret.= '<div class="sg-coll-mailto"><b>Bloc ' . $n . '</b><br>';
				}
				// entête de bloc
				$i = 0;
				$dest = '';
			}
			$txt = SG_Texte::getTexte($adr);
			if ($txt !== '' and $txt !== null) {// supprimer les adresses vides
				if ($dest === '') {
					$dest = $txt;
				} else {
					$dest.= ',' . $txt;
				}
				$i++;
			}
		}
		// fin de la liste, de la balise, de la div cliquable, de la div du bloc mailto
		$ret.= $msg . '<br><a href="mailto:' . $emet . '?' . $mode . '=' . $dest . '">' . $ta;
		$ret.= $dest . '</textarea></a></div>';
		if ($n > 1) {
			$ret = '<div class="sg-coll-mailto"><b>Bloc 1</b><br>' . $ret;
		}
		return new SG_HTML($ret);
	}
	/**
	* Crée un nouveau répertoire à partir des documents ou des photos ou des objets de la collection
	* @since 2.4
	* @param SG_Formule|SG_Texte le code du répertoire à créer
	* @return SG_Repertoire le nouveau répertoire (non enregistré)
	**/
	function CreerRepertoire($pTitre = '') {
		$newrep = new SG_Repertoire();
		$newrep -> titre = SG_Texte::getTexte($pTitre);
		$newrep -> mettreValeur('@Titre', $newrep -> titre);
		$newrep -> Ajouter($this);
		return $newrep;
	}
	/**
	* Retourne l'élément actuel selon le curseur ou erreur 224 si la collection est vide
	* @since 2.4
	* @return SG_Objet|SG_Erreur
	**/
	function Actuel() {
		if (is_null($his -> cursor)) {
			$ret = $this -> Premier();// positionne le curseur au début
		} elseif (array_key_exists($this -> cursor, $this -> elements)) {
			$ret = $this -> elements[$this -> cursor];
		} else {
			$ret = new SG_Erreur('0224', $this -> cursor);
		}
		return $ret;
	}
	/**
	* positionne le curseur sur l'élément précédent et retourne l'élément (ou erreur si n'existe pas)
	* @since 2.4
	* @return SG_Objet|SG_Erreur retourne l'élément actuel selon le curseur
	**/
	function Precedent() {
		if (is_null($this -> cursor)) {
			$ret = new SG_Erreur('0229');
		} else {
			$keys = array_keys($this -> elements);
			$key = array_search($this -> cursor, $keys);
			if ($key === false) {
				$ret = new SG_Erreur('0225', $this -> cursor);
			} else {
				--$key;
				if ($key < 0) {
					$ret = new SG_Erreur('0226');
				} else {
					$this -> cusor = $keys[$key];
					$ret = $this -> elements[$this -> cursor];
				}
			}
		}
		return $ret;
	}
	/**
	* Positionne le curseur sur l'élément suivant et retourne l'élément (ou erreur si n'existe pas)
	* @since 2.4
	* @return SG_Objet|SG_Erreur retourne l'élément actuel selon le curseur
	**/
	function Suivant() {
		if (is_null($this -> cursor)) {
			$this -> AllerAuDebut();
		}
		$keys = array_keys($this -> elements);
		$key = array_search($this -> cursor, $keys);
		if ($key === false) {
			$ret = new SG_Erreur('0227', $this -> cursor);
		} else {
			++$key;
			if ($key >= sizeof($keys)) {
				$ret = new SG_Erreur('0228');
			} else {
				$this -> cusor = $keys[$key];
				$ret = $this -> elements[$this -> cursor];
			}
		}
		return $ret;
	}

	/** 
	 * Positionne le curseur au début de la collection
	 * @since 2.4
	 * @return SG_Collection la collection
	 **/
	function AllerAuDebut() {
		reset($this -> elements);
		$this -> cursor = key($this -> elements);
		return $this;
	}

	/**
	 * positionne le curseur au début de la collection
	 * @since 2.4
	 * @return SG_Collection la collection
	 **/
	function AllerALaFin() {
		end($this -> elements);
		$this -> cursor = key($this -> elements);
		return $this;
	}

	/**
	 * Modifier les éléments de la collection ou en ajouter ou supprimer
	 * @since 2.6
	 * @param string $pRefChamp référence html du champ
	 * @return string HTML de la liste des éléments avec la possibilité d'en ajouter
	 * @uses JS SynerGaia.collAdd(), SynerGaia.collSupp()
	 **/
	function modifierChamp($pRefChamp = '') {
		$ret = '<div class="sg-coll-modif">';
		foreach ($this -> elements as $key => &$elt) {
			if (!is_object($elt) and ! is_null($this -> type)){
				$elt = new $this -> type ($elt);
			}
			if (is_object($elt)) {
				$ret.= '<div class="sg-coll-elt">';
				$ret.= '<div class="sg-coll-elt-field">' . $elt -> modifierChamp($pRefChamp . '[' . $key . ']') . '</div>';
				$ret.= '<div class="sg-coll-elt-plus" onclick="SynerGaia.collAdd(event, \'' . get_class($elt) . '\')" title="Ajouter un élément"></div>';
				$ret.= '<div class="sg-coll-elt-moins" onclick="SynerGaia.collSupp(event)" title="Supprimer cet élément"></div>';
				$ret.= '</div>';
			}
		}
		$ret.= '</div>';
		return $ret;
	}

	/**
	 * Affiche le champ en modification
	 * @since 2.6
	 * @return SG_HTML code pour la saisie d'une collection
	 **/
	function Modifier() {
		return new SG_HTML($this -> modifierChamp());
	}

	/**
	 * Met à jour ou retourne le type d'élément d'une collection d'éléments d'un seul type
	 * Si $pType donne une erreur, le résultat est cette erreur et $this -> type n'est pas modifié
	 * Si le type n'est pas défini, retourne '@Rien'
	 * 
	 * @since 2.6
	 * @param string|SG_Objet|SG_Formule $pType nom du type SynerGaïa des éléments. Si absent on fournira le type
	 * @return SG-Texte|SG_Formule code pour la saisie d'une collection
	 **/
	function Type($pType = null) {
		if (func_num_args() === 0) {
			if (is_null($this -> type)) {
				$ret === SG_Rien::typeSG;
			} else {
				$ret = new SG_Texte($type);
			}
		} else {
			$ret = $this;
			$type = SG_Texte::getTexte($pType);
			if ($type instanceof SG_Erreur) {
				$ret = $type;
			} elseif ($type === "" or $type === SG_Rien::typeSG) {
				$this -> type = null;
			} else {
				$this -> type = $type;
			}
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Collection_trait;
}
?>
