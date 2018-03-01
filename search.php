<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');

if ($key = param('key')){
	$projects = load_projects(['key'=>$key]);
	$url = getUrl('project');
	if (!empty($projects)){ ?>
		<table class="project-index">
		<tr>
		<th><?= t('Name')?></a></th>
				<th><?= t('Status')?></a></th>
				<th><?= t('Users')?></th>
				<th><?= t('Actions')?></th>
			</tr>
		<?php foreach ($projects as $id => $project){ ?>
			<tr>
				<td><a href="<?= $url.$id ?>/view"><?= $project['name'] ?></a></td>
				<td><?= t(project_state($project['status'])) ?></td>
				<td>
				<?php foreach ($project['users'] as $uid => $perm) {?>
				<?= $users[$uid]['login']?><br/>
				<?php } ?>
				</td>
				<td>
					<a title="<?= t('edit')?>"     href="<?= $url.$id ?>/edit?redirect=../index"     class="symbol"></a>
					<a title="<?= t('complete')?>" href="<?= $url.$id ?>/complete?redirect=../index" class="<?= $project['status'] == PROJECT_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
					<a title="<?= t('cancel')?>"   href="<?= $url.$id ?>/cancel?redirect=../index"   class="<?= $project['status'] == PROJECT_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
					<a title="<?= t('do open')?>"     href="<?= $url.$id ?>/open?redirect=../index"     class="<?= $project['status'] == PROJECT_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
				</td>
			</tr>
		<?php } ?>
		
		</table>
		<?php }
}