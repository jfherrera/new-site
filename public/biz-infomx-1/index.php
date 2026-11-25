<?php
// Old funnel URL. Permanent redirect to the new lookup page; the old ?email= query is dropped on purpose.
header('Location: /biz-lookup/', true, 301);
exit;
