<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Article\Providers\ArticleServiceProvider::class,
    Modules\Workflow\Providers\WorkflowServiceProvider::class,
    Modules\Maison\Providers\MaisonServiceProvider::class,
    Modules\User\Providers\UserServiceProvider::class,
];
