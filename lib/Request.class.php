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
	static protected $instance = null;
	
	/** Copy of the config data to make it easier to get to */
	protected $config = null;

	/**
 	 * Object containing all get, post, and cookie variables
	 */
	public $params = null;

	/**
	 * Defined routes
	 */
	static protected $routes = array();

	/**
	 * Route being used for the current request
	 */
	public $route = null;
	
	/**
 	 * Controller that is handling request
	 */
	public $controller = 'default';

	/**
	 * Action that is handling request
	 */
	public $action = 'default';

	/**
	 * Request Constructor
	 *
	 * @return 	object
	 */
	public function __construct() {
		$this->config = $GLOBALS['config'];
		
		$this->params = new stdClass;
		foreach ($_GET as $key => $val) {
			$this->params->get[$key] = $val;
		}

		foreach ($_POST as $key => $val) {
			$this->params->post[$key] = $val;
		}

		foreach ($_COOKIE as $key => $val) {
			$this->params->cookie[$key] = $val;
		}

		if (isset($this->config->app->defaultController)) {
			$this->setController($this->config->app->defaultController);
		}

		if (isset($this->config->app->defaultAction)) {
			$this->setAction($this->config->app->defaultAction);
		}

		$this->loadRoutes();
		$this->routeRequest();
	}
	
	/**
	 * Retrieve the current instance of the request class
	 *
	 * @access	public
	 * @return 	object
	 */
	public function getInstance() {
		if (!(self::$instance instanceof Request)) {
			self::$instance = new Request();
		}
		return self::$instance;
	}

	/**
	 * Load all defined routes
	 *
	 * @access	public
	 * @return	bool
	 */
	public function loadRoutes() {
		// Clear all existing routes before loading
		self::$routes = array();

		if (file_exists($this->config->url->routes)) {
			include $this->config->url->routes;
		}
	}

	/**
	 * Register a new route
	 *
	 * @access	public
	 * @param	string	$regex					Regex used to match the given route
	 * @param	array	$options				Options for the given route
	 * @return	void
	 */
	public function registerRoute($regex, $options = array()) {
		self::$routes[] = array_merge(array('regex' => $regex), $options);
	}

	/**
	 * Parse URI for useful data
	 *
	 * @access	public
	 * @return	void
	 */
	public function routeRequest() {
		// Clear the base URL path from the URI before doing any parsing
		$uri = str_replace($this->config->url->base, '', $_SERVER['REQUEST_URI']);

		// Check to see if index.php is in the url, if so, scrap it	
		if (strpos($uri, '/index.php') == 0) {
			$uri = str_replace('/index.php', '', $uri);
		}

		// Cleanup any repeating slashes
		$uri = preg_replace('#/+#', '/', $uri);

		// Make sure our uri starts with a /
		if ($uri[0] != '/') {
			$uri = '/' . $uri;
		}

		// Pull off the query string if it exists
		if (preg_match('#\?(.*?)$#', $uri, $matches)) {
			parse_str($matches[1], $params);
			foreach ($params as $key => $val) {
				$queryParams[$key] = $val;
			}
			$uri = preg_replace('#\?.*?$#', '', $uri);
		}

		foreach (self::$routes as $key => $route) {
			if (preg_match('#^' . $route['regex'] . '(?<params>/.*?)?$#i', $uri, $matches)) {
				$this->route = $key;

				preg_match_all('#\(.*?<(.*?)>.*?\)#', $route['regex'], $keys);
				foreach ($keys[1] as $key) {
					if ($key == 'controller') {
						$this->setController($matches[$key]);
					} else if ($key == 'action') {
						$this->setAction($matches[$key]);
					} else {
						$this->params->get[$key] = $matches[$key];
					}
				}

				if (!empty($matches['params'])) {
					if ($route['parseParams'] !== false) {
						$params = explode('/', $matches['params']);
						
						// Toss the first param as it should be empty
						array_shift($params);

						foreach ($params as $param) {
							if (strpos($param, $this->config->url->separator) !== false) {
								$parts = explode($this->config->url->separator, $param);
								if (in_array($parts[0], $this->config->url->reserved)) {
									$this->{$parts[0]} = $parts[1];
								} else {
									$this->params->get[$parts[0]] = $parts[1];
								}
							} else {
								$this->params->get[] = $param;
							}
						}
					} else {
						$this->params->get[] = $matches['params'];
					}
				}

				if (isset($route['controller'])) {
					$this->setController($route['controller']);
				}

				if (isset($route['action'])) {
					$this->setAction($route['action']);
				}

				// If we found a route, stop now
				break;
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
			$controller->$method($this->params->get);
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
