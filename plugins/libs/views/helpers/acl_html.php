<?php
App::import('Helper', 'Html');
/**
 * Acl Html Helper class
 * Add ACL checking to links
 *
 */
class AclHtmlHelper extends HtmlHelper {
/**
 * The session key name where the record of the current user is stored
 *
 * @var string
 * @access public
 */
    var $sessionKey = null;
/**
 * User model name
 *
 * @var string
 * @access public
 */
    var $userModel = null;
/**
 * User model primary key
 *
 * @var string
 * @access public
 */
    var $userModelPrimary = null;
/**
 * Array containing the names of helpers this helper uses
 *
 * @var array
 * @access public
 */
    var $helpers = array('Session');
/**
 * Instance of an ACL class
 *
 * @var object
 * @access protected
 */
    var $_Instance = null;
/**
 * Array containing cached permissions from the current user
 *
 * @var array
 * @access protected
 */
    var $_cache = array();
/**
 * Maps actions to CRUD operations.
 *
 * @var array
 * @access public
 * @see AuthComponent::mapActions()
 */
	var $actionMap = array(
		'index'		=> 'read',
		'add'		=> 'create',
		'edit'		=> 'update',
		'view'		=> 'read',
		'remove'	=> 'delete',
		'delete'	=> 'delete'
	);
/**
 * Sets up various vars/classes required
 *
 * @return null
 * @access public
 */
    function __construct($settings = array()) {
        $sessionKey = 'Auth';
        if (is_array($settings) && isset($settings[0])) {
            $sessionKey = $settings[0];
        } elseif (is_string($settings)) {
            $sessionKey = $settings;
        }
        $this->sessionKey = $sessionKey;
        $this->userModel = 'User';
        $user = ClassRegistry::init('User');
        $this->userModelPrimary = $user->primaryKey;
		$name = Inflector::camelize(strtolower(Configure::read('Acl.classname')));
        if (!class_exists($name)) {
            if (App::import('Component', $name)) {
                list($plugin, $name) = pluginSplit($name);
                $name .= 'Component';
            } else {
                trigger_error(sprintf(__('Could not find %s.', true), $name), E_USER_WARNING);
            }
        }
        $this->_Instance =& new $name();
        $this->_Instance->initialize($this);
        parent::__construct();
    }
/**
 * Returns a user array for use with ACL
 *
 * @return array User
 * @access protected
 */
    function _user() {
        if (!$this->Session->check($this->sessionKey)) {
            return array($this->userModel => array($this->userModelPrimary => 0));
        } else {
            $user = $this->Session->read($this->sessionKey);
            if ($user === array()) {
                return array($this->userModel => array($this->userModelPrimary => 0));
            }
            return $user;
        }
    }
/**
 * Creates an HTML link subject to an ACL check
 *
 * ### Options
 *
 * - `auth` Set to false to disable acl check
 * - `wrapper` Used to have the link appear in a block. Use $1 for the link. If the check fails, null is still returned
 *
 * @param string $passTitle The content to be wrapped by <a> tags.
 * @param mixed $passUrl Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $passOptions Array of HTML attributes.
 * @param string $passConfirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @access public
 */
    function link($passTitle, $passUrl = null, $passOptions = array(), $passConfirmMessage = false) {
        if (isset($passOptions['wrapper']) && is_string($passOptions['wrapper'])) {
            $wrapper = $passOptions['wrapper'];
        } else {
            $wrapper = '$1';
        }
        unset($passOptions['wrapper']);
        if (isset($passOptions['auth']) && $passOptions['auth'] === false) {
            unset($passOptions['auth']);
            return str_replace('$1', parent::link($passTitle, $passUrl, $passOptions, $passConfirmMessage), $wrapper);
        }
        unset($passOptions['auth']);
        if ($passUrl !== null) {
            $url = $passUrl;
        	$url['base'] = false;
        	$url = Router::url($url);
        } else {
            $url = $passTitle;
            $url['base'] = false;
            $url = Router::url($url);
        }
        $url = Router::parse($url);
        if ($url['pass'][0]) {
        	if (isset($this->actionMap[$url['action']])) {
        		$action = $this->actionMap[$url['action']];
        	}
        	if (isset($action)) {
		        $modelName = Inflector::classify($url['controller']);
		        $aco = array(
		        	$modelName => array(
		        		'id' => $url['pass'][0]
		        	)
		        );
		        if ($this->_Instance->check($this->_user(), $aco, $action)) {
		            return str_replace('$1', parent::link($passTitle, $passUrl, $passOptions, $passConfirmMessage), $wrapper);
		        }
		        return null;
        	}
        }
        if (!isset($url['controller']) || !isset($url['action'])) {
            return str_replace('$1', parent::link($passTitle, $passUrl, $passOptions, $passConfirmMessage), $wrapper);
        }
        $action = 'controllers/';
        if ($url['plugin'] !== null) {
            $action .= Inflector::camelize($url['plugin']) . '/';
        }
        $action .= Inflector::camelize($url['controller']) . '/' . $url['action'];
        if ($this->check($action)) {
            return str_replace('$1', parent::link($passTitle, $passUrl, $passOptions, $passConfirmMessage), $wrapper);
        }
        return null;
    }
/**
 * Pass-thru function for ACL check instance. Check methods
 * are used to check whether or not the current user can access an ACO
 *
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
    function check($aco, $action = '*') {
        if (!isset($this->_cache[$aco])) {
            $this->_cache[$aco] = $this->_Instance->check($this->_user(), $aco);
        }
        return $this->_cache[$aco];
    }
}
?>