<?php
declare(strict_types=1);
class RandomizationService {
    public function config(array $data):array {
        $unit=$data['unit']??'';$rule=$data['rule']??'';
        if(!in_array($unit,['sub_team','group'],true)||!in_array($rule,['allow_same','different_only'],true))throw new DomainException('กรุณาเลือกหน่วยแข่งขันและกติกาการสุ่ม');
        return ['unit'=>$unit,'rule'=>$unit==='group'?'allow_same':$rule,'fields'=>$this->number($data['fields']??null,1,100),'per_field'=>$this->number($data['per_field']??null,1,100)];
    }
    public function number(mixed $value,int $min,int $max):int {
        if(!is_scalar($value)||filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]])===false)throw new DomainException('ตัวเลขหรือรหัสรายการไม่ถูกต้อง');
        return (int)$value;
    }
    // Deliberately never reads readiness. Every unit in the camp participates.
    public function pool(int $camp,string $unit):array {
        $sql=$unit==='group'?'SELECT id,id AS group_id,name FROM camp_groups WHERE camp_id=? ORDER BY id':'SELECT t.id,t.group_id,CONCAT(g.name," / ",t.name) AS name FROM sub_teams t JOIN camp_groups g ON g.id=t.group_id WHERE t.camp_id=? ORDER BY t.id';
        $q=db()->prepare($sql);$q->execute([$camp]);return $q->fetchAll();
    }
    public function arrange(array $pool,array $config):array {
        $n=count($pool);if(!$n)throw new DomainException('ยังไม่มีทีม/ทีมหลักสำหรับสุ่ม กรุณาจัดทีมก่อน');
        if($n>5000)throw new DomainException('รองรับสูงสุด 5,000 หน่วยต่อรอบ');
        $capacity=$config['per_field'];$count=(int)ceil($n/$capacity);$bins=array_fill(0,$count,[]);
        if($config['unit']==='sub_team'&&$config['rule']==='different_only'){
            $groups=[];foreach($pool as $row)$groups[$row['group_id']][]=$row;
            if(max(array_map('count',$groups))>$count)throw new DomainException('จำนวนทีมแต่ละทีมหลักไม่สมดุล จัดให้ต่างทีมหลักทั้งหมดไม่ได้ด้วยจำนวนทีมต่อสนามนี้ กรุณาลดจำนวนทีมต่อสนามหรืออนุญาตทีมหลักเดียวกัน');
            $groups=array_values($groups);shuffle($groups);usort($groups,fn($a,$b)=>count($b)<=>count($a));
            foreach($groups as $group){
                shuffle($group);$indices=range(0,$count-1);shuffle($indices);
                usort($indices,fn($a,$b)=>count($bins[$a])<=>count($bins[$b]));
                foreach($group as $i=>$row)$bins[$indices[$i]][]=$row;
            }
        }else{
            shuffle($pool);foreach($pool as $i=>$row)$bins[$i%$count][]=$row;
        }
        shuffle($bins);$matches=[];
        foreach($bins as $i=>$entries){shuffle($entries);$matches[]=['queue_no'=>intdiv($i,$config['fields'])+1,'field_id'=>$i%$config['fields']+1,'entries'=>$entries];}
        $this->validate($matches,$pool,$config);return $matches;
    }
    public function validate(array $matches,array $pool,array $config):void {
        $expected=[];foreach($pool as $p)$expected[(int)$p['id']]=$p;
        $seen=[];
        if(count($matches)!==(int)ceil(count($pool)/$config['per_field']))throw new DomainException('จำนวนแมตช์ไม่ตรงกับการตั้งค่า');
        foreach($matches as $i=>$match){
            if($match['queue_no']!==intdiv($i,$config['fields'])+1||$match['field_id']!==$i%$config['fields']+1)throw new DomainException('คิวหรือสนามไม่ถูกต้อง');
            if(!$match['entries']||count($match['entries'])>$config['per_field'])throw new DomainException('จำนวนทีมต่อแมตช์ไม่ถูกต้อง');
            $groups=[];
            foreach($match['entries'] as $entry){
                $id=(int)$entry['id'];
                if(isset($seen[$id])||!isset($expected[$id])||$expected[$id]!==$entry)throw new DomainException('มีทีมซ้ำหรือข้อมูลทีมเปลี่ยนไป กรุณาสุ่มใหม่');
                $seen[$id]=true;
                if($config['unit']==='sub_team'&&$config['rule']==='different_only'&&isset($groups[$entry['group_id']]))throw new DomainException('ทีมทีมหลักเดียวกันอยู่ในแมตช์เดียวกัน');
                $groups[$entry['group_id']]=true;
            }
        }
        if(count($seen)!==count($expected))throw new DomainException('ทีมในรอบไม่ครบ');
    }
}
