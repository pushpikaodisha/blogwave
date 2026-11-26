<?php
require_once __DIR__ . '/../db.php';
$count = (int)$db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
assert($count >= 1, "Expected seeded posts in blogwave");
echo "blogwave smoke test PASSED (posts={$count})\n";
