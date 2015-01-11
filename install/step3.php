<ul id="simple_wizard-titles" class="stepy-titles clearfix">
	<li id="simple_wizard-title-0">
		<div>mySQL details</div>
		<span>Enter your database info</span>
		<span class="stepNb">1</span>
	</li>
	
	<li id="simple_wizard-title-1">
		<div>Site info</div>
		<span>Paths, urls, etc.</span>
		<span class="stepNb">2</span>
	</li>
	
	<li id="simple_wizard-title-2" class="current-step">
		<div>Extra Security</div>
		<span>(added by Hawke663)</span>
		<span class="stepNb">3</span>
	</li>
	
	<li id="simple_wizard-title-3">
		<div>Admin user</div>
		<span>Your first user</span>
		<span class="stepNb">4</span>
	</li>
	
	<li id="simple_wizard-title-4">
		<div>All done</div>
		<span>Enjoy your site</span>
		<span class="stepNb">5</span>
	</li>
</ul>
<br />							
<form method="post" class="stepy-wizzard form-horizontal" id="simple_wizard">
	<fieldset title="" class="step" id="simple_wizard-step-0">
		<legend class="hide">Enter your site details</legend>
		
		
				<div class="formSep control-group<?php if (isset($step3_errors[1])){ print(" error"); }?>">
			<label class="control-label" for="admin_folder">New name for Control Panel:</label>
			<div class="controls">
				<input type="text" id="admin_folder" class="span6" name="admin_folder" <?php if (isset($_POST['admin_folder']) && $_POST['admin_folder']){ print("value=\"{$_POST['admin_folder']}\""); } elseif (isset($_SESSION['admin_folder'])){ print("value=\"{$_SESSION['admin_folder']}\""); } ?>>
				<?php if (isset($step3_errors_errors[2])){ ?>
					<span class="help-inline"><?php print($step3_errors[1]); ?></span>
				<?php } ?>
			</div>
		</div>
		
		

		<p class="simple_wizard-buttons" id="simple_wizard-buttons-0">
			<input type="hidden" name="menu" value="install" />
			<input type="hidden" name="step" value="3" />
			<a id="simple_wizard-back-1" href="index.php?menu=install&step=2" class="btn button-back">&laquo; Back</a>
			<input type="submit" name="process" class="btn btn-inverse button-next" value="Next &raquo;" <?php if (isset($errors) && count($errors)){ print("disabled='disabled'"); } ?> />
		</p>
	</fieldset>
</form>