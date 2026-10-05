<?php require 'app.php';need();if(!superadmin()){http_response_code(403);die('Superadmins only');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check();$id=(int)($_POST['id']??0);
 if(($_POST['do']??'')==='approve')q('UPDATE users SET approved=1 WHERE id=? AND is_staff=1 AND is_admin=0 AND approved=0',[$id]);
 elseif(($_POST['do']??'')==='reject')q('DELETE FROM users WHERE id=? AND is_staff=1 AND is_admin=0 AND approved=0',[$id]);
 header('Location: staff-requests.php');exit;
}
$requests=q('SELECT id,name,email FROM users WHERE is_staff=1 AND is_admin=0 AND approved=0 ORDER BY id')->fetchAll();
head('Staff requests');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Staff requests</h1><span class="badge text-bg-secondary"><?=count($requests)?> pending</span></div>
<?php if(!$requests):?><div class="alert alert-info">No staff registrations are waiting for approval.</div>
<?php else:?><div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
 <thead><tr><th>Name</th><th>Email</th><th class="text-end">Review</th></tr></thead><tbody>
 <?php foreach($requests as $request):?><tr><td><?=e($request['name'])?></td><td><?=e($request['email'])?></td><td class="text-end"><div class="d-flex justify-content-end gap-2">
  <form method="post"><?=T()?><input type="hidden" name="id" value="<?=$request['id']?>"><input type="hidden" name="do" value="approve"><button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Approve</button></form>
  <form method="post"><?=T()?><input type="hidden" name="id" value="<?=$request['id']?>"><input type="hidden" name="do" value="reject"><button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg"></i> Reject</button></form>
 </div></td></tr><?php endforeach?>
 </tbody></table></div></div><?php endif;foot();