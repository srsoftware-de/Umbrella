<div>
<?php if ($pid = param('id')){ ?>
<a href="../index">Index</a>
<a href="../add">Add</a>
<a href="tasks">Tasks</a>
<a href="../../task/add_to_project/<?= $pid ?>">Add Task</a>
<a href="add_user">Add user</a>
<?php } else { ?>
<a href="index">Index</a>
<a href="add">Add</a>
<?php } ?>
</div>
