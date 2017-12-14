<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

require_login('project');
if ($name = post('name')){
	add_project($name,post('description'),post('company'));
    header('Location: index');
    die();
}

$companies = request('company','json');

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Create new Project</legend>
		<fieldset>
			<legend>Company</legend>
			<select name="company">
			<?php foreach($companies as $company) { ?>
				<option value="<?= $company['id'] ?>"><?= $company['name'] ?></a>
			<?php } ?>
			</select>
		</fieldset>
		<fieldset>
			<legend>Name</legend>
			<input type="text" name="name" />
		</fieldset>
		<fieldset>
			<legend>Description</legend>
			<textarea name="description"></textarea>
		</fieldset>
		<?php if (isset($services['bookmark'])){?>
		<fieldset>
        	<legend><?= t('Tags')?></legend>
			<input name="tags" type="text" value="" />
		</fieldset>
		<?php }?> 
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
