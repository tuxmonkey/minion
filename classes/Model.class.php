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
	protected $_dbconfig = 'default';
	
	/** Metadata for the model table */
	protected $_metadata = array();
	
	/** Name of the table that is being presented by the model */
	protected $_table = null;
	
	/** Primary Key for table model */
	protected $_key = null;

	/** Caching time to live */
	protected $_ttl = false;
	
	/** Enable/Disable debug mode */
	public $_debug = false;
	
	/** Array containing data to be used in next built query */
	protected $_queryData = array();
	
	/** Whether or not to clear conditions, joins, etc on next query */
	static protected $_clearAfter = true;

	/** The number of rows affected by the last update or delete */
	static protected $_affected = 0;
	
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
	protected function __call($funcname, $params = array()) {
		$fields = join('|', array_keys($this->_metadata));
		if (preg_match('/^findBy(' . $fields . ')$/i', $funcname, $match)) {
			$field = trim($match[1]);
			$value = trim($params[0]);
			$operator = $params[1] === true ? 'like' : '=';
			$this->addWhere("$field $operator ?", $value);
			return $this->select();
		} else {
			return null;
		}
	}

	protected function __get($var) {
		if (in_array($var, array_keys($this->_hasOne))) {
			$class = $this->_hasOne[$var]['model'];
			$obj = new $class;
			$obj->addWhere($this->_hasOne[$var]['foreign'] . ' = ?', $this->{$this->_hasOne[$var]['local']});
			$obj->limit(1);
			$this->{$var} = $obj->select(true);
			return $this->{$var};
		} else if (in_array($var, array_keys($this->_hasMany))) {
			$class = $this->_hasMany[$var]['model'];
			$obj = new $class;
			$obj->addWhere($this->_hasMany[$var]['foreign'] . ' = ?', $this->{$this->_hasMany[$var]['local']});
			$this->{$var} = $obj->select();
			return $this->{$var};
		}
	}

	/**
	 * Retrieve a row searching by the primary key of the table
	 *
	 * @access	public
	 * @param	mixed	$value			Value to match the primary key against
	 * @return	mixed
	 */	
	public function findByPK($value) {
		global $config;
	
		if ($config->cache->enabled === true && $this->_ttl !== false) {
			if (func_num_args() > 1 && is_array($this->_key)) {
				$memkey = $this->_table . '-pk-' . join('-', func_get_args());
			} else {
				$memkey = $this->_table . '-pk-' . $value;
			}
			$result = CacheManager::get($memkey);
			if ($result !== false) {
				return $result;
			}
		}
		
		if (func_num_args() > 1 && is_array($this->_key)) {
			foreach ($this->_key as $key => $field) {
				$this->addWhere($field . ' = ?', func_get_arg($key));
			}
		} else {
			$this->addWhere($this->_key . ' = ?', $value);
		}

		$row = $this->limit(1)->select(true);
		
		if (is_object($row)) {
			if ($config->cache->enabled === true && $this->_ttl !== false) {
				CacheManager::set($memkey, $row, $this->_ttl);
			}
			return $row;
		}
		return false;
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
	 * @access	public
	 * @param	string	$fields			Fields to be retrieved
	 * @return 	object
	 */
	public function fields($fields) {
		if (!empty($fields)) {
			$this->_queryData['fields'] = $fields;
		}
		return $this;
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
	 * @access	public
	 * @param	array 	$conditions		Condition(s) to be added to the stack
	 * @return 	object
	 */
	public function addWhere($condition) {
		$this->_queryData['where'][] = $condition;
		$numargs = func_num_args();
		if ($numargs > 1) {
			for ($x = 1; $x < $numargs; $x++) {
				$param = func_get_arg($x);
				if (is_array($param)) {
					$this->_queryData['whereParams'][] = join(',', $params);
				} else {
					$this->_queryData['whereParams'][] = $param;
				}
			}
		}
		return $this;
	}
	
	/**
	 * Add fields onto the group stack
	 *
	 * @access	public
	 * @param	mixed	$field				Field(s) to be added to the group stack.
	 * @return 	object
	 */
	public function groupBy($field) {
		if (is_array($field)) {
			foreach ($field as $item) {
				$this->groupBy($item);
			}
		} else if (strpos($field, ',') !== false) {
			$fields = explode(',', $field);
			foreach ($fields as $field) {
				$this->groupBy($field);
			}
		} else {
			$this->_queryData['groupBy'][] = trim($field);
		}
		return $this;
	}
	
	/**
	 * Add items onto the order stack.  Items should be given in the format of
	 * array(field[, sortorder]) or a single item directly as method parameters.
	 * Multiple items can be passed at once using
	 * a multidimensional array.
	 *
	 * @access	public
	 * @param	string 	$order		Field(s) to order on
	 * @return 	object
	 */
	public function orderby($order) {
		if (!empty($order)) {
			$this->_queryData['orderBy'][] = trim($order);
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
		if (is_numeric($limit)) {
			$this->_queryData['limit'] = $limit;
		}
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
		if (is_numeric($offset)) {
			$this->_queryData['offset'] = $offset;
		}
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
	
	protected function buildQuery($type = 'SELECT') {
		switch (strtoupper($type)) {
			case 'SELECT':
				$sql  = "SELECT ";
				$sql .= isset($this->_queryData['fields']) ? $this->_queryData['fields'] : '*';
				$sql .= " FROM " . $this->_table;
				break;

			case 'DELETE':
				$sql = "DELETE FROM " . $this->_table;
				break;
		}

		if (isset($this->_queryData['joins'])) {
			foreach ($this->_queryData['joins'] as $join) {
				$sql .= ' ' . $join['type'] . ' JOIN ' . $join['table'] . ' ' . $join['local'] . ' = ' . $join['foreign'];
			}
		}
	
		if (isset($this->_queryData['where'])) {
			$conditions = join(' AND ', $this->_queryData['where']);
			if (!empty($conditions)) {
				$sql .= " WHERE " . $conditions;
			}
		}

		if (isset($this->_queryData['groupBy'])) {
			$sql .= " GROUP BY " . join(',', $this->_queryData['groupBy']);
		}

		if (isset($this->_queryData['having'])) {
			$conditions = join(' AND ', $this->_queryData['having']);
			if (!empty($conditions)) {
				$sql .= " HAVING " . $conditions;
			}
		}

		if (isset($this->_queryData['orderBy'])) {
			$sql .= " ORDER BY " . join(',', $this->_queryData['orderBy']);
		}

		if (isset($this->_queryData['limit'])) {
			$sql .= " LIMIT " . $this->_queryData['limit'];
		}

		if (isset($this->_queryData['offset'])) {
			$sql .= " OFFSET " . $this->_queryData['offset'];
		}

		return $sql;
	}

	/**
	 * Return the result of the build query process to be reviewed
	 *
	 * @access	public
	 * @param	string	$type			Type of query that is being built (SELECT, DELETE, INSERT, UPDATE)
	 * @return	string
	 */
	public function getQuery($type = 'SELECT') {
		return $this->buildQuery($type);
	}

	/**
	 * Give us a clean slate for the prepared query data
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearQuery() {
		$this->_queryData = array();
	}

	public function select($single = false) {
		$db = DB::getInstance($this->_dbconfig);
		if (!($db instanceof PDO)) {
			return false;
		}

		$sql = $this->buildQuery('SELECT');
		var_dump($sql);
		try {
			$stmt = $db->prepare($sql);
			$params = is_array($this->_queryData['whereParams'])
				? $this->_queryData['whereParams'] : array();
			$params = is_array($this->_queryData['havingParams'])
				? array_merge($params, $this->_queryData['havingParams']) : $params;
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_class($this));
			$stmt->execute($params);
			$result = $stmt->fetchAll();
			if (count($result) == 1 && $single === true) {
				$result = $result[0];
			}
		} catch (PDOException $e) {
			System::logMessage($e->getMessage(), __FUNCTION__, __CLASS__);
			return false;
		}

		$this->clearQuery();
		return $result;
	}
}
