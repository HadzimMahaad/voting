<?php require 'app.php';need();if(!admin())die('Admins only');
function removeOrganizationLogo($path){
 $prefix='uploads/organization-logos/';
 if(strpos($path,$prefix)!==0)return;
 $file=__DIR__.'/'.$prefix.basename($path);
 if(is_file($file))unlink($file);
}
if($_SERVER['REQUEST_METHOD']==='POST'){check();
 switch($_POST['do']??''){
  case 'add':
   $names=array_unique(array_filter(array_map('trim',preg_split('/\R/',$_POST['names']??''))));
   foreach($names as $name)q('INSERT IGNORE INTO organizations(name) VALUES(?)',[$name]);
   break;
   case 'rename':
    $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');
    if($name==='')$_SESSION['organization_error']='Name cannot be blank.';
    elseif(q('SELECT 1 FROM organizations WHERE name=? AND id<>?',[$name,$id])->fetch())$_SESSION['organization_error']='That name is already in use.';
    else q('UPDATE organizations SET name=? WHERE id=?',[$name,$id]);
    break;
    case 'logo':
     $id=(int)($_POST['id']??0);
     $organization=q('SELECT logo_path FROM organizations WHERE id=?',[$id])->fetch();
     if(!$organization)die('Organization not found');
     $upload=$_FILES['logo']??null;
     if(!$upload||$upload['error']!==UPLOAD_ERR_OK)die('Choose a logo image to upload.');
     if($upload['size']>5*1024*1024)die('Logo image must be 5 MB or smaller.');
     $types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
     $mime=(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
     if(!isset($types[$mime]))die('Upload a JPEG, PNG, GIF, or WebP image.');
     $directory=__DIR__.'/uploads/organization-logos';
     if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))die('Unable to create logo storage.');
     $filename=bin2hex(random_bytes(16)).'.'.$types[$mime];
     if(!move_uploaded_file($upload['tmp_name'],$directory.'/'.$filename))die('Unable to save logo image.');
     $path='uploads/organization-logos/'.$filename;
     q('UPDATE organizations SET logo_path=? WHERE id=?',[$path,$id]);
     if($organization['logo_path'])removeOrganizationLogo($organization['logo_path']);
     break;
    case 'delete':
     $organization=q('SELECT logo_path FROM organizations WHERE id=?',[(int)($_POST['id']??0)])->fetch();
     q('DELETE FROM organizations WHERE id=?',[(int)($_POST['id']??0)]);
     if($organization&&$organization['logo_path'])removeOrganizationLogo($organization['logo_path']);
     break;
 }
 header('Location: organizations.php');exit;}
head('Organizations');
?>
<h1 class="h4 mb-3">Groups &amp; organizations</h1>
<?php if(!empty($_SESSION['organization_error'])):?>
<div class="alert alert-danger"><?=e($_SESSION['organization_error'])?></div>
<?php unset($_SESSION['organization_error']);endif?>
<form class="card card-body mb-4" method="post"><?=T()?>
 <input type="hidden" name="do" value="add">
 <label class="form-label" for="organization-names">Add group or organization names</label>
 <div class="input-group">
  <textarea class="form-control" id="organization-names" name="names" rows="3" placeholder="One name per line" required></textarea>
  <button class="btn btn-success"><i class="bi bi-plus-lg"></i> Save list</button>
 </div>
</form>
<?php $organizations=q('SELECT id,name,logo_path FROM organizations ORDER BY name')->fetchAll();
if(!$organizations):?>
<div class="alert alert-info">No groups or organizations have been saved.</div>
<?php else:?>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
 <thead><tr><th scope="col">Logo</th><th scope="col">Group or organization</th><th scope="col">Choose logo (uploads immediately)</th><th scope="col" class="text-end">Action</th></tr></thead>
 <tbody><?php foreach($organizations as $organization):?>
    <tr>
     <td><?php if($organization['logo_path']):?><img src="<?=e($organization['logo_path'])?>" alt="<?=e($organization['name'])?> logo" width="48" height="48" style="object-fit:contain"><?php else:?><span class="text-muted">No logo</span><?php endif?></td>
   <td><form method="post" class="d-flex gap-2"><?=T()?>
    <input type="hidden" name="do" value="rename"><input type="hidden" name="id" value="<?=$organization['id']?>">
    <input class="form-control form-control-sm" name="name" value="<?=e($organization['name'])?>" maxlength="200" required aria-label="Group or organization name">
    <button class="btn btn-outline-success btn-sm">Save</button>
   </form></td>
     <td><form method="post" enctype="multipart/form-data" class="d-flex gap-2 align-items-center"><?=T()?>
        <input type="hidden" name="do" value="logo"><input type="hidden" name="id" value="<?=$organization['id']?>">
        <input class="form-control form-control-sm" type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="this.form.requestSubmit()">
     </form></td>
     <td class="text-end">
   <form method="post" class="d-inline"><?=T()?>
    <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?=$organization['id']?>">
    <button class="btn btn-outline-danger btn-sm" aria-label="Remove <?=e($organization['name'])?>"><i class="bi bi-trash"></i></button>
   </form>
  </td></tr>
 <?php endforeach?></tbody>
</table></div></div>
<?php endif;foot();