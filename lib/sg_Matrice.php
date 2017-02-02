<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.2 (see AUTHORS file)
 * SG_Matrice : Classe de matrice de nombres
 */
class SG_Matrice extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Matrice';
	public $typeSG = self::TYPESG;
	
	public $matrice = array();
	// utiliser si la matrice est carrée avec des clés associatives
	public $cles;
	/** 1.3.2 ajout
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
	/** 1.3.2 
	/** 1.3.2 ajout
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
	/** 1.3.2 ajout
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
	/** 1.3.2 ajout
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
	/** 1.3.2 ajout
	* interversion des lignes et des colonnes
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
	/** 1.3.2 ajout
	* Trier les lignes, les colonnes et les clés selon un vecteur de tri
	* @param (array) $pVecteur ordre des clés à prendre en compte (si $pVecteur ne reprend pas toutes les clés, c'est un extrait de la matrice)
	* @param (boolean) $pSurPlace 
	**/
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
	/** 1.3.2 ajout
	* clés dans l'ordre du + grd nb de précédents
	**/
	function PlusPopulaires () {
		$ret = $this -> SommeColonnes();
		arsort($ret);
		return $ret;
	}
	/** 1.3.2 ajout
	* clés dans l'ordre du + grd nb de suivants
	**/
	function PlusDiscriminants () {
		$ret = $this -> SommeLignes();
		arsort($ret);
		return $ret;
	}
}
?>
