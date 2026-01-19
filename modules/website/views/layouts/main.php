<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use website\bundles\AppAsset;
use yii\helpers\Url;

AppAsset::register($this);
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= Url::to('@web/favicon.ico') ?>">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <?php $this->head() ?>

</head>

<body class="page-docs" style="zoom: 1;">
    <?php $this->beginBody() ?>
    <!-- TOP NAV -->
    <nav id="topbar" class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top sticky-top-shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" onclick="renderOverview()" role="button" tabindex="0" aria-label="API Dashboard Overview"><?= Yii::$app->name ?> Dev Console</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavCollapse" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNavCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" onclick="renderOverview()" role="button" tabindex="0">Overview</a></li>
                    <li class="nav-item"><a class="nav-link" onclick="showModels()" role="button" tabindex="0">Models</a></li>
                    <li class="nav-item"><a class="nav-link" onclick="renderWebSocketTester()" role="button" tabindex="0">WebSocket</a></li>
                    <li class="nav-item"><a class="nav-link" onclick="showHistory()" role="button" tabindex="0">History</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <small class="text-muted"><span id="proxyStatus" class="badge bg-secondary ms-2">Proxy: Unknown</span></small>
                        <button class="btn btn-outline-secondary btn-sm ms-1" onclick="openDefaultEnvModal()" aria-label="Change environment"><span id="envLabel" aria-live="polite">dev</span></button>
                    </div>
                    <div class="dropdown me-2">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" id="authDropdown" data-bs-toggle="dropdown" aria-expanded="false">Auth</button>
                        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:320px;">

                            <div class="mb-2">
                                <label class="form-label mb-1">Basic Auth</label>
                                <div class="input-group">
                                    <input id="authBasicUser" class="form-control form-control-sm" placeholder="username">
                                    <input id="authBasicPass" type="password" class="form-control form-control-sm" placeholder="password">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-1" for="authBearer">Bearer Token</label>
                                <input id="authBearer" class="form-control form-control-sm" placeholder="token" aria-describedby="bearer-help">
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-1" for="authApiKey">API Key (header)</label>
                                <input id="authApiKey" class="form-control form-control-sm" placeholder="x-api-key value" aria-describedby="apikey-help">
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-primary" onclick="applyAuth()">Apply</button>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Settings</button>
                        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:320px;">
                            <div class="mb-2">
                                <label class="form-label mb-1" for="openApiUrlInput">OpenAPI Docs URL</label>
                                <?= Html::dropDownList(
                                    'apiDocs',
                                    null,            // selected value
                                    $this->context->docs ?? [],
                                    ['class' => 'form-select form-select-sm', 'id' => 'openApiUrlInput']
                                ); ?>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-1" for="corsProxyInput">CORS Proxy (optional)</label>
                                <input id="corsProxyInput" class="form-control form-control-sm" placeholder="https://your-cors-proxy/" aria-describedby="cors-help" />
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-secondary btn-sm" onclick="resetConfig()">Reset</button>
                                <button class="btn btn-sm btn-primary" onclick="saveSettings()">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <aside class="sidebar" id="sidebar"></aside>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>