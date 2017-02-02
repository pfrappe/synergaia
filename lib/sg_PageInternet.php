<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1 (see AUTHORS file)
* SG_PageInternet : classe SynerGaia de gestion d'une page internet
*/
class SG_PageInternet extends SG_Document {
	// (string) Type SynerGaia
	const TYPESG = '@PageInternet';
	public $typeSG = self::TYPESG;

	// (@HTML) Document physique associé de la page d'origine)
	public $doc;
	
	// (@SiteInternet) site d'origine
	public $site;
	
	// (string) status de la réponse ('200 OK' par exemple)
	public $status;
	
	/** 1.3.1 ajout
	* Construction de l'objet
	* @param string $pRefenceDocument $url du document
	* @param indefini $pTableau tableau éventuel des propriétés du document
	*/
	public function __construct($pRefDocument = null, $pTableau = null) {
	}
	/** 1.3.1 ajout
	* Met à jour la propriété
	* @param (string ou @Texte) nom de la propriété
	* @param (any) valeur à stocker
	* @return (@PageInternet) ceci
	**/
	public function MettreValeur($pNomChamp = '', $pValeur = '') {
		$this -> doc -> MettreValeur($pNomChamp, $pValeur);
		return $this;
	}
	/** 1.3.1 ajout ; 2.0 parm 2 ; 2.1 supp test provisoire
	* Met à jour la page sur le site d'origine
	* @param (boolean) $pAppelMethodesEnregistrer (inutilisé ici)
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
	/** 1.3.1 ajout
	* extrait du texte html la première partie html correspondant aux critères fournis
	* @param suite de triplets (balise, attribut, valeur)
	* @return (SG_HTML) le noeud demandé ou la collection
	**/
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
	/** 1.3.1 ajout
	* extraire les options d'un champ select sous forme d'une formule SynerGaïa
	* S'il y a une traduction, la valeur retournée sera xxxxx|X
	* @param (string) balise du champ à extraire
	* @return (string) formule donnant la collection des valeurs
	**/
	function ExtraireOptions ($pBalise = '') {
		if ($this -> doc -> texte) {
			$ret = $this -> doc -> ExtraireOptions($pBalise);
		} else {
			$ret = new SG_Erreur('0063');
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* extraire les options d'un champ select sous forme d'une formule SynerGaïa
	* S'il y a une traduction, la valeur retournée sera xxxxx|X
	* @param (string) balise du champ à extraire
	* @return (string) formule donnant la collection des valeurs
	**/
	function Comparer ($pDocument = '') {
		if ($this -> doc -> texte) {
			$ret = call_user_func_array(array($this -> doc, 'Comparer'), func_get_args());
		} else {
			$ret = new SG_Erreur('0064');
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* Retourne true si la page est une page de login (contient un champ login + mot de passe
	* @return (boolean) résultat
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
	/** 1.3.1 ajout
	* Fournit la valeur de la propriété
	* @param (@Texte) nom de la propriété
	* @param (@Texte) valeur par défaut (si inexistant ou texte vide)
	* @param (@Texte) modele attendu (sinon @Texte)
	* @return (any) valeur en texte de la propriete
	**/
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
