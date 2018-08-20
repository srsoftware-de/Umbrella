<?php $title = 'Umbrella Bookmark Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');

if ($share_user = param('share_user_id')) {
	$bookmark = Bookmark::load(['url_hash'=>param('share_url_hash')]);
	$bookmark->share($share_user,param('notify'));
	
	
	share_bookmark($share_user,param('share_url_hash'),param('notify',false));
}

$urls = Bookmark::load(['order' => 'timestamp DESC', 'limit' => param('limit',40)]);


$options = [ 'index'=>'hash', 'url_hash' => array_keys($urls) ];
$tags = Tag::load($options);
foreach ($tags as $hash => $tag) $urls[$hash]->tags = $tag['tags'];


$comments = Comment::load($options);
foreach ($urls as $hash => &$url){
	$url->external=true;
	if (isset($comments[$hash])) $url->comment = $comments[$hash]['comment'];
	foreach ($services as $name => $service){
		if (strpos($url->url,$service['path']) === 0) $url->external = false;
	}
}

$users = load_connected_users();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="bookmark">
	<legend><?= t('latest bookmarks')?></legend>
	
	<?php foreach ($urls as $hash => $bookmark ) {?>
	<fieldset>
		<legend>
			<a class="symbol" href="<?= $hash ?>/edit?returnTo=<?= urlencode(location('*'))?>"></a>
			<a class="symbol" href="<?= $hash ?>/delete?returnTo=<?= urlencode(location('*'))?>"></a>
			<a <?= $bookmark->external?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= isset($bookmark->comment) ? $bookmark->comment:$bookmark->url ?></a>
		</legend>
		<a <?= $bookmark->external?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $bookmark->url ?></a>
		<?php if (isset($bookmark->tags)) { ?>
		<div class="tags">		
			<?php foreach ($bookmark->tags as $related){ ?>
			<a class="button" href="<?= getUrl('bookmark',$related.'/view') ?>"><?= $related ?></a>
			<?php } ?>
		</div>
		<?php } ?>
		<fieldset class="share">
			<legend><?= t('share')?></legend>
			<form method="POST">
				<input type="hidden" name="share_url_hash" value="<?= $hash?>" />
				<input type="hidden" name="notify" value="1" />
				<select name="share_user_id">
				<option value=""><?= t('select user')?></option>
				<?php foreach ($users as $uid => $some_user) {
					if ($uid == $user->id) continue; ?>
				<option value="<?= $uid?>"><?= $some_user['login'] ?></option>
				<?php } ?>
				</select>
				<input type="submit" />
			</form>
		</fieldset>
	</fieldset>
	<?php } ?>	
</fieldset>

<?php include '../common_templates/closure.php'; ?> 
