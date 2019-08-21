--
-- 增加一个商家公告表
--
CREATE TABLE `mos_notice` (
`id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
`number`  varchar(50) NOT NULL COMMENT '商家编号' ,
`content`  varchar(512) NULL DEFAULT NULL COMMENT '公告内容' ,
`background`  int(2) NULL DEFAULT 0 COMMENT '背景颜色编号' ,
`updatetime`  int(11) NULL DEFAULT 0 COMMENT '公告修改时间' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;