<?php

/**
 * Extends CI_Model with ORM query
 * @author Benny <benny_a8@live.com>
 * @copyright ©2012-2014 http://github.com/bennya8
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */
class MY_Model extends CI_Model
{

    /**
     * Primary key
     * @var string
     */
    public $pk = 'id';

    /**
     * Table prefix
     * @var string
     */
    public $tablePrefix = '';

    /**
     * Table name
     * @var
     */
    public $table;

    /**
     * Table columns
     * @var array
     */
    public $columns = array();

    /**
     * Sql error message
     * @var string
     */
    public $errorMessage = '';

    /**
     * Sql error message
     * @var array
     */
    public $errorList = '';

    /**
     * SELECT statement template
     * @var string
     */
    private $_select = 'SELECT @FIELD FROM @TABLE @JOIN@WHERE@GROUP@ORDER@LIMIT';

    /**
     * INSERT statement template
     * @var string
     */
    private $_insert = 'INSERT INTO @TABLE @DATA';

    /**
     * UPDATE statement template
     * @var string
     */
    private $_update = 'UPDATE @TABLE SET @DATA @WHERE';

    /**
     * DELETE statement template
     * @var string
     */
    private $_delete = 'DELETE FROM @TABLE @WHERE';

    /**
     * Query condition
     * @var array
     */
    private $_condition = array();

    /**
     * Query data
     * @var array
     */
    private $_data = array();

    /**
     * Available chains method
     * @var array
     */
    private $_chains = array('table', 'field', 'select', 'update', 'insert', 'where', 'join', 'group', 'order', 'limit');

