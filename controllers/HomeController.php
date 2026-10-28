<?php
 declare(strict_types=1);
class HomeController {
    public function settings(array $user): void {
        require_main($user);
        $camp=operation_camp($user);
        render('camp-settings',['title'=>'ข้อมูลค่าย','user'=>$user,'camp'=>$camp]);
    }
}
