<?php if(count($camps)<=1) return; ?>
<form method="get" class="filter-bar">
    <input type="hidden" name="page" value="<?= e($filterPage) ?>">
    <label>ค่ายที่ต้องการจัดการ<select name="camp_id"><?php foreach($camps as $option): ?><option value="<?= e($option['id']) ?>" <?= $camp && $camp['id']===$option['id']?'selected':'' ?>><?= e($option['name']) ?></option><?php endforeach ?></select></label>
    <button class="button secondary">แสดงข้อมูล</button>
</form>
