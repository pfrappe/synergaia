<?php
/** fichier contenant les classes de gestion des éléments booléens @VraiFaux */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_VraiFaux_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_VraiFaux_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * par défaut trait vide */
	trait SG_VraiFaux_trait{};
}

/**
 * Classe SynerGaia de traitement des booléens
 * Chaque booléen peut avoir un des 3 états possibles :
 * vrai, faux ou indéfini (par exemple dans le cas d'une erreur de calcul) (vrai, faux et indéfini)
 * @since 1.0
 * @version 2.4
 */
class SG_VraiFaux extends SG_Objet {
	/** string Type SynerGaia '@VraiFaux' */
	const TYPESG = '@VraiFaux';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** string Valeur pour "VRAI" */
	const VRAIFAUX_VRAI = 1;
	/** string Valeur pour "FAUX" */
	const VRAIFAUX_FAUX = -1;
	/** string Valeur pour "INDEFINI" */
	const VRAIFAUX_INDEF = 0;

	/** string Texte pour "VRAI" */
	const VRAIFAUX_TEXTE_VRAI = 'oui';
	/** string Texte pour "FAUX" */
	const VRAIFAUX_TEXTE_FAUX = 'non';
	/** string Texte pour "INDEFINI" */
	const VRAIFAUX_TEXTE_INDEF = 'indéfini';

	/** integer Valeur interne de l'objet (1 vrai, 0 indéfini, -1 faux)*/
	public $valeur = 0;

	/**
	 * Construction de l'objet
	 * @version 1.3.2 si $pQuelqueChose type @Document : définit l'existence physique du document, @Collection = non vide
	 * @param any $pQuelqueChose valeur à partir de laquelle le SG_VraiFaux est créé
	 * @level 0 sauf si les paramètres sont des objets
	 */
	function __construct($pQuelqueChose = null) {
		$tmpTypeSG = getTypeSG($pQuelqueChose);
		switch ($tmpTypeSG) {
			case '@VraiFaux' :
				$this -> valeur = $pQuelqueChose -> valeur;
				break;
			case 'boolean' :
				$r = $this -> setBooleen($pQuelqueChose);
				break;
			case 'string' :
				if ($pQuelqueChose === '' . self::VRAIFAUX_VRAI) {
					$this -> valeur = self::VRAIFAUX_VRAI;
				} else {
					if ($pQuelqueChose === '' . self::VRAIFAUX_FAUX) {
						$this -> valeur = self::VRAIFAUX_FAUX;
					} else {
						if ($pQuelqueChose === '' . self::VRAIFAUX_TEXTE_VRAI) {
							$this -> valeur = self::VRAIFAUX_VRAI;
						} else {
							if ($pQuelqueChose === '' . self::VRAIFAUX_TEXTE_FAUX) {
								$this -> valeur = self::VRAIFAUX_FAUX;
							} else {
								$this -> valeur = self::VRAIFAUX_INDEF;
							}
						}
					}
				}
				break;
			case 'integer' :
				if ($pQuelqueChose === self::VRAIFAUX_VRAI) {
					$this -> valeur = self::VRAIFAUX_VRAI;
				} else {
					if ($pQuelqueChose === self::VRAIFAUX_FAUX) {
						$this -> valeur = self::VRAIFAUX_FAUX;
					} else {
						$this -> valeur = self::VRAIFAUX_INDEF;
					}
				}
				break;
			case '@Formule' :
				$v = $pQuelqueChose -> calculer();
				$type = getTypeSG($v);
				// si objet rien ou document = test existence
				if ($type === '@Rien') {
					$this -> valeur = self::VRAIFAUX_FAUX;
				} elseif ($type === '@VraiFaux') {
					$this -> valeur = $v -> valeur;
				} elseif ($type === '@Collection') {
					$this -> valeur = $v -> EstVide() -> Non() -> valeur;
				} elseif (SG_Dictionnaire::isObjetDocument($v)) {
					if ($v -> Existe() -> estVrai()) {
						$this -> valeur = self::VRAIFAUX_VRAI;
					} else {
						$this -> valeur = self::VRAIFAUX_FAUX;
					}
				} elseif($type === '@Fichier') {
					if (isset($v->proprietes[$v -> reference]['data'])) {
						$this -> valeur = self::VRAIFAUX_VRAI;
					} else {
						$this -> valeur = self::VRAIFAUX_FAUX;
					}
				} else {
					$tmpVraiFaux = new SG_VraiFaux($v);
					$this -> valeur = $tmpVraiFaux -> valeur;
				}
				break;
			default :
				if (substr($tmpTypeSG, 0, 1) === "@") {
					// Si objet SynerGaia
					$tmpVraiFaux = new SG_VraiFaux($pQuelqueChose -> toString());
					$this -> valeur = $tmpVraiFaux -> valeur;
				} else {
					$this -> valeur = self::VRAIFAUX_INDEF;
				}
		}
	}

