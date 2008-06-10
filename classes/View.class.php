<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */

/**
 * Generic view class used for displaying of content to the end user
 *
 * @package    Minion
 * @subpackage Classes
 * @author     Edwin <tuxmonkey@slackmaster.org>
 */
class View {
	/** View instance for the current request (only 1 instance allowed) */
	static $_instance = null;
	
	/** Variables to be expanded and used within templates */
	protected $_variables = array();
	
	/** Templates to be render for the current request */
	protected $_templates = array();
	
	/** Layout to be used for displaying the page */
	public $_layout = null;
	
	/** Placeholder for the global configuration */
	protected $_config = null;
	
	/** Request Object */
	protected $_request = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_request = Request::getInstance();
		$this->_config = &$GLOBALS['config'];
		$layout = $this->_request->isAjaxRequest() ? 'ajax.inc' : 'default.inc';
		$this->_layout = realpath(dirname(__FILE__) . DS . '..'
			. DS . 'layouts' . DS . $layout);
	}
	
	/**
	 * Retrieve existing instance of the View class if one exists.  If one
	 * does not exist create one and return it.
	 *
	 * @access	public
	 * @return	object
	 */
	public function getInstance() {
		if (self::$_instance instanceof View) {
			return self::$_instance;
		} else {
			self::$_instance = new View();
			return self::$_instance;
		}
	}
	
	/**
	 * Add a variable to the current view instance
	 *
	 * @access	public
	 * @param	string	$name			Name of variable to be set
	 * @param	mixed	$value			Value to assign to variable
	 * @return	void
	 */
	public function __set($name, $value) {
		$this->_variables[$name] = $value;
	}
	
	/**
	 * Add a template to the stack of templates to be rendered
	 *
	 * @access	public
	 * @param	string	$template		Location of template to be added
	 * @param	string	$module			Module to look for template
	 * @return	bool
	 */
	public function addTemplate($template, $module = null) {
		if (!is_null($module)) {
			$location = $this->_config->dir->modules . DS . $module 
				. DS . 'templates' . DS . $template . '.inc';
		} else {
			$location = $this->_config->dir->templates . DS . $template . '.inc';
		}

		if (file_exists($location)) {
			$this->_templates[] = $location;
			return true;
		}
		return false;
	}
	
	/**
	 * Clear template stack
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearTemplates() {
		$this->_templates = array();
	}
	
	/**
	 * Hook method for allowing modules to tie together without module authors
	 * being required to change each other's code.
	 *
	 * @access	public
	 * @param	string	$hookname			Name of the hook we should look for
	 * @param	array 	$variables			Variables other than the view ones that should be extracted into scope
	 * @return 	void
	 */
	public function hook($hookname, $variables = array()) {
		# Extract all assigned variables
		extract($this->_variables);
		extract($variables);
		
		$files = glob($this->_config->dir->modules . DS . '*' . DS
			. 'hooks' . DS . 'views' . DS . '*' . $hookname . '.inc');
		if (is_array($files)) {
			foreach ($files as $file) {
				include $file;
			}
		}
	}
	
	/**
	 * Render the templates and pass result on to the end user
	 *
	 * @access	public
	 * @return	void
	 */
	public function render() {
		# Extract all assigned variables
		extract($this->_variables);

		if (count($this->_templates) == 0) {
			$module = !empty($_REQUEST['module']) ? $_REQUEST['module'] : 'index';
			$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'index';
			$this->addTemplate($action, $module);
		}
	
		if (!empty($this->_layout)) {
			ob_start();
			foreach ($this->_templates as $template) {
				include $template;
			}
			$content = ob_get_contents();
			ob_end_clean();

			include_once $this->_layout;			
		} else {
			foreach ($this->_templates as $template) {
				include $template;
			}			
		}
	}
	
	/**
	 * Render the templates and write the end result to the given filename
	 *
	 * @access	public
	 * @param	string	$filename			Filename to write result to
	 * @return	bool
	 */
	public function write($filename) {
		ob_start();
		$this->render();
		$content = ob_get_contents();
		ob_end_clean();
		
		$result = file_put_contents($filename, $content);
		if ($result !== false) {
			return true;
		}
		return false;
	}
}
