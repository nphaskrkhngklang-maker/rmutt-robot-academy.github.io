<?php require __DIR__.'/../includes/student-navigation.php'; ?>
<section class="student-space">
<header class="student-welcome"><p>ยินดีต้อนรับเข้าค่าย</p><h1>สวัสดี <?= e($student['name']) ?></h1><small><?= e($student['school_name']) ?></small></header>
<section class="student-team-card"><div><span>ทีมของฉัน</span><h2><?= e($student['team_name']??'กำลังจัดทีมให้') ?></h2><p><?= $student['team_name']?'ไปทำกิจกรรมกับเพื่อนในทีมกันเลย':'พี่สตาฟจะแจ้งทีมให้เร็ว ๆ นี้' ?></p></div><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><rect x="12" y="20" width="40" height="32" rx="12"/><path d="M32 10v10M24 43h16M6 30v12m52-12v12"/><circle cx="24" cy="33" r="2"/><circle cx="40" cy="33" r="2"/></svg></section>
<a class="student-learn-button" href="<?= e(url('home')) ?>"><span>มาเรียนรู้เรื่องหุ่นยนต์กัน</span><strong>เริ่มเรียนรู้ →</strong></a>
<section class="panel student-schedule"><h2>กิจกรรมในค่าย</h2><?php if(!$schedule): ?><p>พี่สตาฟกำลังเตรียมตารางกิจกรรมให้นะ</p><?php endif ?><?php $studentDay=''; foreach($schedule as $task): if($studentDay!==$task['task_date']): $studentDay=$task['task_date']; ?><h3><?= e(date('j/n/Y',strtotime($studentDay))) ?></h3><?php endif ?><article class="student-activity"><time><?= e(substr($task['task_time'],0,5)) ?></time><p><?= e($task['name']) ?></p></article><?php endforeach ?></section>
</section>
