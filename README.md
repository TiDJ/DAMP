# DAMP

DAMP is a little project under MIT licence.
It personnalize your index.php on your local machine (http://localhost or http://127.0.0.1)

<p align="center">
<img  src="https://raw.githubusercontent.com/TiDJ/DAMP/master/assets/screen/top-homepage.png">
</p>

## How does it work

Define (name,version,description,website, important link, etc.) your projects, put an image in "dashboardamp/" directory (optionnal) and that's all !
A hightly configurable panel, who create a Json in the "index.php" (config.json). It contain your configurations to create your own dashboard.
Get easily your web configuration (Apache, PHP, Mysql, etc.)

## Requirements

- PHP 4+
- Apache 2

## How to install

You can simply copy paste the "index.standalone.php", or :
```
git clone git@github.com:TiDJ/DAMP.git # or clone your own fork
cd DAMP
vim config.php
```
and update your WWW_DIR / WORKSPACE folder.

Your new dashboard should now be running on [localhost](http://localhost/).

## Optional

If you want a default project set in your dashboard :
```
cd DAMP
cp config.default.json config.json # or /var/www, /www, etc.
```

Here is an example of the config part :

<p align="center">
<img  src="https://raw.githubusercontent.com/TiDJ/DAMP/master/assets/screen/config-homepage.png">
</p>

## Librairies
- jQuery@3.1.1,
- Bootstrap@v4-alpha
- Tether@1.3.3

## About

The examples of defaults projects are some pretty design, but they are by no means related to me.