	/**
	 * Conversion en chaine de caractères
	 * @return string "vrai", "faux" ou "indéfini"
	 */
	function toString() {
		$texte = '';
		switch($this->valeur) {
			case self::VRAIFAUX_VRAI :
				$texte = self::VRAIFAUX_TEXTE_VRAI;
				break;
			case self::VRAIFAUX_FAUX :
				$texte = self::VRAIFAUX_TEXTE_FAUX;
				break;
			default :
				$texte = self::VRAIFAUX_TEXTE_INDEF;
		}
		return $texte;
	}

	/**
	 * Conversion en code HTML
	 * @version 2.1.1
	 * @return string code HTML
	 */
	function toHTML() {
		return new SG_HTML($this -> toString());
	}

	/**
	 * Affichage
	 * 
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_VraiFaux">' . $this -> toHTML() -> texte . '</span>';
	}

	/**
	 * Modification
	 *
	 * @param string $pRefChamp référence du champ HTML
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '') {

		$ret = '';
		$ret .= '<select class="champ_VraiFaux" name="' . $pRefChamp . '">';

		$ret .= '<option value="' . SG_VraiFaux::VRAIFAUX_INDEF . '"';
		if ($this -> valeur === SG_VraiFaux::VRAIFAUX_INDEF) {
			$ret .= ' selected="selected"';
		}
		$ret .= '>' . SG_VraiFaux::VRAIFAUX_TEXTE_INDEF . '</option>';

		$ret .= '<option value="' . SG_VraiFaux::VRAIFAUX_VRAI . '"';
		if ($this -> valeur === SG_VraiFaux::VRAIFAUX_VRAI) {
			$ret .= ' selected="selected"';
		}
		$ret .= '>' . SG_VraiFaux::VRAIFAUX_TEXTE_VRAI . '</option>';

		$ret .= '<option value="' . SG_VraiFaux::VRAIFAUX_FAUX . '"';
		if ($this -> valeur === SG_VraiFaux::VRAIFAUX_FAUX) {
			$ret .= ' selected="selected"';
		}
		$ret .= '>' . SG_VraiFaux::VRAIFAUX_TEXTE_FAUX . '</option>';

		$ret .= '</select>';

		return $ret;
	}

	/**
	 * Traduction en SG_Texte (selon les constantes VRAIFAUX_TEXTE_??? ou les paramètres)
	 * @version 1.3.1 paramètres
	 * @param SG_Texte $pVrai libellé si vrai
	 * @param SG_Texte $pFaux libellé si faux
	 * @param SG_Texte $pIndefini libellé si indéfini
	 * @return SG_Texte "vrai", "faux" ou "indéfini"
	 */
	function EnTexte($pVrai = self::VRAIFAUX_TEXTE_VRAI, $pFaux = self::VRAIFAUX_TEXTE_FAUX, $pIndefini = self::VRAIFAUX_TEXTE_INDEF) {
		if (func_num_args() > 0) {
			$vrai = SG_Texte::getTExte($pVrai);
			$faux = SG_Texte::getTExte($pFaux);
			$indefini = SG_Texte::getTExte($pIndefini);
		}
		$texte = '';
		switch($this->valeur) {
			case self::VRAIFAUX_VRAI :
				$texte = $vrai;
				break;
			case self::VRAIFAUX_FAUX :
				$texte = $faux;
				break;
			default :
				$texte = $indefini;
		}
		$ret = new SG_Texte($texte);
		return $ret;
	}

	/**
	 * Conversion en booléen (indéfini est traduit en false sauf paramètre)
	 * @version 2.6 paramètre
	 * @param boolean $pIndefini valeur si indéfini (par défaut false)
	 * @return boolean
	 */
	function estVrai($pIndefini = false) {
		$ret = false;
		switch($this->valeur) {
			case self::VRAIFAUX_VRAI :
				$ret = true;
				break;
			case self::VRAIFAUX_FAUX :
				$ret = false;
				break;
			default :
				$ret = $pIndefini;
		}
		return $ret;
	}

