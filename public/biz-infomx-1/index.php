<?php
// Old funnel URL. The canonical-host check in bootstrap sends go.* and www.* requests to kissreviews.com.
// On any other host (staging), this sends the visitor to the new lookup page.
require_once __DIR__ . '/../../app/bootstrap.php';
redirect_to('/biz-lookup/', 301);
