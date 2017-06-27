<?php

setcookie('UmbrellaToken','',time()-5000,'/');
header('Location: index');

?>
