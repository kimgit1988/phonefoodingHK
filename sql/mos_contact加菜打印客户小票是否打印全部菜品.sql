--
-- 加菜订单打印的客户小票是否包含全部菜品：1是，0不是
--
ALTER TABLE `mos_contact`
ADD COLUMN `addOrderPrint`  tinyint(1) NULL DEFAULT 0 COMMENT '加菜订单打印的客户小票是否包含全部菜品：1是，0不是' AFTER `laterPay`;

