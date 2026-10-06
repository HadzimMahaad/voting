<?php 
require 'app.php';need();if(!admin())die('Admins only');
function adminReturnUrl(){
 if(($_POST['do']??'')==='cand'&&($_POST['return_to']??'')==='members.php')return 'members.php?election='.(int)($_POST['id']??0);
 if(($_POST['return_to']??'')==='election-view.php')return 'election-view.php?id='.(int)($_POST['election_id']??$_POST['id']??0);
 if(($_POST['return_to']??'')==='election-edit.php')return 'election-edit.php?id='.(int)($_POST['id']??0);
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
  case 'edit_election':
   $title=trim($_POST['title']??'');
   if($title==='')adminError('Election title cannot be blank.');
   q('UPDATE elections SET title=? WHERE id=?',[$title,$id]);
   $_SESSION['admin_notice']='Election title updated.';
   break;
  case 'delete_election':
   if(!q('SELECT id FROM elections WHERE id=?',[$id])->fetch())adminError('Election not found.');
   try{
    $pdo->beginTransaction();
    q('DELETE FROM ballots WHERE election_id=?',[$id]);
    q('DELETE FROM voted WHERE election_id=?',[$id]);
    q('DELETE FROM elections WHERE id=?',[$id]);
    $pdo->commit();
   }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();adminError('Unable to delete this election. Please try again.');}
   $_SESSION['admin_notice']='Election and its voting records were deleted. Member profiles were kept.';
   break;
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
   case 'remove_cand':
    $electionId=(int)($_POST['election_id']??0);$candidateId=(int)($_POST['candidate_id']??0);
    $candidate=q("SELECT c.image_path FROM candidates c JOIN elections e ON e.id=c.election_id WHERE c.id=? AND e.id=? AND e.status='draft'",[$candidateId,$electionId])->fetch();
    if(!$candidate)adminError('Candidates can only be removed while the election is in draft.');
    q('DELETE FROM candidates WHERE id=? AND election_id=?',[$candidateId,$electionId]);
    if($candidate['image_path'])removeMemberImage($candidate['image_path']);
    $_SESSION['admin_notice']='Candidate removed from this election.';
    break;
   case 'update_cand':
     $electionId=(int)($_POST['election_id']??0);$candidateId=(int)($_POST['candidate_id']??0);
  $candidate=q("SELECT c.name,c.image_path,c.organization_id FROM candidates c JOIN elections e ON e.id=c.election_id WHERE c.id=? AND e.id=? AND e.status='draft'",[$candidateId,$electionId])->fetch();
  if(!$candidate)adminError('Members can only be edited while the election is in draft.');
     $name=$candidate['name'];$organizationId=$candidate['organization_id'];
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
head('Admin');
$elections=q('SELECT e.id,e.title,e.status,COUNT(DISTINCT c.id) member_count,COUNT(DISTINCT b.id) vote_count FROM elections e LEFT JOIN candidates c ON c.election_id=e.id LEFT JOIN ballots b ON b.election_id=e.id GROUP BY e.id ORDER BY e.id DESC')->fetchAll();
?>
<?php if(!empty($_SESSION['admin_error'])):?><div class="alert alert-danger"><?=e($_SESSION['admin_error'])?></div><?php unset($_SESSION['admin_error']);endif?>
<?php if(!empty($_SESSION['admin_notice'])):?><div class="alert alert-success"><?=e($_SESSION['admin_notice'])?></div><?php unset($_SESSION['admin_notice']);endif?>
<div class="d-flex justify-content-between align-items-end gap-3 mb-3"><div><h1 class="h3 mb-1">Elections</h1><div class="text-muted">Manage elections and results</div></div><span class="badge text-bg-secondary"><?=count($elections)?> total</span></div>
<form class="card card-body mb-4" method="post"><?=T()?><input type="hidden" name="do" value="new">
 <label class="form-label" for="new-election-title">New election</label><div class="input-group"><input class="form-control" id="new-election-title" name="title" placeholder="Election title" required><button class="btn btn-success"><i class="bi bi-plus-lg"></i> Create draft</button></div></form>
<?php if(!$elections):?><div class="alert alert-info">No elections have been created yet.</div><?php else:?>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
 <thead><tr><th>Election</th><th>Status</th><th>Members</th><th>Votes</th><th class="text-end">Actions</th></tr></thead><tbody>
 <?php foreach($elections as $election):?><tr>
  <td class="fw-semibold"><?=e($election['title'])?></td><td><?=badge($election['status'])?></td><td><?=number_format($election['member_count'])?></td><td><?=number_format($election['vote_count'])?></td>
  <td><div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary btn-sm" href="election-view.php?id=<?=$election['id']?>"><i class="bi bi-eye"></i> View</a><a class="btn btn-outline-success btn-sm" href="election-edit.php?id=<?=$election['id']?>"><i class="bi bi-pencil"></i> Edit</a>
   <form method="post" onsubmit="return confirm('Delete this election and all its vote records? Member profiles will be kept.');"><?=T()?>
    <input type="hidden" name="do" value="delete_election"><input type="hidden" name="id" value="<?=$election['id']?>"><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Delete</button>
   </form></div></td>
 </tr><?php endforeach?>
 </tbody></table></div></div><?php endif;foot();
