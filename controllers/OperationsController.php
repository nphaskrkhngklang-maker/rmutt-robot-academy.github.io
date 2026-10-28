<?php
declare(strict_types=1);

class OperationsController
{
    public function index(array $user,string $module): void {
        $camp=operation_camp($user); $repo=new Operations(); $errors=[];
        $titles=['teams'=>'ทีมหลักและทีมย่อย','duties'=>'หน้าที่สตาฟ','equipment'=>'เช็กลิสต์อุปกรณ์','timeline'=>'Timeline'];
        $data=[];
        $editId=isset($_GET['edit'])?record_id($_GET['edit']):null;
        $editModule=$module==='teams' && ($_GET['kind']??'')==='group'?'groups':$module;
        if ($editId) {
            require_main($user);
            $data=$camp?$repo->find($editModule,$editId,(int)$camp['id']):false;
            if (!$data) abort_request(404,'ไม่พบข้อมูล','รายการนี้ไม่อยู่ในค่ายที่เลือก');
            if ($module==='timeline') $data['task_time']=substr($data['task_time'],0,5);
        }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            verify_csrf();
            if (!$camp) abort_request(400,'ยังไม่มีค่าย','กรุณาสร้างหรือเลือกค่ายก่อน');
            $mutation=$module==='teams' && input('kind')==='group'?'groups':$module;
            $data=$_POST;
            try {
                if (input('action')==='assign' && input('form_complete')!=='1') throw new DomainException('ข้อมูลการเลือกนักเรียนมาไม่ครบ กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
                (new OperationsService())->run($user,(int)$camp['id'],$mutation,$_POST);
                flash('บันทึกข้อมูลแล้ว'); redirect($module,['camp_id'=>$camp['id']]);
            } catch (DomainException $e) { $errors[]=$e->getMessage(); }
            catch (PDOException $e) { if (($e->errorInfo[1]??0)===1062) $errors[]='ชื่อทีมหลักหรือทีมนี้มีอยู่ในค่ายแล้ว'; else throw $e; }
            if (input('action')==='save') { $editModule=$mutation; $editId=input('id')!==''?record_id(input('id')):null; }
        }
        $common=['title'=>$titles[$module],'user'=>$user,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'module'=>$module,'errors'=>$errors,'data'=>$data,'editId'=>$editId,'editModule'=>$editModule];
        $campId=$camp?(int)$camp['id']:0;
        $common+=['groups'=>$repo->rows('groups',$campId),'teams'=>$repo->rows('teams',$campId),'people'=>$repo->people($campId),'rows'=>$repo->rows($module,$campId),'locked'=>$repo->locked($campId),'students'=>$module==='teams'?$repo->students($campId):[]];
        render('operations',$common);
    }
}
