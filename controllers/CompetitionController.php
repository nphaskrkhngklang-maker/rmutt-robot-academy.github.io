<?php
declare(strict_types=1);
class CompetitionController {
    public function index(array $user):void {
        $camp=operation_camp($user);$repo=new CompetitionService();$games=$camp?(new ContentService())->rows('games',(int)$camp['id']):[];$game=null;$errors=[];
        $gameId=isset($_GET['game_id'])?record_id($_GET['game_id']):($games[0]['id']??null);
        foreach($games as $item)if((int)$item['id']===(int)$gameId)$game=$item;
        if($gameId&&!$game)abort_request(404,'ไม่พบเกม','กรุณาเลือกเกมในค่ายนี้');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            require_main($user);verify_csrf();
            if(!$camp||!$game)abort_request(400,'ยังไม่มีเกม','กรุณาเลือกค่ายและเกมก่อน');
            try{
                switch(input('action')){
                    case 'randomize':$repo->preview($user,(int)$camp['id'],(int)$game['id'],$_POST);break;
                    case 'confirm':$repo->confirm($user,(int)$camp['id'],(int)$game['id'],$_POST);flash('ยืนยันตารางแข่งขันแล้ว');break;
                    case 'status':$repo->status($user,(int)$camp['id'],$_POST);flash('อัปเดตสถานะแมตช์แล้ว');break;
                    default:throw new DomainException('คำสั่งไม่ถูกต้อง');
                }
                redirect('competition',['camp_id'=>$camp['id'],'game_id'=>$game['id']]);
            }catch(DomainException $e){$errors[]=$e->getMessage();}
        }
        $draft=$camp&&$game?$repo->draft((int)$camp['id'],(int)$game['id']):null;
        render('competition',['title'=>'จัดการแข่งขัน','user'=>$user,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'games'=>$games,'game'=>$game,'errors'=>$errors,'draft'=>$draft,'rounds'=>$camp&&$game?$repo->rounds((int)$camp['id'],(int)$game['id']):[]]);
    }
    public function readiness(array $user):void {
        $camp=operation_camp($user);$repo=new ReadinessService();$errors=[];
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();if(!$camp)abort_request(400,'ยังไม่มีค่าย','กรุณาเลือกค่ายก่อน');
            try{$repo->save($user,(int)$camp['id'],$_POST);flash('อัปเดตความพร้อมแล้ว');redirect('readiness',['camp_id'=>$camp['id']]);}
            catch(DomainException $e){$errors[]=$e->getMessage();}
        }
        render('readiness',['title'=>'ความพร้อมทีม','user'=>$user,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'errors'=>$errors,'repo'=>$repo,'rows'=>$camp?$repo->rows((int)$camp['id']):[]]);
    }
}
