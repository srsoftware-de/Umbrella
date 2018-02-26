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
		<th><a href="?order=name"><?= t('Name')?></a></th>
				<?php if ($companies) { ?>
				<th><a href="?order=company"><?= t('Company') ?></a></th>
				<?php } ?>
				<th><a href="?order=status"><?= t('Status')?></a></th>
				<th><?= t('Users')?></th>
				<th><?= t('Actions')?></th>
			</tr>
		<?php foreach ($projects as $id => $project){ ?>
			<tr>
				<td><a href="<?= $url.$id ?>/view"><?= $project['name'] ?></a></td>
				<?php if ($companies) { ?>
				<td><?php if (isset($companies[$project['company_id']])) {
					$company = $companies[$project['company_id']]; ?>
					<a href="<?= getUrl('company',$company['id'].'/view') ?>"><?= $company['name'] ?></a>
					<?php } ?>
				</td>
				<?php }?>
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