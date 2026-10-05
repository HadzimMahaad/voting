<?php require 'app.php';
if(admin()){header('Location: admin.php');exit;}
if(voter()){header('Location: index.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check();$mode=$_POST['mode']??'';
 if($mode==='register'){
    $name=trim($_POST['name']??'');$voterId=strtoupper(trim($_POST['voter_id']??''));$email=trim($_POST['email']??'');$password=$_POST['password']??'';
    if($name===''||$voterId===''||$email===''||$password==='')$error='Complete all registration fields.';
  else try{
    q('INSERT INTO users(name,voter_id,email,password,is_admin,is_staff,approved) VALUES(?,?,?,?,0,0,1)',[$name,$voterId,$email,password_hash($password,PASSWORD_DEFAULT)]);
   session_regenerate_id(true);$_SESSION['uid']=$pdo->lastInsertId();$_SESSION['admin']=false;$_SESSION['superadmin']=false;$_SESSION['staff']=false;$_SESSION['voter']=true;
   header('Location: index.php');exit;
    }catch(PDOException $exception){$error='That No.ID or email address is already registered.';}
 }elseif($mode==='login'){
    $identifier=trim($_POST['voter_id']??'');
    $user=q('SELECT * FROM users WHERE voter_id=? LIMIT 1',[strtoupper($identifier)])->fetch();
    if(!$user)$user=q('SELECT * FROM users WHERE voter_id IS NULL AND email=? LIMIT 1',[$identifier])->fetch();
  if($user&&password_verify($_POST['password']??'',$user['password'])&&!$user['is_admin']&&!$user['is_staff']&&$user['approved']){
   session_regenerate_id(true);$_SESSION['uid']=$user['id'];$_SESSION['admin']=false;$_SESSION['superadmin']=false;$_SESSION['staff']=false;$_SESSION['voter']=true;
   header('Location: index.php');exit;
  }
  $error='Invalid voter email or password.';
 }else $error='Choose login or registration.';
}
head('User Vote');
?>
<h1 class="h3 mb-3">User Vote</h1>
<?php if(is_file(__DIR__.'/assets/spr.png')):?><img class="portal-logo" src="assets/spr.png" alt="SPR voting logo"><?php endif?>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?>
<div class="row g-3">
 <div class="col-md-6"><section class="card h-100"><div class="card-body"><h2 class="h5 mb-3"><i class="bi bi-box-arrow-in-right"></i> Voter login</h2>
  <form method="post"><?=T()?><input type="hidden" name="mode" value="login">
    <div class="mb-3"><label class="form-label" for="voter-login-id">No.ID</label><input class="form-control" id="voter-login-id" name="voter_id" autocomplete="username" maxlength="40" required><div class="form-text">Existing accounts without a No.ID may use their email.</div></div>
   <div class="mb-3"><label class="form-label" for="voter-login-password">Password</label><input class="form-control" id="voter-login-password" type="password" name="password" required></div>
   <button class="btn btn-success w-100">Log in to vote</button>
  </form>
 </div></section></div>
 <div class="col-md-6"><section class="card h-100"><div class="card-body"><h2 class="h5 mb-3"><i class="bi bi-person-plus"></i> Voter registration</h2>
  <form method="post"><?=T()?><input type="hidden" name="mode" value="register">
   <div class="mb-3"><label class="form-label" for="voter-name">Name</label><input class="form-control" id="voter-name" name="name" maxlength="80" required></div>
    <div class="mb-3"><label class="form-label" for="voter-id">No.ID</label><input class="form-control" id="voter-id" name="voter_id" maxlength="40" required></div>
   <div class="mb-3"><label class="form-label" for="voter-email">Email</label><input class="form-control" id="voter-email" type="email" name="email" required></div>
   <div class="mb-3"><label class="form-label" for="voter-password">Password</label><input class="form-control" id="voter-password" type="password" name="password" required></div>
   <button class="btn btn-outline-success w-100">Register to vote</button>
  </form>
 </div></section></div>
</div>
<?php foot();