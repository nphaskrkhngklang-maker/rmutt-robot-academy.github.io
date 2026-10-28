<?php
declare(strict_types=1);
class EvaluationController {
    public function publicForm():void {
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
        $this->board($camp,current_user(),false);
    }
    private function board(array|false|null $camp,?array $user,bool $staff):void {
        $errors=[];$data=[];$posts=[];$total=0;$page=max(1,filter_var($_GET['p']??1,FILTER_VALIDATE_INT)?:1);
        $_SESSION['board_author']??=bin2hex(random_bytes(32));
        $_SESSION['board_token']??=bin2hex(random_bytes(32));
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();$data=$_POST;
            try{
                if(!$camp)throw new DomainException('ยังไม่มีบอร์ด');
                if(!hash_equals($_SESSION['board_token'],input('submission_token')))throw new DomainException('โพสต์นี้ส่งแล้วหรือหมดอายุ กรุณาลองใหม่');
                (new BoardService())->save((int)$camp['id'],$data,$_SESSION['board_token'],$_SESSION['board_author']);
                $_SESSION['board_token']=bin2hex(random_bytes(32));flash('เพิ่มโพสต์บนบอร์ดแล้ว');redirect($staff?'evaluation-results':'evaluation',['camp_id'=>$camp['id']]);
            }catch(DomainException $e){$errors[]=$e->getMessage();}
        }
        if($camp){
            $q=db()->prepare('SELECT COUNT(*) FROM board_posts WHERE camp_id=?');$q->execute([$camp['id']]);$total=(int)$q->fetchColumn();$page=min($page,max(1,(int)ceil($total/24)));
            $q=db()->prepare('SELECT id,author_name,title,body,tone,created_at FROM board_posts WHERE camp_id=? ORDER BY id DESC LIMIT 24 OFFSET '.(($page-1)*24));$q->execute([$camp['id']]);$posts=$q->fetchAll();
        }
        $title='บอร์ดความประทับใจ';$token=$_SESSION['board_token'];
        if($user)render('board',compact('title','user','camp','posts','total','page','errors','data','token','staff'));
        else require __DIR__.'/../views/board-public.php';
    }
    public function results(array $user):void {
        require_main($user);
        $this->board(operation_camp($user),$user,true);
    }
}
