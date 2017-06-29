<div>
<?php if ($pid = param('id')){ ?>
<a href="edit">Edit</a>
<a href="add_user">Add user</a>
<a href="add_subtime">Add subtime</a>
<a href="../index">Index</a>
<a href="times">Times</a>
<?php } else { ?>
<a href="index">List</a>
<a href="add">Add</a>
<?php } ?>
</div>
