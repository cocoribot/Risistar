<?php
/**
 *  2Moons 
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package 2Moons
 * @author Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence MIT
 * @version 1.8.0
 * @link https://github.com/jkroepke/2Moons
 */

class Database
{
	protected $dbHandle = NULL;
	protected $dbTableNames = array();
	protected $lastInsertId = false;
	protected $rowCount = false;
	protected $queryCounter = 0;
	/** @var int Nesting depth for beginTransaction/commit/rollBack (savepoints when > 1). */
	protected $transactionDepth = 0;
	protected $commitCallbacks = [];
	protected $requestRolledBack = false;
	protected static $instance = NULL;


	public static function get()
	{
		if (!isset(self::$instance))
			self::$instance = new self();

		return self::$instance;
	}

	public function getDbTableNames()
	{
		return $this->dbTableNames;
	}

	private function __clone()
	{

	}

	protected function __construct()
	{
		$database = array();
		if (defined('DATABASE_CONFIG_FILE')) {
			require DATABASE_CONFIG_FILE;
		} else {
			require 'includes/config.php';
		}
		//Connect
		$db = new PDO("mysql:host=".$database['host'].";port=".$database['port'].";dbname=".$database['databasename'], $database['user'], $database['userpw']);
		//error behaviour
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		$db->query("set character set utf8");
		$db->query("set names utf8");
		$this->dbHandle = $db;

		$dbTableNames = array();

		include 'includes/dbtables.php';

		foreach($dbTableNames as $key => $name)
		{
			$this->dbTableNames['keys'][]	= '%%'.$key.'%%';
			$this->dbTableNames['names'][]	= $name;
		}
	}

	public function disconnect()
	{
		$this->dbHandle = NULL;
	}

	public function getHandle()
	{
		return $this->dbHandle;
	}

	public function beginTransaction()
	{
		if ($this->transactionDepth === 0) {
			// Under snapshot isolation, FOR UPDATE can fail if the row changed
			// while we waited for the lock. READ COMMITTED re-reads the latest
			// row after the wait, so the lock can proceed.
			$this->dbHandle->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
			$this->dbHandle->beginTransaction();
		} else {
			$this->dbHandle->exec('SAVEPOINT nest_'.$this->transactionDepth);
		}

		$this->transactionDepth++;
		$this->commitCallbacks[$this->transactionDepth] = [];
		return true;
	}

	public function afterCommit(callable $callback)
	{
		if ($this->transactionDepth === 0) {
			$callback();
		} else {
			$this->commitCallbacks[$this->transactionDepth][] = $callback;
		}
	}

	public function wasRequestRolledBack()
	{
		return $this->requestRolledBack;
	}

	public function commit()
	{
		if ($this->transactionDepth <= 0) {
			return false;
		}

		$depth = $this->transactionDepth;
		if ($depth === 1) {
			$this->dbHandle->commit();
		} else {
			$this->dbHandle->exec('RELEASE SAVEPOINT nest_'.($depth - 1));
		}
		$this->transactionDepth--;
		$callbacks = $this->commitCallbacks[$depth] ?? [];
		unset($this->commitCallbacks[$depth]);
		if ($depth === 1) {
			foreach ($callbacks as $callback) {
				$callback();
			}
		} else {
			$this->commitCallbacks[$depth - 1] = array_merge($this->commitCallbacks[$depth - 1] ?? [], $callbacks);
		}
		return true;
	}

	public function rollBack()
	{
		if ($this->transactionDepth <= 0) {
			return false;
		}

		unset($this->commitCallbacks[$this->transactionDepth]);
		$this->transactionDepth--;

		if ($this->transactionDepth === 0) {
			$this->requestRolledBack = true;
			return $this->dbHandle->rollBack();
		}

		// ROLLBACK TO keeps the savepoint; release it so depth stays consistent.
		$this->dbHandle->exec('ROLLBACK TO SAVEPOINT nest_'.$this->transactionDepth);
		$this->dbHandle->exec('RELEASE SAVEPOINT nest_'.$this->transactionDepth);
		return true;
	}

	public function inTransaction()
	{
		return $this->transactionDepth > 0 || $this->dbHandle->inTransaction();
	}

	public function getTransactionDepth()
	{
		return $this->transactionDepth;
	}

	/**
	 * Abort the entire request transaction (all savepoints). Used when a request
	 * dies inside a nested atomic section so partial writes are not committed.
	 */
	public function rollBackAll()
	{
		$this->commitCallbacks = [];
		$this->requestRolledBack = true;
		if ($this->transactionDepth <= 0 && !$this->dbHandle->inTransaction()) {
			return false;
		}

		$this->transactionDepth = 0;

		if ($this->dbHandle->inTransaction()) {
			return $this->dbHandle->rollBack();
		}

		return false;
	}

	/**
	 * Row-lock a planet for the rest of the current transaction.
	 * Prevents concurrent SavePlanetToDB absolute writes from clobbering
	 * RestoreFleet / StoreGoodsToPlanet relative deposits (and vice versa).
	 */
	public function lockPlanet($planetId)
	{
		$planetId = (int) $planetId;
		if ($planetId <= 0) {
			return;
		}

		if ($this->transactionDepth <= 0) {
			throw new Exception('lockPlanet() requires an active transaction');
		}

		$this->selectSingle('SELECT id FROM %%PLANETS%% WHERE id = :planetId FOR UPDATE;', array(
			':planetId' => $planetId,
		));
	}

