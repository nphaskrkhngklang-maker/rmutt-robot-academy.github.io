<?php
declare(strict_types=1);
class DeleteService {
    public const TYPES=[
        'board'=>['board_posts','โพสต์','evaluation-results'],
        'schools'=>['schools','โรงเรียน','schools'], 'participants'=>['participants','ผู้เข้าร่วม','participants'],
        'groups'=>['camp_groups','ทีมหลัก','teams'], 'teams'=>['sub_teams','ทีมย่อย','teams'],
        'duties'=>['staff_assignments','หน้าที่สตาฟ','duties'], 'equipment'=>['equipment','อุปกรณ์','equipment'],
        'timeline'=>['timeline_tasks','งาน Timeline','timeline'], 'media'=>['learning_media','สื่อ','media'],
        'games'=>['games','เกม','games'], 'rounds'=>['competition_rounds','รอบแข่งขัน','competition'],
        'staff'=>['users','บัญชีสตาฟ','staff']
    ];
    public function preview(array $user,string $kind,int $id):array {
        if($user['role']!=='main_staff'||!isset(self::TYPES[$kind]))throw new DomainException('ไม่มีสิทธิ์ลบรายการนี้');
        [$table,$label,$back]=self::TYPES[$kind];$q=db()->prepare("SELECT * FROM $table WHERE id=?");$q->execute([$id]);$row=$q->fetch();
        if(!$row)throw new DomainException('รายการนี้ไม่มีอยู่แล้ว');
        $camp=(int)($row['camp_id']??0);if($camp&&!(new Camp())->findFor($camp,$user))throw new DomainException('ไม่มีสิทธิ์เข้าถึง');
        $count=function($sql,$params) { $q=db()->prepare($sql);$q->execute($params);return (int)$q->fetchColumn(); };
        $effects=[];$blocked='';
        if(in_array($kind,['groups','teams','schools','participants'],true)){
            if((new Operations())->locked($camp))$blocked='กรุณาปลดล็อกการจัดทีมก่อนลบรายการนี้';
            if($count('SELECT COUNT(*) FROM competition_rounds WHERE camp_id=?',[$camp]))$blocked='มีตารางแข่งขันอ้างอิงสมาชิกหรือทีมอยู่ กรุณาลบรอบแข่งขันก่อน เพื่อไม่ให้คะแนนและรายชื่อไม่ตรงกัน';
        }
        if($kind==='schools')$effects[]='ลบผู้เข้าร่วม '.$count('SELECT COUNT(*) FROM participants WHERE school_id=?',[$id]).' คน และลิงก์ลงทะเบียนทั้งหมดของโรงเรียน';
        if($kind==='participants')$effects[]='ลบรายชื่อออกจากโรงเรียนและทีม พร้อมรีเซ็ตความพร้อมทีมที่เกี่ยวข้อง';
        if(in_array($kind,['groups','teams'],true)){
            $where=$kind==='groups'?'group_id=?':'id=?';$effects[]='นักเรียน '.$count("SELECT COUNT(*) FROM participants WHERE sub_team_id IN (SELECT id FROM sub_teams WHERE $where)",[$id]).' คน จะกลับเป็นยังไม่มีทีม โดยไม่ลบรายชื่อนักเรียน';
            if($kind==='groups')$effects[]='ลบทีมย่อย '.$count('SELECT COUNT(*) FROM sub_teams WHERE group_id=?',[$id]).' ทีม';
            $effects[]='ลบสถานะความพร้อมและหน้าที่สตาฟที่ผูกกับทีมที่ลบ';
        }
        if($kind==='games')$effects[]='ลบเกณฑ์คะแนนและรอบแข่งขัน '.$count('SELECT COUNT(*) FROM competition_rounds WHERE game_id=?',[$id]).' รอบ รวมคิว คะแนน และประวัติแก้คะแนนของเกมนี้';
        if($kind==='rounds'){
            $effects[]='ลบคิวแข่งขัน คะแนน และประวัติแก้คะแนนทั้งหมดในรอบนี้ อันดับจะคำนวณใหม่';
            if($count('SELECT COUNT(*) FROM competition_rounds WHERE game_id=? AND round_no>?',[$row['game_id'],$row['round_no']]))$blocked='กรุณาลบรอบล่าสุดของเกมก่อน แล้วจึงย้อนมาลบรอบนี้';
        }
        if($kind==='media')$effects[]='นำสื่อและช่องทางดาวน์โหลดไฟล์ประกอบออกจากระบบ';
        if($kind==='staff'){
            if($id===(int)$user['id'])$blocked='ไม่สามารถลบบัญชีที่คุณกำลังใช้งานอยู่';
            if($row['role']==='main_staff'&&$row['is_active']&&$count("SELECT COUNT(*) FROM users WHERE role='main_staff' AND is_active=1",[])<=1)$blocked='ต้องเก็บสตาฟหลักที่ใช้งานได้อย่างน้อยหนึ่งบัญชี';
            $effects[]='ยกเลิกการเข้าสู่ระบบ สิทธิ์ค่าย และหน้าที่ทั้งหมดของบัญชีนี้';
            $effects[]='งาน Timeline และประวัติผู้สร้าง/ผู้บันทึกจะโอนความรับผิดชอบมายังคุณ';
        }
        if(!$effects)$effects[]='ลบเฉพาะรายการนี้';
        return compact('row','camp','label','back','effects','blocked');
    }
    public function remove(array $user,string $kind,int $id,string $fingerprint):array {
        $pdo=db();$pdo->beginTransaction();try{
            // Same lock order as account edits; camp-scoped operations share the camp lock.
            if($kind==='staff')$pdo->query("SELECT id FROM users WHERE role='main_staff' AND is_active=1 ORDER BY id FOR UPDATE")->fetchAll();
            $initial=$this->preview($user,$kind,$id);
            if($initial['camp']){$q=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE');$q->execute([$initial['camp']]);}
            $table=self::TYPES[$kind][0];$q=$pdo->prepare("SELECT id FROM $table WHERE id=? FOR UPDATE");$q->execute([$id]);
            $info=$this->preview($user,$kind,$id);
            if($info['blocked'])throw new DomainException($info['blocked']);
            if(!hash_equals($this->fingerprint($info),$fingerprint))throw new DomainException('ข้อมูลเปลี่ยนไป กรุณาตรวจรายการล่าสุดแล้วกดยืนยันใหม่');
            $run=function($sql,$params)use($pdo){$q=$pdo->prepare($sql);$q->execute($params);};
            if(in_array($kind,['groups','teams'],true)){$where=$kind==='groups'?'group_id=?':'id=?';$run("UPDATE participants SET sub_team_id=NULL WHERE sub_team_id IN (SELECT id FROM sub_teams WHERE $where)",[$id]);}
            if(in_array($kind,['schools','participants'],true)){
                $where=$kind==='schools'?'school_id=?':'id=?';
                $run("UPDATE team_readiness SET is_ready=0,version=version+1 WHERE team_id IN (SELECT sub_team_id FROM participants WHERE $where)",[$id]);
                $school=$kind==='schools'?$id:(int)$info['row']['school_id'];
                $run("UPDATE schools SET registration_version=registration_version+1,registration_status='in_progress',submitted_at=NULL WHERE id=?",[$school]);
            }
            if($kind==='staff'){
                $actor=(int)$user['id'];
                foreach(['camps'=>'created_by','timeline_tasks'=>'user_id','competition_rounds'=>'confirmed_by','match_score_versions'=>'updated_by','score_revisions'=>'actor_id'] as $t=>$column)$run("UPDATE $t SET $column=? WHERE $column=?",[$actor,$id]);
                $run('DELETE FROM staff_assignments WHERE user_id=?',[$id]);
            }
            if($kind==='rounds')$run('UPDATE games SET version=version+1 WHERE id=?',[$info['row']['game_id']]);
            $run("DELETE FROM $table WHERE id=?",[$id]);$pdo->commit();return $info;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public function fingerprint(array $info):string {return hash('sha256',json_encode($info,JSON_THROW_ON_ERROR));}
}
