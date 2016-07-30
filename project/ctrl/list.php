<?php

include '../bootstrap.php';

$perms = request('permission','ctrl/get');
if (!is_array($perms)) die(NULL);

if (!is_array($perms['project'])) die(NULL);
if (!in_array('list', $perms['project'])) die(NULL);

print file_get_contents('.projects');

?>
