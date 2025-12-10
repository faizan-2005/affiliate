<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    protected static $instance;
    protected $pdo;
    protected $lastQuery;
    protected $lastError;

    private function __construct()
    {
        $config = config('database.connections.mysql');
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        try {
            $this->lastQuery = $sql;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            log_error('Database Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    public function select($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function selectOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        $this->query($sql, $values);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }

        $sql = "UPDATE {$table} SET " . implode(',', $set) . " WHERE {$where}";
        $params = array_merge(array_values($data), (array)$whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    public function raw($sql, $params = [])
    {
        return $this->query($sql, $params);
    }

    public function table($name)
    {
        return new QueryBuilder($this, $name);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}

class QueryBuilder
{
    protected $db;
    protected $table;
    protected $wheres = [];
    protected $limits;
    protected $offset;
    protected $orderBy = [];
    protected $selects = ['*'];
    protected $joins = [];

    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function select(...$columns)
    {
        $this->selects = array_merge($this->selects, $columns);
        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'and',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'or',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function limit($limit)
    {
        $this->limits = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function get()
    {
        $sql = $this->toSql();
        return $this->db->select($sql, $this->getBindings());
    }

    public function first()
    {
        $this->limit(1);
        $sql = $this->toSql();
        return $this->db->selectOne($sql, $this->getBindings());
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $sql .= $this->buildWhere();
        $result = $this->db->selectOne($sql, $this->getBindings());
        return $result['count'] ?? 0;
    }

    public function toSql()
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";
        $sql .= $this->buildWhere();

        if ($this->orderBy) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limits) {
            $sql .= " LIMIT " . $this->limits;
        }

        if ($this->offset) {
            $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    protected function buildWhere()
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];
        foreach ($this->wheres as $where) {
            $operator = $where['operator'];
            if ($where['type'] === 'or') {
                $conditions[] = 'OR ' . $where['column'] . ' ' . $operator . ' ?';
            } else {
                if (!empty($conditions) && strpos($conditions[count($conditions) - 1], 'OR') !== 0) {
                    $conditions[] = 'AND ' . $where['column'] . ' ' . $operator . ' ?';
                } else {
                    $conditions[] = $where['column'] . ' ' . $operator . ' ?';
                }
            }
        }

        return ' WHERE ' . implode(' ', $conditions);
    }

    protected function getBindings()
    {
        $bindings = [];
        foreach ($this->wheres as $where) {
            $bindings[] = $where['value'];
        }
        return $bindings;
    }

    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($data)
    {
        $where = $this->buildWhereForUpdate();
        $bindings = $this->getBindings();
        $params = array_merge(array_values($data), $bindings);

        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }

        $sql = "UPDATE {$this->table} SET " . implode(',', $set) . " WHERE {$where}";
        return $this->db->raw($sql, $params)->rowCount();
    }

    public function delete()
    {
        $where = $this->buildWhereForUpdate();
        $sql = "DELETE FROM {$this->table} WHERE {$where}";
        return $this->db->raw($sql, $this->getBindings())->rowCount();
    }

    protected function buildWhereForUpdate()
    {
        if (empty($this->wheres)) {
            return '1=1';
        }

        $conditions = [];
        foreach ($this->wheres as $i => $where) {
            if ($i === 0) {
                $conditions[] = $where['column'] . ' ' . $where['operator'] . ' ?';
            } else {
                $prefix = $where['type'] === 'or' ? 'OR' : 'AND';
                $conditions[] = $prefix . ' ' . $where['column'] . ' ' . $where['operator'] . ' ?';
            }
        }
        return implode(' ', $conditions);
    }
}

function db()
{
    return Database::getInstance();
}
