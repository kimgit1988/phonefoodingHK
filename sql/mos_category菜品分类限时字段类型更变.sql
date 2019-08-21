ALTER TABLE `mos_category`
MODIFY COLUMN `startTime`  time NULL DEFAULT 0 COMMENT '显示开始时间 如09:00:00' AFTER `contactNumber`,
MODIFY COLUMN `endTime`  time NULL DEFAULT 0 COMMENT '显示结束时间时间戳 如23:59:59' AFTER `startTime`;