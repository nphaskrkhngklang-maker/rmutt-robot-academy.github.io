<section class="registration-complete">
    <div class="complete-icon" aria-hidden="true">✓</div>
    <span class="eyebrow text-teal">REGISTRATION COMPLETE</span>
    <h1>ลงทะเบียนสำเร็จแล้ว</h1>
    <p class="complete-intro">ส่งข้อมูลให้ทีมงานเรียบร้อยแล้ว<br>ขอบคุณที่ร่วมเป็นส่วนหนึ่งของค่ายกับเราค่ะ</p>
    <section class="panel complete-summary">
        <div class="section-heading"><div><h2><?= e($school['name']) ?></h2><p><?= e($school['camp_name']) ?></p></div><span class="badge active">ส่งข้อมูลแล้ว</span></div>
        <div class="section-body">
            <?php require __DIR__.'/../includes/registration-summary.php'; ?>
            <?php if($school['submitted_at']): ?><p class="field-help">บันทึกการส่งล่าสุด <?= e((new DateTimeImmutable($school['submitted_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('d/m/Y H:i')) ?> น. (เวลาไทย)</p><?php endif ?>
        </div>
    </section>
    <div class="complete-next"><h2>เรียบร้อยแล้ว ปิดหน้านี้ได้เลย</h2><p>หากต้องการตรวจสอบหรือแก้ไขรายชื่อ<br>กลับมาใช้ลิงก์เดิมได้ตราบใดที่ลิงก์ลงทะเบียนยังเปิดอยู่</p></div>
    <a class="button primary" href="<?= e(url('register',['token'=>$token])) ?>">กลับไปตรวจสอบ / แก้ไขข้อมูล →</a>
    <p class="complete-help">หากต้องการความช่วยเหลือ กรุณาติดต่อสตาฟหลักของค่าย</p>
</section>
