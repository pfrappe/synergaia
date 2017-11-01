<?php
/** SynerGaïa : fichier contenant la gestion de l'objet @VueCouchDB */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_VueCouchDB : Classe SynerGaia de gestion d'une vue CouchDB
 * @version SynerGaia 2.6
 */
class SG_VueCouchDB extends SG_Objet {
	/** string Type SynerGaia '@VueCouchDB' */
	const TYPESG = '@VueCouchDB';
	/** string Code de la base couchdb contenant les vues */
	const CODEBASE = 'vues';
	
	/** string Type SynerGaia '@VueCouchDB' */
	public $typeSG = self::TYPESG;

	/** SG_Vue Document "vue" associé */
	public $vue;
	
	/** string Code de la vue */
	public $code;
	
	/** string Code de la base */
	public $codeBase;
	/**
	 * string Code complet de la base avec prefixe d'application
	 */
	public $codeBaseComplet = '';

	/**
	 * Construction de l'objet
	 * 
	 * @version 2.3 SG_Config::getCodeBaseComplet()
	 * @param indéfini $pCodeVue code de la vue
	 */
	public function __construct($pCodeVue = '') {
		// Si j'ai un code de vue
		if ($pCodeVue !== '') {
			$infos = explode('/', $pCodeVue);
			if (sizeof($infos) === 2) {
				$codeBase = $infos[0];
				$codeVue = $infos[1];
				$codeBaseComplet = SG_Config::getCodeBaseComplet($codeBase);

				$this -> codeBase = $codeBase;
				$this -> codeBaseComplet = $codeBaseComplet;
				$this -> code = $codeBaseComplet . '/_design/' . $codeVue . '/_view/';// par défaut
			}
		}
	}

	/**
	 * Extrait le contenu de la vue
	 *
	 * @version 2.6 si vue calculée : $codeElement['id'] au lieu de $codeElement['_id']
	 * @param string $pCleRecherche : clé de recherche
	 * @param string $pFiltre : formule à exécuter immédiatement
	 * @param boolean $pIncludeDocs : permet d'inclure les documents dans la recherche 
	 * @param boolean $pExactMatch indique si la clé est excate (défaut true)
	 * @param string $pView type de vue (défaut 'all')
	 * @return SG_Collection les objets SynerGaia lus
	 */
	function Contenu($pCleRecherche = '', $pFiltre = '', $pIncludeDocs = false, $pExactMatch = true, $pView = 'all') {
		if($pFiltre instanceof SG_Formule) {
			$filtre = $pFiltre;
		} else {
			$filtre = new SG_Formule($pFiltre);
		}
//journaliser($filtre);
		if(($filtre -> texte === '' or $filtre -> texte === '""') and $filtre -> php === '' and $filtre -> fonction === '') {
			$filtre = '';
		}
		// Initialise la collection
		$listeElements = $this -> contenuBrut($pCleRecherche, $pIncludeDocs, $pExactMatch, $pView);
		$collection = new SG_Collection();
		if(is_array($listeElements)) {
			// Pour chaque élément fabrique l'objet correspondant
			foreach ($listeElements as $key => $row) {
				$codeElement = $row['value'];
				// on vient d'une vue calculée ($row['value'] = tableau de champs)
				if (is_array($codeElement)) {
					if ($pIncludeDocs === true) {
						$typeElement = 'SG_Document';
						if (isset($row['doc']['@Type'])) {
							$type = $row['doc']['@Type'];
							if (substr($type, 0, 1) === '@') {
								$typeElement = SG_Dictionnaire::getClasseObjet($type);
							}
						}
						$element = new $typeElement($this -> codeBase . '/' . $row['doc']['_id'], $row['doc']);
					} else {
						$element = new SG_Document(''); // 2.1 création sans id - gain de performance
						$element -> doc -> codeDocument = $codeElement['_id'];
						$element -> doc -> codeBase = $this -> codeBase;
					}
					$element -> proprietes = $codeElement;
				} else {
					// on vient d'une vue avec documents
					if ($pIncludeDocs === true) {
						$typeElement = 'SG_Document';
						if (isset($row['doc']['@Type'])) {
							$type = $row['doc']['@Type'];
							if (substr($type, 0, 1) === '@') {
								$typeElement = SG_Dictionnaire::getClasseObjet($type);
							} else {
								$typeElement = $type;
								$codeElement = $this -> codeBase . '/' . $codeElement;
							}
						}
						$element = new $typeElement($codeElement, $row['doc']);
					} else {
						$element = $_SESSION['@SynerGaia'] -> getObjet($this -> codeBase . '/' . $codeElement);
					}
				}
				// filtrage immédiat si fourni
				if ($filtre === '') {
					$collection -> elements[] = $element;
				} else {
					$s = $filtre -> calculerSur($element);
					$resultat = new SG_VraiFaux($s);
					if ($resultat -> estVrai()) {
						$collection -> elements[] = $element;
					}
				}
			}
		}
		return $collection;
	}

