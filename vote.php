<?php require 'app.php';need();if(admin()){header('Location: admin.php');exit;}if(!voter()){header('Location: voter-auth.php');exit;}check();

$el=(int)$_POST['election'];$c=(int)$_POST['candidate'];
$token=voterToken();
 $token=voterToken();

$ok=q("SELECT 1 FROM elections e JOIN candidates c ON c.election_id=e.id WHERE e.id=? AND c.id=? AND e.status='open'",[$el,$c])->fetch();

if(!$ok)die('Invalid or closed election');
if(q('SELECT 1 FROM anonymous_voted WHERE election_id=? AND voter_token=?',[$el,$token])->fetch())die('A vote was already recorded from this browser.');
try{$pdo->beginTransaction();
 q('INSERT INTO voted(election_id,user_id) VALUES(?,?)',[$el,me()]);
 q('INSERT INTO ballots(election_id,candidate_id) VALUES(?,?)',[$el,$c]);
 $pdo->commit();}catch(Exception $x){$pdo->rollBack();die('You have already voted.');}
header('Location: index.php');
