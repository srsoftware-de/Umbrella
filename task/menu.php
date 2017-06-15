<div>
<?php if ($pid = param('id')){ ?>
<a href="../index">Index</a>
<a href="tasks">Tasks</a>
<a href="user_list">Users</a>
<a href="add_user">Add user</a>
<a href="add_subtask">Add subtask</a>
<?php } else { ?>
<a href="index">Index</a>
<?php } ?>
</div>