    /**
     * Database instance
     */
    private $_db = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_db =& get_instance()->db;
        if (empty($this->_db)) {
            show_error('connect failed, please check your database config');
        }
        if (empty($this->_table)) {
            $this->table = $this->tablePrefix . preg_replace('/(_m|_model)?$/', '', strtolower(get_class($this)));
        }
    }

    /**
     * Get table full name
     * @param string $name
     * @return string
     */
    public function getTableName($name = null)
    {
        return empty($name) ? $this->tablePrefix . $this->table : $this->tablePrefix . $name;
    }

    /**
     * Find record by primary key
     * @param int $id
     * @return bool
     */
    public function find($id = null)
    {
        if (empty($id)) return false;
        return $this->table($this->table)
            ->where("`{$this->pk}` = '{$id}'")
            ->limit('1')
            ->select();
    }

    /**
     * Find records by column name
     * @example: $this->findByUsername("abc") equals "WHERE `username` = 'abc'"
     * @param $name
     * @param $value
     * @return mixed
     */
    public function findBy($name, $value)
    {
        return $this->table($this->table)
            ->where("`{$name}` = '{$value}'")
            ->select();
    }

    /**
     * Find records with given condition (support chains invoke)
     * @example:
     * $this->table($table) [optional] table name will be automatic set before method invoke
     *      ->where($where)
     *      ->join($group)
     *      ->limit($limit)
     *      ->findAll();
     * @param array $condition
     * @param array $condition
     * @return mixed
     */
    public function findAll($condition = array())
    {
        return $this->table($this->table)
            ->select($condition);
    }

    /**
     * Insert records
     * @param array $data
     * @return mixed
     */
    public function add($data = null)
    {
        return $this->table($this->table)
            ->insert($data);
    }

    /**
     * Update records with condition
     * @param array $data
     * @param string / array $where
     * @return mixed
     */
    public function save($data = null, $where = null)
    {
        return $this->table($this->table)
            ->update($data, $where);
    }

    /**
     * Delete records with condition
     * @param null $where
     * @return mixed
     */
    public function remove($where = null)
    {
        return $this->table($this->getTableName())
            ->delete($where);
    }

    /**
     * Create a sql and send it to database, return result if query success
     * @param $sql
     * @return array|bool
     */
    public function query($sql)
    {
        $query = $this->_db->query($sql);
        if (!$query) return false;
        $list = array();
        foreach ($query->result_array() as $row) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Create a sql and send it to database, return affected rows if execute success
     * @param $sql
     * @return array|bool
     */
    public function execute($sql)
    {
        $query = $this->_db->query($sql);
        if (!$query) return false;
        return $this->_db->affected_rows();
    }

    /**
     * Commit a transaction
     * @return bool
     */
    public function commit()
    {
        return $this->_db->commit();
    }

    /**
     * Rollback a transaction
     * @return bool
     */
    public function rollback()
    {
        return $this->_db->rollback();
    }

    /**
     * Disable auto commit and start a transaction
     * @return bool
     */
    public function begin()
    {
        $this->_db->trans_off();
        return $this->_db->trans_start();;
    }

    /**
     * The insert ID number when performing database inserts
     * @return mixed
     */
    public function lastInsertId()
    {
        return $this->_db->insert_id();
    }

    /**
     * Displays the number of affected rows, when doing "write" type queries (insert, update, etc.).
     * @return mixed
     */
    public function affectedRows()
    {
        return $this->_db->affected_rows();
    }

    /**
     * Build a select sql and send a db query
     * @access public
     * @param array $condition
     * @return array query result
     */
    public function select($condition = array())
    {
        if (!empty($condition) && is_array($condition)) {
            $this->_condition = array_merge($condition, $this->_condition);
        }
        $sql = str_replace(array(
            '@FIELD',
            '@TABLE',
            '@JOIN',
            '@WHERE',
            '@GROUP',
            '@ORDER',
            '@LIMIT'
        ), array(
            $this->parseField(),
            $this->parseTable(),
            $this->parseJoin(),
            $this->parseWhere(),
            $this->parseGroup(),
            $this->parseOrder(),
            $this->parseLimit()
        ), $this->_select);

        $this->_condition = array();
        return $this->query($sql);
    }

    /**
     * Build a insert sql and send a db query
     * @access public
     * @param array $data
     * @return array affective rows
     */
    public function insert($data = null)
    {
        if (!empty($data) && is_array($data)) {
            $this->_data = $data;
        } else {
            show_error('insert data can not be empty');
        }
        $sql = str_replace(array(
            '@TABLE',
            '@DATA'
        ), array(
            $this->parseTable(),
            $this->parseInsert()
        ), $this->_insert);

        $this->_data = array();
        return $this->execute($sql);
    }

    /**
     * Build an update sql and send a db query
     * @access public
     * @param array $data
     * @param array $where
     * @return array affective rows
     */
    public function update($data = null, $where = null)
    {
        $this->setCondition('where', $where);
        if (empty($this->_condition['where'])) {
            show_error('execute an update statement without setting where condition');
        }

        if (!empty($data) && is_array($data)) {
            $this->_data = $data;
        } else {
            show_error('update data can not be empty');
        }

        $sql = str_replace(array(
            '@TABLE',
            '@DATA',
            '@WHERE'
        ), array(
            $this->parseTable(),
            $this->parseUpdate(),
            $this->parseWhere()
        ), $this->_update);

        $this->_data = array();
        $this->_condition = array();
        return $this->execute($sql);
    }

    /**
     * Build a delete sql and send a db query
     * @access public
     * @param array $where
     * @return array affective rows
     */
    public function delete($where = null)
    {
        $this->setCondition('where', $where);
        if ($this->safe && empty($this->_condition['where'])) {
            show_error('execute a delete statement without setting where condition');
        }
        $sql = str_replace(array(
            '@TABLE',
            '@WHERE'
        ), array(
            $this->parseTable(),
            $this->parseWhere()
        ), $this->_delete);

        $this->_condition = array();
        return $this->execute($sql);
    }

    /**
     * Convert insert data to sql segment
     * @access protected
     * @return string
     */
    protected function parseInsert()
    {
        $parseInsert = '';
        if (!empty($this->_data) && is_array($this->_data)) {
            $k = array_keys($this->_data);
            $v = array_values($this->_data);
            $parseInsert .= '(`' . implode('`,`', $k) . '`) VALUES ';
            $parseInsert .= '(\'' . implode('\',\'', $v) . '\')';
        }
        return $parseInsert;
    }

    /**
     * Convert update data to sql segment
     * @access protected
     * @return string
     */
    protected function parseUpdate()
    {
        $parseUpdate = '';
        if (!empty($this->_data) && is_array($this->_data)) {
            foreach ($this->_data as $k => $v) {
                $parseUpdate .= '`' . $k . '` = \'' . $v . '\',';
            }
            $parseUpdate = rtrim($parseUpdate, ',');
        }
        return $parseUpdate;
    }


    /**
     * Convert field data to sql segment
     * @access protected
     * @return string
     */
    protected function parseField()
    {
        $parseField = '*';
        if ($this->checkCondition('field')) {
            if (is_string($this->_condition['field'])) {
                $parseField = $this->_condition['field'];
            } else if (is_array($this->_condition['field'])) {
                $parseField = implode(',', $this->_condition['field']);
            }
        }
        return $parseField;
    }

    /**
     * set a table name to sql segment
     * @access protected
     * @return string
     */
    protected function parseTable()
    {
        $parseTable = '';
        if ($this->checkCondition('table')) {
            if (is_string($this->_condition['table'])) {
                $parseTable = $this->_condition['table'];
            } else if (is_array($this->_condition['table'])) {
                $parseTable = implode(',', $this->_condition['table']);
            }
        }
        return $parseTable;
    }

    /**
     * Convert where condition data to sql segment
     * @access protected
     * @return string
     */
    protected function parseWhere()
    {
        $parsedWhere = '';
        if ($this->checkCondition('where')) {
            if (is_string($this->_condition['where'])) {
                $parsedWhere = 'WHERE ' . $this->_condition['where'];
            } else if (is_array($this->_condition['where'])) {
                $parsedWhere = 'WHERE ';
                foreach ($this->_condition['where'] as $k => $v) {
                    if (isset($v[0]) && isset($v[1])) {
                        switch (strtolower($v[0])) {
                            case 'in':
                                $where = ' IN ';
                                if (is_array($v[1])) {
                                    $v[1] = '(\'' . implode('\',\'', $v[1]) . '\')';
                                } else if (is_string($v[1])) {
                                    $v[1] = '(' . $v[1] . ')';
                                }
                                break;
                            case 'notin':
                                $where = ' NOT IN ';
                                if (is_array($v[1])) {
                                    $v[1] = '(\'' . implode('\',\'', $v[1]) . '\')';
                                } else if (is_string($v[1])) {
                                    $v[1] = '(' . $v[1] . ')';
                                }
                                break;
                            case 'neq':
                                $where = ' != ';
                                break;
                            case 'lteq':
                                $where = ' <= ';
                                break;
                            case 'gteq':
                                $where = ' >= ';
                                break;
                            case 'lt':
                                $where = ' < ';
                                break;
                            case 'gt':
                                $where = ' > ';
                                break;
                            case 'like':
                                $where = ' LIKE ';
                                break;
                            default:
                                $where = ' = ';
                        }
                        $logic = isset($v[2]) ? ' ' . $v[2] : '';
                        $parsedWhere .= $k . $where . $v[1] . $logic . ' ';
                    }
                }
            }
        }
        return $parsedWhere;
    }

    /**
     * Convert join data to sql segment
     * @access protected
     * @return string
     */
    protected function parseJoin()
    {
        $parseJoin = '';
        if ($this->checkCondition('join')) {
            if (is_array($this->_condition['join'])) {
                foreach ($this->_condition['join'] as $k => $v) {
                    if (isset($v[0]) && isset($v[1])) {
                        switch (strtolower($k)) {
                            case 'right':
                                $parseJoin = 'RIGHT JOIN ';
                                break;
                            case 'inner':
                                $parseJoin = 'INNER JOIN ';
                                break;
                            case 'union':
                                $parseJoin = 'UNION JOIN ';
                                break;
                            default:
                                $parseJoin = 'LEFT JOIN ';
                                break;
                        }
                        $parseJoin .= $v[0] . ' ON ' . $v[1] . ' ';
                    }
                }
            } elseif (is_string($this->_condition['join'])) {
                $parseJoin = $this->_condition['join'];
            }
        }
        return $parseJoin;
    }

    /**
     * Convert group data to sql segment
     * @access protected
     * @return string
     */
    protected function parseGroup()
    {
        $parsedGroup = '';
        if ($this->checkCondition('group')) {
            if (is_string($this->_condition['group'])) {
                $parsedGroup = 'GROUP BY ' . $this->_condition['group'];
            } else if (is_array($this->_condition['group'])) {
                $parsedGroup = 'GROUP BY ' . implode(',', $this->_condition['group']);
            }
        }
        return $parsedGroup;
    }

    /**
     * Convert order data to sql segment
     * @access protected
     * @return string
     */
    protected function parseOrder()
    {
        $parsedOrder = '';
        if ($this->checkCondition('order')) {
            if (is_string($this->_condition['order'])) {
                $parsedOrder = 'ORDER BY ' . $this->_condition['order'];
            } else if (is_array($this->_condition['order'])) {
                $parsedOrder = 'ORDER BY ' . implode(',', $this->_condition['order']);
            }
        }
        return $parsedOrder;
    }

    /**
     * Convert limit data to sql segment
     * @access protected
     * @return string
     */
    protected function parseLimit()
    {
        $parsedLimit = '';
        if ($this->checkCondition('limit')) {
            if (is_string($this->_condition['limit']) || is_numeric($this->_condition['limit'])) {
                $parsedLimit = 'LIMIT ' . $this->_condition['limit'];
            } else if (is_array($this->_condition['limit'])) {
                $parsedLimit = 'LIMIT ' . implode(',', $this->_condition['limit']);
            }
        }
        return $parsedLimit;
    }

    /**
     * Check whether given key in condition exists
     * @param string $key
     * @return boolean
     */
    public function checkCondition($key)
    {
        return isset($this->_condition[$key]) && !empty($this->_condition[$key]) ? true : false;
    }

    /**
     * Chains invoke set condition
     * @param $key
     * @param $value
     */
    public function setCondition($key, $value)
    {
        $this->_condition[$key] = $value;
    }

    /**
     * Chains invoke mechanism
     */
    public function __call($method, $args = array())
    {
        if (in_array($method, $this->_chains)) {
            $this->setCondition($method, $args[0]);
            return $this;
        } elseif (substr($method, 0, 6) === 'findBy') {
            $method = ltrim(strtolower(preg_replace("/[A-Z]/", "_\\0", substr($method, 6))), '_');
            if (isset($args[0])) {
                return $this->findBy($method, $args[0]);
            } else {
                return false;
            }
        } elseif (method_exists($this->_db, $method)) {
            $reflectMethod = new ReflectionMethod($this->_db, $method);
            $reflectMethod->invokeArgs($this->_db, $args);
        } else {
            show_error('invoke not exists method');
        }
    }

}