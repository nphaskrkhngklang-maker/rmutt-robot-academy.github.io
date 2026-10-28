<section class="rra-signin">
<header class="rra-signin-brand"><div class="rra-signin-emblem" aria-hidden="true">R</div><h1>RRA 2026</h1><p>ระบบจัดการแข่งขันและกิจกรรมค่าย</p></header>
<div class="rra-signin-card">
<?php require __DIR__.'/../includes/errors.php'; ?>
<form method="post" action="<?= e(url('login',$adminMode?['mode'=>'admin']:[])) ?>" class="form-stack">
<?= csrf_field() ?><input type="hidden" name="step" value="<?= $passwordStep?'password':'identify' ?>">
<?php if($passwordStep): ?>
<div class="signin-identity"><?= e($email) ?> <a href="<?= e(url('login')) ?>">เปลี่ยนบัญชี</a></div>
<input type="hidden" name="email" value="<?= e($email) ?>">
<label>รหัสผ่าน<input name="password" type="password" autocomplete="current-password" required autofocus></label>
<?php else: ?>
<label><?= $adminMode?'อีเมลสตาฟหลัก':'รหัสนักศึกษา' ?><input name="email" type="<?= $adminMode?'email':'text' ?>" autocomplete="username" maxlength="190" value="<?= e($email) ?>" required autofocus></label>
<?php endif ?>
<button type="submit" class="button primary full">เข้าสู่ระบบ</button>
</form>
</div>
<div class="rra-signin-links"><a href="<?= e(url('student-login')) ?>">สำหรับนักเรียน</a><a href="<?= e(url('forgot-password')) ?>">ลืมรหัสผ่าน?</a></div>
<div class="rra-signin-links"><a href="<?= e(url('home')) ?>">กลับหน้าเว็บไซต์</a><a href="<?= e(url('login',$adminMode?[]:['mode'=>'admin'])) ?>"><?= $adminMode?'ใช้รหัสนักศึกษา':'เข้าสู่ระบบด้วยอีเมล' ?></a></div>
<button class="rra-theme-button" type="button" data-rra-theme aria-label="สลับโหมดสว่างและมืด">☀</button>
</section>
