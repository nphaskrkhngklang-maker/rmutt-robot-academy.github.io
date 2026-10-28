<?php
declare(strict_types=1);
class ScoringController {
    public function index(array $user):void {
        $camp=operation_camp($user);
        render('scoring',['title'=>'บันทึกคะแนนสนาม','user'=>$user,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'rows'=>$camp?(new ScoringService())->queue($user,(int)$camp['id']):[]]);
    }
    public function sheet(array $user,bool $edit):void {
        $repo=new ScoringService();$id=record_id($_GET['id']??null);$match=$repo->match($id);
        if(!$match||!$repo->canScore($user,(int)$match['camp_id'],(int)$match['field_id']))abort_request(403,'ไม่มีสิทธิ์เข้าถึง','กรุณาเลือกแมตช์ของสนามที่ได้รับมอบหมาย');
        $errors=[];$submitted=[];
        $json=str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            if($json){
                $raw=file_get_contents('php://input',false,null,0,1048577);
                $decoded=strlen($raw)<=1048576?json_decode($raw,true):null;
                if(!is_array($decoded)){http_response_code(400);header('Content-Type: application/json');echo json_encode(['error'=>'ข้อมูลไม่ถูกต้อง']);return;}
                $_POST=$decoded;
            }
            verify_csrf();$submitted=is_array($_POST['scores']??null)?$_POST['scores']:[];
            try{
                $repo->save($user,$id,$_POST);
                if($json){header('Content-Type: application/json');echo json_encode(['url'=>url('score-sheet',['id'=>$id])]);return;}
                flash('บันทึกคะแนนแล้ว');redirect('score-sheet',['id'=>$id]);
            }catch(DomainException $e){
                if($json){http_response_code(422);header('Content-Type: application/json');echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);return;}
                $errors[]=$e->getMessage();
            }
        }
        render('score-sheet',['title'=>$edit?'กรอกคะแนน':'ใบบันทึกคะแนน','user'=>$user,'match'=>$match,'edit'=>$edit,'errors'=>$errors,'submitted'=>$submitted]);
    }
}
