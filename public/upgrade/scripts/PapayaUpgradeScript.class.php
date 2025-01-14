<?php

  /**
  * Upgrade from 0.7 to 0.7.1
  *
  * @package ScriptUpgrader.scripts
  * @http://www.projectpier.org/
  */
  class PapayaUpgradeScript extends ScriptUpgraderScript {

    /**
    * Database connection link
    *
    * @var resource
    */
    private $database_connection = null;

    /**
    * Construct the OnionUpgradeScript
    *
    * @param Output $output
    * @return OnionUpgradeScript
    */
    function __construct(Output $output) {
      parent::__construct($output);
      $this->setVersionFrom('0.7');
      $this->setVersionTo('0.7.1');
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

      if (PRODUCT_VERSION == '0.7.1') {
        $this->printMessage('You are already running activeCollab 0.7.1');
        return true;
      } // if

      if (substr(PRODUCT_VERSION, 0, 3) !== '0.7') {
        $this->printMessage('This upgrade script can be used only to upgrade 0.7 to 0.7.1', true);
        return false;
      } // if

      // ---------------------------------------------------
      //  Connect to database
      // ---------------------------------------------------

      if ($this->database_connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
          $this->printMessage('Upgrade script has connected to the database.');
          // getting rid of superfluous code when using mysqli_connect (gwyneth 20210411)
      } else {
        $this->printMessage('Failed to connect to database');
        return false;
      } // if

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
        $this->printMessage('Test query has been executed. Its safe to proceed with database migration.');
        mysqli_query($this->database_connection, "DROP TABLE `$test_table_name`");
      } else {
        $this->printMessage('Failed to executed test query. MySQL said: ' . mysqli_error($this->database_connection), true);
        return false;
      } // if

      //return ;

      // ---------------------------------------------------
      //  Execute migration
      // ---------------------------------------------------

      mysqli_query($this->database_connection, 'BEGIN WORK');

      if ($this->fixPrivateTasks() === false) {
        mysqli_query($this->database_connection, 'ROLLBACK');
        $this->printMessage('Upgrade process failed!', true);
        return false;
      } // if
      if ($this->fixConfigOptions() === false) {
        mysqli_query($this->database_connection, 'ROLLBACK');
        $this->printMessage('Upgrade process failed!', true);
        return false;
      } // if
      if ($this->fixConfigFile() === false) {
        mysqli_query($this->database_connection, 'ROLLBACK');
        $this->printMessage('Upgrade process failed!', true);
        return false;
      } // if

      if (mysqli_query($this->database_connection, 'COMMIT')) {
        $this->printMessage('activeCollab has been upgraded. You are now running activeCollab 0.7.1. Enjoy!');
        return true;
      } else {
        $this->printMessage('Failed to commit updates. Upgrade process failed!', true);
        return false;
      } // if
    } // execute

    /**
    * Fix private tasks
    *
    * @param void
    * @return null
    */
    function fixPrivateTasks() {
      $task_lists_table = TABLE_PREFIX . 'project_task_lists';
      $tasks_table = TABLE_PREFIX . 'project_tasks';
      $logs_table = TABLE_PREFIX . 'application_logs';

      if ($task_lists_result = mysqli_query($this->database_connection, "SELECT `id`, `is_private` FROM `$task_lists_table`")) {
        while ($task_list_row = mysqli_fetch_assoc($task_lists_result)) {
          if ($task_ids_result = mysqli_query($this->database_connection, "SELECT `id` FROM `$tasks_table` WHERE `task_list_id` = " . $task_list_row['id'])) {
            $is_private = "'" . $task_list_row['is_private'] . "'";
            $task_ids = array();
            while ($task_id_row = mysqli_fetch_assoc($task_ids_result)) {
              $task_ids[] = "'" . $task_id_row['id'] . "'";
            } // if

            if (count($task_ids)) {
              $task_ids = implode(', ', $task_ids);

              if (!mysqli_query($this->database_connection, "UPDATE `$logs_table` SET `is_private` = $is_private WHERE `rel_object_manager` = 'ProjectTasks' AND `rel_object_id` IN ($task_ids)")) {
                $this->printMessage('Failed to updated application log. MySQL said: ' . mysqli_error($this->database_connection), true);
                return false;
              } // if
            } // if
          } // if
        } // if
      } // if

      $this->printMessage('Application log has been updated');
    } // fixPrivateTasks

    /**
    * Show some hidden config options to the user
    *
    * @param void
    * @return null
    */
    function fixConfigOptions() {
      $config_options_table = TABLE_PREFIX . 'config_options';
      if (mysqli_query($this->database_connection, "UPDATE `$config_options_table` SET `is_system` = '0' WHERE `name` = 'file_storage_adapter'")) {
        $this->printMessage('Configuration options have been updated');
        return true;
      } else {
        $this->printMessage('Failed to update config options table. MySQL said: ' . mysqli_error($this->database_connection), true);
        return false;
      } // if
    } // fixConfig

    /**
    * This function will configuration file
    *
    * @param void
    * @return null
    */
    function fixConfigFile() {
      $this->printMessage('Updating configuration file');
      $constants = array(
        'DB_ADAPTER'           => DB_ADAPTER,
        'DB_HOST'              => DB_HOST,
        'DB_USER'              => DB_USER,
        'DB_PASS'              => DB_PASS,
        'DB_NAME'              => DB_NAME,
        'DB_PERSIST'           => true,
        'TABLE_PREFIX'         => TABLE_PREFIX,
        'ROOT_URL'             => ROOT_URL,
        'DEFAULT_LOCALIZATION' => DEFAULT_LOCALIZATION,
        'DEBUG'                => false,
        'PRODUCT_VERSION'      => $this->getVersionTo(),
      ); // array
      tpl_assign('config_file_constants', $constants);
      if (file_put_contents(INSTALLATION_PATH . '/config/config.php', tpl_fetch(get_template_path('config_file')))) {
        $this->printMessage('Configuration file updated');
        return true;
      } else {
        $this->printMessage('Failed to update configuration file', true);
        return false;
      } // if
    } // fixConfigFile

  } // PapayaUpgradeScript

?>
