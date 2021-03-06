<div class="source index box">

<h4>
	<?php
		$title = null;
		if (!empty($CurrentProject->fork)) {
			$title = "forks / {$CurrentProject->fork} / ";
		}
		$title .= $CurrentProject->url;
		echo $html->link($title, array('action' => 'index'));
	?>
	<?php
		$path = '/';
		foreach ((array)$args as $part):
			$path .= $part . '/';
			echo '/' . $html->link(' ' . $part . ' ', array('action' => 'index', $path));
		endforeach;
		echo '/ ' . h($current);
	?>
</h4>

<?php
	if (isset($data['Content'])) :
		echo $this->render('view', false);
	else:
?>
	<?php echo $this->element('project_details'); ?>

	<table cellpadding="0" cellspacing="0">
		<tr>
			<th style="padding-left: 28px"><?php __('Name');?></th>
			<th><?php __('Author');?></th>
			<th><?php __('Message');?></th>
			<th><?php __('Date');?></th>
			<th><?php __('Commit');?></th>
		</tr>
	<?php
		$i = 0;
		foreach ((array)$data['Folder'] as $item):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="zebra"';
			}
	?>
			<tr<?php echo $class?>>
				<td nowrap><?php echo $html->link($item['name'], array('action' => 'index', $item['path']), array('class' => 'folder'));?></td>
				<td nowrap><?php echo $item['info']['author'];?></td>
				<td class="message"><?php echo $item['info']['message'];?></td>
				<td nowrap class="date"><?php echo (!empty($item['info']['date'])) ? date("F d Y", strtotime($item['info']['date'])) : null;?></td>
				<td><?php echo $chaw->commit($item['info']['revision'], true);?></td>
			</tr>
	<?php
		endforeach;
	?>

	<?php
		foreach ((array)$data['File'] as $item):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="zebra"';
			}
	?>
			<tr<?php echo $class?>>
				<td nowrap><?php echo $html->link($item['name'], array('action' => 'index', $item['path']), array('class' => 'file'));?></td>
				<td nowrap><?php echo $item['info']['author'];?></td>
				<td class="message"><?php echo $item['info']['message'];?></td>
				<td nowrap class="date"><?php echo (!empty($item['info']['date'])) ? date("F d Y", strtotime($item['info']['date'])) : null;?></td>
				<td><?php echo $chaw->commit($item['info']['revision'], true);?></td>
			</tr>
	<?php
		endforeach;
	?>

	</table>
<?php endif;?>
</div>