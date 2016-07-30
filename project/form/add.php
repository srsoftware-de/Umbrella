<form action="../ctrl/add" method="GET">
	<input type="text" name="name"/>Project Name<br/>
	<input type="text" name="description"/>Description<br/>
	<input type="hidden" name="token" value="<?php print $_GET['token']; ?>"/>
	<input type="submit"/>
</form>
