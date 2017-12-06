<?php
global $errors, $infos;
if (isset($errors) && !empty($errors)): ?>
<div class="errors">
	<?php foreach ($errors as $error): ?>
	<span><?= $error ?></span>
	<?php endforeach; ?>
</div>
<?php endif; 
if (isset($warnings) && !empty($warnings)): ?>
<div class="warnings">
	<?php foreach ($warnings as $warning): ?>
	<span><?= $warning ?></span>
	<?php endforeach; ?>
</div>
<?php endif;
if (isset($infos) && !empty($infos)): ?>
<div class="infos">
	<?php foreach ($infos as $info): ?>
	<span><?= $info ?></span>
	<?php endforeach; ?>
</div>
<?php endif; ?>
