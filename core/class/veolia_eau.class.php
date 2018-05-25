<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************** Includes ****************************** */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/veolia_eau_process.class.php';

class veolia extends eqLogic {
	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = '/tmp/dependancy_veolia_in_progress';
		$return['state'] = 'ok';
		if (exec('apt list --installed php7.0-mbstring | grep -E "mbstring"| wc -l') < 1) {
			$return['state'] = 'nok';
		}
		return $return;
	}
	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('veolia') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}
	
	}