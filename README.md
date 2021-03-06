# Was ist Umbrella?

Umbrella ist eine Open-Source-Software, die es erlaubt online auf einem Server deinen Kram zu managen.

Wenn du es auf einem eigenen Server installierst hast du eine Cloud-Software, deren Daten in deiner Hand liegen.

Die Software umfast verschiedene Module, die unter anderem folgende Funktionskreise umfassen:

* Projekt- und Aufgabenverwaltung
* Zeiterfassung
* Rechnungs-Legung
* Lesezeichen- und Tag-Verwaltung
* Dateiverwaltung
* Modellierung von Geschäftsprozessen
* Chat (mittels externem Anbieter)

## Wie funktioniert Umbrella?

Die Basiskomponenten von Umbrella sind in der Programmiersprache PHP geschrieben.
Es handelt sich um ein kleines Framework, dass es erlaubt in kurzer Zeit Module wie Projektverwaltung oder Lesezeichenmanagement an eine zentrale Benutzerverwaltung zu koppeln.

Jedes Modul liegt standardmäßig in einem Unterordner, z.B. /project für die Projekte.
Die einzelnen Module sind also sogenannte *Microservices*, die nur über das Framework gekoppelt sind.

Daher ist es möglich, die Installation auf benötigte Module zu beschränken, wenn man selbst eine Umbrella-Instanz einrichtet.

## Ich möchte Umbrella testen

Unter https://umbrella-demo.srsoftware.de gibt es eine Demo-Installation, an welcher man sich ohne vorherige Registrierung anmelden kann.

Diese wird täglich zurückgesetzt und umfasst eine Auswahl an Beispielbenutzern mit verschiedenen Projekten, Aufgaben, Notizen und Berechtigungen umfassen

## Ich möchte Umbrella für mich/meine Firma nutzen. Wie geht das?

Kurz und knapp: du musst es auf deinem Webserver installieren.

Das bedeutet:

du brauchst einen funktionierenden Web-Server (es ist ja ein online-Programm) mit apache2 oder nginx
auf dem Server muss ein php-Interpreter installiert sein
auf dem Server muss das SQLite-DBs laufen
Und das ist schon alles!

Zum installieren kannst du dir einfach den Quelltext von [GitHub](https://github.com/srsoftware-de/Umbrella) holen.

Die Installation ist in [einer separaten Datei](INSTALL.md) beschrieben.
