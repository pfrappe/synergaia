<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.2 (see AUTHORS file)
* Classe SynerGaia de gestion du cache
*
* Utilise apc si disponible, (2.1) puis memcached si disponible, puis memcache si disponible, sinon utilise le cache php (via variable globale)
*/
class SG_Cache {
	// Type SynerGaia
	const TYPESG = '@Cache';
	public $typeSG = self::TYPESG;

	// Type de cache ; 2.1 memcached
	const TYPE_PHP = 1;	// Cache "PHP"
	const TYPE_MEMCACHE = 2; // Cache "MEMCACHE"
	const TYPE_MEMCACHED = 4; // Cache "MEMCACHE"
	const TYPE_APC = 3;// Cache "APC"
	static $typeCache = 0;
	
	// Suffixe du type de donnée
	const SUFFIXE_TYPE_DONNEE = '_type';
	// Prefixe général des clés du cache
	const PREFIXE_CODE_CACHE = 'SG#';
	// Prefixe aux clés du cache
	static $prefixeCache = '';
	// 1.0.6 Prefixe aux clés du cache de l'utilisateur
	static $prefixeCacheUser = '';

	// Objet de connexion à Memcache
	static $memcache_obj;
	
	// pour memcached
	static $persistent_id;

	/** 1.0.6 ; 2.1 Memcached
	* Initialise le cache
	*
	* @param integer type de cache à forcer
	* @level 0
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
				SG_Cache::$persistent_id = SG_Champ::idRandom();
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
// error_log(print_r(SG_Cache::$memcache_obj -> getStats()));
	}
	/* 1.0.7
	* @level 0
	*/
	static function initPrefixeCacheAppli() {
		SG_Cache::$prefixeCache = SG_Cache::PREFIXE_CODE_CACHE . SG_Config::getCodeAppli() . '#';
		SG_Cache::initPrefixeCacheUser();
	}
	/* 1.0.7
	* @level 0
	*/
	static function initPrefixeCacheUser() {
		SG_Cache::$prefixeCacheUser = SG_Cache::$prefixeCache . SG_SynerGaia::IdentifiantConnexion() . '#';
	}
	/** 1.1 correction erreur
	* Détermine la clé d'accès au cache suivant le code
	* @param string $pCode code de la variable
	* @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	* @return string clé d'accès au cache
	* @level 0
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
	* @level 0
	*/
	static public function getTypeCache($pCode='') {
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

	/** 1.0.6
	* Détermine si la variable est en cache
	*
	* @param string $pCode code de la variable
	* @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	* @return boolean en cache
	* @level 0
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

	/** 1.0.6
	* Extrait la valeur d'une variable du cache
	*
	* @param string $pCode code de la variable
	* @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	* @return indéfini valeur en cache
	* @level 0
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

	/** 1.0.6 ; 2.1 gère directement les objets
	* Définit la valeur d'une variable du cache
	*
	* @param string $pCode code de la variable
	* @param indéfini $pValeur valeur de la variable
	* @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	* @level 0
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

	/** 1.0.6
	* Supprime la valeur d'une variable du cache
	*
	* @param string $pCode code de la variable
	* @param boolean $pUser si True, c'est le cache de l'utilisateur qui est pris, sinon le cache commun
	* @level 0
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

	/** 1.3.1 : calculs, navigation, $pType ; 2.0 purge ; 2.1 '?' liste des clés
	* Vide le cache
	* @param (string ou @Texte) $pType : type(s) de cache à vider
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

	// TODO Terminer la gestion des objets en cache à partir de json...
	static function decodeJson($proprietes = '') {
		if (is_array($proprietes)) {
			if (isset($proprietes['typeSG'])) {
				$objet = $proprietes['typeSG'];
				$objet = new $objet();
				foreach ($proprietes as $key => $value) {
					if (($key !== '_id') && ($key !== 'typeSG')) {
						$this -> proprietes[$key] = $value;
					}
					if ($key === '_rev') {
						$this -> revision = $value;
					}
				}
			} else {
				$objet = new SG_Collection();
				$objet -> elements = $texte;
			}
		} else {
			$objet = $texte;
		}
		return new SG_Erreur('@Cache.DecodeJson pas terminé !!');
	}
	
	static function getCodeCache($pCode='') {
		if ($pCode === '') {
			$ret = SG_Cache::TYPE_PHP;
		} else {
			$ret = SG_Cache::typeCache;
		}
		return $ret;
	}
	/** 1.1 ajout : reporté depuis SG_Connexion
	 * @level >0
	*/
	static function viderCacheSession() {
		if (isset($_SESSION['principal'])) {
			$op = SG_Navigation::OperationEnCours();
			foreach($_SESSION['principal'] as $key => $doc) {
				if ($key !== $op) {
					unset($_SESSION['principal'][$key]);
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
	}
	/* 1.1 ajout ; 1.3.1 purge tous les thèmes ; 2.0 correction Id() ; ModelesOperations ; 2.1 test $utilisateurs @Erreur ; 2.2 viderCacheBanniere
	* @level >0
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
				$codeCache = 'MenuTheme(' .$theme -> Id() . ')';
				self::effacerEnCache($codeCache, true);
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
	/** 2.2 ajout
	* vide le cache des données de la bannière
	**/
	static function viderCacheBanniere() {
		$_SESSION['page']['banniere'] = '';
	}
	/* 1.1 ajout
	* @level 0
	*/
	static function viderCacheCalculs() {
		unset($_SESSION['parms']);
		unset($_SESSION['formule']);
	}
	/* 1.1 ajout
	* @level 0
	*/
	static function viderCacheConfig() {
		unset($SG_Config);
	}
	/* 1.2 ajout ; 1.3.1 getCodeModele, isMultiple sur chaque propriete, getObjetFonction, user=false ; 2.0 isProprieteExiste
	* @level >0
	*/
	static function viderCacheDictionnaire() {
		self::effacerEnCache('getObjetFonction',false);
		$collec = SG_Dictionnaire::Objets();
		foreach($collec -> elements as $objet) {
			$nom = $objet -> getValeur('@Code');
			self::effacerEnCache('getCodeBase(' . $nom . ')', false);
			self::effacerEnCache('getCodeModele(' . $nom . ')', false);
			self::effacerEnCache('@Dictionnaire.classeObjet(' . $nom . ')', false);
			self::effacerEnCache('@Dictionnaire.getLiens(' . $nom . ')', false);
			self::effacerEnCache('@Dictionnaire.@Champs(' . $nom . ')', false);
			self::effacerEnCache('@Dictionnaire.getMethodesObjet(' . $nom . ')', false);
			self::effacerEnCache('@Dictionnaire.isLien(' . $nom . ')', false);
			self::effacerEnCache('isObjetSysteme(' . $nom . ')', false);
			self::effacerEnCache('@Dictionnaire.getProprietesObjet(' . $nom . ',)', false);
			$proprietes = SG_Dictionnaire::getProprietesObjet($nom);
			foreach($proprietes as $propriete => $uid) {
				$codeElement = $nom . '.' .$propriete;
				self::effacerEnCache('isMultiple(' . $codeElement . ')', false);
				self::effacerEnCache('getCodeModele(' . $codeElement . ')', false);
				self::effacerEnCache('IPE(' . $codeElement . ')', false);
			}
		// vider ? $codeCache = '@Dictionnaire.getMethodesObjet(' . $pCodeObjet . ',' . $pModele . ')';
		}
	}
	/** 1.3.4 ajout ; 2.0 static
	* pour tous les objets : false sauf SG_Erreur et dérivés
	* @level 0
	**/
	static function estErreur() {
		return false;
	}
	/** 2.0 ajout
	* purge complètement le cache
	* @level 0
	**/
	static function purge() {
		$ret = false;
		switch (self::$typeCache) {
			case SG_Cache::TYPE_MEMCACHED :
				SG_Cache::$memcache_obj -> flush(); // pose tout périmé (à la seconde)
				$time = time()+1; //attendre une seconde pour péremption effective
				while(time() < $time) {/*attendre*/}
				$ret = true;
				break;
			case SG_Cache::TYPE_MEMCACHE :
				SG_Cache::$memcache_obj -> flush(); // pose tout périmé (à la seconde)
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
	/** 2.1
	* liste des clés
	* @return SG_Collection de SG_Texte
	**/
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
