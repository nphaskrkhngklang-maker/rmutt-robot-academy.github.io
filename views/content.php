<?php $main=$user['role']==='main_staff'; $game=$kind==='games'; ?>
<div class="page-heading"><div><span class="eyebrow text-teal">LEARN & PLAY</span><h1><?= e($title) ?></h1><p><?= $game?'เตรียมกติกาและเกณฑ์คะแนนก่อนเริ่มการแข่งขัน':'เพิ่มบทเรียน คู่มือ และไฟล์ประกอบของค่าย' ?></p></div></div>
<?php require __DIR__.'/../includes/errors.php'; ?>
<?php if(!$game): require __DIR__.'/academy-library.php'; endif ?>
<?php if(!$camp): ?><section class="panel empty-state">ยังไม่มีค่ายที่เข้าถึงได้</section><?php else: ?>
<?php $filterPage=$kind;require __DIR__.'/../includes/camp-picker.php'; ?>
<?php if($main): ?><details class="panel operations-editor" <?= $editId||$errors?'open':'' ?>><summary><?= $editId?'แก้ไขรายการ':($game?'＋ เพิ่มเกม':'＋ เพิ่มสื่อ') ?></summary><div class="section-body">
<form method="post" enctype="multipart/form-data" class="form-stack">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= e($editId??'') ?>"><input type="hidden" name="version" value="<?= e($data['version']??0) ?>">
<?php if($game): ?>
<label>ชื่อเกม<input name="name" maxlength="200" required value="<?= e($data['name']??'') ?>"></label>
<label>คำอธิบาย<textarea name="description" maxlength="10000"><?= e($data['description']??'') ?></textarea></label>
<div class="form-grid"><label>จำนวนรอบ<input name="rounds" type="number" min="1" max="1000" value="<?= e($data['rounds']??'') ?>"></label><label>เวลาต่อแมตช์ (นาที)<input name="duration_minutes" type="number" min="0.01666667" max="1440" step="any" value="<?= e($data['duration_minutes']??(isset($data['duration_seconds'])?round($data['duration_seconds']/60,8):'')) ?>"></label></div>
<label>กติกา<textarea name="rules" maxlength="20000" required><?= e($data['rules']??'') ?></textarea></label>
<label>สถานะ<select name="status"><?php foreach(['draft'=>'ร่าง','active'=>'พร้อมใช้งาน','archived'=>'เก็บเข้าคลัง'] as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['status']??'draft')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select></label>
<p class="muted">เกมร่างเว้นจำนวนรอบ เวลา และเกณฑ์คะแนนไว้ก่อนได้ กรอกให้ครบก่อนตั้งเป็นพร้อมใช้งาน</p><section data-score-editor><h2>เกณฑ์คะแนน</h2><p class="muted">กำหนดช่วงคะแนนของแต่ละเกณฑ์ คะแนนหักกำหนดเป็นค่าติดลบได้</p><div data-score-rows>
<?php $criteria=is_array($data['criteria']??null)?array_values($data['criteria']):[['name'=>'','score_type'=>'integer','min_score'=>0,'max_score'=>100]];foreach($criteria as $i=>$criterion): require __DIR__.'/score-row.php'; endforeach ?>
</div><button type="button" class="button secondary" data-score-add>＋ เพิ่มเกณฑ์คะแนน</button><p data-score-notice role="status"></p>
<template id="score-template"><?php $i='__INDEX__';$criterion=['name'=>'','score_type'=>'integer','min_score'=>0,'max_score'=>100];require __DIR__.'/score-row.php'; ?></template></section>
<input type="hidden" name="criteria_complete" value="1">
<?php else: ?>
<label>ชื่อสื่อ<input name="title" maxlength="200" required value="<?= e($data['title']??'') ?>"></label>
<label>หมวดหมู่<input name="category" maxlength="100" required list="media-categories" value="<?= e($data['category']??'') ?>"><datalist id="media-categories"><option>วิธีใช้อุปกรณ์</option><option>วิธีประกอบหุ่นยนต์</option><option>กติกา</option><option>Code</option><option>คู่มือกิจกรรม</option></datalist></label>
<label>เนื้อหา<textarea name="content" rows="8" maxlength="100000"><?= e($data['content']??'') ?></textarea></label>
<label>ลิงก์เพิ่มเติม (ถ้ามี)<input name="external_url" type="url" maxlength="2000" value="<?= e($data['external_url']??'') ?>" placeholder="https://"></label>
<label>ไฟล์ประกอบ (ถ้ามี) · PDF, PNG, JPG, TXT ไม่เกิน 5 MB<input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.txt"></label>
<?php if($editId): ?><p class="muted">ไม่เลือกไฟล์ใหม่จะคงไฟล์เดิมไว้ หากบันทึกไม่สำเร็จ กรุณาเลือกไฟล์ใหม่อีกครั้ง</p><label><input type="checkbox" name="remove_file" value="1"> นำไฟล์เดิมออก</label><?php endif ?>
<label>การมองเห็น<select name="visibility"><?php foreach(['draft'=>'ร่าง — สตาฟหลักเท่านั้น','public'=>'สาธารณะ — ทุกคนเปิดดูได้','staff'=>'เฉพาะสตาฟในค่าย'] as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['visibility']??'draft')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select></label>
<?php endif ?>
<div class="form-actions"><a class="button secondary" href="<?= e(url($kind,['camp_id'=>$camp['id']])) ?>">ยกเลิก</a><button class="button primary">บันทึก<?= $game?'เกม':'สื่อ' ?></button></div>
</form></div></details><?php endif ?>

