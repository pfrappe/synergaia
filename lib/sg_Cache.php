<?php
/** SYNERGAIA fichier pour le taitement de l'objet @Cache */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * Classe SynerGaia de gestion des caches
 * Utilise apc si disponible, (2.1) puis memcached si disponible, puis memcache si disponible, sinon utilise le cache php (via variable globale)
 * Cette classe est statique
 * @since 1.0.6
 * @version 2.1 traitement de memcached
 * @version 2.7 added error tests and ViderCache('?')
 * @todo Terminer la gestion des objets en cache à partir de json et utiliser serialize et unserialisze
 */
class SG_Cache {
	/** string Type SynerGaia '@Cache' */
	const TYPESG = '@Cache';

	/** integer Type de cache  "PHP" */
	const TYPE_PHP = 1;

	/** integer Type de cache  "MEMCACHE" */
	const TYPE_MEMCACHE = 2;

	/** integer Type de cache  "APC" */
	const TYPE_APC = 3;

	/** @var integer Type de cache  "MEMCACHED"
	 * @since 2.1 memcached
	 */
	const TYPE_MEMCACHED = 4;

	/** sting Suffixe du type de donnée */
	const SUFFIXE_TYPE_DONNEE = '_type';
	/** string Prefixe général des clés du cache */
	const PREFIXE_CODE_CACHE = 'SG#';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	/** integer Type de cache utilisé */
	static $typeCache = 0;

	/** string Prefixe aux clés du cache */
	static $prefixeCache = '';

	/** string Prefixe aux clés du cache de l'utilisateur
	 * @since 1.0.6
	 */
	static $prefixeCacheUser = '';

	/** handle Objet de connexion à Memcache */
	static $memcache_obj;
	
	/** string identifiat pour memcached */
	static $persistent_id;

	/**
	 * Initialise le cache
	 * @since 1.0.6
	 * @version 2.1 Memcached
	 * @param integer type de cache à forcer
	 */
	static public function initialiser($pTypeCache = 0) {
// tracer() ne marche pas ici. Utiliser error_log()
		SG_Cache::initPrefixeCacheAppli();
		SG_Cache::$typeCache = 0;
		$ok = false;
		// Cherche le cache APC //
		if ((($pTypeCache === 0) or ($pTypeCache == SG_Cache::TYPE_APC)) and (extension_loaded('apc') and ini_get('apc.enabled'))) {
			if (@apc_cache_info() !== false) {
				SG_Cache::$typeCache = SG_Cache::TYPE_APC;
				$ok = true;
			}
		}
		// Si cache APC non disponible : essayer Memcached
		if (! $ok and SG_Cache::$typeCache !== SG_Cache::TYPE_APC) {
			// Cherche Memcached
			if ($pTypeCache === 0 and class_exists('Memcached')) {
				$memcache_host = '127.0.0.1';
				$memcache_port = 11211;
				SG_Cache::$persistent_id = SG_SynerGaia::idRandom();
				SG_Cache::$memcache_obj = new Memcached(SG_Cache::$persistent_id);
				SG_Cache::$memcache_obj -> setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
				SG_Cache::$memcache_obj -> addServer($memcache_host, $memcache_port);
				$stats = @SG_Cache::$memcache_obj -> getStats();
				$memcache_ok = (bool)$stats["$memcache_host:$memcache_port"];
				if ($memcache_ok) {
					SG_Cache::$typeCache = SG_Cache::TYPE_MEMCACHED;
					$ok = true;
				}
			}
		}
		// Si cache Memcached non disponible
		if (!$ok and SG_Cache::$typeCache !== SG_Cache::TYPE_MEMCACHED) {
			// Cherche Memcache
			if ($pTypeCache === 0 and class_exists('Memcache')) {
				$memcache_host = '127.0.0.1';
				$memcache_port = 11211;
				SG_Cache::$memcache_obj = new Memcache();
				SG_Cache::$memcache_obj -> addServer($memcache_host, $memcache_port);
				$stats = @SG_Cache::$memcache_obj -> getExtendedStats();
				$memcache_ok = (bool)$stats["$memcache_host:$memcache_port"];
				if ($memcache_ok && @SG_Cache::$memcache_obj -> connect($memcache_host, $memcache_port)) {
					SG_Cache::$typeCache = SG_Cache::TYPE_MEMCACHE;
					$ok = true;
				}
			}
		}
		// Si aucun autre cache n'est disponible
		if (!$ok and SG_Cache::$typeCache !== SG_Cache::TYPE_MEMCACHE) {
			// Pas de cache spécifique : utilise le cache PHP
			SG_Cache::$typeCache = SG_Cache::TYPE_PHP;
			$_SESSION['cache'] = array();
			$ok = true;
		}
	}

