<?php
declare(strict_types=1);
class ContentService {
    public function table(string $kind): string { return match($kind){'media'=>'learning_media','games'=>'games',default=>throw new DomainException('ประเภทข้อมูลไม่ถูกต้อง')}; }
    public function rows(string $kind,int $camp): array {$q=db()->prepare('SELECT * FROM '.$this->table($kind).' WHERE camp_id=? ORDER BY id DESC');$q->execute([$camp]);return $q->fetchAll();}
    public function find(string $kind,int $id): array|false {$q=db()->prepare('SELECT * FROM '.$this->table($kind).' WHERE id=?');$q->execute([$id]);return $q->fetch();}
    public function criteria(int $game): array {$q=db()->prepare('SELECT * FROM score_criteria WHERE game_id=? ORDER BY position,id');$q->execute([$game]);return $q->fetchAll();}
    public function visible(array $row,?array $user): bool {
        return $row['visibility']==='public' || ($user && ($user['role']==='main_staff' || ($row['visibility']==='staff' && (new Camp())->findFor((int)$row['camp_id'],$user))));
    }
    private function text(array $d,string $key,int $max,bool $required=true):string {
        $v=$d[$key]??''; if(!is_string($v)||mb_strlen(trim($v))>$max||($required&&trim($v)===''))throw new DomainException('กรุณากรอก '.$key.' ให้ถูกต้อง (ไม่เกิน '.$max.' ตัวอักษร)');
        return trim($v);
    }
    private function integer(mixed $v,int $min,int $max):int {
        if(!is_scalar($v)||filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]])===false)throw new DomainException('จำนวนรอบ เวลา หรือเวอร์ชันข้อมูลไม่ถูกต้อง');
        return (int)$v;
    }
    private function choice(array $d,string $key,array $choices):string {
        if(!is_string($d[$key]??null)||!in_array($d[$key],$choices,true))throw new DomainException('กรุณาเลือก '.$key.' ให้ถูกต้อง');return $d[$key];
    }
    public function save(array $user,int $camp,string $kind,array $d,?array $file=null):int {
        if($user['role']!=='main_staff'||!(new Camp())->findFor($camp,$user))throw new DomainException('ไม่มีสิทธิ์แก้ไขข้อมูลค่ายนี้');
        $table=$this->table($kind);$id=empty($d['id'])?null:$this->integer($d['id'],1,PHP_INT_MAX);
        $version=$this->integer($d['version']??0,0,PHP_INT_MAX);
        $old=$id?$this->find($kind,$id):null;
        if($id&&(!$old||(int)$old['camp_id']!==$camp))throw new DomainException('ไม่พบรายการในค่ายนี้');
        $criteria=[];$newFile=null;
        if($kind==='media'){
            $values=['title'=>$this->text($d,'title',200),'category'=>$this->text($d,'category',100),'content'=>$this->text($d,'content',100000,false),'external_url'=>$this->text($d,'external_url',2000,false),'visibility'=>$this->choice($d,'visibility',['public','staff','draft'])];
            if($values['external_url']!==''&&(!filter_var($values['external_url'],FILTER_VALIDATE_URL)||!in_array(strtolower(parse_url($values['external_url'],PHP_URL_SCHEME)?:''),['http','https'],true)))throw new DomainException('ลิงก์ต้องเป็น http หรือ https ที่ถูกต้อง');
            $values['file_key']=$old['file_key']??null;$values['file_mime']=$old['file_mime']??null;
            if(($d['remove_file']??'')==='1'){$values['file_key']=null;$values['file_mime']=null;}
            if($file&&($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                if(($file['error']??null)!==UPLOAD_ERR_OK)throw new DomainException('อัปโหลดไม่สำเร็จ กรุณาเลือกไฟล์ใหม่ (สูงสุด 5 MB)');
                $tmp=$file['tmp_name']??'';
                if(!is_string($tmp)||!is_uploaded_file($tmp)||filesize($tmp)>5*1024*1024||filesize($tmp)===0)throw new DomainException('ไฟล์ไม่ถูกต้องหรือมีขนาดเกิน 5 MB');
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $extensions=['application/pdf'=>['pdf'],'image/png'=>['png'],'image/jpeg'=>['jpg','jpeg'],'text/plain'=>['txt']];
                $ext=strtolower(pathinfo(is_string($file['name']??null)?$file['name']:'',PATHINFO_EXTENSION));
                if(!isset($extensions[$mime])||!in_array($ext,$extensions[$mime],true))throw new DomainException('รองรับเฉพาะ PDF, PNG, JPG และ TXT ที่ชนิดไฟล์ตรงกัน');
                $newFile=bin2hex(random_bytes(24)).'.bin';
                $dir=__DIR__.'/../uploads/learning-media';
                if(!is_dir($dir)&&!mkdir($dir,0770,true))throw new RuntimeException('Cannot create upload directory');
                if(!move_uploaded_file($tmp,$dir.'/'.$newFile))throw new RuntimeException('Upload move failed');
                $values['file_key']=$newFile;$values['file_mime']=$mime;
            }
            if($values['content']===''&&$values['external_url']===''&&!$values['file_key'])throw new DomainException('กรุณาเพิ่มเนื้อหา ลิงก์ หรือไฟล์อย่างน้อยหนึ่งอย่าง');
        }else{
            if(array_key_exists('duration_minutes',$d)) {
                $minutes=$d['duration_minutes'];
                if($minutes==='')$d['duration_seconds']='';
                else {
                    if(!is_scalar($minutes)||!preg_match('/^\d+(?:\.\d{1,8})?$/D',(string)$minutes)||(float)$minutes<=0||(float)$minutes>1440)throw new DomainException('กรุณาระบุเวลาเป็นนาที มากกว่า 0 และไม่เกิน 1440');
                    $d['duration_seconds']=(int)round((float)$minutes*60);
                }
            }
            $values=['name'=>$this->text($d,'name',200),'description'=>$this->text($d,'description',10000,false),'rules'=>$this->text($d,'rules',20000),'rounds'=>(($d['status']??'')==='draft'&&($d['rounds']??'')==='')?null:$this->integer($d['rounds']??null,1,1000),'duration_seconds'=>(($d['status']??'')==='draft'&&($d['duration_seconds']??'')==='')?null:$this->integer($d['duration_seconds']??null,1,86400),'status'=>$this->choice($d,'status',['draft','active','archived'])];
            if(($d['criteria_complete']??'')!=='1')throw new DomainException('ข้อมูลเกณฑ์คะแนนมาไม่ครบ กรุณาเปิดหน้าใหม่');
            $raw=$d['criteria']??[];
            if($values['status']==='draft'&&is_array($raw))$raw=array_values(array_filter($raw,fn($r)=>!is_array($r)||trim(is_string($r['name']??null)?$r['name']:'')!==''));
            if(!is_array($raw)||count($raw)>50||($values['status']!=='draft'&&count($raw)<1))throw new DomainException('ต้องมีเกณฑ์คะแนน 1–50 เกณฑ์');
            $names=[];
            foreach($raw as $row){
                if(!is_array($row))throw new DomainException('ข้อมูลเกณฑ์คะแนนไม่ถูกต้อง');
                $name=$this->text($row,'name',100);
                if(in_array(mb_strtolower($name),$names,true))throw new DomainException('ชื่อเกณฑ์คะแนนต้องไม่ซ้ำกัน');
                $names[]=mb_strtolower($name);$type=$this->choice($row,'score_type',['integer','decimal']);
                foreach(['min_score','max_score'] as $key){
                    $v=$row[$key]??null;
                    if($values['status']==='draft'&&($v===null||$v==='')){$row[$key]=null;continue;}
                    if(!is_scalar($v)||!preg_match('/^-?[0-9]{1,7}(?:\.[0-9]{1,2})?$/',(string)$v)||($type==='integer'&&(float)$v!==floor((float)$v)))throw new DomainException('คะแนนต้องเป็นตัวเลขตามชนิดที่เลือก ทศนิยมไม่เกิน 2 ตำแหน่ง');
                }
                if($row['min_score']!==null&&$row['max_score']!==null&&(float)$row['min_score']>(float)$row['max_score'])throw new DomainException('คะแนนต่ำสุดต้องไม่มากกว่าคะแนนสูงสุด');
                $criteria[]=[$name,$type,$row['min_score'],$row['max_score']];
            }
        }
        $pdo=db();
        try{
            $pdo->beginTransaction();
            if($kind==='games'){
                $q=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE');$q->execute([$camp]);
                if($id){
                    $q=$pdo->prepare('SELECT game_snapshot FROM competition_rounds WHERE game_id=? ORDER BY round_no LIMIT 1');$q->execute([$id]);$snapshot=$q->fetchColumn();
                    if($snapshot&&(int)$values['rounds']!==(int)json_decode($snapshot,true)['rounds'])throw new DomainException('ยืนยันรอบแรกแล้ว ไม่สามารถเปลี่ยนจำนวนรอบของเกมนี้');
                }
            }
            if($id){
                $sql='UPDATE '.$table.' SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($values))).',version=version+1 WHERE id=? AND camp_id=? AND version=?';
                $q=$pdo->prepare($sql);$q->execute([...array_values($values),$id,$camp,$version]);
                if($q->rowCount()!==1)throw new DomainException('ข้อมูลถูกแก้จากอีกหน้า กรุณาเปิดข้อมูลล่าสุดก่อนบันทึก');
            }else{
                $q=$pdo->prepare('INSERT INTO '.$table.' (camp_id,'.implode(',',array_keys($values)).') VALUES ('.implode(',',array_fill(0,count($values)+1,'?')).')');
                $q->execute([$camp,...array_values($values)]);$id=(int)$pdo->lastInsertId();
            }
            if($kind==='games'){
                $pdo->prepare('DELETE FROM score_criteria WHERE game_id=?')->execute([$id]);
                $q=$pdo->prepare('INSERT INTO score_criteria(game_id,name,score_type,min_score,max_score,position) VALUES (?,?,?,?,?,?)');
                foreach($criteria as $i=>$row)$q->execute([$id,...$row,$i]);
            }
            $pdo->commit();
        }catch(Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            if($newFile)unlink(__DIR__.'/../uploads/learning-media/'.$newFile);
            throw $e;
        }
        return $id;
    }
}
