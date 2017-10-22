<div id="main_menu">
<?php foreach ($services as $service){ 
	postLink($service['path'],t($service['name']));
} 
if (isset($user)) {
	postLink($services['user']['path'].'logout?returnTo='.location(),t('Log out ?',$user->login)); 
}
?>
</div>