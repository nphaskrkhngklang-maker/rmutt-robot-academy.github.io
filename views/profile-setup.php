<section class="login-panel login-simple"><div class="login-box">
<a class="back-link" href="<?= e(url('login')) ?>">← กลับหน้าเข้าสู่ระบบ</a>
<h2>ตั้งค่าโปรไฟล์ครั้งแรก</h2>
<p class="field-help">ตั้งชื่อและรหัสผ่านของคุณ ครั้งถัดไปใช้รหัสนักศึกษากับรหัสผ่านนี้เข้าสู่ระบบ อีเมลและเบอร์โทรเพิ่มเติมภายหลังได้</p>
<?php require __DIR__.'/../includes/errors.php'; ?>
<form method="post" action="<?= e(url('profile')) ?>" class="form-stack">
<?= csrf_field() ?>
<label>รหัสนักศึกษา<input value="<?= e($account['student_id']) ?>" readonly autocomplete="username"></label>
<label>ชื่อที่แสดง<input name="name" value="<?= e($data['name']) ?>" maxlength="150" required autocomplete="name"></label>
<label>อีเมล (ไม่บังคับ)<input type="email" name="email" value="<?= e($data['email']) ?>" maxlength="190" autocomplete="email"></label>
<label>เบอร์โทร (ไม่บังคับ)<input type="tel" name="phone" value="<?= e($data['phone']) ?>" maxlength="30" autocomplete="tel"></label>
<label>ตั้งรหัสผ่าน<input type="password" name="new_password" required minlength="12" maxlength="72" autocomplete="new-password"><small class="field-help">12–72 ตัวสำหรับอักษรอังกฤษ ตัวเลข และสัญลักษณ์</small></label>
<label>ยืนยันรหัสผ่าน<input type="password" name="confirm_password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<button type="submit" class="button primary full">บันทึกและเริ่มใช้งาน →</button>
</form></div></section>
