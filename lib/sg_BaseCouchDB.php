<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_BaseCouchDB : Classe de gestion d'une base de données CouchDB
*/
class SG_BaseCouchDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@BaseCouchDB';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	/**
	* Code de la base
	*/
	public $codeBase = '';
	/**
	* Code complet de la base avec prefixe
	*/
	public $codeBaseComplet = '';
	/**
	* Url de préfixe des requetes au serveur
	*/
	public $url;

	/** 2.3 getTexte , SG_Config::getCodeBaseComplet()
	* Construction de l'objet
	*
	* @param string $pCodeBase code de la base
	* @param boolean $pCreerSiInexistante creer la base si besoin
	*/
	function __construct($pCodeBase = null, $pCreerSiInexistante = false) {
		$sgbd = $_SESSION['@SynerGaia'] -> sgbd;

		$codeBase = SG_Texte::getTexte($pCodeBase);
		$codeBaseComplet = SG_Config::getCodeBaseComplet($codeBase);

		if (!$sgbd -> BaseExiste($codeBaseComplet)) {
			$creer = $pCreerSiInexistante;
			if (getTypeSG($creer) === '@Formule') {
				$creer = $creer -> calculer();
			}
			if (getTypeSG($creer) !== 'boolean') {
				if (getTypeSG($creer) === '@VraiFaux') {
					$creer = $creer -> estVrai();
				} else {
					$creer = false;
				}
			}   
			if ($creer) {
				$sgbd -> AjouterBase($codeBaseComplet);
			}
		}
		if ($sgbd -> BaseExiste($codeBaseComplet)) {
			$this -> codeBase = $codeBase;
			$this -> codeBaseComplet = $codeBaseComplet;
			$this -> url = $sgbd -> url . $codeBaseComplet;
		}
	}

	/**
	* Renvoie le code de la base
	*
	* @return string code de la base
	*/
	function getCodeBase() {
		return $this -> codeBase;
	}

	/**
	* Supprime la base
	*
	* @return SG_VraiFaux
	*/
	function Supprimer() {
		return $_SESSION['@SynerGaia'] -> sgbd -> SupprimerBase($this -> codeBaseComplet);
	}

	/**
	* Créé un nouveau document dans la base
	*
	* @return SG_DocumentCouchDB nouveau document
	*/
	function CreerDocument() {
		$doc = new SG_DocumentCouchDB();
		$doc -> codeBase = $this -> codeBase;
		return $doc;
	}

	/**
	* La base existe-t-elle ?
	*
	* @return SG_VraiFaux existe ou pas
	*/
	function Existe() {
		return new SG_VraiFaux($_SESSION['@SynerGaia'] -> sgbd -> BaseExiste($this -> codeBaseComplet));
	}

	/** 1.0.6
	* Sauvegarder le contenu de la base dans un fichier
	*
	* @param string $pNomFichier nom du fichier dans lequel enregistrer la sauvegarde
	*
	* @return SG_VraiFaux sauvegarde OK
	*/
	function Sauvegarder($pNomFichier = '') {
		$retBool = false;

		$tmpNomFichier = new SG_Texte($pNomFichier);
		$nomFichier = $tmpNomFichier -> toString();

		// Fabrique le fichier
		$fOut = fopen($nomFichier, 'w');
		// Exporte la base
		$urlRequete = $this -> url . '/_all_docs?include_docs=true';
		$contenuBase = $_SESSION['@SynerGaia'] -> sgbd -> requete($urlRequete);
		$nb = fwrite($fOut, $contenuBase);
		fclose($fOut);

		return new SG_VraiFaux($nb > 0);
	}
	
	/**
	* Restaurer le contenu de la base dans un fichier
	*
	* @param (string ou @Texte) $pNomFichier : chemin complet du fichier de la sauvegarde à importer
	*
	* @return SG_VraiFaux sauvegarde OK
	*/
	function Restaurer($pNomFichier = '') {
		$resultat = false;

		$nomFichier = new SG_Texte($pNomFichier);
		$nomFichier = $nomFichier -> toString();
		// Lit le fichier
		$fichier = file_get_contents($nomFichier);
		if ($fichier === false) {
			$resultat = new SG_Erreur('0054', $nomFichier);
		} else {
			$fichier = json_decode($fichier);
			if (isset($fichier -> rows)) {
				//reformate le fichier
				$docs = '{"_conflicts":false, "docs": [';
				$deb = true;
				foreach($fichier -> rows as $row) {
					if (isset($row -> doc)) {
						if ($deb === false) {
							$docs .= ', ';
						} else {
							$deb = false;
						}
						$row -> doc -> _rev = null;
						$json = json_encode($row -> doc, true);
						$json = str_replace( ',"_rev":null', '',$json);
						$docs .= $json;
					}
				}
				$docs .= ']}';
				// Importe la base
				$urlRequete = $this -> url . '/_bulk_docs';
				$resultat = $_SESSION['@SynerGaia'] -> sgbd -> requete($urlRequete, 'POST', $docs, 'application/json');
			}
		}
		return $resultat;
	}
	/** 1.1 : ajout
	* Compacte la base au sens de CouchDB
	*/
	public function Compacter() {
		$urlRequete = $this -> url . '/_compact';
		$resultat = $_SESSION['@SynerGaia'] -> sgbd -> requete($urlRequete, 'POST', '', 'application/json');
		return $resultat;
	}
}
?>
