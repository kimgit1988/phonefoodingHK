--
-- 增加菜品，分类，规格字段
--
ALTER TABLE `mos_category`
ADD COLUMN `name_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '菜品分类英文' AFTER `name`,
ADD COLUMN `name_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '菜品分类其他语言' AFTER `name_en`;

ALTER TABLE `mos_goods`
ADD COLUMN `name_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '商品名称' AFTER `name`,
ADD COLUMN `name_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '商品名称' AFTER `name_en`,
ADD COLUMN `remark_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '备注' AFTER `remark`,
ADD COLUMN `remark_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '备注' AFTER `remark_en`,
ADD COLUMN `detail_en`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情' AFTER `detail`,
ADD COLUMN `detail_other`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情' AFTER `detail_en`;

ALTER TABLE `mos_set_meal_category`
ADD COLUMN `name_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '套餐分类英文' AFTER `name`,
ADD COLUMN `name_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '套餐分类其他语言' AFTER `name_en`;

ALTER TABLE `mos_set_meal`
ADD COLUMN `name_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '套餐名称' AFTER `name`,
ADD COLUMN `name_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '套餐名称' AFTER `name_en`,
ADD COLUMN `remark_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '备注' AFTER `remark`,
ADD COLUMN `remark_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '备注' AFTER `remark_en`,
ADD COLUMN `detail_en`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情' AFTER `detail`,
ADD COLUMN `detail_other`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情' AFTER `detail_en`;

ALTER TABLE `mos_spec`
ADD COLUMN `spec_name_en`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '规格名称英文' AFTER `spec_name`,
ADD COLUMN `spec_name_other`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '规格名称其他语言' AFTER `spec_name_en`;