	/**
	 * Initialise le préfixe du cache pour l'application
	 * 
	 * @since 1.0.7
	 */
	static function initPrefixeCacheAppli() {
		SG_Cache::$prefixeCache = SG_Cache::PREFIXE_CODE_CACHE . SG_Config::getCodeAppli() . '#';
		SG_Cache::initPrefixeCacheUser();
	}

	/**
	 * Initialise le préfixe du cache pour l'utilisateur en cours
	 * 
	 * @since 1.0.7
	 */
	static function initPrefixeCacheUser() {
		SG_Cache::$prefixeCacheUser = SG_Cache::$prefixeCache . SG_SynerGaia::IdentifiantConnexion() . '#';
	}

	/**
	 * Détermine la clé d'accès au cache suivant le code
	 * 
	 * @since 1.1 correction erreur
	 * @param string $pCode code de la variable
	 * @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	 * @return string clé d'accès au cache
	 */
	static public function getKey($pCode = '', $pUser = true) {
		if ($pUser === true) {
			$cle = SG_Cache::$prefixeCacheUser . $pCode;
		} else {
			$cle = SG_Cache::$prefixeCache . $pCode;
		}
		if (strlen($cle) > 64) {
			$cle = md5($cle);
		}
		return $cle;
	}

	/**
	 * Détermine le type de cache pour information
	 *
	 * @return string type de cache
	 */
	static public function getTypeCache() {
		$ret = 'aucun';
		switch (SG_Cache::$typeCache) {
			case SG_Cache::TYPE_APC :
				$ret = 'apc';
				break;
			case SG_Cache::TYPE_MEMCACHED :
				$ret = 'memcached';
				break;
			case SG_Cache::TYPE_MEMCACHE :
				$ret = 'memcache';
				break;
			case SG_Cache::TYPE_PHP :
				$ret = 'php';
				break;
		}
		return $ret;
	}

	/**
	 * Détermine si la variable est en cache
	 * 
	 * @since 1.0.6
	 * @param string $pCode code de la variable
	 * @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	 * @return boolean est ou non en cache
	 */
	static public function estEnCache($pCode = '', $pUser = true) {
		$ret = false;
		$code = SG_Cache::getKey($pCode, $pUser);
		switch (SG_Cache::$typeCache) {
			case SG_Cache::TYPE_APC :
				$ret = apc_exists($code . SG_Cache::SUFFIXE_TYPE_DONNEE);
				break;
			case SG_Cache::TYPE_MEMCACHED :
				$ret = (SG_Cache::$memcache_obj -> get($code) !== false);
				break;
			case SG_Cache::TYPE_MEMCACHE :
				$ret = (SG_Cache::$memcache_obj -> get($code . SG_Cache::SUFFIXE_TYPE_DONNEE) !== false);
				break;
			case SG_Cache::TYPE_PHP :
				if (array_key_exists('cache', $_SESSION)) {
					$ret = array_key_exists($code, $_SESSION['cache']);
				}
				break;
		}
		return $ret;
	}

