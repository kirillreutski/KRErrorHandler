# KRErrorHandler
PHP Custom error/exception handler with notifications to Slack


How to: 

include("KRErrorHandler.php");
KRErrorHandler::init();

// for test run
throw new Exception('your error');
