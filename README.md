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

```
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
```
sudo apt install -y apt-transport-https lsb-release ca-certificates wget
```

- Ajouter le dépôt d'Ondřej Surý
```
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
```

- Ajouter le dépôt PHP
```
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
```

- Mettre à jour la liste des paquets

```
sudo apt update
```

#### Installer PHP 7.4 et les extensions nécessaires
```
sudo apt install -y php7.4 php7.4-json php7.4-common php7.4-gd php7.4-mbstring php7.4-curl php7.4-xml php7.4-dev
```

- Vérifier l'installation
```
php7.4 -v
```

### Installer l'extension MongoDB PHP 1.12.1 pour PHP 7.4
La bonne version de l'extension MongoDB PHP peut être récupérée sur [PECL](https://pecl.php.net/) (un dêpot d'extensions PHP).

```
wget https://pecl.php.net/get/mongodb-1.12.1.tgz
tar -xvzf mongodb-1.12.1.tgz
cd mongodb-1.12.1/
phpize
./configure
make all -j 5
sudo make install
```

- Ajouter l'extension dans php.ini
```
sudo nano /etc/php/7.4/cli/conf.d/20-mongodb.ini
```

```
extension=mongodb.so
```

### Installer les dépendances nécessaires au projet
- Se rendre dans le dossier ```project/```

- Installer les dépendances avec Composer en pointant sur PHP 7.4
```
/usr/bin/php7.4 /usr/local/bin/composer update composer install
```

Téléphone
---------

[Installer l'application sur téléphone](doc/Telephone.md)
