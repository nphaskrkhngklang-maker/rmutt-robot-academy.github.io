<div class="page-heading"><div><h1>เริ่มค่ายรอบใหม่</h1><p>ใช้ค่ายเดิม เลือกเฉพาะข้อมูลที่ต้องการล้าง</p></div></div>
<?php require __DIR__.'/../includes/errors.php'; ?>
<?php if($camp): ?>
<section class="panel form-panel"><h2><?= e($camp['name']) ?></h2><p class="muted">สื่อการเรียนรู้ เกม เกณฑ์คะแนน บัญชีและสิทธิ์สตาฟ รวมถึงข้อมูลค่าย จะยังอยู่เหมือนเดิม</p><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="reset_token" value="<?= e($_SESSION['camp_reset_token']) ?>">
<?php foreach(CampResetService::OPTIONS as $key=>[$label,$description,$table]): ?><label class="reset-option"><input type="checkbox" name="clear[]" value="<?= e($key) ?>" <?= in_array($key,$selected,true)?'checked':'' ?>><span><strong><?= e($label) ?> <small>(<?= $counts[$key] ?> <?= $key==='competition'?'รอบ':($key==='teams'?'ทีม':'รายการ') ?>)</small></strong><small><?= e($description) ?></small></span></label><?php endforeach ?>
<div class="alert error">ข้อมูลที่เลือกล้างจะถูกลบถาวรและย้อนกลับจากหน้านี้ไม่ได้ กรุณาสำรองข้อมูลก่อนเริ่มรอบใหม่ ข้อมูลที่ไม่เลือกจะเก็บไว้</div>
<label>พิมพ์ “เริ่มรอบใหม่” เพื่อยืนยัน<input name="confirmation" required autocomplete="off"></label><label>รหัสผ่านของคุณ<input type="password" name="password" required autocomplete="current-password"></label>
<div class="form-actions"><a class="button secondary" href="<?= e(url('dashboard')) ?>">ยกเลิก</a><button class="button primary" type="submit">ยืนยันล้างข้อมูลที่เลือก</button></div></form></section>
<?php else: ?><section class="panel empty-state">ยังไม่มีข้อมูลค่าย</section><?php endif ?>
