<?php
/**
 * $Id$
 *
 * @package    Minion
 * @subpackage Classes
 */
/**
 * Model Class
 *
 * @package    Minion
 * @subpackage Classes
 **/
class Model {
	/** Database configuration to use if not using the default */
	static protected $_dbconfig = 'default';
	
	/** Database connection being used by model */
	static protected $_db = null;
	
	/** Metadata for the model table */
	static protected $_metadata = array();
	
	/** Name of the table that is being presented by the model */
	static protected $_table = null;
	
	/** Primary Key for table model */
	static protected $_key = null;
	
	/** Enable/Disable debug mode */
	static public $_debug = false;
	
	/** Comma seperator listed of fields to be retrieved by next select query */
	static protected $_fields = null;
	
	/** Array of parameters that will be prepared in next query */
	static protected $_params = array();
	
	/** Join statements for next select query */
	static protected $_join = array();
	
	/** Where conditions to be used in the next select, update, or delete query */
	static protected $_where = array();
	
	/** Group by statements to be used in the next select query */
	static protected $_group = array();
	
	/** Order by clauses to be used in the next select query */
	static protected $_order = array();
	
	/** Limit for number of rows the next select query should return */
	static protected $_limit = null;
	
	/** Offset of rows to be used for next select query */
	static protected $_offset = null;
	
	/** Index hint to be used for next query */
	static protected $_indexHint = null;
	
	/** Optimization hint to be used for next query */
	static protected $_optHint = null;
	
	/** Whether or not to clear conditions, joins, etc on next query */
	static protected $_clearAfter = true;

	/** The number of rows affected by the last update or delete */
	static protected $_affected = 0;
	
