--
-- MySQL database dump
-- Created by DbManage class, Power By zhonchengcheng. 
-- https://github.com/lovezhao311 
--
-- 主机: localhost
-- 生成日期: 2016 年  10 月 31 日 14:07
-- MySQL版本: 5.6.26
-- PHP 版本: 5.6.13

--
-- 数据库: `pal`
--

-- -------------------------------------------------------

--
-- 表的结构article
--

DROP TABLE IF EXISTS `article`;
CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '所属分类',
  `title` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `attr` int(10) NOT NULL,
  `keyword` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '关键词',
  `description` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '描述',
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '缩略图',
  `content` text CHARACTER SET utf8mb4,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='文章发布';

--
-- 转存表中的数据 article
--

INSERT INTO `article` VALUES 
('4','0','你是我的小呀小苹果1','3','歌曲','你是我的小呀小苹果','/public/uploads/20161014/fc0264025f8d223159976f43d2c3946d.png','<p>11111111111111111111</p>','1476424441','1477647190')
,('6','1','1111111111111111111111111111111','4','1111111111111111111111111111111111111','11111111111111111111111111','/public/uploads/20161022/b760862b3ed8678494c0b87b1f306dfd.png','<p>11111111111111111111111111111111111111111111111111111</p>','1477125813','0')
;