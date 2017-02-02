<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'un theme graphique
* Cette classe est statique
*/
class SG_ThemeGraphique {
	// Type SynerGaia
	const TYPESG = '@ThemeGraphique';
	public $typeSG = self::TYPESG;

	/** 1.1 prise ne changement changement de thème
	*/	
	static function initThemeGraphique($type = '') {
		$actuel = SG_ThemeGraphique::ThemeGraphique();
		if ($type === 'm') {
			SG_ThemeGraphique::ThemeGraphique('mobile');
		} elseif ($type === 'd') {
			SG_ThemeGraphique::ThemeGraphique('defaut');
		} else {
			if (!isset($_SESSION['page']['themegraphique'])) {
				if (!get_cfg_var('browscap')) {
					journaliser ('SG_ThemeGraphique.initThemeGraphique : Browscap non configure !');
					$browser = array();
				} else {
					$browser = get_browser(null, true);
				}
				SG_Config::setConfig('SynerGaia_theme', 'defaut');
				SG_ThemeGraphique::ThemeGraphique('defaut');
			}
		}
		// prise en compte d'un changement
		if ($actuel !== SG_ThemeGraphique::ThemeGraphique())  {
			SG_Cache::viderCacheNavigation();
		}
	}

	/**
	* Détermine le code du thème graphique en cours, à partir de la config
	* @param (string ou SG_ThemeGraphique) éventuellement un thème graphique dont on veut le code
	* @return string code du thème
	*/
	static function ThemeGraphique($pThemeGraphique = null) {
		if ($pThemeGraphique !== null and $pThemeGraphique !== '') {
			$_SESSION['page']['themegraphique'] = $pThemeGraphique;
		}
		if (!isset($_SESSION['page']['themegraphique'])) {
			$_SESSION['page']['themegraphique'] = SG_Config::getConfig('SynerGaia_theme', 'defaut');
		}
		return $_SESSION['page']['themegraphique'];
	}

	/**
	* data-theme pour jQuery
	*/
	static function dataTheme() {
		return 'data-theme="c"';
	}
}
?>
