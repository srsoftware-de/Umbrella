<div id="main_menu">
<?php foreach ($services as $service){ if ($service['name'] == 'Invoices') continue;?>	
	<a class="button" href="<?= $service['path'] ?>"><?= t($service['name']) ?></a>
<?php } 
if (isset($user)) { ?>
	<div class="search">
	<form action="<?= getUrl('user','search')?>" method="GET">
	<input type="text" name="key" />
	<button type="submit" class="symbol"></button>
	</form>
	</div>
	<a class="button" href="<?= $services['user']['path'].'logout?returnTo='.location() ?>"><?= t('Log out ?',$user->login) ?></a>
<?php }?>
</div>