<?php
declare(strict_types=1);
class CompetitionService {
    private function main(array $user,int $camp):void {
        if($user['role']!=='main_staff'||!(new Camp())->findFor($camp,$user))throw new DomainException('เฉพาะสตาฟหลักในค่ายเท่านั้นที่จัดการแข่งขันได้');
    }
    private function lockCamp(int $camp):void {$q=db()->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE');$q->execute([$camp]);}
    private function context(int $camp,int $game):array {
        $q=db()->prepare('SELECT * FROM games WHERE id=? AND camp_id=? FOR UPDATE');$q->execute([$game,$camp]);$row=$q->fetch();
        if(!$row||$row['status']!=='active')throw new DomainException('เลือกเกมที่พร้อมใช้งานในค่ายนี้ก่อน');
        $q=db()->prepare('SELECT id,round_no FROM competition_rounds WHERE game_id=? ORDER BY round_no DESC LIMIT 1');$q->execute([$game]);$previous=$q->fetch();
        $next=$previous?(int)$previous['round_no']+1:1;
        if($previous){
            $q=db()->prepare("SELECT COUNT(*) FROM competition_matches WHERE round_id=? AND status<>'finished'");$q->execute([$previous['id']]);
            if((int)$q->fetchColumn())throw new DomainException('ต้องแข่งขันรอบปัจจุบันให้ครบก่อนเริ่มรอบถัดไป');
        }
        if($next>(int)$row['rounds'])throw new DomainException('แข่งขันครบจำนวนรอบของเกมนี้แล้ว');
        return [$row,$next];
    }
    public function key(int $camp,int $game):string{return $camp.':'.$game;}
    public function preview(array $user,int $camp,int $game,array $data):array {
        $this->main($user,$camp);$random=new RandomizationService();$config=$random->config($data);
        $pdo=db();$pdo->beginTransaction();
        try{
            $this->lockCamp($camp);[$gameRow,$round]=$this->context($camp,$game);
            for($field=1;$field<=$config['fields'];$field++)db()->prepare('INSERT IGNORE INTO arenas(camp_id,field_no,name) VALUES (?,?,?)')->execute([$camp,$field,'สนาม '.$field]);
            $pool=$random->pool($camp,$config['unit']);$matches=$random->arrange($pool,$config);
            $draft=['id'=>bin2hex(random_bytes(24)),'camp_id'=>$camp,'game_id'=>$game,'round_no'=>$round,'user_id'=>(int)$user['id'],'created_at'=>time(),'config'=>$config,'game'=>$gameRow,'pool'=>$pool,'matches'=>$matches];
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        $_SESSION['competition_drafts'][$this->key($camp,$game)]=$draft;
        return $draft;
    }
    public function draft(int $camp,int $game):?array{return $_SESSION['competition_drafts'][$this->key($camp,$game)]??null;}
    public function confirm(array $user,int $camp,int $game,array $data):int {
        $this->main($user,$camp);$draft=$this->draft($camp,$game);
        if(!$draft||!is_string($data['draft_id']??null)||!hash_equals($draft['id'],$data['draft_id'])||(int)$draft['user_id']!==(int)$user['id'])throw new DomainException('ตัวอย่างนี้ไม่ใช่ฉบับล่าสุด กรุณาสุ่มใหม่');
        if(time()-$draft['created_at']>1800)throw new DomainException('ตัวอย่างหมดอายุ กรุณาสุ่มใหม่');
        $config=(new RandomizationService())->config($data);
        if($config!==$draft['config'])throw new DomainException('การตั้งค่าเปลี่ยนไป กรุณาสุ่มใหม่ก่อนยืนยัน');
        $pdo=db();$pdo->beginTransaction();
        try{
            $this->lockCamp($camp);[$gameRow,$round]=$this->context($camp,$game);
            if($round!==$draft['round_no']||$gameRow!==$draft['game'])throw new DomainException('เกมหรือรอบถูกแก้ไข กรุณาสุ่มใหม่');
            $random=new RandomizationService();$pool=$random->pool($camp,$config['unit']);
            if($pool!==$draft['pool'])throw new DomainException('ทีมหลักหรือทีมถูกแก้ไขหลังสุ่ม กรุณาสุ่มใหม่');
            $random->validate($draft['matches'],$pool,$config);
            $gameRow['criteria']=(new ContentService())->criteria($game);
            $json=fn($v)=>json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $q=$pdo->prepare('INSERT INTO competition_rounds(camp_id,game_id,round_no,config_snapshot,game_snapshot,pool_snapshot,confirmed_by) VALUES (?,?,?,?,?,?,?)');
            $q->execute([$camp,$game,$round,$json($config),$json($gameRow),$json($pool),$user['id']]);$roundId=(int)$pdo->lastInsertId();
            $matchQuery=$pdo->prepare('INSERT INTO competition_matches(round_id,game_id,round_no,queue_no,field_id) VALUES (?,?,?,?,?)');
            $entryQuery=$pdo->prepare('INSERT INTO competition_entries(round_id,match_id,unit_type,entity_id,group_id,name_snapshot) VALUES (?,?,?,?,?,?)');
            foreach($draft['matches'] as $match){
                $matchQuery->execute([$roundId,$game,$round,$match['queue_no'],$match['field_id']]);$matchId=(int)$pdo->lastInsertId();
                foreach($match['entries'] as $entry)$entryQuery->execute([$roundId,$matchId,$config['unit'],$entry['id'],$entry['group_id'],$entry['name']]);
            }
            $pdo->commit();unset($_SESSION['competition_drafts'][$this->key($camp,$game)]);return $roundId;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public function rounds(int $camp,int $game):array {
        $q=db()->prepare('SELECT * FROM competition_rounds WHERE camp_id=? AND game_id=? ORDER BY round_no DESC');$q->execute([$camp,$game]);$rounds=$q->fetchAll();
        foreach($rounds as &$round){
            $round['config']=json_decode($round['config_snapshot'],true,512,JSON_THROW_ON_ERROR);
            $round['game']=json_decode($round['game_snapshot'],true,512,JSON_THROW_ON_ERROR);
            $m=db()->prepare('SELECT * FROM competition_matches WHERE round_id=? ORDER BY queue_no,field_id');$m->execute([$round['id']]);$round['matches']=$m->fetchAll();
            $e=db()->prepare('SELECT * FROM competition_entries WHERE round_id=? ORDER BY id');$e->execute([$round['id']]);$entries=[];
            foreach($e->fetchAll() as $entry)$entries[$entry['match_id']][]=$entry;
            $round['completed']=0;$round['total']=0;
            foreach($round['matches'] as &$match){$match['entries']=$entries[$match['id']]??[];$round['total']+=count($match['entries']);if($match['status']==='finished')$round['completed']+=count($match['entries']);}
            unset($match);
        }
        unset($round);return $rounds;
    }
    public function status(array $user,int $camp,array $data):void {
        $this->main($user,$camp);$random=new RandomizationService();$id=$random->number($data['match_id']??null,1,PHP_INT_MAX);$version=$random->number($data['version']??null,0,PHP_INT_MAX);
        $next=$data['status']??'';if(!in_array($next,['playing','finished'],true))throw new DomainException('สถานะแมตช์ไม่ถูกต้อง');
        $pdo=db();$pdo->beginTransaction();
        try{
            $this->lockCamp($camp);
            $q=$pdo->prepare('SELECT m.* FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE m.id=? AND r.camp_id=? FOR UPDATE');$q->execute([$id,$camp]);$match=$q->fetch();
            if(!$match||(int)$match['version']!==$version)throw new DomainException('ไม่พบแมตช์หรือสถานะถูกแก้ไขแล้ว กรุณาโหลดหน้าใหม่');
            if(($next==='playing'&&$match['status']!=='waiting')||($next==='finished'&&$match['status']!=='playing'))throw new DomainException('สถานะต้องเปลี่ยนจากรอ → กำลังแข่ง → จบแล้ว ตามลำดับ');
            if($next==='playing'){
                $q=$pdo->prepare("SELECT COUNT(*) FROM competition_matches WHERE round_id=? AND field_id=? AND queue_no<? AND status<>'finished'");$q->execute([$match['round_id'],$match['field_id'],$match['queue_no']]);
                if((int)$q->fetchColumn())throw new DomainException('สนามนี้ยังแข่งคิวก่อนหน้าไม่จบ');
                $q=$pdo->prepare("SELECT COUNT(*) FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE r.camp_id=? AND m.field_id=? AND m.status='playing'");$q->execute([$camp,$match['field_id']]);
                if((int)$q->fetchColumn())throw new DomainException('สนามนี้กำลังใช้งานอยู่ กรุณาจบแมตช์เดิมก่อน');
                $q=$pdo->prepare("SELECT COUNT(*) FROM competition_entries a JOIN competition_entries b ON ((b.entity_id=a.entity_id AND b.unit_type=a.unit_type) OR (b.group_id=a.group_id AND (a.unit_type='group' OR b.unit_type='group'))) JOIN competition_matches m ON m.id=b.match_id JOIN competition_rounds r ON r.id=m.round_id WHERE a.match_id=? AND r.camp_id=? AND m.status='playing'");$q->execute([$id,$camp]);
                if((int)$q->fetchColumn())throw new DomainException('มีทีมกำลังแข่งขันอีกแมตช์อยู่');
            }
            $pdo->prepare('UPDATE competition_matches SET status=?,version=version+1 WHERE id=?')->execute([$next,$id]);$pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
