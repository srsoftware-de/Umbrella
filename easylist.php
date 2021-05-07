<?php include 'controller.php';

$view = easylist();
$previous = NULL;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
    <legend>
    	<?= t('Tag') ?>
	</legend>
    <form>    	
    	<input name="tag" value="<?= param('id') ?>">
    	<button type="submit"><?= t("Update") ?></button>
    </form>
</fieldset>

<fieldset>
<legend><?= t('open')?></legend>
<?php foreach ($view->tasks as $task) if ($task->status == TASK_STATUS_OPEN) { ?>
<div>
<a class="button" href="<?= $base_url.$view->tag .'/easylist?complete='.$task->id.'#'.$previous ?>" name="task-<?= $task->id ?>">
<?= $task->name ?>
<?= $task->description ? '<p>'.strip_tags(markdown($task->description),'<br>').'</p>' : ''?>
</a>
<a class="symbol" href="<?= $base_url.'/'.$task->id .'/view' ?>"></a>
</div>
<?php 
$previous = 'task-'.$task->id;
}?>
</fieldset>

<fieldset>
<legend><?= t('closed')?></legend>
<?php foreach ($view->tasks as $task) if ($task->status == TASK_STATUS_COMPLETE) {  ?>
<div>
<a class="button" href="<?= $base_url.$view->tag .'/easylist?open='.$task->id.'#'.$previous ?>" name="task-<?= $task->id ?>">
<?= $task->name ?>
<?= $task->description ? '<p>'.strip_tags(markdown($task->description),'<br>').'</p>' : ''?>
</a>
<a class="symbol" href="<?= $base_url.'/'.$task->id .'/view' ?>"></a>
</div>
<?php 
$previous = 'task-'.$task->id;
}?>
</fieldset>


<?php include '../common_templates/closure.php'; ?>
