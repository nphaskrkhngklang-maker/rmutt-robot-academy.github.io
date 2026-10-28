<?php
$activePage=$_GET['page']??'dashboard';
$aliases=['camp-reset'=>'camp-settings','camp'=>'camp-settings','camp-edit'=>'camp-settings','staff-edit'=>'staff','school'=>'schools','school-edit'=>'schools','registration-edit'=>'schools','score-edit'=>'scoring','score-sheet'=>'scoring'];
$activePage=$aliases[$activePage]??$activePage;
$activePage=['games'=>'competition','scoring'=>'competition','rankings'=>'competition','participants'=>'schools','readiness'=>'teams','duties'=>($user['role']==='main_staff'?'staff':'duties')][$activePage]??$activePage;
$groups=[
 'เตรียมค่ายและผู้เข้าร่วม'=>['◎',['camp-settings'=>'ข้อมูลค่าย','staff'=>'สตาฟและหน้าที่','duties'=>'หน้าที่สตาฟ','schools'=>'โรงเรียนและรายชื่อ','teams'=>'ทีมและความพร้อม']],
 'เตรียมกิจกรรม'=>['▣',['timeline'=>'กำหนดการ','equipment'=>'อุปกรณ์']],
 'สื่อการเรียนรู้'=>['▣',['media'=>'สื่อการเรียนรู้']],
 'การแข่งขัน'=>['◇',['competition'=>'การแข่งขัน']],
 'ความคิดเห็น'=>['◈',['evaluation-results'=>'บอร์ดความคิดเห็น']]
];
if($user['role']==='main_staff')unset($groups['เตรียมค่ายและผู้เข้าร่วม'][1]['duties']);
$mainOnly=['staff','schools','participants','camp-settings','evaluation-results'];
?>
<nav aria-label="เมนูหลัก">
<a class="nav-link <?= $activePage==='dashboard'?'active':'' ?>" <?= $activePage==='dashboard'?'aria-current="page"':'' ?> href="<?= e(url('dashboard')) ?>"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>ภาพรวม</a>
<?php foreach($groups as $label=>[$icon,$links]):
if($user['role']!=='main_staff')$links=array_diff_key($links,array_flip($mainOnly));
if(!$links)continue;
$selected=isset($links[$activePage]);
if(count($links)===1): $singleKey=array_key_first($links); ?>
<div class="sidebar-group sidebar-single"><a class="nav-link <?= $selected?'active':'' ?>" <?= $selected?'aria-current="page"':'' ?> data-search-keywords="<?= $singleKey==='competition'?'เกม เกณฑ์ คะแนน อันดับ สนาม':'' ?>" href="<?= e(url($singleKey==='competition'?'games':$singleKey)) ?>"><?= e($label) ?></a></div>
<?php continue; endif ?>
<details class="sidebar-group" <?= $selected?'open':'' ?> name="sidebar-sections">
<summary class="<?= $selected?'has-current':'' ?>"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php if($icon==='⚑'): ?><path d="M4 21V4l8-2 8 2v17M9 21v-5h6v5M8 7h1m6 0h1M8 11h1m6 0h1"/><?php elseif($icon==='◎'): ?><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0112 0v3M16 5a3 3 0 010 6m2 4a5 5 0 013 4v2"/><?php elseif($icon==='▣'): ?><rect x="4" y="5" width="16" height="16" rx="3"/><path d="M8 3v4m8-4v4M4 11h16M8 15h3m-3 3h7"/><?php elseif($icon==='◇'): ?><path d="M8 3h8v6a4 4 0 01-8 0V3Zm0 2H4v3a4 4 0 004 4m8-7h4v3a4 4 0 01-4 4m-4 1v5m-4 3h8m-6-3h4"/><?php else: ?><path d="M4 3v18h17M8 16v-4m5 4V7m5 9v-6"/><?php endif ?></svg><span><?= e($label) ?></span><span class="sidebar-chevron" aria-hidden="true">›</span></summary>
<div class="sidebar-submenu"><?php foreach($links as $key=>$text): ?>
<a class="nav-link <?= $activePage===$key?'active':'' ?>" <?= $activePage===$key?'aria-current="page"':'' ?> data-search-keywords="<?= e(['schools'=>'รายชื่อ นักเรียน ครู','teams'=>'ความพร้อม เช็ก ทีมหลัก ทีมย่อย','staff'=>'หน้าที่ มอบหมาย','competition'=>'เกม เกณฑ์ คะแนน อันดับ สนาม'][$key]??'') ?>" href="<?= e(url($key)) ?>"><?= e($text) ?></a>
<?php endforeach ?></div></details>
<?php endforeach ?>
<form method="post" action="<?= e(url('logout')) ?>" class="sidebar-signout"><?= csrf_field() ?><button type="submit" class="nav-link sidebar-logout"><span aria-hidden="true">↪</span>ออกจากระบบ</button></form>
</nav>
