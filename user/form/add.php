<form action="../ctrl/add" method="GET">
	<input type="text" name="username"/>Username<br/>
	<input type="password" name="password"/>Password<br/>
	<input type="hidden" name="token" value="<?php print $_GET['token']; ?>"/>
	<input type="submit"/>
</form>