	/**
	 * Model contructor.  When a instance of the class is loaded it will
	 * attempt to find the primary key of the table we are dealing with.
	 *
	 * @static
	 * @access	private
	 * @param	string	$dbconfig	Database configuration to use
	 * @return 	bool
	 */
	static private function _connect($dbconfig = false) {
		global $config;

		if (is_resource($this->_db)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Magic method for retrieval of rows by fields existing in the table.
	 * 
	 * Parameters for findBy<field> methods:
	 *	string	criteria			What to match the field against
	 *
	 * @access	protected
	 * @param	string	$funcname			Name of function that was called
	 * @param	array 	$params				Array of parameters that were passed
	 * @return 	mixed
	 */
	static protected function __call($funcname, $params = array()) {
		$fields = join('|', array_keys($this->_metadata->fields));
		if (preg_match('/^findBy(' . $fields . ')$/i', $funcname, $match)) {
			$field = trim($match[1]);
			$value = trim($params[0]);
			$operator = $params[1] === true ? 'like' : '=';
			self::addWhere("$field $operator ?", $value);
			return $this->findWhere();
		} else if (preg_match('/clear(.*?)$/i', $funcname, $match)) {
			$option = trim(strtolower($match[1]));
			if (empty($option)) {
				$this->_fields = null;
				$this->_join = array();
				$this->_where = array();
				$this->_group = array();
				$this->_order = array();
				$this->_limit = null;
				$this->_offset = null;
				$this->clearAfter(true);
			} else {
				switch ($option) {
					case 'fields':
					case 'limit':
					case 'offset':
						$this->{'_' . $option} = null;
						break;
					
					case 'join':
					case 'where':
					case 'group':
					case 'order':
						$this->{'_' . $option} = array();
						break;
				}
			}
		} else {
			return null;
		}
	}
	
	/**
	 * Gets an SQL WHERE condition, based on table metadata, for use in queries.
	 * This is to account for composite keys.
	 *
	 * @access	protected
	 * @param	mixed	$keys				Keys that will be matched against
	 * @return	string
	 */
	protected function _keyCondition($keys = null) {
		if (is_array($this->_key)) {
			foreach ($this->_key as $key) {
				if (!is_null($keys)) {
					$this->where($key, '=', $keys[$key]);
				} else {
					$this->where($key, '=', $this->$key);
				}
			}
		} else {
			if (!is_null($keys)) {
				$this->where($this->_key, '=', $keys);
			} else {
				$this->where($this->_key, '=', $this->{$this->_key});				
			}
		}
	}
		
	/**
	 * Gets the value(s) of the primary key as fetched for a particular record.
	 * This is what we should be assigning to $_id.  This is also to account for
	 * composite primary keys.  Returns an atomic value for tables with a,
	 * single-column key and an associative array for composite keys.
	 *
	 * @access	protected
	 * @return	mixed
	 */
	public function _getKey() {
		if (is_array($this->_key)) {
			foreach ($this->_key as $k) {
				$result[$k] = $this->$k;
			}
		} else {
			$result = $this->{$this->_key};
		}
		return $result;
	}
	
	static public function findByPK($value) {
		global $config;
		
		if ($config->cache->enabled === true && self::$_ttl !== false) {
			$memkey = self::$_table . '-pk-' . $value;
			$result = CacheManager::get($memkey);
			if ($result !== false) {
				return $result;
			}
		}
		
		self::addWhere(self::$_key . ' = ?', $value);
		$row = self::execute();
		
		if (is_object($row)) {
			if ($config->cache->enabled === true && self::$_ttl !== false) {
				CacheManager::set($memkey, $row, self::$_ttl);
			}
			return $row;
		}
		return false;
	}
	
	/**
	 * Retrieve row from the table where the primary key matches the id given
	 *
	 * @access	public
	 * @param	mixed	$id				Id to match against primary key.  If null, use the _id property.
	 * @param	bool	$get_current	If set to true, will fetch the record into the current object, 
	 *									otherwise returns a new object.
	 * @param	int		$ttl			Time to live for cache record
	 * @return 	object
	 */
	public function find($id = null, $get_current = false, $ttl = 0) {
		global $config;
		
		if ($config->cache->enabled === true && $ttl !== false) {
			$memkey = $this->_table . '-key-' . (is_array($id) ? join('-', $id) : $id);
			$result = CacheManager::get($memkey);
			if ($result !== false) {
				if ($get_current === true) {
					foreach ($this->_metadata->fields as $key => $item) {
						$this->$key = $result->$key;
					}
					$this->_id = $this->_getKey();
					return true;
				} else {
					return $result;
				}
			}			
		}

		$params = array();

		$this->_keyCondition($id);
		$where = $this->_getWhereClause($params);
		$where = !empty($where) ? ' WHERE ' . $where : '';
		
		$sql = "SELECT * FROM " . $this->_table . $where;
		
		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}
		
		try {
			$pass_id = $id ? $id : $this->_id;
			$stmt = $this->_db->prepare($sql);
			foreach ($params as $key => $val) {
				$stmt->bindValue(($key + 1), $val);
			}
			$stmt->execute();
			
			if (!is_null($this->_fetchClass)) {
				if ($get_current) {
					$stmt->setFetchMode(PDO::FETCH_INTO, $this);
				} else {
					$stmt->setFetchMode(PDO::FETCH_CLASS, $this->_fetchClass);
				}
			}
			
			$result = $stmt->fetch();
			
			if ($result) {
				if ($get_current) {
					$this->_id = $this->_getKey();
				} else {
					$result->_id = $result->_getKey();
				}
				if ($config->cache->enabled === true && $ttl !== false) {
					CacheManager::set($memkey, $get_current === true ? $this : $result, $ttl);
				}
				return $result;
			}
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
		}
		return false;
	}
	
	/**
	 * Retrieve all rows from the table
	 *
	 * @access	public
	 * @return 	array
	 */
	public function getAll() {
		$fields = !is_null($this->_fields) ? $this->_fields : '*';
		$this->_fields = null;
		
		if (count($this->_order) > 0) {
			$order = '';
			while ( count($this->_order) > 0 ) {
				$item = array_shift($this->_order);
				$order .= ' ' . $item['field'] . ' ' . $item['order'];
			}
		}
		$order = !empty($order) ? ' ORDER BY ' . $order : '';
		
		$sql = "SELECT " . $fields . " FROM " . $this->_table . $order;
		
		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}
		
		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute();
			if (!is_null($this->_fetchClass)) {
				$stmt->setFetchMode(PDO::FETCH_CLASS, $this->_fetchClass);
			} else {
				$stmt->setFetchMode(PDO::FETCH_OBJ);
			}
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return array();
		}
	}
	
	/**
	 * Form the conditions of a SQL query for use in SELECT, DELETE, and UPDATE queries
	 *
	 * @access	protected
	 * @param	array	&$params				Array of values that will bound to the prepared query
	 * @return	string
	 */
	protected function _getWhereClause(&$params) {
		if ($this->_clearAfter === false) {
			$saveWhere = $this->_where;
		}
		$where = '';
		while ( count($this->_where) > 0 ) {
			$cond = array_shift($this->_where);
			if (!isset($cond['field'])) {
				$wheregroup = '';
				while ( count($cond) > 0 ) {
					$item = array_shift($cond);
					$wheregroup .= !empty($wheregroup) ? ' OR ' : '';
					if (strpos($item['value'], '=') === 0) {
						$wheregroup .= $item['field'] . ' ' . $item['operator'] . ' ' . substr($item['value'], 1);
					} else {
						$wheregroup .= $item['field'] . ' ' . $item['operator'] . ' ? ';
						$params[] = $item['value'];
					}
				}
				$where .= !empty($where) ? ' AND (' . $wheregroup . ') ' : ' (' . $wheregroup . ') ';
				unset($wheregroup);
			} else {
				$where .= !empty($where) ? ' AND ' : '';
				if (strpos($cond['value'], '=') === 0) {
					$where .= $cond['field'] . ' ' . $cond['operator'] . ' ' . substr($cond['value'], 1);
				} else {
					$where .= $cond['field'] . ' ' . $cond['operator'] . ' ? ';
					$params[] = $cond['value'];
				}
			}
		}
		if ($this->_clearAfter === false) {
			$this->_where = $saveWhere;
		}
		return $where;
	}
	
	/**
	 * Retrieve all rows matching given criteria
	 *
	 * @access	public
	 * @param	bool	$single			If a single row would be returned, just return the object instead
	 * @return 	mixed
	 */
	public function findWhere($single = false) {
		$fields = !is_null($this->_fields) ? $this->_fields : '*';
		if ($this->_clearAfter !== false) {
			$this->_fields = null;
		}
		
		$joins = '';
		if ($this->_clearAfter === false) {
			$saveJoins = $this->_join;
		}
		while (count($this->_join) > 0) {
			$join = array_shift($this->_join);
			$joins .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'] 
				. ' ON ' . $join['src'] . '=' . $join['dest'] . ' ' . $join['hint'] . ' ';
		}
		if ($this->_clearAfter === false) {
			$this->_join = $saveJoins;
		}
		
		$params = array();
		$where = $this->_getWhereClause($params);
		$where = !empty($where) ? ' WHERE ' . $where : '';
		
		if (count($this->_group) > 0) {
			$group = join(',', $this->_group);
			if ($this->_clearAfter !== false) {
				$this->_group = array();
			}
		}
		$group = !empty($group) ? ' GROUP BY ' . $group : '';
		
		if (count($this->_order) > 0) {
			if ($this->_clearAfter === false) {
				$saveOrder = $this->_order;
			}
			$order = '';
			while ( count($this->_order) > 0 ) {
				$item = array_shift($this->_order);
				$order .= !empty($order) ? ', ' : '';
				$order .= $item['field'] . ' ' . $item['order'];
			}
			if ($this->_clearAfter === false) {
				$this->_order = $saveOrder;
			}
		}
		$order = !empty($order) ? ' ORDER BY ' . $order : '';
		
		$limit = is_numeric($this->_limit) ? ' LIMIT ' . $this->_limit : '';
		if ($this->_clearAfter !== false) {
			$this->_limit = null;
		}
		
		$offset = is_numeric($this->_offset) ? ' OFFSET ' . $this->_offset : '';
		if ($this->_clearAfter !== false) {
			$this->_offset = null;		
		}
		
		$sql = "SELECT " . $this->_optHint . ' ' . $fields . " FROM " 
			. $this->_table . ' ' . $this->_indexHint 
			. $joins . $where . $group . $order . $limit . $offset;
		
		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}

		try {
			$stmt = $this->_db->prepare($sql);
			foreach ($params as $key => $param) {
				$stmt->bindValue(($key + 1), $param);
			}
			if ($stmt->execute()) {
				if ($stmt->columnCount() == 1) {
					while (($col = $stmt->fetchColumn()) !== false) {
						$rows[] = $col;
					}
				} else {
					if (!is_null($this->_fetchClass)) {
						$stmt->setFetchMode(PDO::FETCH_CLASS, $this->_fetchClass);
					} else {
						$stmt->setFetchMode(PDO::FETCH_OBJ);
					}
					$rows = $stmt->fetchAll();
				}
				if ($single === true) {
					if (@count($rows) == 1) {
						return $rows[0];
					}
				}
				return $rows;
			}
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return array();
		}
	}
	
	/**
	 * Retrieve a set of rows based on the "page" they would be on.
	 *
	 * @access	public
	 * @param	int		$page			Page number to retrieve items for
	 * @param	int		$items			Number of items per page
	 * @return 	array
	 */
	public function getPage($page = 1, $items = 25) {
		return $this->limit($items)->offset(($items * ($page - 1)))->findWhere();
	}
	
	/**
	 * Insert new row into the table
	 *
	 * @access	public
	 * @param	array 	$data			Array containing data to be inserted into the table
	 * @param	bool	$return			Return new id for an auto incrementing table
	 * @return 	bool
	 */
	public function insert($data, $return = false) {
		if (!is_array($data)) {
			System::logMessage('Data not passed as array', __FUNCTION__, __CLASS__);
		}

		$fields = array();
		$params = array();
		
		foreach ( $data as $key => $val ) {
			if (!array_key_exists($key, $this->_metadata)) {
				unset($data[$key]);
			}
		}
		
		$sql = "INSERT INTO " . $this->_table . " (" . join(', ', array_keys($data)) . ")
				VALUES(" . (count($data) > 1 ? str_repeat('?,', count($data) - 1) : '') . "?)";
		
		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}
		
		try {
			$stmt = $this->_db->prepare($sql);
			$result = $stmt->execute(array_values($data));
			if ($result === true && $return === true) {
				return $this->_db->lastInsertId();
			}
			return $result;
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}
	}
	
	/**
	 * Update row in the table
	 *
	 * @access	public
	 * @param	array 	$data			Array containing data to be inserted into the table
	 * @param	bool	$allow_literals	Pass values with a leading '=' as literals into the SQL string.
	 * @param	bool	$primary		Update the row based on the current primary key of the object
	 *									This will bypass all other conditions passed to the instance
	 * @return 	bool
	 */
	public function update($data, $allow_literals = false, $primary = false) {
		if (!is_array($data)) {
			System::logMessage('Data not passed as array', __FUNCTION__, __CLASS__);
			return false;
		}
		
		$dataset = '';
		
		foreach ($data as $key => $val) {
			if (array_key_exists($key, $this->_metadata)) {
				$dataset .= !empty($dataset) ? ', ' : '';
				if (substr($val, 0, 1) == '=' && $allow_literals) {
					$dataset .= $key . ' = ' . substr($val, 1) . ' ';
					unset($data[$key]);
				} else {
					$dataset .= $key . ' = ? ';
				}
			}
		}
		
		$params = array_values($data);
		
		if ($primary === true) {
			$this->_keyCondition();
		}
		
		$where = $this->_getWhereClause($params);
		$where = !empty($where) ? ' WHERE ' . $where : '';			
		
		$sql = "UPDATE " . $this->_table . " SET " . $dataset . $where;
		
		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}
		
		try {
			$stmt = $this->_db->prepare($sql);
			foreach ($params as $key => $val) {
				$stmt->bindValue(($key + 1), $val);
			}
			$result = $stmt->execute();
			$this->_affected = $stmt->rowCount();
			return $result;
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}
	}
	
	/**
	 * Delete rows from the table
	 *
	 * @access	public
	 * @return 	bool
	 */
	public function delete() {
		global $config;
		
		if (@count($this->_where) == 0) {
			if (is_array($this->_key)) {
				foreach ( $this->_key as $k ) {
					$this->where($k, '=', $this->$k);
				}
			} else {
				$this->where($this->_key, '=', $this->{$this->_key});
			}
		}
		
		if (@count($this->_where) == 0) {
			return false;
		}
		
		$params = array();
		$where = $this->_getWhereClause($params);
		$where = !empty($where) ? ' WHERE ' . $where : '';
		
		$sql = "DELETE FROM " . $this->_table . $where;
		
		if ($this->_debug === true) {
			System::debugMessage('Query: ' . $sql, __FUNCTION__, __CLASS__);
		}
		
		try {
			$stmt = $this->_db->prepare($sql);
			foreach ($params as $key => $val) {
				$stmt->bindValue(($key + 1), $val);
			}
			$retval = $stmt->execute();
			$this->_affected = $stmt->rowCount();
            if (is_array($this->_key)) {
                foreach ($this->_key as $key) {
                    $id .= '-' . $this->$key;
                }
            } else {
                $id = '-' . $this->{$this->_key};
            }
			if ($config->cache->enabled === true && $retval === true) {
	            $memkey = $this->_table . '-key-' . $id;
				CacheManager::delete($memkey);
			}
			return $retval;
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}
	}
	
	/**
	 * Save the current row being worked with into back to the table
	 *
	 * @access	public
	 * @param	bool	$return 			Whether or not to return the insert id
	 * @return 	bool
	 */
	public function save($return = false) {
		global $config;
		
		$data = array();
		foreach ($this->_metadata as $key => $val) {
			if ($key != $this->_key || $this->_updateablePrimaryKey) {
				$data[$key] = $this->$key;
			}
		}
		
		if (!empty($this->_key)) {
			if (is_array($this->_key)) {
				foreach ($this->_key as $key) {
					$id .= '-' . $this->$key;
				}
			} else {
				$id = '-' . $this->{$this->_key};
			}
			$retval = empty($this->_id)
				? $this->insert($data, $return)
				: $this->update($data, false, true);
			if ($config->cache->enabled === true && $retval === true) {
				$memkey = $this->_table . '-key-' . $id;
				CacheManager::delete($memkey);
			}
			return $retval;
		} else {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}
	}
	
	/**
	 * Set the list of fields to be retrieved in the next select query
	 *
	 * @static
	 * @access	public
	 * @param	string	$fields			Fields to be retrieved
	 * @return 	void
	 */
	static public function fields($fields) {
		if (!empty($fields)) {
			self::$_fields = $fields;
		}
	}
	
	/**
	 * Add a condition onto the stack to be used in the next query
	 * Conditions may included place holders for prepared parameters,
	 * and then pass the parameters as extra arguments to the function.
	 * For example:
	 *		Model::addWhere('first_name = ?', 'Bob');
	 *
	 * You can also pass multiple conditions in one call to the method
	 * by simply adding AND/OR between conditions, Example:
	 *		Model::addWhere('first_name = ? AND last_name = ?', 'Bob', 'Smith');
	 *
	 * @static
	 * @access	public
	 * @param	array 	$conditions		Condition(s) to be added to the stack
	 * @return 	void
	 */
	static public function addWhere($condition) {
		self::$_where[] = $condition;
		$numargs = func_num_args();
		if ($numargs > 1) {
			for ($x = 1; $x < $numargs; $x++) {
				$param = func_get_arg($x);
				if (is_array($param)) {
					self::$_params[] = join(',', $param);
				} else {
					self::$_params[] = $param;
				}
			}
		}
		return self;
	}
	
	/**
	 * Add fields onto the group stack
	 *
	 * @access	public
	 * @param	mixed	$field				Field(s) to be added to the group stack.
	 * @return 	object
	 */
	static public function groupBy($field) {
		if (is_array($field)) {
			foreach ($field as $item) {
				self::groupBy($item);
			}
		} else if (strpos($field, ',') !== false) {
			$fields = explode(',', $field);
			foreach ($fields as $field) {
				self::groupBy($field);
			}
		} else {
			self::$_group[] = trim($field);
		}
	}
	
	/**
	 * Add items onto the order stack.  Items should be given in the format of
	 * array(field[, sortorder]) or a single item directly as method parameters.
	 * Multiple items can be passed at once using
	 * a multidimensional array.
	 *
	 * @access	public
	 * @param	array 	$order		Array of items for the order by clause
	 * @return 	object
	 */
	public function orderby($order) {
		if (is_string($order)) {
			$order = array();
			$order[0] = func_get_arg(0);
			if (func_num_args() == 2) {
				$order[1] = func_get_arg(1);
			} else {
				$order[1] = 'asc';
			}
		} elseif (!is_array($order)) {
			System::logMessage('Conditions not an array', __FUNCTION__, __CLASS__);
			return $this;
		}
		
		if (is_array($order[0])) {
			foreach ( $order as $item ) {
				$data = array('field' => $item[0], 'order' => $item[1]);
				$this->_order[] = $data;
			}
		} else {
			$data = array('field' => $order[0], 'order' => $order[1]);
			$this->_order[] = $data;
		}
		return $this;
	}
	
	/**
	 * Set the limit to be used for the next select query
	 *
	 * @access	public
	 * @param	int		$limit			Number of rows to set limit to
	 * @return 	object
	 */
	public function limit($limit) {
		$this->_limit = is_numeric($limit) ? $limit : null;
		return $this;
	}
	
	/**
	 * Set the offset to be used in the next select query
	 *
	 * @access	public
	 * @param	int		$offset			Offset to be used
	 * @return 	object
	 */
	public function offset($offset) {
		$this->_offset = is_numeric($offset) ? $offset : null;
		return $this;
	}
	
	/**
	 * Add a join statement for the next select query.  Takes a 5-element array with
	 * the type, table, source field, and destination field, and optional index hint 
	 * for the join.  Can also take an array of such arrays, for multiple joins, or 
	 * the 5 values as separate parameters for a single join.
	 *
	 * @access	public
	 * @param	array 	$joins	Join statements to add.
	 * @return 	object
	 */
	public function join($joins) {
		if (is_string($joins)) {
			$joins = array();
			for ($i = 0; $i < func_num_args(); $i++) {
				$joins[$i] = func_get_arg($i);
			}
		}
		
		if (is_array($joins[0])) {
			foreach ( $joins as $join ) {
				$item = array('type' => $join[0], 'table' => $join[1], 'src' => $join[2], 'dest' => $join[3], 'hint' => $join[4]);
				$this->_join[] = $item;
			}
		} else {
			$item = array('type' => $joins[0], 'table' => $joins[1], 'src' => $joins[2], 'dest' => $joins[3], 'hint' => $joins[4]);
			$this->_join[] = $item;
		}
		
		return $this;
	}
	
	/**
	 * Optimization hint to be used in next query
	 * 
	 * @access	public
	 * @param 	string	$hint			Optimization hint to be used in next query
	 * @return 	object
	 */
	public function optHint($hint) {
		$this->_optHint = $hint;
		return $this;
	}
	
	/**
	 * Index hint to be used in next query
	 * 
	 * @access	public
	 * @param 	string	$hint			Index hint to be used in next query
	 * @return 	object
	 */
	public function indexHint($hint) {
		$this->_indexHint = $hint;
		return $this;
	}	
	
	/**
	 * Enable or disable maintaining of conditions, limits, groups, etc.
	 * through multiple queries
	 * 
	 * @access	public
	 * @param 	bool	$clear			Whether or not to clear the options
	 * @return 	object
	 */
	public function clearAfter($clear = true) {
		$this->_clearAfter = is_bool($clear) ? $clear : true;
		return $this;
	}
	
	static public function execute() {
		$db = DB::getInstance(self::$_dbconfig);
		var_dump($db);
		if (!($db instanceof PDO)) {
			return false;
		}

		var_dump(self::$_fields);

		$sql = "SELECT " . self::$_fields . " FROM " . self::$_table;
		if (count(self::$_where) > 0) {
			$conditions = join(' AND ', self::$_where);
			if (!empty($conditions)) {
				$sql .= " WHERE " . $conditions;
			}
		}
		print '<pre>';
		var_dump($sql);
		print '</pre>';
	}
}
