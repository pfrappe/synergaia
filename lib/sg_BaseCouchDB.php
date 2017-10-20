<?php
/** SynerGaia fichier pour la gestion de l'objet @BaseCouchDB */
 defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_BaseCouchDB : Classe de gestion d'une base de données CouchDB
 * @version 2.6 
 */
class SG_BaseCouchDB extends SG_Objet {
	/** string Type SynerGaia '@BaseCouchDB' */
	const TYPESG = '@BaseCouchDB';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/**
	* string Code de la base
	*/
	public $codeBase = '';
	/**
	* string Code complet de la base avec prefixe
	*/
	public $codeBaseComplet = '';
	/**
	* string Url de préfixe des requetes au serveur
	*/
	public $url;

	/**
	 * Construction de l'objet
	 *
	 * @version 2.3 getTexte , SG_Config::getCodeBaseComplet()
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
	 *  @return SG_VraiFaux
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
	 * La base physique existe-t-elle ?
	 *
	 * @return SG_VraiFaux existe ou pas
	 */
	function Existe() {
		return new SG_VraiFaux($_SESSION['@SynerGaia'] -> sgbd -> BaseExiste($this -> codeBaseComplet));
	}

	/**
	 * Sauvegarder le contenu de la base dans un fichier
	 * 
	 * @since 1.0.6
	 * @version 2.4 vers directorie et serialize
	 * @version 2.6 n° erreurs, $prefixe
	 * @todo manque l'export des méthodes 
	 * @param string $pDir nom du répertoire dans lequel enregistrer la sauvegarde
	 * @param string $pPrefixe préfixe de sélection des objets à exporter
	 * @return SG_VraiFaux sauvegarde OK
	 */
	function Sauvegarder($pDir = '', $pPrefixe = '') {
		$ret = true;
		// vérifier que le répertoire de sortie est fourni, accessible ou le créer
		$dir = SG_Texte::getTexte($pDir);
		if ($dir === '') {
			$ret = new SG_Erreur('0286');
		} else {
			if (substr($dir, strlen($dir) -1, 1) !== '/') {
				$dir.= '/';
			}
			$handle = opendir($dir);
			if (!$handle) {
				$ret = new SG_Erreur('0287');
			} else {
				$dir.= $this -> codeBaseComplet . '_' . date('Y_m_d') . '/';	
				if (!is_dir($dir)) {
					mkdir($dir, 0777, true);
				}
				$prefixe = SG_Texte::getTexte($pPrefixe);
				// récupérer tous les ids
				$url = $_SESSION['@SynerGaia'] -> sgbd -> url . $this -> codeBaseComplet . '/_all_docs';
				if ($prefixe !== '') {
					$url.= '?startkey="' . $prefixe . '"&endkey="' . $prefixe . 'ZZZZZZZZZZZZZZZZZZZ"';
				}
				$res = $_SESSION['@SynerGaia'] -> sgbd -> requete($url, "GET");
				try {
					ini_set('memory_limit', '512M');
					$res = json_decode($res, true);
					$res = $res['rows'];
					ini_restore('memory_limit');
				} catch (Exception $e) {
					$ret = new SG_Erreur('0119', $e -> getMessage());
				}
				
				// traiter les fichiers
				if ($ret === true) {
					$ret = array();
					$url = $_SESSION['@SynerGaia'] -> sgbd -> url . $this -> codeBaseComplet . '/';
					foreach ($res as $key => $row) {
						if(! is_array($row) or !isset($row['id'])) {
							$ret[] = new SG_Erreur('0288', $key);
						} else {
							$id = $row['id'];
							if (substr($id, 0, 8) !== '_design/') {// laisser tomber le design
								$jsondoc = $_SESSION['@SynerGaia'] -> sgbd -> requete($url . '/' . $id . '?attachments=false', "GET");
								$doc = json_decode($jsondoc, true);
								$ret[] = $id . ' : ' . file_put_contents($dir . $id, $jsondoc);
								if (isset($doc['_attachments'])) {
									mkdir($dir . $id . '/attachments', 0777, true);
									foreach ($doc['_attachments'] as $fic => $stub) {
										$jsondoc = $_SESSION['@SynerGaia'] -> sgbd -> requete($url . '/' . $id . '/' . $fic, "GET");
										$ret[] = $id . '/' . $fic . file_put_contents($dir . $id . '/attachments/' . $fic, $jsondoc);
									}
								}
							}
						}
					}
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Restaurer le contenu de la base dans un fichier
	 *
	 * @param (string ou @Texte) $pNomFichier : chemin complet du fichier de la sauvegarde à importer
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

	/**
	 * Compacte la base au sens de CouchDB
	 * 
	 * @since 1.1 ajout
	 * @version 2.4 view cleanup
	 * @return string le résultat du compactage
	 */
	public function Compacter() {
		$urlRequete = $this -> url . '/_view_cleanup';
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> requete($urlRequete, 'POST', '', 'application/json');
		$urlRequete = $this -> url . '/_compact';
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> requete($urlRequete, 'POST', '', 'application/json');
		return $ret;
	}

	/**
	 * Retourne le tableau des informations sur la base CouchDB
	 * 
	 * @since 2.4
	 * @return SG_Collection tableau des informations 
	 **/
	public function Infos() {
		$url = 'http://localhost:5984/' . $this -> codeBaseComplet;
		$infos = $_SESSION['@SynerGaia'] -> sgbd -> requete($url, 'GET', '', 'application/json');
		$ret = new SG_Collection();
		$infos = json_decode($infos);
		foreach ($infos as $key => $elt) {
			$ret -> elements[] = new SG_Texte($key . ' : ' . $elt);
		}
		return $ret;
	}
}
?>
