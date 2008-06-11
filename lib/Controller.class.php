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
	/** Instance of the request to make things a little easier */
	protected $request = null;

	/** Instance of the view to make things a little easier */
	protected $view = null;
	
	/** Copy of the config data to make it easier to get to */
	protected $config = null;

	/** Paramaters passed in through the rest */
	public $params = null;
	
	/** Constructor */
	public function __construct() {
		$this->request = Request::getInstance();
		$this->view = View::getInstance();
		$this->config = $GLOBALS['config'];
		$this->params = $this->request->params;
	}
}
