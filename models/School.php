<?php
declare(strict_types=1);

class School
{
    public function find(int $id): array|false {
        $q = db()->prepare('SELECT s.*, c.name AS camp_name FROM schools s JOIN camps c ON c.id=s.camp_id WHERE s.id=?');
        $q->execute([$id]); return $q->fetch();
    }
    public function all(int $campId): array {
        $q = db()->prepare("SELECT s.*, (SELECT COUNT(*) FROM participants p WHERE p.school_id=s.id AND p.type='student') AS student_count,
            (SELECT COUNT(*) FROM participants p WHERE p.school_id=s.id AND p.type='teacher') AS teacher_count
            FROM schools s WHERE s.camp_id=? ORDER BY s.name,s.id");
        $q->execute([$campId]); return $q->fetchAll();
    }
    public function rename(int $id, string $name): void {
        $q = db()->prepare('UPDATE schools SET name=? WHERE id=?'); $q->execute([$name,$id]);
    }
}
