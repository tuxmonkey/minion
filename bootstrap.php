<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Bootstrap
 */
// Pull in config file
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Setup our include path
set_include_path($config->dir->lib 
	. PATH_SEPARATOR . $config->dir->modules
	. PATH_SEPARATOR . $config->dir->helpers
	. PATH_SEPARATOR . $config->dir->vendors
	. PATH_SEPARATOR . get_include_path());

// Load all system classes
$classes = glob($config->dir->lib . DS . '*.class.php');
foreach ($classes as $class) {
	require_once $class;
}

// Define our auto loader
#spl_autoload_register(array('System', 'autoload'));

// Pull in any module specific config files
System::hook('config', array($config));

// Initialize instead of request class
$request = Request::getInstance();

// Set wrapper header and footer
$view = View::getInstance();

// Pull in any module specific init scripts
System::hook('init', $GLOBALS);
