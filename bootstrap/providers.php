<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    Modules\Core\Providers\CoreServiceProvider::class, // ← attention à la casse et aux backslashes
];
