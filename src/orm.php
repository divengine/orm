<?php

/**
 * [[]] Div PHP Object Relational Mapping
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program as the file LICENSE.txt; if not, please see
 *
 * https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package divengine/orm
 * @author  Rafa Rodriguez @rafageist [https://rafageist.github.io]
 * @version 0.1.0
 *
 * @link    https://divengine.com/orm
 * @link    https://github.com/divengine/orm
 * @link    https://github.com/divengine/orm/wiki
 */

namespace divengine;

use PDOStatement;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use RuntimeException;
use PDO;

class orm
{

    /** @var int Map type OBJECT */
    public const OBJECT = 0;

    /** @var int Map type TABLE */
    public const TABLE = 1;

    /** @var int Map type RECORD */
    public const RECORD = 2;

    /** @var int Map type VIEW */
    public const VIEW = 3;

    /** @var int Map type PROCEDURE */
    public const PROCEDURE = 4;

    /** @var int Map type QUERY */
    public const QUERY = 5;

    /** @var int Map type SCHEMA */
    public const SCHEMA = 6;

    /** @var string Flag for define field that receive automatic data in the database engine when you insert */
    public const AUTOMATIC = 'AUTOMATIC-FLAG-DIVENGINE-ORM-5d3a408d0e06f8.81243238';

    /** @var string Flag for stop the loops */
    public const BREAK = 'DIV_ORM_BREAK';

    /**
     * Current version of lib
     *
     * @var string
     */
    private static $__version = '0.1.0';

    /** @var PDO Global PDO connection for all instances of ORM or their children */
    private static $__pdo_global;

    /** @var int Map type is the type of database object that you map */
    protected $__map_type = self::RECORD;

    /** @var string Map name is the name of object that you map (schema, table, view, ...) */
    protected $__map_name;

    /** @var string Map class is the class that receive the data from database */
    protected $__map_class;

    /** @var string Map schema is the name of schema where is located your database objects */
    protected $__map_schema;

    /** @var string Map identity is the SQL filter that identify records in a table (ex: id = ? ) */
    protected $__map_identity;

    /** @var array The data of the database object (record data or table data) */
    protected $__data;

    /** @var PDO Database connection with PDO */
    private $__pdo;

    /** @var PDOStatement */
    private $__last_statement;

    protected static $__meta_query_processor;

    /**
     * ORM constructor.
     *
     * @param array $data
     *
     * @throws ReflectionException
     */
    public function __construct($data = [])
    {
        // Set data
        $this->setData($data);
    }

