<?php

  final class installation {

    /**
    * Output object
    *
    * @var Output
    */
    private $output;

    /**
    * Database connection resource
    *
    * @var resource
    */
    private $database_connection;

    /**
    * Type of the database
    *
    * @var string
    */
    private $database_type = 'mysqli'; // this is the new database adapter! (gwyneth 20210411)

    /**
    * Database host
    *
    * @var string
    */
    private $database_host;

    /**
    * Database username
    *
    * @var string
    */
    private $database_username;

    /**
    * Database password
    *
    * @var string
    */
    private $database_password;

    /**
    * Database name
    *
    * @var string
    */
    private $database_name;

    /**
    * Table prefix
    *
    * @var string
    */
    private $table_prefix;

    /**
    * Absolute URL
    *
    * @var string
    */
    private $absolute_url;

    /**
    * Constructor
    *
    * @param Output $output
    * @return installation
    */
    function __construct(Output $output) {
      $this->setOutput($output);
    } // __construct

    /**
    * Prepare and process config form
    *
    * @access public
    * @param void
    * @return boolean
    */
    function execute() {
      $database_type    = $this->getDatabaseType();
      $database_host    = $this->getDatabaseHost();
      $database_user    = $this->getDatabaseUsername();
      $database_pass    = $this->getDatabasePassword();
      $database_name    = $this->getDatabaseName();
      $database_charset = 'utf8mb4';
      $database_prefix  = $this->getTablePrefix();
      $installkey       = sha1(date('l dS \of F Y h:i:s A').$_SERVER['REMOTE_ADDR'].rand(10000,99999));

      $connected = false;
      if ($this->database_connection = mysqli_connect($database_host, $database_user, $database_pass, $database_name)) {
        $connected = true; // simpler on mysqli (gwyneth 20210410)
      } // if

      if ($connected) {
        $this->printMessage('Database connection has been established successfully');
      } else {
        return $this->breakExecution('Failed to connect to database with data you provided');
      } // if

      // ---------------------------------------------------
      //  Check if we have at least 4.1
      // ---------------------------------------------------
      $mysql_version = mysqli_get_server_info($this->database_connection);
      if ($mysql_version && version_compare($mysql_version, '4.1', '<')) {
        $this->breakExecution('MySQL version is '.$mysql_version.'. PP needs 4.1 or higher. Choose another MySQL server or upgrade.');
      }

      // ---------------------------------------------------
      //  Check if we have InnoDB support (transactions)
      // ---------------------------------------------------
      if ($this->haveInnoDbSupport()) {
        $this->printMessage('InnoDB storage engine is supported');
        mysqli_query($this->database_connection, "SET STORAGE_ENGINE='INNODB'");
      } else {
        $this->printMessage('InnoDB storage engine is not supported, this is okay for low volume installations');
      } // if

      $constants = array(
        'DB_ADAPTER'           => $database_type,
        'DB_HOST'              => $database_host,
        'DB_USER'              => $database_user,
        'DB_PASS'              => $database_pass,
        'DB_NAME'              => $database_name,
        'DB_PREFIX'            => $database_prefix,
        'DB_CHARSET'           => $database_charset,
        'DB_PERSIST'           => false
      ); // array

      tpl_assign('table_prefix', $database_prefix);

      mysqli_query($this->database_connection, "rollback");
      mysqli_query($this->database_connection, "unlock tables");
      mysqli_query($this->database_connection, mysqli_real_escape_string($this->database_connection, sprintf("SET NAMES '%s' COLLATE '%s'", $database_charset, $database_charset . '_unicode_ci')));
      mysqli_query($this->database_connection, "SET SQL_MODE=''");
      mysqli_query($this->database_connection, "SET STORAGE_ENGINE=INNODB");
      tpl_assign('default_collation', 'COLLATE ' . $database_charset . '_unicode_ci');
      tpl_assign('default_charset', 'CHARACTER SET ' . $database_charset);

      mysqli_query($this->database_connection, 'BEGIN WORK');

      // Database construction
      $total_queries = 0;
      $executed_queries = 0;
      if ($this->executeMultipleQueries(tpl_fetch(get_template_path('db/mysql/schema.php')), $total_queries, $executed_queries)) {
        $this->printMessage("Database '$database_name' setup. (Queries executed: $executed_queries)");
      } else {
        return $this->breakExecution('Failed to setup database. Reason: ' . mysqli_error($this->database_connection));
      } // if

      // Initial data
      $total_queries = 0;
      $executed_queries = 0;
      if ($this->executeMultipleQueries(tpl_fetch(get_template_path('db/mysql/initial_data.php')), $total_queries, $executed_queries)) {
        $this->printMessage("Database '$database_name' initialized. (Queries executed: $executed_queries)");
      } else {
        return $this->breakExecution('Failed to initialize database. Reason: ' . mysqli_error($this->database_connection));
      } // if

      mysqli_query($this->database_connection, 'COMMIT');

      if ($this->writeConfigFile($constants)!==false) {
        $this->printMessage('Configuration saved');
      } else {
        return $this->breakExecution('Failed to save configuration data. Is config/config.php writable?');
      } // if

      if ($this->clearAutoLoaderFile()!==false) {
        $this->printMessage('Autoloader cleared');
      } else {
        $this->printMessage('Autoloader NOT cleared. Is cache/autoloader.php writable?');
      } // if

      if ($this->clearLogFile()!==false) {
        $this->printMessage('Log cleared');
      } else {
        $this->printMessage('Log NOT cleared. Is cache/log.php writable?');
      } // if

      return true;
    } // excute

    // ---------------------------------------------------
    //  Util methods
    // ---------------------------------------------------

    /**
    * Add error message to all messages and break the execution
    *
    * @access public
    * @param string $error_message Reason why we are breaking execution
    * @return boolean
    */
    function breakExecution($error_message) {
      $this->printMessage($error_message, true);
      if (is_resource($this->database_connection) || is_object($this->database_connection)) {
        mysqli_query($this->database_connection, 'ROLLBACK');
      } // if
      return false;
    } // breakExecution

    /**
    * Write $constants in config file
    *
    * @access public
    * @param array $constants
    * @return boolean
    */
    function writeConfigFile($constants) {
      tpl_assign('config_file_constants', $constants);
      return file_put_contents(INSTALLATION_PATH . '/config/config.php', tpl_fetch(get_template_path('config_file.php')));
    } // writeConfigFile

    /**
    * Clear autoloader file
    *
    * @access public
    * @param array $constants
    * @return boolean
    */
    function clearAutoLoaderFile() {
      return file_put_contents(INSTALLATION_PATH . '/cache/autoloader.php', '');
    } // clearAutoLoaderFile

    /**
    * Clear log file
    *
    * @access public
    * @param array $constants
    * @return boolean
    */
    function clearLogFile() {
      return file_put_contents(INSTALLATION_PATH . '/cache/log.php', '');
    } // clearLogFile

    /**
    * This function will return true if server we are connected on has InnoDB support
    *
    * @param void
    * @return boolean
    */
    function haveInnoDbSupport() {
      // The old way of checking 'have_innodb' is deprecated since MySQL now has InnoDB as default (gwyneth 20210411)
//    if ($result = mysqli_query($this->database_connection, "SHOW VARIABLES LIKE 'have_innodb'")) {
      if ($result = mysqli_query($this->database_connection, "SELECT SUPPORT FROM INFORMATION_SCHEMA.ENGINES WHERE ENGINE = 'InnoDB'")) {
        if ($row = mysqli_fetch_assoc($result)) {
          $innoDBSupported = strtolower($row['SUPPORT']);
          // error_log("DEBUG: InnoDB support is '" . $innoDBSupported . "'");
          return ($innoDBSupported == 'yes' || $innoDBSupported == 'default');
        } // if
      } // if
      error_log("Installation error: Checking if InnoDB is present failed! Error was:" . mysqli_error($this->database_connection));
      return false;
    } // haveInnoDBSupport

    /**
    * Execute multiple queries
    *
    * This one is really quick and dirty because I want to finish this and catch
    * the bus. Need to be redone ASAP
    *
    * This function returns true if all queries are executed successfully
    *
    * @access public
    * @todo Make a better implementation
    * @param string $sql
    * @param integer $total_queries Total number of queries in SQL
    * @param integer $executed_queries Total number of successfully executed queries
    * @return boolean
    */
    function executeMultipleQueries($sql, &$total_queries, &$executed_queries) {
      if (!trim($sql)) {
        $total_queries = 0;
        $executed_queries = 0;
        return true;
      } // if

      // Make it work on PHP 5.0.4
      $sql = str_replace(array("\r\n", "\r"), array("\n", "\n"), $sql);

      $queries = explode(";\n", $sql);
      if (!is_array($queries) || !count($queries)) {
        $total_queries = 0;
        $executed_queries = 0;
        return true;
      } // if

      $total_queries = count($queries);
      foreach ($queries as $query) {
        if (trim($query)) {
          if (mysqli_query($this->database_connection, trim($query))) {
            $executed_queries++;
          } else {
            return false;
          } // if
        } // if
      } // if

      return true;
    } // executeMultipleQueries

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
    * Get output
    *
    * @param null
    * @return Output
    */
    function getOutput() {
      return $this->output;
    } // getOutput

    /**
    * Set output value
    *
    * @param Output $value
    * @return null
    */
    function setOutput(Output $value) {
      $this->output = $value;
      return $value;
    } // setOutput

    /**
    * Print message through output object
    *
    * @param string $message
    * @param boolean $is_error
    * @return null
    */
    function printMessage($message, $is_error = false) {
      if ($this->output instanceof Output) {
        $this->output->printMessage($message, $is_error);
      } // if
    } // printMessage

    /**
    * Get database_type
    *
    * @param null
    * @return string
    */
    function getDatabaseType() {
      return $this->database_type;
    } // getDatabaseType

    /**
    * Set database_type value
    *
    * @param string $value
    * @return null
    */
    function setDatabaseType($value) {
      $this->database_type = $value;
    } // setDatabaseType

    /**
    * Get database_host
    *
    * @param null
    * @return string
    */
    function getDatabaseHost() {
      return $this->database_host;
    } // getDatabaseHost

    /**
    * Set database_host value
    *
    * @param string $value
    * @return null
    */
    function setDatabaseHost($value) {
      $this->database_host = $value;
    } // setDatabaseHost

    /**
    * Get database_username
    *
    * @param null
    * @return string
    */
    function getDatabaseUsername() {
      return $this->database_username;
    } // getDatabaseUsername

    /**
    * Set database_username value
    *
    * @param string $value
    * @return null
    */
    function setDatabaseUsername($value) {
      $this->database_username = $value;
    } // setDatabaseUsername

    /**
    * Get database_password
    *
    * @param null
    * @return string
    */
    function getDatabasePassword() {
      return $this->database_password;
    } // getDatabasePassword

    /**
    * Set database_password value
    *
    * @param string $value
    * @return null
    */
    function setDatabasePassword($value) {
      $this->database_password = $value;
    } // setDatabasePassword

    /**
    * Get database_name
    *
    * @param null
    * @return string
    */
    function getDatabaseName() {
      return $this->database_name;
    } // getDatabaseName

    /**
    * Set database_name value
    *
    * @param string $value
    * @return null
    */
    function setDatabaseName($value) {
      $this->database_name = $value;
    } // setDatabaseName

    /**
    * Get table_prefix
    *
    * @param null
    * @return string
    */
    function getTablePrefix() {
      return $this->table_prefix;
    } // getTablePrefix

    /**
    * Set table_prefix value
    *
    * @param string $value
    * @return null
    */
    function setTablePrefix($value) {
      $this->table_prefix = $value;
    } // setTablePrefix

  } // installation

?>