	public function lastInsertId()
	{
		return $this->lastInsertId;
	}

	public function rowCount()
	{
		return $this->rowCount;
	}
	
	protected function _query($qry, array $params, $type)
	{
		if (in_array($type, array("insert", "select", "update", "delete", "replace")) === false)
		{
			throw new Exception("Unsupported Query Type");
		}

		$this->lastInsertId = false;
		$this->rowCount = false;
		
		$qry	= str_replace($this->dbTableNames['keys'], $this->dbTableNames['names'], $qry);

		/** @var $stmt PDOStatement */
		$stmt	= $this->dbHandle->prepare($qry);

		if (isset($params[':limit']) || isset($params[':offset']))
		{
			foreach($params as $param => $value)
			{
				if($param == ':limit' || $param == ':offset')
				{
					$stmt->bindValue($param, (int) $value, PDO::PARAM_INT);
				}
				else
				{
					$stmt->bindValue($param, (int) $value, PDO::PARAM_STR);
				}
			}
		}

		try {
			$success = (count($params) !== 0 && !isset($params[':limit']) && !isset($params[':offset'])) ? $stmt->execute($params) : $stmt->execute();
		}
		catch (PDOException $e) {
			throw new Exception($e->getMessage()."<br>\r\n<br>\r\nQuery-Code:".str_replace(array_keys($params), array_values($params), $qry));
		}

		$this->queryCounter++;

		if (!$success)
			return false;

		if ($type === "insert")
			$this->lastInsertId = $this->dbHandle->lastInsertId();
		$this->rowCount = $stmt->rowCount();

		return ($type === "select") ? $stmt : true;
	}

	protected function getQueryType($qry)
	{
		if(!preg_match('!^(\S+)!', $qry, $match))
        {
            throw new Exception("Invalid query $qry!");
        }

		if(!isset($match[1]))
        {
            throw new Exception("Invalid query $qry!");
        }

		return strtolower($match[1]);
	}

	public function delete($qry, array $params = array())
	{
		if (($type = $this->getQueryType($qry)) !== "delete")
			throw new Exception("Incorrect Delete Query");

		return $this->_query($qry, $params, $type);
	}

	public function replace($qry, array $params = array())
	{
		if (($type = $this->getQueryType($qry)) !== "replace")
			throw new Exception("Incorrect Replace Query");

		return $this->_query($qry, $params, $type);
	}

	public function update($qry, array $params = array())
	{
		if (($type = $this->getQueryType($qry)) !== "update")
			throw new Exception("Incorrect Update Query");

		return $this->_query($qry, $params, $type);
	}

	public function insert($qry, array $params = array())
	{
		if (($type = $this->getQueryType($qry)) !== "insert")
			throw new Exception("Incorrect Insert Query");

		return $this->_query($qry, $params, $type);
	}

	public function select($qry, array $params = array())
	{
		if (($type = $this->getQueryType($qry)) !== "select")
			throw new Exception("Incorrect Select Query");

		$stmt = $this->_query($qry, $params, $type);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function selectSingle($qry, array $params = array(), $field = false)
	{
		if (($type = $this->getQueryType($qry)) !== "select")
			throw new Exception("Incorrect Select Query");

		$stmt = $this->_query($qry, $params, $type);
		$res = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(PHP_VERSION_ID <= 70400) {
			return ($field === false || is_null($res)) ? $res : $res[$field];
		} else {
			return ($field === false || (empty($res))) ? $res : $res[$field];
		}
	}
	
	/**
	 * Lists column values of a table
	 * with desired key from the
	 * database as an array.
	 *
	 * @param  string 		$table
	 * @param  string 		$column
	 * @param  string|null 	$key
	 * @return array
	 */
	public function lists($table, $column, $key = null)
	{
		$selects = implode(', ', is_null($key) ? array($column) : array($column, $key));
		
		$qry = "SELECT {$selects} FROM %%{$table}%%;";
		$stmt = $this->_query($qry, array(), 'select');

		$results = array();
		if (is_null($key))
		{
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$results[] = $row[$column];
			}
		}
		else
		{
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$results[$row[$key]] = $row[$column];
			}
		}

		return $results;
	}

	public function query($qry)
	{
		$this->lastInsertId = false;
		$this->rowCount = false;
		$this->rowCount = $this->dbHandle->exec($qry);
		$this->queryCounter++;
	}

	public function nativeQuery($qry)
	{
		$this->lastInsertId = false;
		$this->rowCount = false;

		$qry	= str_replace($this->dbTableNames['keys'], $this->dbTableNames['names'], $qry);

		/** @var $stmt PDOStatement */
		$stmt	= $this->dbHandle->query($qry);

		$this->rowCount = $stmt->rowCount();

		$this->queryCounter++;
		return in_array($this->getQueryType($qry), array('select', 'show')) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
	}

	public function getQueryCounter()
	{
		return $this->queryCounter;
	}

	static public function formatDate($time)
	{
		return date('Y-m-d H:i:s', $time);
	}

	public function quote($str)
	{
		return $this->dbHandle->quote($str);
	}
}
