<div id="main_menu">
<?php foreach ($services as $service){ ?>
	<a class="button" href="<?= $service['path'] ?>"><?= t($service['name']) ?></a>
<?php } 
if (isset($user)) { ?>
	<a class="button" href="<?= $services['user']['path'].'logout?returnTo='.location() ?>"><?= t('Log out ?',$user->login) ?></a>
<?php } ?>
</div>