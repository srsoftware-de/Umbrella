<?php

include 'bootstrap.php';

if ($token == null) die(NULL);

$perms = request('permission','get?token='.$token);
print_r($perms);


