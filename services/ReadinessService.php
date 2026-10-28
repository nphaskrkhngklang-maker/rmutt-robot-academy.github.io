<?php
declare(strict_types=1);
class ReadinessService {
    public function canEdit(array $user,int $camp,int $team):bool {
        if(!(new Camp())->findFor($camp,$user))return false;
        if($user['role']==='main_staff')return true;
        $q=db()->prepare("SELECT 1 FROM staff_assignments WHERE camp_id=? AND user_id=? AND type='team' AND sub_team_id=?");
        $q->execute([$camp,$user['id'],$team]);return (bool)$q->fetchColumn();
    }
    public function rows(int $camp):array {
        $q=db()->prepare('SELECT t.id,t.name,g.name AS group_name,COALESCE(r.is_ready,0) AS is_ready,COALESCE(r.version,0) AS version FROM sub_teams t JOIN camp_groups g ON g.id=t.group_id LEFT JOIN team_readiness r ON r.team_id=t.id WHERE t.camp_id=? ORDER BY g.id,t.id');
        $q->execute([$camp]);return $q->fetchAll();
    }
    public function save(array $user,int $camp,array $data):void {
        $numbers=new RandomizationService();$team=$numbers->number($data['team_id']??null,1,PHP_INT_MAX);
        $ready=$numbers->number($data['ready']??null,0,1);$version=$numbers->number($data['version']??null,0,PHP_INT_MAX);
        $pdo=db();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE');$q->execute([$camp]);
            if(!$this->canEdit($user,$camp,$team)||!(new Operations())->find('teams',$team,$camp))throw new DomainException('เปลี่ยนสถานะได้เฉพาะทีมที่คุณรับผิดชอบ');
            $pdo->prepare('INSERT IGNORE INTO team_readiness(team_id,camp_id) VALUES (?,?)')->execute([$team,$camp]);
            $q=$pdo->prepare('UPDATE team_readiness SET is_ready=?,version=version+1 WHERE team_id=? AND version=?');$q->execute([$ready,$team,$version]);
            if($q->rowCount()!==1)throw new DomainException('สถานะถูกแก้จากอีกหน้า กรุณาโหลดข้อมูลล่าสุด');
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
