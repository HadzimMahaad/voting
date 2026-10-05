<?php 
require 'app.php';need();if(!admin())die('Admins only');
function adminReturnUrl(){
 if(($_POST['do']??'')==='cand'&&($_POST['return_to']??'')==='members.php')return 'members.php?election='.(int)($_POST['id']??0);
 return 'admin.php';
}
function adminError($message){$_SESSION['admin_error']=$message;header('Location: '.adminReturnUrl());exit;}
function removeMemberImage($path){
 $prefix='uploads/member-images/';
 if(strpos($path,$prefix)!==0)return;
 if(q('SELECT 1 FROM members WHERE image_path=? UNION ALL SELECT 1 FROM candidates WHERE image_path=? LIMIT 1',[$path,$path])->fetch())return;
 $file=__DIR__.'/'.$prefix.basename($path);
 if(is_file($file))unlink($file);
}
if($_SERVER['REQUEST_METHOD']==='POST'){check();$id=(int)($_POST['id']??0);
 switch($_POST['do']??''){
  case 'new': q('INSERT INTO elections(title,status) VALUES(?,?)',[trim($_POST['title']),'draft']);break;
  case 'cand':
   if(!q("SELECT id FROM elections WHERE id=? AND status='draft'",[$id])->fetch())adminError('Members can only be added to draft elections.');
   $memberIds=$_POST['member_ids']??[];
   if(!is_array($memberIds))adminError('Choose members from the directory.');
   $memberIds=array_values(array_unique(array_filter(array_map('intval',$memberIds))));
   if(!$memberIds)adminError('Choose at least one member.');
   foreach($memberIds as $memberId){
    if(!q('SELECT id FROM members WHERE id=?',[$memberId])->fetch())adminError('A selected member was not found.');
    if(q('SELECT id FROM candidates WHERE election_id=? AND member_id=?',[$id,$memberId])->fetch())adminError('A selected member is already in this election.');
   }
  try{
   $pdo->beginTransaction();
    foreach($memberIds as $memberId)q('INSERT INTO candidates(election_id,name,organization_id,image_path,member_id) SELECT ?,name,organization_id,image_path,id FROM members WHERE id=?',[$id,$memberId]);
   $pdo->commit();
  }catch(Throwable $error){
   if($pdo->inTransaction())$pdo->rollBack();
    adminError('Unable to add the selected members. Please try again.');
  }
  break;
    case 'update_cand':
     $electionId=(int)($_POST['election_id']??0);$candidateId=(int)($_POST['candidate_id']??0);
  $candidate=q("SELECT c.image_path FROM candidates c JOIN elections e ON e.id=c.election_id WHERE c.id=? AND e.id=? AND e.status='draft'",[$candidateId,$electionId])->fetch();
  if(!$candidate)adminError('Members can only be edited while the election is in draft.');
     $name=trim($_POST['name']??'');$organizationId=(int)($_POST['organization_id']??0)?:null;
     if($name==='')adminError('Member name cannot be blank.');
     if($organizationId&&!q('SELECT id FROM organizations WHERE id=?',[$organizationId])->fetch())adminError('Select a valid organization.');
     $newImagePath=$candidate['image_path'];$upload=$_FILES['photo']??null;
     if($upload&&$upload['error']!==UPLOAD_ERR_NO_FILE){
        if($upload['error']!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name']))adminError('Choose a valid member photo.');
        if($upload['size']>5*1024*1024)adminError('Member photos must be 5 MB or smaller.');
        $types=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        if(!isset($types[$mime])||getimagesize($upload['tmp_name'])===false)adminError('Use a valid JPEG, PNG, GIF, or WebP member photo.');
        $directory=__DIR__.'/uploads/member-images';
        if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))adminError('Unable to create member photo storage.');
        $filename=bin2hex(random_bytes(16)).'.'.$types[$mime];
        if(!move_uploaded_file($upload['tmp_name'],$directory.'/'.$filename))adminError('Unable to save member photo.');
        $newImagePath='uploads/member-images/'.$filename;
     }
     try{
        q('UPDATE candidates SET name=?,organization_id=?,image_path=? WHERE id=? AND election_id=?',[$name,$organizationId,$newImagePath,$candidateId,$electionId]);
     }catch(Throwable $error){
        if($newImagePath!==$candidate['image_path'])removeMemberImage($newImagePath);
        adminError('Unable to update this member. Please try again.');
     }
     if($newImagePath!==$candidate['image_path']&&$candidate['image_path'])removeMemberImage($candidate['image_path']);
     break;
   case 'status':
      if(($_POST['s']??'')==='draft')q("UPDATE elections SET status='draft' WHERE id=? AND status IN ('draft','open')",[$id]);
    elseif(($_POST['s']??'')==='open')q("UPDATE elections SET status='open' WHERE id=? AND status='draft'",[$id]);
    elseif(($_POST['s']??'')==='closed')q("UPDATE elections SET status='closed' WHERE id=? AND status='open'",[$id]);
    break;}
 header('Location: '.adminReturnUrl());exit;}
