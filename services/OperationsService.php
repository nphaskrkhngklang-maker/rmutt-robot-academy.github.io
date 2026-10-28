<?php
declare(strict_types=1);

class OperationsService
{
    private function text(array $data,string $key,int $max,bool $required=true): string {
        $value=is_string($data[$key]??null)?trim($data[$key]):'';
        if (($required && $value==='') || mb_strlen($value)>$max) throw new DomainException('กรุณากรอกข้อมูลให้ครบและไม่เกินความยาวที่กำหนด');
        return $value;
    }
    private function number(mixed $value,int $min=1): int {
        if (!is_scalar($value) || filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>1000000000]])===false) throw new DomainException('จำนวนหรือรหัสรายการไม่ถูกต้อง');
        return (int)$value;
    }
    private function choice(array $data,string $key,array $choices): string {
        if (!is_string($data[$key]??null) || !in_array($data[$key],$choices,true)) throw new DomainException('ประเภทหรือสถานะไม่ถูกต้อง');
        return $data[$key];
    }
    private function target(string $module,mixed $id,int $camp): int {
        $id=$this->number($id);
        if (!(new Operations())->find($module,$id,$camp)) throw new DomainException('รายการที่เลือกไม่อยู่ในค่ายนี้');
        return $id;
    }
    private function assignee(mixed $id,int $camp): int {
        $id=$this->number($id);
        if (!in_array($id,array_map('intval',array_column((new Operations())->people($camp),'id')),true)) throw new DomainException('ผู้รับผิดชอบต้องเป็นบัญชีที่เปิดใช้งานและเข้าถึงค่ายนี้ได้');
        return $id;
    }
    // All team and registration changes lock the camp before school/participant rows.
    public function run(array $user,int $camp,string $module,array $data): void {
        if (!(new Camp())->findFor($camp,$user)) throw new DomainException('ไม่มีสิทธิ์เข้าถึงค่ายนี้');
        $pdo=db(); $pdo->beginTransaction();
        try {
            $q=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE'); $q->execute([$camp]);
            $action=$data['action']??'save'; $main=$user['role']==='main_staff'; $repo=new Operations();
            if ($module==='teams' && $action==='assign') {
                if ($repo->locked($camp)) throw new DomainException('ล็อกการจัดทีมแล้ว กรุณาติดต่อสตาฟหลัก');
                $ids=$data['participant_ids']??[];
                if (!is_array($ids) || !$ids || count($ids)>500) throw new DomainException('เลือกนักเรียน 1–500 คนก่อนจัดทีม');
                $ids=array_values(array_unique(array_map(fn($id)=>$this->number($id),$ids)));
                $team=($data['team_id']??'')===''?null:$this->target('teams',$data['team_id'],$camp);
                $q=$pdo->prepare('SELECT id,school_id,type,sub_team_id FROM participants WHERE camp_id=? AND id IN ('.implode(',',array_fill(0,count($ids),'?')).') FOR UPDATE');
                $q->execute([$camp,...$ids]); $rows=$q->fetchAll();
                if (count($rows)!==count($ids) || array_filter($rows,fn($p)=>$p['type']!=='student')) throw new DomainException('เลือกได้เฉพาะนักเรียนในค่ายนี้');
                foreach ($rows as $row) {
                    $expected=$data['expected_team'][$row['id']]??null;
                    if (!is_scalar($expected) || (string)$expected!==(string)($row['sub_team_id']??'')) throw new DomainException('มีการเปลี่ยนทีมจากอีกหน้าแล้ว กรุณาโหลดข้อมูลล่าสุด');
                }
                $q=$pdo->prepare('UPDATE participants SET sub_team_id=? WHERE camp_id=? AND id IN ('.implode(',',array_fill(0,count($ids),'?')).')'); $q->execute([$team,$camp,...$ids]);
                $schoolIds=array_unique(array_column($rows,'school_id'));
                foreach ($schoolIds as $schoolId) { $q=$pdo->prepare('UPDATE schools SET registration_version=registration_version+1 WHERE id=?'); $q->execute([$schoolId]); }
            } elseif ($module==='teams' && $action==='lock') {
                if (!$main) throw new DomainException('เฉพาะสตาฟหลักเท่านั้นที่เปลี่ยนการล็อกทีมได้');
                $locked=$this->choice($data,'locked',['0','1']);
                $q=$pdo->prepare('INSERT INTO camp_team_locks (camp_id,is_locked) VALUES (?,?) ON DUPLICATE KEY UPDATE is_locked=VALUES(is_locked)'); $q->execute([$camp,$locked]);
            } elseif ($action==='status' && in_array($module,['equipment','timeline'],true)) {
                $id=$this->target($module,$data['id']??null,$camp); $row=$repo->find($module,$id,$camp);
                if ($module==='timeline' && !$main && empty($row['all_staff']) && (int)$row['user_id']!==(int)$user['id']) throw new DomainException('เปลี่ยนสถานะได้เฉพาะงานที่คุณรับผิดชอบ');
                $status=$this->choice($data,'status',array_keys($module==='equipment'?equipment_statuses():task_statuses()));
                $values=['status'=>$status];
                if ($module==='equipment') $values['notes']=$this->text($data,'notes',2000,false);
                $this->update($module,$id,$camp,$values,$this->number($data['version']??null,0));
            } else {
                if (!$main) throw new DomainException('ส่วนนี้สำหรับสตาฟหลักเท่านั้น');
                $id=empty($data['id'])?null:$this->target($module,$data['id'],$camp);
                if ($module==='duties' && $action==='remove' && $id) {
                    $q=$pdo->prepare('DELETE FROM staff_assignments WHERE id=? AND camp_id=?'); $q->execute([$id,$camp]);
                } elseif($module==='duties'&&$action==='assign_pair') {
                    $person=$this->assignee($data['user_id']??null,$camp);
                    $pair=$data['pair_teams']??null;
                    if(!is_array($pair)||count($pair)!==2)throw new DomainException('กรุณาเลือกทีมย่อย 2 ทีม');
                    $pair=array_map(fn($v)=>$this->target('teams',$v,$camp),array_values($pair));
                    if($pair[0]===$pair[1])throw new DomainException('กรุณาเลือกทีมย่อยที่ไม่ซ้ำกัน');
                    foreach($pair as $teamId){
                        $q=$pdo->prepare("SELECT id FROM staff_assignments WHERE camp_id=? AND user_id=? AND type='team' AND sub_team_id=?");$q->execute([$camp,$person,$teamId]);
                        if(!$q->fetchColumn()){
                            $q=$pdo->prepare("INSERT INTO staff_assignments(camp_id,user_id,type,sub_team_id,field_name,description) VALUES (?,?,'team',?,'','')");$q->execute([$camp,$person,$teamId]);
                        }
                    }
                } elseif ($action==='save') {
                    if (in_array($module,['groups','teams'],true) && $repo->locked($camp)) throw new DomainException('ปลดล็อกการจัดทีมก่อนแก้ไขทีมหลักหรือทีม');
                    $values=match($module) {
                        'groups'=>['name'=>$this->text($data,'name',100)],
                        'teams'=>['name'=>$this->text($data,'name',100),'group_id'=>$this->target('groups',$data['group_id']??null,$camp)],
                        'equipment'=>['name'=>$this->text($data,'name',200),'category'=>$this->choice($data,'category',equipment_categories()),'quantity'=>$this->number($data['quantity']??null,0),'status'=>$this->choice($data,'status',array_keys(equipment_statuses())),'notes'=>$this->text($data,'notes',2000,false)],
                        'duties'=>$this->duty($data,$camp),
                        'timeline'=>$this->task($data,$camp,$user),
                        default=>throw new DomainException('ไม่พบรายการที่ต้องการจัดการ')
                    };
                    if ($id) $this->update($module,$id,$camp,$values,in_array($module,['equipment','timeline'],true)?$this->number($data['version']??null,0):null);
                    else {
                        $values=['camp_id'=>$camp]+$values;
                        $q=$pdo->prepare('INSERT INTO '.$repo->table($module).' ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_fill(0,count($values),'?')).')'); $q->execute(array_values($values));
                    }
                } else throw new DomainException('คำสั่งไม่ถูกต้อง');
            }
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    private function update(string $module,int $id,int $camp,array $values,?int $version): void {
        $sql='UPDATE '.(new Operations())->table($module).' SET '.implode(',',array_map(fn($key)=>$key.'=?',array_keys($values)));
        if ($version!==null) $sql.=',version=version+1';
        $sql.=' WHERE id=? AND camp_id=?'; $args=[...array_values($values),$id,$camp];
        if ($version!==null) { $sql.=' AND version=?'; $args[]=$version; }
        $q=db()->prepare($sql); $q->execute($args);
        if ($version!==null && $q->rowCount()!==1) throw new DomainException('ข้อมูลถูกแก้จากอีกหน้าแล้ว กรุณาเปิดข้อมูลล่าสุดก่อนบันทึก');
    }
    private function duty(array $data,int $camp): array {
        if(($data['freeform_duty']??'')==='1') {
            $existing=!empty($data['id'])?(new Operations())->find('duties',$this->number($data['id']),$camp):false;
            return ['user_id'=>$this->assignee($data['user_id']??null,$camp),'description'=>$this->text($data,'description',1000),
                'type'=>$existing['type']??'general','group_id'=>$existing['group_id']??null,'sub_team_id'=>$existing['sub_team_id']??null,
                'field_no'=>$existing['field_no']??null,'field_name'=>$existing['field_name']??''];
        }
        if(array_key_exists('assignment_target',$data)) {
            $target=$this->text($data,'assignment_target',60,false);
            $hasField=($data['field_no']??'')!==''||$this->text($data,'field_name',100,false)!=='';
            if($target!==''&&$hasField)throw new DomainException('กรุณาแยกรายการดูแลทีมกับบันทึกคะแนนสนาม โดยเลือกสตาฟคนเดิมได้');
            if($target!=='') {
                if(!preg_match('/^(group|team):([1-9][0-9]*)$/D',$target,$parts))throw new DomainException('ทีมที่เลือกไม่ถูกต้อง');
                $data['type']=$parts[1];$data[$parts[1]==='group'?'group_id':'sub_team_id']=$parts[2];
            } else {$data['type']=$hasField?'field':'general';}
        }
        $type=$this->choice($data,'type',array_keys(duty_types()));
        return ['user_id'=>$this->assignee($data['user_id']??null,$camp),'type'=>$type,
            'group_id'=>$type==='group'?$this->target('groups',$data['group_id']??null,$camp):null,
            'sub_team_id'=>$type==='team'?$this->target('teams',$data['sub_team_id']??null,$camp):null,
            'field_no'=>$type==='field'&&($data['field_no']??'')!==''?(new RandomizationService())->number($data['field_no'],1,100):null,
            'field_name'=>$type==='field'?$this->text($data,'field_name',100):'',
            'description'=>$this->text($data,'description',1000,$type==='general')];
    }
    private function task(array $data,int $camp,array $user): array {
        $date=$this->text($data,'task_date',10); $time=$this->text($data,'task_time',5);
        $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if (!$parsed || $parsed->format('Y-m-d')!==$date || $date<'1000-01-01' || !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D',$time)) throw new DomainException('ระบุวันที่และเวลาให้ถูกต้อง');
        return ['name'=>$this->text($data,'name',200),'description'=>$this->text($data,'description',2000,false),'all_staff'=>($data['user_id']??'')==='all'?1:0,'user_id'=>($data['user_id']??'')==='all'?(int)$user['id']:$this->assignee($data['user_id']??null,$camp),'task_date'=>$date,'task_time'=>$time,'phase'=>$this->choice($data,'phase',array_keys(task_phases())),'status'=>$this->choice($data,'status',array_keys(task_statuses()))];
    }
}
