<div class="wrap">
	<h2>Configuration</h2>

	<form method="post" name="myForm" id="myForm" class="configuration-form" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="default_category">Ryver Webhook</label></th>
					<td>
						<input name="ryver_webhook" type="text" id="ryver_webhook" value="<?php echo get_option('RYVER_WEBHOOK');?>" class="regular-text code">
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
	</form>
</div>