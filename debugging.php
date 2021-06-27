<?php
/**
 * This file is just for testing various things in order to debug and check functionality
 */

require 'vendor/autoload.php';

function debug($callable) {
  ob_start();
  $callable();
  $result = ob_get_clean();
  write_data($result);
}

function write_data($content) {
  $f = fopen('debugoutput.txt', 'w');
  fwrite($f, $content);
  fclose($f);
}

debug(function() {
  // Whatever to run here
});