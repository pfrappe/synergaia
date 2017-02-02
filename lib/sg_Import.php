<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.2 (see AUTHORS file)
* SG_Import : Classe SynerGaia de gestion des imports de fichier ou flux
*/
class SG_Import extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Import';
	public $typeSG = self::TYPESG;

	// Format de source inconnu
	const FORMAT_INCONNU = 0;
	/**
	 * Format de source CSV
	 */
	const FORMAT_CSV = 1;
	/**
	 * Format de source JSON
	 */
	const FORMAT_JSON = 3;
	/**
	 * Format de source DXL
	 */
	const FORMAT_DXL = 4;

	/**
	 * Emplacement de la source
	 */
	public $source = '';
	/**
	 * Format de la source
	 */
	public $format = 0;
	/**
	 * Type d'objet à importer
	 */
	public $typeObjet = '';
	/**
	 * Séparateur de champs
	 */
	public $separateur = ',';
	/**
	 * Appel des méthodes Enregistrer après enregistrement
	 */
	public $appelEnregistrer = true;

	/**
	 * Construction de l'objet
	 *
	 * @param indéfini $pSource emplacement de la source (url ou fichier)
	 * @param indéfini $pFormat type de contenu (autodéfini si vide)
	 * @param indéfini $pTypeObjet force le type d'objet importé
	 */
	function __construct($pSource = '', $pFormat = self::FORMAT_INCONNU, $pTypeObjet = '') {
		// repérage du fichier ou de la source
		if(getTypeSG($pSource) === 'string') {
			$source = new SG_Texte($pSource);
		} else {
			$source = $pSource -> calculer();
		}
		$this -> source = $source;

		// repérage du format de fichier
		$tmpFormat = new SG_Texte($pFormat);
		$format = '.' . strtolower($tmpFormat -> texte);

		// Si format non fourni => cherche par le nom de la source
		$typeSource = getTypeSG($source);
		if($typeSource === '@Texte') {
			$nomFichier = $this -> source -> texte;
		} elseif ($typeSource === '@Fichier') {
			$nomFichier = $this -> source -> reference;
		} else {
			$nomFichier = new SG_Erreur('0051', $typeSource);
		}
		if (($pFormat === self::FORMAT_INCONNU or $format === '.') and getTypeSG($nomFichier) !== '@Erreur') {
			if (strtolower(substr($nomFichier, -4)) === ".csv") {
				$this -> format = self::FORMAT_CSV;
			} elseif (strtolower(substr($nomFichier, -5)) === ".json") {
				$this -> format = self::FORMAT_JSON;
			} elseif (strtolower(substr($nomFichier, -4)) === ".dxl") {
				$this -> format = self::FORMAT_DXL;
			} else {				
				$this -> format = new SG_Erreur('0052', $nomFichier);
			}
		} else {
			switch (strtolower($format)) {
				case '.csv' :
					$this -> format = self::FORMAT_CSV;
					break;
				case '.json' :
					$this -> format = self::FORMAT_JSON;
					break;
				case '.dxl' :
					$this -> format = self::FORMAT_DXL;
					break;
				default :
					$this -> format = self::FORMAT_INCONNU;
			}
		}
		// repérage du type d'objet qui sera créé
		$tmpTypeObjet = new SG_Texte($pTypeObjet);
		$this -> typeObjet = $tmpTypeObjet -> toString();
	}

	/** 1.0.7
	 * Exécution de l'import
	 *
	 * @param indéfini $pBaseCible code de la base cible
	 * @return @VraiFaux selon le résultat
	 */
	function Importer($pBaseCible = '') {
		$tmpBaseCible = new SG_Texte($pBaseCible);
		$codeBaseCible = $tmpBaseCible -> toString();
		if ($codeBaseCible === '' and $this -> typeObjet !== '') {
			$codeBaseCible = SG_Dictionnaire::getCodeBase($this -> typeObjet);
		}

		$ret = null;
		if ($this -> format === self::FORMAT_CSV) {
			$ret = $this -> importer_CSV($codeBaseCible);
		} elseif ($this -> format === self::FORMAT_JSON) {
			$ret = $this -> importer_JSON($codeBaseCible);
		} elseif ($this -> format === self::FORMAT_DXL) {
			$ret = $this -> importer_DXL($codeBaseCible);
		}
		return $ret;
	}

	/**
	 * Définir le séparateur de champs
	 *
	 * @param indéfini $pSeparateur séparateur
	 */
	function DefinirSeparateur($pSeparateur = ',') {
		$tmpSeparateur = new SG_Texte($pSeparateur);
		$this -> separateur = $tmpSeparateur -> toString();
		return $this;
	}

	/** 1.1.2 traitement d'un @Fichier ; 1.3.2 enregistrer false
	 * Import d'un fichier au format CSV, entêtes = nom des champs
	 *
	 * @param string $codeBaseCible code de la base cible
	 */
	function importer_CSV($codeBaseCible = '') {
		$ret = SG_Rien::Vrai();

		// Liste des champs présents dans le fichier csv
		$champs = array();
		$nbChamps = 0;

		$typeSource = getTypeSG($this-> source);
		$collec = new SG_Collection();
		if($typeSource === '@Fichier') {
			$this-> source -> format = 'csv';
			$collec = $this-> source -> Charger();
		} elseif ($typeSource === '@Texte') {
			if (file_exists($this -> source->texte)) {
				$handle = fopen($this -> source->texte, 'r');
				while (($data = fgetcsv($handle, 4096, $this -> separateur)) !== FALSE) {
					$collec -> elements[] = $data;
				}
			}
		}
		$index_id = -1;	// Colonne par défaut contenant l'id du document
		$index_type = -1;
		$ligne = 1;
		foreach($collec->elements as $data) {
			$num = count($data);
			// Si première ligne => prend les entetes pour connaitre les champs
			if ($ligne === 1) {
				$champs = $data;
				$nbChamps = sizeof($champs);

				// Cherche un champ "_id"
				for ($c = 0; $c < $nbChamps; $c++) {
					if ($champs[$c] === '_id') {
						$index_id = $c;
					} elseif ($champs[$c] === '@Type') {
						$index_type = $c;
					}
				}
			} else {
				// Sinon met les valeurs dans les champs correspondants du document

				// Si j'ai assez de données pour récupérer l'_id
				if (sizeof($data) > $index_id) {
					// Détermine l'id du document
					$document_id = '';
					// Si j'ai une colonne "_id"
					if ($index_id !== -1) {
						$document_id = $data[$index_id];
					} else {
						$document_id = sha1(implode($data));
					}

					if ($codeBaseCible === '') {
						if ($this -> typeObjet === '' and  $index_type !== -1) {
							$codeBaseCible = $_SESSION['@SynerGaia'] -> getCodeBase($data[$index_type]);
						}
					}
					$document = new SG_Document($codeBaseCible . '/' . $document_id);
					$document_modif = false;

					// Définit le type si besoin
					if ($this -> typeObjet !== '') {
						$ancienne_valeur = $document -> getValeur('@Type');
						if ($ancienne_valeur !== $this -> typeObjet) {
							$document -> setValeur('@Type', $this -> typeObjet);
							$document_modif = true;
						}
					}
					for ($c = 0; $c < $num; $c++) {
						$nomChamp = '';
						if ($c < $nbChamps) {
							$nomChamp = $champs[$c];
						} else {
							$nomChamp = 'col' . strval($c);
						}
						$valeurChamp = utf8_encode($data[$c]);

						// TODO vérifier que les données du fichier csv sont bien avec un encoding accepté

						if ($nomChamp !== '_id') {
							$ancienne_valeur = $document -> getValeur($nomChamp, '');
							if ($ancienne_valeur !== $valeurChamp) {
								$document -> setValeur($nomChamp, $valeurChamp);
								$document_modif = true;
							}
						}

					}

					if ($document_modif === true) {
						$document -> Enregistrer($this -> appelEnregistrer, false);
					}
				}
			}
			$ligne++;
		}
		return $ret;
	}

	/** 1.3.2 engistrer false
	 * Import d'un fichier au format JSON
	 *
	 * @param string $codeBaseCible code de la base cible
	 *
	 * @return SG_VraiFaux
	 */
	function importer_JSON($codeBaseCible = '') {
		$ret = SG_Rien::Vrai();

		$typeSource = getTypeSG($this-> source);
		$contenuTexte = '';
		if($typeSource === '@Fichier') {
			$this-> source -> format = 'json';
			if(isset($this->proprietes[$this->reference]['data'])) {
				$contenuTexte = base64_decode($this->proprietes[$this->reference]['data']);
			}
		} elseif ($typeSource === '@Texte') {
			if (file_exists($this -> source -> texte)) {
				//$handle = fopen($this -> source->texte, 'r');				
				$contenuTexte = file_get_contents($this -> source -> texte);
			}
		}
		if ($contenuTexte !== '') {
			$contenuJSON = json_decode($contenuTexte, true);
			if ($contenuJSON !== null) {
				foreach ($contenuJSON as $key => $val) {
					// Pour chaque document rencontré
					$nbVal = sizeof($val);
					for ($i = 0; $i < $nbVal; $i++) {
						$docJSON = $val[$i];

						// Calcul de l'_id du document
						$idDoc = '';
						if (array_key_exists('_id', $docJSON) === true) {
							$idDoc = $docJSON['_id'];
						} else {
							if (array_key_exists('@Code', $docJSON) === true) {
								$idDoc = $docJSON['@Code'];
							} else {
								if (array_key_exists('Code', $docJSON) === true) {
									$idDoc = $docJSON['Code'];
								}
							}
						}

						// Fabrique le document
						$doc = new SG_Document($codeBaseCible . '/' . $idDoc);
						foreach ($docJSON as $champ => $valeur) {
							// Définit la valeur des champs
							if ($champ !== '_id') {
								$doc -> setValeur($champ, $valeur);
							}
						}
						// Enregistre ; 1.3.2 ,false)
						$doc -> Enregistrer($this -> appelEnregistrer, false);
					}
				}
			} else {
				$ret = new SG_Erreur(SG_Erreur::ERR_FICHIER_JSON_INVALIDE, $this -> source);
			}
		} else {
			$ret = new SG_Erreur(SG_Erreur::ERR_FICHIER_NON_TROUVE, $this -> source);
		}

		return $ret;
	}

	/** 1.3.2 engistrer false
	 * Import d'un fichier au format DXL (Lotus Notes), 
	 *
	 * @param string $codeBaseCible code de la base cible
	 */
	function importer_DXL($codeBaseCible = '') {
		$ret = SG_Rien::Vrai();

		if (file_exists($this -> source)) {
			// Eclate le fichier en plus petits
			$fichiers = array();
			$nbMaxiDocs = 50;
			$nbDocs = 0;
			$nbFichiers = 0;
			$enteteXML = '';
			$enteteXML_ok = false;
			$document = '';
			$documents = array();
			$fIn = fopen($this -> source, 'r');
			if ($fIn) {
				// Lecture de l'entete XML DXL : <database ...><databaseinfo>...</databaseinfo>
				while (($line = fgets($fIn)) !== false) {
					// Détermine l'entete XML
					if ($enteteXML_ok === false) {
						// Cherche '</databaseinfo>'
						if (strpos($line, '</databaseinfo>') === false) {
							$enteteXML .= $line;
							$line = '';
						} else {
							$enteteXML .= substr($line, 0, strpos($line, '</databaseinfo>') + strlen('</databaseinfo>'));
							$line = substr($line, strpos($line, '</databaseinfo>') + strlen('</databaseinfo>'));
							$enteteXML_ok = true;
						}
					}
					if ($enteteXML_ok === true) {
						// On n'est plus dans l'entete : lit les documents

						// Si pas encore de document, on cherche '<document'
						if ($document === '') {
							if (strpos($line, '<document') === false) {
								// Pas trouvé => ne fait rien
							} else {
								// Trouvé '<document'
								$finLigne = substr($line, strpos($line, '<document'));
								$document = substr($finLigne, 0, strpos($finLigne, '>') + strlen('>'));
								$line = substr($finLigne, strpos($finLigne, '>') + strlen('>'));
							}
						}
						// Si un document en cours de lecture, on cherche '</document>'
						if ($document !== '') {
							if (strpos($line, '</document>') === false) {
								// Pas trouvé => prend toute la ligne
								$document .= $line;
								$line = '';
							} else {
								// Trouvé => ne prend que le début de la ligne
								$document .= substr($line, 0, strpos($line, '</document>') + strlen('</document>'));
								$line = substr($line, strpos($line, '</document>') + strlen('</document>'));

								// On a une fin de document : ajoute à la liste des documents en cours
								$documents[] = $document;
								$document = '';
							}
						}

						// On a atteint la limite du nombre de documents ou on est à la fin du fichier
						if ((sizeof($documents) === $nbMaxiDocs) || (strpos($line, '</database>') !== false)) {
							if (sizeof($documents) !== 0) {
								$nbFichiers++;

								// fabrique un fichier temporaire
								$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'synergaia_dxl_' . $codeBaseCible . '_' . substr('00000' . $nbFichiers, -5) . '.tmp';

								$fOut = fopen($tmpFile, 'w');
								fwrite($fOut, $enteteXML . PHP_EOL);
								foreach ($documents as $tmpDocument) {
									fwrite($fOut, $tmpDocument . PHP_EOL);
								}
								fwrite($fOut, '</database>');
								fclose($fOut);
								$fichiers[] = $tmpFile;

								$documents = array();
							}
						}
					}
				}
			}

			foreach ($fichiers as $fichier) {
				// Parse le fichier
				$database = simplexml_load_file($fichier);

				// Cherche les infos de la base
				$db_replicaid = (string)$database['replicaid'];
				$db_path = (string)$database['path'];
				$db_title = (string)$database['title'];

				$tmp_database_databaseinfo = $database -> databaseinfo[0];
				$db_id = (string)$tmp_database_databaseinfo['dbid'];
				$db_numberofdocuments = (string)$tmp_database_databaseinfo['numberofdocuments'];

				// Lecture des documents
				$listeDocuments = $database -> document;
				$nombreDocuments = sizeof($listeDocuments);
				for ($numDocument = 0; $numDocument < $nombreDocuments; $numDocument++) {
					$document = $listeDocuments[$numDocument];

					$doc_form = (string)$document['form'];
					$doc_parent = (string)$document['parent'];

					$tmp_doc_noteinfo = $document -> noteinfo;
					$doc_unid = (string)$tmp_doc_noteinfo['unid'];

					$doc = new SG_Document($codeBaseCible . '/' . $doc_unid);
					$doc -> setValeur('@Type', $doc_form);
					if ($doc_parent !== '') {
						$doc -> setValeur('@Parent', $doc_parent);
					}

					// Lecture des champs
					$listeItems = $document -> item;
					$nombreItems = sizeof($listeItems);
					for ($numItem = 0; $numItem < $nombreItems; $numItem++) {
						$item = $listeItems[$numItem];

						$item_name = (string)$item['name'];

						// Remplace le premier caractère "_" par "@" si besoin
						if (substr($item_name, 0, 1) === '_') {
							$item_name = '@' . substr($item_name, 1);
						}

						$tmp_item_valeur = $item -> children();
						foreach ($tmp_item_valeur as $item_type => $item_valeur) {
						}

						switch($item_type) {
							case 'text' :
							case 'number' :
								$item_valeur = utf8_decode((string)$item_valeur[0]);
								break;
							case 'datetime' :
								$datetime = (string)$item_valeur[0];
								$tmp_date = substr($datetime, 0, 8);
								$tmp_heure = substr($datetime, 9, 6);
								$str_datetime = '';
								if ($tmp_date !== '') {
									$str_datetime .= substr($tmp_date, 0, 4) . '/';
									$str_datetime .= substr($tmp_date, 4, 2) . '/';
									$str_datetime .= substr($tmp_date, 6, 2) . ' ';
								}
								if ($tmp_heure !== '') {
									$str_datetime .= substr($tmp_heure, 0, 2) . ':';
									$str_datetime .= substr($tmp_heure, 2, 2) . ':';
									$str_datetime .= substr($tmp_heure, 2, 2);
								}
								$item_valeur = $str_datetime;
								break;
							case 'textlist' :
								$tmp_liste = $item_valeur[0];
								$liste = array();
								foreach ($tmp_liste as $element) {
									$liste[] = utf8_decode((string)$element);
								}
								$item_valeur = $liste;
								break;
							case 'richtext' :
								$item_valeur = utf8_decode($item_valeur -> asXML());
								break;
							case 'object' :
								$item_valeur = $item_valeur -> asXML();
								break;
							default :
								$item_valeur = $item_valeur -> asXML();
								break;
						}
						$doc -> setValeur($item_name, $item_valeur);
					}
					$doc -> Enregistrer($this -> appelEnregistrer, false);
				}
				unlink($fichier);
			}
		} else {
			$ret = new SG_Erreur(SG_Erreur::ERR_FICHIER_NON_TROUVE, $this -> source);
		}
		return $ret;
	}

}
?>
