<?php
error_reporting(E_ALL);
require('../../config/config.php');
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME) or die('Could not connect to database server ' . DB_HOST);

$id = $_GET['id'];

//$cs = 'character set '.config_option('character_set', 'utf8');
//$co = 'collate '.config_option('collation', 'utf8_unicode_ci');

$cs = 'character set utf8';
$co = 'collate utf8_unicode_ci';

$sql = file_get_contents("$id.sql");
$sql = str_replace('{$tp}', DB_PREFIX, $sql);
$sql = str_replace('<?php echo $table_prefix ?>', DB_PREFIX, $sql);
$sql = str_replace('<?php echo $default_collation ?>', $co, $sql);
$sql = str_replace('<?php echo $default_charset ?>', $cs, $sql);
//executeMultipleQueries($sql, &$total_queries, &$executed_queries, $link);
executeMultipleQueries($sql, $total_queries, $executed_queries, $link);
echo 'Summary' . "<br>\n";
echo 'Total queries in SQL: ' . $total_queries . " <br>\n";
echo 'Total executed queries: ' . $executed_queries  . " <br>\n";

mysqli_close($link);


    function executeMultipleQueries($sql, $total_queries, $executed_queries, $link) {
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
          echo $query;
          if (mysqli_query($link, trim($query), $link)) {
            $executed_queries++;
            echo " OK<br>\n";
          } else {
            echo " FAIL<br>\n";
            echo mysqli_error($link). "<br>\n";
           // return false;
          } // if
        } // if
      } // if

      return true;
    } // executeMultipleQueries
?>
