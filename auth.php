<?php
if(isset($_GET['out'])){
 require __DIR__.'/app.php';
 $_SESSION=[];session_destroy();header('Location: auth.php');exit;
}
$requestPath=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'';
$staffPath=trim($_SERVER['PATH_INFO']??'','/')==='admin'||str_ends_with(rtrim($requestPath,'/'),'/auth.php/admin');
require __DIR__.($staffPath?'/staff-auth.php':'/voter-auth.php');