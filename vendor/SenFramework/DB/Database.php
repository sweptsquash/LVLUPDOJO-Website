<?php
namespace SenFramework\DB;

class Database {
	
	private $connection = false;

	
	/**
	 * Run a mysql query.
	 *
	 * Can be used to run a query or just to instantiate the connection.
	 *
	 * @param string $query SQL Query or empty to instantiate the connection
	 *
	 * @return bool|resource Mysqli result on success, false on failure
	 */
	public function mq($query = '') {
		
		// Start a new connection
		if (empty($this->connection)) {
			$this->connection = new \mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE, MYSQL_PORT);
			if ($this->connection->connect_errno) {
				return false;
			}
		}
		
		if (empty($query)) {
			return false;
		}
		
		// Run query
		$result = $this->connection->query($query);
		
		// Show errors if CONSOLE_CONTENT or DEVELOP are true.
		if ($error = $this->connection->error) {
			\SenFramework\SenFramework::addLogEntry('[MySQLi Error]: '.$error.' - Query: '.$query, 'database');
			
			if (defined('CONSOLE_CONTENT') && CONSOLE_CONTENT === true) {
				echo LF.'MySQL:'.LF.'- Error: '.$error.LF.'- Query: '.$query.LF;
			} elseif (DEVELOP === true) {
				echo LF.'<br /><b>MySQL query</b>: '.$query.'<br /><br /><b>MySQL error</b>: '.$error.'<br /><br />'.LF;		
				exit;
			}
			
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Escape query values
	 *
	 * @param mixed Value to escape
	 *
	 * @return string Escaped value
	 */
	public function mres($value) {
		if (empty($this->connection)) {
			$this->mq();
		}
		return $this->connection->real_escape_string($value);
	}
	
	/**
	 * Mysql Fetch Assoc or next array item
	 *
	 * @param mixed $result array or mysql result
	 *
	 * @return mixed next result item or false on failure
	 */
	public function mfa(&$result) {
		if (gettype($result) == 'array') {
			if ($return = current($result)) {
				next($result);
				return $return;
			}
			return false;
		}
		
		return !empty($result) ? $result->fetch_assoc() : false;
	}
	
	/**
	 * Get num rows
	 *
	 * @param mixed $result array or mysql result
	 *
	 * @return int result count
	 */
	public function mnr(&$result) {
		if (gettype($result) == "array") {
			return count($result);
		}
		
		return $result->num_rows;
	}
	
	/**
	 * Get specific value
	 *
	 * @param mixed $result array or mysql result
	 * @param int $offset offset of desired value
	 * @param int $field Field name
	 * @param int $default Default return value
	 *
	 * @return mixed desired value
	 */
	public function mr(&$result, $offset = 0, $field = 0, $default = 0) {
		if (gettype($result) == 'array') {
			if (empty($result)) {
				return $default;
			}
			$alias = array_keys($result[$offset]);
			return $result[$offset][$alias[$field]];
		}
		
		if (!self::mnr($result)) {
			return $default;
		}
		
		$result->data_seek($offset);
		$row = $result->fetch_array();
		$value = $row[$field]; 
		if (is_null($value)) {
			$value = $default;
		}
		
		return $value;
	}
	
	/**
	 * Get mysql error
	 *
	 * @return string|bool Error string, false on failure
	 */
	public static function merror() {
		if ($error = $this->connection->error) {
			return $error;
		}
		return false;
	}
	
	/**
	 * Start a transaction
	 *
	 * @return bool true on success, false on failure
	 */
	public function beginTransaction() {
		if (empty($this->connection)) {
			$this->mq();
		}
		return $this->connection->begin_transaction();
	}
	
	/**
	 * Commit a transaction
	 *
	 * @return bool true on success, false on failure
	 */
	public function commitTransaction() {
		if (empty($this->connection)) {
			return false;
		}
		return $this->connection->commit();
	}
	
	/**
	 * Roll a transaction back
	 *
	 * @return bool true on success, false on failure
	 */
	public function rollbackTransaction() {
		if (empty($this->connection)) {
			return false;
		}
		return $this->connection->rollback();
	}
	
	public function lastId() {
		return $this->connection->insert_id;
	}
	
	/**
	 * Closes Database Connection
	 *
	 * @return bool true on success, false on failure
	 */
	public function close() {
		if (!empty($this->connection)) {
			return $this->connection->close();	
		}
		return false;
	}
	
	public function build_array($query, $assoc_ary = false) {
		if (!is_array($assoc_ary)) {
			return false;
		}

		$fields = $values = array();

		if ($query == 'INSERT' || $query == 'INSERT_SELECT') {
			
			foreach ($assoc_ary as $key => $var) {
				$fields[] = $key;

				if (is_array($var) && is_string($var[0])) {
					// This is used for INSERT_SELECT(s)
					$values[] = self::validate_value($var[0]);
				} else {
					$values[] = self::validate_value($var);
				}
			}

			$query = ($query == 'INSERT') ? ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')' : ' (' . implode(', ', $fields) . ') SELECT ' . implode(', ', $values) . ' ';
			
		}  else if ($query == 'UPDATE' || $query == 'SELECT' || $query == 'DELETE') {
			$values = array();
			
			foreach ($assoc_ary as $key => $var) {
				$values[] = "$key = " . self::validate_value($var);
			}
			
			$query = implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
		}

		return $query;
	}
	
	function validate_value($var)
	{
		if (is_null($var))
		{
			return 'NULL';
		}
		else if (is_string($var))
		{
			return "'" . self::mres($var) . "'";
		}
		else
		{
			return (is_bool($var)) ? intval($var) : $var;
		}
	}
}