<?php
require "config.php";

$sql = "
    ALTER TABLE `user`
    DROP COLUMN `status`,
    ADD COLUMN `site` VARCHAR(100) DEFAULT NULL AFTER `role`
";

$pdo->exec($sql);

echo "Table updated successfully";
