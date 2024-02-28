project
=======

A Symfony project created on January 19, 2016, 5:19 pm.

Docker
=====

```
podman|docker build -t aurouze-7.0 .
podman|docker run -p 8000:80 --replace -v "$PWD":/var/www/html/ --name apache-aurouze-7.0 localhost/aurouze-7.0:latest
```
