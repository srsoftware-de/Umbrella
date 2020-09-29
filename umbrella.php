<?php include 'controller.php';

require_login('mindmap');

if (param('data')){
    print Mindmap::save()->id;
} else {
    $mindmap = Mindmap::load();
    print '{"title":"'.$mindmap->title.'","data":'.$mindmap->data.'}';
}

?>

