<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
function competition_api(callable $handler):never {
    header('Content-Type: application/json; charset=utf-8');
    try{
        if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');http_response_code(405);echo json_encode(['error'=>'ใช้คำขอ POST เท่านั้น']);exit;}
        $user=current_user();
        if(!$user){http_response_code(401);echo json_encode(['error'=>'กรุณาเข้าสู่ระบบใหม่']);exit;}
        if(!isset($_POST['csrf'])||!is_string($_POST['csrf'])||!hash_equals(csrf(),$_POST['csrf'])){http_response_code(419);echo json_encode(['error'=>'แบบฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่']);exit;}
        $camp=(new RandomizationService())->number($_POST['camp_id']??null,1,PHP_INT_MAX);
        if(!(new Camp())->findFor($camp,$user)){http_response_code(403);echo json_encode(['error'=>'ไม่มีสิทธิ์เข้าถึงค่ายนี้']);exit;}
        echo json_encode($handler($user,$camp),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }catch(DomainException $e){http_response_code(422);echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
    catch(Throwable $e){error_log((string)$e);http_response_code(500);echo json_encode(['error'=>'ไม่สามารถดำเนินการได้ กรุณาลองใหม่'],JSON_UNESCAPED_UNICODE);}
    exit;
}
