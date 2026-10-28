<div class="participant-row" data-participant-row>
    <input type="hidden" name="participants[<?= e($index) ?>][id]" value="<?= e($row['id']??'') ?>">
    <label>ประเภท<select name="participants[<?= e($index) ?>][type]" data-person-type><option value="student" <?= ($row['type']??'student')==='student'?'selected':'' ?>>นักเรียน</option><option value="teacher" <?= ($row['type']??'')==='teacher'?'selected':'' ?>>ครู</option></select></label>
    <label>ชื่อ-นามสกุล<input name="participants[<?= e($index) ?>][name]" value="<?= e($row['name']??'') ?>" maxlength="200" data-person-name placeholder="ชื่อและนามสกุล"></label>
    <label>ไซซ์เสื้อ<select name="participants[<?= e($index) ?>][shirt_size]" data-person-shirt><option value="">ยังไม่ระบุ / ครูไม่รับเสื้อ</option><?php foreach(shirt_sizes() as $size): ?><option value="<?= e($size) ?>" <?= ($row['shirt_size']??'')===$size?'selected':'' ?>><?= e($size) ?></option><?php endforeach ?></select></label>
    <button type="button" class="button secondary remove-person" data-remove-person aria-label="ลบรายชื่อนี้">ลบ</button>
</div>
