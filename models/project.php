<?php
/**
 * Short description
 *
 * Long description
 *
 * Copyright 2008, Garrett J. Woodworth <gwoo@cakephp.org>
 * Redistributions not permitted
 *
 * @copyright		Copyright 2008, Garrett J. Woodworth
 * @package			chaw
 * @subpackage		chaw.models
 * @since			Chaw 0.1
 * @license			commercial
 *
 */
class Project extends AppModel {
	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $name = 'Project';

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $current = array();

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $Repo;

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $hooks = array(
		'git' => array('pre-receive', 'post-receive'),
		'svn' => array('pre-commit', 'post-commit')
	);

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $repoTypes = array('Git'/*, 'Svn'*/);

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $messages = array('response' => null, 'debug' => null);

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $validate = array(
		'name' => array(
			'minimum' => array(
				'rule' => array('minLength', 5),
				'required' => true,
				'message' => 'Must be at least 5 characters'
			),
			'unique' => array(
				'rule' => 'isUnique',
				'required' => true,
				'message' => 'Required: Project must be unique'
			)
		),
		'user_id' => array('notEmpty')
	);

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $belongsTo = array('User');

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $hasMany = array('ProjectPermission');

	/**
	 * undocumented class variable
	 *
	 * @var string
	 */
	var $__created = false;

