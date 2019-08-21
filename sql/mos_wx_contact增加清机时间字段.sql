--
-- 增加清机时间字段
--
ALTER TABLE `mos_contact`
ADD COLUMN `cleanStartTime`  time NULL DEFAULT '00:00:01' COMMENT '清机开始时间 如00:00:01' AFTER `is_service_fee`,
ADD COLUMN `cleanEndTime`  time NULL DEFAULT '00:00:01' COMMENT '清机结束时间 如23:59:59' AFTER `cleanStartTime`;