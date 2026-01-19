<?php

namespace website\bundles;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot/modules/website/assets';
    public $baseUrl = '@web/modules/website/assets';
    public $css = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css',
        "api/css/style.css",
    ];

    public $js = [
        'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/json.min.js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js',
        'api/js/dexie.min.js',
        'api/js/storage.js',
        'api/js/documentation.js'
    ];

    public $jsOptions = [
        'position' => \yii\web\View::POS_END
    ];
}
