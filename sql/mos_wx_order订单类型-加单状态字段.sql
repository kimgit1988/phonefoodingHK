--
-- 增加订单类型,加单状态字段
--
ALTER TABLE `mos_wx_order`
ADD COLUMN `addStatus`  int(1) NULL DEFAULT 0 COMMENT '是不是用户加单：默认0不是，1是' AFTER `isDelete`,
ADD COLUMN `orderType`  int(1) NULL DEFAULT 1 COMMENT '订单类型：默认1堂食，2外带，3外卖' AFTER `addStatus`;

ALTER TABLE `mos_wx_order_goods`
ADD COLUMN `addStatus`  int(1) NULL DEFAULT 0 COMMENT '是不是用户加单菜品：默认0不是，1是' AFTER `goodsType`;