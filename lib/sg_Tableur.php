<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Tableur */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Tableur : Classe SynerGaia de gestion des tableaur via PHPExcel
 * @since 1.3.0
 * @version 2.4
 * @uses PHPExcel
 */
class SG_Tableur extends SG_Objet {
	/** string Type SynerGaia '@Tableur' */
	const TYPESG = '@Tableur';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** PHPExcel Objet PHPExcel pour les manipulations */
	public $fichier;
	
	/** PHPExcel feuille active (objet PHPExcel) */
	public $feuilleactive;
	/** PHPExcel ligne active (objet PHPExcel) */
	public $ligneactive;
	/** PHPExcel colonne active (objet PHPExcel) */
	public $colonneactive;
	
	/** string format de date sur tableur */
	private $formatDate;
	
	/** array traduction enn chiffres des mois anglais en 3 lettres */
	private $moistrad = array('jan'=>'01','feb' => '02','mar' => '03', 'apr' => '04','may' => '05','jun' => '06'
		,'jul' => '07','aug' => '08','sep' => '09','oct' => '10','nov' => '11','dec' => '12');

	/**
	* Construction de l'objet
	* 
	* @since 1.3.0
	* @version 2.4 init feuille 1
	* @param indéfini $pSource emplacement de la source (url ou fichier)
	*/
	function __construct($pSource = '') {
		if (!class_exists('PHPExcel_IOFactory')) {
			SG_Pilote::OperationEnCours() -> STOP('0272');
		} else {
			if (! class_exists('ZipArchive')) {
				PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
			}
			$source = SG_Texte::getTexte($pSource);
			if ($source !== '') {
				try {
					$this -> fichier = PHPExcel_IOFactory::load($source);
				} catch(Exception $e) {
					SG_Pilote::OperationEnCours() -> STOP('0271', $e -> getMessage());
				}
			} else {
				$this -> fichier = new PHPExcel();
			}
			$this -> Feuille(1);
		}
	}

	/**
	* Sélection une feuille
	* 
	* @since 1.3.0 ajout
	* @version 2.0 test fichier
	* @version 2.4 return this
	* @param (any) si string : par nom, si numérique pas n°
	* @return SG_Tableur ce tableur
	*/
	public function Feuille() {
		if (getTypeSG($this -> fichier) === '@Erreur') {
			$feuille = $this -> fichier;
		} elseif (func_num_args() === 0) {
			$feuille = $this -> fichier -> getActiveSheet();
		} else {
			$index = func_get_arg(0);
			if (getTypeSG($index) === '@Formule') {
				$index = $index -> calculer();
			}
			if (getTypeSG($index) === '@Nombre') {
				$index = $index -> toInteger();
			} elseif (getTypeSG($index) === '@Texte') {
				$index = $index -> texte;
			}
			if (is_string($index)) {
				$feuille = $this -> fichier -> setActiveSheetIndexByName($index);
			} elseif (is_numeric($index)) {
				$feuille = $this -> fichier -> setActiveSheetIndex($index - 1);
			} else {
				$feuille = $this -> fichier -> setActiveSheetIndex(0);
			}
		}
		$this -> feuilleactive = $feuille;
		return $feuille;
	}

	/**
	* Sélection d'une cellule de feuille
	* 
	* @since 1.3.0 ajout
	* @param integer $indexLigne indice de ligne (de 1 à maxLigne)
	* @param integer $indexColonne indice de ligne (de 1 à maxColonne)
	* @return SG_DateHeure|SG_Texte|SG_Nombre contenu de la cellule
	*/
	public function Cellule($indexLigne = 1, $indexColonne = 'A') {
		$iligne = new SG_Nombre($indexLigne);
		$iligne = $iligne -> toInteger();
		$icolonne = new SG_Texte($indexColonne);
		$icolonne = $icolonne -> texte;
		$feuille = $this -> Feuille();
		try {
			$cell = $feuille -> getCell($icolonne . $iligne) -> getFormattedValue();
			if (SG_DateHeure::validerTemps($cell)) {
				$ret = new SG_DateHeure($cell);
			} elseif (is_numeric($cell)) {
				$ret = new SG_Nombre($cell);
			} else {
				$ret = new SG_Texte($cell);
			}
		} catch (Exception $e) {
			$ret = new SG_Erreur('Indices de colonne ou de ligne hors limite de la feuille : ' . $e->getMessage());
		}
		return $ret;
	}