	/**
	 * Extrait le contenu brut de la vue
	 *
	 * @version 2.1 lack of memory 256M erreur 0119
	 * @version 2.7 correct getMessage()
	 * @param string $pCleRecherche clé de recherche
	 * @param boolean $pIncludeDocs permet d'inclure les documents dans la recherche 
	 * @param boolean $pExactMatch indique si la clé est excate (défaut true)
	 * @param string $pView type de vue (défaut 'all')
	 * @return array tableau des rows => value ou SG_Erreur
	 */
	function contenuBrut($pCleRecherche = '', $pIncludeDocs = false, $pExactMatch = true, $pView = 'all') {
		$cleRecherche = $pCleRecherche;
		// Définit l'url d'accès à la vue
		$vueURL = $_SESSION['@SynerGaia'] -> sgbd -> url . $this -> code . $pView;
		// Ajoute le critère de recherche si besoin
		$parms = false;
		if ($cleRecherche !== '') {
			if ($pExactMatch === true) {
				$vueURL .= '?key="' . urlencode($cleRecherche) . '"';
			} else {
				$key = urlencode(strtolower($cleRecherche));
				$vueURL .= '?startkey="' . $key . '"&endkey="' . $key . 'ZZZZZZZZZZZZ"';
			}
			$parms = true;
		}
		if ($pIncludeDocs === true) {
			if($parms === false) {
				$vueURL .= '?';
			} else {
				$vueURL .= '&';
			}
			$vueURL .= 'include_docs=true';
			$parms = true;
		}
		// Lance la requete, extrait la liste des éléments renvoyés par la vue
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> requete($vueURL, "GET");
		try {
			ini_set('memory_limit', '512M');
			$resultat = json_decode($ret, true);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$resultat['rows'] = new SG_Erreur('0119', $e -> getMessage());
		}
		return $resultat['rows']; // $listeElements; 1.3.4
	}

	/**
	 * Cherche les éléments correpondants à une clé dans la vue
	 *
	 * @param string $pCleRecherche clé de recherche
	 * @param string $pView type de vue (défaut 'all')
	 * @return SG_Collection
	 */
	function ChercherElements($pCleRecherche = '', $pView = 'all') {
		return $this -> Contenu($pCleRecherche, $pView);
	}

	/**
	 * Cherche la première valeur trouvée dans la vue par une clé
	 *
	 * @version 2.7 test SG_Erreur
	 * @param string $pCleRecherche clé de recherche
	 * @param string $pView type de vue (défaut 'all')
	 * @return string valeur trouvée
	 */
	function ChercherValeur($pCleRecherche = '', $pView = 'all') {
		$cleRecherche = $pCleRecherche;
		$sgbd = $_SESSION['@SynerGaia'] -> sgbd;
		$vueURL = $sgbd -> url . $this -> code . $pView;
		if ($cleRecherche !== '') {
			$vueURL .= '?key="' . urlencode($cleRecherche) . '"';
		}

		$resultat = $sgbd -> requete($vueURL, "GET");
		if ($resultat instanceof SG_Erreur) {
			$ret = $resultat;
		} else {
			$resultat_Tab = json_decode($resultat, true);
			$listeElements = $resultat_Tab['rows'];

			// Ne prend que la première occurence :
			$ret = null;
			if (sizeof($listeElements) !== 0) {
				$ret = $listeElements[0]['value'];
			}
		}
		return $ret;
	}

	/**
	 * Chercher toutes les valeurs à partir d'une clé
	 *
	 * @version 2.7 test SG_Erreur
	 * @param string $pCleRecherche clé de recherche
	 * @param string $pView type de vue (défaut 'all')
	 * @return SG_Collection résultat de la recherche
	 */
	function ChercherValeurs($pCleRecherche = '', $pView = 'all') {
		$cleRecherche = $pCleRecherche;

		$sgbd = $_SESSION['@SynerGaia'] -> sgbd;
		$vueURL = $sgbd -> url . $this -> code . $pView;
		if ($cleRecherche !== '') {
			$vueURL .= '?key="' . urlencode($cleRecherche) . '"';
		}
		$resultat = $sgbd -> requete($vueURL, "GET");
		if ($resultat instanceof SG_Erreur) {
			$ret = $resultat;
		} else {
			$resultat_Tab = json_decode($resultat, true);
			if (! isset($resultat_Tab['rows'])) {
				$ret = new SG_Erreur('0029', $this -> code . $pView);
			} else {
				$listeElements = $resultat_Tab['rows'];

				$collection = new SG_Collection();
				$nbResultats = sizeof($listeElements);
				if ($nbResultats !== 0) {
					for ($i = 0; $i < $nbResultats; $i++) {
						$collection -> Ajouter($listeElements[$i]['value']);
					}
				}
				$ret = $collection;
			}
		}
		return $ret;
	}

	/**
	 * La vue existe-t-elle ?
	 *
	 * @return SG_VraiFaux vue existe
	 */
	function Existe() {
		return new SG_VraiFaux(true);
	}

	/**
	 * Enregistre la définition de la vue (ne fait rien)
	 * @todo à supprimer ??
	 */
	function Enregistrer() {
	}

	/**
	 * Extrait le contenu brut de la vue
	 * @since 2.4 ajout
	 * @param string $pCleDebut clé de recherche début
	 * @param string $pCleFin clé de recherche fin 
	 * @param boolean $pIncludeDocs permet d'inclure les documents dans la recherche 
	 * @param string $pView type de vue (défaut 'all')
	 * @return array tableau des rows => value ou SG_Erreur
	 */
	function getCollection($pCleDebut = '', $pCleFin = '', $pIncludeDocs = false, $pView = 'all') {
		// Définit l'url d'accès à la vue
		$vueURL = $_SESSION['@SynerGaia'] -> sgbd -> url . $this -> code . $pView;
		// Ajoute le critère de recherche si besoin
		$parms = false;
		$vueURL .= '?startkey="' . urlencode(strtolower($pCleDebut)) . '"&endkey="' . urlencode(strtolower($pCleFin)) . '\ufff0"';
		if ($pIncludeDocs === true) {
			$vueURL .= '&include_docs=true';
		}
		// Lance la requete, extrait la liste des éléments renvoyés par la vue
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> requete($vueURL, "GET");
		try {
			ini_set('memory_limit', '512M');
			$resultat = json_decode($ret, true);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$resultat['rows'] = new SG_Erreur('0239', $e -> getMessage);
		}
		return $resultat['rows'];
	}
}
?>
