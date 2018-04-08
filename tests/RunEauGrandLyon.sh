#!/bin/bash
rm -rf veolia_Lyon_data
mkdir veolia_Lyon_data
php testVeoliaLyon.php > veolia_Lyon_data/testVeoliaLyon.txt
echo diff -B veolia_Lyon_data/testVeoliaLyon.txt Veolia-Lyon/testVeoliaLyon.txt
echo cp veolia_Lyon_data/testVeoliaLyon.txt Veolia-Lyon