	/**
	 * Extrait la valeur d'une variable du cache
	 * 
	 * @since 1.0.6
	 * @param string $pCode code de la variable
	 * @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	 * @return indéfini valeur en cache
	 */
	static public function valeurEnCache($pCode = '', $pUser = true) {
		$code = SG_Cache::getKey($pCode, $pUser);
		switch (SG_Cache::$typeCache) {
			case SG_Cache::TYPE_APC :
				$ret_type = apc_fetch($code . SG_Cache::SUFFIXE_TYPE_DONNEE);
				if ($ret_type !== false) {
					$ret = apc_fetch($code);
					switch ($ret_type) {
						case 'string' :
						case 'array' :
							// La valeur récupérée est déjà avec le bon type
							break;
						case 'boolean' :
							$ret = (bool)$ret;
							break;
						case 'integer' :
							$ret = (int)$ret;
							break;
						case 'double' :
						case 'float' :
							$ret = (double)$ret;
							break;
					}
					return $ret;
				}
				break;
			case SG_Cache::TYPE_MEMCACHED :
				$ret = SG_Cache::$memcache_obj -> get($code);
				return $ret;
				break;
			case SG_Cache::TYPE_MEMCACHE :
				$ret_type = SG_Cache::$memcache_obj -> get($code . SG_Cache::SUFFIXE_TYPE_DONNEE);
				if ($ret_type !== false) {
					$ret = SG_Cache::$memcache_obj -> get($code);
					switch ($ret_type) {
						case 'string' :
						case 'array' :
							// La valeur récupérée est déjà avec le bon type
							break;
						case 'boolean' :
							$ret = (bool)$ret;
							break;
						case 'integer' :
							$ret = (int)$ret;
							break;
						case 'double' :
						case 'float' :
							$ret = (double)$ret;
							break;
					}
					return $ret;
				}
				break;
			case SG_Cache::TYPE_PHP :
				if (array_key_exists($code, $_SESSION['cache'])) {
					return $_SESSION['cache'][$code];
				}
				break;
		}
		return null;
	}

	/**
	 * Définit la valeur d'une variable du cache
	 * @since 1.0.6
	 * @version 2.1 gère directement les objets
	 * @param string $pCode code de la variable
	 * @param indéfini $pValeur valeur de la variable
	 * @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	 */
	static public function mettreEnCache($pCode = '', $pValeur, $pUser = true) {
		$ret = false;
		$code = SG_Cache::getKey($pCode, $pUser);
		switch (SG_Cache::$typeCache) {
			case SG_Cache::TYPE_APC :
				$ret = apc_store($code, $pValeur) && apc_store($code . SG_Cache::SUFFIXE_TYPE_DONNEE, gettype($pValeur));
				break;
			case SG_Cache::TYPE_MEMCACHED :
				$ret = SG_Cache::$memcache_obj -> set($code, $pValeur);//2.1 and SG_Cache::$memcache_obj -> set($code . SG_Cache::SUFFIXE_TYPE_DONNEE, gettype($pValeur));
				break;
			case SG_Cache::TYPE_MEMCACHE :
				$ret = SG_Cache::$memcache_obj -> set($code, $pValeur) && SG_Cache::$memcache_obj -> set($code . SG_Cache::SUFFIXE_TYPE_DONNEE, gettype($pValeur));
				break;
			case SG_Cache::TYPE_PHP :
				$_SESSION['cache'][$code] = $pValeur;
				$ret = true;
				break;
		}
		return $ret;
	}

