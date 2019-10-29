<?php
class KRErrorHandler {
  function __construct(){
    set_exception_handler("KRErrorHandler::exception");
    set_error_handler("KRErrorHandler::error");
  }
  private static function getCodePart($file, $line, $lines = 5){
    $file = file($file);
    $outLines = []; 
    $outLines[] = "... \r\n";
    $startLine = $line-$lines; 
    $startLine = $startLine < 0 ? 0 : $startLine; 
    for ($i = $startLine; $i < $startLine + $lines * 2; $i ++){
      if (isset($file[$i])) {
        $outLines[] = $file[$i];
      }
    }
    $outLines[] = "... \r\n";
    return implode("", $outLines);
  }
  public static function exception($e){
    $message = jTraceEx($e);
    $message = str_replace('/var/www/html/', '', $message);
    KRErrorHandler::notifySlack($message);
  }
  public static function error($errno, $errstr, $errfile, $errline){
    global $db; 
    $message = $errfile . ', Line: ' . $errline . '. Error: ' . $errstr . "\r\n" . KRErrorHandler::getCodePart($errfile, $errline) . "\r\n\r\n"; 
    $e = new Exception();
    $message .= jTraceEx($e);
    $message = str_replace('/var/www/html/', '', $message);
    KRErrorHandler::notifySlack($message);
    return true; 
  }
  public static function init(){
    set_exception_handler("KRErrorHandler::exception");
    set_error_handler("KRErrorHandler::error");
  }
  public static function var_dump($data){
    //var_dump(print_r($data, true));
    KRErrorHandler::notifySlack(print_r($data, true));//
  }
  public static function notifySlack($message){
    
    $c = curl_init("https://hooks.slack.com/services/XXXXX/YYYYYY/ZZZZZZZZZZZZZZ");
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, ['payload'=>json_encode(array('text' => $message))]);
    curl_setopt($c, CURLOPT_VERBOSE, 0);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_exec($c);
    curl_close($c);
  }
}
/**
* jTraceEx() - provide a Java style exception trace
* @param $exception
* @param $seen      - array passed to recursive calls to accumulate trace lines already seen
*                     leave as NULL when calling this function
* @return array of strings, one entry per trace line
*/
function jTraceEx($e, $seen=null) {
  $starter = $seen ? 'Caused by: ' : '';
  $result = array();
  if (!$seen) $seen = array();
  $trace  = $e->getTrace();
  $prev   = $e->getPrevious();
  $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
  $file = $e->getFile();
  $line = $e->getLine();
  // array_shift($trace);
  // array_shift($trace);
  // array_shift($trace);
  //$args = json_encode($e->getTrace());
  while (true) {
      $current = "$file:$line";
      // if (is_array($seen) && in_array($current, $seen)) {
      //     $result[] = sprintf(' ... %d more', count($trace)+1);
      //     //break;
      // }
      $result[] = sprintf(' at %s%s%s (%s%s%s)',
                                  count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                                  count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                                  count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                                  $line === null ? $file : basename($file),
                                  $line === null ? '' : ':',
                                  $line === null ? '' : $line) . "| Args: " . json_encode($trace[0]['args']);
      if (is_array($seen))
          $seen[] = "$file:$line";
      if (!count($trace))
          break;
      $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
      $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
      array_shift($trace);
  }
  $result = join("\n", $result);
  if ($prev)
      $result  .= "\n" . jTraceEx($prev, $seen);
  
  return $result;
}