    /**
     * Return current version of ORM
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::$__version;
    }

    /**
     * Get public data of object
     *
     * @param $obj
     *
     * @return array
     * @throws ReflectionException
     */
    public static function getPublicData($obj): array
    {
        $reflection = new ReflectionObject($obj);
        $properties = $reflection->getProperties();

        $data = [];
        foreach ($properties as $property) {
            $key = $property->getName();
            $key_underScore = self::getUnderScoreName($key);
            $key_camelCase = self::getCamelCaseName($key);
            $accessMethod = 'get'.ucfirst($key_camelCase);

            if ($reflection->hasMethod($accessMethod)) {
                $method = $reflection->getMethod($accessMethod);
                if ($method->isPublic()) {
                    $data[$key] = $obj->$accessMethod();
                }
            }

            if (!isset($data[$key])) {
                foreach (
                    [
                        $key,
                        $key_camelCase,
                        $key_underScore,
                    ] as $propertyName
                ) {
                    if ($reflection->hasProperty($propertyName)) {
                        $property = $reflection->getProperty($propertyName);
                        if ($property->isPublic()) {
                            $data[$key] = $obj->$propertyName;
                            break;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Create under score name from camel case
     *
     * @param $camelCaseName
     *
     * @return string
     */
    public static function getUnderScoreName(string $camelCaseName): string
    {
        $l = strlen($camelCaseName);
        $underScoreName = '';
        $upper = true;
        for ($i = 0; $i < $l; $i++) {
            if ($camelCaseName[$i] === '_') {
                $underScoreName .= '_';
                continue;
            }
            $upper = !$upper
                && $camelCaseName[$i] === strtoupper(
                    $camelCaseName[$i]
                );
            $underScoreName .= ($upper ? '_' : '').strtolower(
                    $camelCaseName[$i]
                );
        }

        return $underScoreName;
    }

    /**
     * Create under score name from camel case
     *
     * @param      $underScoreName
     *
     * @param bool $ucFirst
     *
     * @return string
     */
    public static function getCamelCaseName(
        string $underScoreName,
        $ucFirst = false
    ): string {
        $l = strlen($underScoreName);
        $camelCaseName = '';
        $upper = false;
        for ($i = 0; $i < $l; $i++) {
            $ch = $underScoreName[$i];
            if ($ch === '_') {
                $upper = true;
                continue;
            }
            if ($upper) {
                $camelCaseName .= strtoupper($ch);
                $upper = false;
                continue;
            }
            $camelCaseName .= strtolower($ch);
        }

        if ($ucFirst) {
            return ucfirst($camelCaseName);
        }

        return $camelCaseName;
    }

    /**
     * Try to set property to object
     *
     * @param object          $obj
     * @param string          $key
     * @param mixed           $value
     * @param ReflectionClass $reflection
     *
     * @return bool
     * @throws ReflectionException
     */
    public static function tryToSetProperty(
        $obj,
        string $key,
        $value,
        ReflectionClass $reflection = null
    ): bool {

        if ($reflection === null) {
            $reflection = new ReflectionObject($obj);
        }

        $key_underScore = self::getUnderScoreName($key);
        $key_camelCase = self::getCamelCaseName($key);
        $accessMethod = 'set'.ucfirst($key_camelCase);

        $set = false;
        if ($reflection->hasMethod($accessMethod)) {
            $method = $reflection->getMethod($accessMethod);
            if ($method->isPublic()) {
                $obj->$accessMethod($value);
                $set = true;
            }
        }

        if (!$set) {
            foreach ([$key, $key_camelCase, $key_underScore] as $propertyName) {
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    if ($property->isPublic()) {
                        $obj->$propertyName = $value;
                        $set = true;
                        break;
                    }
                }
            }
        }

        return $set;
    }

    /**
     * Connect to database
     *
     * @param PDO $pdo
     */
    public static function connectGlobal(PDO $pdo): void
    {
        self::$__pdo_global = $pdo;
    }

    /**
     * Get last PDO statement
     *
     * @return PDOStatement
     */
    public function getLastStatement(): PDOStatement
    {
        return $this->__last_statement;
    }

    /**
     * @param orm      $orm
     * @param array    $joins
     * @param string   $filters
     * @param array    $params
     * @param string   $fields
     * @param int|null $limit
     * @param int|null $offset
     * @param null     $orderBy
     *
     * @throws \ReflectionException
     */
    public function loadRelated(orm $orm, $joins, $filters = '', $params = [], $fields = '*', int $limit = null, int $offset = null, $orderBy = null): void
    {

        if ($orm->getMapType() === self::TABLE) {

            if ($fields !== '*') {
                self::prepareFields($fields);
            } else {
                $fields = " {$this->getMapName()}.* ";
            }

            $sql = "SELECT $fields FROM {$this->getMapName()} "." INNER JOIN {$orm->getMapName()} ";

            $joinList = [];
            foreach ($joins as $join) {
                $f1 = array_keys($join)[0];
                $f2 = array_values($join)[0];
                $joinList[] = " ON {$this->getMapName()}.$f1 = {$orm->getMapName()}.$f2 ";
            }

            $sql .= implode(' AND ', $joinList);

            $sql .= (empty($filters) ? '' : " WHERE $filters ").($orderBy === null ? '' : "ORDER BY $orderBy").($limit === null ? '' : " LIMIT $limit ").($offset === null ? '' : " OFFSET $offset ");
            $this->execute($sql, $params);
        }
    }

    /**
     * Map type
     *
     * @return int
     */
    public function getMapType(): int
    {
        return $this->__map_type;
    }

    /**
     * Prepare fields
     *
     * @param mixed $fields
     *
     * @return string
     */
    public static function prepareFields(&$fields): string
    {
        if (is_array($fields)) {
            $newFields = [];
            foreach ($fields as $alias => $field) {
                if (is_int($alias)) {
                    $newFields [] = $field;
                } else {
                    $newFields [] = $field.' AS '.$alias;
                }
            }
            $fields = implode(', ', $newFields);
        }

        return $fields;
    }

    /**
     * Map name
     *
     * @return string
     */
    public function getMapName(): string
    {
        if ($this->__map_name === null) {
            $this->__map_name = self::getUnderScoreName(
                pathinfo(get_class($this), PATHINFO_BASENAME)
            );
        }

        $schema = $this->__map_schema === null ? '' : $this->__map_schema.'.';

        return $schema.$this->__map_name;
    }

    /**
     * Prepare SQL and save last statement
     *
     * @param string $sql
     *
     * @return bool|PDOStatement
     */
    public function prepare(string $sql)
    {
        $this->__last_statement = $this->db()->prepare($sql);

        return $this->__last_statement;
    }

    /**
     * Get database connection
     *
     * @return PDO
     */
    public function db(): PDO
    {
        if ($this->__pdo === null) {
            if (self::$__pdo_global !== null) {
                $this->connect(self::$__pdo_global);
            }
        }

        return $this->__pdo;
    }

    /**
     * Connect to database
     *
     * @param PDO $pdo
     */
    public function connect(PDO $pdo): void
    {
        $this->__pdo = $pdo;
    }

    /**
     * @param string $filters
     * @param array  $params
     * @param string $fields
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function getAll(string $filters, array $params = [], $fields = '*')
    {
        $this->load(null, null, $fields, $filters, $params);

        return $this->getData();
    }

    /**
     * Load data from db
     *
     * @param int    $limit
     *
     * @param int    $offset
     * @param mixed  $fields
     * @param string $filters
     * @param array  $params
     *
     * @throws ReflectionException
     */
    public function load(int $limit = null, int $offset = null, $fields = '*', $filters = '', $params = []): void
    {
        self::prepareFields($fields);
        self::prepareFilter($filters);

        $sql = "SELECT $fields FROM {$this->getMapName()} "." $filters ".($limit === null ? '' : " LIMIT $limit ").($offset === null ? '' : " OFFSET $offset ");
        $this->execute($sql, $params);
    }

    /**
     * Prepare WHERE
     *
     * @param $filter
     *
     * @return array|string
     */
    public static function prepareFilter(&$filter)
    {
        if (is_array($filter)) {
            if (count($filter) > 1) {
                foreach ($filter as $key => $value) {
                    if (!is_numeric($key)) {
                        if (stripos($key, 'and ') !== 0
                            && stripos($key, 'or ') !== 0) {
                            $filter[$key] = "AND $key = $value";
                        } else {
                            $filter[$key] = "$key = $value";
                        }
                    }
                }
            }

            $filter = implode(' ', $filter);
        }

        $filter = trim($filter);
        if (!empty($filter)) {
            $filter = " WHERE $filter ";
        }

        return $filter;
    }

    /**
     * Get data
     *
     * @param bool $autoUpdate
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function getData($autoUpdate = true)
    {
        if ($autoUpdate) {
            $this->updateDataFromObject();
        }

        return $this->__data;
    }

    /**
     * Set data to object
     *
     * @param $data
     *
     * @throws ReflectionException
     */
    public function setData($data): void
    {
        if (empty($data)) {
            return;
        }

        $className = $this->getMapClass();
        $obj = null;

        if ($className === get_class($this)) {
            $obj = &$this;
        } elseif (class_exists($className)) {
            $obj = new $className();
        }

        if ($obj !== null) {
            $reflection = new ReflectionClass($className);

            switch ($this->__map_type) {
                case self::RECORD:
                    if (is_object($data)) {
                        $data = self::getPublicData($data);
                    }

                    $data = (array)$data;
                    foreach ($data as $key => $value) {
                        self::tryToSetProperty($obj, $key, $value, $reflection);
                    }
                    break;

                case self::TABLE:
                    $data = (array)$data;
                    foreach ($data as $itemIndex => $item) {
                        $clone = clone $obj;
                        if (get_class($this) !== $className) {
                            if ($reflection->getMethod('setData')) {
                                $clone->setData($item);
                            }
                        } else {
                            foreach ($item as $key => $value) {
                                self::tryToSetProperty($clone, $key, $value, $reflection);
                            }
                        }

                        $data[$itemIndex] = $clone;
                    }

                    break;
            }
        }

        $this->__data = $data;
    }

    /**
     * Update __data from object properties
     *
     * @param object $obj
     *
     * @return bool
     * @throws ReflectionException
     */
    public function updateDataFromObject($obj = null): bool
    {
        $set = false;

        if ($obj === null) {
            $obj = $this;
        }

        if (!is_object($obj)) {
            return false;
        }

        $reflection = new ReflectionObject($obj);

        if ($reflection->hasMethod('getMapType')) {
            switch ($obj->getMapType()) {
                case self::RECORD:
                    foreach ($this->__data ?? [] as $key => $value) {
                        $key_underScore = self::getUnderScoreName($key);
                        $key_camelCase = self::getCamelCaseName($key);
                        $accessMethod = 'get'.ucfirst($key_camelCase);

                        if ($reflection->hasMethod($accessMethod)) {
                            $method = $reflection->getMethod($accessMethod);
                            if ($method->isPublic()) {
                                $newValue = $obj->$accessMethod();
                                if ($newValue !== $this->__data[$key]) {
                                    $this->__data[$key] = $newValue;
                                    $set = true;
                                }
                            }
                        }

                        if (!$set) {
                            foreach ([$key, $key_camelCase, $key_underScore] as $propertyName) {
                                if ($reflection->hasProperty($propertyName)) {
                                    $property = $reflection->getProperty(
                                        $propertyName
                                    );
                                    if ($property->isPublic()) {
                                        $newValue = $obj->$propertyName;
                                        if ($newValue !== $this->__data[$key]) {
                                            $this->__data[$key] = $obj->$propertyName;
                                            $set = true;
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;

                case self::TABLE:
                    if (is_array($this->__data)) {
                        foreach ($this->__data as $key => $item) {
                            if (is_object($item)) {
                                $reflection = new ReflectionObject($item);
                                $methodName = 'updateDataFromObject';
                                if ($reflection->hasMethod($methodName)) {
                                    $method = $reflection->getMethod(
                                        $methodName
                                    );
                                    if ($method->isPublic()) {
                                        $set = $set || $this->__data[$key]->$methodName();
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
        }

        return $set;
    }

    /**
     * @param string $filters
     * @param array  $params
     * @param string $fields
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function getFirstItem(string $filters, array $params = [], $fields = '*')
    {
        $this->load(1, 0, $fields, $filters, $params);

        return $this->getItem(0);
    }

    /**
     * Get item of data
     *
     * @param $index
     *
     * @return mixed|null
     */
    public function getItem($index)
    {
        if (is_array($this->__data)) {
            if (array_key_exists($index, $this->__data)) {
                return $this->__data[$index];
            }
        }

        return null;
    }

    /**
     * @param mixed $obj
     * @param null  $index
     *
     * @throws ReflectionException
     */
    public function addItem($obj, $index = null): void
    {
        $obj = (object)$obj;
        $this->insert($obj);

        switch ($this->__map_type) {
            case self::RECORD:
                $this->setData($obj);
                break;
            case self::TABLE:
                if ($index === null) {
                    $this->__data[] = $obj;
                } else {
                    $this->__data[$index] = $obj;
                }
                break;
        }
    }

    /**
     * Add/insert object
     *
     * @param object $obj
     *
     * @return array | null
     * @throws ReflectionException
     */
    public function insert($obj = null): ?array
    {
        if ($obj === null) {
            if ($this->getMapClass() === get_class($this)) {
                $obj = &$this;
            } else {
                throw new RuntimeException(
                    'Null object can not be added to collection'
                );
            }
        }

        // getting values from object
        $reflection = new ReflectionObject($obj);
        $properties = $reflection->getProperties();
        $defaults = $reflection->getDefaultProperties();
        $values = [];

        foreach ($properties as $property) {
            $key = $property->getName();
            $value = self::tryGetProperty($obj, $key, $reflection);
            if (!is_object($value)
                && !(array_key_exists($key, $defaults) && $defaults[$key] === self::AUTOMATIC)
                && (strpos($key, '__') !== 0)) {
                $key = self::getUnderScoreName($key);
                //echo "new key $key<br/>";
                $values[$key] = $value;
            }
        }

        // set default values
        foreach ($defaults as $key => $value) {
            $key = self::getUnderScoreName($key);
            if (!array_key_exists($key, $values)
                && $value !== self::AUTOMATIC
                && strpos($key, '__') !== 0) {
                $values[$key] = $value;
            }
        }

        $values_count = count($values);
        $s_values = str_repeat('?,', $values_count - 1).'?';

        // build the query
        $sql = "INSERT INTO {$this->getMapName()} (".implode(',', array_keys($values)).") VALUES ($s_values) RETURNING *;";

        $st = $this->db()->prepare($sql);
        $st->execute(array_values($values));
        $result = $st->fetchAll(PDO::FETCH_OBJ);

        if (method_exists($obj, 'updateDataFromObject')) {
            $obj->updateDataFromObject();
            $obj->setData($result[0]);
        }

        return $result;
    }

    /**
     * Get map class
     *
     * @return string|null
     */
    public function getMapClass(): ?string
    {
        if ($this->__map_class === null) {
            if ($this->__map_type === self::RECORD || $this->__map_type === self::TABLE) {
                $this->__map_class = get_class($this);
            }
        }

        return $this->__map_class;
    }

    /**
     * Try get property
     *
     * @param      $obj
     * @param      $key
     * @param null $reflection
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function tryGetProperty($obj, $key, $reflection = null)
    {
        if ($reflection === null) {
            $reflection = new ReflectionObject($obj);
        }

        $keyUnderScore = self::getUnderScoreName($key);
        $keyCamelCase = self::getCamelCaseName($key);
        $accessMethod = 'get'.ucfirst($keyCamelCase);

        if ($reflection->hasMethod($accessMethod)) {
            $method = $reflection->getMethod($accessMethod);
            if ($method->isPublic()) {
                return $obj->$accessMethod();
            }
        }

        foreach ([$key, $keyCamelCase, $keyUnderScore] as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                if ($property->isPublic()) {
                    return $obj->$propertyName;
                }
            }
        }

        return null;
    }

    /**
     * Save object to database
     *
     * @param null $fields
     * @param null $identity
     *
     * @return array
     * @throws ReflectionException
     */
    public function save($fields = null, $identity = null)
    {

        if ($this->getMapType() === self::RECORD) {
            if ($fields === null) {
                $fields = $this->getData();
            }

            if ($identity === null) {
                $identity = $this->__map_identity;
            }

            if ($identity === null) {
                throw new RuntimeException(
                    'Save fail: '.get_class($this).' not have identity'
                );
            }

            $sets = [];
            foreach ($fields as $field => $value) {
                $sets[] = " $field = :$field ";
            }

            $sql = 'UPDATE '.$this->getMapName().' SET '.implode(', ', $sets).' WHERE '.$identity;

            $st = $this->db()->prepare($sql);
            $st->execute(array_values($fields));

            $result = $st->fetchAll(PDO::FETCH_OBJ);

            $this->updateDataFromObject();

            return $result;
        }

        return null;
    }

    /**
     * Apply closure to each item
     *
     * @param $closure
     */
    public function each($closure): void
    {
        if (is_array($this->__data)) {
            foreach ($this->__data as $item) {
                $result = $closure($item);
                if ($result === self::BREAK) {
                    break;
                }
            }
        }
    }

    /**
     * Force to boolean value
     *
     * @param $v
     *
     * @return bool
     */
    public function strBool($v): bool
    {
        if ($v === null || $v === false) {
            return 'false';
        }

        return 'true';
    }

    /**
     * Delete records
     *
     * @param string $filter
     * @param array  $params
     *
     * @throws \ReflectionException
     */
    public function delete($filter = '', $params = [])
    {
        if ($this->__map_type === self::RECORD) {
            $filter = $this->__map_identity;
            $paramsX = $this->getRawData();
            $params = [];
            foreach ($paramsX as $p => $v) {
                if (stripos($filter,':'.$p) !== false)
                    $params[$p] = $v;
            }
        }

        self::prepareFilter($filter);

        $sql = 'DELETE FROM '.$this->getMapName()." $filter ";

        $st = $this->db()->prepare($sql);

        $st->execute($params);
    }

    /**
     * @param $procesor
     */
    static function setMetaQueryProcessor($procesor)
    {
        self::$__meta_query_processor = $procesor;
    }


    /**
     * @param       $metaQuery
     * @param array $params
     * @param array $args
     *
     * @return array
     */
    public function rawMetaQuery($metaQuery, $params = [], $args = [])
    {

        $query = self::processMetaQuery($metaQuery, $args);

        return $this->rawQuery($query, $params);
    }

    /**
     * @param       $metaQuery
     * @param array $params
     * @param array $args
     *
     * @return array
     * @throws \ReflectionException
     */
    public function metaQuery($metaQuery, $params = [], $args = [])
    {
        $data = $this->rawMetaQuery($metaQuery, $params, $args);

        $data = $this->processQueryResult($data);

        return $data;
    }

    /**
     * @param string $sql
     * @param        $params
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function execute(string $sql, $params)
    {
        $data = $this->rawQuery($sql, $params);

        $data = $this->processQueryResult($data);

        return $data;
    }

    /**
     * @param string $sql
     * @param        $params
     *
     * @return array
     */
    public function rawQuery(string $sql, $params): array
    {
        $st = $this->prepare($sql);
        $st->execute($params);
        $data = $st->fetchAll(PDO::FETCH_OBJ);

        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

    /**
     * @param $metaQuery
     * @param $args
     *
     * @return mixed
     */
    public static function processMetaQuery($metaQuery, $args)
    {
        $t1 = microtime(true);
        $processor = self::$__meta_query_processor;
        $query = $metaQuery;
        if ($processor !== null) {
            $query = $processor($query, $args);
        }

        echo $query."<br/>";
        $t2 = microtime(true);
        echo number_format($t2 - $t1, 5);

        return $query;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \ReflectionException
     */
    public function processQueryResult(array $data): array
    {
        switch ($this->__map_type) {
            case self::RECORD:
                if (isset($data[0])) {
                    $this->setData($data[0]);
                }
                break;
            case self::TABLE:
                $this->setData($data);
                break;
        }

        return $data;
    }

    /**
     * Total of rows loaded
     *
     * @return int
     */
    public function count(): int
    {
        switch ($this->__map_type) {
            case self::RECORD:
                return 1;
            case self::TABLE:
                if (is_array($this->__data) || is_object($this->__data)) {
                    return count($this->__data);
                }
        }

        return 0;
    }

    /**
     * Get raw data
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getRawData()
    {

        if ($this->__map_type === self::RECORD) {
            return $this->getData(false);
        }

        $rawData = [];
        $this->each(function ($item) use (&$rawData) {
            $rawData[] = $this->getData(false);
        });

        return $rawData;
    }

    /**
     * Return true if exists records
     *
     * @param $filters
     * @param $params
     * @param $firstItem
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function exists($filters, $params, &$firstItem = null): bool {

        if ($this->__map_type === self::RECORD) {
            // TODO: search inside record ?
            return false;
        }

        $firstItem = $this->getFirstItem($filters, $params);

        return $firstItem !== null;
    }
}