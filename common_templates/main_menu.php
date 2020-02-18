<div id="main_menu">
<?php if (isset($user)) { ?>
	<div class="search">
	<form action="<?= getUrl('user','search')?>" method="GET">
	<input type="text" name="key" />
	<button type="submit" class="symbol"></button>
	</form>
	</div>
<?php }
	foreach ($services as $service){ if (empty($service['name'])) continue;?>
	<a class="button" href="<?= $service['path'] ?>"><?= t($service['name']) ?></a>
<?php }
if (isset($user)) { ?>
	<a class="button" href="<?= $services['user']['path'].'logout?returnTo='.location() ?>"><?= t('Log out ◊',$user->login) ?></a>
<?php }?>
</div>