<?php
declare(strict_types=1);

class Operations
{
    private const TABLES = ['groups'=>'camp_groups','teams'=>'sub_teams','duties'=>'staff_assignments','equipment'=>'equipment','timeline'=>'timeline_tasks'];
    public function table(string $module): string { return self::TABLES[$module] ?? throw new InvalidArgumentException('Unknown module'); }
    public function find(string $module,int $id,int $campId): array|false {
        $q=db()->prepare('SELECT * FROM '.$this->table($module).' WHERE id=? AND camp_id=?'); $q->execute([$id,$campId]); return $q->fetch();
    }
    public function rows(string $module,int $campId): array {
        $order=$module==='timeline'?'task_date,task_time,id':'id';
        $q=db()->prepare('SELECT * FROM '.$this->table($module).' WHERE camp_id=? ORDER BY '.$order); $q->execute([$campId]); return $q->fetchAll();
    }
    public function people(int $campId): array {
        $q=db()->prepare("SELECT id,name FROM users u WHERE is_active=1 AND (role='main_staff' OR EXISTS (SELECT 1 FROM camp_users cu WHERE cu.user_id=u.id AND cu.camp_id=?)) ORDER BY name");
        $q->execute([$campId]); return $q->fetchAll();
    }
    public function students(int $campId): array {
        $q=db()->prepare("SELECT p.id,p.name,p.sub_team_id,s.name AS school_name,t.name AS team_name,g.name AS group_name FROM participants p JOIN schools s ON s.id=p.school_id LEFT JOIN sub_teams t ON t.id=p.sub_team_id AND t.camp_id=p.camp_id LEFT JOIN camp_groups g ON g.id=t.group_id WHERE p.camp_id=? AND p.type='student' ORDER BY p.name,p.id");
        $q->execute([$campId]); return $q->fetchAll();
    }
    public function locked(int $campId): bool {
        $q=db()->prepare('SELECT is_locked FROM camp_team_locks WHERE camp_id=?'); $q->execute([$campId]); return (bool)$q->fetchColumn();
    }
    public function mine(array $user): array {
        $sql="SELECT a.*,c.name AS camp_name,g.name AS group_name,t.name AS team_name FROM staff_assignments a JOIN camps c ON c.id=a.camp_id LEFT JOIN camp_groups g ON g.id=a.group_id LEFT JOIN sub_teams t ON t.id=a.sub_team_id WHERE a.user_id=? AND (EXISTS (SELECT 1 FROM camp_users cu WHERE cu.camp_id=a.camp_id AND cu.user_id=a.user_id) OR ?='main_staff') ORDER BY c.start_date DESC,a.id";
        $q=db()->prepare($sql); $q->execute([$user['id'],$user['role']]); $duties=$q->fetchAll();
        $q=db()->prepare("SELECT t.*,c.name AS camp_name FROM timeline_tasks t JOIN camps c ON c.id=t.camp_id WHERE (t.user_id=? OR t.all_staff=1) AND (EXISTS (SELECT 1 FROM camp_users cu WHERE cu.camp_id=t.camp_id AND cu.user_id=?) OR ?='main_staff') ORDER BY t.task_date,t.task_time,t.id");
        $q->execute([$user['id'],$user['id'],$user['role']]); return ['myDuties'=>$duties,'myTasks'=>$q->fetchAll()];
    }
}
