<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Paquet */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Paquet : Classe de traitement d'un pack standard SynerGaïa
 * @since 1.3.1
 * @version 2.6 devient un document géré via le dictionnaire et des opérations
 */
class SG_Paquet extends SG_Document {
	/** string Type SynerGaia  '@Paquet' */
	const TYPESG = '@Paquet';
	/** string Code de la base de stockage */
	const CODEBASE = 'synergaia_paquets';
	/** string préfixe du module php */
	const PREFIXE = 'Pack_';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;
	
	/** string code du paquet */
	public $code='';
	/** string chemin de stockage */
	public $chemin='';
	/** string titre du paquet */
	public $titre= '';
		
	/** string type : privé 'p', standard SynerGaïa 's' */
	public $type='s';

	/**
	 * Titre
	 * @since 1.3.1
	 * @return SG_Texte
	 */
	function Titre() {
		return new SG_Texte($this -> toString()) ;
	}

	/**
	 * texte de sortie
	 * 
	 * @since 1.3.1
	 * @version 2.7 parm1 compatibilité SG_Document
	 * @return string
	 */
	function toString($pDefaut = null) {
		return $this -> titre;
	}

	/**
	 * Code
	 * 
	 * @since 1.3.1
	 * @return SG_Texte
	 */
	function Code() {
		return new SG_Texte($this -> code);
	}

	/**
	 * Importer ou mettre à jour dans SynerGaïa
	 * 
	 * @since 1.3.1
	 * @version 2.6
	 * @return SG_Paquet|SG_Erreur
	 */
	function Importer() {
		$import = new SG_Import($this -> chemin . $this -> code . '.json');
		$ret = $import -> Importer(SG_Dictionnaire::CODEBASE);
		if (!$ret instanceof SG_Erreur) {
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * Type en code
	 * 
	 * @since 1.3.1
	 * @return SG_Texte
	 */
	function Type() {
		return new SG_Texte($this -> type);
	}

	/**
	 * Type en clair
	 * 
	 * @since 1.3.1
	 * @return SG_Texte|SG_Erreur type en clair
	 */
	function TypeEnClair() {
		switch ($this -> type) {
			case 's' :
				$ret = new SG_Texte('Standard SynerGaïa');
				break;
			case 'p' :
				$ret = new SG_Texte('Privé');
				break;
			default :
				$ret = new SG_Erreur('0067', $this -> type);
		}
		return $ret;
	}

	/**
	 * Exécute un paquet contenant des formules SynerGaïa.
	 * Cette façon de procéder permet d'inclure des traitements sur les données incluses
	 * ainsi que des tests sur l'existence d'objets, le paramétrage préalable d'options, etc.
	 * 
	 * @since 2.6
	 * @return SG_Paquet|SG_Erreur
	 */
	function Installer() {
		$formule = $this -> getValeur('@Formule');
		$classe = self::PREFIXE . $this -> getValeur('@Code','');
		if (!class_exists($classe)) {
			$ret = new SG_Erreur('0298', $classe);
		} else {
			$operation = new $classe();
			$ret = $operation -> Traiter();
			if (!$ret instanceof SG_Erreur) {
				$ret = new SG_Erreur('0297');
				SG_Cache::ViderCache();
			}
		}
		return $ret;
	}

	/**
	 * Prépare la compilation de la formule et met à jout le fichier .php
	 * 
	 * @since 2.6
	 * @return boolean|SG_Erreur
	 */
	function preEnregistrer() {
		$ret = $this -> Compiler();
		return $ret;
	}

	/**
	 * Compile la formule de l'objet en PHP et stocke le résultat dans le répertoire des objets compilés
	 * C'est l'équivalent d'un modèle d'opération mais son préfixe est par Pack_
	 * 
	 * @since 2.6
	 * @return boolean|SG_Erreur
	 */
	function Compiler() {
		// terminer l'initialisation
		if (is_null($this -> code) or $this -> code === '') {
			$this -> code = $this -> getValeur('@Code','');
		}
		// compiler avant de sauvegarder
		$formule = $this -> getValeur('@Formule', '');
		$compil = new SG_Compilateur($formule);
		$compil -> titre = 'Paquet : ' . $this -> toString();
		$ret = $compil -> Traduire();
		// tester le retour
		if (! $ret instanceof SG_Erreur) {
			if ($compil -> erreur !== '') {
				$ret = $compil -> erreur;
				$ret -> gravite = SG_Erreur::ERREUR_CTRL;
				SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
			} else {
				// si pas d'erreur, créer la classe du modèle d'opération
				if ($compil -> php !== '') {
					$this -> setValeur('@PHP', 'oui' );
				} else {
					$this -> setValeur('@PHP', '' );
				}
				$ret = $compil -> compilerOperation($this -> code, $formule, $compil -> php, self::PREFIXE);
				if ($ret instanceof SG_Erreur) {
					SG_Pilote::OperationEnCours() -> erreurs[] = $ret;
				} elseif ($compil -> erreur !== '') {
					SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
				}
			}
		}
		return $ret;
	}
}