	/**
	 * undocumented function
	 *
	 * @param string $params
	 * @return void
	 */
	function initialize($params = array()) {
		$this->recursive = -1;
		$this->current = Configure::read('Project');

		if (!empty($this->data['Project']['url'])) {
			$params['project'] = $this->data['Project']['url'];
		}
		if (!empty($this->data['Project']['fork'])) {
			$params['fork'] = $this->data['Project']['fork'];
		}
		if (!empty($this->data['Project']['username'])) {
			$params['username'] = $this->data['Project']['username'];
		}

		if (!empty($params['project'])) {
			extract($this->key($params));
			$project = Cache::read($key, 'project');
			if (empty($project)) {
				$project = $this->find($conditions);
				if (!empty($project)) {
					Cache::write($key, $project, 'project');
				}
			}
		}
		if (empty($project)) {
			$key = Configure::read('App.dir');
			$project = Cache::read($key, 'project');
			if (empty($project)) {
				$project = $this->findById(1);
				if (!empty($project)) {
					Cache::write($key, $project, 'project');
				}
			}
		}

		if (!empty($this->data['Project'])) {
			if (empty($project)) {
				$project = array('Project' => array());
			}
			$project['Project'] = array_merge($project['Project'], $this->data['Project']);
		}

		if (empty($project['Project'])) {
			Configure::write('Project', $this->current);
			return false;
		}

		$this->current = array_merge($this->current, $project['Project']);

		$repoType = strtolower($this->current['repo_type']);

		if (!empty($this->data['Project']['remote'])) {
			$this->current['remote'][$repoType] = $this->data['Project']['remote'];
		}
		if (is_string($this->current['remote'])) {
			$this->current['remote'][$repoType] = $this->current['remote'];
		}

		$path = Configure::read("Content.{$repoType}");

		$fork = null;
		if (!empty($this->current['fork'])) {
			$fork = 'forks' . DS . $this->current['fork'] . DS;
		}

		$username = null;
		if (!empty($this->current['username'])) {
			$username = $this->current['username'] . DS;
		}

		$this->current['repo'] = array(
			'class' => 'repo.' . $this->current['repo_type'],
			'type' => $repoType,
			'chmod' => 0777,
			'path' => $path . 'repo' . DS . $username . DS . $fork . $this->current['url'],
			'working' => $path . 'working' . DS . $username . DS . $fork . $this->current['url'],
			'remote' => is_string($this->current['remote']) ? $this->current['remote'] : $this->current['remote'][$repoType]
		);

		if ($repoType == 'git') {
			$this->current['repo']['path'] .= '.git';
		}

		if (!empty($this->current['config']) && is_string($this->current['config']) && substr($this->current['config'], 0, 3) == "a:1") {
			$this->current['config'] = unserialize($this->current['config']);
		}

		$this->id = $this->current['id'];
		Configure::write('Project', $this->current);
		
		App::import('Model', $this->current['repo']['class'], false);
		$this->Repo = new $this->current['repo_type']($this->current['repo']);
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	function beforeValidate() {
		if (!empty($this->data['Project']['name']) && empty($this->data['Project']['url'])) {
			$this->data['Project']['url'] = Inflector::slug(strtolower($this->data['Project']['name']));
		} else if (empty($this->data['Project']['name']) && !empty($this->data['Project']['url'])) {
			$this->data['Project']['name'] = $this->current['name'];
		}
		if (empty($this->data['Project']['username'])) {
			$this->current['username'] = User::get('username');
		}
		if (!empty($this->data['Project']['id']) && $this->id == $this->data['Project']['id']) {
			unset($this->validate['name']['minimum']);
		}
		return true;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	function beforeSave() {
		$this->createShell();
		if (empty($this->data['Project']['fork'])) {
			$this->data['Project']['fork'] = null;
		}

		if (empty($this->data['Project']['username'])) {
			$this->invalidate('name', 'the project could not be created, you need to setup a nickname in your account settings');
			return false;
		}

		if (empty($this->data['Project']['url'])) {
			$this->invalidate('name', 'the project could not be created');
			return false;
		}

		if (!empty($this->data['config'])) {
			$this->data['Project']['config'] = array_merge($this->data['Project']['config'], $this->data['config']);
			unset($this->data['config']);
		}

		if (!empty($this->data['Project']['config']) && is_array($this->data['Project']['config'])) {
			$this->data['Project']['config'] = serialize($this->data['Project']['config']);
		}

		if (!empty($this->data['Project']['approved'])) {
			if ($this->initialize() === false) {
				return false;
			}

			if (!file_exists($this->current['repo']['path']) || !file_exists($this->current['repo']['working'])) {
				$this->__created = $this->Repo->create(array(
					'remote' => $this->current['repo']['remote'],
				));

				if ($this->__created !== true) {
					$this->invalidate('repo_type', 'the repo could not be created');
					return false;
				}
			}
		}

		if ($this->__created && !$this->id && (empty($this->data['Project']['username']) || empty($this->data['Project']['user_id']))) {
			$this->invalidate('user', 'Invalid user');
			return false;
		}

		return true;
	}

	/**
	 * undocumented function
	 *
	 * @param string $created
	 * @return void
	 */
	function afterSave($created) {
		if (!empty($this->data['Project']['approved'])) {

			$this->current['id'] = $this->id;

			$hooksCreated = $this->createHooks($this->hooks[$this->Repo->type], array(
				'username' => $this->data['Project']['username'],
				'project' => $this->data['Project']['url'],
				'fork' => (!empty($this->data['Project']['fork'])) ? $this->data['Project']['fork'] : false,
				'root' => Configure::read('Content.base')
			));

			if ($this->__created && $hooksCreated) {
				foreach ($this->hooks[$this->Repo->type] as $hook) {
					if ($hook === 'post-commit') {
						$this->Repo->execute("env - {$this->Repo->path}/hooks/{$hook} {$this->Repo->path} 1");
					}

					if ($hook === 'post-receive') {
						$this->Repo->execute("env - {$this->Repo->path}/hooks/{$hook} refs/heads/master");
					}
				}
			}

			$this->messages = array('response' => $this->Repo->response, 'debug' => $this->Repo->debug);

			$this->ProjectPermission->config($this->current);
			if ($this->ProjectPermission->fileExists() !== true) {
				$this->ProjectPermission->saveFile(array('ProjectPermission' => array(
					'username' => "@admin"
				)));
			}

			$this->permit(array(
				'user' => $this->current['user_id'],
				'group' => 'admin',
				'count' => 1
			));
		}

		if (!empty($this->current['url'])) {
			$conditions = array($this->current['url']);
			$conditions[] = (!empty($this->current['fork'])) ? $this->current['fork'] : false;
			$key = join('_', array_filter($conditions));
			Cache::delete($key, 'project');
		}

		$this->__created = false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	function afterDelete() {
		$CleanUp = new Folder($this->Repo->path);
		if ($CleanUp->pwd() == $this->Repo->path /*&& strpos($this->Repo->path, 'forks') !== false*/) {
			$CleanUp->delete();

			$CleanUp = new Folder($this->Repo->working);
			if ($CleanUp->pwd() == $this->Repo->working) {
				$CleanUp->delete();
			}
			$key = $this->key();
			if (!empty($key)) {
				Cache::delete($key['key'], 'project');
			}
		}
	}

	/**
	 * undocumented function
	 *
	 * @param string $hooks
	 * @param string $options
	 * @return void
	 */
	function createHooks($hooks, $options = array()) {
		extract(array_merge(array('project' => null, 'fork' => null, 'root' => null), $options));
		$result = array();
		if (!empty($hooks)) {
			foreach ((array)$hooks as $hook) {
				if (!file_exists("{$this->Repo->path}/hooks/{$hook}")) {
					$result[] = $this->Repo->hook($hook, array('username' => $username, 'project' => $project, 'fork' => $fork, 'root' => $root));
				}
			}
		}
		return $result;
	}

	/**
	 * undocumented function
	 *
	 * @param string $fields 
	 * @param string $id 
	 * @return void
	 */
	function read($fields = null, $id = null) {
		$data = parent::read($fields, $id);
		$data['Project']['config'] = unserialize($data['Project']['config']);
		return $data;
	}

	/**
	 * undocumented function
	 *
	 * @param string $data
	 * @return void
	 */
	function createShell($data = array()) {

		$template = CONFIGS . 'templates' . DS;
		$chaw = Configure::read('Content.base');

		if (file_exists($template . 'chaw') && !file_exists($chaw . 'chaw')) {
			$console = array_pop(Configure::corePaths('cake')) . 'console' . DS;
			ob_start();
			include($template . 'chaw');
			$data = ob_get_clean();

			$File = new File($chaw . 'chaw', true, 0775);
			@chmod($File->pwd(), 0775);
			return $File->write($data);
		}

		return true;
	}

	/**
	 * undocumented function
	 *
	 * @param string $data
	 * @return void
	 */
	function fork($data = array()) {
		$this->set($data);

		if (empty($this->Repo)) {
			return false;
		}

		$hasFork = $this->find(array(
			'fork' => $this->data['Project']['fork'],
			'url' => $this->current['url']
		));
		if (!empty($hasFork)) {
			return false;
		}

		if ($this->Repo->fork($this->data['Project']['fork'], array('remote' => $this->current['repo']['remote']))) {
			$this->__created = true;
			$this->data['Project']['project_id'] = $this->id;
			$this->data['Project']['name'] = $this->data['Project']['fork'] . "'s fork of " . $this->data['Project']['name'];
			$this->data['Project']['username'] = $this->data['Project']['fork'];
			$this->data['Project']['users_count'] = 1;
		}
		if (!empty($this->data['Project']['id'])) {
			$this->id = null;
			unset($this->data['Project']['id'], $this->data['Project']['created'], $this->data['Project']['modified']);
		}

		if ($data = $this->save()) {
			$this->id = $data['Project']['project_id'];
			$this->permit(array(
				'user' => $data['Project']['user_id'],
				'id' => $data['Project']['project_id'],
			));
			return $data;
		}
		return false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	function forks() {
		$forks = $this->find('all', array(
			'conditions' => array('Project.project_id' => $this->id),
			'recursive' => -1
		));
		return Set::extract('/Project/id', $forks);
	}

	/**
	 * undocumented function
	 *
	 * @param string $id
	 * @param string $include
	 * @return void
	 */
	function all($id = null, $include = true) {
		if (!$id) {
			$id = $this->id;
		}

		if (!empty($this->current['fork'])) {
			$id = $this->current['project_id'];
		}

		$conditions = array('Project.project_id' => $id);

		if ($include) {
			$conditions = array(
				'OR' => array('Project.id' => $id, 'Project.project_id' => $id)
			);
		}

		return $this->find('all', compact('conditions'));
	}

	/**
	 * undocumented function
	 *
	 * @param string $conditions
	 * @return void
	 */
	function users($conditions = array()) {
		$scope = array('ProjectPermission.project_id' => $this->id, 'User.username !=' => null);
		if (!empty($conditions)) {
			$scope = array_merge($scope, (array)$conditions);
		}
		$users = $this->ProjectPermission->find('all', array(
			'fields' => array('User.id', 'User.username'),
			'conditions' => $scope
		));

		if (empty($users)) {
			return array();
		}

		return array_filter(Set::combine($users, '/User/id', '/User/username'));
	}

	/**
	 * undocumented function
	 *
	 * @param string $user
	 * @param string $group
	 * @return void
	 */
	function permit($user, $group = null) {
		$id = $this->id;
		$count = 'Project.users_count + 1';

		if (is_array($user)) {
			extract($user);
		}

		$user = $this->ProjectPermission->user($user);

		if (!$user || !$id) {
			return false;
		}

		if (!$this->ProjectPermission->field('id', array('project_id' => $id, 'user_id' => $user))) {
			$this->ProjectPermission->create(array(
				'project_id' => $id,
				'user_id' => $user,
				'group' => $group
			));
			$this->ProjectPermission->save();

			$this->recursive = -1;
			$this->updateAll(
				array('Project.users_count' => $count),
				array('Project.id' => $id)
			);
		}
	}

	/**
	 * undocumented function
	 *
	 * @param string $data
	 * @param string $options
	 * @return void
	 */
	function isUnique($data, $options = array()) {
		if (!empty($data['name'])) {
			$test = $this->findByUrl(Inflector::slug(strtolower($data['name'])));
			if (!empty($test) && $test['Project']['id'] != $this->id) {
				return false;
			}
			return true;
		}
		if (!empty($data['url'])) {
			$reserved = array('forks', 'users', 'commits', 'dashboard', 'pages', 'project_permissions', 'repo', 'source', 'timeline');
			if (in_array($data['url'], $reserved)) {
				$this->invalidate('name');
				return false;
			}
			$test = $this->findByUrl($data['url']);
			if (in_array($data['url'], $reserved) || !empty($test) && $test['Project']['id'] != $this->id) {
				$this->invalidate('name');
				return false;
			}
			return true;
		}
	}

	/**
	 * undocumented function
	 *
	 * @param string $key
	 * @return void
	 */
	function groups($key = null) {
		$result = array();
		if (!empty($this->current['config']['groups'])) {
			$groups = array_map('trim', explode(',', $this->current['config']['groups']));
			$Inflector = Inflector::getInstance();
			$groups = array_map(array($Inflector, 'slug'), $groups, array_fill(0, count($groups), '-'));
			$result = array_combine($groups, $groups);
		}
		if (!isset($result['admin'])) {
			$result['admin'] = 'admin';
		}
		if (!isset($result['user'])) {
			$result['user'] = 'user';
		}
		arsort($result);
		return $result;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	function repoTypes() {
		return array_combine($this->repoTypes, $this->repoTypes);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	function from() {
		$baseDomain = env('HTTP_BASE');
		if ($baseDomain[0] === '.') {
			$baseDomain = substr($baseDomain, 1);
		}
		$from = sprintf('<noreply@%s>', $baseDomain);
		return $from;
	}

	/**
	 * undocumented function
	 *
	 * @param string $params
	 * @return void
	 */
	function key($params = array()) {
		if (empty($params)) {
			$params = $this->current;
		}
		if (empty($params['project'])) {
			if (empty($params['url'])) {
				return array();
			}
			$params['project'] = $params['url'];
		}
		$conditions['fork'] = null;
		if (!empty($params['fork'])) {
			$conditions['fork'] = $params['fork'];
		}
		$conditions['username'] = null;
		if (!empty($params['username'])) {
			$conditions['username'] = $params['username'];
		}
		$conditions['url'] = $params['project'];
		$key = join('_', array_filter(array_values($conditions)));
		return compact('key', 'conditions');
	}
}
?>