<?php 

include "../../bootstrap.php";

$projects = request('project', 'list?token='.$token);
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
