<?php include_once 'bootstrap.php'; include 'common_templates/head.php'; ?>

<h2>Willkommen bei Umbrella!</h2>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Was ist Umbrella?</legend>
	<p>Umbrella ist eine Open-Source-Software, die es erlaubt online auf einem Server deinen Kram zu managen.</p>
	<p>Die Software umfast verschiedene Module, die unter anderem folgende Funktionskreise umfassen:</p>
	<ul style="margin-block-end:0">
		<li>Projekt- und Aufgabenverwaltung</li>
		<li>Zeiterfassung</li>
	</ul>
	<table style="background:transparent">
		<tr>
			<td><ul><li>Fakturierung:<br/></li></ul></td>
			<td>⎧<br/>⎨<br/>⎥<br/>⎩</td>
			<td>Angebote,<br/>Bestätigungen,<br/>Rechnungen und<br/>Mahnungen</td>
		</tr>
	</table>
	<ul style="margin-block-start:0">
		<li>Lesezeichen- und Tag-Verwaltung</li>
		<li>Dateiverwaltung</li>
		<li>Inventarverwaltung</li>
		<li>Notizen zu allen Elementen</li>
		<li>Projektmodellierung</li>
		<li>Online-Videokonferenzen</li>
	</ul>
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Ich möchte Umbrella testen</legend>
	<p>Unter <a class="button" href="https://umbrella-demo.keawe.de/project/3/view">https://umbrella-demo.keawe.de</a> gibt es eine Demo-Installation, an welcher man sich ohne vorherige Registrierung anmelden kann.</p>
	<p>Der Vorteil: Sie müssen nichts installieren und können sofort starten!</p>
	<p>Diese Demo-Version wird täglich zurückgesetzt und umfasst eine Auswahl an Beispielbenutzern mit verschiedenen Projekten, Aufgaben, Notizen und Berechtigungen und erläutert die wichtigsten Elemente der Software.</p>
	<p>Wenn du es auf einem eigenen Server installierst hast du eine Cloud-Software, deren Daten <b>in deiner Hand</b> liegen.
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Wie funktioniert Umbrella?</legend>
	<p>Die Basiskomponenten von Umbrella sind in der Programmiersprache PHP geschrieben. Es handelt sich um ein kleines Framework, dass es erlaubt in kurzer Zeit Module wie Projektverwaltung oder Lesezeichenmanagement an eine zentrale Benutzerverwaltung zu koppeln.</p>
	<p>Jedes Modul liegt standardmäßig in einem Unterordner, z.B. <em>/project</em> für die Projekte. Die einzelnen Module sind also sogenannte Microservices, die nur über das Framework gekoppelt sind.<p>
	<p>Daher ist es möglich, die Installation auf benötigte Module zu beschränken, wenn man selbst eine Umbrella-Instanz einrichtet.</p>
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Ich vermisse Funktion XYZ</legend>
	<p>Kein großes Problem!</p>
	<p>Umbrella ist so modular gestaltet, dass man binnen kurzer Zeit weitere Module für beliebige Aufgaben ergänzen kann. Dies kann entweder in PHP geschehen, oder aber in einer beliebigen anderen Programmiersprache, die über eine HTTP-Verbindung mit dem Framework kommunizieren kann.</p>
	<p>Durch die Quelloffenheit kann jeder neue Module beisteuern oder bestehende anpassen</p>
	<p>Natürlich können Sie auch gern ein Modul für Ihre Zwecke <a class="button" href="https://keawe.de/contact">in Auftrag geben</a>  oder Funktionalitäten anfragen!</p>
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Stärken und Schwächen</legend>
	<p>Eine große Stärke von Umbrella ist, dass die PHP-Module <b>nicht</b> auf irgendwelchen großen Frameworks aufbauen und (fast) kein JavaScript verweden.</p>
	<p>Dadurch ergeben sich folgende Vorteile:
		<ul>
			<li>Wenige Abhängigkeiten von fremden Programmbibliotheken</li>
			<li>schlankerer, schneller Quellcode</li>
			<li>kleinere Angriffsfläche für Code-Injections in Fremdpaketen</li>
			<li>Umbrella funktioniert auch bei deaktiviertem Javascript</li>
		</ul>
	</p>
	<p>Es gibt auch einige wenige Nachteile: Seiteninhalte werden nicht per Ajax nachgeladen, obwohl das gerade Mode ist.</p>
</fieldset>

<fieldset style="max-width: 800px; display: inline-block;">
	<legend>Ich möchte Umbrella für mich/meine Firma nutzen. Wie geht das?</legend>
	<p>Kurz und knapp: du musst es auf deinem Webserver installieren.</p>
	<p>Das bedeutet:</p>
	<ul>
		<li>du brauchst einen funktionierenden Web-Server (es ist ja ein online-Programm) mit apache2 oder nginx</li>
		<li>auf dem Server muss ein php-Interpreter installiert sein</li>
		<li>auf dem Server muss das SQLite-DBs laufen</li>
	</ul>
	<p>Und das ist schon alles!</p>
	<p>Zum installieren kannst du dir einfach den Quelltext von <a class="button" href="https://github.com/keawe-software/Umbrella">GitHub</a> holen.</p>
	<p>Für die weniger IT-versierten können wir auch eine betreute Installation vornehmen, <a class="button" href="https://keawe.de/contact">Kontaktieren Sie uns</a> einfach, wir erstellen gern ein individuelles Angebot! Dabei können Sie entscheiden, ob die Software bei Ihnen gehostet werden soll oder wir für Sie ein Hosting bereitstellen.</p>
</fieldset>


<p>
	<a class="button" href="project">Login</a>
</p>
<?php include 'common_templates/closure.php'; ?>
