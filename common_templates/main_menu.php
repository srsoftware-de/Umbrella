<div id="main_menu">
<?php foreach ($services as $service){ 
	postLink($service['path'],$service['name']);
} 
if (isset($user)) {
	postLink($services['user']['path'].'logout','Log out '.$user->login); 
}
?>
</div>
