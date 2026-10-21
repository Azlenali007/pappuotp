<?php
$userId = (int)($_GET['user_id'] ?? 1);
header("Location: /admin/transactions.php?user_id=" . $userId);
exit;
