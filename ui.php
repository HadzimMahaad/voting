<?php
function head($t,$wide=false){$mainWidth=$wide?'1100px':'760px';$siteLogo=null;try{$siteLogo=q("SELECT setting_value FROM app_settings WHERE setting_key='site_logo'")->fetchColumn();}catch(Throwable $ignored){}?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($t)?> · E-Voting</title>
<?php if(staffPathRequest()):?><base href="<?=e(appBasePath())?>"><?php endif?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{background:#f4f6f8}.navbar{background:#0b6e4f}.navbar-brand{display:inline-flex;align-items:center;gap:.5rem}.brand-logo{width:40px;height:40px;object-fit:contain;background:#fff;border-radius:4px;padding:2px}.portal-logo{display:block;width:88px;height:106px;object-fit:contain;background:#fff;border-radius:4px;padding:3px;margin:0 auto 16px}.candidate{cursor:pointer}.form-check-input:checked~.form-check-label{font-weight:600}.card{border:0;box-shadow:0 1px 4px #0001}</style></head><body>
<nav class="navbar navbar-expand-md navbar-dark mb-4"><div class="container">
<a class="navbar-brand" href="<?=admin()?'admin.php':'index.php'?>"><?php if($siteLogo&&is_file(__DIR__.'/'.$siteLogo)):?><img class="brand-logo" src="<?=e($siteLogo)?>" alt="Portal logo"><?php elseif(is_file(__DIR__.'/assets/spr.png')):?><img class="brand-logo" src="assets/spr.png" alt="SPR logo"><?php else:?><i class="bi bi-check2-square"></i><?php endif?> E-Voting</a>
<?php if(me()):?><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nv"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="nv"><ul class="navbar-nav ms-auto">
<?php if(admin()):?><li class="nav-item"><a class="nav-link" href="admin.php">Election</a></li><li class="nav-item"><a class="nav-link" href="members.php">Members</a></li><li class="nav-item"><a class="nav-link" href="organizations.php">Organizations</a></li><li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li><?php if(superadmin()):?><li class="nav-item"><a class="nav-link" href="staff-requests.php">Staff requests</a></li><?php endif?><?php else:?><li class="nav-item"><a class="nav-link" href="index.php">Vote</a></li><?php endif?>
<li class="nav-item"><a class="nav-link" href="auth.php?out=1">Logout</a></li></ul></div><?php else:?><ul class="navbar-nav ms-auto"><li class="nav-item dropdown">
 <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-box-arrow-in-right"></i> Login SPR</a>
 <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="auth.php/admin">Staff SPR</a></li><li><a class="dropdown-item" href="auth.php">User Vote</a></li></ul>
</li></ul><?php endif?>
</div></nav><main class="container" style="max-width:<?=$mainWidth?>">
<?php }
function foot(){echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';}
function badge($s){$c=['draft'=>'secondary','open'=>'success','closed'=>'dark'][$s];return "<span class='badge text-bg-$c text-uppercase'>$s</span>";}
function results($id){
 $rows=q('SELECT c.name,c.image_path,o.name organization_name,o.logo_path organization_logo,COUNT(b.id) n FROM candidates c LEFT JOIN organizations o ON o.id=c.organization_id LEFT JOIN ballots b ON b.candidate_id=c.id WHERE c.election_id=? GROUP BY c.id,o.id ORDER BY n DESC',[$id])->fetchAll();
 $t=array_sum(array_column($rows,'n'));
 foreach($rows as $i=>$r){$p=$t?round($r['n']*100/$t):0;$cls=$i===0&&$t?'bg-success':'bg-secondary';
    echo "<div class='mb-3'><div class='d-flex justify-content-between align-items-center'><span class='d-flex align-items-center gap-2'>";
    if($r['image_path'])echo "<img src='".e($r['image_path'])."' alt='".e($r['name'])."' width='48' height='48' class='rounded' style='object-fit:cover'>";
    echo "<span>".e($r['name']).($i===0&&$t?" <i class='bi bi-trophy-fill text-warning'></i>":"")."<small class='d-flex align-items-center gap-2 text-muted'>";
    if($r['organization_logo'])echo "<img src='".e($r['organization_logo'])."' alt='' width='22' height='22' style='object-fit:contain'>";
    echo e($r['organization_name']??'No organization')."</small></span></span><span class='text-muted'>{$r['n']} votes · $p%</span></div><div class='progress' style='height:18px'><div class='progress-bar $cls' style='width:$p%'></div></div></div>";}
 echo "<small class='text-muted'>Total votes: $t</small>";}
