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
set_include_path($config->dir->classes 
	. PATH_SEPARATOR . $config->dir->modules
	. PATH_SEPARATOR . $config->dir->vendors
	. PATH_SEPARATOR . get_include_path());

// Pull in system class for access to our auto loader
require_once 'System.class.php';

// Define our auto loader
spl_autoload_register(array('System', 'autoload'));

// Pull in any module specific config files
System::hook('config', $config);

// Initialize instead of request class
$request = Request::getInstance();

if ($config->url->cleanurl === true) {
	// Parse URI for useful data
	$request->parseURI();	
}

// Set wrapper header and footer
$view = View::getInstance();

// Pull in any module specific init scripts
System::hook('init', $GLOBALS);
