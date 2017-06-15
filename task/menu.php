<div>
<?php if ($pid = param('id')){ ?>
<a href="edit">Edit</a>
<a href="add_user">Add user</a>
<a href="add_subtask">Add subtask</a>
<a href="../index">Index</a>
<a href="tasks">Tasks</a>
<?php } else { ?>
<a href="index">Index</a>
<?php } ?>
</div>