	/**
	 * Supprime la valeur d'une variable du cache
	 * 
	 * @since 1.0.6
	 * @param string $pCode code de la variable
	 * @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	 * @return boolean ok ou non
	 */
	static public function effacerEnCache($pCode = '', $pUser = true) {
		$ret = false;
		$code = SG_Cache::getKey($pCode, $pUser);
		switch (SG_Cache::$typeCache) {
			case SG_Cache::TYPE_APC :
				$ret = apc_delete($code) && apc_delete($code . SG_Cache::SUFFIXE_TYPE_DONNEE);
				break;
			case SG_Cache::TYPE_MEMCACHED :
				$ret = SG_Cache::$memcache_obj -> delete($code) and SG_Cache::$memcache_obj -> delete($code . SG_Cache::SUFFIXE_TYPE_DONNEE);
				break;
			case SG_Cache::TYPE_MEMCACHE :
				$ret = SG_Cache::$memcache_obj -> delete($code) and SG_Cache::$memcache_obj -> delete($code . SG_Cache::SUFFIXE_TYPE_DONNEE);
				break;
			case SG_Cache::TYPE_PHP :
				unset($_SESSION['cache'][$code]);
				$ret = true;
				break;
		}
		return $ret;
	}

	/**
	 * Vide le cache
	 * @since 1.3.0
	 * @version 1.3.1 : calculs, navigation, $pType
	 * @version 2.0 purge
	 * @version 2.1 '?' liste des clés
	 * @param string|SG_Texte $pType : type(s) de cache à vider
	 * @return boolean ok ou non
	 */
	static public function viderCache($pType = '') {
		$type = new SG_Texte($pType);
		$type = strtolower($type -> texte);
		$ret = true;
		if ($type === '' or strpos('u', $type) !== false) {
			switch (SG_Cache::$typeCache) {
				case SG_Cache::TYPE_APC :
					$ret = $ret and apc_clear_cache('user');
					break;
				case SG_Cache::TYPE_MEMCACHED :
					$ret = $ret and SG_Cache::purge();
					break;
				case SG_Cache::TYPE_MEMCACHE :
					$ret = $ret and SG_Cache::purge();
					break;
				case SG_Cache::TYPE_PHP :
					$_SESSION['cache'] = array();
					$ret = $ret and true;
					break;
			}
		}
		if ($type === '' or strpos('f', $type) !== false) {
			self::viderCacheCalculs();
			$ret = $ret and true;
		}
		if ($type === '' or strpos('n', $type) !== false) {
			self::viderCacheNavigation();
			$ret = $ret and true;
		}
		if ($type === '' or strpos('c', $type) !== false) {
			self::viderCacheConfig();
			$ret = $ret and true;
		}
		if ($type === '' or strpos('d', $type) !== false) {
			self::viderCacheDictionnaire();
			self::viderCacheCalculs();
			$ret = $ret and true;
		}
		if ($type === '' or strpos('s', $type) !== false) {
			self::viderCacheSession();
			$ret = $ret and true;
		}
		if ($type === '*') {
			self::purge();
			$ret = $ret and true;
		}
		if ($type === '?') {
			$ret = self::cles();
		}
		return $ret;
	}

	/**
	 * Retourne le code du type de cache utilisé
	 * @since 1.1
	 * @param string $pCode si non vide on récupère le type de cache sinon type cache PHP
	 * @return integer code de cache
	 * @todo cette fonction semble inutilisée : à supprimer ?
	 */
	static function getCodeCache($pCode='') {
		if ($pCode === '') {
			$ret = SG_Cache::TYPE_PHP;
		} else {
			$ret = SG_Cache::typeCache;
		}
		return $ret;
	}

