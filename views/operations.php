<?php $main=$user['role']==='main_staff'; $groupNames=array_column($groups,'name','id'); $teamNames=array_column($teams,'name','id'); $peopleNames=array_column($people,'name','id'); ?>
<div class="page-heading"><div><span class="eyebrow text-teal">CAMP OPERATIONS</span><h1><?= e($title) ?></h1><p>เตรียมทีมงานและกิจกรรมของค่าย</p></div></div>
<?php require __DIR__.'/../includes/errors.php'; ?>
<?php if(!$camp): ?><section class="panel empty-state"><h2>ยังไม่มีค่ายที่เข้าถึงได้</h2><p>กรุณาสร้างค่าย หรือให้สตาฟหลักเพิ่มสิทธิ์เข้าค่าย</p></section><?php else: ?>
<?php $filterPage=$module; require __DIR__.'/../includes/camp-picker.php'; ?>
<?php if($module==='teams'): require __DIR__.'/teams.php'; else: ?>
<?php if($main): ?><details class="panel operations-editor" <?= $editId || $errors?'open':'' ?>><summary><?= $editId?'แก้ไขรายการ':'＋ เพิ่มรายการ' ?></summary><div class="section-body"><?php require __DIR__.'/operations-form.php'; ?></div></details><?php endif ?>
<?php if(!$rows): ?><section class="panel empty-state"><h3>ยังไม่มีรายการในค่ายนี้</h3><p><?= $main?'เพิ่มรายการเพื่อเตรียมการดำเนินค่าย':'รายการที่สตาฟหลักสร้างจะแสดงที่นี่' ?></p></section><?php endif ?>
<?php if($module==='equipment'): require __DIR__.'/equipment-checklist.php'; else: ?><div class="ops-card-grid"><?php foreach($rows as $item): ?>
<article class="panel ops-card">
<?php if($module==='duties'): ?>
<h2><?= e($peopleNames[$item['user_id']]??'บัญชีปิดใช้งาน / ไม่มีสิทธิ์ค่าย') ?></h2><p class="description"><?= e($item['description']!==''?$item['description']:($groupNames[$item['group_id']]??$teamNames[$item['sub_team_id']]??$item['field_name'])) ?></p>
<?php elseif($module==='equipment'): ?>
<span class="eyebrow text-teal"><?= e($item['category']) ?></span><h2><?= e($item['name']) ?></h2><p class="muted">จำนวน <?= e($item['quantity']) ?></p><span class="badge <?= $item['status']==='checked'?'active':($item['status']==='checking'?'draft':'') ?>"><?= e(equipment_statuses()[$item['status']]) ?></span>
<form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="version" value="<?= e($item['version']) ?>"><label>สถานะการตรวจ<select name="status"><?php foreach(equipment_statuses() as $k=>$v): ?><option value="<?= e($k) ?>" <?= $item['status']===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach ?></select></label><label>หมายเหตุ<textarea name="notes" maxlength="2000" rows="2"><?= e($item['notes']) ?></textarea></label><button class="button secondary">บันทึกการตรวจ</button></form>
<?php else: ?>
<span class="eyebrow text-teal"><?= e(task_phases()[$item['phase']]) ?></span><h2><?= e($item['name']) ?></h2><p><?= e($item['task_date']) ?> · <?= e(substr($item['task_time'],0,5)) ?> น.</p><p class="muted">ผู้รับผิดชอบ: <?= e(!empty($item['all_staff'])?'ทุกคน':($peopleNames[$item['user_id']]??'บัญชีปิดใช้งาน / ไม่มีสิทธิ์ค่าย')) ?></p><p class="description"><?= e($item['description']) ?></p><span class="badge <?= $item['status']==='done'?'active':'draft' ?>"><?= e(task_statuses()[$item['status']]) ?></span>
<?php if($main || !empty($item['all_staff']) || (int)$item['user_id']===(int)$user['id']): ?><form method="post" class="compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="version" value="<?= e($item['version']) ?>"><button class="button secondary" name="status" value="<?= $item['status']==='done'?'not_done':'done' ?>"><?= $item['status']==='done'?'เปลี่ยนเป็นยังไม่เสร็จ':'ทำเสร็จแล้ว' ?></button></form><?php endif ?>
<?php endif ?>
<?php if($main): ?><div class="ops-actions"><a class="text-link" href="<?= e(url($module,['camp_id'=>$camp['id'],'edit'=>$item['id']])) ?>">แก้ไขรายการ →</a> <?= delete_link($module,(int)$item['id']) ?></div><?php endif ?>
</article><?php endforeach ?></div>
<?php endif; endif; endif ?>
