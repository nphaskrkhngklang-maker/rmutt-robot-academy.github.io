<?php $checkedCount=count(array_filter($rows,fn($row)=>$row['status']==='checked')); ?>
<?php if($rows): ?>
<div class="equipment-progress"><strong>ตรวจแล้ว <?= $checkedCount ?> / <?= count($rows) ?> รายการ</strong><span>กดช่องสี่เหลี่ยมเพื่อบันทึกหรือยกเลิกการตรวจ</span><progress max="<?= count($rows) ?>" value="<?= $checkedCount ?>" aria-label="ความคืบหน้าการตรวจอุปกรณ์"></progress></div>
<section class="panel equipment-checklist" aria-label="รายการตรวจอุปกรณ์">
<?php foreach($rows as $item): $checked=$item['status']==='checked'; ?>
<article class="equipment-row">
<div class="equipment-line">
<form method="post" class="equipment-toggle"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="version" value="<?= e($item['version']) ?>"><input type="hidden" name="notes" value="<?= e($item['notes']) ?>"><button type="submit" role="checkbox" aria-checked="<?= $checked?'true':'false' ?>" aria-label="<?= e(($checked?'ยกเลิกการตรวจ: ':'ตรวจแล้ว: ').$item['name']) ?>" name="status" value="<?= $checked?'not_checked':'checked' ?>" class="equipment-check <?= $checked?'is-checked':'' ?>"><span aria-hidden="true"><?= $checked?'✓':'' ?></span></button></form>
<div class="equipment-name"><h2><?= e($item['name']) ?></h2><small><?= e($item['category']) ?> · จำนวน <?= e($item['quantity']) ?></small></div>
<span class="badge <?= $checked?'active':($item['status']==='checking'?'draft':'') ?>"><?= e(equipment_statuses()[$item['status']]) ?></span>
</div>
<details class="equipment-details" <?= $errors && (string)($data['id']??'')===(string)$item['id']?'open':'' ?>><summary>รายละเอียด<?= $item['notes']?' · มีหมายเหตุ':'' ?></summary>
<form method="post" class="form-stack compact-form"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="version" value="<?= e($item['version']) ?>"><label>สถานะการตรวจ<select name="status"><?php foreach(equipment_statuses() as $k=>$v): ?><option value="<?= e($k) ?>" <?= $item['status']===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach ?></select></label><label>หมายเหตุ<textarea name="notes" maxlength="2000" rows="2"><?= e($item['notes']) ?></textarea></label><div class="equipment-detail-actions"><button class="button secondary">บันทึกรายละเอียด</button><?php if($main): ?><a class="text-link" href="<?= e(url($module,['camp_id'=>$camp['id'],'edit'=>$item['id']])) ?>">แก้ไขรายการ →</a> <?= delete_link($module,(int)$item['id']) ?><?php endif ?></div></form>
</details>
</article>
<?php endforeach ?>
</section>
<?php endif ?>
