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

Téléphone
---------

[Installer l'application sur téléphone](doc/Telephone.md)
