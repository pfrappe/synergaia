<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.0 (see AUTHORS file)
* SG_Tableur : Classe SynerGaia de gestion des tableaur via PHPExcel
*/
class SG_Tableur extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Tableur';
	public $typeSG = self::TYPESG;
	
	// Objet PHPExcel pour les manipulations
	public $fichier;
	
	// feuille active (objet PHPExcel)
	public $feuilleactive;
	// ligne active (objet PHPExcel)
	public $ligneactive;
	// colonne active (objet PHPExcel)
	public $colonneactive;

	/** 1.3.0 ajout
	* Construction de l'objet
	*
	* @param indéfini $pSource emplacement de la source (url ou fichier)
	*/
	function __construct($pSource = '') {
		$fichier = SG_Texte::getTexte($pSource);
		if ($fichier !== '') {
			try {
				$this -> fichier = PHPExcel_IOFactory::load($fichier);
			} catch(Exception $e) {
				$this -> fichier = new SG_Erreur($e -> getMessage());
			}
		} else {
			$this -> fichier = new PHPExcel();
		}
	}
	/** 1.3.0 ajout ; 2.0 test fichier
	* Sélection une feuille
	* @param (any) si string : par nom, si numérique pas n°
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
	/** 1.3.0 ajout
	* Sélection d'une cellule de feuille
	* @param $indexLigne (numeric) indice de ligne (de 1 à maxLigne)
	* @param $indexColonne (numeric) indice de ligne (de 1 à maxColonne)
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
	/** 1.3.0 ajout ; 2.0 test $feuille
	* parcourt une ligne et transfère une cellule dans chaque champ du document
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
	/** (1.3.0) Importer ; 2.0 getTexte, test fichier, test pas clé, variables document temporaires
	* Importer une feuille de tableur dans des documents
	* Chaque ligne représente les champs d'un document du type passé en paramètre.
	* Rien n'est fait si aucun champ n'est passé ou les paramètres sont en erreur.
	* La fonction .@Enregistrer n'est pas exécutée.
	* @param $pTypeObjet = '' : le type d'objet de la collection 
	* @param $pChampCle = '' : le nom du champ clé (devientdra @Code)
	* @param $pColIndex = 'A' : la colonne où se trouve la valeur du champ clé
	* @param $pChamps = null : la suite des champs (soit "champ1, champ2" si tout est pris, soit "A|champ1, C|champ2, Z|champs3" si sélection
	* @param $pDebut = 1 : n0 de la première ligne (à partir de 1)
	* @param $pFin = 0 : n° de la dernière ligne (0 si jusqu'à la fin)
	* @param $pFiltre = '' : filtre exécuté sur le document avant de le garder dans la collection
	* @return (SG_Collection) : la collection de documents
	*/
	public function Importer($pTypeObjet = '', $pChampCle = '', $pColIndex = 'A', $pChamps = '', $pDebut = 1, $pFin = 0, $pFiltre = '') {
		if (getTypeSG($this -> fichier) === '@Erreur') {
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
					$ret = new SG_Erreur('la liste de champs n\' pas interprétable');
				}
			}
		}
		if (getTypeSG($ret) !== '@Erreur') {
			// boucle sur les lignes
			$lignes = $this -> Lignes($pDebut,$pFin) -> elements;
			$iligne = 1;
			$formule = new SG_Formule();
			foreach($lignes as $key => $ligne) {
				if ($champCle === '' or $cle === '') {
					$doc = SG_Rien::Nouveau($typeObjet);
				} else {
					$cle = SG_Texte::getTexte($ligne -> elements[$index]);
					$formule -> setFormule( '.' . $champCle . '.@Egale("'. $cle . '")');
					$collec = SG_Rien::Chercher($pTypeObjet, '', $formule);
					if(getTypeSG($collec) === '@Collection') {
						if(sizeof($collec -> elements) == 0) {
							$doc = SG_Rien::Nouveau($typeObjet);
						} else {
							$doc = $collec -> Premier();
						}
					} else {
						$doc = new SG_Erreur('0114', $typeObjet . ' : ' . $cle);
					}
				}
				$doc -> proprietes['noligne'] = new SG_Nombre($key);
				if (getTypeSG($doc) !== '@Erreur') {
					$icol = 'A';
					foreach($champs as $i => $champ) {
						if ($champ !== '') {
							$c = explode('|', $champ);
							if (sizeof($c) > 1) {
								$champ = $c[1];
								$icol = $c[0];
							}
							if (isset($ligne -> elements[$icol])) {
								// test si propriété locale temporaire
								if (SG_Dictionnaire::isProprieteExiste(getTypeSG($doc), $champ)) {
									$doc -> setValeur($champ, $ligne -> elements[$icol]);
								} else {
									$doc -> proprietes[$champ] = $ligne -> elements[$icol];
								}
							}
						}
						$icol++;
					}
				}
				if($pFiltre === '' or getTypeSG($doc) !== '@Erreur') {					
					$ret -> elements[] = $doc;
				} else {
					$ok = $pFiltre -> calculerSur($doc);
					if($ok -> estVrai() === true) {
						$ret -> elements[] = $doc;
					}
				}
			}
		}
		return $ret;
	}
	/** (1.3.0) NoDerniereLigne ; 2.0 test fichier
	* N° de la dernière ligne de la feuille active
	* @return (@Nombre) n° dernière ligne
	*/
	public function NoDerniereLigne () {
		if (getTypeSG($this -> fichier) === '@Erreur') {
			$ret = new SG_Nombre(0);
		} else {
			$ret = new SG_Nombre($this -> Feuille() -> getHighestRow());
		}
		return $ret;
	}
	/** (1.3.0) NoDerniereColonne
	* N° de la dernière colonne de la feuille active
	* @return (@Nombre) n° dernière ligne
	*/
	public function NoDerniereColonne () {
		$ret = new SG_Nombre($this -> Feuille() -> getHighestColumn());
		return $ret;
	}
	/*** (1.3.1) Ajouter
	* Ajouter des lignes après la dernière ligne de la page active
	* @param (@Collection) collection source
	* @param (any) liste des formules de donnée à ajouter provenant de chaque élément de la collection
	* @return (@Tableur) la feuille de calcul modifiée non enregistrée
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
	/** (1.3.1) Enregistrer
	* Enregistrer le fichier
	* @param (@Texte) $pCheminComplet chemin à partir de /synergaia/ (exemple tmp/test.xls)
	* @param (@Texte) $pFormat (defaut "Excel2007")
	* @return (@Tableur) $this
	**/
	function Enregistrer($pCheminComplet = '', $pFormat = 'Excel2007') {
		$format = SG_Texte::getTexte($pFormat);
		$chemin = SG_Texte::getTexte($pCheminComplet);
		$writer = PHPExcel_IOFactory::createWriter($this -> fichier, $format);
		$ret = $writer -> save($chemin);
		return $this;
	}
	/** (1.3.1) getSimpleValue
	* Retourne une valeur simple PHP
	* @param (any) objet SynerGaia
	* @return (string, numeric, datetime)
	**/
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
	/** (1.3.1) Afficher
	* Afficher le tableau en HTML
	**/
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
}