<?php if($game&&!$rows): ?><section class="panel empty-state"><h2>ยังไม่มี<?= $game?'เกม':'สื่อที่แสดงได้' ?></h2><p>รายการที่เพิ่มในค่ายนี้จะแสดงที่นี่</p></section><?php endif ?>
<?php if($game): ?><section class="panel table-wrap"><table><thead><tr><th>ชื่อกิจกรรม</th><th>รอบ / เวลา</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody><?php foreach($rows as $event): ?><tr><td><?= e($event['name']) ?></td><td><?= e($event['rounds']??'—') ?> รอบ · <?= isset($event['duration_seconds'])?e(round($event['duration_seconds']/60,2)):'—' ?> นาที</td><td><?= e(['draft'=>'ร่าง','active'=>'พร้อมใช้งาน','archived'=>'เก็บเข้าคลัง'][$event['status']]) ?></td><td><a class="button secondary" href="<?= e(url('competition',['game_id'=>$event['id'],'camp_id'=>$camp['id']])) ?>">จัดการแข่งขัน</a> <?php if($main): ?><a class="text-link" href="<?= e(url('games',['edit'=>$event['id'],'camp_id'=>$camp['id']])) ?>">แก้ไข</a> <?= delete_link('games',(int)$event['id']) ?><?php endif ?></td></tr><?php endforeach ?></tbody></table></section><details><summary>ดูกติกาและสื่อประกอบทั้งหมด</summary><?php endif ?><div class="ops-card-grid">
<?php foreach($rows as $item): ?><article class="panel ops-card">
<h2><?= e($item[$game?'name':'title']) ?></h2>
<?php if($game): ?><span class="badge <?= $item['status']==='active'?'active':'draft' ?>"><?= e(['draft'=>'ร่าง','active'=>'พร้อมใช้งาน','archived'=>'เก็บเข้าคลัง'][$item['status']]) ?></span><p>รอบ: <?= e($item['rounds']??'ยังไม่กำหนด') ?> · เวลา: <?= $item['duration_seconds']!==null?e(round($item['duration_seconds']/60,4)).' นาที':'ยังไม่กำหนด' ?></p><p class="description"><?= e($item['description']) ?></p><p><a class="text-link" href="<?= e(url('competition',['camp_id'=>$camp['id'],'game_id'=>$item['id']])) ?>">เปิดตารางแข่งขัน →</a></p><details class="game-rules"><summary>กติกาและเกณฑ์คะแนน (<?= count($item['criteria']) ?>)</summary><p class="description"><?= e($item['rules']) ?></p><ul><?php foreach($item['criteria'] as $c): ?><li><strong><?= e($c['name']) ?></strong> · <?= e($c['min_score']??'ยังไม่กำหนด') ?> ถึง <?= e($c['max_score']??'ยังไม่กำหนด') ?> (<?= $c['score_type']==='integer'?'จำนวนเต็ม':'ทศนิยม' ?>)</li><?php endforeach ?></ul></details>
<?php else: ?><p class="muted"><?= e($item['category']) ?> · <?= e(['draft'=>'ร่าง','public'=>'สาธารณะ','staff'=>'เฉพาะสตาฟ'][$item['visibility']]) ?></p><a class="text-link" href="<?= e(url('learning',['article'=>'media-'.$item['id']])) ?>">เปิดอ่าน →</a><?php endif ?>
<?php if($game&&!empty($item['source_article'])): $sourceArticle=null;foreach((new Academy())->pages() as $sourcePage)if($sourcePage['id']===$item['source_article']){$sourceArticle=$sourcePage;break;} if($sourceArticle): ?><details class="game-rules"><summary>สื่อต้นฉบับ · ภาพกติกาและวิดีโอ</summary><article class="academy-article"><?= $sourceArticle['html'] ?></article><a class="text-link" href="<?= e(url('learning',['article'=>$sourceArticle['id']])) ?>">เปิดสื่อเต็มหน้า →</a></details><?php endif;endif ?>
<?php if($main): ?><div class="ops-actions"><a class="text-link" href="<?= e(url($kind,['camp_id'=>$camp['id'],'edit'=>$item['id']])) ?>">แก้ไข →</a> <?= delete_link($kind,(int)$item['id']) ?></div><?php endif ?>
</article><?php endforeach ?></div><?php if($game): ?></details><?php endif ?>
<?php endif ?>
