--
-- 增加服务费字段和补充茶位费备注
--
ALTER TABLE `mos_contact`
MODIFY COLUMN `fee`  smallint(6) NULL DEFAULT 0 COMMENT '每人茶位费' AFTER `isDelete`,
MODIFY COLUMN `is_cover_charge`  tinyint(4) NULL DEFAULT 0 COMMENT '是否收茶位费' AFTER `fee`,
ADD COLUMN `service_fee`  decimal(6,2) NULL DEFAULT 0 COMMENT '服务费率' AFTER `is_cover_charge`,
ADD COLUMN `is_service_fee`  tinyint(1) NULL DEFAULT 0 COMMENT '是否收服务费' AFTER `service_fee`;