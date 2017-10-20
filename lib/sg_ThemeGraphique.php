<?php
/** SYNERGAIA fichier pour le traitement de l'objet @ThemeGraphique */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * Classe SynerGaia de gestion d'un theme graphique
 * Cette classe est statique
 * @since 1.1
 */
class SG_ThemeGraphique {
	/** string Type SynerGaia '@ThemeGraphique' */
	const TYPESG = '@ThemeGraphique';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/**
	 * Initialise un thème graphique
	 * @since 1.1 prise en charge changement de thème
	 * @param string $type 
	 */	
	static function initThemeGraphique($type = '') {
		$actuel = self::ThemeGraphique();
		if ($type === 'm') {
			self::ThemeGraphique('mobile');
		} elseif ($type === 'd') {
			self::ThemeGraphique('defaut');
		} elseif ($type === 't') {
			self::ThemeGraphique('defaut01');
		} else {
			if (!isset($_SESSION['page']['themegraphique'])) {
				if (!get_cfg_var('browscap')) {
					journaliser ('SG_ThemeGraphique.initThemeGraphique : Browscap non configure !');
					$browser = array();
				} else {
					$browser = get_browser(null, true);
				}
				SG_Config::setConfig('SynerGaia_theme', 'defaut');
				self::ThemeGraphique('defaut');
			}
		}
		// prise en compte d'un changement
		if ($actuel !== self::ThemeGraphique())  {
			SG_Cache::viderCacheNavigation();
		}
	}

	/**
	 * Détermine le code du thème graphique en cours, à partir de la config
	 * @param string|SG_ThemeGraphique $pThemeGraphique éventuellement un thème graphique dont on veut le code 
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
	 * @return string 
	 */
	static function dataTheme() {
		return 'data-theme="c"';
	}
}
?>
