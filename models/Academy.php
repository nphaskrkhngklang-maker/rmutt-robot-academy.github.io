<?php
declare(strict_types=1);
class Academy
{
    public function pages(): array {
        $catalog=json_decode(file_get_contents(__DIR__.'/../content/academy.json'),true,512,JSON_THROW_ON_ERROR);
        return $catalog['pages'];
    }
}
