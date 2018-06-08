ALTER TABLE `store_orders`
    ADD `lesson` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `digital`,
    ADD `postcode` VARCHAR(5) NULL DEFAULT NULL AFTER `lesson`,
    ADD `band` VARCHAR(5) NULL DEFAULT NULL AFTER `postcode`;

ALTER TABLE `store_products`
    ADD `lesson` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `digitalloc`,
    ADD `lessonrelation` VARCHAR(30) NULL DEFAULT NULL AFTER `lesson`;