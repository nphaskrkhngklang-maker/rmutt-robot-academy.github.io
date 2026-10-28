<?php
$current=$_GET['page']??'dashboard';
$sections=[
 ['schools'=>'โรงเรียน','participants'=>'รายชื่อผู้เข้าร่วม'],
 ['teams'=>'จัดทีม','readiness'=>'เช็กความพร้อม'],
 ['staff'=>'บัญชีสตาฟ','duties'=>'มอบหมายหน้าที่'],
 ['games'=>'เกมและเกณฑ์','competition'=>'จัดการแข่งขัน','scoring'=>'บันทึกคะแนน','rankings'=>'อันดับ'],
];
$aliases=['school'=>'schools','school-edit'=>'schools','registration-edit'=>'schools','staff-edit'=>'staff','score-edit'=>'scoring','score-sheet'=>'scoring'];
$current=$aliases[$current]??$current;
foreach($sections as $tabs){
 if(!isset($tabs[$current]))continue;
 if($user['role']!=='main_staff')$tabs=array_diff_key($tabs,array_flip(['schools','participants','staff']));
 if(count($tabs)<2)break;
 $params=[];if(isset($_GET['camp_id'])&&is_string($_GET['camp_id'])&&ctype_digit($_GET['camp_id']))$params['camp_id']=$_GET['camp_id'];
 ?>
 <nav class="workspace-tabs" aria-label="ส่วนที่เกี่ยวข้อง"><?php foreach($tabs as $target=>$label): ?><a href="<?= e(url($target,$params)) ?>" <?= $target===$current?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach ?></nav>
 <?php break;
}
?>
