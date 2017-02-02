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
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name=”viewport” content=”width=device-width, initial-scale=1″>
		<title>SynerGaïa - Déconnexion</title>
		<link rel="stylesheet" type="text/css" title="Design" href="themes/<?php echo SG_ThemeGraphique::ThemeGraphique(); ?>/css/login.css" />
		<link rel="icon" type="image/png" href="themes/<?php echo SG_ThemeGraphique::ThemeGraphique(); ?>/img/favicon.png" />
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.css" />
		<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
		<script src="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.js"></script>
	</head>
	<body id="login">
		<div id="login-wrapper" class="png_bg">
			<div id="login-top"></div>
			<div id="login-content">
				<div id="logout">
					Vous êtes maintenant déconnecté.
					<br />
					<br />
					<a href="<?php echo SG_Navigation::URL_PRINCIPALE; ?>">SynerGaïa</a>
				</div>
			</div>
		</div>
	</body>
</html>
