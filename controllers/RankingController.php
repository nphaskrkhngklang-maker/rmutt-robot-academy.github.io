<?php
declare(strict_types=1);
class RankingController {
    public function index():void {
        $repo=new RankingService();$user=current_user();$errors=[];
        $studentViewer=current_student();
        if($studentViewer) {
            $query=db()->prepare("SELECT id,name FROM camps WHERE id=? AND status<>'cancelled'");$query->execute([$studentViewer['camp_id']]);$camp=$query->fetch();
        } elseif(isset($_GET['camp_id'])) {
            $requestedCamp=record_id($_GET['camp_id']);
            $query=db()->prepare("SELECT id,name FROM camps WHERE id=? AND status<>'cancelled'");$query->execute([$requestedCamp]);$camp=$query->fetch();
            if(!$camp)abort_request(404,'ไม่พบค่าย','ไม่พบข้อมูลที่ต้องการ');
        } else {
            $camp=db()->query("SELECT id,name FROM camps WHERE status<>'cancelled' ORDER BY start_date DESC,id DESC LIMIT 1")->fetch() ?: null;
        }
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $user=require_user();require_main($user);verify_csrf();
            if(!$camp)abort_request(400,'ยังไม่มีค่าย','กรุณาเลือกค่ายก่อน');
            try{$repo->setMode($user,(int)$camp['id'],input('mode'));redirect('rankings',['camp_id'=>$camp['id']]);}catch(DomainException $e){$errors[]=$e->getMessage();}
        }
        $games=$camp?$repo->games((int)$camp['id']):[];$game=null;$result=null;
        $gameId=isset($_GET['game_id'])?record_id($_GET['game_id']):null;
        if($gameId){foreach($games as $g)if((int)$g['id']===$gameId)$game=$g;if(!$game)abort_request(404,'ไม่พบเกม','เกมนี้ยังไม่มีผลสำหรับแสดง');$result=$repo->game((int)$camp['id'],$gameId);}
        $overall=$camp?$repo->overall((int)$camp['id']):null;
        require __DIR__.'/../views/rankings.php';
    }
}
