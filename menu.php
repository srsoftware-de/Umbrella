<?php $query = param('path') ? '?dir='.param('path') : ''; ?>
<a class="symbol" title="<?= t('add new file') ?>" href="add<?= $query ?>"></a>
<a class="symbol" title="<?= t('add new directory') ?>" href="add_dir<?= $query ?>"></a>
