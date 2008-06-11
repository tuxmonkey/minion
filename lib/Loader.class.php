<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Class for easily loading different types of includes into the framework
 *
 * @package    Minion
 * @subpackage Classes
 */
class Loader {
	/**
	 * Load a system class from $config->dir->classes
	 *
	 * @static
	 * @access	public
	 * @param	string	$class				Name of the class to include
	 * @return	bool
	 */
	static public function loadClass($class) {
		global $config;

		if (is_array($class)) {
			foreach ($class as $item) {
				self::loadClass($item);
			}
		} else {
			if (file_exists($config->dir->classes . DS . $class . '.class.php')) {
				return include_once $config->dir->classes . DS . $class . '.class.php';
			}
		}
		return false;
	}

	/**
	 * Load a model class from $config->dir->models
	 *
	 * @static
	 * @access	public
	 * @param	string	$model				Name of model class to include
	 * @return	bool
	 */
	static public function loadModel($model) {
		global $config;

		if (is_array($model)) {
			foreach ($model as $item) {
				self::loadModel($item);
			}
		} else {
			if (file_exists($config->dir->models . DS . $model . '.php')) {
				return include_once $config->dir->models . DS . $model . '.php';
			}
		}
		return false;
	}

	/**
	 * Load a helper class from $config->dir->helpers
	 *
	 * @static
	 * @access	public
	 * @param	string	$helper				Name of helper class to include
	 * @return	bool
	 */
	static public function loadHelper($helper) {
		global $config;

		if (is_array($helper)) {
			foreach ($helper as $item) {
				self::loadHelper($item);
			}
		} else {
			if (file_exists($config->dir->helpers . DS . $helper . '.php')) {
				return include_once $config->dir->helpers . DS . $helper . '.php';
			}
		}
		return false;
	}
}
