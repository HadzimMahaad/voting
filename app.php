<?php
session_start();
$pdo=new PDO('mysql:host=localhost;dbname=voting;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function q($s,$a=[]){global $pdo;$r=$pdo->prepare($s);$r->execute($a);return $r;}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function me(){return $_SESSION['uid']??null;}
function admin(){return !empty($_SESSION['admin']);}
function superadmin(){return !empty($_SESSION['superadmin'])||(!empty($_SESSION['admin'])&&empty($_SESSION['staff']));}
function voter(){return !empty($_SESSION['voter']);}
function appBasePath(){
 if(staffPathRequest()){
  $path=rtrim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'','/');
  return rtrim(substr($path,0,-strlen('/auth.php/admin')),'/').'/';
 }
 return rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/').'/';
}
function staffPathRequest(){
 $path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'';
 return trim($_SERVER['PATH_INFO']??'','/')==='admin'||str_ends_with(rtrim($path,'/'),'/auth.php/admin');
}
function need(){if(!me()){header('Location: auth.php');exit;}}
function csrf(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function check(){if(!hash_equals(csrf(),$_POST['t']??''))die('Bad token');}
function voterToken(){
 $cookie='evoting_voter';$token=$_COOKIE[$cookie]??'';
 if(!preg_match('/\A[a-f0-9]{64}\z/',$token)){
  $token=bin2hex(random_bytes(32));
  setcookie($cookie,$token,['expires'=>time()+31536000,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
  $_COOKIE[$cookie]=$token;
 }
 return hash('sha256',$token);
}
function T(){return "<input type=hidden name=t value='".csrf()."'>";}
require __DIR__.'/ui.php';
