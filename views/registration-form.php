<?php if(!$public): ?><a class="back-link" href="<?= e(url('school',['id'=>$school['id']])) ?>">← กลับข้อมูลโรงเรียน</a><?php endif ?>
<div class="page-heading"><div><span class="eyebrow text-teal"><?= e($school['camp_name']) ?></span><h1><?= e($school['name']) ?></h1><p>กรอกรายชื่อนักเรียน ครู และไซซ์เสื้อสำหรับเข้าร่วมค่าย</p></div><span class="badge <?= $school['registration_status']==='submitted'?'active':($school['registration_status']==='in_progress'?'draft':'') ?>"><?= e(registration_statuses()[$school['registration_status']]) ?></span></div>
<?php if($notice): ?><div class="alert success" role="status"><?= e($notice) ?></div><?php endif ?>
<?php require __DIR__.'/../includes/errors.php'; ?>
<?php if($errors): ?><p class="form-reload"><a class="text-link" href="<?= e($public?url('register',['token'=>$token]):url('registration-edit',['id'=>$school['id']])) ?>">เปิดข้อมูลล่าสุดที่บันทึกไว้</a> <span class="field-help">ค่าที่กำลังกรอกในหน้านี้จะถูกแทนด้วยข้อมูลล่าสุด</span></p><?php endif ?>
<form method="post" class="registration-form" data-registration-form>
    <?= csrf_field() ?><input type="hidden" name="version" value="<?= e($version) ?>">
    <section class="panel registration-section"><div class="section-heading"><div><h2>รายชื่อผู้เข้าร่วม</h2><p>เว้นแถวว่างได้ · นักเรียนต้องระบุไซซ์เสื้อก่อนส่งข้อมูล · ครูเลือกไม่รับเสื้อได้</p></div></div>
    <div class="section-body"><p class="field-help">บันทึกได้สูงสุด 150 คนต่อโรงเรียน รายชื่อที่เพิ่มใหม่ยังไม่ได้จัดทีม</p><div class="participant-rows" data-participant-rows><?php foreach(array_values($rows) as $index=>$row) require __DIR__.'/../includes/participant-row.php'; ?></div>
    <div class="row-actions"><button class="button secondary" name="action" value="add_student" data-add-person="student" formnovalidate>+ เพิ่มนักเรียน</button><button class="button secondary" name="action" value="add_teacher" data-add-person="teacher" formnovalidate>+ เพิ่มครู</button></div><p class="field-help" role="status" data-row-notice></p><noscript><p class="field-help">หากต้องการลบรายการ ให้เปิด JavaScript ในเบราว์เซอร์</p></noscript></div></section>
    <section class="panel registration-section"><div class="section-heading"><div><h2>สรุปจำนวนผู้เข้าร่วมและเสื้อ</h2><p>คำนวณจากรายชื่อที่กรอก ไม่ต้องนับหรือกรอกยอดซ้ำ</p></div></div><div class="section-body"><?php require __DIR__.'/../includes/registration-summary.php'; ?></div></section>
    <input type="hidden" name="form_complete" value="1">
    <div class="registration-save"><p class="muted">บันทึกร่างไว้กรอกต่อได้<?= $public?' และแก้ไขได้ตราบใดที่ลิงก์ยังเปิด':'' ?></p><div><button class="button secondary" name="action" value="draft">บันทึกร่าง</button><button class="button primary" name="action" value="submit">ส่งข้อมูลลงทะเบียน</button></div></div>
</form>
<template id="participant-template"><?php $index='__INDEX__'; $row=['id'=>'','type'=>'student','name'=>'','shirt_size'=>'']; require __DIR__.'/../includes/participant-row.php'; ?></template>
