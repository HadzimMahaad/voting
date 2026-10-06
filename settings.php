<?php
require 'app.php';need();if(!admin())die('Admins only');
q("CREATE TABLE IF NOT EXISTS app_settings (setting_key varchar(100) NOT NULL PRIMARY KEY, setting_value varchar(255) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
q("CREATE TABLE IF NOT EXISTS states (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, name varchar(150) NOT NULL UNIQUE, logo_path varchar(255) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
q("CREATE TABLE IF NOT EXISTS parliaments (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, state_id int DEFAULT NULL, name varchar(150) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
q("CREATE TABLE IF NOT EXISTS state_constituencies (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, state_id int DEFAULT NULL, name varchar(150) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach(['parliaments','state_constituencies'] as $table){$columns=q("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);if(!in_array('state_id',$columns,true))q("ALTER TABLE `$table` ADD state_id int DEFAULT NULL");}
$stateColumns=q('SHOW COLUMNS FROM states')->fetchAll(PDO::FETCH_COLUMN);if(!in_array('logo_path',$stateColumns,true))q('ALTER TABLE states ADD logo_path varchar(255) DEFAULT NULL');
$types=['parliament'=>'parliaments','constituency'=>'state_constituencies'];$typeNames=['parliament'=>'Parliament','constituency'=>'State Constituency'];
function settingsRedirect($query=''){header('Location: settings.php'.($query?'?'.$query:''));exit;}
function settingsFlash($message,$error=false){$_SESSION[$error?'settings_error':'settings_notice']=$message;}
function removeSettingLogo($path){$prefix='uploads/site-logo/';if(strpos($path,$prefix)!==0)return;$file=__DIR__.'/'.$prefix.basename($path);if(is_file($file))unlink($file);}
function removeStateLogo($path){$prefix='uploads/state-logos/';if(strpos($path,$prefix)!==0)return;$file=__DIR__.'/'.$prefix.basename($path);if(is_file($file))unlink($file);}
function saveStateLogo($upload){
 if(!$upload||($upload['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
 if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name']??''))throw new RuntimeException('Choose a valid state logo image.');
 if($upload['size']>5*1024*1024)throw new RuntimeException('State logo must be 5 MB or smaller.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);$formats=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
 if(!isset($formats[$mime])||getimagesize($upload['tmp_name'])===false)throw new RuntimeException('Upload a valid JPEG, PNG, GIF, or WebP state logo.');
 $directory=__DIR__.'/uploads/state-logos';if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('Unable to create state logo storage.');
 $filename=bin2hex(random_bytes(16)).'.'.$formats[$mime];if(!move_uploaded_file($upload['tmp_name'],$directory.'/'.$filename))throw new RuntimeException('Unable to save state logo.');
 return 'uploads/state-logos/'.$filename;
}
$tab=$_GET['tab']??'logo';if(!in_array($tab,['logo','state'],true))$tab='logo';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check();$action=$_POST['do']??'';$tab=$_POST['tab']??'logo';if(!in_array($tab,['logo','state'],true))$tab='logo';
 if($tab==='logo'&&$action==='logo'){
  $upload=$_FILES['logo']??null;
  if(!$upload||($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name']??'')){settingsFlash('Choose an image to upload.',true);settingsRedirect('tab=logo');}
  if($upload['size']>5*1024*1024){settingsFlash('Logo must be 5 MB or smaller.',true);settingsRedirect('tab=logo');}
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);$formats=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
  if(!isset($formats[$mime])||getimagesize($upload['tmp_name'])===false){settingsFlash('Upload a valid JPEG, PNG, GIF, or WebP image.',true);settingsRedirect('tab=logo');}
  $directory=__DIR__.'/uploads/site-logo';if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory)){settingsFlash('Unable to create logo storage.',true);settingsRedirect('tab=logo');}
  $filename=bin2hex(random_bytes(16)).'.'.$formats[$mime];if(!move_uploaded_file($upload['tmp_name'],$directory.'/'.$filename)){settingsFlash('Unable to save the logo.',true);settingsRedirect('tab=logo');}
  $old=q("SELECT setting_value FROM app_settings WHERE setting_key='site_logo'")->fetchColumn();q("INSERT INTO app_settings(setting_key,setting_value) VALUES('site_logo',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)",['uploads/site-logo/'.$filename]);if($old)removeSettingLogo($old);settingsFlash('Logo updated.');settingsRedirect('tab=logo');
 }
 if($tab==='state'){
  $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');$type=$_POST['type']??'';$stateId=(int)($_POST['state_id']??0);$query='tab=state';
  if($action==='save_state'){
   $existing=$id?q('SELECT logo_path FROM states WHERE id=?',[$id])->fetch():null;$oldPath=$existing['logo_path']??null;$newPath=$oldPath;
   if($name==='')settingsFlash('State name cannot be blank.',true);
   else try{$upload=$_FILES['state_logo']??null;if($upload&&($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$newPath=saveStateLogo($upload);if($id){q('UPDATE states SET name=?,logo_path=? WHERE id=?',[$name,$newPath,$id]);settingsFlash('State updated.');}else{q('INSERT INTO states(name,logo_path) VALUES(?,?)',[$name,$newPath]);settingsFlash('State added.');}if($newPath!==$oldPath&&$oldPath)removeStateLogo($oldPath);}
   catch(Throwable $error){if($newPath!==$oldPath&&$newPath)removeStateLogo($newPath);settingsFlash($error instanceof PDOException?'That state name already exists.':$error->getMessage(),true);}
  }elseif($action==='delete_state'){
   $state=q('SELECT logo_path FROM states WHERE id=?',[$id])->fetch();q('DELETE FROM parliaments WHERE state_id=?',[$id]);q('DELETE FROM state_constituencies WHERE state_id=?',[$id]);q('DELETE FROM states WHERE id=?',[$id]);if($state&&$state['logo_path'])removeStateLogo($state['logo_path']);settingsFlash('State and its Parliament and State Constituency entries deleted.');
  }elseif(isset($types[$type])&&$stateId&&q('SELECT id FROM states WHERE id=?',[$stateId])->fetch()){
   $table=$types[$type];$query.='&state_id='.$stateId.'&type='.$type;
   if($action==='save_location'){
    if($name==='')settingsFlash($typeNames[$type].' name cannot be blank.',true);else try{if($id){q("UPDATE `$table` SET name=? WHERE id=? AND state_id=?",[$name,$id,$stateId]);settingsFlash($typeNames[$type].' updated.');}else{q("INSERT INTO `$table`(state_id,name) VALUES(?,?)",[$stateId,$name]);settingsFlash($typeNames[$type].' added.');}}catch(PDOException $error){settingsFlash('That name already exists.',true);}
   }elseif($action==='delete_location'){q("DELETE FROM `$table` WHERE id=? AND state_id=?",[$id,$stateId]);settingsFlash($typeNames[$type].' deleted.');}
   else settingsFlash('Unknown action.',true);
  }else settingsFlash('Select a valid state and location type.',true);
  settingsRedirect($query);
 }
 settingsFlash('Unknown settings action.',true);settingsRedirect('tab='.$tab);
}
$logo=q("SELECT setting_value FROM app_settings WHERE setting_key='site_logo'")->fetchColumn();
$states=q('SELECT id,name,logo_path FROM states ORDER BY name')->fetchAll();
$type=$_GET['type']??'parliament';if(!isset($types[$type]))$type='parliament';
$stateId=(int)($_GET['state_id']??0);$selectedState=null;foreach($states as $state)if((int)$state['id']===$stateId)$selectedState=$state;
$table=$types[$type];$locations=$selectedState?q("SELECT id,name FROM `$table` WHERE state_id=? ORDER BY name",[$stateId])->fetchAll():[];
$editId=(int)($_GET['edit']??0);$editName='';foreach($locations as $location)if((int)$location['id']===$editId)$editName=$location['name'];
$editStateId=(int)($_GET['edit_state']??0);$editStateName='';$editStateLogo='';foreach($states as $state)if((int)$state['id']===$editStateId){$editStateName=$state['name'];$editStateLogo=$state['logo_path']??'';}
head('Settings',true);
?>
<div class="mb-3"><h1 class="h3 mb-1">Settings</h1><div class="text-muted">Manage the portal logo and election locations.</div></div>
<?php if(!empty($_SESSION['settings_error'])):?><div class="alert alert-danger"><?=e($_SESSION['settings_error'])?></div><?php unset($_SESSION['settings_error']);endif?>
<?php if(!empty($_SESSION['settings_notice'])):?><div class="alert alert-success"><?=e($_SESSION['settings_notice'])?></div><?php unset($_SESSION['settings_notice']);endif?>
<ul class="nav nav-tabs mb-4"><li class="nav-item"><a class="nav-link <?=$tab==='logo'?'active':''?>" href="settings.php?tab=logo">Logo</a></li><li class="nav-item"><a class="nav-link <?=$tab==='state'?'active':''?>" href="settings.php?tab=state">State</a></li></ul>
<?php if($tab==='logo'):?><div class="card card-body"><h2 class="h5">Portal logo</h2><p class="text-muted">Shown in the navigation bar. JPEG, PNG, GIF, or WebP, up to 5 MB.</p><?php if($logo):?><div class="mb-3"><img src="<?=e($logo)?>" alt="Current portal logo" style="max-width:180px;max-height:120px;object-fit:contain"></div><?php endif?><form method="post" enctype="multipart/form-data"><?=T()?><input type="hidden" name="do" value="logo"><input type="hidden" name="tab" value="logo"><div class="input-group"><input class="form-control" type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" required><button class="btn btn-success">Upload logo</button></div></form></div>
<?php else:?>
<div class="row g-4"><section class="col-lg-5"><form class="card card-body" method="post" enctype="multipart/form-data"><?=T()?><input type="hidden" name="do" value="save_state"><input type="hidden" name="tab" value="state"><input type="hidden" name="id" value="<?=$editStateId?>"><h2 class="h5"><?=$editStateId?'Edit':'Add'?> State</h2><label class="form-label" for="state-name">Name</label><input class="form-control mb-3" id="state-name" name="name" maxlength="150" value="<?=e($editStateName)?>" required><?php if($editStateLogo):?><div class="mb-2"><img src="<?=e($editStateLogo)?>" alt="<?=e($editStateName)?> logo" width="64" height="64" style="object-fit:contain"></div><?php endif?><label class="form-label" for="state-logo"><?=$editStateLogo?'Replace state logo':'Add state logo'?></label><input class="form-control mb-3" type="file" id="state-logo" name="state_logo" accept="image/jpeg,image/png,image/gif,image/webp"><div class="form-text mb-3">Optional. JPEG, PNG, GIF, or WebP, up to 5 MB.</div><button class="btn btn-success align-self-start">Save</button></form></section>
<section class="col-lg-7"><div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>State</th><th class="text-end">Actions</th></tr></thead><tbody><?php foreach($states as $state):?><tr><td class="d-flex align-items-center gap-2"><?php if($state['logo_path']):?><img src="<?=e($state['logo_path'])?>" alt="" width="36" height="36" style="object-fit:contain"><?php endif?><?=e($state['name'])?></td><td class="text-end text-nowrap"><a class="btn btn-outline-success btn-sm" href="settings.php?tab=state&amp;edit_state=<?=$state['id']?>">Edit</a> <a class="btn btn-outline-primary btn-sm" href="settings.php?tab=state&amp;state_id=<?=$state['id']?>&amp;type=parliament">Parliament</a> <a class="btn btn-outline-primary btn-sm" href="settings.php?tab=state&amp;state_id=<?=$state['id']?>&amp;type=constituency">State Constituency</a> <form class="d-inline" method="post" onsubmit="return confirm('Delete this state and its location entries?');"><?=T()?><input type="hidden" name="do" value="delete_state"><input type="hidden" name="tab" value="state"><input type="hidden" name="id" value="<?=$state['id']?>"><button class="btn btn-outline-danger btn-sm">Delete</button></form></td></tr><?php endforeach?><?php if(!$states):?><tr><td colspan="2" class="text-muted">Add a state to manage its Parliament and State Constituency entries.</td></tr><?php endif?></tbody></table></div></div></section></div>
<?php if($selectedState):?><section class="card card-body mt-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1"><?=e($typeNames[$type])?> for <?=e($selectedState['name'])?></h2><div class="btn-group btn-group-sm"><a class="btn <?=$type==='parliament'?'btn-primary':'btn-outline-primary'?>" href="settings.php?tab=state&amp;state_id=<?=$stateId?>&amp;type=parliament">Parliament</a><a class="btn <?=$type==='constituency'?'btn-primary':'btn-outline-primary'?>" href="settings.php?tab=state&amp;state_id=<?=$stateId?>&amp;type=constituency">State Constituency</a></div></div></div>
<form method="post" class="row g-2 align-items-end mb-3"><?=T()?><input type="hidden" name="do" value="save_location"><input type="hidden" name="tab" value="state"><input type="hidden" name="type" value="<?=e($type)?>"><input type="hidden" name="state_id" value="<?=$stateId?>"><input type="hidden" name="id" value="<?=$editId?>"><div class="col"><label class="form-label" for="location-name"><?=$editId?'Edit':'Add'?> <?=e($typeNames[$type])?></label><input class="form-control" id="location-name" name="name" maxlength="150" value="<?=e($editName)?>" required></div><div class="col-auto"><button class="btn btn-success">Save</button></div></form>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th><?=e($typeNames[$type])?></th><th class="text-end">Actions</th></tr></thead><tbody><?php foreach($locations as $location):?><tr><td><?=e($location['name'])?></td><td class="text-end"><a class="btn btn-outline-success btn-sm" href="settings.php?tab=state&amp;state_id=<?=$stateId?>&amp;type=<?=e($type)?>&amp;edit=<?=$location['id']?>">Edit</a> <form class="d-inline" method="post" onsubmit="return confirm('Delete this entry?');"><?=T()?><input type="hidden" name="do" value="delete_location"><input type="hidden" name="tab" value="state"><input type="hidden" name="type" value="<?=e($type)?>"><input type="hidden" name="state_id" value="<?=$stateId?>"><input type="hidden" name="id" value="<?=$location['id']?>"><button class="btn btn-outline-danger btn-sm">Delete</button></form></td></tr><?php endforeach?><?php if(!$locations):?><tr><td colspan="2" class="text-muted">No entries for this state yet.</td></tr><?php endif?></tbody></table></div></section><?php endif?>
<?php endif?>
<?php foot();
