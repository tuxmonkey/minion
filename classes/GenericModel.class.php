<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Generic model used for getting the model functionality on tables which
 * are not used often enough to warrant a full model class for.
 *
 * @package    Minion
 * @subpackage Classes
 */
class GenericModel extends Model {
	/**
	 * Class constructor
	 *
	 * @access	public
	 * @param	string	$table				Table that the generic model will wrap
	 * @param	mixed	$key				Primary key(s) for the given table
	 * @param	int		$id					Value of the primary key for the record we want.
	 * @param	string	$dbconfig			Database configuration to use
	 * @param	mixed	$ttl				Time to live for cached value, set to false to ignore cache
	 * @return	void
	 */
	public function __construct($table, $key = null, $id = null, $dbconfig = false, $ttl = false) {
		$this->_table = $table;
		if (!is_null($key)) {
			$this->_key = $key;
		}
		parent::__construct($id, $dbconfig, $ttl);
		$this->_fetchClass = null;
	}
}
