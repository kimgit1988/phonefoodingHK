ALTER TABLE `mos_contact`
ADD COLUMN `laterPay`  tinyint(1) NULL DEFAULT 0 COMMENT '允许飯后支付' AFTER `autoOrder`;

