<?php
/** SYNERGAIA fichier pour le traitement de l'objet @PageInternet */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_PageInternet : classe SynerGaia de gestion d'une page internet
 * @since 1.3.1
 * @version 2.1
 */
class SG_PageInternet extends SG_Document {
	/** string Type SynerGaia '@PageInternet' */
	const TYPESG = '@PageInternet';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** SG_HTML Document physique associé de la page d'origine */
	public $doc;
	
	/** SG_SiteInternet site d'origine de la page */
	public $site;
	
	/** string status de la réponse ('200 OK' par exemple) */
	public $status;
	
	/**
	 * Construction de l'objet
	 * @since 1.3.1
	 * @param string|SG_Texte|SG_Formule $pRefDocument $url du document
	 * @param indefini $pTableau tableau éventuel des propriétés du document
	 */
	public function __construct($pRefDocument = null, $pTableau = null) {
	}

	/**
	 * Met à jour une propriété
	 * @since 1.3.1 ajout
	 * @param string|SG_Texte|SG_Formule $pNomChamp nom de la propriété
	 * @param any $pValeur valeur à stocker
	 * @return SG_PageInternet ceci
	 */
	public function MettreValeur($pNomChamp = '', $pValeur = '') {
		$this -> doc -> MettreValeur($pNomChamp, $pValeur);
		return $this;
	}

	/**
	 * Met à jour la page sur le site d'origine
	 * @since 1.3.1
	 * @version 2.0 parm 2
	 * @version 2.1 supp test provisoire
	 * @param boolean|SG_VraiFaux|SG_Formule $pAppelMethodesEnregistrer (inutilisé ici)
	 * @param boolean|SG_VraiFaux|SG_Formule $pCalculTitre
	 * @return SG_VraiFaux résultat de l'enregistrement
	 */
	public function Enregistrer($pAppelMethodesEnregistrer = true, $pCalculTitre = true) {
		$preenr = SG_VraiFaux::getBooleen($pAppelMethodesEnregistrer);
		$ret = false;
		// préparation si méthode définie
		if (method_exists($this, 'preEnregistrer')) {
			$this -> preEnregistrer();
		}
		if (getTypeSG($this -> site) === '@SiteInternet') {
			$site = $this -> site;
			$url = $site -> url;
			$methode = 'POST';
			foreach($this -> doc -> forms as $form) {
				if($form['action'] !== '') {
					$url = $site -> urlComplete($form['action']);
				}
				if(isset($form['methode']) and !is_null($form['methode']) and $form['methode'] !== '') {
					$methode = $form['methode'];
				}
			}
			$ret = $site -> requete($url, $methode, $this -> doc -> proprietes);
			$ret -> status = $site -> status;
		}
		return $ret;
	}

	/**
	 * extrait du texte html la première partie html correspondant aux critères fournis
	 * 
	 * @since 1.3.1
	 * @param string|SG_Texte|SG_Formule $pBalise suite de triplets (balise, attribut, valeur)
	 * @return SG_HTML|SG_Erreur le noeud demandé ou la collection
	 */
	public function Extraire ($pBalise = '') {
		if ($this -> doc -> texte) {
			if (SG_Texte::getTexte($pBalise) === '') {
				$ret = $this -> doc;
			} else {
				$ret = $this -> doc -> Extraire($pBalise);
			}
		} else {
			$ret = new SG_Erreur('0062');
		}
		return $ret;
	}

	/**
	 * extraire les options d'un champ select sous forme d'une formule SynerGaïa
	 * S'il y a une traduction, la valeur retournée sera xxxxx|X
	 * 
	 * @since 1.3.1
	 * @param  string|SG_Texte|SG_Formule $pBalise balise du champ à extraire
	 * @return (string) formule donnant la collection des valeurs
	 */
	function ExtraireOptions ($pBalise = '') {
		if ($this -> doc -> texte) {
			$ret = $this -> doc -> ExtraireOptions($pBalise);
		} else {
			$ret = new SG_Erreur('0063');
		}
		return $ret;
	}

	/**
	 * Comparer le texte de deux versions d'un document ou deux documents
	 * 
	 * @since 1.3.1
	 * @param SG_PageInternet $pDocument document à comparer
	 * @return array tableau des différences
	 */
	function Comparer ($pDocument = '') {
		if ($this -> doc -> texte) {
			$ret = call_user_func_array(array($this -> doc, 'Comparer'), func_get_args());
		} else {
			$ret = new SG_Erreur('0064');
		}
		return $ret;
	}

	/**
	 * Retourne true si la page est une page de login (contient un champ login + mot de passe
	 * 
	 * @since 1.3.1 ajout
	 * @return boolean résultat
	 **/
	function estPageLogin () {
		// recherche des champs login psw sur la page
		$ret = false;
		$champLogin = $this -> site -> champLogin;
		if($champLogin !== '' and isset($this -> doc -> proprietes[$champLogin])) {
			$champMotDePasse = $this -> site -> champMotDePasse;
			if($champMotDePasse !== '' and isset($this -> doc -> proprietes[$champMotDePasse])) {
				$ret = true;
			}
		}
		return $ret;
	}
	/**
	 * Fournit la valeur de la propriété
	 * @since 1.3.1
	 * @param string|SG_Texte|SG_Formule $pNomChamp nom de la propriété
	 * @param string|SG_Texte|SG_Formule $pValeurDefaut valeur par défaut (si inexistant ou texte vide)
	 * @param string|SG_Texte|SG_Formule $pModele modele attendu (sinon @Texte)
	 * @return SG_Objet valeur de la propriete selon le modèle fourni en paramètre
	 */
	public function Valeur($pNomChamp = '', $pValeurDefaut = '', $pModele = '@Texte') {
		$nomchamp = SG_Texte::getTexte($pNomChamp);
		$modele = sg_Dictionnaire::getClasseObjet(SG_Texte::getTexte($pModele));
		$valeur = new $modele($pValeurDefaut);
		if(isset($this -> doc -> proprietes[$nomchamp])) {
			if($this -> doc -> proprietes[$nomchamp] !== '') {
				$valeur = new $modele($this -> doc -> proprietes[$nomchamp]);
			}
		}
		return $valeur;
	}
}