	/**
	 * Vide le cache de la session en cours (variables conservées dans $_SESSION) sauf l'opération en cours
	 * 
	 * @since 1.1 ajout : reporté depuis SG_Connexion
	 * @version 2.6 test $op objet
	 * @version 2.4 raz libellés
	 * @return SG_Nombre place libérée en mémoire en octets
	 */
	static function viderCacheSession() {
		$ret = memory_get_usage();
		if (isset($_SESSION['@SynerGaia']) and sizeof($_SESSION['@SynerGaia'] -> libelles) > 0) {
			$_SESSION['@SynerGaia'] -> libelles = array();
		}
		$op = SG_Pilote::OperationEnCours();
		if (is_object($op)) {
			$op = $op -> reference;
		} else {
			$op = '';
		}
		if (isset($_SESSION['principal']) and is_array($_SESSION['principal'])) {
			foreach($_SESSION['principal'] as $key => $doc) {
				if ($key !== $op) {
					unset($_SESSION['principal'][$key]);
				}
			}
		} else {
			$_SESSION['principal'] = array();
		}
		if (isset($_SESSION['operations']) and is_array($_SESSION['operations'])) {
			foreach($_SESSION['operations'] as $key => $operation) {
				if ($key !== $op) {
					unset($_SESSION['operations'][$key]);
				}
			}
		}
		//unset($_SESSION['page']); 1.3.1 trop dangereux
		unset($_SESSION['bases']);
		unset($_SESSION['benchmark']);
		unset($_SESSION['chrono']);
		unset($_SESSION['cache']);
		unset($_SESSION['debug']);
		unset($_SESSION['formule']);
		unset($_SESSION['panels']);
		unset($_SESSION['page']['mesthemes']);
		unset($_SESSION['page']['themes']);
		unset($_SESSION['parms']);
		unset($_SESSION['users']);
		$codeAppli = SG_Config::getCodeAppli();
		unset($_SESSION[$codeAppli]['BE']); // liste des bases existantes
		unset($_SESSION[$codeAppli]['PO']); // propriétés des objets
		unset($_SESSION[$codeAppli]['gCD']); // getChercherDocument
		$ret = new SG_Nombre($ret - memory_get_usage());
		return $ret;
	}

	/**
	 * Vide le cache lié à la navigation (panneaux et thèmes) en $_SESSION
	 * @since 1.1 ajout
	 * @version 1.3.1 purge tous les thèmes
	 * @version 2.0 correction Id() ; ModelesOperations
	 * @version 2.1 test $utilisateurs @Erreur
	 * @version 2.2 viderCacheBanniere
	 */
	static function viderCacheNavigation() {
		unset($_SESSION['panels']);
		self::viderCacheBanniere();
		// liste des thèmes de l'utilisateur
		unset($_SESSION['page']['mesthemes']);
		unset($_SESSION['page']['themes']);
		$_SESSION['page']['theme'] = '';
		$_SESSION['page']['menu'] = '';
		// menus des thèmes
		$themes = SG_Rien::Chercher("@Theme");
		if (getTypeSG($themes) === '@Collection') {
			foreach($themes -> elements as $theme) {
				if (getTypeSG($theme) === '@Theme') {
					$codeCache = 'MenuTheme(' .$theme -> Id() . ')';
					self::effacerEnCache($codeCache, true);
				}
			}
		}
		$utilisateurs = SG_Rien::Chercher("@Utilisateur");
		if(getTypeSG($utilisateurs) !== '@Erreur') {
			foreach($utilisateurs -> elements as $u) {
				$codeCache = 'MOP(' . $u -> identifiant . ')';
				self::effacerEnCache($codeCache);
			}
		}
	}

	/**
	 * vide le cache des données de la bannière
	 * @since 2.2 ajout
	 */
	static function viderCacheBanniere() {
		$_SESSION['page']['banniere'] = '';
	}

	/**
	 * Vide le cache des données de calculs ('parms' 'formule')
	 * @since 1.1 ajout
	 * @todo ces caches semblent inutilisés : à supprimer ??
	 */
	static function viderCacheCalculs() {
		unset($_SESSION['parms']);
		unset($_SESSION['formule']);
	}

	/**
	 * Vide le cache des données de config ($SG_Config)
	 * 
	 * @since 1.1 ajout
	 */
	static function viderCacheConfig() {
		unset($SG_Config);
	}

