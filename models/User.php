<?php
declare(strict_types=1);

class User
{
    public function find(int $id): array|false {
        $q = db()->prepare('SELECT * FROM users WHERE id = ?'); $q->execute([$id]); return $q->fetch();
    }
    public function byEmail(string $email): array|false {
        $q = db()->prepare('SELECT * FROM users WHERE email = ?'); $q->execute([$email]); return $q->fetch();
    }
    public function byLogin(string $identifier): array|false {
        if(str_contains($identifier,'@'))return $this->byEmail(strtolower($identifier));
        $number=str_replace(['-',' '],'',$identifier);
        if(!preg_match('/^[0-9]{13}$/D',$number))return false;
        $canonical=substr($number,0,12).'-'.substr($number,12);
        $q=db()->prepare('SELECT * FROM users WHERE student_id=?');$q->execute([$canonical]);return $q->fetch();
    }
    public function all(): array {
        return db()->query('SELECT id, name, email, student_id, needs_setup, role, is_active FROM users ORDER BY is_active DESC, name, id')->fetchAll();
    }
    public function campIds(int $id): array {
        $q = db()->prepare('SELECT camp_id FROM camp_users WHERE user_id = ?'); $q->execute([$id]); return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    }
}
