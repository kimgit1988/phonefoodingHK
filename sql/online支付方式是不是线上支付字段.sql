--
-- 商家关闭的支付方式
--
ALTER TABLE `mos_contact`
ADD COLUMN `offpaytype`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '商家关闭的线下支付方式json' AFTER `cleanEndTime`;