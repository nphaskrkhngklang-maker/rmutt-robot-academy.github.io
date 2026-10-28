<?php
declare(strict_types=1);

class Participant
{
    public function forSchool(int $schoolId): array {
        $q = db()->prepare('SELECT id,type,name,shirt_size,sub_team_id FROM participants WHERE school_id=? ORDER BY id');
        $q->execute([$schoolId]); return $q->fetchAll();
    }
    public function pool(int $campId, ?int $schoolId, string $type, string $team): array {
        $sql = 'SELECT p.*,s.name AS school_name FROM participants p JOIN schools s ON s.id=p.school_id WHERE p.camp_id=?'; $args = [$campId];
        if ($schoolId) { $sql .= ' AND p.school_id=?'; $args[] = $schoolId; }
        if ($type !== 'all') { $sql .= ' AND p.type=?'; $args[] = $type; }
        if ($team === 'assigned') $sql .= ' AND p.sub_team_id IS NOT NULL';
        if ($team === 'unassigned') $sql .= ' AND p.sub_team_id IS NULL';
        $q = db()->prepare($sql . ' ORDER BY s.name,p.type,p.name,p.id'); $q->execute($args); return $q->fetchAll();
    }
    public static function summary(array $rows): array {
        $summary = ['student'=>0,'teacher'=>0,'shirts'=>0,'sizes'=>array_fill_keys(shirt_sizes(),0)];
        foreach ($rows as $row) {
            if (trim((string)($row['name'] ?? '')) === '') continue;
            if (isset($summary[$row['type'] ?? ''])) $summary[$row['type']]++;
            if (isset($summary['sizes'][$row['shirt_size'] ?? ''])) { $summary['shirts']++; $summary['sizes'][$row['shirt_size']]++; }
        }
        return $summary;
    }
}
