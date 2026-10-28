<?php
declare(strict_types=1);
class DeleteController {
    public function index(array $user):void {
        require_main($user);$kind=is_string($_GET['kind']??null)?$_GET['kind']:'';$id=record_id($_GET['id']??null);$service=new DeleteService();$errors=[];
        try{$info=$service->preview($user,$kind,$id);}catch(DomainException $e){abort_request(404,'ไม่พบรายการ',$e->getMessage());}
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();try{
                if(input('confirm')!=='delete')throw new DomainException('กรุณายืนยันการลบ');
                $info=$service->remove($user,$kind,$id,input('fingerprint'));flash('ลบ'.$info['label'].'แล้ว');redirect($info['back'],$info['camp']?['camp_id'=>$info['camp']]:[]);
            }catch(DomainException $e){$errors[]=$e->getMessage();$info=$service->preview($user,$kind,$id);}
        }
        render('delete',['title'=>'ยืนยันการลบ','user'=>$user,'info'=>$info,'errors'=>$errors,'fingerprint'=>$service->fingerprint($info)]);
    }
}
