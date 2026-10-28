<?php
declare(strict_types=1);

class AccountService
{
    public function save(?int $id, array $data, string $password, array $campIds): void {
        $pdo = db(); $pdo->beginTransaction();
        try {
            // Serialize account changes so concurrent edits cannot remove the last active administrator.
            $admins = $pdo->query("SELECT id FROM users WHERE role='main_staff' AND is_active=1 ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            if ($id && in_array($id, array_map('intval', $admins), true) && count($admins) === 1 && ($data['role'] !== 'main_staff' || !$data['is_active'])) {
                throw new DomainException('ต้องมีสตาฟหลักที่ใช้งานได้อย่างน้อย 1 คน');
            }
            if ($id) {
                $q = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_active=?, auth_version=auth_version+1 WHERE id=?');
                $q->execute([$data['name'], $data['email']?:null, $data['role'], $data['is_active'], $id]);
                if ($password !== '') { $q = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?'); $q->execute([password_hash($password, PASSWORD_DEFAULT), $id]); }
            } else {
                $q = $pdo->prepare('INSERT INTO users (name,email,role,is_active,password_hash) VALUES (?,?,?,?,?)');
                $q->execute([$data['name'], $data['email']?:null, $data['role'], $data['is_active'], password_hash($password, PASSWORD_DEFAULT)]);
                $id = (int) $pdo->lastInsertId();
            }
            $q = $pdo->prepare('DELETE FROM camp_users WHERE user_id=?'); $q->execute([$id]);
            $q = $pdo->prepare('INSERT INTO camp_users (camp_id,user_id) VALUES (?,?)');
            foreach ($campIds as $campId) $q->execute([$campId, $id]);
            if($password!==''){ $q=$pdo->prepare('DELETE FROM password_help_requests WHERE user_id=?');$q->execute([$id]); }
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
