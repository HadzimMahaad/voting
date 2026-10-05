<?php require 'app.php';
if(admin()){header('Location: '.appBasePath().'admin.php');exit;}
if(voter()){header('Location: '.appBasePath().'index.php');exit;}
$error='';$notice='';$formAction=staffPathRequest()?appBasePath().'auth.php/admin':'staff-auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check();$mode=$_POST['mode']??'';
 if($mode==='register'){
  $name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$password=$_POST['password']??'';
  if($name===''||$email===''||$password==='')$error='Complete all registration fields.';
  else try{
   $firstAdmin=!q('SELECT id FROM users WHERE is_admin=1 LIMIT 1')->fetch();
   q('INSERT INTO users(name,email,password,is_admin,is_staff,approved) VALUES(?,?,?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),(int)$firstAdmin,1,(int)$firstAdmin]);
   if($firstAdmin){
    session_regenerate_id(true);$_SESSION['uid']=$pdo->lastInsertId();$_SESSION['admin']=true;$_SESSION['superadmin']=true;$_SESSION['staff']=false;$_SESSION['voter']=false;
    header('Location: '.appBasePath().'admin.php');exit;
   }
   $notice='Your SPR staff registration is pending approval by an existing admin.';
  }catch(PDOException $exception){$error='That email address is already registered.';}
 }elseif($mode==='login'){
  $user=q('SELECT * FROM users WHERE email=?', [trim($_POST['email']??'')])->fetch();
  if($user&&password_verify($_POST['password']??'',$user['password'])){
   if($user['is_admin']||($user['is_staff']&&$user['approved'])){
    session_regenerate_id(true);$_SESSION['uid']=$user['id'];$_SESSION['admin']=true;$_SESSION['superadmin']=(bool)$user['is_admin'];$_SESSION['staff']=!(bool)$user['is_admin'];$_SESSION['voter']=false;
    header('Location: '.appBasePath().'admin.php');exit;
   }
   if($user['is_staff']&&!$user['approved'])$error='Your SPR staff registration is awaiting admin approval.';
   else $error='Invalid staff email or password.';
  }else $error='Invalid staff email or password.';
 }else $error='Choose login or registration.';
}
head('Staff SPR');
?>
<h1 class="h3 mb-3">Staff SPR</h1>
<?php if(is_file(__DIR__.'/assets/spr.png')):?><img class="portal-logo" src="assets/spr.png" alt="SPR voting logo"><?php endif?>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?>
<?php if($notice):?><div class="alert alert-info"><?=e($notice)?></div><?php endif?>
<div class="row g-3">
 <div class="col-md-6"><section class="card h-100"><div class="card-body"><h2 class="h5 mb-3"><i class="bi bi-box-arrow-in-right"></i> Staff login</h2>
    <form method="post" action="<?=e($formAction)?>"><?=T()?><input type="hidden" name="mode" value="login">
   <div class="mb-3"><label class="form-label" for="staff-login-email">Email</label><input class="form-control" id="staff-login-email" type="email" name="email" required></div>
   <div class="mb-3"><label class="form-label" for="staff-login-password">Password</label><input class="form-control" id="staff-login-password" type="password" name="password" required></div>
   <button class="btn btn-success w-100">Log in to SPR</button>
  </form>
 </div></section></div>
 <div class="col-md-6"><section class="card h-100"><div class="card-body"><h2 class="h5 mb-3"><i class="bi bi-person-plus"></i> Staff registration</h2>
    <form method="post" action="<?=e($formAction)?>"><?=T()?><input type="hidden" name="mode" value="register">
   <div class="mb-3"><label class="form-label" for="staff-name">Name</label><input class="form-control" id="staff-name" name="name" maxlength="80" required></div>
   <div class="mb-3"><label class="form-label" for="staff-email">Email</label><input class="form-control" id="staff-email" type="email" name="email" required></div>
   <div class="mb-3"><label class="form-label" for="staff-password">Password</label><input class="form-control" id="staff-password" type="password" name="password" required></div>
   <button class="btn btn-outline-success w-100">Request SPR staff access</button>
  </form>
 </div></section></div>
</div>
<?php foot();