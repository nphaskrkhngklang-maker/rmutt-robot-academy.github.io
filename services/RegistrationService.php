<?php
declare(strict_types=1);

class RegistrationService
{
    public const MAX_ROWS = 150;

    public function validate(mixed $posted, bool $submit): array {
        $rows = []; $errors = [];
        if (!is_array($posted) || count($posted)>self::MAX_ROWS) return [[],['รองรับผู้เข้าร่วมสูงสุด 150 คนต่อโรงเรียน กรุณาตรวจสอบรายการ']];
        $seen = [];
        foreach (array_values($posted) as $index=>$row) {
            if (!is_array($row)) { $errors[] = 'รูปแบบรายชื่อไม่ถูกต้อง'; continue; }
            $clean = [];
            foreach (['id','type','name','shirt_size'] as $field) $clean[$field] = is_scalar($row[$field] ?? null) ? trim((string)$row[$field]) : '';
            if ($clean['name']==='' && $clean['id']==='' && $clean['shirt_size']==='') continue;
            $n = $index+1;
            if ($clean['name']==='' || mb_strlen($clean['name'])>200) $errors[] = "รายการที่ $n: ระบุชื่อ-นามสกุลไม่เกิน 200 ตัวอักษร";
            if (!in_array($clean['type'],['student','teacher'],true)) $errors[] = "รายการที่ $n: ประเภทผู้เข้าร่วมไม่ถูกต้อง";
            if ($clean['shirt_size']!=='' && !in_array($clean['shirt_size'],shirt_sizes(),true)) $errors[] = "รายการที่ $n: ไซซ์เสื้อไม่ถูกต้อง";
            if ($submit && $clean['type']==='student' && $clean['shirt_size']==='') $errors[] = "รายการที่ $n: เลือกไซซ์เสื้อของนักเรียนก่อนส่งข้อมูล";
            if ($clean['id']!=='' && (!ctype_digit($clean['id']) || (int)$clean['id']<1 || isset($seen[$clean['id']]))) $errors[] = 'รหัสผู้เข้าร่วมไม่ถูกต้องหรือซ้ำกัน';
            if ($clean['id']!=='') $seen[$clean['id']] = true;
            $rows[] = $clean;
        }
        if ($submit && !array_filter($rows,fn($r)=>$r['type']==='student' && $r['name']!=='')) $errors[] = 'เพิ่มนักเรียนอย่างน้อย 1 คนก่อนส่งข้อมูล';
        return [$rows,array_values(array_unique($errors))];
    }
    public function save(int $schoolId, ?string $token, array $rows, string $version, bool $submit): void {
        [$rows,$errors] = $this->validate($rows,$submit);
        if ($errors) throw new DomainException(implode(' / ',$errors));
        $pdo = db(); $pdo->beginTransaction();
        try {
            $lookup=$pdo->prepare('SELECT camp_id FROM schools WHERE id=?'); $lookup->execute([$schoolId]); $registrationCamp=$lookup->fetchColumn();
            if (!$registrationCamp) throw new DomainException('ไม่พบโรงเรียน');
            $campLock=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE'); $campLock->execute([$registrationCamp]);
            // Same lock is used by link close/rotation: stale forms cannot save after revocation.
            $q = $pdo->prepare('SELECT * FROM schools WHERE id=? FOR UPDATE'); $q->execute([$schoolId]); $school = $q->fetch();
            if (!$school) throw new DomainException('ไม่พบโรงเรียน');
            if ($token !== null) {
                $resolved = (new RegistrationTokenService())->resolve($token);
                if (!$resolved || (int)$resolved['id']!==$schoolId) throw new DomainException('ลิงก์นี้ถูกปิดหรือออกใหม่แล้ว กรุณาติดต่อสตาฟหลัก');
            }
            if (!ctype_digit($version) || (int)$version!==(int)$school['registration_version']) throw new DomainException('มีการบันทึกข้อมูลจากอีกหน้าหนึ่งแล้ว กรุณาเปิดข้อมูลล่าสุดก่อนแก้ไขอีกครั้ง');
            $existing = [];
            foreach ((new Participant())->forSchool($schoolId) as $participant) $existing[(int)$participant['id']] = $participant;
            $keep = [];
            foreach ($rows as $row) {
                $id = $row['id']==='' ? null : (int)$row['id'];
                if ($id && !isset($existing[$id])) throw new DomainException('พบรายชื่อที่ไม่ใช่ของโรงเรียนนี้ กรุณาเปิดข้อมูลล่าสุด');
                if ($id) {
                    if ($existing[$id]['sub_team_id']!==null && $row['type']!==$existing[$id]['type']) throw new DomainException('ไม่สามารถเปลี่ยนประเภทผู้เข้าร่วมที่จัดทีมแล้ว');
                    $q = $pdo->prepare('UPDATE participants SET type=?,name=?,shirt_size=? WHERE id=? AND school_id=?');
                    $q->execute([$row['type'],$row['name'],$row['shirt_size']?:null,$id,$schoolId]); $keep[$id] = true;
                } else {
                    // Client cannot assign a team or choose a different camp/school.
                    $q = $pdo->prepare('INSERT INTO participants (camp_id,school_id,type,name,shirt_size,sub_team_id) VALUES (?,?,?,?,?,NULL)');
                    $q->execute([$school['camp_id'],$schoolId,$row['type'],$row['name'],$row['shirt_size']?:null]);
                }
            }
            foreach ($existing as $id=>$participant) if (!isset($keep[$id])) {
                if ($participant['sub_team_id']!==null) throw new DomainException('ไม่สามารถลบผู้เข้าร่วมที่จัดทีมแล้ว');
                $q = $pdo->prepare('DELETE FROM participants WHERE id=? AND school_id=?'); $q->execute([$id,$schoolId]);
            }
            $q = $pdo->prepare('UPDATE schools SET registration_status=?, registration_version=registration_version+1, submitted_at=' . ($submit?'UTC_TIMESTAMP()':'NULL') . ' WHERE id=?');
            $q->execute([$submit?'submitted':'in_progress',$schoolId]); $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