	/**
	 * Verifie si la valeur est définie ou non
	 *
	 * @return SG_VraiFaux
	 */
	function EstDefini() {
		$retBool = true;
		if ($this -> valeur === self::VRAIFAUX_INDEF) {
			$retBool = false;
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/**
	 * Teste si la valeur est vide (non définie)
	 *
	 * @return SG_VraiFaux
	 */
	function EstVide() {
		$ret = $this -> EstDefini() -> Non();
		return $ret;
	}

	/**
	 * Inversion du SG_VraiFaux
	 * 
	 * @version 2.4 parm
	 * @param boolean|SG_VraiFaux|SG_Formule $pSiIndefini : retourne la valeur à donner si indéfini
	 * @return SG_VraiFaux
	 */
	function Non($pSiIndefini = null) {
		switch($this -> valeur) {
			case self::VRAIFAUX_VRAI :
				$ret = new SG_VraiFaux(false);
				break;
			case self::VRAIFAUX_FAUX :
				$ret = new SG_VraiFaux(true);
				break;
			default :
				if ($pSiIndefini !== null) {
					if (getTypeSG($pSiIndefini) === '@Formule') {
						$ret = $pSiIndefini -> calculer();
					} else {
						$ret = $pSiIndefini;
					}
				} else {
					$ret = new SG_VraiFaux();
				}
				break;
		}
		return $ret;
	}

	/**
	 * Ou logique
	 * @since 1.1
	 * @param boolean|SG_VraiFaux|SG_Formule $pQuelqueChose valeur avec laquelle effectuer le "ou"
	 *
	 * @return SG_VraiFaux
	 */
	function Ou($pQuelqueChose) {
		if ($this->valeur === self::VRAIFAUX_VRAI) {
			$ret = new SG_VraiFaux(true);
		} else {
			$tmpVal = new SG_VraiFaux($pQuelqueChose);
			if ($this->valeur === self::VRAIFAUX_FAUX) {
				switch($tmpVal->valeur) {
					case self::VRAIFAUX_VRAI :
						// FAUX + VRAI => VRAI
						$ret = new SG_VraiFaux(true);
						break;
					case self::VRAIFAUX_FAUX :
						// FAUX + FAUX => FAUX
						$ret = new SG_VraiFaux(false);
						break;
					default :
						// FAUX + INDF => INDF
						$ret = new SG_VraiFaux();
						break;
				}
			} else {
				switch($tmpVal->valeur) {
					case self::VRAIFAUX_VRAI :
						// INDF + VRAI => VRAI
						$ret = new SG_VraiFaux(true);
						break;
					case self::VRAIFAUX_FAUX :
						// INDF + FAUX => INDF
						$ret = new SG_VraiFaux();
						break;
					default :
						// INDF + INDF => INDF
						$ret = new SG_VraiFaux();
						break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Et logique ($pQuelquechose n'est calculé que si nécessaire)
	 * @since 1.1
	 * @param boolean|SG_VraiFaux|SG_Formule $pQuelqueChose valeur avec laquelle effectuer le "et"
	 *
	 * @return SG_VraiFaux
	 */
	function Et($pQuelqueChose) {
		if ($this->valeur === self::VRAIFAUX_FAUX) {
			$ret = new SG_VraiFaux(false);
		} else {
			$tmpVal = new SG_VraiFaux($pQuelqueChose);
			if ($this->valeur === self::VRAIFAUX_VRAI) {
				switch($tmpVal->valeur) {
					case self::VRAIFAUX_VRAI :
						// VRAI . VRAI => VRAI
						$ret = new SG_VraiFaux(true);
						break;
					case self::VRAIFAUX_FAUX :
						// VRAI . FAUX => FAUX
						$ret = new SG_VraiFaux(false);
						break;
					default :
						// VRAI . INDF => INDF
						$ret = new SG_VraiFaux();
						break;
				}
			 } else {
				switch($tmpVal->valeur) {
					case self::VRAIFAUX_VRAI :
						// INDF . VRAI => INDF
						$ret = new SG_VraiFaux();
						break;
					case self::VRAIFAUX_FAUX :
						// INDF . FAUX => FAUX
						$ret = new SG_VraiFaux(false);
						break;
					default :
						$ret = new SG_VraiFaux();
						// INDF . INDF => INDF
						break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Attribue un nom d'icône en fonction de la valeur
	 * @since 1.1 ajout
	 * @param string $pVrai = accept.png
	 * @param string $pFaux = cancel.png
	 * @param string $pIndef ''
	 * @return @Icone
	 */
	function Icone($pVrai = 'accept.png', $pFaux = 'cancel.png', $pIndef = '') {
		$icone = '';
		switch($this->valeur) {
			case self::VRAIFAUX_VRAI :
				$icone = new Icone($pVrai);
				break;
			case self::VRAIFAUX_FAUX :
				$icone =  new Icone($pFaux);
				break;
			default :
				$icone =  new Icone($pIndef);
		}
		return $icone;
	}

	/**
	 * Retourne la valeur booleénne d'une expression. Indéfini est retourné false
	 * @since 1.3.1 ajout
	 * @param any $pValeur valeur à tester
	 * @return (boolean) valeur boléenne
	 **/
	static function getBooleen ($pValeur = '') {
		$val = new SG_VraiFaux($pValeur);
		return $val -> estVrai();
	}

	/**
	 * met à jour la valeur selon un booleen vrai (sinon indefini)
	 * @since 1.3.2 ajout
	 * @param boolean $pBooleen valeur à mettre
	 **/
	function setBooleen($pBooleen = SG_VraiFaux::VRAIFAUX_INDEF) {
		if ($pBooleen === true) {
			$this -> valeur = SG_VraiFaux::VRAIFAUX_VRAI;
		} elseif ($pBooleen === false) {
			$this -> valeur = SG_VraiFaux::VRAIFAUX_FAUX;
		} else {
			$this -> valeur = SG_VraiFaux::VRAIFAUX_INDEF;
		}
	}

	// 2.3 complément de classe créée par compilation
	use SG_VraiFaux_trait;
}
?>
