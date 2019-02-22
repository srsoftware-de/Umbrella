<form method="POST" action="<?= getUrl('bookmark','add') ?>">
	<fieldset>
		<legend><?= t('Add new URL') ?></legend>
		<fieldset>
			<legend>URL</legend>
			<input type="text" name="url" id="url" value="<?= $url ?>" autofocus="true"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="comment" descr="<?= t('You can select a comment from the site here')?>"></textarea>
		</fieldset>
		<fieldset>
			<legend>Tags</legend>
			<input type="text" name="tags" value="<?= $tags ?>" />
		</fieldset>
		<fieldset class="share">
			<legend><?= t('Share bookmark')?></legend>
			<table>
				<tr>
					<th><?= t('User')?></th>
					<th><?= t('Don\'t share')?></th>
					<th><?= t('Share & notify')?></th>
					<th><?= t('Share, don\'t notify')?></th>
				</tr>
				<?php foreach ($users as $usr) {  if ($usr['id']==$user->id) continue; ?>
				<tr>
					<td><?= $usr['login']?></td>
					<td><input type="radio" name="users[<?= $usr['id']?>]" value="<?= NO_SHARE ?>" checked="checked"/></td>
					<td><input type="radio" name="users[<?= $usr['id']?>]" value="<?= SHARE_AND_NOTIFY ?>" /></td>
					<td><input type="radio" name="users[<?= $usr['id']?>]" value="<?= SHARE_DONT_NOTIFY ?>" /></td>
				</tr>
				<?php } ?>
			</table>
		</fieldset>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
	<script type="text/javascript">
	$('#url').bind('input',getHeadings_delayed);
	</script>
</form>