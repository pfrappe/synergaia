<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Matrice */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Matrice : Classe de matrice de nombres
 * @since 1.3.2
 * @todo terminer la classe ; mettre des objets SynerGaïa partout
 */
class SG_Matrice extends SG_Objet {
	/** string Type SynerGaia '@Matrice' */
	const TYPESG = '@Matrice';

	/** string Type SynerGaïa */
	public $typeSG = self::TYPESG;

	/** array Tableau des valeurs internes */
	public $matrice = array();

	/** array liste des clés utiliser si la matrice est carrée avec des clés associatives */
	public $cles;
	
	/**
	 * Construction initiale de la @Matrice
	 * @since 1.3.2 ajout
	 * @param array $pTableau des valeurs
	 */
	public function __construct($pTableau) {
		$this -> matrice = array();
		$n = 0;
		foreach($pTableau as $keyl => $ligne) {
			if (!isset($this -> cles[$keyl])) {
				$nl = $this -> cles[$keyl] = $n;
				$n++;
			} else {
				$nl = $this -> cles[$keyl];
			}
			foreach($ligne as $keyc => $valeur) {
				if (!isset($this -> cles[$keyc])) {
					$nc = $this -> cles[$keyc] = $n;
					$n++;
				} else {
					$nc = $this -> cles[$keyc];
				}
				$this -> matrice[$nl][$nc] = $valeur;
			}
		}
	}

	/** 
	 * Calcule la somme des lignes 
	 * @since 1.3.2
	 * @return array vecteur de la somme des lignes
	 */
	function SommeLignes() {
		$ret = array();
		foreach($this -> matrice as $key => $ligne) {
			$somme = 0;
			foreach($ligne as $valeur) {
				$somme+= $valeur;
			}
			$ret[$key] = $somme;
		}
		return $ret;
	}

	/**
	 * Calcule la somme des colonnes
	 * @since 1.3.2
	 * @return array vecteur de la somme des colonnes
	 */
	function SommeColonnes() {
		$ret = array();
		foreach($this -> matrice as $ligne) {
			foreach($ligne as $key => $valeur) {
				if(!isset($ret[$key])) {
					$ret[$key] = $valeur;
				} else {
					$ret[$key]+= $valeur;
				}
			}
		}
		return $ret;
	}

	/**
	 * Totalise les éléments de la matrice
	 * @since 1.3.2
	 * @return array
	 */
	function SommeLignesColonnes() {
		$ret = $this -> SommeLignes();
		// ajouter les colonnes
		$colonnes = $this -> SommeColonnes();
		foreach($colonnes as $key => $valeur) {
			if(!isset($ret[$key])) {
				$ret[$key] = $valeur;
			} else {
				$ret[$key]+= $valeur;
			}
		}
		return $ret;
	}

	/**
	 * interversion des lignes et des colonnes
	 * @since 1.3.2
	 * @param string $pCle1
	 * @param string $pCle2
	 * @param boolean $pSurPlace
	 * @return SG_Matrice $this
	 */
	function Permuter($pCle1 = '', $pCle2 = '', $pSurPlace = true) {
		$newmatrice = array();
		$cle1 = $this -> cles[$pCle1];
		$cle2 = $this -> cles[$pCle2];
		// échange des 2 lignes
		if(isset($this->matrice[$cle1]) and isset($this->matrice[$cle2])) {
			$tmp = $this->matrice[$cle1];
			$this->matrice[$cle1] = $this->matrice[$cle2];
			$this->matrice[$cle2] = $tmp;
		} elseif (!isset($this->matrice[$cle1])) {
			$this->matrice[$cle1] = $this->matrice[$cle2];
			unset($this->matrice[$cle2]);
		} elseif (!isset($this->matrice[$cle2])) {
			$this->matrice[$cle2] = $this->matrice[$cle1];
			unset($this->matrice[$cle1]);
		}
		// échange des 2 colonnes
		foreach($this -> cles as $cle) {
			if(isset($this->matrice[$cle1][$cle2]) and isset($this->matrice[$cle2][$cle1])) {
				$tmp = $this->matrice[$cle1][$cle2];
				$this->matrice[$cle1][$cle2] = $this->matrice[$cle2][$cle1];
				$this->matrice[$cle2][$cle1] = $tmp;
			} elseif (!isset($this->matrice[$cle1][$cle2])) {
				$this->matrice[$cle1][$cle2] = $this->matrice[$cle2][$cle1];
				unset($this->matrice[$cle2][$cle1]);
			} elseif (!isset($this->matrice[$cle2][$cle1])) {
				$this->matrice[$cle2][$cle1] = $this->matrice[$cle1][$cle2];
				unset($this->matrice[$cle1][$cle2]);
			}
		}
		// échange des clés
		if(isset($this->cles[$pCle1]) and isset($this->cles[$pCle2])) {
			$tmp = $this->cles[$pCle1];
			$this->cles[$pCle1] = $this->cles[$pCle2];
			$this->cles[$pCle2] = $tmp;
		} elseif (!isset($this->cles[$pCle1])) {
			$this->cles[$pCle1] = $this->cles[$pCle2];
			unset($this->matrice[$pCle2]);
		} elseif (!isset($this->cles[$pCle2])) {
			$this->cles[$pCle2] = $this->cles[$pCle1];
			unset($this->cles[$pCle1]);
		}
		$ret = $this;
	}

	/**
	 * Trier les lignes, les colonnes et les clés selon un vecteur de tri
	 * @since 1.3.2 ajout
	 * @param array $pVecteur ordre des clés à prendre en compte (si $pVecteur ne reprend pas toutes les clés, c'est un extrait de la matrice)
	 * @param 0boolean $pSurPlace
	 * @return SG_Matrice $this
	 */
	function Trier($pVecteur ='', $pSurPlace = true) {
		$newmatrice = array();
		// tri des lignes et des colonnes
		for ($nl = 0; $nl<sizeof($pVecteur); $nl++) {
			$oldnl = $pVecteur[$nl];
			if (isset($this -> matrice[$oldnl])) {
				for ($nc = 0; $nc < sizeof($pVecteur); $nc++) {
					if (isset($this -> matrice[$oldnl][$pVecteur[$nc]])) {
						$newmatrice[$nl][$nc] = $this -> matrice[$oldnl][$pVecteur[$nc]];
					}
				}
			}
		}
		// tri des clés
		$newcles = array();
		for($n = 0; $n < sizeof($pVecteur); $n++) {
			$newcles[$n] = $this -> cles[$pVecteur[$n]];
		}
		$this -> cles = $newcles;
		$this -> matrice = $newmatrice;
		$ret = $this;
	}

	/**
	 * clés dans l'ordre du + grd nb de précédents
	 * @since 1.3.2
	 */
	function PlusPopulaires () {
		$ret = $this -> SommeColonnes();
		arsort($ret);
		return $ret;
	}

	/** 1.3.2 ajout
	 * clés dans l'ordre du + grd nb de suivants
	 * @since 1.3.2
	 */
	function PlusDiscriminants () {
		$ret = $this -> SommeLignes();
		arsort($ret);
		return $ret;
	}
}
?>
