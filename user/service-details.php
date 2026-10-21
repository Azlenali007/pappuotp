<?php
$serviceId = (int)($_GET['id'] ?? $_GET['service_id'] ?? 1);
header("Location: /user/buy-number.php?service_id=" . $serviceId);
exit;
