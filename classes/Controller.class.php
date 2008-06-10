<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */

/**
 * General controller class meant to be extended by modules 
 * 
 * @package    Minion
 * @subpackage Classes
 */
class Controller {
	/** Instance of the view to make things a little easier */
	protected $_view = null;
	
	/** Copy of the config data to make it easier to get to */
	protected $_config = null;
	
	/** Constructor */
	public function __construct() {
		$this->_request = Request::getInstance();
		$this->_view = View::getInstance();
		$this->_config = &$GLOBALS['config'];
	}
}
