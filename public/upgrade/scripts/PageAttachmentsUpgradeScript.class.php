<?php

  /**
  * Upgrade ProjectPier to support PageAttachments
  *
  * @package ScriptUpgrader.scripts
  * @http://www.projectpier.org/
  */
  class PageAttachmentsUpgradeScript extends ScriptUpgraderScript {

    /**
    * Database connection link
    *
    * @var resource
    */
    private $database_connection = null;

    /**
    * Construct the PageAttachmentsUpgradeScript
    *
    * @param Output $output
    * @return PageAttachmentsUpgradeScript
    */
    function __construct(Output $output) {
      parent::__construct($output);
      $this->setVersionFrom('0.8.0');
      $this->setVersionTo('0.8.0');
    } // __construct

    /**
    * Execute the script
    *
    * @param void
    * @return boolean
    */
    function execute() {
      define('ROOT', realpath(dirname(__FILE__) . '/../../../'));

      // ---------------------------------------------------
      //  Load config
      // ---------------------------------------------------

      $config_is_set = require_once INSTALLATION_PATH . '/config/config.php';
      if (!$config_is_set) {
        $this->printMessage('Valid config files was not found!', true);
        return false;
      } else {
        $this->printMessage('Config file found and loaded.');
      } // if

      if (substr(PRODUCT_VERSION, 0, 3) !== '0.8') {
        $this->printMessage('This upgrade script is intended for version 0.8.x. You\'re running ProjectPier v.'.PRODUCT_VERSION.'.', true);
        return false;
      } // if

      // ---------------------------------------------------
      //  Connect to database
      // ---------------------------------------------------

      if ($this->database_connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
          $this->printMessage('Upgrade script has connected to the database.');
      } else {
        $this->printMessage('Failed to connect to database', true);
        return false;
      } // if


      // ---------------------------------------------------
      //  Check existence of tables for Tickets
      // ---------------------------------------------------

      $tables_to_check = array('page_attachments');

      foreach ($tables_to_check as $table) {
        $test_table_exists_sql = "SHOW TABLES LIKE '".TABLE_PREFIX."$table';";
        if (mysqli_num_rows(mysqli_query($this->database_connection, $test_table_exists_sql))) {
          $this->printMessage("Table ".TABLE_PREFIX."$table already exists. It is recommended to proceed with the upgrade manually.", true);
          return false;
        }
      } // foreach
      $this->printMessage('The tables that need to be created do not exist already. It is safe to proceed with the database migration.');

      // ---------------------------------------------------
      //  Check MySQL version
      // ---------------------------------------------------

      $mysql_version = mysqli_get_server_info($this->database_connection);
      if ($mysql_version && version_compare($mysql_version, '4.1', '>=')) {
        $constants['DB_CHARSET'] = 'utf8mb4';
        mysqli_query($this->database_connection, "SET NAMES 'utf8mb4'");
        tpl_assign('default_collation', $default_collation = 'collate utf8mb4_unicode_ci');
        tpl_assign('default_charset', $default_charset = 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
      } else {
        tpl_assign('default_collation', $default_collation = '');
        tpl_assign('default_charset', $default_charset = '');
      } // if

      tpl_assign('table_prefix', TABLE_PREFIX);

      // ---------------------------------------------------
      //  Check test query
      // ---------------------------------------------------

      $test_table_name = TABLE_PREFIX . 'test_table';
      $test_table_sql = "CREATE TABLE `$test_table_name` (
        `id` int(10) unsigned NOT NULL auto_increment,
        `name` varchar(50) $default_collation NOT NULL default '',
        PRIMARY KEY  (`id`)
      ) ENGINE=InnoDB $default_charset;";

      if (mysqli_query($this->database_connection, $test_table_sql)) {
        $this->printMessage('Test query has been executed. It\'s safe to proceed with database migration.');
        mysqli_query($this->database_connection, "DROP TABLE `$test_table_name`");
      } else {
        $this->printMessage('Failed to executed test query. MySQL said: ' . mysqli_error($this->database_connection), true);
        return false;
      } // if

      // ---------------------------------------------------
      //  Execute migration
      // ---------------------------------------------------

      $total_queries = 0;
      $executed_queries = 0;
      $upgrade_script = tpl_fetch(get_template_path('db_migration/page_attachments'));

      mysqli_query($this->database_connection, 'BEGIN WORK');
      if ($this->executeMultipleQueries($upgrade_script, $total_queries, $executed_queries, $this->database_connection)) {
        $this->printMessage("Database schema transformations executed (total queries: $total_queries)");
        mysqli_query($this->database_connection, 'COMMIT');
      } else {
        $this->printMessage('Failed to execute DB schema transformations. MySQL said: ' . mysqli_error($this->database_connection), true);
        mysqli_query($this->database_connection, 'ROLLBACK');
        return false;
      } // if

      $this->printMessage('ProjectPier has been patched to use page attachments. Enjoy!');
    } // execute


    /**
    * Return script name.
    *
    * @param void
    * @return string
    */
    function getScriptName() {
      return 'Upgrade of DB for Page Attachment patch';
    } // getName


  } // PageAttachmentsUpgradeScript

?>
