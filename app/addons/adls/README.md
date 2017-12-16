ALTER TABLE `cscart_adls_release_links`
ADD COLUMN `productId`  mediumint(8) UNSIGNED NOT NULL AFTER `releaseId`;

ALTER TABLE `cscart_adls_release_links`
MODIFY COLUMN `licenseId`  mediumint(8) UNSIGNED NOT NULL DEFAULT 0 AFTER `productId`,
MODIFY COLUMN `subscriptionId`  mediumint(8) UNSIGNED NOT NULL DEFAULT 0 AFTER `licenseId`;