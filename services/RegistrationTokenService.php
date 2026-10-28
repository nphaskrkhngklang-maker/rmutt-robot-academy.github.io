<?php
declare(strict_types=1);

class RegistrationTokenService
{
    private function key(): string {
        $path = __DIR__ . '/../storage/registration.key';
        // The key is outside public routes. File locking prevents competing requests writing different keys.
        $file = fopen($path, 'c+b');
        if (!$file || !flock($file, LOCK_EX)) throw new RuntimeException('Cannot access registration encryption key');
        try {
            $key = stream_get_contents($file);
            if ($key === '') { $key = random_bytes(32); if (fwrite($file,$key)!==32) throw new RuntimeException('Cannot save encryption key'); fflush($file); }
            if (strlen($key)!==32) throw new RuntimeException('Invalid registration encryption key');
            return $key;
        } finally { flock($file, LOCK_UN); fclose($file); }
    }
    private function encrypt(string $token): string {
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($token,'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,$iv,$tag);
        if ($cipher === false) throw new RuntimeException('Cannot encrypt token');
        return base64_encode($iv . $tag . $cipher);
    }
    public function current(int $schoolId): ?string {
        $q = db()->prepare('SELECT token_encrypted FROM school_registration_tokens WHERE school_id=? AND revoked_at IS NULL ORDER BY id DESC LIMIT 1');
        $q->execute([$schoolId]); $value = $q->fetchColumn();
        if (!$value) return null;
        $blob = base64_decode($value,true);
        if ($blob === false || strlen($blob)<29) throw new RuntimeException('Invalid encrypted token');
        $token = openssl_decrypt(substr($blob,28),'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,substr($blob,0,12),substr($blob,12,16));
        if ($token === false) throw new RuntimeException('Cannot decrypt registration link');
        return $token;
    }
    // Caller must hold the school row lock inside a transaction.
    private function issue(int $schoolId): string {
        $q = db()->prepare('UPDATE school_registration_tokens SET revoked_at=UTC_TIMESTAMP() WHERE school_id=? AND revoked_at IS NULL'); $q->execute([$schoolId]);
        $token = bin2hex(random_bytes(32));
        $q = db()->prepare('INSERT INTO school_registration_tokens (school_id,token_hash,token_encrypted) VALUES (?,?,?)');
        $q->execute([$schoolId,hash('sha256',$token),$this->encrypt($token)]);
        return $token;
    }
    public function createSchool(int $campId, string $name): int {
        $pdo = db(); $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('INSERT INTO schools (camp_id,name) VALUES (?,?)'); $q->execute([$campId,$name]);
            $id = (int)$pdo->lastInsertId(); $this->issue($id); $pdo->commit(); return $id;
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public function manage(int $schoolId, string $action): void {
        $pdo = db(); $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('SELECT id FROM schools WHERE id=? FOR UPDATE'); $q->execute([$schoolId]);
            if (!$q->fetch()) throw new DomainException('ไม่พบโรงเรียน');
            if ($action === 'rotate') $this->issue($schoolId);
            elseif (!in_array($action,['activate','deactivate'],true)) throw new DomainException('คำสั่งไม่ถูกต้อง');
            $q = $pdo->prepare('UPDATE schools SET link_status=? WHERE id=?');
            $q->execute([$action==='deactivate'?'inactive':'active',$schoolId]);
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public function resolve(string $token): array|false {
        if (!preg_match('/^[a-f0-9]{64}$/D',$token)) return false;
        $q = db()->prepare("SELECT s.*,c.name AS camp_name FROM school_registration_tokens t JOIN schools s ON s.id=t.school_id JOIN camps c ON c.id=s.camp_id
            WHERE t.token_hash=? AND t.revoked_at IS NULL AND s.link_status='active'");
        $q->execute([hash('sha256',$token)]); return $q->fetch();
    }
}
