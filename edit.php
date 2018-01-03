<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$project_id = param('id');
if (!$project_id) error('No project id passed to view!');


if ($name = post('name')){
	update_project($project_id,$name,post('description'),post('company'));
	if ($redirect=param('redirect')){
		redirect($redirect);
	} else {
		redirect('view');
	}
}

$project = load_projects(['ids'=>$project_id,'single'=>true]);
$companies = request('company','json');

if (isset($services['bookmark'])){
	$hash = sha1(getUrl('project',$project_id.'/view'));
	$bookmark = request('bookmark','json_get?id='.$hash);
}


include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend><?= t('Edit Project')?></legend>
                <fieldset>
                        <legend>Company</legend>
                        <select name="company">
				<option value="0"><?= t('== no company assigned =='); ?></option>
                        <?php foreach($companies as $company) { ?>
                                <option value="<?= $company['id'] ?>" <?= $company['id'] == $project['company_id']?'selected="true"':''?>><?= $company['name'] ?></a>
                        <?php } ?>
                        </select>
                </fieldset>
		<fieldset>
			<legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= $project['name']; ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="https://www.markdownguide.org/cheat-sheet">click here for Markdown and extended Markdown cheat sheet</a>')?></legend>
			<textarea name="description"><?= $project['description']?></textarea>
		</fieldset>
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? implode(' ', $bookmark['tags']) : ''?>" />
		</fieldset>
		<?php } ?>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
