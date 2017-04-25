<?php
header('content-type:text/html;charset=utf-8');
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define('APP_DEBUG' , true);
define('SITE_PATH' , '/wasuCMS/');
define('WEB_PATH' , '/webRoot/');
define('CSS_PATH' , '/wasuCMS/public/css/');
define('JS_PATH' , '/wasuCMS/public/javascript/');
define('IMAGES_PATH' , '/wasuCMS/public/images/');
define('CF_PATH' , '/wasuCMS/public/clientFramework/');
define('HIGHCHARTS_PATH' , '/wasuCMS/public/highcharts/');
define('EDITOR_URL' , '/wasuCMS/public/editor/');
define('JSON_CACHE_PATH' , '/wasuCMS/public/json/');
define('VIDEO_PATH' , '/video/');
define('UPLOAD_PATH' , WEB_PATH . 'upload/');
define('TEMPLATE_PATH' , WEB_PATH . 'template/');
require '../ThinkPHP/ThinkPHP.php';