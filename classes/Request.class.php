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
	 * Request Constructor
	 *
	 * @return 	object
	 */
	public function __construct() {
		$this->_config = $GLOBALS['config'];
		
		if ($this->_config->url->cleanurl == true) {
			$this->parseURI();
		}
	}
	
	/**
	 * Retrieve the current instance of the request class
	 *
	 * @return 	object
	 */
	public function getInstance() {
		if (self::$_instance instanceof Request) {
			return self::$_instance;
		} else {
			self::$_instance = new Request();
			return self::$_instance;
		}
	}
	
	/**
	 * Parse URI for useful data
	 *
	 * @access	public
	 * @return	void
	 */
	public function parseURI() {
		$uri = str_replace($this->_config->url->base, '', $_SERVER['REQUEST_URI']);
        $params = explode('/', trim($uri));
		
		$_REQUEST['params'] = array();
	
		foreach ($params as $param) {
			$param = filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
			if (empty($param)) {
				continue;
			}

			if (strpos($param, $this->_config->url->separator) === false) {
				if (!@in_array($param, $this->_config->url->reserved)) {
					if (!isset($_REQUEST['module']) && file_exists($this->_config->dir->modules . DS . $param)) {
						$_REQUEST['module'] = $param;
					} else if (!isset($_REQUEST['action']) && isset($_REQUEST['module'])) {
						$_REQUEST['action'] = $param;
					} else if (!isset($_REQUEST['id']) && isset($_REQUEST['action'])) {
						$_REQUEST['id'] = $param;
					}
					$_REQUEST['params'][] = $param;
				} else {
					$this->{$param} = true;
				}
			} else {
				$pieces = explode($this->_config->url->separator, trim($param));
				$_REQUEST[$pieces[0]] = $pieces[1];
				$_REQUEST['params'][] = $param;
			}
		}
	}
	
	/**
	 * Handle an incoming request and pass it along to the correct
	 * controller if found.
	 *
	 * @access	public
	 * @return	void
	 */
	public function handleRequest() {
		global $config;
		
		// Retrieve and render the page
		$view = View::getInstance();

		if (!empty($_REQUEST['module']) && file_exists($config->dir->modules . DS . $_REQUEST['module'] . DS . 'controller.php')) {
			include_once $_REQUEST['module'] . DS . 'controller.php';
			$classname = $_REQUEST['module'] . 'Controller';
			$controller = new $classname();
		} else {
			if (file_exists($config->dir->modules . DS . 'index' . DS . 'controller.php')) {
				include_once($config->dir->modules . DS . 'index' . DS . 'controller.php');
				$controller = new indexController();
			} else {
				System::fatalError('No index controller present', __FUNCTION__, __CLASS__);
			}
		}

		if (!empty($_REQUEST['action']) && method_exists($controller, $_REQUEST['action'] . 'Action')) {
			$method = $_REQUEST['action'] . 'Action';
			$controller->$method();
		} else if (method_exists($controller, 'indexAction')) {
			$controller->indexAction();
		} else {
			System::fatalError('No default action present for current controller', __FUNCTION__, __CLASS__);
		}
		
		$view->render();
	}
	
	/**
	 * Determine if the current request is a POST request
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isPostRequest() {
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
	public function isAjaxRequest() {
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
	static public function redirectRequest($url = null) {
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
