<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$companies = Company::load();

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

foreach ($companies as $company){ ?>

<fieldset>
	<legend>
		<span>
			<a href="<?= $company->id ?>/edit" class="symbol"></a>
		</span>
		<?= $company->name ?>
	</legend>
	<table class="vertical">
	<?php foreach (Company::fields() as $field => $props) {
		if (in_array($field,['id','name','currency','decimal_separator','decimals','thousands_separator'])) continue; 
		if (isset($company->{$field})){ ?>
		<tr>
			<th><?= t($field)?></th>
			<td><?= str_replace("\n","<br/>\n",$company->{$field}) ?></td>		
		</tr>	
	<?php }}?>
	</table>
</fieldset>

<?php }

include '../common_templates/bottom.php';
