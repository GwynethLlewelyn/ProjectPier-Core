<?php

  /**
  * BasePlugins class
  *
  * @http://www.projectpier.org/
  */
  abstract class BasePlugins extends DataManager {

    /**
    * Column name => Column type map
    *
    * @var array
    * @static
    */
    static private $columns = array('plugin_id' => DATA_TYPE_INTEGER, 'name' => DATA_TYPE_STRING, 'installed' => DATA_TYPE_BOOLEAN);

    /**
    * Construct
    *
    * @return BasePlugins
    */
    function __construct() {
      parent::__construct('Plugin', 'plugins', true);
    } // __construct

    // -------------------------------------------------------
    //  Description methods
    // -------------------------------------------------------

    /**
    * Return array of object columns
    *
    * @access public
    * @param void
    * @return array
    */
    statuc function getColumns() {
      return array_keys(self::$columns);
    } // getColumns

    /**
    * Return column type
    *
    * @access public
    * @param string $column_name
    * @return string
    */
    static function getColumnType($column_name) {
      if (isset(self::$columns[$column_name])) {
        return self::$columns[$column_name];
      } else {
        return DATA_TYPE_STRING;
      } // if
    } // getColumnType

    /**
    * Return array of PK columns. If only one column is PK returns its name as string
    *
    * @access public
    * @param void
    * @return array or string
    */
    static function getPkColumns() {
      return 'plugin_id';
    } // getPkColumns

    /**
    * Return name of first auto_incremenent column if it exists
    *
    * @access public
    * @param void
    * @return string
    */
    static function getAutoIncrementColumn() {
      return NULL;
    } // getAutoIncrementColumn

    // -------------------------------------------------------
    //  Finders
    // -------------------------------------------------------

    /**
    * Do a SELECT query over database with specified arguments
    *
    * @access public
    * @param array $arguments Array of query arguments. Fields:
    *
    *  - one - select first row
    *  - conditions - additional conditions
    *  - order - order by string
    *  - offset - limit offset, valid only if limit is present
    *  - limit
    *
    * @return one or Plugins objects
    * @throws DBQueryError
    */
    static function find($arguments = null) {
      if (isset($this) && instance_of($this, 'Plugins')) {
        return parent::find($arguments);
      } else {
        return Plugins::instance()->find($arguments);
        //$instance =& Plugins::instance();
        //return $instance->find($arguments);
      } // if
    } // find

    /**
    * Find all records
    *
    * @access public
    * @param array $arguments
    * @return one or Plugins objects
    */
    static function findAll($arguments = null) {
      trace(__FILE__,'findAll()');
      if (isset($this) && instance_of($this, 'Plugins')) {
        trace(__FILE__,'findAll() - parent::findAll()');
        return parent::findAll($arguments);
      } else {
        return Plugins::instance()->findAll($arguments);
        //$instance =& Plugins::instance();
        //return $instance->findAll($arguments);
      } // if
    } // findAll

    /**
    * Find one specific record
    *
    * @access public
    * @param array $arguments
    * @return Plugin
    */
    static function findOne($arguments = null) {
      if (isset($this) && instance_of($this, 'Plugins')) {
        return parent::findOne($arguments);
      } else {
        return Plugins::instance()->findOne($arguments);
        //$instance =& Plugins::instance();
        //return $instance->findOne($arguments);
      } // if
    } // findOne

    /**
    * Return object by its PK value
    *
    * @access public
    * @param mixed $id
    * @param boolean $force_reload If true cache will be skipped and data will be loaded from database
    * @return Plugin
    */
    static function findById($id, $force_reload = false) {
      if (isset($this) && instance_of($this, 'Plugins')) {
        return parent::findById($id, $force_reload);
      } else {
        return Plugins::instance()->findById($id, $force_reload);
        //$instance =& Plugins::instance();
        //return $instance->findById($id, $force_reload);
      } // if
    } // findById

    /**
    * Return number of rows in this table
    *
    * @access public
    * @param string $conditions Query conditions
    * @return integer
    */
    static function count($condition = null) {
      if (isset($this) && instance_of($this, 'Plugins')) {
        return parent::count($condition);
      } else {
        return Plugins::instance()->count($condition);
        //$instance =& Plugins::instance();
        //return $instance->count($condition);
      } // if
    } // count

    /**
    * Delete rows that match specific conditions. If $conditions is NULL all rows from table will be deleted
    *
    * @access public
    * @param string $conditions Query conditions
    * @return boolean
    */
    static function delete($condition = null) {
      if (isset($this) && instance_of($this, 'Plugins')) {
        return parent::delete($condition);
      } else {
        return Plugins::instance()->delete($condition);
        //$instance =& Plugins::instance();
        //return $instance->delete($condition);
      } // if
    } // delete

    /**
    * This static function will return paginated result. Result is an array where first element is
    * array of returned object and second populated pagination object that can be used for
    * obtaining and rendering pagination data using various helpers.
    *
    * Items and pagination array vars are indexed with 0 for items and 1 for pagination
    * because you can't use associative indexing with list() construct
    *
    * @access public
    * @param array $arguments Query argumens (@see find()) Limit and offset are ignored!
    * @param integer $items_per_page Number of items per page
    * @param integer $current_page Current page number
    * @return array
    */
    static function paginate($arguments = null, $items_per_page = 10, $current_page = 1) {
      if (isset($this) && instance_of($this, 'Plugins')) {
        return parent::paginate($arguments, $items_per_page, $current_page);
      } else {
        return Plugins::instance()->paginate($arguments, $items_per_page, $current_page);
        //$instance =& Plugins::instance();
        //return $instance->paginate($arguments, $items_per_page, $current_page);
      } // if
    } // paginate

    /**
    * Return manager instance
    *
    * @return Plugins
    */
    static function instance() {
      static $instance;
      if (!instance_of($instance, 'Plugins')) {
        $instance = new Plugins();
      } // if
      return $instance;
    } // instance

  } // BasePlugins

?>
