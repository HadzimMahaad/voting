<?php require 'app.php';need();if(!admin())die('Admins only');
$id=(int)($_GET['id']??0);$election=q('SELECT id,title,status FROM elections WHERE id=?',[$id])->fetch();
if(!$election){http_response_code(404);die('Election not found.');}
$organizations=q('SELECT id,name,logo_path FROM organizations ORDER BY name')->fetchAll();
$members=q('SELECT c.id,c.name,c.organization_id,c.image_path,o.name organization_name,o.logo_path organization_logo FROM candidates c LEFT JOIN organizations o ON o.id=c.organization_id WHERE c.election_id=? ORDER BY c.id',[$id])->fetchAll();
$availableMembers=q('SELECT m.id,m.name,m.image_path,o.name organization_name,o.logo_path organization_logo FROM members m LEFT JOIN organizations o ON o.id=m.organization_id WHERE NOT EXISTS(SELECT 1 FROM candidates c WHERE c.election_id=? AND c.member_id=m.id) ORDER BY o.name,m.name',[$id])->fetchAll();
head('Election details',true);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
 <div><a class="small text-muted" href="admin.php"><i class="bi bi-arrow-left"></i> All elections</a><h1 class="h3 mb-1 mt-2"><?=e($election['title'])?></h1><?=badge($election['status'])?></div>
 <div class="d-flex gap-2"><a class="btn btn-outline-success btn-sm" href="election-edit.php?id=<?=$id?>"><i class="bi bi-pencil"></i> Edit election</a><?php if($election['status']==='closed'):?><a class="btn btn-outline-primary btn-sm" href="report.php?election=<?=$id?>"><i class="bi bi-file-earmark-arrow-down"></i> Generate report</a><?php endif?></div>
</div>
<?php if(!empty($_SESSION['admin_error'])):?><div class="alert alert-danger"><?=e($_SESSION['admin_error'])?></div><?php unset($_SESSION['admin_error']);endif?>
<?php if(!empty($_SESSION['admin_notice'])):?><div class="alert alert-success"><?=e($_SESSION['admin_notice'])?></div><?php unset($_SESSION['admin_notice']);endif?>
<section class="mb-4" aria-labelledby="candidate-heading">
 <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0" id="candidate-heading">Members / candidates</h2><span class="text-muted small"><?=count($members)?> total</span></div>
 <?php if(!$members):?><div class="alert alert-light border">No members have been added to this election.</div>
 <?php else:?><div class="list-group">
  <?php foreach($members as $member):?><div class="list-group-item">
   <div class="d-flex align-items-center gap-3">
    <?php if($member['image_path']):?><img src="<?=e($member['image_path'])?>" alt="<?=e($member['name'])?>" width="56" height="56" class="rounded" style="object-fit:cover"><?php else:?><span class="rounded bg-light d-flex align-items-center justify-content-center" style="width:56px;height:56px"><i class="bi bi-person text-secondary"></i></span><?php endif?>
    <div><div class="fw-semibold"><?=e($member['name'])?></div><div class="small text-muted d-flex align-items-center gap-2"><?php if($member['organization_logo']):?><img src="<?=e($member['organization_logo'])?>" alt="" width="22" height="22" style="object-fit:contain"><?php endif?><?=e($member['organization_name']??'No organization')?></div></div>
   </div>
   <?php if($election['status']==='draft'&&$organizations):?><form method="post" action="admin.php" enctype="multipart/form-data" class="row g-2 align-items-end mt-2"><?=T()?>
    <input type="hidden" name="do" value="update_cand"><input type="hidden" name="election_id" value="<?=$id?>"><input type="hidden" name="candidate_id" value="<?=$member['id']?>"><input type="hidden" name="return_to" value="election-view.php">
    <div class="col-md-3"><label class="form-label small">Member name</label><input class="form-control form-control-sm" name="name" value="<?=e($member['name'])?>" maxlength="120" readonly></div>
    <div class="col-md-3"><label class="form-label small">Organization</label><select class="form-select form-select-sm" name="organization_id" disabled><option value="">No organization</option><?php foreach($organizations as $organization):?><option value="<?=$organization['id']?>" <?=$member['organization_id']==$organization['id']?'selected':''?>><?=e($organization['name'])?></option><?php endforeach?></select></div>
    <div class="col"><label class="form-label small">Replace photo (uploads immediately)</label><input class="form-control form-control-sm" type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" onchange="this.form.requestSubmit()"></div>
   </form><?php endif?>
   <?php if($election['status']==='draft'):?><form method="post" action="admin.php" class="d-flex justify-content-end mt-2" onsubmit="return confirm('Remove this candidate from the draft election?');"><?=T()?>
    <input type="hidden" name="do" value="remove_cand"><input type="hidden" name="election_id" value="<?=$id?>"><input type="hidden" name="candidate_id" value="<?=$member['id']?>"><input type="hidden" name="return_to" value="election-view.php">
    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-person-x"></i> Remove candidate</button>
   </form><?php endif?>
  </div><?php endforeach?>
 </div><?php endif?>
</section>
<?php if($election['status']==='draft'):?>
 <section class="card card-body mb-4" aria-labelledby="add-members-heading">
  <h2 class="h5" id="add-members-heading">Add members from directory</h2>
  <?php if(!$availableMembers):?><p class="text-muted mb-0">All directory members are already in this election.</p>
  <?php else:?><form method="post" action="admin.php"><?=T()?>
   <input type="hidden" name="do" value="cand"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="return_to" value="election-view.php">
   <div class="row g-2 mb-3">
    <?php foreach($availableMembers as $member):?><div class="col-md-6"><label class="list-group-item h-100 d-flex align-items-center gap-2">
     <input class="form-check-input flex-shrink-0" type="checkbox" name="member_ids[]" value="<?=$member['id']?>">
     <?php if($member['image_path']):?><img src="<?=e($member['image_path'])?>" alt="" width="44" height="44" class="rounded" style="object-fit:cover"><?php endif?>
     <span class="flex-grow-1"><span class="d-block fw-semibold"><?=e($member['name'])?></span><small class="text-muted d-flex align-items-center gap-1"><?php if($member['organization_logo']):?><img src="<?=e($member['organization_logo'])?>" alt="" width="18" height="18" style="object-fit:contain"><?php endif?><?=e($member['organization_name']??'No organization')?></small></span>
    </label></div><?php endforeach?>
   </div><button class="btn btn-success"><i class="bi bi-person-plus"></i> Add selected members</button>
  </form><?php endif?>
 </section>
<?php endif?>
<?php if($election['status']==='open'):?><div class="d-flex gap-2 mb-4">
 <form method="post" action="admin.php"><?=T()?><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="s" value="draft"><input type="hidden" name="return_to" value="election-view.php"><button class="btn btn-outline-secondary">Save as draft</button></form>
 <form method="post" action="admin.php"><?=T()?><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="s" value="closed"><input type="hidden" name="return_to" value="election-view.php"><button class="btn btn-dark">End voting &amp; publish results</button></form>
</div><?php elseif($election['status']==='draft'):?><form method="post" action="admin.php" class="mb-4"><?=T()?>
 <input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="s" value="open"><input type="hidden" name="return_to" value="election-view.php"><button class="btn btn-success">Open voting</button>
</form><?php endif?>
<?php if($election['status']!=='draft'):?><section aria-labelledby="results-heading"><h2 class="h5 mb-3" id="results-heading">Live tally</h2><?php results($id);?></section><?php endif;foot();
