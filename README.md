# KRErrorHandler
PHP Custom error/exception handler with notifications to Slack


How to: 

include("KRErrorHandler.php");

KRErrorHandler::init();

// for test run

throw new Exception('your error');


Also you can use a function somewhere in your code to send var_dumps to your slack: 

KRErrorHandler::var_dump($array);
