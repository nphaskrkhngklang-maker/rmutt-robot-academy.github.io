<?php if($draft): ?>
<section class="panel competition-preview">
<span class="badge draft">ตัวอย่าง · ยังไม่ยืนยัน</span><h2>รอบ <?= e($draft['round_no']) ?> · <?= e($draft['game']['name']) ?></h2>
<p><?= $draft['config']['unit']==='group'?'ทีมหลัก':'ทีมย่อย' ?> · <?= $draft['config']['rule']==='different_only'?'ต่างทีมหลักเท่านั้น':'อนุญาตทีมหลักเดียวกัน' ?> · <?= e($draft['config']['fields']) ?> สนาม · สูงสุด <?= e($draft['config']['per_field']) ?> หน่วย / แมตช์</p>
<p>ครบ <?= count($draft['pool']) ?> หน่วย · <?= count($draft['matches']) ?> แมตช์ · เวลา <?= e(round($draft['game']['duration_seconds']/60,4)) ?> นาที / แมตช์</p>
<?php if(array_filter($draft['matches'],fn($m)=>count($m['entries'])<$draft['config']['per_field'])): ?><div class="alert">จำนวนหน่วยหารไม่ลงตัว จึงกระจายเป็นแมตช์ที่มีจำนวนน้อยกว่าที่ตั้งไว้ ทุกหน่วยยังต้องแข่งครบ ไม่มีการให้ผ่านรอบอัตโนมัติ</div><?php endif ?>
<div class="competition-queue"><?php foreach($draft['matches'] as $match): ?><div class="competition-slot"><small>คิว <?= e($match['queue_no']) ?> · สนาม <?= e($match['field_id']) ?></small><strong><?= e(implode(' vs ',array_column($match['entries'],'name'))) ?></strong></div><?php endforeach ?></div>
<form method="post" action="<?= e(url('competition',['camp_id'=>$draft['camp_id'],'game_id'=>$draft['game_id']])) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="draft_id" value="<?= e($draft['id']) ?>"><?php foreach($draft['config'] as $key=>$value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach ?><button class="button primary">ยืนยันตารางแข่งขัน</button></form><p class="muted">หากต้องการเปลี่ยนผล กด “สุ่มใหม่ / ดูตัวอย่าง” ด้านบนก่อนยืนยัน · ตัวอย่างมีอายุ 30 นาที</p>
</section>
<?php endif ?>
