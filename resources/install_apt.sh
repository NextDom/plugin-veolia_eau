#!/bin/bash
PROGRESS_FILE=/tmp/dependancy_veolia_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}

# Racine du plugin (ce script est dans resources/)
PLUGIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
export COMPOSER_ALLOW_SUPERUSER=1

echo "********************************************************"
echo "*       Installation des dépendances veolia_eau        *"
echo "********************************************************"
sudo apt-get update
echo 20 > ${PROGRESS_FILE}
# Extensions PHP requises par PhpSpreadsheet (mbstring, zip, xml, gd) + curl
sudo apt-get install -y php-mbstring php-zip php-xml php-gd php-curl unzip
echo 50 > ${PROGRESS_FILE}

# Composer : installé s'il est absent
if ! command -v composer >/dev/null 2>&1; then
	echo "Installation de composer..."
	php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
	sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
	rm -f /tmp/composer-setup.php
fi
echo 70 > ${PROGRESS_FILE}

echo "Installation des librairies PHP (PhpSpreadsheet)..."
cd "${PLUGIN_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
