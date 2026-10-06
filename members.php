<?php require 'app.php';need();if(!admin())die('Admins only');
function membersError($message){$_SESSION['member_error']=$message;header('Location: members.php');exit;}
function removeDirectoryPhotoIfUnused($path){
 $prefix='uploads/member-images/';
 if(strpos($path,$prefix)!==0)return;
 if(q('SELECT 1 FROM members WHERE image_path=? UNION ALL SELECT 1 FROM candidates WHERE image_path=? LIMIT 1',[$path,$path])->fetch())return;
 $file=__DIR__.'/'.$prefix.basename($path);
 if(is_file($file))unlink($file);
}
function saveDirectoryPhoto($upload){
 if(!$upload||($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name']??''))membersError('Choose a valid member photo.');
 if($upload['size']>5*1024*1024)membersError('Member photos must be 5 MB or smaller.');
 $types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
 if(!isset($types[$mime])||getimagesize($upload['tmp_name'])===false)membersError('Use a valid JPEG, PNG, GIF, or WebP member photo.');
 $directory=__DIR__.'/uploads/member-images';
 if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))membersError('Unable to create member photo storage.');
 $filename=bin2hex(random_bytes(16)).'.'.$types[$mime];
 if(!move_uploaded_file($upload['tmp_name'],$directory.'/'.$filename))membersError('Unable to save member photo.');
 return 'uploads/member-images/'.$filename;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check();$action=$_POST['do']??'';$id=(int)($_POST['id']??0);
 switch($action){
  case 'add':
   $name=trim($_POST['name']??'');$organizationId=(int)($_POST['organization_id']??0);
   if($name==='')membersError('Member name cannot be blank.');
   if(!$organizationId||!q('SELECT id FROM organizations WHERE id=?',[$organizationId])->fetch())membersError('Select a valid organization.');
   $imagePath=saveDirectoryPhoto($_FILES['photo']??null);
   try{q('INSERT INTO members(name,organization_id,image_path) VALUES(?,?,?)',[$name,$organizationId,$imagePath]);}
   catch(Throwable $error){removeDirectoryPhotoIfUnused($imagePath);membersError('Unable to add this member. Check for a duplicate name in the organization.');}
   $_SESSION['member_notice']='Member added to the directory.';
   break;
  case 'update':
   $member=q('SELECT image_path FROM members WHERE id=?',[$id])->fetch();
   if(!$member)membersError('Member not found.');
   $name=trim($_POST['name']??'');$organizationId=(int)($_POST['organization_id']??0);
   if($name==='')membersError('Member name cannot be blank.');
   if(!$organizationId||!q('SELECT id FROM organizations WHERE id=?',[$organizationId])->fetch())membersError('Select a valid organization.');
   $imagePath=$member['image_path'];$upload=$_FILES['photo']??null;
   if($upload&&($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$imagePath=saveDirectoryPhoto($upload);
   try{q('UPDATE members SET name=?,organization_id=?,image_path=? WHERE id=?',[$name,$organizationId,$imagePath,$id]);}
   catch(Throwable $error){if($imagePath!==$member['image_path'])removeDirectoryPhotoIfUnused($imagePath);membersError('Unable to update this member. Check for a duplicate name in the organization.');}
   if($imagePath!==$member['image_path']&&$member['image_path'])removeDirectoryPhotoIfUnused($member['image_path']);
   $_SESSION['member_notice']='Member profile updated.';
   break;
  case 'delete':
   $member=q('SELECT image_path FROM members WHERE id=?',[$id])->fetch();
   if(!$member)membersError('Member not found.');
   q('DELETE FROM members WHERE id=?',[$id]);
   if($member['image_path'])removeDirectoryPhotoIfUnused($member['image_path']);
   $_SESSION['member_notice']='Member removed from the directory. Existing election results are unchanged.';
   break;
  default:membersError('Unknown member action.');
 }
 header('Location: members.php');exit;
}
$organizations=q('SELECT id,name,logo_path FROM organizations ORDER BY name')->fetchAll();
$rows=q('SELECT o.id organization_id,o.name organization_name,o.logo_path organization_logo,m.id member_id,m.name member_name,m.image_path FROM organizations o LEFT JOIN members m ON m.organization_id=o.id ORDER BY o.name,m.name')->fetchAll();
$groups=[];$unassigned=[];$memberCount=0;
foreach($rows as $row){
 $key=(int)$row['organization_id'];
 if(!isset($groups[$key]))$groups[$key]=['name'=>$row['organization_name'],'logo'=>$row['organization_logo'],'members'=>[]];
 if($row['member_id']){$groups[$key]['members'][]=['id'=>$row['member_id'],'name'=>$row['member_name'],'image_path'=>$row['image_path'],'organization_id'=>$key];$memberCount++;}
}
$unassigned=q('SELECT id,name,image_path FROM members WHERE organization_id IS NULL ORDER BY name')->fetchAll();
$memberCount+=count($unassigned);
head('Members',true);
?>
<style>
.directory-title{border-bottom:1px solid #dce5e0;padding-bottom:16px}.member-add-panel{border-top:3px solid #0b6e4f}.directory-heading{border-bottom:1px solid #dce5e0;padding-bottom:12px}.organization-group{padding:16px 0;border-bottom:1px solid #dce5e0}.organization-group:last-child{border-bottom:0}.organization-group-heading{display:flex;align-items:center;gap:10px;margin-bottom:8px}.organization-logo{width:36px;height:36px;object-fit:contain}.member-record{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-top:1px solid #edf0ee}.member-photo,.member-placeholder{width:48px;height:48px;object-fit:cover;flex:0 0 48px}.member-edit summary{color:#0b6e4f;cursor:pointer;font-size:13px}.member-edit[open] summary{margin-bottom:10px}
</style>
<div class="directory-title d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
 <div><div class="text-success small fw-semibold text-uppercase"></div><h1 class="h3 mb-0">Member directory</h1></div>
  <span class="text-muted"><?//=number_format($memberCount)?></span>
</div>
<?php if(!empty($_SESSION['member_error'])):?><div class="alert alert-danger"><?=e($_SESSION['member_error'])?></div><?php unset($_SESSION['member_error']);endif?>
<?php if(!empty($_SESSION['member_notice'])):?><div class="alert alert-success"><?=e($_SESSION['member_notice'])?></div><?php unset($_SESSION['member_notice']);endif?>
<div class="row g-4 align-items-start">
 <section class="col-lg-4" aria-labelledby="add-member-heading">
  <?php if($organizations):?><form method="post" enctype="multipart/form-data" class="card card-body member-add-panel"><?=T()?>
   <input type="hidden" name="do" value="add"><h2 class="h5 mb-3" id="add-member-heading">Add a member</h2>
   <div class="mb-3"><label class="form-label" for="member-name">Name</label><input class="form-control" id="member-name" name="name" maxlength="120" required></div>
   <div class="mb-3"><label class="form-label" for="member-organization">Organization</label><select class="form-select" id="member-organization" name="organization_id" required><option value="">Select organization</option><?php foreach($organizations as $organization):?><option value="<?=$organization['id']?>"><?=e($organization['name'])?></option><?php endforeach?></select></div>
   <div class="mb-3"><label class="form-label" for="member-photo">Photo</label><input class="form-control" type="file" id="member-photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" required><div class="form-text">JPEG, PNG, GIF, or WebP · max 5 MB</div></div>
   <button class="btn btn-success w-100"><i class="bi bi-person-plus"></i> Save to directory</button>
  </form><?php else:?><div class="alert alert-warning mb-0">Add an organization before adding members. <a href="organizations.php">Manage organizations</a></div><?php endif?>
 </section>
 <section class="col-lg-8" aria-labelledby="organization-roster-heading">
  <div class="directory-heading"><h2 class="h5 mb-0" id="organization-roster-heading">Members by organization</h2></div>
  <?php if(!$rows):?><div class="text-muted py-4">No members saved yet.</div><?php endif?>
  <?php foreach($groups as $group):?><section class="organization-group" aria-label="<?=e($group['name'])?> members">
   <div class="organization-group-heading"><?php if($group['logo']):?><img class="organization-logo" src="<?=e($group['logo'])?>" alt=""><?php else:?><span class="organization-logo d-flex align-items-center justify-content-center bg-light rounded"><i class="bi bi-building text-secondary"></i></span><?php endif?><h3 class="h6 mb-0 flex-grow-1"><?=e($group['name'])?></h3><span class="text-muted small"><?=count($group['members'])?></span></div>
   <?php foreach($group['members'] as $member):?><div class="member-record">
    <?php if($member['image_path']):?><img class="member-photo rounded" src="<?=e($member['image_path'])?>" alt="<?=e($member['name'])?>"><?php else:?><span class="member-placeholder rounded bg-light d-flex align-items-center justify-content-center"><i class="bi bi-person text-secondary"></i></span><?php endif?>
    <div class="flex-grow-1"><div class="fw-semibold"><?=e($member['name'])?></div><details class="member-edit mt-1"><summary>Edit profile</summary>
     <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end mt-1"><?=T()?>
      <input type="hidden" name="do" value="update"><input type="hidden" name="id" value="<?=$member['id']?>">
      <div class="col-md-5"><label class="form-label small">Name</label><input class="form-control form-control-sm" name="name" value="<?=e($member['name'])?>" maxlength="120" required></div>
      <div class="col-md-4"><label class="form-label small">Organization</label><select class="form-select form-select-sm" name="organization_id" required><?php foreach($organizations as $organization):?><option value="<?=$organization['id']?>" <?=$member['organization_id']==$organization['id']?'selected':''?>><?=e($organization['name'])?></option><?php endforeach?></select></div>
      <div class="col-md-8"><label class="form-label small">Replace photo</label><input class="form-control form-control-sm" type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"></div>
      <div class="col-auto"><button class="btn btn-outline-success btn-sm"><i class="bi bi-save"></i> Save</button></div>
     </form>
    </details></div>
    <form method="post" onsubmit="return confirm('Remove this member from the directory? Existing election results will remain.');"><?=T()?>
     <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?=$member['id']?>"><button class="btn btn-outline-danger btn-sm" aria-label="Remove <?=e($member['name'])?>"><i class="bi bi-trash"></i></button>
    </form>
   </div><?php endforeach?>
  </section><?php endforeach?>
  <?php if($unassigned):?><section class="organization-group"><div class="organization-group-heading"><span class="organization-logo d-flex align-items-center justify-content-center bg-light rounded"><i class="bi bi-building text-secondary"></i></span><h3 class="h6 mb-0 flex-grow-1">No organization</h3><span class="text-muted small"><?=count($unassigned)?></span></div>
    <?php foreach($unassigned as $member):?><div class="member-record">
     <?php if($member['image_path']):?><img class="member-photo rounded" src="<?=e($member['image_path'])?>" alt="<?=e($member['name'])?>"><?php else:?><span class="member-placeholder rounded bg-light d-flex align-items-center justify-content-center"><i class="bi bi-person text-secondary"></i></span><?php endif?>
     <div class="flex-grow-1"><div class="fw-semibold"><?=e($member['name'])?></div><details class="member-edit mt-1"><summary>Edit profile</summary><form method="post" enctype="multipart/form-data" class="row g-2 align-items-end mt-1"><?=T()?>
      <input type="hidden" name="do" value="update"><input type="hidden" name="id" value="<?=$member['id']?>">
      <div class="col-md-5"><label class="form-label small">Name</label><input class="form-control form-control-sm" name="name" value="<?=e($member['name'])?>" maxlength="120" required></div>
      <div class="col-md-4"><label class="form-label small">Organization</label><select class="form-select form-select-sm" name="organization_id" required><option value="">Select organization</option><?php foreach($organizations as $organization):?><option value="<?=$organization['id']?>"><?=e($organization['name'])?></option><?php endforeach?></select></div>
      <div class="col-md-8"><label class="form-label small">Replace photo</label><input class="form-control form-control-sm" type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"></div>
      <div class="col-auto"><button class="btn btn-outline-success btn-sm"><i class="bi bi-save"></i> Save</button></div>
     </form></details></div>
     <form method="post" onsubmit="return confirm('Remove this member from the directory? Existing election results will remain.');"><?=T()?>
      <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?=$member['id']?>"><button class="btn btn-outline-danger btn-sm" aria-label="Remove <?=e($member['name'])?>"><i class="bi bi-trash"></i></button>
     </form>
    </div><?php endforeach?>
  </section><?php endif?>
 </section>
</div>
<?php foot();