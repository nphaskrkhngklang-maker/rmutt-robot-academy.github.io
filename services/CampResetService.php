<?php
declare(strict_types=1);
class CampResetService {
    public const OPTIONS = [
        'board'=>['โพสต์บนบอร์ด','ลบข้อความบนบอร์ดความประทับใจทั้งหมด','board_posts'],
        'competition'=>['ตารางแข่งและคะแนน','ลบรอบ คิว ผลคะแนน ประวัติแก้คะแนน และอันดับที่คำนวณจากคะแนน โดยเก็บเกม เกณฑ์และสูตรคะแนนไว้','competition_rounds'],
        'participants'=>['รายชื่อผู้เข้าร่วม','ลบนักเรียนและครู รีเซ็ตสถานะโรงเรียนและความพร้อมทีม ยกเลิกลิงก์ลงทะเบียนเก่า ต้องเลือกล้างการแข่งขันด้วย','participants'],
        'teams'=>['ทีมหลัก ทีม และหน้าที่สตาฟ','ลบทีมหลัก ทีม และการมอบหมายหน้าที่ทั้งหมด นักเรียนที่เก็บไว้จะกลับเป็นยังไม่มีทีม ต้องเลือกล้างการแข่งขันด้วย','sub_teams'],
        'schools'=>['รายชื่อโรงเรียน','ลบโรงเรียนและลิงก์ลงทะเบียน ต้องเลือกล้างผู้เข้าร่วมด้วย','schools'],
        'evaluations'=>['คำตอบแบบประเมิน','ลบคำตอบและผลสรุปทั้งหมด เพื่อรับคำตอบรอบใหม่','evaluations'],
        'equipment'=>['สถานะเช็กอุปกรณ์','เปลี่ยนเป็นยังไม่ตรวจ เก็บรายการ จำนวน และหมายเหตุไว้','equipment'],
        'timeline'=>['งาน Timeline','ลบงาน วันที่นัดหมาย และผู้รับผิดชอบเดิม','timeline_tasks'],
    ];
    public function counts(int $camp):array {
        $counts=[];foreach(self::OPTIONS as $key=>$option){$q=db()->prepare('SELECT COUNT(*) FROM '.$option[2].' WHERE camp_id=?');$q->execute([$camp]);$counts[$key]=(int)$q->fetchColumn();}return $counts;
    }
    public function validate(array $selected):void {
        if(!$selected||array_diff($selected,array_keys(self::OPTIONS)))throw new DomainException('กรุณาเลือกข้อมูลที่จะล้าง');
        if((in_array('participants',$selected,true)||in_array('teams',$selected,true))&&!in_array('competition',$selected,true))throw new DomainException('เมื่อล้างผู้เข้าร่วมหรือทีม ต้องเลือกล้างตารางแข่งและคะแนนด้วย');
        if(in_array('schools',$selected,true)&&!in_array('participants',$selected,true))throw new DomainException('เมื่อล้างโรงเรียน ต้องเลือกล้างรายชื่อผู้เข้าร่วมด้วย');
    }
    public function reset(array $user,int $camp,array $selected):void {
        if($user['role']!=='main_staff'||!(new Camp())->findFor($camp,$user))throw new DomainException('เฉพาะสตาฟหลักเท่านั้น');
        $this->validate($selected);$pdo=db();$pdo->beginTransaction();
        try {
            $run=function(string $sql)use($pdo,$camp){$q=$pdo->prepare($sql);$q->execute([$camp]);};
            $run('SELECT id FROM camps WHERE id=? FOR UPDATE');
            if(in_array('competition',$selected,true)){ $run('DELETE FROM competition_rounds WHERE camp_id=?'); $run('UPDATE games SET version=version+1 WHERE camp_id=?'); }
            if(in_array('participants',$selected,true)){
                $run('DELETE FROM participants WHERE camp_id=?');
                $run("UPDATE schools SET registration_status='not_started',submitted_at=NULL,registration_version=registration_version+1 WHERE camp_id=?");
                $run('UPDATE school_registration_tokens t JOIN schools s ON s.id=t.school_id SET t.revoked_at=NOW() WHERE s.camp_id=? AND t.revoked_at IS NULL');
                $run('UPDATE team_readiness SET is_ready=0,version=version+1 WHERE camp_id=?');
                $run('UPDATE camp_team_locks SET is_locked=0 WHERE camp_id=?');
            }
            if(in_array('teams',$selected,true)){
                $run('UPDATE participants SET sub_team_id=NULL WHERE camp_id=?');
                $run('DELETE FROM staff_assignments WHERE camp_id=?');
                $run('DELETE FROM camp_groups WHERE camp_id=?');
                $run('UPDATE camp_team_locks SET is_locked=0 WHERE camp_id=?');
            }
            if(in_array('schools',$selected,true))$run('DELETE FROM schools WHERE camp_id=?');
            if(in_array('board',$selected,true))$run('DELETE FROM board_posts WHERE camp_id=?');
            if(in_array('evaluations',$selected,true))$run('DELETE FROM evaluations WHERE camp_id=?');
            if(in_array('equipment',$selected,true))$run("UPDATE equipment SET status='not_checked',version=version+1 WHERE camp_id=?");
            if(in_array('timeline',$selected,true))$run('DELETE FROM timeline_tasks WHERE camp_id=?');
            $pdo->commit();unset($_SESSION['competition_drafts']);
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
