<?php
declare(strict_types=1);

class DashboardController
{
    public function index(array $user): void {
        $camp=operation_camp($user);
        render('dashboard', ['camp'=>$camp,'summary'=>$camp?(new DashboardService())->summary((int)$camp['id']):null,'title' => 'ภาพรวม', 'user' => $user, 'camps' => (new Camp())->allFor($user), 'staff' => $user['role'] === 'main_staff' ? (new User())->all() : []] + (new Operations())->mine($user));
    }
}
