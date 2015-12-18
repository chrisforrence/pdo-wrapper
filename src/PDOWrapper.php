<?php namespace Forrence;

use PDO;
use PDOException;

class PDOWrapper extends PDO
{
    private static $instance = null;
    private $connection;

    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Instantiates a new connection to a database
     *
     * @param $database
     * @param $hostname
     * @param $username
     * @param $password
     */
    public function __construct($database, $hostname, $username, $password)
    {
        try {
            $this->connection = new PDO("mysql:dbname=$database;host=$hostname",
                $username,
                $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    /**
     *
     * Creates an instance of a PDO wrapper
     *
     * @param $database
     * @param $hostname
     * @param $username
     * @param $password
     * @return PDOWrapper|null
     */
    public static function getInstance($database, $hostname, $username, $password)
    {
        if (self::$instance == null || empty(self::$instance)) {
            try {
                self::$instance = new PDOWrapper($database, $hostname, $username, $password);
            } catch (PDOException $p) {
                error_log("PDOException caught: " . $p->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Runs a SELECT statement
     *
     * @param $query
     * @param array $args
     * @param null $id_to_map
     * @return array
     */
    public function select($query, $args = array(), $id_to_map = null)
    {
        $ret = array();
        try {
            $sth = $this->connection->prepare($query);
            if (empty($args)) {
                $sth->execute();
            } else {
                $sth->execute($args);
            }
            $ret = $sth->fetchAll();

            if($id_to_map !== null && count($ret) > 0 && array_key_exists($id_to_map, $ret[0])) {
                $tmp = array();
                foreach($ret as $row) {
                    $tmp[$row[$id_to_map]] = $row;
                }
                $ret = $tmp;
            }

        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            error_log('Query : ' . $query);
        }
        return $ret;
    }

    /**
     * Runs a SELECT statement when only one result is expected
     *
     * @param $query
     * @param array $args
     * @return null|mixed
     */
    public function selectOne($query, $args = array())
    {
        $ret = null;
        try {
            $sth = $this->connection->prepare($query);
            if (empty($args)) {
                $sth->execute();
            } else {
                $sth->execute($args);
            }
            $ret = $sth->fetch();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            error_log('Query : ' . $query);
        }
        return $ret;
    }

    /**
     * Runs an UPDATE statement
     * @param $query
     * @param array $args
     * @return int The number of rows affected
     */
    public function update($query, $args = array())
    {
        try {
            $this->connection->beginTransaction();
            $sth = $this->connection->prepare($query);
            if (empty($args)) {
                $sth->execute();
            } else {
                $sth->execute($args);
            }
            $ret = $sth->rowCount();
            $this->connection->commit();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            error_log('Query : ' . $query);
            $this->connection->rollBack();
            $ret = 0;
        }
        return $ret;
    }

    /**
     * Runs a DELETE statement
     *
     * @param $query
     * @param array $args
     * @return int The number of rows affected
     */
    public function delete($query, $args = array())
    {
        try {
            $this->connection->beginTransaction();
            $sth = $this->connection->prepare($query);
            if (empty($args)) {
                $sth->execute();
            } else {
                $sth->execute($args);
            }
            $ret = $sth->rowCount();
            $this->connection->commit();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            error_log('Query : ' . $query);
            $this->connection->rollBack();
            $ret = 0;
        }
        return $ret;
    }

    /**
     * Runs an INSERT statement
     *
     * @param $query
     * @param array $args
     * @return int|bool False if the query failed or the insertion ID
     */
    public function insert($query, $args = array())
    {
        $ret = false;
        try {
            $this->connection->beginTransaction();
            $sth = $this->connection->prepare($query);
            if (empty($args)) {
                $sth->execute();
            } else {
                $sth->execute($args);
            }
            $ret = $this->getLastInsertID();
            $this->connection->commit();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            error_log('Query : ' . $query);
            $this->connection->rollBack();
        }
        return $ret;
    }

    /**
     * Runs an SQL query
     *
     * @param $query
     * @param array $args
     * @return array|int|string
     */
    public function execute($query, $args = array())
    {
        $tokens = explode(" ", $query);
        try {
            $this->connection->beginTransaction();
            $sth = $this->connection->prepare($query);
            if (empty($args)) {
                $sth->execute();
            } else {
                $sth->execute($args);
            }
            if (strtoupper($tokens[0]) == "SELECT") {
                $sth->setFetchMode(PDO::FETCH_ASSOC);
                $ret = $sth->fetchAll();
            } else if (strtoupper($tokens[0]) == "INSERT") {
                $ret = $this->getLastInsertID();
            } else {
                $ret = $sth->rowCount();
            }
            $this->connection->commit();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            error_log('Query : ' . $query);
            $this->connection->rollBack();
            $ret = -1;
        }
        return $ret;
    }

    private function getLastInsertID()
    {
        return $this->connection->lastInsertId();
    }
}
