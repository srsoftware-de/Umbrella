<?php

include '../bootstrap.php';

$perms = request('permission','ctrl/get');
if (!is_array($perms)) die(NULL);

if (!is_array($perms['user'])) die(NULL);
if (!in_array('list', $perms['user'])) die(NULL);

print file_get_contents('.userlist');

?>
