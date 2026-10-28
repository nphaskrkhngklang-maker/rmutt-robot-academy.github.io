<?php
declare(strict_types=1);
class ContentController {
    public function index(array $user,string $kind):void {
        $camp=operation_camp($user);$repo=new ContentService();$errors=[];$data=[];$editId=null;
        if(isset($_GET['edit'])){
            require_main($user);$editId=record_id($_GET['edit']);$data=$repo->find($kind,$editId);
            if(!$data||!$camp||(int)$data['camp_id']!==(int)$camp['id'])abort_request(404,'ไม่พบรายการ','กรุณาเลือกรายการในค่ายนี้');
            if($kind==='games')$data['criteria']=$repo->criteria($editId);
        }
        if($_SERVER['REQUEST_METHOD']==='POST'){
            require_main($user);verify_csrf();$data=$_POST;
            if(!$camp)abort_request(400,'ยังไม่มีค่าย','กรุณาเลือกค่ายก่อน');
            try{
                $repo->save($user,(int)$camp['id'],$kind,$data,$_FILES['attachment']??null);
                flash('บันทึกข้อมูลแล้ว');redirect($kind,['camp_id'=>$camp['id']]);
            }catch(DomainException $e){$errors[]=$e->getMessage();$editId=empty($data['id'])?null:record_id($data['id']);}
        }
        $rows=$camp?$repo->rows($kind,(int)$camp['id']):[];
        if($kind==='media')$rows=array_values(array_filter($rows,fn($r)=>$repo->visible($r,$user)));
        if($kind==='games')foreach($rows as &$row)$row['criteria']=$repo->criteria((int)$row['id']);
        render('content',['user'=>$user,'title'=>$kind==='media'?'จัดการสื่อการเรียนรู้':'เกมและเกณฑ์คะแนน','kind'=>$kind,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'errors'=>$errors,'data'=>$data,'editId'=>$editId,'rows'=>$rows]);
    }
    public function file():never {
        $repo=new ContentService();$row=$repo->find('media',record_id($_GET['id']??null));
        if(!$row||!$repo->visible($row,current_user())||!$row['file_key'])abort_request(404,'ไม่พบไฟล์','ไฟล์นี้ไม่มีอยู่หรือไม่ได้เปิดเผย');
        if(!preg_match('/^[a-f0-9]{48}\.bin$/',$row['file_key']))abort_request(404,'ไม่พบไฟล์','ไฟล์ไม่ถูกต้อง');
        $path=__DIR__.'/../uploads/learning-media/'.$row['file_key'];
        if(!is_file($path))abort_request(404,'ไม่พบไฟล์','ไฟล์ถูกย้ายหรือไม่มีอยู่');
        $ext=['application/pdf'=>'pdf','image/png'=>'png','image/jpeg'=>'jpg','text/plain'=>'txt'][$row['file_mime']]??null;
        if(!$ext)abort_request(404,'ไม่พบไฟล์','ชนิดไฟล์ไม่รองรับ');
        header('Content-Type: '.$row['file_mime']);header('Content-Length: '.filesize($path));
        header('Content-Disposition: attachment; filename="learning-media-'.$row['id'].'.'.$ext.'"');
        header("Content-Security-Policy: default-src 'none'; sandbox");
        readfile($path);exit;
    }
}
