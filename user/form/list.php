<?php 

include "../../bootstrap.php";

$users = request('user', 'ctrl/list');
?>
<?php include 'menu.php'?>
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
