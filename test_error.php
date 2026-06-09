<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\Article\Models\ArticleVersion;
try {
    $version = new ArticleVersion();
    $version->article_id = 'c7e8e9ea-722a-4db5-b8dc-7f5b854b73b2';
    $version->numero_version = 1;
    $version->titre = 'Test';
    $version->contenu = 'Test content';
    $version->cree_par = 'c7e8e9ea-722a-4db5-b8dc-7f5b854b73b2';
    $version->save();
} catch (\Throwable $e) {
    echo $e->getMessage();
}
