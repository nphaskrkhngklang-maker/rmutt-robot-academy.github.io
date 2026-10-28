<?php
declare(strict_types=1);

class ParticipantController
{
    public function index(array $user): void {
        $camp = selected_camp($user); $schoolId = !empty($_GET['school_id']) ? record_id($_GET['school_id']) : null;
        $type = is_string($_GET['type']??null) ? $_GET['type'] : 'student';
        $team = is_string($_GET['team']??null) ? $_GET['team'] : 'all';
        if (!in_array($type,['student','teacher','all'],true) || !in_array($team,['all','assigned','unassigned'],true)) abort_request(400,'ตัวกรองไม่ถูกต้อง','กรุณาเลือกตัวกรองใหม่');
        if ($schoolId) {
            $school = (new School())->find($schoolId);
            if (!$camp || !$school || (int)$school['camp_id']!==(int)$camp['id']) abort_request(404,'ไม่พบโรงเรียน','โรงเรียนนี้ไม่อยู่ในค่ายที่เลือก');
        }
        $rows = $camp ? (new Participant())->pool((int)$camp['id'],$schoolId,$type,$team) : [];
        render('participants',['title'=>'รายชื่อผู้เข้าร่วม','user'=>$user,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'schools'=>$camp?(new School())->all((int)$camp['id']):[],'rows'=>$rows,'summary'=>Participant::summary($rows),'schoolId'=>$schoolId,'type'=>$type,'team'=>$team]);
    }
}
