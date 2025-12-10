<?php

namespace App\Models;

use App\Core\Database;

abstract class Model
{
    protected $table;
    protected $fillable = [];
    protected $casts = [];
    protected $attributes = [];
    protected $originalAttributes = [];
    protected $exists = false;

    public function __construct($attributes = [])
    {
        $this->fill($attributes);
        $this->originalAttributes = $this->attributes;
    }

    public static function create($attributes = [])
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function find($id)
    {
        return static::where('id', '=', $id)->first();
    }

    public static function where($column, $operator = null, $value = null)
    {
        $query = new QueryScope(new static);
        return $query->where($column, $operator, $value);
    }

    public static function all()
    {
        return static::where('1=1')->get();
    }

    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function save()
    {
        $db = Database::getInstance();

        if ($this->exists) {
            $changed = array_diff_assoc($this->attributes, $this->originalAttributes);
            if (empty($changed)) {
                return true;
            }

            $where = 'id = ?';
            $db->update($this->table, $changed, $where, [$this->attributes['id']]);
        } else {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
            $id = $db->insert($this->table, $this->attributes);
            $this->attributes['id'] = $id;
            $this->exists = true;
        }

        $this->originalAttributes = $this->attributes;
        return true;
    }

    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $db = Database::getInstance();
        return $db->delete($this->table, 'id = ?', [$this->attributes['id']]) > 0;
    }

    public function __get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
        return null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function toJson()
    {
        return json_encode($this->attributes);
    }
}

class QueryScope
{
    protected $model;
    protected $wheres = [];
    protected $orderBys = [];
    protected $limit;
    protected $offset;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function where($column, $operator = null, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBys[] = "{$column} {$direction}";
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function first()
    {
        $this->limit = 1;
        $data = $this->buildQuery();
        if ($data) {
            return new $this->model($data[0]);
        }
        return null;
    }

    public function get()
    {
        $data = $this->buildQuery();
        $models = [];
        foreach ($data as $row) {
            $model = new $this->model($row);
            $model->exists = true;
            $models[] = $model;
        }
        return $models;
    }

    public function count()
    {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM {$this->model->table}" . $this->buildWhere();
        $result = $db->selectOne($sql, $this->getBindings());
        return $result['count'] ?? 0;
    }

    public function update($data)
    {
        $db = Database::getInstance();
        $sql = "UPDATE {$this->model->table} SET " . implode(',', array_map(fn($k) => "$k = ?", array_keys($data))) . $this->buildWhere();
        $params = array_merge(array_values($data), $this->getBindings());
        return $db->raw($sql, $params)->rowCount();
    }

    public function delete()
    {
        $db = Database::getInstance();
        $sql = "DELETE FROM {$this->model->table}" . $this->buildWhere();
        return $db->raw($sql, $this->getBindings())->rowCount();
    }

    protected function buildQuery()
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM {$this->model->table}" . $this->buildWhere();

        if ($this->orderBys) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBys);
        }

        if ($this->limit) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset) {
            $sql .= " OFFSET " . $this->offset;
        }

        return $db->select($sql, $this->getBindings());
    }

    protected function buildWhere()
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];
        foreach ($this->wheres as $where) {
            $conditions[] = "{$where['column']} {$where['operator']} ?";
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    protected function getBindings()
    {
        return array_map(fn($w) => $w['value'], $this->wheres);
    }
}
