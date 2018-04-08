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
# how to test this file:
# su --shell=/bin/bash - www-data -c "/usr/bin/php /var/www/html/core/class/../../core/php/jeePlugin.php plugin_id=veolia_eau function=update callInstallFunction=1"
# su --shell=/bin/bash - pi -c "/usr/bin/php /var/www/html/core/class/../../core/php/jeePlugin.php plugin_id=veolia_eau function=update callInstallFunction=1"

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function veolia_eau_install()
{
    log::add('veolia_eau', 'error', 'config - install started');
    veolia_eau_update() ;
//    foreach (eqLogic::byType('veolia_eau') as $eqLogic) {
//        log::add('veolia_eau', 'error', $eqLogic->getHumanName());
//        $eqLogicId = $eqLogic->getId();
//        $cmd=cmd::byEqLogicIdAndLogicalId($eqLogicId, 'conso');
//        $cmdId = $cmd->getId();
//         if ($cmdId == 647){
//             log::add('veolia_eau', 'error', '$cmdId:' . $cmdId);
//             $debut='2018-04-02 00:00:00';
//             log::add('veolia_eau', 'error', 'debut:' . $debut);
//             $fin=$debut;
//             log::add('veolia_eau', 'error', 'fin:' . $fin);
//             $value = history::all($cmdId, $debut, $fin);
//             log::add('veolia_eau', 'error', 'count(value):' . count($value));
//
//             $value_date_time = history::byCmdIdDatetime(  $cmdId, $debut);
//             echo var_dump($value_date_time);
//             if (is_object($value_date_time )){
//                 log::add('veolia_eau', 'error', 'value_date_time:' . $value_date_time->getValue());
//                 log::add('veolia_eau', 'error', 'value[0]:' . $value[0]->getValue());
//                 //log::add('veolia_eau', 'error', 'value[1]:' . $value[1]);
//                 $value_date_time->remove();
//                 DB::remove($value_date_time);
//                 //$value_date_time->setValue(0);
//                 //$value_date_time->save($cmd,true);
//                 //$value_date_time->emptyHistory($cmdId,$debut);
//                 log::add('veolia_eau', 'error', 'value_date_time2:' . $value_date_time->getValue());
//                 $value_date_time = history::byCmdIdDatetime(  $cmdId, $debut);
//                 //echo var_dump($value_date_time);
//                 log::add('veolia_eau', 'error', 'value_date_time3:' . $value_date_time->getValue());
//             } else{
//                 log::add('veolia_eau', 'error', 'pas d historique pour: ' . $debut);
//
//             }
//
//         }
//
//    }
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
      $offsetVeoliaDate=$eqLogic->getConfiguration('offsetVeoliaDate');
      if ($offsetVeoliaDate == ""){
          $offsetVeoliaDate=3;
           $eqLogic->setConfiguration('offsetVeoliaDate',$offsetVeoliaDate);
      }
      $eqLogic->save();
      log::add('veolia_eau', 'info', $eqLogic->getHumanName().': $lastdate: '.$lastdate.' depart:'.$depart_compteur.' offsetVeoliaDate: '.$offsetVeoliaDate);

    }
}

function veolia_eau_remove() {
    log::add('veolia_eau', 'info', 'config - remove started');
}
