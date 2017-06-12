<?php foreach ($services as $service){ 
	postLink($service['path'],$service['name']);
} 
if (isset($user)) {
	echo '| <a href="user/logout">Log out '.$user->login.'</a>'; 
}
?>

