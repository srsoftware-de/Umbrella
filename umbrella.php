<?php include 'controller.php';

require_login('mindmap');

if (param('data')){
    print Mindmap::save()->id;
} else {
    print Mindmap::load()->data;    
}

?>

