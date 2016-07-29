<?php 

include "../../bootstrap.php";

$users = request('user', 'list?token='.$token);
?>
<table>
	<tr>
		<th>Username</th><th>Password</th>
	<tr>
	<?php foreach ($users as $username => $pass) { ?>
	<tr>
		<td><?php print $username ?></td><td>XXXX</td>
	</tr>
	<?php } ?>
</table>
