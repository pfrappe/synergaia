<?php
/** SynerGaia fichier de gestion du @Dictionnaire
 * @todo rendre indépendant de la casse via tableau de traduction nomminsucule => méthode ou propriété synergaia
 */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dictionnaire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dictionnaire_trait.php';
} else {
	/** trait vide par défaut pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Dictionnaire_trait{};
}

/**
 * Classe SynerGaia de gestion du dictionnaire SynerGaia
 * @version 2.6
 */
class SG_Dictionnaire extends SG_Base {

	/** string Type SynerGaia '@Dictionnaire' */
	const TYPESG = '@Dictionnaire';

	/** string Code de la base de stockage */
	const CODEBASE = 'synergaia_dictionnaire';

	/** string Préfixe des objets système */
	const PREFIXE_SYSTEME = '@';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/**
	 * Construction de l'objet
	 */
	function __construct() {
	}

	/**
	 * Détermine le code de la base à partir du code d'un objet
	 * @version 2.2 @Photo
	 * @version 2.6 @Paquet
	 * @param string $pCodeObjet code de l'objet cherché
	 * @param boolean $pRefresh faut-il forcer le recalcul ? false par défaut
	 * @return string code de la base stockant l'objet cherché
	 * @todo vérifier que cela fonctionne pour les classes d'opération après 2.1
	 */
	static function getCodeBase($pCodeObjet = '', $pRefresh = false) {
		$codeObjet = SG_Texte::getTexte($pCodeObjet);
		$codeBase = '';
		switch ($codeObjet) {
			case '@DictionnaireVue' :
				$codeBase = SG_DictionnaireVue::CODEBASE;
				break;
			case '@DictionnaireObjet' :
			case '@DictionnaireMethode' :
			case '@DictionnairePropriete' :
			case '@DictionnaireBase' :
			case '@ModeleOperation' :
			case '@Theme' :
			case '@SiteInternet' :
				$codeBase = SG_Dictionnaire::CODEBASE;
				break;
			case '@Libelle' :
				$codeBase = SG_Libelle::CODEBASE;
				break;
			case '@Operation' :
				$codeBase = SG_Operation::CODEBASE;
				break;
			case '@Utilisateur' :
				$codeBase = SG_Annuaire::CODEBASE;
				break;
			case '@Parametre' :
				$codeBase = SG_Parametre::CODEBASE;
				break;
			case '@Formulaire' :
				$codeBase = SG_Formulaire::CODEBASE;
				break;
			case '@Paquet':
				$codeBase = SG_Paquet::CODEBASE;
				break;
			case '@Photo' :
				$codeBase = SG_Photo::CODEBASE;
				break;
			case '@Repertoire' :
				$codeBase = SG_Repertoire::CODEBASE;
				break;
			default :
				if (SG_Operation::isOperation($codeObjet)) {
					$codeBase = SG_Operation::CODEBASE;
				} else {
					// Cherche en cache
					$codeCache = 'getCodeBase(' . $codeObjet . ')';
					if (!$pRefresh and SG_Cache::estEnCache($codeCache, false) === true) {
						// Lit en cache
						$codeBase = SG_Cache::valeurEnCache($codeCache, false);
					} else {
						// Pas en cache : calcule la valeur
						// TODO : chercher le codeBase dans les parents si besoin
						$docObjet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, '@DictionnaireObjet', $codeObjet);
						if (getTypeSG($docObjet) !== '@Erreur') {
							$codeBase = $docObjet -> getValeur('@Base', strtolower($codeObjet));
						}
						// Enregistre en cache
						SG_Cache::mettreEnCache($codeCache, $codeBase, false);
					}
				}
				break;
		}
		return $codeBase;
	}

	/**
	 * Détermine si l'objet demandé est un objet systeme (codé) 
	 * PENSER A VIDER LE CACHE EN CAS DE MODIFICATION !!
	 * @version 2.6 @Periode
	 * @version 2.7 @Couleur
	 * @param string $pTypeObjet objet demandé
	 * @return boolean objet systeme
	 * @todo voir cas des noms de variables identiques à des noms d'objets système ??
	 */
	static function isObjetSysteme($pTypeObjet = '') {
		$ret = false;
		$codeObjet = $pTypeObjet;
		$codeCache = 'isObjetSysteme(' . $codeObjet . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true) {
			$ret = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			switch ($codeObjet) {// Si l'objet est un objet fondamental accessible par un New sur une classe programmée
			// par ordre de fréquence probable
				case '@Texte' :
				case '@TexteParametre' :
				case '@TexteRiche' :
				case '@Document' :
				case '@DocumentCouchDB' :
				case '@DocumentDominoDB' :
				case '@Collection' :
				case '@Champ' :
				case '@Date' :
				case '@DateHeure' :
				case '@Fichier' :
				case '@Fichiers' :
				case '@Formule' :
				case '@Heure' :
				case '@Adresse' :
				case '@Nombre' :
				case '@Annuaire' :
				case '@Dictionnaire' :
				case '@DictionnaireBase' :
				case '@DictionnaireMethode' :
				case '@DictionnaireObjet' :
				case '@DictionnairePropriete' :
				case '@DictionnaireVue' :
				case '@ModeleOperation' :
				case '@Utilisateur' :
				case '@Ville' :
				case '@Objet' :
				case '@ObjetComposite' :
				// à partir d'ici par ordre alphabétique
				case '@Application' :
				case '@Base' :
				case '@BaseCouchDB' :
				case '@BaseDominoDB' :
				case '@Bouton' :
				case '@Cache' :
				case '@Cadre' :
				case '@Calendrier' :
				case '@CanalODBC' :
				case '@Categorie' :
				case '@Compilateur' :
				case '@Config' :
				case '@Connexion' :
				case '@CouchDB' :
				case '@Couleur' :
				case '@Dates' :
				case '@DominoDB' :
				case '@Dossier' :
				case '@Echelle' :
				case '@Email' :
				case '@Erreur' :
				case '@Evenement' :
				case '@Formulaire' :
				case '@Graphique' :
				case '@HTML' :
				case '@Icone' :
				case '@IDDoc' :
				case '@Image' :
				case '@Import' :
				case '@Installation' :
				case '@Libelle' :
				case '@Lien' :
				case '@Log' :
				case '@Matrice' :
				case '@Memo' :
				case '@Montant' :
				case '@MotDePasse' :
				case '@Navigation' :
				case '@Notation' :
				case '@Operation' : // opérations dérivées voir default
				case '@PageInternet' :
				case '@Paquet' :
				case '@Parametre' :
				case '@Periode':
				case '@Personne' :
				case '@Photo';
				case '@Profil' :
				case '@Repertoire' :
				case '@Rien' :
				case '@Rythme' :
				case '@SiteInternet' :
				case '@SynerGaia' :
				case '@Table' :
				case '@Tableur' :
				case '@TexteFormule' :
				case '@Theme' :
				case '@ThemeGraphique' :
				case '@Update' :
				case '@VraiFaux' :
				case '@Vue' :
				case '@VueCouchDB' :
				case '@VueDominoDB' :
					$ret = true;
					break;
				default :
					if(SG_Operation::isOperation($codeObjet)) {
						$ret = true;
					}
					break;
			}
			SG_Cache::mettreEnCache($codeCache, $ret, false);
		}
		return $ret;
	}

	/**
	 * Renvoie un objet SG_DictionnaireObjet
	 * 
	 * @since 1.0.5
	 * @param string $pCode code de l'objet recherché 
	 * @return SG_DictionnaireObjet l'objet trouvé
	 * @formula @DictionnaireObjet("code")
	 */
	static function getDictionnaireObjet($pCode) {
		return $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, '@DictionnaireObjet', $pCode);
	}

	/** 
	 * Détermine si l'objet demandé existe
	 * @since 1.0.6
	 * @param string $pTypeObjet objet demandé
	 * @param boolean $pForce force la mise à jour du cache
	 * @return boolean objet existe
	 */
	static function isObjetExiste($pTypeObjet = '', $pForce = false) {
		$ret = false;
		$codeObjet = $pTypeObjet;
		$codeCache = 'isObjetExiste(' . $codeObjet . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true and $pForce === false) {
			$ret = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			$collecObjets = SG_Dictionnaire::getDictionnaireObjet($codeObjet);
			if (getTypeSG($collecObjets) !== '@Erreur') {
				$ret = true;
			}
			SG_Cache::mettreEnCache($codeCache, $ret, false);
		}
		return $ret;
	}

	/** 
	 * Détermine si la méthode demandée existe
	 * @since 1.0.6
	 * @version 2.1 param1 objet , IME
	 * @param string|SG_Objet $pTypeObjet objet sur lequel la méthode est demandée
	 * @param string $pMethode code de la méthode demandée
	 * @param boolean $pForce
	 * @return boolean méthode existe
	 */
	static function isMethodeExiste($pTypeObjet = '', $pMethode = '', $pForce = false) {
		$ret = false;
		if(is_string($pTypeObjet)) {
			$type = $pTypeObjet;
		} else {
			$type = getTypeSG($pTypeObjet);
		}
		$codeMethode = $type . '.' . $pMethode;
		if ($codeMethode === '@Rien.@Chercher') {
			$ret = true;
		} else {
			$codeCache = 'IME(' . $codeMethode . ')';
			if (SG_Cache::estEnCache($codeCache, false) === true and $pForce === false) {
				$ret = SG_Cache::valeurEnCache($codeCache, false);
			} else {
				$docObjet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnaireMethode', $codeMethode);
				if (getTypeSG($docObjet) !== '@Erreur') {
					$ret = true;
				}
				SG_Cache::mettreEnCache($codeCache, $ret, false);
			}
		}
		return $ret;
	}

	/**
	* Recherche une propriété
	* @version 1.1 ajout valeur défaut
	* @param string $pTypeObjet objet sur lequel la propriété est demandée
	* @param string $pPropriete code de la propriété demandée
	* @param any $pValeurDefaut
	* @return document @DictionnairePropriete
	* @formule @Chercher("@DictionnairePropriete").@Premier
	*/
	static function getPropriete($pTypeObjet = '', $pPropriete = '', $pValeurDefaut = '') {
		$ret = $pValeurDefaut;
		$codePropriete = $pTypeObjet . '.' . $pPropriete;
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnairePropriete', $codePropriete);
		return $ret;
	}

	/**
	 * Détermine si la propriété demandée existe
	 * @since 1.0.7
	 * @version 2.1 code IPE
	 * @param string $pTypeObjet objet sur lequel la propriété est demandée
	 * @param string $pPropriete code de la propriété demandée
	 * @param boolean $pForce force la mise à jour du cache (default false)
	 *
	 * @return boolean propriété existe
	 */
	static function isProprieteExiste($pTypeObjet = '', $pPropriete = '', $pForce = false) {
		$ret = false;
		$codePropriete = $pTypeObjet . '.' . $pPropriete;

		$codeCache = 'IPE(' . $codePropriete . ')';
		if (SG_Cache::estEnCache($codeCache, false) !== true or $pForce === true) {
			$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnairePropriete', $codePropriete);
			if (! $doc instanceof SG_Erreur) {
				SG_Cache::mettreEnCache($codeCache, 'o', false);
			} else {
				SG_Cache::mettreEnCache($codeCache, 'n', false);
			}
		}
		if ($ret === false) { // sinon $ret est une @erreur
			$ret = (SG_Cache::valeurEnCache($codeCache, false) === 'o');
		}
		return $ret;
	}

	/**
	 * Cherche l'action de la méthode demandée
	 * 
	 * @since 1.0.7
	 * @version 2.4 fournit le texte (au lieu de l'action)
	 * @param string $pTypeObjet objet sur lequel la méthode est demandée
	 * @param string $pMethode code de la méthode demandée
	 * @param boolean $pForce
	 * @return string action de la méthode
	 */
	static function getActionMethode($pTypeObjet = '', $pMethode = '', $pForce = false) {
		$ret = false;
		$codeMethode = $pTypeObjet . '.' . $pMethode;
		$codeCache = 'getActionMethode(' . $codeMethode . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true and $pForce = false) {
			$ret = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			$action = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnaireMethode', $codeMethode);
			if ($action instanceof SG_DictionnaireMethode) {
				$ret = $action -> getValeur('@Action','');
			}
			SG_Cache::mettreEnCache($codeCache, $ret, false);
		}
		return $ret;
	}

	/** 1.1 param $pDefaut ; 2.4 récup correct du code modèle dans la boucle
	 * Détermine la liste des champs d'un type d'objet (éventuellement seuls champs du type donné)
	 * On remonte l'ascendance des objets de type @Document.
	 * Si on ne donne pas de $pCodeObjet, tous les champs du modèle demandé à travers tout le dictionnaire des propriétés
	 *
	 * @param string $pCodeObjet code de l'objet cherché
	 * @param string $pModele si fourni, on limite la liste aux champs de ce modèle
	 * @param array string $pDefaut : codechamp => null : tableau des propriétés par défaut (permet de les mettre en tête)
	 *
	 * @return array liste des champs
	 */
	static function getListeChamps($pCodeObjet = '', $pModele = '', $pDefaut = array()) {
		$codeObjet = $pCodeObjet;
		$listeChamps = $pDefaut;
		// recherche directe
		$champs = self::getProprietesObjet ($codeObjet, $pModele, true);
		foreach ($champs as $key => $objet) {
			if (!isset($listeChamps[$key]) or $listeChamps[$key] === null) {
				$listeChamps[$key] = $objet;
			}
		}
		// recherche dans l'ascendance
		// Cherche le modèle de l'objet
		$codeModele = '';
		$docObjet = SG_Dictionnaire::getDictionnaireObjet($codeObjet);
		if ($docObjet !== null) {
			$codeModele = $docObjet -> getValeur('@Modele', '');
			$ipos = strrpos($codeModele, '/'); // todo voir le cas de nouveau document dont le modèle n'est pas base/modele mais base/id
			if (! is_bool($ipos)) {
				$codeModele = substr($codeModele, $ipos + 1);
			}
		}
		// Si on a un modèle, cherche aussi les champs du modèle (si doublon, on garde le précédent qui est dérivé)
		if ($codeModele !== '') {
			if (SG_Dictionnaire::modeleDeriveDeDocument($codeModele) === true) {
				$listeChampsParent = SG_Dictionnaire::getListeChamps($codeModele, $pModele);
				// Ajoute les champs trouvés chez le parent, sans les doublons éventuels
				foreach ($listeChampsParent as $key => $champParent) {
					if (! array_key_exists($key, $listeChamps)) {
						$listeChamps[$key] = $champParent;
					}
				}
			}
		}
		return $listeChamps;
	}

	/**
	 * Détermine le modèle de l'objet, de la méthode ou de la propriété (dans cet ordre)
	 * @since 1.0.7
	 * @version 2.1 rechercher aussi sur la classe de l'objet Synergaia
	 * @param string $pCode code de l'objet/méthode/propriété cherché (objet.code)
	 * @param boolean $pForce
	 *
	 * @return string code du modèle de l'objet
	 */
	static function getCodeModele($pCode = '', $pForce = false) {
		$codeElement = $pCode;
		$codeModele = '';
		$codeCache = 'getCodeModele(' . $codeElement . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true and $pForce === false) {
			$codeModele = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			// Si on a un objet (pas de ".", exemple @Document)
			$ipos = strpos($codeElement, '.');
			if ($ipos === false) {
				$docElement = SG_Dictionnaire::getDictionnaireObjet($codeElement);
				if ($docElement !== null) {
					$codeModele = $docElement -> getValeur('@Modele', '');
				}
			} else {
				// On a un '.' donc on cherche d'abord une méthode (exemple @Document.@Afficher)
				$docObjet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnaireMethode', $codeElement);
				if (getTypeSG($docObjet) !== '@Erreur') {
					$codeModele = $docObjet -> getValeur('@Modele', '');
				} else {
					// On n'a pas trouvé de méthode, on cherche une propriété
					$docObjet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnairePropriete', $codeElement);
					if (getTypeSG($docObjet) !== '@Erreur') {
						$codeModele = $docObjet -> getValeur('@Modele', '');
					} else {
						// Si on n'a rien trouvé, on cherche dans le modèle du parent (si possible)
						$elements = explode('.', $codeElement);
						$codeObjet = $elements[0];
						if (($codeObjet !== '@Rien') && ($codeObjet !== '')) {
							$codeElement = $elements[1];
							$codeObjetParent = SG_Dictionnaire::getCodeModele($codeObjet);
							if ($codeObjetParent !== '') {
								$codeModele = SG_Dictionnaire::getCodeModele($codeObjetParent . '.' . $codeElement);
							}
						}
					}
				}
			}
			// 1.0.7 enlever le code base si existe et chercher le code
			$i = strpos($codeModele,'/');
			if ( $i !== false) {
				$modele = $_SESSION['@SynerGaia'] -> getObjet($codeModele);
				$codeModele = $modele -> getValeur('@Code');
			}
			SG_Cache::mettreEnCache($codeCache, $codeModele, false);
		}
		return $codeModele;
	}

	/** 1.0.6
	 * Détermine le libellé de l'objet, de la méthode ou de la propriété
	 *
	 * @param string $pCode code de l'objet/méthode/propriété cherché
	 * @return string libellé
	 */
	static function getLibelle($pCode = '') {
		$codeElement = $pCode;
		$libelle = '';
		$codeCache = 'getLibelle(' . $codeElement . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true) {
			$libelle = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			// Si on a un objet (pas de ".")
			if (strpos($codeElement, '.') === false) {
				$docElement = SG_Dictionnaire::getDictionnaireObjet($codeElement);
				if ($docElement !== null) {
					$libelle = $docElement -> getValeur('@Titre', '');
				}
			} else {
				// On a un '.' donc on cherche une méthode
				$docObjet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE,'@DictionnaireMethode', $codeElement);
				if (getTypeSG($docObjet) !== '@Erreur') {
					$libelle = $docObjet -> getValeur('@Titre', '');
				} else {
					// On n'a pas trouvé de méthode, on cherche une propriété
					$docObjet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, '@DictionnairePropriete', $codeElement);
					if (getTypeSG($docObjet) !== '@Erreur') {
						$libelle = $docObjet -> getValeur('@Titre', '');
					} else {
						// Si on n'a rien trouvé, on cherche le modèle du parent (si possible)
						$elements = explode('.', $codeElement);
						if (sizeof($elements) === 2) {
							$codeObjet = $elements[0];
							if (($codeObjet !== '@Rien') && ($codeObjet !== '')) {
								$codeElement = $elements[1];
								$codeObjetParent = SG_Dictionnaire::getCodeModele($codeObjet);
								if ($codeObjetParent !== '') {
									$libelle = SG_Dictionnaire::getLibelle($codeObjetParent . '.' . $codeElement);
								}
							}
						} else {
							$libelle = '@Rien' . '.' . $elements[sizeof($elements) - 1];
						}
					}
				}
			}

			if ($libelle === '') {
				$libelle = $codeElement;
			}
			SG_Cache::mettreEnCache($codeCache, $libelle, false);
		}

		return $libelle;
	}

	/** 1.0.6
	 * Détermine la formule des valeurs proposées pour une propriétés
	 *
	 * @param string $pCode code de la propriété cherchée
	 * @return string formule
	 */
	static function getFormuleValeursPossibles($pCode = '') {
		$codeElement = $pCode;
		$formule = '';
		$codeCache = 'getFormuleValeursPossibles(' . $codeElement . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true) {
			$formule = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, '@DictionnairePropriete', $codeElement);
			if (getTypeSG($doc) !== '@Erreur') {
				$formule = $doc -> getValeur('@ValeursPossibles', '');
			} else {
				// Si on n'a rien trouvé, on cherche le modèle du parent (si possible)
				$elements = explode('.', $codeElement);
				$codeObjet = $elements[0];
				if (($codeObjet !== '@Rien') && ($codeObjet !== '')) {
					$codeElement = $elements[1];
					$codeObjetParent = SG_Dictionnaire::getCodeModele($codeObjet);
					if ($codeObjetParent !== '') {
						$formule = SG_Dictionnaire::getFormuleValeursPossibles($codeObjetParent . '.' . $codeElement);
					}
				}
			}
			SG_Cache::mettreEnCache($codeCache, $formule, false);
		}

		return $formule;
	}

	/** 1.0.6 ; 2.1 cache sous forme texte
	 * Détermine si la propriété demandée accepte les valeurs multiples
	 *
	 * @param string $pCode code de la propriété cherchée
	 * @return boolean valeurs multiples autorisées
	 * @formula : @DictionnairePropriete($pCode).@Multiple
	 */
	static function isMultiple($pCode = '') {
		$codeElement = $pCode;
		$multiple = false;
		$codeCache = 'isMultiple(' . $codeElement . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true) {
			$multiple = SG_Cache::valeurEnCache($codeCache, false) === 'o';
		} else {
			$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, '@DictionnairePropriete', $codeElement);
			if (getTypeSG($doc) !== '@Erreur') {
				$tmpMultiple = new SG_VraiFaux($doc -> getValeur('@Multiple', ''));
				$multiple = $tmpMultiple -> estVrai() ;
			} else {
				// Si on n'a rien trouvé, on cherche le modèle du parent (si possible)
				$elements = explode('.', $codeElement);
				$codeObjet = $elements[0];
				if (($codeObjet !== '@Rien') and ($codeObjet !== '')) {
					$codePropriete = $elements[1];
					$codeObjetParent = SG_Dictionnaire::getCodeModele($codeObjet);
					if ($codeObjetParent !== '') {
						$multiple = SG_Dictionnaire::isMultiple($codeObjetParent . '.' . $codePropriete);
					}
				}
			}
			SG_Cache::mettreEnCache($codeCache, ($multiple?'o':'n'), false);
		}
		return $multiple;
	}
	/**
	 * isObjetDocument ; true si le type d'objet dérive de @Document, false sinon
	 * 
	 * @version 1.1 : paramètre peut être un objet ;
	 * @version 1.3.1 in_array
	 * @version 2.7 getObjetsDocument
	 * @param string|SG_Objet $pType : type de l'objet à analyse
	 * @return boolean : true si le type d'objet dérive de @Document
	 */
	static function isObjetDocument ($pType = '') {
		if (is_object($pType)) {
			$type = getTypeSG($pType);
		} else {
			$type = $pType;
		}
		return in_array($type, self::getObjetsDocument());
	}

	/**
	* Affichage du dictionnaire (dérive de SG_Objet)
	*
	* @return string code HTML d'affichage du dictionnaire
	* @formula @Dictionnaire.@ExporterJSON via ajax
	*/
	function Afficher() {
		$idBloc = SG_SynerGaia::idRandom();
		$urlJSON = SG_Navigation::URL_PRINCIPALE . '?' . SG_Navigation::URL_VARIABLE_CODE . '=xdi';
		$html = '<div id="dictionnaire' . $idBloc . '" class="consultationDictionnaire"></div>' . PHP_EOL;
		$script = '<script>consulter_dictionnaire("' . $idBloc . '","' . $urlJSON . '");</script>' . PHP_EOL;

		return $html . $script;

	}
	/** 1.0.6
	* @formula : @Chercher("@DictionnaireObjet")
	*/
	static function Objets() {
		return SG_Rien::Chercher('@DictionnaireObjet');
	}

	/**
	* Export du dictionnaire en JSON
	* @FIXME : problème si un objet est mal défini (vide) => JSON non valide
	* @return string JSON du contenu du dictionnaire
	*/
	static function ExporterJSON() {
		/**
		 * Genere le code JSON du dictionnaire à partir du modèle fourni
		 * L'export est fait de manière récursive pour respecter l'ordre de dérivation des objets (pour un import ultérieur)
		 *
		 * @param SG_Collection $listeComplete
		 * @param string $codeModele
		 * @return string
		 */
		function exportObjetsDuModele($listeComplete = null, $codeModele = '') {
			$listeObjets = SG_Dictionnaire::ObjetsDuModele($codeModele, $listeComplete);
			$retJSON = '';
			// Si on a trouvé quelquechose
			$nbObjetsTrouves = $listeObjets -> Compter() -> toInteger();
			if ($nbObjetsTrouves !== 0) {
				for ($j = 0; $j < $nbObjetsTrouves; $j++) {
					$retJSON .= '{';
					$codeObjet = $listeObjets -> elements[$j] -> getValeur('@Code', '');
					if ($codeObjet !== '') {
						$retJSON .= '"code": "' . $codeObjet . '"';
						$retJSON .= ',';
						$retJSON .= '"libelle": "' . SG_Dictionnaire::getLibelle($codeObjet) . '"';
						// Ajoute les propriétés de l'objet
						
						$listeProprietes = SG_Dictionnaire::getListeChamps($codeObjet);
						if (sizeof($listeProprietes) !== 0) {
							$retJSON .= ',';
							$retJSON .= '"proprietes": [';
							$deb = true;
							foreach($listeProprietes as $propriete => $modele) {
								if ($deb === false) {
									$retJSON .= ',';
								} else {
									$deb = false;
								}
								$retJSON .= '{';
								$retJSON .= '"code": "' . $propriete . '"';
								$retJSON .= ',';
								$txtProprieteMultiple = '';
								$tmpProprieteMultiple = SG_Dictionnaire::isMultiple($codeObjet . '.' . $propriete);
								if ($tmpProprieteMultiple === true) {
									$txtProprieteMultiple = ', multiple';
								}
								$retJSON .= '"modele": "' . $modele . $txtProprieteMultiple . '"';
								$retJSON .= ',';
								$retJSON .= '"libelle": "' . SG_Dictionnaire::getLibelle($codeObjet . '.' . $propriete) . '"';
								$retJSON .= '}';
							}
							$retJSON .= ']';
						}
						if(false) { //boucle
							$tmpSuite = exportObjetsDuModele($listeComplete, $codeObjet);
							if ($tmpSuite !== '') {
								$retJSON .= ',';
								$retJSON .= '"children": [' . $tmpSuite . ']';
							}
						}
					}
					$retJSON .= '}';
					if ($j < ($nbObjetsTrouves - 1)) {
						$retJSON .= ',';
					}
				}
			}
			return $retJSON;
		}
		return exportObjetsDuModele(SG_Dictionnaire::Objets());
	}

	/**
	 * Retourne le tableau des objets du type @Document ou en dérivant
	 * 
	 * @since 1.0.6
	 * @version 2.7 getObjetsDocument, DOD
	 * @param boolean $pNomsSeuls : ne retourner qu'un tabeau de nom s (au lieu des objets)
	 * @param boolean $pRefresh : forcer la mise à jour du cache
	 * @return SG_Collection|SG_Erreur tableau des noms ou des objets @DictionnaireObjet trouvés ; ou erreur
	 */	
	static function ObjetsDocument ($pNomsSeuls = false, $pRefresh = false) {
		$ret = new SG_Collection();
		$nomsSeuls = getBooleanValue($pNomsSeuls);		
		$refresh = getBooleanValue($pRefresh);
		
		$codeCache = 'DOD';
		if (SG_Cache::estEnCache($codeCache, false) and $pRefresh === false) {
			if ($nomsSeuls) {
				$tmp = self::getObjetsDocument();
				foreach ($tmp as $code) {
					$ret -> elements[] = new SG_Texte($code);
				}
			} else {
				$ret -> elements = unserialize(SG_Cache::valeurEnCache($codeCache, false));
			}
		} else {
			if ($nomsSeuls) {
				$tmp = self::getObjetsDocument(true);
				foreach ($tmp as $code) {
					$ret -> elements[] = new SG_Texte($code);
				}
			} else {
				$liste = array();
				$tmp = self::getObjetsDocument(true);
				foreach ($tmp as $code) {
					$liste[] = new SG_DictionnaireObjet($code);
				}
				$ret -> elements = $liste;
				SG_Cache::mettreEnCache($codeCache, serialize($liste), false);
			}
		}
		return $ret;
	}

	/**
	 * Retourne la liste des modèles d'objets dérivés d'un modèle donné
	 * @since 1.0.6
	 * @version 1.3.3 foreach
	 * @version 2.7 test SG_Erreur
	 * @param string|SG_Texte|SG_Formule $pCodeModele
	 * @param [SG_Collection] $listeComplete liste de modèles
	 * @return SG_Collection|SG_Erreur
	 */
	static function ObjetsDuModele($pCodeModele = '', $listeComplete = null) {
		$codeModele = SG_Texte::getTexte($pCodeModele);
		if ($listeComplete === null) {
			$listeComplete = SG_Dictionnaire::Objets();
		}
		if ($listeComplete instanceof SG_Erreur) {
			$ret = $listeComplete;
		} else {
			$ret = new SG_Collection();
			foreach($listeComplete -> elements as $objet) {
				$modele = SG_Champ::extractCodeDocument($objet -> getValeur('@Modele'));
				if ($codeModele === '' or $modele === $codeModele) {
					$ret -> Ajouter($objet);
				}
			}
		}
		return $ret;
	}

	/**
	 * liste des champs de type document d'un modèle
	 * @since 1.0.6
	 * @param indefini : nom du modèle à analyser
	 * @param indéfini donnant un booleen : forcer un refresh du cache
	 * 
	 * @return SGCollection contenant un tableau : nom du champ | nom du modèle | base de stockage de l'objet atteint
	 */
	static function ChampsDocument($pCodeModele = '', $pRefresh = false) {
		$ret = new SG_Collection();
		$refresh = getBooleanValue($pRefresh);
		$codeModele = SG_Texte::getTexte($pCodeModele);
		
		$codeCache = '@Dictionnaire.@Champs(' . $codeModele . ')';
		if (SG_Cache::estEnCache($codeCache, false) and $refresh === false) {
			$ret -> elements = json_decode(SG_Cache::valeurEnCache($codeCache, false), true);
		} else {
			$champs = self::getListeChamps($codeModele);
			foreach($champs as $champ => $modele) {
				$codebase = self::getCodeBase($modele);
				$ret -> elements[] = $champ . '|' . $modele . '|' . $codebase;
			}
			SG_Cache::mettreEnCache($codeCache, json_encode($ret -> elements), false);
		}
		return $ret;
	}		
	/** 1.0.7
	 * retourne le type d'objet document vers lequel la propriété renvoie
	 * 
	 * @param string $pCode code de la propriété cherchée (objet.propriété)
	 *
	 * @return vide ou nom du type d'objet document
	 */
	static function isLien($pCode = '') {
		$codeElement = $pCode;
		$ret = '';
		// en cache ?
		$codeCache = '@Dictionnaire.isLien(' . $codeElement . ')';
		if (SG_Cache::estEnCache($codeCache, false) === true) {
			$ret = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			// pas en cache : cherche si le champ fait partie des champs documents du modèle
			$modele = SG_Dictionnaire::getCodeModele($codeElement);
			$docs = SG_Dictionnaire::ObjetsDocument();
			if(array_key_exists($modele, $docs -> elements)) {
				$ret = $modele;
			}
			SG_Cache::mettreEnCache($codeCache, $ret, false);
		}
		return $ret;
	}

	/**
	 * Un modele d'obbjet dérive-t-il de SG_Document ?
	 * @version 1.3 true pour accélérer (dérive de SG_Objet, donc non static)
	 * @param string $pCode
	 * @return boolean
	 */
	static function modeleDeriveDeDocument($pCode = '') {
		return array_key_exists($pCode, SG_Dictionnaire::ObjetsDocument(true) -> elements);
	}
		
	/**
	 * Crée directement un @DictionnaireObjet et ses @DictionnairePropriété
	 * @since 1.0.6
	 * @version1.3.1 ok si objet existe déjà
	 * @param string Objet à créer : "@Code|@Modele|@Titre" par défaut modèle = @Document, titre = @Code
	 * @param string autant que de propriétés à créer : "@Code|@Modele|@Titre" par défaut modèle = @Texte, titre = @Code
	 *
	 * @return boolean est ou non un lien
	 */	
	static function DefinirObjet() {
		$ret = new SG_VraiFaux(false);
		$args = func_get_args();
		if (isset($args[0])) {
			// création de l'objet
			$parm = new SG_Texte($args[0]);
			$parm = $parm -> toString();
			$parmObjet = explode(',',$parm);
			if(SG_Dictionnaire::isObjetExiste($parmObjet[0])) {
				$ret = new SG_Erreur('0068', $parmObjet[0]);
			}
			$objet = new SG_DictionnaireObjet($parmObjet[0]);
			$objet -> setValeur('@Code', $parmObjet[0]);
			$objet -> setValeur('@Type', '@DictionnaireObjet');
			$objet -> setValeur('@Base', strtolower($parmObjet[0]));
			if (isset($parmObjet[1])) {
				if ($parmObjet[1] === '') {						
					$objet -> setValeur('@Modele',  '@Document');
				} else {
					$objet -> setValeur('@Modele', $parmObjet[1]);
				}
			} else {
				$objet -> setValeur('@Modele',  '@Document');
			}
			if (isset($parmObjet[2])) {
				if ($parmObjet[2] === '') {						
					$objet -> setValeur('@Titre',  $parmObjet[0]);
				} else {
					$objet -> setValeur('@Titre', $parmObjet[2]);
				}
			} else {
				$objet -> setValeur('@Titre',  $parmObjet[0]);
			}
			$objet -> Enregistrer();
			SG_Dictionnaire::isObjetExiste($parmObjet[0], true); // force la mise à jour du cache
			// création des propriétés
			for($i = 1; $i < sizeof($args); $i++) {
				$parm = new SG_Texte($args[$i]);
				$parm = $parm -> toString();
				$parmPropriete = explode(',',$parm);
				if(SG_Dictionnaire::isProprieteExiste($parmObjet[0], $parmPropriete[0], true)) {
					$ret = new SG_Erreur('La propriété ' . $parmPropriete[0] . 'existe déjà !');
				} else {						
					$code = $parmObjet[0] . '.' . $parmPropriete[0];
					$propriete = new SG_DictionnairePropriete($code);
					$propriete -> setValeur('@Code', $code);
					$propriete -> setValeur('@Propriete', $parmPropriete[0]);
					$propriete -> setValeur('@Objet', self::CODEBASE . '/' . $parmObjet[0]);
					$propriete -> setValeur('@Type', '@DictionnairePropriete');
					if (isset($parmPropriete[1])) {
						if ($parmPropriete[1] === '') {						
							$propriete -> setValeur('@Modele', '@Texte');
						} else {
							$propriete -> setValeur('@Modele', $parmPropriete[1]);
						}
					} else {
						$propriete -> setValeur('@Modele',  '@Texte');
					}
					if (isset($parmPropriete[2])) {							
						if ($parmPropriete[2] === '') {						
							$propriete -> setValeur('@Titre', $parmPropriete[0]);
						} else {
							$propriete -> setValeur('@Titre', $parmPropriete[2]);
						}
					} else {
						$propriete -> setValeur('@Titre',  $parmPropriete[0]);
					}
					$propriete -> Enregistrer();
					SG_Dictionnaire::isProprieteExiste($parmObjet[0], $parmPropriete[0], true); // force la mise à jour du cache
				}
			}
			$ret = $objet;
		}
		return $ret;
	}
	/** 1.1 ajout ; 1.3.1 $pCode peut etre un objet ; 2.1 $pForce, nlles classes compilées
	* fournit la classe système pour la création d'un objet de ce type
	* @param (string ou objet) $pCode : code de l'objet dont on cherche la classe (si $pCode est un objet : modele de l'objet)
	* @param (boolean) $pForce : si vrai recalcul systématique
	* @return string : classe PHP de l'objet SynerGaïa
	*/
	static function getClasseObjet($pCode = '', $pForce = false) {
		$code = SG_Texte::getTexte($pCode);
		$ret = '';
		if ($code !== '') {
			if(substr($code, 0, 1) === '@') {
				$ret = 'SG_' . substr($code, 1);
			} else {
				$ret = $code;
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* recherche la première classe SG_ de l'objet
	* @param (string ou objet) $pObjet : objet ou classe dont veut chercher la classe SG
	**/
	static function getClasseSG($pObjet) {
		$ret = get_class($pObjet);
		while (substr($ret, 0, 3) !== 'SG_') {
			$ret = get_parent_class($ret);
		}
		return $ret;
	}

	/**
	* Crée le thème et les modèles d'opération de base pour un objet
	* @version 2.3 test $retour non objet
	* @param string|SG_Texte|SG_Formule $pObjet : nom de l'objet à gérer
	* @param string|SG_Texte|SG_Formule $pIcone
	* @return string HTML résultant
	*/
	static function DefinirMenu ($pObjet = '', $pIcone = '') {
		$ret = '<ul>';
		$objet = SG_Texte::getTexte($pObjet);
		$nomIcone = SG_Texte::getTexte($pIcone);
		if ($nomIcone === '') {
			$nomIcone = strtolower($objet);
		}
		$icone = new SG_Icone($nomIcone . 'png');
		if ($icone -> Existe() -> estVrai() === false) {
			$nomIcone = 'application';
			$icone = new SG_Icone($nomIcone . '.png');
		}
		if ($objet !== '') {
			$base = SG_Dictionnaire::CODEBASE;
			// créer le thème associé
			$theme = new SG_Theme();
			$theme -> setValeur('@Titre', $objet);
			$theme -> setValeur('@Code', $objet . '_theme');
			$theme -> setValeur('@Position', 300); // arbitraire
			$theme -> setValeur('@IconeTheme', $icone -> code);
			$retour = $theme -> Enregistrer();
			if (is_object($retour) and !$retour -> estErreur()) {
				$ret .= '<li>Thème \'' . $objet . '\' créé;</li>';
			} else {
				$ret .= '<li>Thème \'' . $objet . '\' NON créé;</li>';
			}
			$idtheme = $theme -> getUUID();
			//quelles sont les propriétés Titre et Code ?
			$titre = '@Titre';
			if (self::isProprieteExiste($objet, 'Titre') === true) {
				$titre = 'Titre';
			}
			$code = '@Code';
			if (self::isProprieteExiste($objet, 'Titre') === true) {
				$code = 'Code';
			}
			// créer modèle d'opération "Ajouter"
			$codeOpe = $objet . 'Nouveau';
			$modele = new SG_ModeleOperation($base . '/' . $codeOpe);
			$modele -> setValeur('@Code', $codeOpe);
			$modele -> setValeur('@Titre', 'Ajouter un ' . $objet . '');
			$modele -> setValeur('@Position', 10);
			$modele -> setValeur('@Phrase', '@Nouveau("' . $objet . '").@Modifier|Enregistrer>.@Afficher');
			$iconeOpe = new SG_Icone($nomIcone . '-add.png');
			if ($iconeOpe -> Existe() -> estVrai() === false) {
				$iconeOpe = new SG_Icone('add.png');
			}
			$modele -> setValeur('@IconeOperation', $icone -> code);
			$modele -> setValeur('@Theme', $idtheme);
			$retour = $modele -> Enregistrer();
			if (is_object($retour) and !$retour -> estErreur()) {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Nouveau\' créé;</li>';
			} else {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Nouveau\' NON créé !! base : '.$modele -> doc -> codeBase . '</li>';
			}
			// créer modèle d'opération "Gérer"
			$codeOpe = $objet . 'Gerer';
			$modele = new SG_ModeleOperation($base . '/' . $codeOpe);
			$modele -> setValeur('@Code', $codeOpe);
			$modele -> setValeur('@Titre', 'Gérer les \'' . $objet . '\'');
			$modele -> setValeur('@Position', 20);
			$phrase = '@Chercher("' . $objet . '").@Trier(.' . $titre . ').@Afficher(.' . $code . ',.' . $titre . ')|>.@Afficher|Modifier>.@Modifier';
			$modele -> setValeur('@Phrase', $phrase);

			$iconeOpe = new SG_Icone($nomIcone . '-edit.png');
			if ($iconeOpe -> Existe() -> estVrai() === false) {
				$iconeOpe = new SG_Icone('pencil.png');
			}
			$modele -> setValeur('@IconeOperation', $iconeOpe -> code);
			$modele -> setValeur('@Theme', $idtheme);
			$retour = $modele -> Enregistrer();
			if (is_object($retour) and !$retour -> estErreur()) {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Gerer\' créé;</li>';
			} else {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Gerer\' NON créé !! base : '.$modele -> doc -> codeBase . '</li>';
			}
			// créer modèle d'opération "Lister"
			$codeOpe = $objet . 'Lister';
			$modele = new SG_ModeleOperation($base . '/' . $codeOpe);
			$modele -> setValeur('@Code', $codeOpe);
			$modele -> setValeur('@Titre', 'Lister les \'' . $objet . '\'');
			$modele -> setValeur('@Position', 30);
			$phrase = '@Chercher("' . $objet . '").@Trier(.' . $titre . ').@Afficher(.' . $code . ',.' . $titre . ')|>.@Afficher';
			$modele -> setValeur('@Phrase', $phrase);

			$iconeOpe = new SG_Icone($nomIcone . '-cascade.png');
			if ($iconeOpe -> Existe() -> estVrai() === false) {
				$iconeOpe = $icone;
			}
			$modele -> setValeur('@IconeOperation', $iconeOpe -> code);
			$modele -> setValeur('@Theme', $idtheme);
			$retour = $modele -> Enregistrer();
			if (is_object($retour) and !$retour -> estErreur()) {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Lister\' créé;</li>';
			} else {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Lister\' NON créé !! base : '.$modele -> doc -> codeBase . '</li>';
			}
			
			// créer modèle d'opération "Supprimer"
			$codeOpe = $objet . 'Supprimer';
			$modele = new SG_ModeleOperation($base . '/' . $codeOpe);
			$modele -> setValeur('@Code', $codeOpe);
			$modele -> setValeur('@Titre', 'Supprimer des \'' . $objet . '\'');
			$modele -> setValeur('@Position', 40);
			$phrase = '@Chercher("' . $objet . '").@Trier(.' . $titre . ').@Choisir(.' . $code . ',.' . $titre . ')|Valider>';
			$phrase .= 'collec=@Principal;collec.@PourChaque(.' . $titre . ');"Voulez-vous supprimer ces ".@Concatener(collec.@Compter," \'' . $objet . '\' ?")';
			$phrase .= '|Supprimer>collec.@PourChaque(.@Supprimer);"Fait !"';
			$modele -> setValeur('@Phrase', $phrase);
			$iconeOpe = new SG_Icone($nomIcone . '-delete');
			if ($iconeOpe -> Existe() -> estVrai() === false) {
				$iconeOpe = new SG_Icone('cancel.png');
			}
			$modele -> setValeur('@IconeOperation', $iconeOpe -> code);
			$modele -> setValeur('@Theme', $idtheme);
			$retour = $modele -> Enregistrer();
			if (is_object($retour) and !$retour -> estErreur()) {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Supprimer\' créé;</li>';
			} else {
				$ret .= '<li>Modèle d\'opération \'' . $objet . 'Supprimer\' NON créé !! base : '.$modele -> doc -> codeBase . '</li>';
			}
			
			// mise à jour du profil administrateur
			$profil = $_SESSION['@SynerGaia'] -> sgbd -> getCollectionObjetsParCode('@Profil','ProfilAdministrateur') -> Premier();
			$op = $profil -> getValeur('@ModelesOperations');
			$prefixe = self::CODEBASE . '/' . $objet;
			$op[] = $prefixe . 'Nouveau';
			$op[] = $prefixe . 'Gerer';
			$op[] = $prefixe . 'Lister';
			$op[] = $prefixe . 'Supprimer';
			$profil -> setValeur('@ModelesOperations', $op);
			$retour = $profil -> Enregistrer();
			if (is_object($retour) and !$retour -> estErreur()) {
				$ret .= '<li>Profil administrateur mis à jour.</li>';
			} else {
				$ret .= '<li>Profil administrateur NON mis à jour !!</li>';
			}
			// vide le cache
			$ret .= '<li> Vider le cache ' . $_SESSION['@SynerGaia'] -> ViderCache() -> toString() . '</li>';
		}
		$ret .='</ul>';
		return $ret;
	}

	/**
	 * présente le json des mots du dictionnaire dépendants d'un mot passé en paramètres
	 * @since 1.2 ajout
	 * @param string $pReference
	 * @return string JSON du dictionnaire
	 */
	static function ajaxMots($pReference = '') {
		$s = $_SESSION['@SynerGaia'];
		$mots = array();
		if($pReference === '') {
			$motsplus = self::ObjetsDocument(true) -> elements;
			foreach ($motsplus as $key => $mot) {
				$mots[] = $key;
			}
			$motsplus = self::getProprietesObjet('@Rien');
			foreach ($motsplus as $key => $mot) {
				$mots[] = $key;
			}
			$motsplus = self::getMethodesObjet('@Rien','',true);
			foreach ($motsplus as $key => $mot) {
				$mots[] = $key;
			}
		} else {
			$modeles = $s -> sgbd -> getModeleDesMots($pReference);
			foreach ($modeles as $idmodele => $vide) {
				$modele = $s -> getObjet($idmodele);
				$modele = $modele -> code;
				$motsplus = self::getProprietesObjet($modele);
				foreach ($motsplus as $key => $mot) {
					$mots[] = $key;
				}
				$motsplus = self::getMethodesObjet($modele);
				foreach ($motsplus as $key => $mot) {
					$mots[] = $key;
				}
			}
		}
		sort($mots);// natcasesort crée un objet au lieu d'un tableau...
		return json_encode($mots);
	}

	/**
	 * retourne le tableau des propriétés d'un objet sous la forme méthode => modèle
	 * 
	 * @since 1.2
	 * @param string $pCodeObjet code de l'objet à analyser
	 * @param string $pModele est un filtre supplémentaire éventuel
	 * @param boolean $pRefresh permet de forcer le rafraichissement du cache
	 * @return SG_Collection dont le tableau est composé d'array ('nom' : propriété, 'idmodele' : modele de la propriété)
	 */
	static function getMethodesObjet ($pCodeObjet = '', $pModele = '', $pRefresh = false) {
		$valeurs = array();
		$codeCache = 'DMO(' . $pCodeObjet . ',' . $pModele . ')';
		if (SG_Cache::estEnCache($codeCache, false) === false or $pRefresh) {
			$collec = $_SESSION['@SynerGaia'] -> sgbd -> getMethodesObjet($pCodeObjet, $pModele);
			foreach($collec -> elements as $element) {
				$valeurs[$element['nom']] = $element['idmodele'];
			}
			SG_Cache::mettreEnCache($codeCache, json_encode($valeurs, true), false);
		} else {
			$valeurs = json_decode(SG_Cache::valeurEnCache($codeCache, false), true);
		}
		return $valeurs;
	}
	/** 1.2 ajout
	* Liste des codes de propriétés de type lien pour un modèle d'objet donné
	* @param (string) $pModele  : modele de l'objet objet
	* @param (boolean) $pRefresh : rafraichir le cache
	* @return (array) liste des champs trouvés
	*/
	static function getLiens($pModele = '', $pRefresh = false) {		
		$valeurs = array();
		$codeCache = '@Dictionnaire.getLiens(' . $pModele . ')';
		if (SG_Cache::estEnCache($codeCache, false) === false or $pRefresh) {
			$champs = self::ChampsDocument($pModele);
			foreach($champs -> elements as $champ) {
				$parties = explode('|', $champ);
				if (self::isLien($pModele . '.' . $parties[0])) {
					$valeurs[] = $parties[0];
				}
			}
			SG_Cache::mettreEnCache($codeCache, json_encode($valeurs, true), false);
		} else {
			$valeurs = json_decode(SG_Cache::valeurEnCache($codeCache, false), true);
		}
		return $valeurs;
	}
	/**
	* crée le tableau des liens entrant vers un modele de document
	* 
	* @since 1.2
	* @version 2.7 refresh false ; code = DLE
	* @param string $pModele  modele de l'objet
	* @param boolean $pRefresh : rafraichir le cache (défaut false)
	* @return array liste des modeles trouvés (cité une seule fois)
	*/
	static function getLiensEntrants($pModele = '', $pRefresh = false) {		
		$valeurs = array();
		$codeCache = 'DLE(' . $pModele . ')';
		if (SG_Cache::estEnCache($codeCache, false) === false or $pRefresh) {
			$objets = self::ObjetsDocument();
			$objetsok = array();
			foreach($objets -> elements as $key => $objet) {
				$liens = self::getLiens($key, true);
				foreach($liens as $lien) {
					if ($lien === $pModele) {
						$objetsok[$key] = '';
					}
				}
			}
			$valeurs = array_keys($objetsok);
			SG_Cache::mettreEnCache($codeCache, json_encode($valeurs, true), false);
		} else {
			$valeurs = json_decode(SG_Cache::valeurEnCache($codeCache, false), true);
		}
		return $valeurs;
	}

	/**
	* Liste des codes de propriétés de type TexteRiche pour un modèle d'objet donné
	* 
	* @since 1.2
	* @version 2.7 code DTR
	* @param string $pModele  : modele de l'objet 
	* @param boolean $pRefresh : rafraichir le cache (défaut false)
	* @return array liste des champs trouvés
	*/
	static function getTextesRiches($pModele = '', $pRefresh = false) {		
		$valeurs = array();
		$codeCache = 'DTR(' . $pModele . ')';
		if (SG_Cache::estEnCache($codeCache, false) === false or $pRefresh) {
			$champs = self::ChampsDocument($pModele);
			foreach($champs -> elements as $champ) {
				$parties = explode('|', $champ);
				$docobjet = new SG_DictionnaireObjet($parties[1]);
				if($docobjet -> deriveDe('@TexteRiche')) {
					$valeurs[] = $parties[0];
				}
			}
			SG_Cache::mettreEnCache($codeCache, json_encode($valeurs, true), false);
		} else {
			$valeurs = json_decode(SG_Cache::valeurEnCache($codeCache, false), true);
		}
		return $valeurs;
	}

	/**
	 * Liste le vocabulaire du dictionnaire (objets, propriétés, méthodes, modèles d'opération)
	 * @since 1.3 ajout
	 * @param strnig|SG_Texte|SG_Formule $pPrefixe préfixe de filtre du vocabulaire (sensible à la casse)
	 * @return SG_Collection de mots
	 * @formula @Chercher("@DictionnaireObjet").@Concatener(@Chercher("DictionnairePropriete")).@Concatener(@Chercher("DictionnaireMethode")).@Lister(.@Code)
	 */
	static function Vocabulaire($pPrefixe = '') {
		$prefixe = SG_Texte::getTexte($pPrefixe);
		$len = strlen($prefixe);
		$ret = new SG_Collection();
		$js = "function(doc) { if (doc['@Type']==='@DictionnaireObjet' || doc['@Type']==='@DictionnairePropriete'";
		$js.= " || doc['@Type']==='@DictionnaireMethode')";
		$js.= "{ var code='';if (doc['Code'] != null) { code = doc['Code'];} else if (doc['@Code'] != null) {code = doc['@Code']; }; emit(code,doc['_id'])} }";
		$vue = new SG_Vue(SG_Dictionnaire::CODEBASE . '/vue_vocabulaire', self::CODEBASE, $js, true);
		if ($vue -> creerVue() === true) {
			$result = $vue -> vue -> contenuBrut();
			foreach($result as $r) {
				$mot = $r['key'];
				$needle = strpos($mot, '.');
				if($needle !== false) {
					$mot = substr($mot, $needle + 1);
				}
				if($len === 0) {
					$ret -> elements[$mot] = $mot;
				} elseif (strtolower(substr($mot,0,$len)) === $prefixe) {
					$ret -> elements[$mot] = $mot;
				}
			}
		}
		sort($ret -> elements);
		return $ret;
	}
	/** 
	 * Retourne le tableau des propriétés d'un objet sous la forme nom propriété => modèle
	 * 
	 * @since 1.0.5 dans SG_SynerGaia ;
	 * @version 1.3.1 déplace de SG_SynerGaia et changement de code cache  ; erreur si nom inconnu
	 * @version 2.6 code err 0293 ; @Dictionnaire.getProprietesObjet => DPO
	 * @version 2.7 @Texte si ['idmodele'] inexistant
	 * @param string $pCodeObjet code de l'objet à analyser
	 * @param string $pModele est un filtre supplémentaire éventuel
	 * @param boolean $pRefresh permet de forcer le rafraichissement du cache
	 * @return @Collection dont le tableau est composé d'array ('nom' : propriété, 'idmodele' : modele de la propriété)
	 */
	static function getProprietesObjet ($pCodeObjet = '', $pModele = '', $pRefresh = false) {
		$valeurs = array();
		$codeCache = 'DPO(' . $pCodeObjet . ',' . $pModele . ')';
		if ($pRefresh === true or SG_Cache::estEnCache($codeCache, false) === false) {
			$collec = $_SESSION['@SynerGaia'] -> sgbd -> getProprietesObjet($pCodeObjet, $pModele, $pRefresh);
			foreach($collec -> elements as $element) {
				if (isset($element['nom'])) {
					if (isset($element['idmodele'])) {
						$valeurs[$element['nom']] = $element['idmodele'];
					} else {
						$valeurs[$element['nom']] = SG_Texte::TYPESG;
					}
				} else {
					// élément sans code dans le dictionnaire
					$valeurs[''] = new SG_Erreur('0293');
				}
			}
			SG_Cache::mettreEnCache($codeCache, json_encode($valeurs, true), false);
		} else {
			$valeurs = json_decode(SG_Cache::valeurEnCache($codeCache, false), true);
		}
		return $valeurs;
	}

	/**
	 * Retourne l'objet, le code et le type propriété ou méthode associé à objet.fonction
	 * Pour chaque niveau hiérarchique, on cherche dans l'ordre : 
	 * 	une action, une méthode, une propriété, 
	 * 	puis on remonte d'un étage dans la hiérarchie des objets jusqu'à @Rien
	 * Si on a rien trouvé pour une fonction applicative (sans @), on recommence avec @fonction
	 * @since 1.3.1 ajout
	 * @version 2.0 parm1 peut être objet.fonction
	 * @param string $pTypeObjet
	 * @param string $pFonction
	 * @param boolean $pRefresh
	 * @return array objet, type('action','methode','champ','erreur'), fonction (@fonction ou fonction)
	 */
	static function getObjetFonction($pTypeObjet = '', $pFonction = '', $pRefresh = false) {
		$typeObjet = $pTypeObjet;
		$fonction = $pFonction;
		if ($pFonction === '') {
			$ipos = strpos($pTypeObjet, '.');
			if ($ipos !== FALSE) {
				$typeObjet = substr($pTypeObjet, 0, $ipos);
				$fonction = substr($pTypeObjet, $ipos + 1);
			}
		}
		$typeObjetInitial = $typeObjet;
		$modele = $typeObjet . '.' . $fonction;
		$code = '';
		$type = 'erreur'; // si pas trouvé
		$sortir = '';
		$numBoucle = 0; // sert à détecter une éventuelle boucle dans le dictionnaire
		$codeCache = 'ObjetFonction';
		if (SG_Cache::estEnCache($codeCache, false) and $pRefresh === false) {
			$cache = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			$cache = array();
		}
		if (array_key_exists($modele, $cache)) {
			$ret = $cache[$modele];
		} else {
			// pas encore en cache : on calcul et on renvoie
			while ($sortir === '') {
				while ($sortir === '') {
					$methodeExiste = SG_Dictionnaire::isMethodeExiste($typeObjet, $fonction);
					if ($methodeExiste === true) {
						$action = SG_Dictionnaire::getActionMethode($typeObjet, $fonction);
						if ($action !== '') {// C'est une action
							$type = 'action';
							$code = $action;
						} elseif (substr($fonction, 0, 1) === '@') {
							// C'est une méthode système
							$codeFonctionExecutable = str_replace('@','',$fonction);
							$type = 'methode';
							$code = $codeFonctionExecutable;
						}
						$sortir = 'ok';
					} else {
						// La méthode demandée n'existe pas	: Cherche si la propriété existe
						$proprieteExiste = SG_Dictionnaire::isProprieteExiste($typeObjet, $fonction);
						if ($proprieteExiste === true) {
							$type = 'champ';
							$code = '';
							$sortir = 'ok';
						} else {
							// La propriété demandée n'existe pas
							// Cherche dans le modèle parent si la méthode ou la propriété existe
							$typeObjet = SG_Dictionnaire::getCodeModele($typeObjet);
						}
					}
					$numBoucle++;
					if ($numBoucle >= 20) {
						$sortir = 'erreur';
					} elseif ($typeObjet === '') {
						$sortir = '?';
					}
				}
				// si nécesaire, on re-essaie avec les fonctions système (@)
				if ($numBoucle >= 20 or $sortir === 'ok') {
					// fin
				} elseif ($sortir === '?' and substr($fonction, 0, 1) !== '@') {
					$fonction = '@' . $fonction;
					$typeObjet = $typeObjetInitial;
					$sortir = '';
				} else {
					// cas tordu
				}
			}
			$ret = array('type' => $type, 'code' => $code, 'objet' => $typeObjet, 'fonction' => $fonction);
			if ($sortir === 'ok') { // on ne garde pas si erreur => temps de traitement plus long mais plus facile à rafraichir
				$cache[$modele] = $ret;
				SG_Cache::mettreEnCache($codeCache, $cache, false);
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* Chercher si parm1 est ou dérive de parm2
	* @param (any) $pModele1 : le modèle dont on cherche le modele parent (soit un string soit un objet)
	* @param (any) $pModele2 : le modèle dont on cherche le modele parent (soit un string soit un objet)
	* @return (boolean) : le résultat
	**/
	static function deriveDe ($pModele1 = '', $pModele2 = '') {
		$ret = false;
		$m1 = $pModele1;
		if (is_object($pModele1)) {
			$m1 = getTypeSG($pModele1);
		}
		$m2 = $pModele2;
		if (is_object($pModele2)) {
			$m2 = getTypeSG($pModele2);
		}
		if ($m1 === $m2) {
			$ret = true;
		} else {
			$c1 = self::getClasseObjet($m1);
			$c2 = self::getClasseObjet($m2);
			$ret = is_subclass_of($c1, $c2);
		}			
		return $ret;
	}
	/** 2.4 ajout
	* retourne le code base complet d'un objet ou @erreur
	* @param string : code objet
	* @return string : code base complet
	**/
	static function getCodeBaseComplet($pObjet = '') {
		$base = SG_Dictionnaire::getCodeBase($pObjet);
		$ret = SG_Config::getCodeBaseComplet($base);
		return $ret;
	}

	/**
	* retourne le modèle d'une propriété d'objet au dictionnaire (pour le moemnt il n'y a pas de cache utilisé)
	* @since 2.4 ajout
	* @param string $pObjet : code de l'objet dont on parle
	* @param string $pPropriete : code de la propruété que l'on cherche
	* @param boolean $pForce : forcer le recalcul des valeurs en cache
	* @return string code du modèle
	**/
	static function getModelePropriete($pObjet, $pPropriete, $pForce = false) {
		$proprietes = $_SESSION['@SynerGaia'] -> sgbd -> getProprietesObjet($pObjet, '', $pForce);
		$ret = '';
		foreach ($proprietes -> elements as $p) {
			if ($p['nom'] === $pPropriete) {
				$ret = $p['idmodele'];
				break;
			}
		}
		return $ret;
	}

	/**
	* Recherche une méthode
	* 
	* @since 2.4 ajout
	* @version 2.6 supp param 3 valeur par défaut inutile
	* @param string $pTypeObjet objet sur lequel la méthode est demandée
	* @param string $pMethode code de la méthode demandée
	* @formule @Chercher("@DictionnaireMethode").@Premier	*
	* @return document @DictionnaireMethode
	*/
	static function getMethode($pTypeObjet = '', $pMethode = '') {
		$code = $pTypeObjet . '.' . $pMethode;
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, '@DictionnaireMethode', $code);
		return $ret;
	}

	/**
	 * Exporte des éléments de l'application pour en faire un paquet.
	 * Les éléments sont stockés dans un fichier séparé sur le serveur dans le répertoire /tmp de l'application
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pNom nom du paquet (et nom du fichier .sgp)
	 * @param string|SG_Texte|SG_Formule $pTitre titre du paquet (par défaut = nom)
	 * @param SG_Collection|SG_Formule $pElements collection des éléments à exporter. Cela peut être des objets, des thèmes, des modèles d'opération.
	 * @return SG_Texte|SG_Erreur le nom du fichier résultant ou une erreur
	 **/
	static function CreerPaquet ($pNom = '', $pTitre = '', $pElements = '') {
		$ret = false;
		$nom = SG_Texte::getTexte($pNom);
		if ($nom === '') {
			$nom = 'Paquet_' . now;
		}
		$titre = SG_Texte::getTexte($pTitre);
		if ($titre === '') {
			$titre = $nom;
		}
		if ($pElements instanceof SG_Formule) {
			$collec = $pElements -> calculer();
		} else {
			$collec = $pElements;
		}
		if (!$collec instanceof SG_Collection) {
			$ret = new SG_Erreur('pas collec');
		} else {
			$texte = '{
				"_id": "' . $nom . '",
				"@Type": "@Paquet",
				"@Code": "' . $nom . '",
				"@Titre": "' . $titre . '",
				"@Version": "' . SG_SynerGaia::VERSION . '",
				"@Description": "<h1></h1>",
				"@Formule": "';
			foreach ($collec -> elements as $elt) {
				// o=@DictionnaireObjet.\n\t@setUID(\"Document\").\n\t@Code(\"Document\").\n\t@Modele(@DictionnaireObjet(\"@Document\")).\n\t@Titre(\"Document\");
				// o.@Enregistrer;
				// \no.@AjouterPropriete(\"Contenu\",\"Contenu\",\"@TexteRiche\");
				// \no.@AjouterMethode(\"Date\",\"Date\",\"@Date\");
				// \nt=@Theme.\n\t@setUID(\"documents_theme\").\n\t@Code(\"Documents\").\n\t@Titre(\"Documents\").\n\t@IconeTheme(\"group.png\").\n\t@Position(400);
				// \nt.@Enregistrer;
				// \n@ModeleOperation.@setUID(\"DocumentGerer\").\n\t@Code(\"DocumentGerer\").\n\t@Titre(\"Gérer les documents\").\n\t@Position(30)
				// \n\t.@Phrase(\"@Chercher(\\\"Document\\\")...\")
				// \n\t.@IconeOperation(\"pencil.png\").\n\t@Theme(t).\n\t@Enregistrer;
				if ($elt instanceof SG_DictionnaireObjet) {
					// @DictionnaireObjet
					$code = $elt -> getValeur('@Code');
					$texte.= 'o=@DictionnaireObjet.\n\t@setUID(\"' . $code . '\")\n\t.@Code(\"' . $code . '\")\n\t.@Modele(@DictionnaireObjet(\"@Document\"))';
					$titre = $elt -> getValeur('@Titre',$code);
					$texte.= '\n\t.@Titre(\"' . $titre . '\");\no.@Enregistrer;';
					// @DictionnairePropriete
					$proprietes = $elt -> Proprietes() -> elements;
					foreach ($proprietes as $obj) {
						$codepr = $obj -> getValeur('@Code');
						$titrepr = $obj -> getValeur('@Titre', $code);
						$modele = $obj -> getValeurPropriete('@Modele') -> getValeur('@Code','');
						$texte.= '\no.@AjouterPropriete(\"' . $codepr . '\",\"' . $titrepr . '\",\"' . $modele . '\");';
					}
					// @DictionnaireMethode
					$methodes = $elt -> Methodes() -> elements;
					foreach ($methodes as $obj) {
						$codepr = $obj -> getValeur('@Code');
						$titrepr = $obj -> getValeur('@Titre', $code);
						$modele = $obj -> getValeurPropriete('@Modele') -> getValeur('@Code','');
						$formule = $obj -> getValeur('@Action');
						$formule = str_replace('\\','\\\\', $formule);
						$texte.= '\no.@AjouterMethode(\"' . $codepr . '\",\"' . $titrepr . '\",\"' . $modele . '\",\"' . $formule .'\");';
					}
					$texte.= '';
				}
				$texte.= '"
				}';
			}
		}
		$ret = $texte;
		return $ret;
	}

	/**
	 * Retourne la liste des code des modèles de type Document
	 * Elle est en cache sauf si $pRefresh est à true
	 * 
	 * @param boolean $pRefresh rafraichir le cache ? (défaut false)
	 * @return array tableau des codes (sous la forme '@Texte' etc)
	 */
	static function getObjetsDocument($pRefresh = false) {
		$codeCache = 'gOD';
		if ($pRefresh === false and SG_Cache::estEnCache($codeCache, false)) {
			$cache = SG_Cache::valeurEnCache($codeCache, false);
			$ret = explode(',', $cache);
		} else {
			$ret = new SG_Collection();
			$documents = array();
			$objets = self::Objets();
			foreach($objets -> elements as $objet) {
				$classe = self::getClasseObjet($objet -> code);
				if (class_exists($classe)) {
					try {
						$doc = new $classe();
					} catch (Exception $e) {
						$doc = new SG_Erreur('');
					}
					if ($doc instanceof SG_Document) {
						$documents[] = $objet -> code;
					}
				}
			}
			$ret = $documents;
			SG_Cache::mettreEnCache($codeCache, implode(',', $documents), false);
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Dictionnaire_trait;
}
?>
