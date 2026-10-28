<div class="page-heading"><div><span class="eyebrow text-teal">TEAM READINESS</span><h1>ความพร้อมทีม</h1><p>Ready / Not Ready เป็นข้อมูลประกอบ ทุกทีมยังอยู่ในการสุ่มตามปกติ</p></div></div>
<?php require __DIR__.'/../includes/errors.php'; ?>
<?php if($camp): $filterPage='readiness';require __DIR__.'/../includes/camp-picker.php'; endif ?>
<?php if(!$rows): ?><section class="panel empty-state">ยังไม่มีทีมในค่ายที่เลือก</section><?php endif ?>
<section class="panel equipment-checklist">
<?php foreach($rows as $row): ?><article class="equipment-row"><div class="equipment-line"><div class="equipment-name"><h2><?= e($row['name']) ?></h2><small><?= e($row['group_name']) ?></small></div><span class="badge <?= $row['is_ready']?'active':'draft' ?>"><?= $row['is_ready']?'Ready · พร้อม':'Not Ready · ยังไม่พร้อม' ?></span>
<?php if($repo->canEdit($user,(int)$camp['id'],(int)$row['id'])): ?>
<form method="post" data-readiness-form><?= csrf_field() ?><input type="hidden" name="camp_id" value="<?= e($camp['id']) ?>"><input type="hidden" name="team_id" value="<?= e($row['id']) ?>"><input type="hidden" name="version" value="<?= e($row['version']) ?>"><input type="hidden" name="ready" value="<?= $row['is_ready']?0:1 ?>"><button class="button secondary"><?= $row['is_ready']?'เปลี่ยนเป็นยังไม่พร้อม':'เปลี่ยนเป็นพร้อม' ?></button><span data-readiness-notice role="status"></span></form>
<?php endif ?></div></article><?php endforeach ?>
</section>
