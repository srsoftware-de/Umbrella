<?php $base = getUrl('user'); ?>
<?php if (isset($user->id)) { ?>
<a class="symbol" title="<?= t('edit your account')?>" href="<?= $base . $user->id ?>/edit"></a>
<?php } ?>
<a class="symbol" title="<?= t('add user')?>" href="<?= $base ?>add"></a>
<a class="symbol" title="<?= t('connect with other account')?>" href="<?= $base ?>add_openid_login"></a>

