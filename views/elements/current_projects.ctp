<?php
	if (empty($projects)) {
		return false;
	}

	if (!empty($projects)):

		$li = null;
		foreach ((array)$projects as $project):

			$url = null;
			if ($project['Project']['id'] != 1) {
				$url = $project['Project']['url'];
			}
			$fork = null;
			if (!empty($project['Project']['fork'])) {
				$fork = $project['Project']['fork'];
			}
			$username = null;
			if ($url && !$fork) {
				$username = $project['Project']['username'];
			}

			$li .= $html->tag('li',
				$html->link($project['Project']['name'], array(
					'admin' => false, 'username' => $username, 'project' => $url, 'fork'=> $fork,
					'controller' => 'source', 'action' => 'index',
				))
				. $html->tag('span',
					$html->link('remove', array(
						'admin' => false,
						'controller' => 'projects', 'action' => 'remove',
						$project['Project']['id']
					)),
				array('class' => 'small right'))
			);

		endforeach;

		echo $html->tag('div',
			$html->tag('h4', __('Projects',true)) .$html->tag('ul', $li),
			array('class' => 'panel', 'escape' => false)
		);

	endif;
?>