	/**
	 * parcourt une ligne et transfère une cellule dans chaque champ du document
	 * 
	 * @since 1.3.0 ajout
	 * @version 2.0 test $feuille
	 * @param integer|SG_Nombre|SG_Formule $pDebut
	 * @param integer|SG_Nombre|SG_Formule $pFin
	 * @return SG_Collection
	 */
	public function Lignes($pDebut = 1, $pFin = 0) {
		// interprétation des paramètres
		$debut = new SG_Nombre($pDebut);
		$debut = $debut -> toInteger();
		if ($debut <= 0) {
			$debut = 1;
		}
		$lignemax = $this -> NoDerniereLigne() -> valeur;
		if($debut > $lignemax) {
			$debut = $lignemax;
		}
		$fin = new SG_Nombre($pFin);
		$fin = $fin -> valeur;
		if($fin == 0) {
			$fin = $lignemax; 
		} elseif ($fin < 0) {
			$fin = $lignemax - $ifin;
		} elseif ($fin > $lignemax) {
			$fin = $lignemax;
		}
		// pour chaque rangée...
		$feuille = $this -> Feuille();
		if (getTypeSG($feuille) === '@Erreur') {
			$ret = $feuille;
		} else {
			$icolmax = $feuille -> getHighestColumn();
			$icolmax++;
			$ret = new SG_Collection();
			for($iligne = $debut; $iligne <= $fin; $iligne++) {
				// pour chaque colonne...			
				$collecColonne = new SG_Collection();
				for($icol = 'A'; $icol !== $icolmax; $icol++) {
					$cell = $this -> Cellule($iligne, $icol);
					$collecColonne -> elements[$icol] = $cell;
				}
				$ret -> elements[$iligne] = $collecColonne;
			}
		}
		return $ret; 
	}

