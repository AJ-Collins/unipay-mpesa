<?php
$this->title = "API Documentation";
$this->registerJsVar('appName', strtolower(Yii::$app->id)."-docs-console-DB");
?>
<!-- Main -->
<main class="main-content" id="main" role="main">
    <div id="content-area" aria-live="polite">Loading...</div>
</main>
<!-- Default Environment Modal -->
<div class="modal fade" id="defaultEnvModal" tabindex="-1" aria-labelledby="defaultEnvModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="defaultEnvModalLabel">Default Environment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="envList"></div>
            </div>
        </div>
    </div>
</div>
<!-- Schema Modal -->
<div class="modal fade" id="schemaModal" tabindex="-1" aria-labelledby="schemaModalLabel">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="schemaModalLabel">Schema</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="schemaModalBody" style="max-height:70vh; overflow:auto;"></div>
        </div>
    </div>
</div>
<!-- Save Request Modal -->
<div class="modal fade" id="saveRequestModal" tabindex="-1" aria-labelledby="saveRequestModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveRequestModalLabel">Save Request</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input id="saveReqName" class="form-control mb-2" placeholder="Collection name / request name" aria-describedby="save-req-help" />
                <small id="save-req-help" class="form-text text-muted">Name for the saved request.</small>
                <button class="btn btn-primary" onclick="saveCurrentRequest()">Save</button>
            </div>
        </div>
    </div>
</div>