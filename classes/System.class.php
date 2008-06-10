<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */

/**
 * @package    Minion
 * @subpackage Classes
 */
class System {
	/**
	 * Log a message to the error log formatted for our application
	 *
	 * @access	public
	 * @param	string	$message			Message to be sent to the error log
	 * @param	string	$funcname			Name of the function to log for
	 * @param	string	$classname			Name of the class to log for
	 * @return	void
	 */
	static public function logMessage($message, $funcname = null, $classname = null) {
		$log = '[' . $GLOBALS['config']->app->name . '] ';
		$log .= !is_null($classname) ? $classname . '::' : '';
		$log .= !is_null($funcname) ? $funcname . ' => ' : '';
		$log .= $message;
		error_log($log);
	}

	/**
	 * Log a warning to the error log formatted for our application
	 *
	 * @access	public
	 * @param	string	$message			Message to be sent to the error log
	 * @param	string	$funcname			Name of the function to log for
	 * @param	string	$classname			Name of the class to log for
	 * @return	void
	 */
	static public function logWarning($message, $funcname = null, $classname = null) {
		$log = '[' . $GLOBALS['config']->app->name . '] ';
		$log .= !is_null($classname) ? $classname . '::' : '';
		$log .= !is_null($funcname) ? $funcname . ' => ' : '';
		$log .= $message;
		trigger_error($log, E_USER_WARNING);
	}
	
	/**
	 * Log a debug message to the error log formatted for our application
	 *
	 * @access	public
	 * @param	string	$message			Message to be sent to the error log
	 * @param	string	$funcname			Name of the function to log for
	 * @param	string	$classname			Name of the class to log for
	 * @return	void
	 */
	static public function debugMessage($message, $funcname = null, $classname = null) {
		$log = '[' . $GLOBALS['config']->app->name . '][DEBUG] ';
		$log .= !is_null($classname) ? $classname . '::' : '';
		$log .= !is_null($funcname) ? $funcname . ' => ' : '';
		$log .= $message;
		trigger_error($log, E_USER_NOTICE);
	}
	
	/**
	 * Log a fatal error message to the error log formatted for our application
	 *
	 * @access	public
	 * @param	string	$message			Message to be sent to the error log
	 * @param	string	$funcname			Name of the function to log for
	 * @param	string	$classname			Name of the class to log for
	 * @return	void
	 */
	static public function fatalError($message, $funcname = null, $classname = null) {
		$log = '[' . $GLOBALS['config']->app->name . '][FATAL] ';
		$log .= !is_null($classname) ? $classname . '::' : '';
		$log .= !is_null($funcname) ? $funcname . ' => ' : '';
		$log .= $message;
		trigger_error($log, E_USER_ERROR);
	}
		
	/**
	 * Hook method for allowing modules to tie together without module authors
	 * being required to change each other's code.
	 *
	 * @access	public
	 * @access	public
	 * @param	string	$hookname			Name of the hook we should look for
	 * @param	array 	$variables			Variables other than the view ones that should be extracted into scope
	 * @return 	void
	 */
	static public function hook($hookname, $variables = array()) {
		# Extract variables into scope
		if (is_array($variables)) {
			extract($variables, EXTR_REFS);			
		}
		
		$files = glob($_config->dir->modules . DS . '*' . DS . 'hooks'
			. DS . 'system' . DS . '*' . $hookname . '.php');
		if (is_array($files)) {
			foreach ($files as $file) {
				include $file;
			}
		}
	}
	
	/**
	 * Class auto loader
	 *
	 * @static
	 * @access	public
	 * @param	string	$class 				Name of class to be loaded
	 * @return 	void
	 */
	static public function autoload($class) {
		global $config;

		if (file_exists($config->dir->classes . DS . $class . '.class.php')) {
			include_once $config->dir->classes . DS . $class . '.class.php';
		}
		$files = glob($config->dir->modules . DS . '*' . DS . $class . '.model.php');
		if (is_array($files)) {
			foreach ($files as $file) {
				include_once $file;
			}
		}
		$files = glob($config->dir->modules . DS . '*' . DS . $class . '.class.php');
		if (is_array($files)) {
			foreach ($files as $file) {
				include_once $file;
			}
		}
	}
}
