# Überblick

Hier soll kurz erklärt werden, wie du die Umbrella-Suite (oder Teile davon) auf deinem Webserver installieren kannst.

## Voraussetzungen

Du brauchst auf jeden Fall einen funktionierenden **Webserver mit PHP**-Unterstützung (PHP 5.6 oder neuer).
Welchen Webserver und welche PHP-Version du verwendest sollte keine große Rolle spielen.

Entwickelt und getestet wird die Software auf einem Debian-Server mit PHP 5.6 und apache 2.4.10.

Für die Standardinstallation wird **git** benötigt.
Außerdem muss noch die Sqlite-Unterstützung installiert werden, z.B. mit `apt-get install git php-sqlite sqlite3`.

Für einige Module benutzen DOMDocuemnt (funktionieren aber auch ohne), es wird empfohlen das Paket `php-dom` zu installieren


### PHP7

In php7 wurde die Behandlung von sogenannten *Assertions* (Zusicherungen) grundlegend verändert. Damit die Software korrekt funktioniert, ist es notwendig in der php.ini-Datei (/etc/php/7.0/apache2/php.ini) den Wert von zend.assertions von -1 auf 0 oder 1 zu setzen.
In zukünftigen Versionen wird dieses Verhalten angepasst werden.

## Installation mehrerer Services auf dem gleichen Webserver

Bei dieser Standardinstallation werden die Basis-Scripte und mehrere Module installiert.
Dazu muss zunächst in das Wurzelverzeichnis gehen, in welchem später der Umbrella-Webservice laufen soll.

Bei Apache ist das meist /var/www, deshalb nutzen wir das auch im Beispiel:

```
cd /var/www
git clone https://github.com/keawe-software/Umbrella
```

Diese Befehle werden die Basisdateien von Umbrella in den Ordner `/var/www/Umbrella` ablegen.

Weiter geht es mit der Installation der Module. 
Dies geht mit einem Script, welches Sie bei jedem Modul fragt, ob Sie es installieren wollen.
Im Beispielcode unten ist eine URL (http://example.com) angehängt – diese sollten Sie durch die Url Ihrer Installation ersetzen.
Falls Sie noch nicht wissen, unter welcher Url die Services später mal erreichbar sein werden, können Sie die Url auch weglassen, müssen dann aber später händisch die Datei config.php anpassen.

```
cd /var/www/Umbrella
./install-services http://example.com
```

Dieses Script wird mittels *git* alle gewünschten Module installieren und eine entsprechende Konfigurationsdatei `config.php` anlegen. 
Falls Sie Module händisch installieren, müssen Sie auch diese Konfigurationsdatei manuell anpassen.

## Verteilte Installation auf mehreren Webservern

TODO