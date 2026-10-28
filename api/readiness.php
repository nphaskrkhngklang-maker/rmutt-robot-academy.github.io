<?php
require __DIR__.'/../core/competition-api.php';
competition_api(function(array $user,int $camp):array {
    (new ReadinessService())->save($user,$camp,$_POST);
    return ['ok'=>true];
});
