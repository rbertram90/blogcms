<div class="posts">
	<div id="messages">
	<?php
	if(isset($_SESSION['messagetoshow']) && $_SESSION['messagetoshow'] != false) {
		echo $_SESSION['messagetoshow'];
		$_SESSION['messagetoshow'] = false;
	}
	?>
	</div>
	<?=$DATA['page_content']?>
	
</div><div class="leftcolumn">
	<div class="actions">
		<?php include SERVER_PATH_BLOGCMS.'/app/view/blog_actions.php'; ?>
	</div>
	<?php if(array_key_exists('leftpanel', $DATA['widget_content'])) echo $DATA['widget_content']['leftpanel']; ?>
</div>