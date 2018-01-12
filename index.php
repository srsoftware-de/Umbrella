<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$projects = load_projects(['order'=>param('order')]);
$all_user_ids = load_users($projects);
$users = request('user','json',['ids'=>$all_user_ids]);
$show_closed = param('closed') == 'show' || param('order') == 'status';
$companies = isset($services['company']) ? request('company','json') : null;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<?php if (!$show_closed){ ?>
<a class="symbol" title="<?= t('show closed projects') ?>" href="?closed=show"></a>
<?php }?>
<table class="project-index">
	<tr>
		<th><a href="?order=name"><?= t('Name')?></a></th>
		<?php if ($companies) { ?>
		<th><a href="?order=company"><?= t('Company') ?></a></th>
		<?php } ?>
		<th><a href="?order=status"><?= t('Status')?></a></th>
		<th><?= t('Users')?></th>
		<th><?= t('Actions')?></th>
	</tr>
<?php foreach ($projects as $id => $project){
	if (!$show_closed && $project['status']>50) continue;
	?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $project['name'] ?></a></td>
		<?php if ($companies) { ?>
		<td><a href="<?= $id ?>/view"><?= isset($companies[$project['company_id']])?$companies[$project['company_id']]['name']:'' ?></a></td>
		<?php }?>
		<td><?= t(project_state($project['status'])) ?></td>
		<td>
		<?php foreach ($project['users'] as $id => $perm) {?>
		<?= $users[$id]['login']?><br/>
		<?php } ?>
		</td>
		<td>
			<a title="<?= t('edit')?>"     href="<?= $id ?>/edit?redirect=../index"     class="symbol"></a>
			<a title="<?= t('complete')?>" href="<?= $id ?>/complete?redirect=../index" class="<?= $project['status'] == PROJECT_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('cancel')?>"   href="<?= $id ?>/cancel?redirect=../index"   class="<?= $project['status'] == PROJECT_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('do open')?>"     href="<?= $id ?>/open?redirect=../index"     class="<?= $project['status'] == PROJECT_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
		</td>
	</tr>
<?php } ?>

</table>
<?php include '../common_templates/closure.php'; ?>
