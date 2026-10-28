<?php
declare(strict_types=1);
class RankingService {
    public function ordered(array $rows):array {
        usort($rows,fn($a,$b)=>($b['total']<=>$a['total'])?:strcmp($a['name'],$b['name']));
        $rank=0;$last=null;
        foreach($rows as $i=>&$row){if($last===null||$last!==$row['total'])$rank=$i+1;$row['rank']=$rank;$last=$row['total'];}unset($row);
        return $rows;
    }
    public function game(int $camp,int $game):array {
        $rounds=(new CompetitionService())->rounds($camp,$game);
        if(!$rounds)return ['complete'=>false,'rows'=>[],'rounds'=>[],'name'=>'','expected'=>0];
        $first=end($rounds);$expected=(int)$first['game']['rounds'];$complete=count($rounds)===$expected;$rows=[];
        $scoreRepo=new ScoringService();
        foreach($rounds as $round){
            if($round['total']!==$round['completed'])$complete=false;
            foreach($round['matches'] as $m){
                $match=$scoreRepo->match((int)$m['id']);$count=count($match['game']['criteria']??[]);
                foreach($match['entries'] as $entry){
                    $key=$entry['unit_type'].':'.$entry['entity_id'];
                    $rows[$key]??=['key'=>$key,'type'=>$entry['unit_type'],'entity_id'=>(int)$entry['entity_id'],'name'=>$entry['name_snapshot'],'total'=>0,'round_scores'=>[],'scored'=>0,'appearances'=>0,'maximum'=>0];
                    $values=$match['scores'][$entry['id']]??[];$valid=$count>0&&count($values)===$count;
                    if(!$valid)$complete=false;
                    $total=$valid?array_sum($values):null;
                    $rows[$key]['round_scores'][(int)$round['round_no']]=$total;
                    $rows[$key]['appearances']++;
                    foreach($match['game']['criteria']??[] as $c)$rows[$key]['maximum']+=ScoringService::cents($c['max_score']);
                    if($valid){$rows[$key]['total']+=$total;$rows[$key]['scored']++;}
                }
            }
        }
        $groups=['sub_team'=>[],'group'=>[]];
        foreach($rows as $row)$groups[$row['type']][]=$row;
        foreach($groups as &$group)$group=$this->ordered($group);unset($group);
        return ['complete'=>$complete,'rows'=>$groups,'rounds'=>array_reverse(array_column($rounds,'round_no')),'name'=>$first['game']['name'],'expected'=>$expected];
    }

    public function mode(int $camp):?string {
        $q=db()->prepare('SELECT mode FROM ranking_settings WHERE camp_id=?');$q->execute([$camp]);return $q->fetchColumn()?:null;
    }
    public function setMode(array $user,int $camp,string $mode):void {
        if($user['role']!=='main_staff'||!(new Camp())->findFor($camp,$user))throw new DomainException('เฉพาะสตาฟหลักเท่านั้นที่ตั้งสูตรคะแนนได้');
        if(!in_array($mode,['raw','normalized'],true))throw new DomainException('กรุณาเลือกสูตรคะแนน');
        db()->prepare('INSERT INTO ranking_settings(camp_id,mode) VALUES (?,?) ON DUPLICATE KEY UPDATE mode=VALUES(mode)')->execute([$camp,$mode]);
    }
    public function games(int $camp):array {
        $q=db()->prepare("SELECT id,name FROM games WHERE camp_id=? AND (status='active' OR EXISTS(SELECT 1 FROM competition_rounds r WHERE r.game_id=games.id)) ORDER BY id");
        $q->execute([$camp]);return $q->fetchAll();
    }
    public function overall(int $camp):array {
        $mode=$this->mode($camp);$games=$this->games($camp);$rows=[];$finished=[];$pending=[];$invalid=false;
        foreach($games as $game){
            $result=$this->game($camp,(int)$game['id']);
            if(!$result['complete']){$pending[]=$game;continue;}
            $finished[]=$game;
            foreach($result['rows'] as $group)foreach($group as $unit){
                $key=$unit['key'];
                $rows[$key]??=['key'=>$key,'type'=>$unit['type'],'name'=>$unit['name'],'total'=>0,'game_scores'=>[]];
                $value=$unit['total'];
                if($mode==='normalized'){
                    if($unit['maximum']<=0){$invalid=true;continue;}
                    // Hundredths of a percentage point. Round once per game.
                    $value=(int)round($unit['total']/$unit['maximum']*10000);
                }
                $rows[$key]['game_scores'][$game['id']]=$value;
                $rows[$key]['total']+=$value;
            }
        }
        $adjust=db()->prepare('SELECT t.id,t.name,t.group_id,COALESCE(SUM(a.amount),0) amount FROM sub_teams t LEFT JOIN score_adjustments a ON a.team_id=t.id AND a.camp_id=t.camp_id WHERE t.camp_id=? GROUP BY t.id');$adjust->execute([$camp]);
        foreach($adjust as $team){$key='sub_team:'.$team['id'];$rows[$key]??=['key'=>$key,'type'=>'sub_team','name'=>$team['name'],'total'=>0,'game_scores'=>[]];$rows[$key]['adjustment']=(int)$team['amount'];$rows[$key]['total']+=(int)$team['amount'];}
        $groups=['sub_team'=>[],'group'=>[]];
        if($mode&&!$invalid)foreach($rows as $row)$groups[$row['type']][]=$row;
        foreach($groups as &$group)$group=$this->ordered($group);unset($group);
        return ['mode'=>$mode,'games'=>$finished,'pending'=>$pending,'rows'=>$groups,'complete'=>$games&&!$pending&&!$invalid&&$mode!==null,'invalid'=>$invalid];
    }
}
