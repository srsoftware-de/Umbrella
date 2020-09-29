<?php include 'controller.php';

require_login('mindmap');

$mindmap = Mindmap::load();

include '../common_templates/head.php'; ?>

<?php 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<canvas id="canvas" style="width:100%; height:98%"></canvas>
<script type="text/javascript" src="mindmap.js"></script>

<script type="text/javascript">
var mindmap = <?= $mindmap->data ?>;
renderMindmap();
</script>

<?php include '../common_templates/closure.php'; ?>
