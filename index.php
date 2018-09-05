<?php include 'bootstrap.php'; include 'common_templates/head.php'; ?>

<h2>Willkommen bei Umbrella!</h2>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Was ist Umbrella?</legend>
	<p>Umbrella ist eine Open-Source-Software, die es erlaubt online auf einem Server deinen Kram zu managen.</p>
	<p>Wenn du es auf einem eigenen Server installierst hast du eine Cloud-Software, deren Daten <b>in deiner Hand</b> liegen.
	<p>Die Software umfast verschiedene Module, die unter anderem folgende Funktionskreise umfassen:</p>
	<ul>
		<li>Projekt- und Aufgabenverwaltung</li>
		<li>Zeiterfassung</li>
		<li>Rechnungs-Legung</li>
		<li>Lesezeichen- und Tag-Verwaltung</li>
		<li>Dateiverwaltung</li>
	</ul>	
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Wie funktioniert Umbrella?</legend>
	<p>Die Basiskomponenten von Umbrella sind in der Programmiersprache PHP geschrieben. Es handelt sich um ein kleines Framework, dass es erlaubt in kurzer Zeit Module wie Projektverwaltung oder Lesezeichenmanagement an eine zentrale Benutzerverwaltung zu koppeln.</p>
	<p>Jedes Modul liegt standardmäßig in einem Unterordner, z.B. <em>/project</em> für die Projekte. Die einzelnen Module sind also sogenannte Microservices, die nur über das Framework gekoppelt sind.<p>
	<p>Daher ist es möglich, die Installation auf benötigte Module zu beschränken, wenn man selbst eine Umbrella-Instanz einrichtet.</p>
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Ich möchte Umbrella testen</legend>
	<p>Unter <a class="button" href="https://umbrella-demo.keawe.de/project/3/view">https://umbrella-demo.keawe.de</a> gibt es eine Demo-Installation, an welcher man sich ohne vorherige Registrierung anmelden kann.</p>
	<p>Diese wird täglich zurückgesetzt und umfasst eine Auswahl an Beispielbenutzern mit verschiedenen Projekten, Aufgaben, Notizen und Berechtigungen umfassen</p>	
</fieldset>

<fieldset style="max-width: 382px; display: inline-block; text-align: justify">
	<legend>Ich vermisse Funktion XYZ</legend>
	<p>Kein großes Problem!</p>
	<p>Umbrella ist so modular gestaltet, dass man binnen kurzer Zeit weitere Module für beliebige Aufgaben ergänzen kann. Dies kann entweder in PHP geschehen, oder aber in einer beliebigen anderen Programmiersprache, die über eine HTTP-Verbindung mit dem Framework kommunizieren kann.</p>
	<p>Durch die Quelloffenheit kann jeder neue Module beisteuern oder bestehende anpassen</p>
	<p>Natürlich können Sie auch gern ein Modul für Ihre Zwecke in Auftrag geben!</p>
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
	<p>Zum installieren kannst du dir einfach den Quelltext von <a class="button" href="https://github.com/keawe-software/Umbrella">GitHub</a> holen:</p>
	<code>
	git clone https://github.com/keawe-software/Umbrella.git /var/www<br/>
	git clone --branch user https://github.com/keawe-software/Umbrella.git /var/www/user<br/>
	git clone --branch task https://github.com/keawe-software/Umbrella.git /var/www/task<br/>
	git clone --branch project https://github.com/keawe-software/Umbrella.git /var/www/project<br/>	
	</code>...	
	<p>wobei die Zeilen <code>git clone --branch <b>XYZ</b> ... /var/www/<b>XYZ</b></code> dann jeweils das Modul XYZ installieren.
	Eine Übersicht der Module findest du <a class="button" href="https://github.com/keawe-software/Umbrella/branches/active">hier</a>.
	Anschließend musst du nur noch die von dir gewählten Branches in der Datei config.php aktivieren, eine Vorlage dafür findest du in config.template.php.</p>
</fieldset>
<p>
	<a class="button" href="project">Login</a>
</p>
<?php include 'common_templates/closure.php'; ?>
