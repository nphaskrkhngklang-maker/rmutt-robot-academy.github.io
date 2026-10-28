<?php
declare(strict_types=1);
require __DIR__ . '/core/bootstrap.php';
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$routes = ['arenas','scoreboard','displays','live-display','student','student-login','student-logout','forgot-password','profile','delete','camp-reset','evaluation','evaluation-results','rankings','scoring','score-edit','score-sheet','competition','readiness','media','games','media-file','learning', 'home', 'camp-settings', 'login', 'logout', 'dashboard', 'camps', 'camp', 'camp-edit', 'staff', 'staff-edit', 'schools', 'school', 'school-edit', 'participants', 'registration-edit', 'register', 'registration-success', 'teams', 'duties', 'equipment', 'timeline'];
if (!in_array($page, $routes, true)) abort_request(404, 'ไม่พบหน้า', 'หน้าที่คุณต้องการไม่มีอยู่ในระบบ');
$method = $_SERVER['REQUEST_METHOD'];
$allowed = in_array($page,['logout','student-logout'],true) ? ['POST'] : (in_array($page, ['arenas','scoreboard','displays','student-login','forgot-password','profile','evaluation-results','delete','camp-reset','evaluation','rankings','score-edit','competition','readiness','media','games','login','camp-edit','staff-edit','school','school-edit','registration-edit','register','teams','duties','equipment','timeline'], true) ? ['GET','POST'] : ['GET']);
if (!in_array($method, $allowed, true)) { header('Allow: ' . implode(', ', $allowed)); abort_request(405, 'คำขอไม่ถูกต้อง', 'วิธีเรียกใช้งานหน้านี้ไม่ถูกต้อง'); }
if ($page === 'student-login') { (new StudentController())->login(); exit; }
if ($page === 'student') { (new StudentController())->index(); exit; }
if ($page === 'student-logout') { (new StudentController())->logout(); exit; }
if ($page === 'live-display') { (new ControlController())->display(); exit; }
if ($page === 'home') { (new LearningController())->index(true); exit; }
if ($page === 'learning') { (new LearningController())->index(); exit; }
if ($page === 'forgot-password') { (new PasswordHelpController())->index(); exit; }
if ($page === 'login') { (new AuthController())->login(); exit; }
if ($page === 'register') { (new RegistrationController())->index(); exit; }
if ($page === 'registration-success') { (new RegistrationController())->success(); exit; }
if ($page === 'media-file') { (new ContentController())->file(); }
if ($page === 'rankings') { (new RankingController())->index(); exit; }
if ($page === 'evaluation') { (new EvaluationController())->publicForm(); exit; }
if ($page === 'profile' && !empty($_SESSION['setup_user_id'])) { (new ProfileController())->setup(); exit; }
$user = require_user();
match ($page) {
    'arenas','scoreboard','displays'=>(new ControlController())->index($user,$page),
    'profile' => (new ProfileController())->index($user),
    'delete' => (new DeleteController())->index($user),
    'camp-reset' => (new CampResetController())->index($user),
    'evaluation-results' => (new EvaluationController())->results($user),
    'scoring' => (new ScoringController())->index($user),
    'score-edit','score-sheet' => (new ScoringController())->sheet($user,$page==='score-edit'),
    'competition' => (new CompetitionController())->index($user),
    'readiness' => (new CompetitionController())->readiness($user),
    'media','games' => (new ContentController())->index($user,$page),
    'teams', 'duties', 'equipment', 'timeline' => (new OperationsController())->index($user,$page),
    'camp-settings' => (new HomeController())->settings($user),
    'logout' => (new AuthController())->logout(),
    'dashboard' => (new DashboardController())->index($user),
    'camps' => (new CampController())->index($user),
    'camp' => (new CampController())->show($user),
    'camp-edit' => (new CampController())->edit($user),
    'staff' => (new StaffController())->index($user),
    'staff-edit' => (new StaffController())->edit($user),
    'schools' => (new SchoolController())->index($user),
    'school' => (new SchoolController())->show($user),
    'school-edit' => (new SchoolController())->edit($user),
    'participants' => (new ParticipantController())->index($user),
    'registration-edit' => (new RegistrationController())->index($user),
};
