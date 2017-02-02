<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de traitement des booléens
*
* Chaque booléen peut en réalité avoir un des 3 états possibles :
* vrai, faux ou indéfini (par exemple dans le cas d'une erreur de calcul)
*/
// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_VraiFaux_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_VraiFaux_trait.php';
} else {
	trait SG_VraiFaux_trait{};
}
class SG_VraiFaux extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@VraiFaux';
	public $typeSG = self::TYPESG;
	
	// Valeur pour "VRAI"
	const VRAIFAUX_VRAI = 1;
	/**
	 * Valeur pour "FAUX"
	 */
	const VRAIFAUX_FAUX = -1;
	/**
	 * Valeur pour "INDEFINI"
	 */
	const VRAIFAUX_INDEF = 0;

	/**
	 * Texte pour "VRAI"
	 */
	const VRAIFAUX_TEXTE_VRAI = 'oui';
	/**
	 * Texte pour "FAUX"
	 */
	const VRAIFAUX_TEXTE_FAUX = 'non';
	/**
	 * Texte pour "INDEFINI"
	 */
	const VRAIFAUX_TEXTE_INDEF = 'indéfini';

	/**
	 * Valeur interne de l'objet
	 */
	public $valeur = 0;

	/** 1.3.2 si $pQuelqueChose type @Document : définit l'existence physique du document, @Collection = non vide
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_VraiFaux est créé
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
					if (isset($v->proprietes[$v->reference]['data'])) {
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
	 *
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

	/** 2.1.1
	 * Conversion en code HTML
	 *
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
	 * @param $pRefChamp référence du champ HTML
	 *
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

	/** 1.3.1 paramètres
	 * Conversion en SG_Texte
	 * @param (SG_Texte) valeur si vrai (sinon 'vrai')
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
	 * Conversion en booléen
	 *
	 * @return boolean
	 */
	function estVrai() {
		$ret = false;
		switch($this->valeur) {
			case self::VRAIFAUX_VRAI :
				$ret = true;
				break;
			case self::VRAIFAUX_FAUX :
				$ret = false;
				break;
			default :
				$ret = false;
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
	 * @return SG_VraiFaux
	 */
	function Non() {
		switch($this->valeur) {
			case self::VRAIFAUX_VRAI :
				$ret = new SG_VraiFaux(false);
				break;
			case self::VRAIFAUX_FAUX :
				$ret = new SG_VraiFaux(true);
				break;
			default :
				$ret = new SG_VraiFaux();
				break;
		}
		return $ret;
	}

	/** 1.1
	 * Ou logique
	 *
	 * @param indéfini $pQuelqueChose valeur avec laquelle effectuer le "ou"
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

	/** 1.1
	 * Et logique ($pQuelquechose n'est calculé que si nécessaire)
	 *
	 * @param indéfini $pQuelqueChose valeur avec laquelle effectuer le "et"
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
	/** 1.1
	 */
	function Tracer($pMsg = '') {
		$msg = new SG_Texte($pMsg);
		echo '<b>' . $msg -> texte . ' : (' . $this -> typeSG . ') </b>' . $this -> toString();
		return $this;
	}
	/** 1.1 ajout
	* vrai = accept.png, faux = cancel.png
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
	/** 1.3.1 ajout
	/ Retourne la valeur booleénne d'une expression. Indéfini est retourné false
	* @param (any) valeur à tester
	* @return (boolean) valeur boléenne
	**/
	static function getBooleen ($pValeur = '') {
		$val = new SG_VraiFaux($pValeur);
		return $val -> estVrai();
	}
	/** 1.3.2 ajout
	* met à jour la valeur selon un booleen vrai (sinon indefini)
	* @param ($pBooleen) valeur à mettre
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
