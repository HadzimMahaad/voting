<?php require 'app.php';need();if(admin()){header('Location: admin.php');exit;}if(!voter()){header('Location: voter-auth.php');exit;}$voterToken=voterToken();head('Vote');
$els=q("SELECT * FROM elections WHERE status<>'draft' ORDER BY id DESC")->fetchAll();
if(!$els)echo "<div class='alert alert-info'>No elections available yet.</div>";
foreach($els as $el){
 $done=q('SELECT 1 FROM voted WHERE election_id=? AND user_id=? UNION ALL SELECT 1 FROM anonymous_voted WHERE election_id=? AND voter_token=? LIMIT 1',[$el['id'],me(),$el['id'],$voterToken])->fetch();?>
<div class="card mb-3"><div class="card-body">
 <div class="d-flex justify-content-between align-items-start mb-3"><h5 class="mb-0"><?=e($el['title'])?></h5><?=badge($el['status'])?></div>
 <?php if($el['status']==='closed'):results($el['id']);
 elseif($done):?><div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill"></i> Your vote has been recorded. Results appear when the election closes.</div>
 <?php else:?><form method="post" action="vote.php"><?=T()?><input type="hidden" name="election" value="<?=$el['id']?>">
  <div class="list-group mb-3">
    <?php foreach(q('SELECT c.id,c.name,c.image_path,o.name organization_name,o.logo_path organization_logo FROM candidates c LEFT JOIN organizations o ON o.id=c.organization_id WHERE c.election_id=? ORDER BY c.id',[$el['id']]) as $c):?>
     <label class="list-group-item candidate d-flex align-items-center gap-3"><input class="form-check-input flex-shrink-0" type="radio" name="candidate" value="<?=$c['id']?>" required>
        <?php if($c['image_path']):?><img src="<?=e($c['image_path'])?>" alt="<?=e($c['name'])?>" width="64" height="64" class="rounded" style="object-fit:cover"><?php else:?><div class="rounded bg-light d-grid place-items-center" style="width:64px;height:64px"><i class="bi bi-person text-secondary"></i></div><?php endif?>
        <span class="flex-grow-1"><span class="form-check-label d-block fw-semibold"><?=e($c['name'])?></span><small class="text-muted d-flex align-items-center gap-2">
         <?php if($c['organization_logo']):?><img src="<?=e($c['organization_logo'])?>" alt="" width="24" height="24" style="object-fit:contain"><?php endif?><?=e($c['organization_name']??'No organization')?>
        </small></span>
     </label>
  <?php endforeach?></div>
  <button class="btn btn-success w-100 w-md-auto"><i class="bi bi-check2-square"></i> Cast vote</button></form>
 <?php endif?></div></div>
<?php }foot();
