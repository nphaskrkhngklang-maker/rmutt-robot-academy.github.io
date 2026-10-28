<?php
declare(strict_types=1);

class Camp
{
    public function allFor(array $user): array {
        if ($user['role'] === 'main_staff') return db()->query('SELECT * FROM camps ORDER BY start_date DESC, id DESC')->fetchAll();
        $q = db()->prepare('SELECT c.* FROM camps c JOIN camp_users cu ON cu.camp_id = c.id WHERE cu.user_id = ? ORDER BY c.start_date DESC, c.id DESC');
        $q->execute([$user['id']]); return $q->fetchAll();
    }
    public function findFor(int $id, array $user): array|false {
        $sql = 'SELECT c.* FROM camps c WHERE c.id = ?'; $params = [$id];
        if ($user['role'] !== 'main_staff') { $sql .= ' AND EXISTS (SELECT 1 FROM camp_users cu WHERE cu.camp_id = c.id AND cu.user_id = ?)'; $params[] = $user['id']; }
        $q = db()->prepare($sql); $q->execute($params); return $q->fetch();
    }
    public function save(?int $id, array $data, int $creator): int {
        if ($id) {
            $q = db()->prepare('UPDATE camps SET name=?, start_date=?, end_date=?, status=?, description=? WHERE id=?');
            $q->execute([...array_values($data), $id]); return $id;
        }
        $q = db()->prepare('INSERT INTO camps (name,start_date,end_date,status,description,created_by) VALUES (?,?,?,?,?,?)');
        $q->execute([...array_values($data), $creator]); return (int) db()->lastInsertId();
    }
}
