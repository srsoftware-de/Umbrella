<?php $query = param('path') ? '?dir='.param('path') : ''; ?>
<a class="symbol" title="add new file" href="add<?= $query ?>"></a>
