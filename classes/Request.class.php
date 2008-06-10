<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Class for general request handling
 *
 * @package    Minion
 * @subpackage Classes
 */
class Request {
	/**
	 * Instance of the Request class
	 */
	static $_instance = null;
	
	/**
 	 * Object containing all get, post, and cookie variables
	 */
	public $params = null;

	/**
 	 * Controller that is handling request
	 */
	public $controller = 'default';

	/**
	 * Action that is handling request
	 */
	public $action = 'default';

	/**
	 * Parameters that will be passed to the controller action
	 */
	public $actionParams = array();

	/**
	 * Request Constructor
	 *
	 * @return 	object
	 */
	public function __construct() {
		$this->config = $GLOBALS['config'];
		
		$this->params = new stdClass;
		foreach ($_GET as $key => $val) {
			$this->params->get->$key = $val;
		}

		foreach ($_POST as $key => $val) {
			$this->params->post->$key = $val;
		}

		foreach ($_COOKIE as $key => $val) {
			$this->params->cookie->$key = $val;
		}

		if ($this->config->url->cleanurl == true) {
			$this->parseURI();
		}
	}
	
	/**
	 * Retrieve the current instance of the request class
	 *
	 * @return 	object
	 */
	public function getInstance() {
		if (!(self::$_instance instanceof Request)) {
			self::$_instance = new Request();
		}
		return self::$_instance;
	}
	
	/**
	 * Parse URI for useful data
	 *
	 * @access	public
	 * @return	void
	 */
	public function parseURI() {
		$uri = str_replace($this->config->url->base, '', $_SERVER['REQUEST_URI']);
        $params = explode('/', trim($uri));
		
		foreach ($params as $param) {
			if (empty($param)) {
				continue;
			}

			if (strpos($param, $this->config->url->separator) === false) {
				if (!@in_array($param, $this->config->url->reserved)) {
					if (!isset($this->controller) && file_exists($this->config->dir->modules . DS . $param)) {
						$this->setController($param);
					} else if (!isset($this->action) && isset($this->controller)) {
						$this->setAction($param);
					} else {
						$this->actionParams[] = $param;
					}
				} else {
					$this->{$param} = true;
				}
			} else {
				$pieces = explode($this->config->url->separator, trim($param));
				$this->params->get->{$pieces[0]} = $pieces[1];
			}
		}
	}

	/**
	 * Set the controller being used for the current request
	 *
	 * @access	public
	 * @param	string	$controller				Name of the controller to be used
	 * @return	bool
	 */
	public function setController($controller) {
		$controller = filter_var($controller, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		if (file_exists($this->config->dir->modules . DS . $controller . DS . 'controller.php')) {
			$this->controller = $controller;
			return true;
		}
		return false;
	}

	/**
	 * Set the action being used for the current request
	 *
	 * @access	public
	 * @param	string	$action					Name of the action to be called
	 * @return	bool
	 */
	public function setAction($action) {
		$action = filter_var($action, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$this->action = $action;
		return true;
	}
	
	/**
	 * Handle an incoming request and pass it along to the correct
	 * controller if found.
	 *
	 * @access	public
	 * @return	void
	 */
	public function handleRequest() {
		if (!empty($this->controller) && file_exists($this->config->dir->modules . DS . $this->controller . DS . 'controller.php')) {
			require_once $this->controller . DS . 'controller.php';
			$classname = $this->controller . 'Controller';
			$controller = new $classname();
		} else {
			System::fatalError($this->controller . 'does not exist.  Request failed.', __FUNCTION__, __CLASS__);
		}

		$method = $this->action . 'Action';
		if (method_exists($controller, $method)) {
			call_user_func_array(array($controller, $method), $this->actionParams);
		} else {
			System::fatalError($this->action . ' not defined within ' . $this->controller . ' controller.', __FUNCTION__, __CLASS__);
		}
	}

	/**
	 * Forward the request to another contoller/action to be handled
	 *
	 * @access	public
	 * @param	string	$controller				Name of the controller to forward the request to
	 * @param	string	$action					Name of the action to forward the request to
	 * @return	void
	 */
	public function forward($controller, $action = null) {
		$this->setController($controller);
		if (!is_null($action)) {
			$this->setAction($action);
		}
		$this->handleRequest();
	}
	
	/**
	 * Determine if the current request is a POST request
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isPost() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return true;
		}
		return false;
	}
	
	/**
	 * Determine if the current request is behind handled through AJAX
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isAjax() {
		return $this->ajax === true ? true : false;
	}
		
	/**
	 * Redirect the current request to the given url
	 *
	 * @static
	 * @access	public
	 * @param	string	$url				URL to redirect request to
	 * @return	void
	 */
	static public function redirect($url = null) {
		if (is_null($url)) {
			$url = $_SERVER['HTTP_REFERER'];
		}
		if (!empty($url)) {
			header('Location: ' . $url);
			exit;			
		}
	}	
	
	/**
	 * Validate and retrieve a request variable.  If the value is not of the requested
	 * type then the boolean value false is returned instead.
	 *
	 * @access	public
	 * @param	string	$type				Variable type to retrieve (get, post, cookie, etc.)
	 * @param	string	$varname			Name of the variable to retrieve
	 * @param	string	$validation			Type of validation to perform on the value
	 * @param	array 	$options			Array of options for the validation
	 * @return 	mixed
	 */
	public function getVariable($type, $varname, $validation, $options = array()) {
		// Figure out which array of variables we're dealing with
		$variables = ${'_' . strtoupper($type)};
		
		// Check to see if the variable is set, if not just return false now
		if (!isset($variables[$varname])) {
			return false;
		}
		
		return filter_var($variables[$varname], $validator, $options);		
	}
}
