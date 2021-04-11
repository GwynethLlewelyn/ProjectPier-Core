<?php
  include_once("../functions.php");  // to get access to mb_encode() (gwyneth 20210411)

  /**
  * Email controller is used for handling incoming emails
  *
  * @version 1.0
  * @http://www.projectpier.org/
  */
  class EmailController extends Controller {

    /**
    * The message lines
    *
    * @var array of string
    */
    private $lines;

    /**
    * The current line into $message
    *
    * @var integer
    */
    private $current_line;

    /**
    * Construct the EmailController
    *
    * @access public
    * @param void
    * @return EmailController
    */
    function __construct() {
      parent::__construct();
      $this->setSystemControllerClass('Controller');;
    } // __construct

    function store() {
      ob_end_clean();
      if (isset($_POST['message'])) {
        DB::beginWork();
        $user = $_POST['from'];
        //$sql = "insert into " . DB_PREFIX . "`email_in` (created_by, raw) values (";
        $sql = "insert into `PP086_email_in` (created_by, raw) values (";
        $sql .= "'$user', '" . mb_escape($_POST['message']) . "')";  // what is the database link? No idea. So use mb_escape() instead (gwyneth 20210411)
        DB::execute($sql);
        //ApplicationLogs::createLog('new email received', null, ApplicationLogs::ACTION_ADD);
        DB::commit();
        //header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
        //header('Status: 200');
        echo 'ok';
        die();
      }
      header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
      header('Status: 404 Not Found');
      die();
    }

  } // EmailController

?>
