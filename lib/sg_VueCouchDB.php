<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_VueCouchDB : Classe SynerGaia de gestion d'une vue CouchDB
*/
class SG_VueCouchDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@VueCouchDB';
	public $typeSG = self::TYPESG;
	
	const CODEBASE = 'vues';

	// Document "vue" associé
	public $vue;
	
	// Code de la vue
	public $code;
	
	// Code de la base
	public $codeBase;
	/**
	 * Code complet de la base avec prefixe
	 */
	public $codeBaseComplet = '';

	/** 2.3 SG_Config::getCodeBaseComplet()
	* Construction de l'objet
	*
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
	* 1.1 traitement d'une vue calculée ; 1.3.0 performance sur filtre (gain 60%) ; 1.3.4 $pView ; 2.1 création docDB sans id ; 2.2 test -> fonction
	* Extrait le contenu de la vue
	*
	* @param string $pCleRecherche : clé de recherche
	* @param string $pFiltre : formule à exécuter immédiatement
	* @param boolean $pIncludeDocs : permet d'inclure les documents dans la recherche 
	* @return : SG_Collection des objets SynerGaia lus
	*/
	function Contenu($pCleRecherche = '', $pFiltre = '', $pIncludeDocs = false, $pExactMatch = true, $pView = 'all') {
		if(getTypeSG($pFiltre) === '@Formule') {
			$filtre = $pFiltre;
		} else {
			$filtre = new SG_Formule($pFiltre);
		}
		if(($filtre -> formule === '' or $filtre -> formule === '""') and $filtre -> php === '' and $filtre -> fonction === '') {
			$filtre = '';
		}
		// Initialise la collection
		$listeElements = $this -> contenuBrut($pCleRecherche, $pIncludeDocs, $pExactMatch, $pView);
		$collection = new SG_Collection();
		if(is_array($listeElements)) {
			// Pour chaque élément fabrique l'objet correpondant
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
						$element = new $typeElement($this -> codeBase . '/' . $codeElement['_id'], $row['doc']);
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

	/** 1.1 traitement d'une vue calculée ; 1.3.4 $pView ; 2.1 lack of memory 256M erreur 0119
	* Extrait le contenu brut de la vue
	*
	* @param string $pCleRecherche clé de recherche
	* @param boolean $pIncludeDocs permet d'inclure les documents dans la recherche 
	* @return tableau des rows => value ou SG_Erreur
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
journaliser($e -> getMessage, false);
			$resultat['rows'] = new SG_Erreur('0119', $e -> getMessage);
		}
		return $resultat['rows']; // $listeElements; 1.3.4
	}
	/**
	* Cherche les éléments correpondants à une clé dans la vue
	*
	* @param string $pCleRecherche clé de recherche
	* @return SG_Collection
	*/
	function ChercherElements($pCleRecherche = '', $pView = 'all') {
		return $this -> Contenu($pCleRecherche, $pView);
	}

	/**
	* Cherche la première valeur trouvée dans la vue par une clé
	*
	* @param string $pCleRecherche clé de recherche
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

		$resultat_Tab = json_decode($resultat, true);
		$listeElements = $resultat_Tab['rows'];

		// Ne prend que la première occurence :
		$element = null;
		if (sizeof($listeElements) !== 0) {
			$element = $listeElements[0]['value'];
		}

		return $element;
	}

	/**
	 * Chercher toutes les valeurs à partir d'une clé
	 *
	 * @param string $pCleRecherche clé de recherche
	 *
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
	 * Enregistre la définition de la vue
	 */
	function Enregistrer() {
	}
}
?>
