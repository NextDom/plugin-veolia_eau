#!/bin/bash
rm -rf veolia_eau_data
mkdir veolia_eau_data
cp ./veolia_eau_data_src/consommation.xls veolia_eau_data
php testVeoliaEau.php > veolia_eau_data/testVeoliaEau.txt
echo diff -B veolia_eau_data/testVeoliaEau.txt veolia_eau_data_src/testVeoliaEau.txt
echo cp veolia_eau_data/testVeoliaEau.txt veolia_eau_data_src
