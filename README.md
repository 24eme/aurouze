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

Déployer le projet en local avec php7.4 sur Debian
------------------------------
### Installer php7.4
Le dépôt de Ondrej Sury est réputé mais nest pas un dépôt officiel. Il est donc important d'être vigilant.

- Installer les dépendances nécessaires
```
sudo apt install -y apt-transport-https lsb-release ca-certificates wget
```

- Ajouter le dépôt de Sury
```
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
```

Ajouter le dépôt PHP
```
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
```

Mettre à jour la liste des paquets

```
sudo apt update
```

Installer php7.4 et les extensions nécessaires
```
sudo apt install -y php7.4 php7.4-json php7.4-common php7.4-mysql php7.4-zip php7.4-gd php7.4-mbstring php7.4-curl php7.4-xml php7.4-bcmath
```

### Installer le driver MongoDB 1.12.1 pour php7.4
```
wget https://pecl.php.net/get/mongodb-1.12.1.tgz
tar -xvzf mongodb-1.12.1.tgz
cd mongodb-1.12.1/
phpize
./configure
make all -j 5
sudo make install
```

### Installer les dépendances nécessaires au projet
- Se rendre dans le dossier ```project/```

- Installer les dépendances
```
composer install
```

Téléphone
---------

[Installer l'application sur téléphone](doc/Telephone.md)
