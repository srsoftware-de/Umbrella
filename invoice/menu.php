<div>
<?php if ($pid = param('id')){ ?>
<a title="edit"     href="edit"     class="symbol"></a>
<a title="index"    href="../index" class="symbol"></a>
<a title="add"      href="../add" class="symbol"></a>
<?php } else { ?>
<a title="index"    href="index" class="symbol"></a>
<a title="add" href="add" class="symbol"></a>
<?php } ?>
</div>
