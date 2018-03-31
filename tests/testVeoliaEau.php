<?php
/**
 *
 */

require_once './veolia_eauMockJeedom.class.php';
require_once '../core/class/veolia_eau_process.class.php' ;

$instanceVeolia = new veolia_eau;
$instanceVeolia->setConfiguration('last',"2018-03-09");
$instanceVeolia->setConfiguration('website',1);

$instanceVeolia->setConfiguration('mock_date',"2018-03-12");
$instanceVeolia->setConfiguration('csv_mock_file',"veolia_eau_data/consommation.xls");
$instanceVeolia->displayConfig();
$instanceVeolia->getConso(2);
/**
 *
 */
?>