	/**
	 * Vide le cache des données du dictionnaire
	 * 
	 * @since 1.2 ajout
	 * @version 1.3.1 getCodeModele, isMultiple sur chaque propriete, getObjetFonction, user=false
	 * @version 2.0 isProprieteExiste
	 * @version 2.6 @Dictionnaire.getMethodesObjet => DMO
	 * @version 2.7 test SG_Erreur et return ; effacer 'DLE', 'DTR', 'gOD'
	 * @todo vider ? $codeCache = 'DMO(' . $pCodeObjet . ',' . $pModele . ')';
	 */
	static function viderCacheDictionnaire() {
		self::effacerEnCache('getObjetFonction',false);
		self::effacerEnCache('DOD', false);
		self::effacerEnCache('gOD', false);
		$collec = SG_Dictionnaire::Objets();
		if ($collec instanceof SG_Erreur) {
			$ret = $collec;
		} else {
			// effacer les caches par modèle de document
			foreach($collec -> elements as $objet) {
				$nom = $objet -> getValeur('@Code');
				self::effacerEnCache('getCodeBase(' . $nom . ')', false);
				self::effacerEnCache('getCodeModele(' . $nom . ')', false);
				self::effacerEnCache('@Dictionnaire.classeObjet(' . $nom . ')', false);
				self::effacerEnCache('@Dictionnaire.getLiens(' . $nom . ')', false);
				self::effacerEnCache('@Dictionnaire.@Champs(' . $nom . ')', false);
				self::effacerEnCache('@Dictionnaire.isLien(' . $nom . ')', false);
				self::effacerEnCache('isObjetSysteme(' . $nom . ')', false);
				self::effacerEnCache('DLE(' . $nom . ')', false);
				self::effacerEnCache('DMO(' . $nom . ')', false);
				self::effacerEnCache('DPO(' . $nom . ',)', false);
				self::effacerEnCache('DTR(' . $nom . ')', false);
				$proprietes = SG_Dictionnaire::getProprietesObjet($nom);
				foreach($proprietes as $propriete => $uid) {
					// effacer les caches par code propriété
					$codeElement = $nom . '.' .$propriete;
					self::effacerEnCache('isMultiple(' . $codeElement . ')', false);
					self::effacerEnCache('getCodeModele(' . $codeElement . ')', false);
					self::effacerEnCache('IPE(' . $codeElement . ')', false);
				}
			}
			$ret = true;
		}
		return $ret;
	}

	/**
	 * pour tous les objets : false sauf SG_Erreur et dérivés
	 * 
	 * @since 1.3.4 ajout
	 * @version 2.0 static
	 */
	static function estErreur() {
		return false;
	}

	/**
	 * Purge complètement le cache
	 * @since 2.0 ajout
	 */
	static function purge() {
		$ret = false;
		switch (self::$typeCache) {
			case SG_Cache::TYPE_MEMCACHED :
				SG_Cache::$memcache_obj -> flush(); // pose tout comme périmé (à la seconde)
				$time = time()+1; //attendre une seconde pour péremption effective
				while(time() < $time) {/*attendre*/}
				$ret = true;
				break;
			case SG_Cache::TYPE_MEMCACHE :
				SG_Cache::$memcache_obj -> flush(); // pose tout comme périmé (à la seconde)
				$time = time()+1; //attendre une seconde pour péremption effective
				while(time() < $time) {/*attendre*/}
				$ret = true;
				break;
			case SG_Cache::TYPE_APC :
				break;
			case SG_Cache::TYPE_PHP :
				$SESSION['cache'] = array();
				break;
		}
		return true;
	}

	/**
	 * Liste des clés du cache utilisé
	 * @since 2.1
	 * @return SG_Collection de SG_Texte
	 */
	static function cles() {
		$ret = array();
		switch (self::$typeCache) {
			case SG_Cache::TYPE_MEMCACHED :
				$ret = SG_Cache::$memcache_obj -> getAllKeys();
				break;
			case SG_Cache::TYPE_MEMCACHE :
				$ret = SG_Cache::$memcache_obj -> getAllKeys();
				break;
			case SG_Cache::TYPE_APC :
				break;
			case SG_Cache::TYPE_PHP :
				break;
		}
		return new SG_Collection($ret);
	}
}
?>
