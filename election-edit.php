<?php require 'app.php';need();if(!admin())die('Admins only');
$id=(int)($_GET['id']??0);$election=q('SELECT id,title,status FROM elections WHERE id=?',[$id])->fetch();
if(!$election){http_response_code(404);die('Election not found.');}
head('Edit election');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Edit election</h1>
<a class="btn btn-outline-secondary btn-sm" href="election-view.php?id=<?=$id?>"><i class="bi bi-arrow-left"></i> Back to election</a></div>
<?php if(!empty($_SESSION['admin_error'])):?><div class="alert alert-danger"><?=e($_SESSION['admin_error'])?></div><?php unset($_SESSION['admin_error']);endif?>
<?php if(!empty($_SESSION['admin_notice'])):?><div class="alert alert-success"><?=e($_SESSION['admin_notice'])?></div><?php unset($_SESSION['admin_notice']);endif?>
<form class="card card-body" method="post" action="admin.php"><?=T()?>
 <input type="hidden" name="do" value="edit_election"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="return_to" value="election-edit.php">
 <div class="mb-3"><label class="form-label" for="election-title">Election title</label><input class="form-control" id="election-title" name="title" value="<?=e($election['title'])?>" maxlength="200" required></div>
 <div class="text-muted small mb-3">Status: <?=badge($election['status'])?></div>
 <button class="btn btn-success align-self-start"><i class="bi bi-save"></i> Save changes</button>
</form>
<?php foot();