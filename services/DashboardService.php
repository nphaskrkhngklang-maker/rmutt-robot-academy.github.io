<?php
declare(strict_types=1);
class DashboardService {
    public function summary(int $camp):array {
        $queries=[
        'โรงเรียนทั้งหมด'=>'SELECT COUNT(*) FROM schools WHERE camp_id=?',
        'โรงเรียนส่งข้อมูลแล้ว'=>"SELECT COUNT(*) FROM schools WHERE camp_id=? AND registration_status='submitted'",
        'นักเรียนทั้งหมด'=>"SELECT COUNT(*) FROM participants WHERE camp_id=? AND type='student'",
        'นักเรียนยังไม่มีทีม'=>"SELECT COUNT(*) FROM participants WHERE camp_id=? AND type='student' AND sub_team_id IS NULL",
        'ทีมทั้งหมด'=>'SELECT COUNT(*) FROM sub_teams WHERE camp_id=?',
        'ทีมพร้อม'=>"SELECT COUNT(*) FROM team_readiness WHERE camp_id=? AND is_ready=1",
        'อุปกรณ์ยังไม่ตรวจครบ'=>"SELECT COUNT(*) FROM equipment WHERE camp_id=? AND status<>'checked'",
        'งาน Timeline ค้าง'=>"SELECT COUNT(*) FROM timeline_tasks WHERE camp_id=? AND status='not_done'"];
        $stats=[];foreach($queries as $label=>$sql){$q=db()->prepare($sql);$q->execute([$camp]);$stats[$label]=(int)$q->fetchColumn();}
        $q=db()->prepare("SELECT m.*,r.game_snapshot FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE r.camp_id=? AND m.status<>'finished' ORDER BY (m.status='playing') DESC,r.id DESC,m.queue_no,m.field_id LIMIT 1");$q->execute([$camp]);$current=$q->fetch();
        if($current)$current['game']=json_decode($current['game_snapshot'],true);
        $q=db()->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(status='done'),0) AS done FROM timeline_tasks WHERE camp_id=?");$q->execute([$camp]);$tasks=$q->fetch();
        $q=db()->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(m.status='finished'),0) AS done FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE r.camp_id=?");$q->execute([$camp]);$matches=$q->fetch();
        $q=db()->prepare("SELECT name,task_time,status FROM timeline_tasks WHERE camp_id=? AND task_date=? ORDER BY task_time,id LIMIT 5");$q->execute([$camp,date('Y-m-d')]);$today=$q->fetchAll();
        $q=db()->prepare("SELECT name,task_date,task_time FROM timeline_tasks WHERE camp_id=? AND status='not_done' AND TIMESTAMP(task_date,task_time)>=NOW() ORDER BY task_date,task_time LIMIT 1");$q->execute([$camp]);$next=$q->fetch();
        $q=db()->prepare("SELECT s.name,s.registration_status,COUNT(p.id) AS total FROM schools s LEFT JOIN participants p ON p.school_id=s.id WHERE s.camp_id=? GROUP BY s.id,s.name,s.registration_status ORDER BY total DESC,s.id LIMIT 6");$q->execute([$camp]);$schools=$q->fetchAll();
        $q=db()->prepare("SELECT COUNT(*) FROM participants WHERE camp_id=?");$q->execute([$camp]);$participants=(int)$q->fetchColumn();
        return ['tasks'=>$tasks,'matches'=>$matches,'today'=>$today,'next'=>$next,'schools'=>$schools,'participants'=>$participants,'stats'=>$stats,'current'=>$current,'ranking'=>(new RankingService())->overall($camp)];
    }
}