head('Admin');$organizations=q('SELECT id,name,logo_path FROM organizations ORDER BY name')->fetchAll();$directoryMembers=q('SELECT m.id,m.name,m.image_path,o.name organization_name,o.logo_path organization_logo FROM members m LEFT JOIN organizations o ON o.id=m.organization_id ORDER BY o.name,m.name')->fetchAll();?>
<?php if(!empty($_SESSION['admin_error'])):?><div class="alert alert-danger"><?=e($_SESSION['admin_error'])?></div><?php unset($_SESSION['admin_error']);endif?>
<form class="card card-body mb-4" method="post"><?=T()?><input type="hidden" name="do" value="new">
 <div class="input-group"><input class="form-control" name="title" placeholder="New election title" required><button class="btn btn-success"><i class="bi bi-plus-lg"></i> Create</button></div></form>
<?php foreach(q('SELECT * FROM elections ORDER BY id DESC')->fetchAll() as $el):?>
<div class="card mb-3"><div class="card-body">
 <div class="d-flex justify-content-between mb-2"><h5><?=e($el['title'])?></h5><?=badge($el['status'])?></div>
 <?php $members=q('SELECT c.id,c.name,c.organization_id,c.image_path,o.name organization_name,o.logo_path organization_logo FROM candidates c LEFT JOIN organizations o ON o.id=c.organization_id WHERE c.election_id=? ORDER BY c.id',[$el['id']])->fetchAll();if($members):?>
 <div class="list-group mb-3"><?php foreach($members as $member):?><div class="list-group-item">
  <div class="d-flex align-items-center gap-3">
   <?php if($member['image_path']):?><img src="<?=e($member['image_path'])?>" alt="<?=e($member['name'])?>" width="56" height="56" class="rounded" style="object-fit:cover"><?php else:?><div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:56px;height:56px"><i class="bi bi-person text-secondary"></i></div><?php endif?>
   <div><div class="fw-semibold"><?=e($member['name'])?></div><div class="small text-muted d-flex align-items-center gap-2">
    <?php if($member['organization_logo']):?><img src="<?=e($member['organization_logo'])?>" alt="" width="24" height="24" style="object-fit:contain"><?php endif?><?=e($member['organization_name']??'No organization')?>
   </div></div>
  </div>
  <?php if($el['status']==='draft'&&$organizations):?><form method="post" enctype="multipart/form-data" class="row g-2 align-items-end mt-2"><?=T()?>
   <input type="hidden" name="do" value="update_cand"><input type="hidden" name="election_id" value="<?=$el['id']?>"><input type="hidden" name="candidate_id" value="<?=$member['id']?>">
   <div class="col-lg-3"><label class="form-label">Member name</label><input class="form-control form-control-sm" name="name" value="<?=e($member['name'])?>" maxlength="120" required></div>
   <div class="col-lg-3"><label class="form-label">Organization</label><select class="form-select form-select-sm" name="organization_id"><option value="">No organization</option><?php foreach($organizations as $organization):?><option value="<?=$organization['id']?>" <?=$member['organization_id']==$organization['id']?'selected':''?>><?=e($organization['name'])?></option><?php endforeach?></select></div>
   <div class="col"><label class="form-label">Replace member photo</label><input class="form-control form-control-sm" type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"></div>
   <div class="col-auto"><button class="btn btn-outline-success btn-sm"><i class="bi bi-save"></i> Save changes</button></div>
  </form><?php endif?>
 </div><?php endforeach?></div><?php endif?>
 <?php if($el['status']==='draft'):?>
   <?php $availableMembers=q('SELECT m.id,m.name,m.image_path,o.name organization_name,o.logo_path organization_logo FROM members m LEFT JOIN organizations o ON o.id=m.organization_id WHERE NOT EXISTS(SELECT 1 FROM candidates c WHERE c.election_id=? AND c.member_id=m.id) ORDER BY o.name,m.name',[$el['id']])->fetchAll();?>
   <?php if(!$directoryMembers):?><div class="alert alert-warning">Create member profiles first. <a href="members.php">Open the member directory</a></div>
   <?php elseif(!$availableMembers):?><div class="alert alert-info">All directory members are already in this election.</div>
   <?php else:?><form method="post" class="mb-3" data-member-form><?=T()?>
    <input type="hidden" name="do" value="cand"><input type="hidden" name="id" value="<?=$el['id']?>">
    <label class="form-label">Add members from directory</label>
    <div class="vstack gap-2 mb-2" data-member-rows></div>
    <template data-member-template><div class="row g-2 align-items-center" data-member-row>
      <div class="col-md-5"><label class="visually-hidden">Select a member</label><select class="form-select" name="member_ids[]" data-member-select required><option value="">Select member</option><?php foreach($availableMembers as $directoryMember):?><option value="<?=$directoryMember['id']?>" data-name="<?=e($directoryMember['name'])?>" data-photo="<?=e($directoryMember['image_path']??'')?>" data-organization="<?=e($directoryMember['organization_name']??'No organization')?>" data-logo="<?=e($directoryMember['organization_logo']??'')?>"><?=e($directoryMember['name'])?> · <?=e($directoryMember['organization_name']??'No organization')?></option><?php endforeach?></select></div>
      <div class="col" data-member-preview><span class="small text-muted">Choose a member to preview saved details.</span></div>
      <div class="col-auto"><button type="button" class="btn btn-outline-danger" data-remove-member aria-label="Remove member selection"><i class="bi bi-trash"></i></button></div>
    </div></template>
    <div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary btn-sm" data-add-member><i class="bi bi-plus-lg"></i> Add another</button><button class="btn btn-success btn-sm"><i class="bi bi-person-plus"></i> Add to election</button></div>
  </form><?php endif?><?php endif?>
 <div class="d-flex flex-wrap gap-2">
 <?php if($el['status']==='closed'):?><a class="btn btn-outline-primary btn-sm" href="report.php?election=<?=$el['id']?>"><i class="bi bi-file-earmark-arrow-down"></i> Generate report</a><?php endif?>
 <?php $statusActions=[];
 if($el['status']==='draft')$statusActions=['draft'=>['Save as draft','outline-secondary'],'open'=>['Open voting','success']];
 elseif($el['status']==='open')$statusActions=['draft'=>['Save as draft','outline-secondary'],'closed'=>['End voting & publish results','dark']];
 foreach($statusActions as $s=>[$l,$c]):?>
  <form method="post"><?=T()?><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?=$el['id']?>"><input type="hidden" name="s" value="<?=$s?>"><button class="btn btn-<?=$c?> btn-sm"><?=$l?></button></form>
 <?php endforeach?></div>
 <?php if($el['status']!=='draft'):?><hr><h6>Live tally</h6><?php results($el['id']);endif?>
