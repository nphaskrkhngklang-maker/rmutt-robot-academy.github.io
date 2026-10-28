<div class="score-row" data-score-row>
<label>ชื่อเกณฑ์<input name="criteria[<?= e($i) ?>][name]" maxlength="100" value="<?= e($criterion['name']??'') ?>"></label>
<label>ชนิดคะแนน<select name="criteria[<?= e($i) ?>][score_type]"><option value="integer" <?= ($criterion['score_type']??'')==='integer'?'selected':'' ?>>จำนวนเต็ม</option><option value="decimal" <?= ($criterion['score_type']??'')==='decimal'?'selected':'' ?>>ทศนิยม</option></select></label>
<label>ต่ำสุด<input name="criteria[<?= e($i) ?>][min_score]" type="number" step="0.01" min="-9999999" max="9999999" value="<?= e($criterion['min_score']??'') ?>"></label>
<label>สูงสุด<input name="criteria[<?= e($i) ?>][max_score]" type="number" step="0.01" min="-9999999" max="9999999" value="<?= e($criterion['max_score']??'') ?>"></label>
<button type="button" class="text-link" data-score-remove>ลบเกณฑ์</button>
</div>