	/**
	 * Importer une feuille de tableur dans des documents
	 * Chaque ligne représente les champs d'un document du type passé en paramètre.
	 * Rien n'est fait si aucun champ n'est passé ou les paramètres sont en erreur.
	 * La fonction .@Enregistrer n'est pas exécutée.
	 * 
	 * @since 1.3.0
	 * @version 2.0 getTexte, test fichier, test pas clé, variables document temporaires
	 * @version 2.4 modif sur calcul modele propriété ; test dates
	 * @param string|SG_Texte|SG_Formule $pTypeObjet = '' : le type d'objet de la collection 
	 * @param string|SG_Texte|SG_Formule $pChampCle = '' : le nom du champ clé (devientdra @Code)
	 * @param string|SG_Texte|SG_Formule $pColIndex = 'A' : la colonne où se trouve la valeur du champ clé
	 * @param string|SG_Texte|SG_Formule $pChamps = null : la suite des champs (soit "champ1, champ2" si tout est pris, soit "A|champ1, C|champ2, Z|champs3" si sélection
	 * @param integer|SG_Nombre|SG_Formule $pDebut = 1 : n0 de la première ligne (à partir de 1)
	 * @param integer|SG_Nombre|SG_Formule $pFin = 0 : n° de la dernière ligne (0 si jusqu'à la fin)
	 * @param SG_Formule $pFiltre = '' : filtre exécuté sur le document avant de le garder dans la collection
	 * @return SG_Collection : la collection de documents
	 */
	public function Importer($pTypeObjet = '', $pChampCle = '', $pColIndex = 'A', $pChamps = '', $pDebut = 1, $pFin = 0, $pFiltre = '') {
		if ($this -> fichier instanceof SG_Erreur) {
			$ret = $this -> fichier;
		} else {
			$ret = new SG_Collection();
			// reformatage des paramètres
			$typeObjet = SG_Texte::getTexte($pTypeObjet);
			// si type d'objet fourni
			if ($typeObjet === '') {
				$typeObjet = '@Document';
			}
			$champCle = SG_Texte::getTexte($pChampCle);
			$index = SG_Texte::getTexte($pColIndex);
			// recherche de la liste des n° de colonne de la feuille
			$colmax = $this -> NoDerniereColonne() -> toString();
			$icol = 'A';
			$champstous = array();
			while ($icol <= $colmax) {
				$champstous[] = $icol;
				$icol++;
			}
			// analyse de la liste des champs fournis
			if(is_null($pChamps)) {
				$champs = $champstous;
			} else {
				$type = getTypeSG($pChamps);
				if($type === '@Formule') {
					$champs = $pChamps -> calculer();
					$type = getTypeSG($champs);
				} else {
					$champs = $pChamps;
				}
				if($type === 'string' or $type === '@Texte') {
					$txt = SG_Texte::getTexte($champs);
					if ($txt === '') {
						$champs = $champstous;
					} else {
						$champs = explode(',', $txt);
					}
				} elseif($type === '@Collection') {
					$tmp = array();
					foreach($champs -> elements as $champ) {
						$tmp[] = SG_Texte::getTexte($champ);
					}
					$champs = $tmp;
				} else {
					$ret = new SG_Erreur('0203');
				}
			}
		}
	
		if (! $ret instanceof SG_Erreur) {
			// préparation des champs à traiter
			$champsdoc = array();
			$icol = 'A';
			foreach($champs as $champ) {
				$r = array();
				if ($champ !== '') {
					$c = explode('|', $champ);
					if (sizeof($c) > 1) {
						$r[0] = $c[1];
						$icol = $c[0];
						$r[1] = $icol;
					} else {
						$r[0] = $champ;
						$r[1] = $icol;
					}
					// test si propriété locale temporaire
					if (SG_Dictionnaire::isProprieteExiste($typeObjet, $r[0])) {
						$r[2] = true;
						$r[3] = SG_Dictionnaire::getModelePropriete($typeObjet, $r[0]);
						$ipos = strpos($r[3],'/');
						if ($ipos !== false) {
							$r[3] = substr($r[3],$ipos + 1);
						}
					} else {
						$r[2] = false;
						$r[3] = '@Texte';
					}
					$champsdoc[] = $r;
				}
				$icol++;
			}
			// boucle sur les lignes
			$lignes = $this -> Lignes($pDebut,$pFin) -> elements;
			$iligne = 1;
			$formule = new SG_Formule();
			$cle = '';
			foreach($lignes as $key => $ligne) {
				// nouveau document ou rechercher sur clé ?
				if ($champCle === '' or $cle === '') {
					$doc = SG_Rien::Nouveau($typeObjet);
				} else {
					$cle = SG_Texte::getTexte($ligne -> elements[$index]);
					$formule -> setFormule( '.' . $champCle . '.@Egale("'. $cle . '")');
					$collec = SG_Rien::Chercher($typeObjet, $formule);
					if($collec instanceof SG_Collection) {
						if(sizeof($collec -> elements) == 0) {
							$doc = SG_Rien::Nouveau($typeObjet);
						} else {
							$doc = $collec -> Premier();
						}
					} else {
						$doc = new SG_Erreur('0114', $typeObjet . ' : ' . $cle);
					}
				}
				// mise à jour des propriétés
				$doc -> proprietes['noligne'] = new SG_Nombre($key);
				if (! $doc instanceof SG_Erreur) {
					foreach($champsdoc as $champ) {
						if ($champ[0] !== '') {
							if (isset($ligne -> elements[$champ[1]])) {
								$val = $ligne -> elements[$champ[1]];
								// traitement des dates
								if ($champ[3] === '@Date' and ! $val instanceof SG_Date) {
									$val = SG_Texte::getTexte($val);
									if (!is_null($this -> formatDate)) {
										$dt = new SG_Date();
										$dt -> _date = DateTime::createFromFormat($this -> formatDate, $val);
										if ($dt -> _date instanceof DateTime) {
											$val = $dt -> toString();
										}
									} elseif (strpos($val, '-')) { // date avec '-'
										// enlever le temps ?
										$ip = strpos($val, ' ');
										if ($ip !== false) {
											$val = substr($val, 0, $ip);
										}
										$dt = explode('-',$val);
										if (sizeof($dt) > 1 and strlen($dt[1]) === 3) {
											$dt[1] = $this -> moistrad[strtolower($dt[1])];
										}
										if (sizeof($dt) > 2 and strlen($dt[2]) === 2) {
											if ($dt[2] <= '25') { // cas 1900 ou 2000
												$dt[2] = '20' . $dt[2];
											} else {
												$dt[2] = '19' . $dt[2];
											}
										}
										$val = implode('/', $dt);
									}
								}
								// test si propriété locale temporaire
								if ($champ[2]) {
									// cas des dates en format spécial
									$doc -> setValeur($champ[0], $val);
								} else {
									$doc -> proprietes[$champ[0]] = $val;
								}
							}
						}
					}
				}
				if($pFiltre === '' or $doc instanceof SG_Erreur) {					
					$ret -> elements[] = $doc;
				} elseif ($pFiltre instanceof SG_Formule) {
					$ok = $pFiltre -> calculerSur($doc);
					if($ok -> estVrai() === true) {
						$ret -> elements[] = $doc;
					}
				} else {
					$ret -> elements[] = $pFiltre;
				}
			}
		}
		return $ret;
	}

	/**
	 * N° de la dernière ligne de la feuille active
	 * 
	 * @since 1.3.0
	 * @version 2.0 test fichier
	 * @return SG_Nombre n° dernière ligne
	 */
	public function NoDerniereLigne () {
		if (getTypeSG($this -> fichier) === '@Erreur') {
			$ret = new SG_Nombre(0);
		} else {
			$ret = new SG_Nombre($this -> Feuille() -> getHighestRow());
		}
		return $ret;
	}

	/**
	 * N° de la dernière colonne de la feuille active
	 * @since 1.3.0
	 * @return SG_Nombre n° dernière colonne
	 */
	public function NoDerniereColonne () {
		$ret = new SG_Nombre($this -> Feuille() -> getHighestColumn());
		return $ret;
	}

