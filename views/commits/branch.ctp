<?php
$script = '
$(document).ready(function(){
	$(".message").each(function () {
		$(this).html(converter.makeHtml(jQuery.trim($(this).text())))
	});
});
';
$html->scriptBlock($script, array('inline' => false));
?>
<div class="page-navigation">
	Branches
	<?php
		foreach((array)$branches as $branch) :
			echo ' | ' . $html->link($branch, $chaw->url((array)$CurrentProject, array(
				'controller' => 'commits', 'action' => 'branch', $branch
			)));
		endforeach;
	?>

</div>

<div class="box">
<h4>
	<?php __('Commits for') ?>

	<?php
		$title = null;
		if (!empty($CurrentProject->fork)) {
			$title = "forks / {$CurrentProject->fork} / ";
		}
		$title .= $CurrentProject->url;
		echo $html->link($title, array('controller' => 'source', 'action' => 'index'));
	?>
	<?php
		$path = '/';
		foreach ((array)$args as $part):
			$path .= $part . '/';
			echo '/' . $html->link(' ' . $part . ' ', array('controller' => 'source', 'action' => 'index', $path));
		endforeach;
		echo '/ ' . $html->link($current, array('controller' => 'source', 'action' => 'index', $path, $current));
	?>
</h4>

<div class="commits history">

	<?php $i = 0; foreach ((array)$commits as $commit): $zebra = ($i++ % 2) ? ' zebra' : null?>

		<div class="commit <?php echo $zebra?>">
			<strong>
				<?php echo $chaw->commit($commit['Repo']['revision'], (array)$CurrentProject);?>
			</strong>
			
			<div class="right">
				<p>
					<strong><?php __('Author') ?>:</strong> <?php echo $commit['Repo']['author'];?>
				</p>

				<p>
					<strong><?php __('Date') ?>:</strong> <?php echo $commit['Repo']['commit_date'];?>
				</p>
			</div>

			<p class="message">
				<?php echo $commit['Repo']['message'];?>
			</p>

			<div class="clear"><!----></div>

		</div>

	<?php endforeach;?>

</div>
</div>
<?php echo $this->element('layout/pagination'); ?>
