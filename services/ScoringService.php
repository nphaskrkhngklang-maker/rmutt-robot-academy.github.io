<?php
declare(strict_types=1);
class ScoringService {
    public static function cents(mixed $value):int {
        if(!is_scalar($value)||!preg_match('/^-?[0-9]{1,7}(?:\.[0-9]{1,2})?$/D',(string)$value))throw new DomainException('คะแนนต้องเป็นตัวเลข ทศนิยมไม่เกิน 2 ตำแหน่ง');
        $s=(string)$value;$negative=str_starts_with($s,'-');$parts=explode('.',ltrim($s,'-'));
        $cents=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
        return $negative?-$cents:$cents;
    }
    public static function decimal(int $cents):string {
        return ($cents<0?'-':'').intdiv(abs($cents),100).'.'.str_pad((string)(abs($cents)%100),2,'0',STR_PAD_LEFT);
    }
    public function canScore(array $user,int $camp,int $field):bool {
        if(!(new Camp())->findFor($camp,$user))return false;
        if($user['role']==='main_staff')return true;
        $q=db()->prepare("SELECT 1 FROM staff_assignments WHERE camp_id=? AND user_id=? AND type='field' AND field_no=?");$q->execute([$camp,$user['id'],$field]);return (bool)$q->fetchColumn();
    }
    public function match(int $id):array|false {
        $q=db()->prepare('SELECT m.*,r.camp_id,r.game_snapshot,r.config_snapshot,COALESCE(v.version,0) AS score_version,v.updated_at AS scored_at FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id LEFT JOIN match_score_versions v ON v.match_id=m.id WHERE m.id=?');
        $q->execute([$id]);$match=$q->fetch();if(!$match)return false;
        $match['game']=json_decode($match['game_snapshot'],true,512,JSON_THROW_ON_ERROR);
        $match['config']=json_decode($match['config_snapshot'],true,512,JSON_THROW_ON_ERROR);
        $q=db()->prepare('SELECT * FROM competition_entries WHERE match_id=? ORDER BY id');$q->execute([$id]);$match['entries']=$q->fetchAll();
        $q=db()->prepare('SELECT s.* FROM score_entries s JOIN competition_entries e ON e.id=s.entry_id WHERE e.match_id=?');$q->execute([$id]);$match['scores']=[];
        foreach($q->fetchAll() as $row)$match['scores'][$row['entry_id']][$row['criterion_no']]=(int)$row['amount_cents'];
        return $match;
    }
    public function queue(array $user,int $camp):array {
        $q=db()->prepare('SELECT m.id FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE r.camp_id=? ORDER BY m.game_id,m.round_no,m.queue_no,m.field_id');$q->execute([$camp]);$rows=[];
        foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id){$m=$this->match((int)$id);if($this->canScore($user,$camp,(int)$m['field_id']))$rows[]=$m;}
        return $rows;
    }
    public function save(array $user,int $id,array $data):void {
        $initial=$this->match($id);if(!$initial)throw new DomainException('ไม่พบแมตช์');
        $camp=(int)$initial['camp_id'];$pdo=db();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE');$q->execute([$camp]);
            $q=$pdo->prepare('SELECT id FROM competition_matches WHERE id=? FOR UPDATE');$q->execute([$id]);
            $match=$this->match($id);
            if(!$this->canScore($user,$camp,(int)$match['field_id']))throw new DomainException('บันทึกคะแนนได้เฉพาะสนามที่ได้รับมอบหมาย');
            if($match['status']==='waiting')throw new DomainException('แมตช์ยังไม่เริ่ม จึงยังบันทึกคะแนนไม่ได้');
            foreach(['game_id','round_no','field_id'] as $key)if((string)($data[$key]??'')!==(string)$match[$key])throw new DomainException('เกม รอบ หรือสนามไม่ตรงกับแมตช์');
            if(($data['form_complete']??'')!=='1')throw new DomainException('ข้อมูลส่งมาไม่ครบ กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
            if(!is_scalar($data['score_version']??null)||(string)$data['score_version']!==(string)$match['score_version'])throw new DomainException('คะแนนถูกแก้จากอีกหน้า กรุณาโหลดคะแนนล่าสุด');
            $criteria=$match['game']['criteria']??[];if(!$criteria)throw new DomainException('รอบนี้ไม่มีเกณฑ์คะแนน กรุณาติดต่อสตาฟหลัก');
            $scores=$data['scores']??null;if(!is_array($scores)||count($scores)!==count($match['entries']))throw new DomainException('ต้องกรอกคะแนนให้ครบทุกทีม/ทีมหลักในแมตช์');
            $validated=[];
            foreach($match['entries'] as $entry){
                $values=$scores[$entry['id']]??null;
                if(!is_array($values)||count($values)!==count($criteria))throw new DomainException('กรอกคะแนนทุกเกณฑ์ให้ครบ');
                foreach($criteria as $i=>$criterion){
                    $value=self::cents($values[$i]??null);
                    if($criterion['min_score']===null||$criterion['max_score']===null)throw new DomainException('เกณฑ์คะแนนของรอบนี้ยังไม่ครบ');
                    if(($criterion['score_type']==='integer'&&$value%100!==0)||$value<self::cents($criterion['min_score'])||$value>self::cents($criterion['max_score']))throw new DomainException('คะแนน '.$criterion['name'].' อยู่นอกช่วงหรือไม่ตรงชนิดที่กำหนด');
                    $validated[$entry['id']][$i]=$value;
                }
            }
            $pdo->prepare('INSERT IGNORE INTO match_score_versions(match_id) VALUES (?)')->execute([$id]);
            $q=$pdo->prepare('UPDATE match_score_versions SET version=version+1,updated_by=? WHERE match_id=? AND version=?');$q->execute([$user['id'],$id,$match['score_version']]);
            if($q->rowCount()!==1)throw new DomainException('คะแนนถูกแก้ไข กรุณาโหลดหน้าใหม่');
            $q=$pdo->prepare('INSERT INTO score_entries(entry_id,criterion_no,amount_cents) VALUES (?,?,?) ON DUPLICATE KEY UPDATE amount_cents=VALUES(amount_cents)');
            foreach($validated as $entry=>$values)foreach($values as $criterion=>$value)$q->execute([$entry,$criterion,$value]);
            $pdo->prepare('INSERT INTO score_revisions(match_id,version,actor_id,scores_json) VALUES (?,?,?,?)')->execute([$id,(int)$match['score_version']+1,$user['id'],json_encode($validated,JSON_THROW_ON_ERROR)]);
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
