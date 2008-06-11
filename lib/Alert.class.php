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
class Alert {
	/**
	 * Add an alert onto the stack
	 *
	 * @static
	 * @access	public
	 * @param	string	$message			Message to add to the stack
	 * @return 	void
	 */
	static public function addAlert($message) {
		$_SESSION['alerts'][] = $message;
	}
}