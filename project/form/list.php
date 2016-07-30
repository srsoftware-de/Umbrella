<?php 

include "../../bootstrap.php";

$projects = request('project', 'ctrl/list');
include 'menu.php';
?>
<table>
	<tr>
		<th>Project</th><th>Description</th>
	<tr>
	<?php foreach ($projects as $project => $description) { ?>
	<tr>
		<td><?php print $project ?></td><td><?php print $description ?></td>
	</tr>
	<?php } ?>
</table>