</div></div>
<?php endforeach;?>
<script>
document.querySelectorAll('[data-member-form]').forEach((form)=>{
 const rows=form.querySelector('[data-member-rows]');
 const template=form.querySelector('[data-member-template]');
 const previewMember=(row)=>{
   const select=row.querySelector('[data-member-select]');
   const option=select.options[select.selectedIndex];
   const preview=row.querySelector('[data-member-preview]');
   preview.replaceChildren();
   if(!select.value){preview.innerHTML='<span class="small text-muted">Choose a member to preview saved details.</span>';return;}
   if(option.dataset.photo){const photo=document.createElement('img');photo.src=option.dataset.photo;photo.alt='';photo.width=44;photo.height=44;photo.className='rounded me-2';photo.style.objectFit='cover';preview.append(photo);}
   const details=document.createElement('span');details.className='d-inline-flex flex-column align-middle';
   const name=document.createElement('strong');name.className='small';name.textContent=option.dataset.name;details.append(name);
   const organization=document.createElement('span');organization.className='small text-muted d-inline-flex align-items-center gap-1';
   if(option.dataset.logo){const logo=document.createElement('img');logo.src=option.dataset.logo;logo.alt='';logo.width=18;logo.height=18;organization.append(logo);}
   organization.append(document.createTextNode(option.dataset.organization));details.append(organization);preview.append(details);
 };
 const refreshSelections=()=>{
   const selects=[...rows.querySelectorAll('[data-member-select]')];const selected=new Set();
   selects.forEach((select)=>{if(select.value&&selected.has(select.value))select.value='';if(select.value)selected.add(select.value);});
   selects.forEach((select)=>{[...select.options].forEach((option)=>{if(option.value)option.disabled=selected.has(option.value)&&select.value!==option.value;});previewMember(select.closest('[data-member-row]'));});
 };
 const addRow=()=>{rows.append(template.content.cloneNode(true));refreshSelections();};
 addRow();
 form.querySelector('[data-add-member]').addEventListener('click',addRow);
 form.addEventListener('change',(event)=>{if(event.target.matches('[data-member-select]'))refreshSelections();});
 form.addEventListener('click',(event)=>{
  const removeButton=event.target.closest('[data-remove-member]');
  if(!removeButton)return;
  const row=removeButton.closest('[data-member-row]');
  if(rows.children.length>1)row.remove();
   else row.querySelector('[data-member-select]').selectedIndex=0;
   refreshSelections();
 });
});
</script>
<?php foot();
