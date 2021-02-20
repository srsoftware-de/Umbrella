<?php include 'controller.php';

$view = easylist();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form>
	<label>
	<?= t('Tag') ?>:&nbsp;<input name="tag" value="<?= param('id') ?>">
	</label>
	<button type="submit"><?= t("Update") ?></button>
</form>

<fieldset>
<legend><?= t('open')?></legend>
<?php foreach ($view->tasks as $task) if ($task->status == TASK_STATUS_OPEN) { ?>
<p>
<a class="button" href="<?= $base_url.'/'.$view->tag .'/easylist?complete='.$task->id ?>">
<?= $task->name ?><?= $task->description ? '<br/>'.$task->description : ''?>
</a>
<a class="symbol" href="<?= $base_url.'/'.$task->id .'/view' ?>"></a>
</p>
<?php }?>
</fieldset>

<fieldset>
<legend><?= t('closed')?></legend>
<?php foreach ($view->tasks as $task) if ($task->status == TASK_STATUS_COMPLETE) { ?>
<p>
<a class="button" href="<?= $base_url.'/'.$view->tag .'/easylist?open='.$task->id ?>">
<?= $task->name ?><?= $task->description ? '<br/>'.$task->description : ''?>
</a>
<a class="symbol" href="<?= $base_url.'/'.$task->id .'/view' ?>"></a>
</p>
<?php }?>
</fieldset>


<?php include '../common_templates/closure.php'; ?>