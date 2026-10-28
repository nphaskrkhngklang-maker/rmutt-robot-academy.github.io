<?php
declare(strict_types=1);

class StudentAccess {
    public static function normalize(string $name): string {
        $name=preg_replace('/[\s\x{200B}\x{FEFF}]+/u','',trim($name));
        return preg_replace('/^(เด็กชาย|เด็กหญิง|นางสาว|นาย|นาง|ด\.ช\.|ด\.ญ\.|น\.ส\.)/u','',$name);
    }
    public function matches(string $name): array {
        $key=self::normalize($name);
        if($key===''||mb_strlen($name)>200)return [];
        $rows=db()->query("SELECT p.id,p.name,p.school_id,s.name school_name FROM participants p JOIN schools s ON s.id=p.school_id JOIN camps c ON c.id=p.camp_id WHERE p.type='student' AND c.status<>'cancelled'")->fetchAll();
        return array_values(array_filter($rows,fn($row)=>self::normalize($row['name'])===$key));
    }
}
