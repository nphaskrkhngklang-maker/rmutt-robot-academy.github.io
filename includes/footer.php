<?php if ($user): ?><?php if($user['role']==='main_staff'): ?><div class="reset-page-bottom"><a class="button secondary reset-camp-link" href="<?= e(url('camp-reset')) ?>">↻ เริ่มรอบใหม่</a></div><?php endif ?></main><footer class="page-footer">RoBot Camp Management <span>จัดการค่ายอย่างเป็นระบบ</span></footer></div></div><?php else: ?></main><?php endif ?>
</body></html>
