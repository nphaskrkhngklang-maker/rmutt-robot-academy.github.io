<section class="staff-academy-library">
<div class="section-heading"><div><h2>สื่อการเรียนรู้ RMUTT Robot Academy</h2><p class="muted">บทเรียน ใบงาน และกติกาจากเว็บไซต์ของทีม พร้อมภาพประกอบ เปิดอ่านในระบบได้เลย</p></div></div>
<?php $academyPages=(new Academy())->pages();foreach(['เนื้อหาการเรียนรู้','ใบงานการทดลอง','กติกากิจกรรม'] as $category): ?>
<details class="panel academy-library-category" <?= $category==='เนื้อหาการเรียนรู้'?'open':'' ?>><summary><?= e($category) ?></summary>
<div class="academy-cards">
<?php foreach($academyPages as $lesson): if($lesson['is_index']||$lesson['category']!==$category)continue; ?>
<a class="academy-card" href="<?= e(url('learning',['article'=>$lesson['id']])) ?>">
<?php if(preg_match('/<img[^>]+src="([^"]+)"/',$lesson['html'],$thumbnail)): ?><img class="academy-thumbnail" src="<?= e($thumbnail[1]) ?>" alt="" loading="lazy"><?php endif ?>
<h3><?= e($lesson['title']) ?></h3><span class="text-link">เปิดอ่าน →</span></a>
<?php endforeach ?>
</div></details><?php endforeach ?>
</section>
