<?php
declare(strict_types=1);
class EvaluationService {
    public function save(int $camp,array $data,string $token):void {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new DomainException('แบบฟอร์มหมดอายุ กรุณาเปิดใหม่');
        $q=db()->prepare("SELECT id FROM camps WHERE id=? AND status<>'cancelled'");$q->execute([$camp]);if(!$q->fetchColumn())throw new DomainException('ไม่พบค่ายที่เปิดให้ประเมิน');
        $ratings=[];foreach(['satisfaction','activities','staff_rating'] as $key)$ratings[]=(new RandomizationService())->number($data[$key]??null,1,5);
        $level=$data['activity_level']??'';
        if(!in_array($level,['easy','appropriate','difficult'],true))throw new DomainException('กรุณาเลือกความยากของกิจกรรม');
        $text=[];foreach(['learned','feedback'] as $key){$v=$data[$key]??'';if(!is_string($v)||mb_strlen(trim($v))>2000)throw new DomainException('ข้อความต้องไม่เกิน 2,000 ตัวอักษร');$text[]=trim($v);}
        $q=db()->prepare('INSERT INTO evaluations(camp_id,submission_token,satisfaction,activities,staff_rating,activity_level,learned,feedback) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE submission_token=VALUES(submission_token)');
        $q->execute([$camp,$token,...$ratings,$level,...$text]);
    }
    public function summary(int $camp):array {
        $q=db()->prepare('SELECT COUNT(*) AS total,AVG(satisfaction) AS satisfaction,AVG(activities) AS activities,AVG(staff_rating) AS staff_rating FROM evaluations WHERE camp_id=?');$q->execute([$camp]);return $q->fetch();
    }
}
