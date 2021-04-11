<?php

  /**
  * Single log entry
  *
  * @package Logger
  * @http://www.projectpier.org/
  */
  class Logger_Entry {

    /**
    * Entry message
    *
    * @var string
    */
    private $message;

    /**
    * Severity
    *
    * @var integer
    */
    private $severity;

    /**
    * Time when this entry is created. It is microtime, not a DateTime
    *
    * @var integer
    */
    private $created_on;

    /**
    * Counter to give an idea on how many times this is being called and _not_ destoyed
    *
    * @var integer
    * @author Gwyneth Llewelyn
    */
    public static $count=0;

    const LOGGER_ENTRY_CONSTRUCT_LOG = INSTALLATION_PATH . "/cache/logger-entry.log";

    /**
    * Constructor
    *
    * @param string $message to log
    * @param integer $severity for this message
    * @return Logger_Entry
    */
    public function __construct($message, $severity = Logger::DEBUG) {
      Logger_Entry::$count++;  // just to see how often this is called (gwyneth 20210411)
      // init special logging (gwyneth 20210411)
      if (Logger_Entry::$count == 1) {
        if (file_put_contents(LOGGER_ENTRY_CONSTRUCT_LOG, date("c") . ": Logging started for Logger_Entry::_construct()" === false)) {
          error_log("Could not initialise special log for Logger_Entry!");
        }
      }
      error_log(date("c") . ": '" . $message . "' (count: " . $count . ")", 3, LOGGER_ENTRY_CONSTRUCT_LOG);
      if (Logger_Entry::$count % 10000 == 0) {
        error_log("Logger_Entry instanciated " . Logger_Entry::$count . " times so far.");
      }

      try {
        $this->setMessage($message);
        $this->setSeverity($severity);
        $this->setCreatedOn(microtime(true));
      } catch(exception $e) {
        error_log("Logger_Entry::__construct() threw an error after " . Logger_Entry::$count . " run(s): " . $e->getMessage());
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
      Logger_Entry::$count--;
      error_log(date("c") . ": Removing one Logger_Entry: " . $count . " left.", 3, LOGGER_ENTRY_CONSTRUCT_LOG);
      // TODO(gwyneth): probably we need to remove/rotate the file at some point (gwyneth 20210411)
      if ((Logger_Entry::$count % 10000 == 0)) {
        error_log("Logger_Entry::__destruct called; # of instances is now " . Logger_Entry::$count);
      }
    }

    /**
    * Return formated message
    *
    * @param string $new_line_prefix Prefix that is put in front of every new line (so multiline
    * messages are indented and separated from the rest of the messages)
    * @return string
    */
    function getformattedMessage($new_line_prefix = '') {
      $message = $this->getMessage();
      $message = str_replace(array("\r\n", "\r"), array("\n", "\n"), $message);
      $message = str_replace("\n", "\n" . $new_line_prefix, $message);
      return $message;
    } // getformattedMessage

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
    * Get message
    *
    * @param null
    * @return string
    */
    function getMessage() {
      return $this->message;
    } // getMessage

    /**
    * Set message value
    *
    * @param string $value
    * @return null
    */
    function setMessage($value) {
      $this->message = $value;
    } // setMessage

    /**
    * Get severity
    *
    * @param null
    * @return integer
    */
    function getSeverity() {
      return $this->severity;
    } // getSeverity

    /**
    * Set severity value
    *
    * @param integer $value
    * @return null
    */
    function setSeverity($value) {
      $this->severity = $value;
    } // setSeverity

    /**
    * Get created_on
    *
    * @param null
    * @return float
    */
    function getCreatedOn() {
      return $this->created_on;
    } // getCreatedOn

    /**
    * Set created_on value
    *
    * @param float $value
    * @return null
    */
    protected function setCreatedOn($value) {
      $this->created_on = $value;
    } // setCreatedOn

  } // Logger_Entry

?>