	/**
	 * Ajouter des lignes après la dernière ligne de la page active
	 * 
	 * @since 1.3.1
	 * @param SG_Collection $pCollection collection source
	 * @param SG_Formule arg liste des formules de donnée à ajouter provenant de chaque élément de la collection
	 * @return SG_Tableur la feuille de calcul modifiée non enregistrée
	 */
	function Ajouter ($pCollection = null) {
		if(getTypeSG($pCollection) === '@Formule') {
			$collection = $pCollection -> calculer();
		} else {
			$collection = $pCollection;
		}
		if (getTypeSG($collection) === '@Collection') {
			$noLigne = $this -> NoDerniereLigne() -> toInteger();
			$feuille = $this -> Feuille();
			foreach($collection -> elements as $element) {
				$noCol = 'A';
				for($i = 1; $i < func_num_args(); $i++) {
					$value = func_get_arg($i) -> calculerSur($element);
					$type = getTypeSG($value);
					$feuille -> setCellValue($noCol . $noLigne, self::getSimpleValue($value));
					if ($type === '@DateHeure') {
						$feuille -> getStyleByColumnAndRow($noCol, $noLigne) -> getNumberFormat() -> setFormatCode('dd/mm/yyyy h:mm');
					} elseif ($type === '@Date') {
						$feuille -> getStyleByColumnAndRow($noCol, $noLigne) -> getNumberFormat() -> setFormatCode('dd/mm/yyyy');
					} elseif ($type === '@DateHeure') {
						$feuille -> getStyleByColumnAndRow($noCol, $noLigne) -> getNumberFormat() -> setFormatCode('h:mm');
					}
					$noCol++;
				}
				$noLigne++;
			}
		}
		return $this;
	}

	/**
	 * Enregistrer le fichier
	 * 
	 * @since 1.3.1
	 * @param string|SG_Texte|SG_Formule $pCheminComplet chemin à partir de /synergaia/ (exemple tmp/test.xls)
	 * @param string|SG_Texte|SG_Formule $pFormat (defaut "Excel2007")
	 * @return SG_Tableur $this
	 */
	function Enregistrer($pCheminComplet = '', $pFormat = 'Excel2007') {
		$format = SG_Texte::getTexte($pFormat);
		$chemin = SG_Texte::getTexte($pCheminComplet);
		$writer = PHPExcel_IOFactory::createWriter($this -> fichier, $format);
		$ret = $writer -> save($chemin);
		return $this;
	}

	/**
	 * Retourne une valeur simple PHP
	 * 
	 * @since 1.3.1
	 * @param SG_Objet $pObjet objet SynerGaia
	 * @return string|numeric|datetime
	 */
	static function getSimpleValue($pObjet = null) {
		$ret = '';
		if (is_string($pObjet) or is_numeric($pObjet)) {
			$ret = $pObjet;
		} else {
			switch (getTypeSG($pObjet)) {
				case '@Nombre':
					$ret = $pObjet -> valeur;
					break;
				case '@Date':
				case '@DateHeure':
				case '@Heure':
					$date = DateTime::createFromFormat('d/m/Y H:i:s', $pObjet -> toString());
					$ret = PHPExcel_Shared_Date::PHPToExcel($date);
					$ret = $pObjet -> toString();
					break;
				default:
					$ret = $pObjet -> toString();
			}
		}
		return $ret;
	}

	/**
	 * Calculer le code HTML pour l'affichage
	 * 
	 * @since 1.3.1
	 */
	function Afficher() {
		$ret = '<div class="tableur"><table>';
		// pour chaque rangée...
		$feuille = $this -> Feuille();
		$ilignefin = $feuille -> getHighestRow();
		$icolmax = $feuille -> getHighestColumn();
		$icolmax++;
		for($iligne = 0; $iligne <= $ilignefin; $iligne++) {
			$ret.= '<tr class="tableur_ligne">';
			// pour chaque colonne...
			for($icol = 'A'; $icol !== $icolmax; $icol++) {
				$cell = $this -> Cellule($iligne, $icol);
				$ret.= '<td>' . $cell -> toHTML() . '</td>';
			}
			$ret.='</tr>';
		}
		$ret.='</table></div>';
		return new SG_HTML($ret);
	}

	/**
	 * Met à jour le format des dates sur le tableur
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pFormat format (avec d, m, y, h, i, s comme pour createFromFormat de DateTime)
	 * @return SG_Tableur|SG_Erreur le tableur ou une erreur
	 */
	function FormatDate($pFormat = null) {
		$ret = $this;
		if ($pFormat !== null) {
			$format = SG_Texte::getTexte($pFormat);
			$this -> formatDate = $format;
		}
		return $ret;
	}
}
