<?php
declare(strict_types=1);
class LearningController
{
    public function index(bool $home=false): void {
        $pages=(new Academy())->pages();$article=null;
        $repo=new ContentService();$viewer=current_user();
        $custom=db()->query('SELECT * FROM learning_media ORDER BY id DESC')->fetchAll();
        foreach($custom as $row){
            if(!$repo->visible($row,$viewer))continue;
            $html='<span class="eyebrow text-teal">'.e($row['category']).'</span><p class="description">'.e($row['content']).'</p>';
            if($row['external_url'])$html.='<p><a href="'.e($row['external_url']).'" target="_blank" rel="noopener noreferrer">เปิดลิงก์ประกอบ ↗</a></p>';
            if($row['file_key'])$html.='<p><a class="button secondary" href="'.e(url('media-file',['id'=>$row['id']])).'">ดาวน์โหลดไฟล์ประกอบ</a></p>';
            $pages[]=['id'=>'media-'.$row['id'],'title'=>$row['title'],'category'=>'สื่อของค่าย','html'=>$html,'is_index'=>false,'custom'=>true,'url'=>'','pending_images'=>0];
        }
        if(!$home){
            $id=is_string($_GET['article']??null)?$_GET['article']:'';
            foreach($pages as $item)if($item['id']===$id){$article=$item;break;}
            if(!$article)abort_request(404,'ไม่พบบทเรียน','กรุณาเลือกบทเรียนจากเมนูสื่อการเรียนรู้');
        }
        $campName=db()->query("SELECT name FROM camps WHERE status<>'cancelled' ORDER BY (status='active') DESC,start_date DESC,id DESC LIMIT 1")->fetchColumn() ?: 'RoBot Camp';
        require __DIR__.'/../views/academy.php';
    }
}
