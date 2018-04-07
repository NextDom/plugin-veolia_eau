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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function veolia_eau_install() {
    log::add('veolia_eau', 'info', 'config - install started');
    veolia_eau_update() ;
}

function veolia_eau_update() {
    // Set default values for each existing equipments
    log::add('veolia_eau', 'info', 'config - update started');
    foreach (eqLogic::byType('veolia_eau') as $eqLogic) {
      $lastdate = $eqLogic->getConfiguration('last');
      if ($lastdate == ""){
          $lastdatenum = time();
          $monthCur = date("F",$lastdatenum);
          $FirstDayMonth = strtotime("first day of ".$monthCur, $lastdatenum);
          $lastdate = date("Y-m-d",$FirstDayMonth);
          // $lastdate = "2017-09-10";
          $eqLogic->setConfiguration('last',$lastdate); //default value in config::
      }
      $depart_compteur=$eqLogic->getConfiguration('depart');
      if ($depart_compteur == ""){
           $depart_compteur=0;
           $eqLogic->setConfiguration('depart',$depart_compteur);
      }
      $eqLogic->save();
      log::add('veolia_eau', 'info', '$lastdate: '.$lastdate.' depart:'.$depart_compteur);

    }
}

function veolia_eau_remove() {
}
