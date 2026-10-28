<?php
require __DIR__.'/../core/competition-api.php';
competition_api(function(array $user,int $camp):array {
    if($user['role']!=='main_staff'){http_response_code(403);return ['error'=>'เฉพาะสตาฟหลักเท่านั้นที่สุ่มได้'];}
    $game=(new RandomizationService())->number($_POST['game_id']??null,1,PHP_INT_MAX);
    $draft=(new CompetitionService())->preview($user,$camp,$game,$_POST);
    ob_start();require __DIR__.'/../views/competition-preview.php';$html=ob_get_clean();
    return ['draft_id'=>$draft['id'],'html'=>$html];
});
