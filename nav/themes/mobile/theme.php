<?php
/**
 * SynerGaia
 *
 * @author SynerGaia team
 * @copyleft 2012-2014
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Définition des blocs de la page

// ENTETE : <head>
if (!isset($_SESSION['template']['Entete'])) {
	$_SESSION['template']['Entete'] = ' <title>SynerGaïa</title>' . PHP_EOL;
}

// Eventuels styles complémentaires
if (!isset($_SESSION['template']['StylesComplementaires'])) {
	$parametreStylesComplementaires = new SG_Parametre('StylesComplementaires');
	$stylesComplementaires = $parametreStylesComplementaires -> Lire() -> toString();
	$_SESSION['template']['StylesComplementaires'] = '';
	if ($stylesComplementaires !== '') {
		$_SESSION['template']['StylesComplementaires'] = '<style>' . PHP_EOL . $stylesComplementaires . PHP_EOL . '</style>' . PHP_EOL;
	}
}

// BANNIERE en haut de la page (rafraichit si dictionnaire doit être remis à jour)
$tmpUpdate = false;
if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === true) {
	$tmpUpdate = SG_Update::updateDictionnaireNecessaire();
}

if (!isset($_SESSION['template']['Banniere']) || $tmpUpdate === true) {
	$informationsUtilisateur = SG_Rien::Moi() -> toHTML();
	$informationsSynerGaia = ', version ' . $_SESSION['@SynerGaia']->Version();
	if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === true) {
		$informationsSynerGaia .= ', cache : ' . SG_Cache::getTypeCache();
	}
	$_SESSION['template']['Banniere'] = '   <ul>' . PHP_EOL;
	$_SESSION['template']['Banniere'] .= '    <li><abbr title="SynerGaïa' . $informationsSynerGaia . '">SynerGaïa</abbr></li>' . PHP_EOL;
	$_SESSION['template']['Banniere'] .= '    <li><abbr title="' . $informationsUtilisateur . '">' . $_SESSION['@SynerGaia']->IdentifiantConnexion() . '</abbr></li>' . PHP_EOL;
	$_SESSION['template']['Banniere'] .= '    <li><a href="' . SG_Navigation::URL_LOGOUT . '">Déconnexion</a></li>' . PHP_EOL;
	// Affiche la boite de saisie d'une formule si administrateur
	if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === true) {
		$_SESSION['template']['Banniere'] .= '    <li>' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '     <form method="get" action="">' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '      <fieldset>' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '       <input type="text" name="' . SG_Navigation::URL_VARIABLE_FORMULE . '" />' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '       <input type="submit" value="F"/>' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '      </fieldset>' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '     </form>' . PHP_EOL;
		$_SESSION['template']['Banniere'] .= '    </li>' . PHP_EOL;

		// Message pour l'administrateur
		// Le dictionnaire est-il à jour ?
		if (SG_Update::updateDictionnaireNecessaire() === true) {
			$operationUpdate = new SG_ModeleOperation('Update');
			$lienOperationUpdate = $operationUpdate -> LienPourNouvelleOperation();
			$_SESSION['template']['Banniere'] .= '    <li class="message">Une version plus récente du dictionnaire est disponible : ' . $lienOperationUpdate . '</li>' . PHP_EOL;
		}
	}
	$_SESSION['template']['Banniere'] .= '   </ul>' . PHP_EOL;
}

require 'template.php';
?>
