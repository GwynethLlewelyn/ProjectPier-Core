<?php

  /**
  * Checks step - check environment - PHP version, are folder writable etc
  *
  * @package ScriptInstaller
  * @subpackage installation
  * @version 1.0
  * @http://www.projectpier.org/
  */
  class ChecksStep extends ScriptInstallerStep {

    /**
    * Array of files and folders that need to writable
    *
    * @var array
    */
    private $check_is_writable = null;

    /**
    * Array of extensions that need to be present for ProjectPier to be installed
    *
    * @var array
    */
    private $check_extensions = null;

    /**
    * Construct the ChecksStep
    *
    * @access public
    * @param void
    * @return ChecksStep
    */
    function __construct() {
      $this->setName('Server check');

      $this->check_is_writable = array(
        '/config',
        '/public/files',
        '/tmp',
        '/cache',
        '/upload'
      ); // array
      // We ought to use the system's defined path for saving sessions, because it might be in a place
      //  where it's not normally accesible by the web server (good practice!)
      //  In this case, we ought to check that we can write to it (gwyneth 20210411)
      //if (!empty(ini_get('session.save_path'))) {
      //  array_push($this->check_is_writable, ini_get('session.save_path'));
      //} // if

      $this->check_extensions = array(
        'session' => true,
        'mysqli' => true,      // now we need to check for mysqli, not mysql which is deprecated (gwyneth 20210410)
        'calendar' => false,
        'gd' => false,
        'simplexml' => false,
        'ldap' => false,
        'sockets' => false,
        'curl' => false        // not strictly necessary, but useful to have (gwyneth 20210411)
      ); // array

    } // __construct

    /**
    * Execute environment checks
    *
    * @access public
    * @param void
    * @return boolean
    */
    function execute() {
      $all_ok = true;

      // Check PHP version
      if (version_compare(PHP_VERSION, '5.6', 'ge')) {  // bumping version
        $this->addToChecklist('PHP version is ' . PHP_VERSION, true);
      } else {
        $this->addToChecklist('Error: PHP version on this system is ' . PHP_VERSION . '. PHP 5.6 or newer is required', false);
        $all_ok = false;
      } // if

      foreach ($this->check_extensions as $extension_name => $required) {
        if (extension_loaded($extension_name)) {
          $this->addToChecklist("'$extension_name' extension is available", true);
        } else {
          if ($required) {
            $this->addToChecklist("Error: '$extension_name' extension is not available but is required", false);
            $all_ok = false;
          } else {
            $this->addToChecklist("Warning: '$extension_name' extension is not available (check documentation)", false);
          }
        } // if
      } // if

      if (is_array($this->check_is_writable)) {
        foreach ($this->check_is_writable as $relative_folder_path) {
          $check_this = INSTALLATION_PATH . $relative_folder_path;

          if (!file_exists($check_this)) {
            $this->addToChecklist("$relative_folder_path does not exist", false);
            $all_ok = false;
            continue;
          }

          $is_writable = false;
          if (is_file($check_this)) {
            $is_writable = file_is_writable($check_this);
          } elseif (is_dir($check_this)) {
            $is_writable = folder_is_writable($check_this);
          } // if

          if ($is_writable) {
            $this->addToChecklist("$relative_folder_path is writable", true);
          } else {
            $this->addToChecklist("$relative_folder_path is not writable", false);
            $all_ok = false;
          } // if
        } // foreach
      } // if

      $this->setContentFromTemplate('checks.php');

      if (ini_get('zend.ze1_compatibility_mode')) {
        $this->addToChecklist('zend.ze1_compatibility_mode is set to On. This can cause some strange problems. It is strongly suggested to turn this value to Off (in your php.ini file)', false);
      } // if

      if (empty(ini_get('session.auto_start')) || ini_get('session.auto_start') == 0 || (strtolower(ini_get('session.auto_start')) == 'off')) {
        $this->addToChecklist('session.auto_start seems to be unset or set to Off. In some systems this might get the session data a bit confused, so you may try to turn it On (in your php.ini file) and see if it works better that way', true);
      } // if

      if ($all_ok) {
        return $this->isSubmitted();
      } // if

      $this->setNextDisabled(true);
      return false;
    } // execute

  } // ChecksStep

?>
