<?php


  /**
  * ConfigOptions class
  *
  * @http://www.projectpier.org/
  *
  * Note: Moved a lot of functions to 'static' (gwyneth 20210411)
  */
  abstract class BaseConfigOptions extends DataManager {

    /**
    * Column name => Column type map
    *
    * @var array
    * @static
    */
    static private $columns = array(
      'id'                    => DATA_TYPE_INTEGER,
      'category_name'         => DATA_TYPE_STRING,
      'name'                  => DATA_TYPE_STRING,
      'value'                 => DATA_TYPE_STRING,
      'config_handler_class'  => DATA_TYPE_STRING,
      'is_system'             => DATA_TYPE_BOOLEAN,
      'option_order'          => DATA_TYPE_INTEGER,
      'dev_comment'           => DATA_TYPE_STRING
    );

    /**
    * Statically defined instance of a ConfigOption
    * This is probably needed because we're running out of memory!
    * To be reverted if we figure out where the problem is (gwyneth 20210411)
    *
    * @var object
    * @static
    *
    * @author Gwyneth Llewelyn
    */
    // static $coInstance; // we'll try to have only _one_ instance , to save memory!

    public static $count = 0;
    public const BASECONFIGOPTIONS_CONSTRUCT_LOG = ROOT . "/cache/BaseConfigOptions.log";

    /**
    * Construct
    *
    * @return BaseConfigOptions
    */
    function __construct() {
      BaseConfigOptions::$count++;  // just to see how often this is called (gwyneth 20210411)
      // init special logging (gwyneth 20210411)
      if (BaseConfigOptions::$count == 1) {
        if (file_put_contents(BaseConfigOptions::LOGGER_ENTRY_CONSTRUCT_LOG, date("c") . "\tLogging started for Logger_Entry::_construct()" . PHP_EOL . PHP_EOL, LOCK_EX) === false) {
          error_log("Could not initialise special log for BaseConfigOptions!");
        }
      }
      file_put_contents(BaseConfigOptions::LOGGER_ENTRY_CONSTRUCT_LOG, date("c") . "\t'" . $message . "' (count: " . BaseConfigOptions::$count . ")" . PHP_EOL, FILE_APPEND | LOCK_EX);
      if (BaseConfigOptions::$count % 100000 == 0) {
        error_log("BaseConfigOptions instanciated " . BaseConfigOptions::$count . " times so far.");
      }
      try {
        parent::__construct('ConfigOption', 'config_options', true);
      } catch(exception $e) {
        error_log("BaseConfigOptions::__construct() threw an error after " . BaseConfigOptions::$count . " run(s): " . $e->getMessage());
      }
    } // __construct

    /**
    * Destructor
    * Used only for debugging purposes; diminishes the counters
    *
    * @param void
    * @return void
    *
    * @author Gwyneth Llewelyn
    */
    public function __destruct() {
      BaseConfigOptions::$count--;
      file_put_contents(BaseConfigOptions::BASECONFIGOPTIONS_CONSTRUCT_LOG, date("c") . "\tRemoving one BaseConfigOptions: " . BaseConfigOptions::$count . " left.", FILE_APPEND | LOCK_EX);
      // TODO(gwyneth): probably we need to remove/rotate the file at some point (gwyneth 20210411)
      if ((BaseConfigOptions::$count % 100000 == 0)) {
        error_log("BaseConfigOptions::__destruct called; # of instances is now " . BaseConfigOptions::$count);
      }
    }

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
    static function getColumns() {
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
      return 'id';
    } // getPkColumns

    /**
    * Return name of first auto_incremenent column if it exists
    *
    * @access public
    * @param void
    * @return string
    */
    static function getAutoIncrementColumn() {
      return 'id';
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
    * @return one or ConfigOptions objects
    * @throws DBQueryError
    */
    static function find($arguments = null) {
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::find($arguments);
      } else {
        return ConfigOptions::instance()->find($arguments);
        //$instance =& ConfigOptions::instance();
        //return $instance->find($arguments);
      } // if
    } // find

    /**
    * Find all records
    *
    * @access public
    * @param array $arguments
    * @return one or ConfigOptions objects
    */
    static function findAll($arguments = null) {
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::findAll($arguments);
      } else {
        return ConfigOptions::instance()->findAll($arguments);
        //$instance =& ConfigOptions::instance();
        //return $instance->findAll($arguments);
      } // if
    } // findAll

    /**
    * Find one specific record
    *
    * @access public
    * @param array $arguments
    * @return ConfigOption
    */
    static function findOne($arguments = null) {
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::findOne($arguments);
      } else {
        return ConfigOptions::instance()->findOne($arguments);
        //$instance =& ConfigOptions::instance();
        //return $instance->findOne($arguments);
      } // if
    } // findOne

    /**
    * Return object by its PK value
    *
    * @access public
    * @param mixed $id
    * @param boolean $force_reload If true cache will be skipped and data will be loaded from database
    * @return ConfigOption
    */
    static function findById($id, $force_reload = false) {
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::findById($id, $force_reload);
      } else {
        return ConfigOptions::instance()->findById($id, $force_reload);
        //$instance =& ConfigOptions::instance();
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
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::count($condition);
      } else {
        return ConfigOptions::instance()->count($condition);
        //$instance =& ConfigOptions::instance();
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
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::delete($condition);
      } else {
        return ConfigOptions::instance()->delete($condition);
        //$instance =& ConfigOptions::instance();
        //return $instance->delete($condition);
      } // if
    } // delete

    /**
    * This function will return paginated result. Result is an array where first element is
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
      if (isset($this) && instance_of($this, 'ConfigOptions')) {
        return parent::paginate($arguments, $items_per_page, $current_page);
      } else {
        return ConfigOptions::instance()->paginate($arguments, $items_per_page, $current_page);
        //$instance =& ConfigOptions::instance();
        //return $instance->paginate($arguments, $items_per_page, $current_page);
      } // if
    } // paginate

    /**
    * Return manager instance
    *
    * @return ConfigOptions
    */
    static function instance() {
      static $instance;
      if (!instance_of($instance, 'ConfigOptions')) {
        $instance = new ConfigOptions();
      } // if
      return $instance;

      // if (empty(self::$coInstance) || !is_object(self::$coInstance) || !instance_of(self::$coInstance, 'ConfigOptions')) {
      //   try {
      //     self::$coInstance = new ConfigOptions();
      //   } catch (exception $e) {
      //     error_log("BaseConfigOptions::instance() threw an error when creating a new ConfigOptions object: " . $e->getMessage());
      //   }
      // } // if
      // return self::$coInstance;
    } // instance

  } // ConfigOptions

?>
