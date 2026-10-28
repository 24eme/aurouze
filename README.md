AUROUZE
===

Projet de dématérialisation informatique des activités d'Aurouze

License utilisée
----------------

Ce logiciel est mis à disposition en licence AGPL.

Aperçu de l'application
-----------------------

[Voir toutes les captures d'écran de l'application](doc/Interfaces.md)

Installation
------------

[Via ansible](https://github.com/24eme/aurouze/tree/master/ansible)

Déployer mongodb 4.0 avec docker
--------------------------------

```sh
docker run --rm --name mongodb -v /var/lib/mongodb:/data/db -p 27017:27017 -d mongo:4.0
```

Compass
-------

Version de compass compatible avec mongo 4.0 : https://github.com/mongodb-js/compass/releases/tag/v1.46.1

Déployer le projet en local avec PHP7.4 sur Debian/Ubuntu
------------------------------

### Installer php7.4

Le dépôt d'Ondřej Surý est réputé mais n'est pas un dépôt officiel. Il est donc important d'être vigilant.

- Installer les dépendances nécessaires
```sh
sudo apt install -y apt-transport-https lsb-release ca-certificates wget
```

- Ajouter le dépôt d'Ondřej Surý
```sh
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
```

- Ajouter le dépôt PHP
```sh
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
```

- Mettre à jour la liste des paquets

```sh
sudo apt update
```

#### Installer PHP 7.4 et les extensions nécessaires
```sh
sudo apt install -y php7.4 php7.4-json php7.4-common php7.4-gd php7.4-mbstring php7.4-curl php7.4-xml php7.4-dev
```

- Vérifier l'installation
```sh
php7.4 -v
```

#### Installer php7.4-intl

L'extension intl n'est pas distribuée par le dépôt sury (au moins pour Debian 14)
Un dépôt Github compile les différentes versions de php-intl (de php 5.6 à 8.5) linké aux différentes lib ICU (de 50.2 à 78.3)

Il suffit de télécharger la bonne version en fonction de la version de libicu installé sur la machine et de la version de php :
```sh
dpkg -l | grep libicu
dpkg -l | grep php
```

Le dépôt se trouve ici : [icu-intl](https://github.com/shivammathur/icu-intl)

(D'autres versions existent dans la page release s'il s'agit d'une machine arm ou en version thread safe)

Il faut ensuite placer le `.so` téléchargé dans le dossier des modules de php7.4, trouvable via la commande `php-config74 --extension-dir`, le renommer en `intl.so`, et l'activer dans la configuration :

```sh
sudoedit /etc/php/7.4/cli/conf.d/20-intl.ini
```
```ini
extension=intl.so
```

### Installer l'extension MongoDB PHP 1.20.1 pour PHP 7.4
La bonne version de l'extension MongoDB PHP peut être récupérée sur [PECL](https://pecl.php.net/) (un dêpot d'extensions PHP).

```sh
wget https://pecl.php.net/get/mongodb-1.20.1.tgz
tar -xvzf mongodb-1.20.1.tgz
cd mongodb-1.20.1/
phpize
./configure
make all -j $(nproc)
sudo make install
```

- Ajouter l'extension dans php.ini
```sh
sudo nano /etc/php/7.4/cli/conf.d/20-mongodb.ini
```

```ini
extension=mongodb.so
```

### Installer les dépendances nécessaires au projet
- Se rendre dans le dossier `project/`

- Installer les dépendances avec Composer en pointant sur PHP 7.4
```sh
/usr/bin/php7.4 /usr/local/bin/composer install
```

### Problèmes rencontrés

Extension 'intl' introuvable :

vérifier l'installation de libicu78

```
dpkg -l | grep -i libicu
```

Si libicu n'est pas installé, télécharger la dernière version depuis :
```
sudo apt-get install libicu78
```

Ensuite, téléchargez la version de intl correspondant à la version de libicu78 :
https://github.com/shivammathur/icu-intl/releases/download/intl-78.3/php7.4-intl-78.3.so

Déplacer le fichier des téléchargement dans les fichiers d'extension de php7.4

```
mv dossier-de-telechargement/php7.4-intl-78.3.so /usr/lib/php/20190902/intl.so
```

Direction le php.ini de php7.4 pour activer l'extension intl :
```
sudo vim /etc/php/7.4/cli/php.ini
```
Chercher la ligne 'extension=intl' et décommentez la


Téléphone
---------

[Installer l'application sur téléphone](doc/Telephone.md)
