<?php include 'controller.php';

require_login('time');

$time_id = param('id');
if (!$time_id) error('No time id passed to view!');

$time = Timetrack::load(['ids'=>$time_id]);

if ($subject = post('subject')){
	$time->update($subject,post('description'),post('start'),post('end'),post('state'))->save();
	redirect('..');
}


include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend><?= t('Edit Time')?></legend>
		<fieldset>
			<legend><?= t('Subject')?></legend>
			<input type="text" name="subject" value="<?= htmlspecialchars($time->subject); ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="description"><?= $time->description?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Start')?></legend>
			<input type="text" name="start" value="<?= date('Y-m-d H:i',$time->start_time?$time->start_time:time());?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('End')?></legend>
			<input type="text" name="end" value="<?= date('Y-m-d H:i',$time->end_time?$time->end_time:time());?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('State')?></legend>
			<select name="state">
			<?php foreach (TIME_STATES as $k => $v) { ?>
				<option value="<?= $k?>" <?= $k == TIME_STATUS_OPEN?'selected="true"':''?>><?= t($v)?></option>
			<?php }?>
			</select>
		</fieldset>
		<button type="submit"><?= t("Save")?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
