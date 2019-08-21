set charset utf8;
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
insert into `article`(`id`,`category_id`,`title`,`attr`,`keyword`,`description`,`thumbnail`,`content`,`create_time`,`update_time`) values('4','0','你是我的小呀小苹果1','3','歌曲','你是我的小呀小苹果','/public/uploads/20161014/fc0264025f8d223159976f43d2c3946d.png','<p>11111111111111111111</p>','1476424441','1477647190'

);

insert into `article`(`id`,`category_id`,`title`,`attr`,`keyword`,`description`,`thumbnail`,`content`,`create_time`,`update_time`) values('6','1','1111111111111111111111111111111','4','1111111111111111111111111111111111111','11111111111111111111111111','/public/uploads/20161022/b760862b3ed8678494c0b87b1f306dfd.png','<p>11111111111111111111111111111111111111111111111111111</p>','1477125813','0'

);

CREATE TABLE `articlecat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET utf8mb4 NOT NULL COMMENT '标题',
  `description` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '简介',
  `keyword` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '关键词',
  `sort` int(11) NOT NULL DEFAULT '255' COMMENT '排序',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='文章分类';
insert into `articlecat`(`id`,`parent_id`,`title`,`description`,`keyword`,`sort`,`create_time`,`update_time`) values('1','0','体育1','体育,shan,mqq','体育,shan,mqq','1','1465203618','1477553310'

);

insert into `articlecat`(`id`,`parent_id`,`title`,`description`,`keyword`,`sort`,`create_time`,`update_time`) values('3','1','篮球1','篮球,科比,詹姆斯','篮球,科比,詹姆斯','4','1465265950','1476687454'

);

insert into `articlecat`(`id`,`parent_id`,`title`,`description`,`keyword`,`sort`,`create_time`,`update_time`) values('4','3','php','我就是pgper','php','5','0','1476687468'

);

insert into `articlecat`(`id`,`parent_id`,`title`,`description`,`keyword`,`sort`,`create_time`,`update_time`) values('5','4','呵呵','22232','1111','33','1476686735','0'

);

insert into `articlecat`(`id`,`parent_id`,`title`,`description`,`keyword`,`sort`,`create_time`,`update_time`) values('6','0','mysql','11231243','mysql','2','1476686805','1476687442'

);

CREATE TABLE `attr` (
  `aid` int(10) NOT NULL,
  `attr_name` varchar(10) NOT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章属性';
insert into `attr`(`aid`,`attr_name`) values('1','头条'

);

insert into `attr`(`aid`,`attr_name`) values('2','推荐'

);

insert into `attr`(`aid`,`attr_name`) values('3','热点'

);

CREATE TABLE `focus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_id` int(11) NOT NULL COMMENT '焦点图所属位置',
  `title` varchar(100) CHARACTER SET utf8mb4 NOT NULL COMMENT '焦点图标题',
  `url` varchar(255) CHARACTER SET utf8mb4 NOT NULL COMMENT '焦点图链接地址',
  `image` varchar(255) CHARACTER SET utf8mb4 NOT NULL COMMENT '图片',
  `remark` tinytext CHARACTER SET utf8mb4 COMMENT '焦点图说明',
  `status` tinyint(4) NOT NULL COMMENT '是否可用',
  `sort` int(11) NOT NULL COMMENT '排序',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `position` (`position_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='焦点图';
insert into `focus`(`id`,`position_id`,`title`,`url`,`image`,`remark`,`status`,`sort`,`create_time`,`update_time`) values('4','1','国庆专题','http://www.baidu.com','/public/uploads/focus/20161026/b5d09b000628e357ba3245f26570e0a0.jpg','banner','1','255','1477470698','0'

);

CREATE TABLE `focus_position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 NOT NULL COMMENT '调用代码',
  `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL COMMENT '名称',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='焦点图位置';
insert into `focus_position`(`id`,`code`,`name`,`create_time`,`update_time`) values('1','index_1','首页第一p','1464751294','1477470428'

);

insert into `focus_position`(`id`,`code`,`name`,`create_time`,`update_time`) values('2','list_1','列表页底部','1477470224','0'

);

insert into `focus_position`(`id`,`code`,`name`,`create_time`,`update_time`) values('5','article_1','文章页','1477619775','0'

);

CREATE TABLE `groupdata` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `rules` char(180) NOT NULL DEFAULT '',
  `remark` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='用户组权限';
insert into `groupdata`(`id`,`title`,`rules`,`remark`) values('1','管理员','12,2,1,3,9,4,5,6,7,10,11','zcczcczcc'

);

insert into `groupdata`(`id`,`title`,`rules`,`remark`) values('2','管理员1','12,55,2,1,3,9,13,53,4,5,6,7,17,18,19,20,29,30,31,32,33,34,35,36,37,38,39,54,10,11,14,15,16,21,22,23,24,25,26,27,28,40,41,42,43,44,45,46,47,48,49,50,51,52','钟程程'

);

insert into `groupdata`(`id`,`title`,`rules`,`remark`) values('4','文章编辑','1,7,2,9,13','编写文章！上传                       '

);

CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 NOT NULL COMMENT 'logo',
  `linker` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '联系人说明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1 开启 0 关闭',
  `sort` int(10) NOT NULL DEFAULT '255' COMMENT '排序 ',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='友情 链接';
insert into `links`(`id`,`title`,`url`,`logo`,`linker`,`status`,`sort`,`create_time`,`update_time`) values('1','京东','http://www.sino.com','\\static\\links_logo\\2016-06-01\\8672b9290390e6e026fe570cd145647d.png','QQ:36363636','1','255','1464745978','1477452180'

);

CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级',
  `title` varchar(100) CHARACTER SET utf8mb4 NOT NULL COMMENT '标题',
  `description` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '简介',
  `keyword` varchar(100) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '关键字',
  `content` text CHARACTER SET utf8mb4 COMMENT '内容',
  `sort` int(11) NOT NULL COMMENT '排序',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='单页面';
insert into `page`(`id`,`parent_id`,`title`,`description`,`keyword`,`content`,`sort`,`create_time`,`update_time`) values('1','0','公司介绍','','公司介绍','<h2 style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';background-color:#FFFFFF;\">
	公司介绍
</h2>
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">上海顶想信息科技有限公司（TOPThink Inc.）是国内领先的WEB应用和服务提供商致力于WEB应用平台、产品和应用的研发和服务，为企事业单位提供基于WEB的应用开发快速解决方案和产品。公司成立于2008年9月，是一家拥有自主知识产权的高新企业。</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司长期专注于WEB应用框架、应用平台和企业解决方案的研究，公司的核心技术框架ThinkPHP由创始人刘晨于2006年创立，经过7年多的精心打造和发展，具有广泛的用户基础和良好的业内口碑，已经成长为国内领先和最具影响力的WEB应用开发框架，国外同比也具有相当大的优势。其应用领域分布于各个行业，在门户、社区和电子商务领域有着非常良好支持以及拓展，大小案例不下千家，在安全、效率、负载上都有很大优势，已经成为WEB应用的快速开发解决方案和最佳实践！</span><br />
<br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司总部位于上海，由从事互联网和用户体验研究达10年的资深专家领军，拥有一批专业的策划、设计和技术团队以及广泛的社区技术力量。公司长期以来凭借业内的影响力、良好的客户和合作关系，邀请了众多安全和项目专家作为顾问，有力得保证了客户项目的开发和实施。公司还拥有一支资深用户体验和设计研究队伍，针对不同用户量身定做用户体验流程，有着良好的产品设计和设计概念。</span><br />
<br />
<br />
<h2 style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';background-color:#FFFFFF;\">
	公司理念
</h2>
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司理念：专业源于专注，细节决定成败。</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">我们的口号是：WE CAN DO IT ,JUST THINK !</span><br />
<br />
<br />
<h2 style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';background-color:#FFFFFF;\">
	公司服务
</h2>
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">专业网站策划开发</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">企业业务系统定制</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">为企业应用提供一系列的解决方案</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">ThinkPHP认证培训</span><br />
<br />
<br />
<h2 style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';background-color:#FFFFFF;\">
	公司优势
</h2>
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">ThinkPHP 6年的用户基础和口碑</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">日益增加的典型案例</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">具有技术优势的团队</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">广泛的合作资源，包括：校园、企业、培训和媒体</span><br />
<br />
<br />
<h2 style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';background-color:#FFFFFF;\">
	公司价值观
</h2>
<b>合作</b><span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">&nbsp;—— 团队合作，共同成长</span><br />
<b>专业</b><span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">&nbsp;—— 提倡专业素质</span><br />
<b>专注</b><span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">&nbsp;—— 成为某个领域的专家</span><br />
<b>创新</b><span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">&nbsp;—— 持续创新，不断改进</span><br />
<b>服务</b><span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">&nbsp;—— 给客户最满意的服务而不是产品</span><br />
<b>贡献</b><span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">&nbsp;—— 有贡献就有收获</span><br />
<br />
<br />
<h2 style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';background-color:#FFFFFF;\">
	我们的客户
</h2>
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">国家保密局 中青旅 联想中国 美特斯邦威 腾讯家居 凤凰家居 56.Com 星巴克 宝矿力水特 特力屋 奔驰 莆田在线 都市客 商虎网 泡面三国 三国英雄传 贵州便民网 中国西部开发网 中华家园网 记忆日 互动日程 魔力岛 巨人网络 灵通集团…</span><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">更多典型案例演示：</span><a href=\"http://www.thinkphp.cn/Case/\" target=\"_blank\">http://www.thinkphp.cn/Case/&nbsp;</a><br />
<br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">社 区：</span><a href=\"http://thinkphp.cn/\" target=\"_blank\">http://thinkphp.cn/</a><br />
<span style=\"color:#323232;font-family:\'Century Gothic\', \'Microsoft yahei\';font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司官网：</span><a href=\"http://topthink.net/\" target=\"_blank\">http://topthink.net/</a>','255','1464939566','1464939566'

);

insert into `page`(`id`,`parent_id`,`title`,`description`,`keyword`,`content`,`sort`,`create_time`,`update_time`) values('2','1','关于我们','关于我们 ','关于我们','<h2 style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;background-color:#FFFFFF;\">&nbsp; &nbsp;
	公司介绍</h2><p><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">上海顶想信息科技有限公司（TOPThink Inc.）是国内领先的WEB应用和服务提供商致力于WEB应用平台、产品和应用的研发和服务，为企事业单位提供基于WEB的应用开发快速解决方案和产品。公司成立于2008年9月，是一家拥有自主知识产权的高新企业。</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司长期专注于WEB应用框架、应用平台和企业解决方案的研究，公司的核心技术框架ThinkPHP由创始人刘晨于2006年创立，经过7年多的精心打造和发展，具有广泛的用户基础和良好的业内口碑，已经成长为国内领先和最具影响力的WEB应用开发框架，国外同比也具有相当大的优势。其应用领域分布于各个行业，在门户、社区和电子商务领域有着非常良好支持以及拓展，大小案例不下千家，在安全、效率、负载上都有很大优势，已经成为WEB应用的快速开发解决方案和最佳实践！</span><br/><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司总部位于上海，由从事互联网和用户体验研究达10年的资深专家领军，拥有一批专业的策划、设计和技术团队以及广泛的社区技术力量。公司长期以来凭借业内的影响力、良好的客户和合作关系，邀请了众多安全和项目专家作为顾问，有力得保证了客户项目的开发和实施。公司还拥有一支资深用户体验和设计研究队伍，针对不同用户量身定做用户体验流程，有着良好的产品设计和设计概念。</span><br/><br/><br/></p><h2 style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;background-color:#FFFFFF;\">&nbsp; &nbsp;
	公司理念</h2><p><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司理念：专业源于专注，细节决定成败。</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">我们的口号是：WE CAN DO IT ,JUST THINK !</span><br/><br/><br/></p><h2 style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;background-color:#FFFFFF;\">&nbsp; &nbsp;
	公司服务</h2><p><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">专业网站策划开发</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">企业业务系统定制</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">为企业应用提供一系列的解决方案</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">ThinkPHP认证培训</span><br/><br/><br/></p><h2 style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;background-color:#FFFFFF;\">&nbsp; &nbsp;
	公司优势</h2><p><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">ThinkPHP 6年的用户基础和口碑</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">日益增加的典型案例</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">具有技术优势的团队</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">广泛的合作资源，包括：校园、企业、培训和媒体</span><br/><br/><br/></p><h2 style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;background-color:#FFFFFF;\">&nbsp; &nbsp;
	公司价值观</h2><p><strong>合作</strong><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\"> —— 团队合作，共同成长</span><br/><strong>专业</strong><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\"> —— 提倡专业素质</span><br/><strong>专注</strong><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\"> —— 成为某个领域的专家</span><br/><strong>创新</strong><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\"> —— 持续创新，不断改进</span><br/><strong>服务</strong><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\"> —— 给客户最满意的服务而不是产品</span><br/><strong>贡献</strong><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\"> —— 有贡献就有收获</span><br/><br/><br/></p><h2 style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;background-color:#FFFFFF;\">&nbsp; &nbsp;
	我们的客户</h2><p><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">国家保密局 中青旅 联想中国 美特斯邦威 腾讯家居 凤凰家居 56.Com 星巴克 宝矿力水特 特力屋 奔驰 莆田在线 都市客 商虎网 泡面三国 三国英雄传 贵州便民网 中国西部开发网 中华家园网 记忆日 互动日程 魔力岛 巨人网络 灵通集团…</span><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">更多典型案例演示：</span><a href=\"http://www.thinkphp.cn/Case/\" target=\"_blank\">http://www.thinkphp.cn/Case/ </a><br/><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">社 区：</span><a href=\"http://thinkphp.cn/\" target=\"_blank\">http://thinkphp.cn/</a><br/><span style=\"color:#323232;font-family:&#39;Century Gothic&#39;, &#39;Microsoft yahei&#39;;font-size:16px;line-height:35.2px;background-color:#FFFFFF;\">公司官网：</span><a href=\"http://topthink.net/\" target=\"_blank\">http://topthink.net/</a></p>','25','1464944062','1464944962'

);

insert into `page`(`id`,`parent_id`,`title`,`description`,`keyword`,`content`,`sort`,`create_time`,`update_time`) values('7','0','产品技术','产品技术','产品技术','<p>11111111111111111</p>','25','0','0'

);

CREATE TABLE `rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `name` char(80) NOT NULL DEFAULT '',
  `title` char(20) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `class` varchar(15) NOT NULL,
  `sort` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 COMMENT='控制器方法菜单';
insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('1','2','Article/index','文章管理','1','sa-side-typogra','2'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('2','0','Content','内容管理','1','sa-side-widget','2'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('3','1','Article/add','文章添加','1','','3'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('4','2','Articlecat/index','栏目管理','1','','4'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('5','4','Articlecat/edit','栏目编辑','1','','5'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('6','4','Articlecat/add','栏目添加','1','','6'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('7','4','Articlecat/destroy','栏目删除','1','','7'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('9','1','Article/destroy','文章删除','1','','9'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('10','0','Rule','权限管理','1','sa-side-page','9'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('11','10','Groupdata/index','用户组','1','','10'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('12','0','Home','Home','1','sa-side-home','1'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('14','11','Groupdata/add','添加用户组','1','','34'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('13','1','Article/edit','文章编辑','1','','66'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('15','11','Groupdata/detele','删除用户组','1','','3'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('16','11','Groupdata/edit','编辑用户组','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('17','2','Attr/index','文章属性','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('18','17','Attr/add','添加属性','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('19','17','Attr/edit','编辑属性','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('20','17','Attr/destroy','删除属性','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('21','10','Rule/index','权限菜单','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('22','21','Rule/add','添加权限','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('23','21','Rule/edit','菜单编辑','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('24','21','Rule/delete','菜单删除','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('25','10','User/index','用户列表','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('26','25','User/add','添加用户','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('27','25','User/edit','用户编辑','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('28','25','User/destroy','删除用户','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('29','2','Page/index','单页管理','1','','1'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('30','29','Page/add','添加单页','1','','1'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('31','29','Page/destroy','删除单页','1','','3'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('32','29','Page/edit','编辑单页','1','','2'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('33','0','system','系统设置','1','sa-side-ui','2'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('34','33','Databases/index','数据库操作','1','','1'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('35','34','Databases/optimize','优化表','1','','33'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('36','34','Databases/repair','修复表','1','','3'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('37','34','Databases/backup','数据备份','1','','4'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('38','34','Databases/backdata','备份数据库','1','','5'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('39','34','Databases/leaddata','导入sql文件','1','','43'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('40','0','Other Functions','功能管理','1','sa-side-folder','43'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('41','40','Links/index','友情链接','1','','34'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('42','41','Links/add','添加链接','1','','23'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('43','41','Links/edit','编辑链接','1','','34'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('44','41','Links/destroy','删除链接','1','','34'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('45','40','Focus/index','焦点图列表','1','','1'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('46','45','Focus/add','添加焦点图','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('47','45','Focus/edit','编辑焦点图','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('48','45','Focus/destroy','删除焦点图','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('49','40','Focusposition/index','焦点图位置','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('50','49','Focusposition/add','添加位置','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('51','49','Focusposition/edit','编辑位置','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('52','49','Focusposition/destroy','删除位置','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('53','1','Article/searcharticle','栏目文章','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('54','33','Systemlog/index','系统日志','1','','0'

);

insert into `rule`(`id`,`parent_id`,`name`,`title`,`status`,`class`,`sort`) values('55','12','Index/index','后台主页','1','','1'

);

CREATE TABLE `system_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '登录用户id',
  `remark` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '简单说明',
  `ip` varchar(20) CHARACTER SET utf8mb4 NOT NULL COMMENT 'IP地址',
  `op_time` int(11) NOT NULL COMMENT '时间',
  PRIMARY KEY (`id`),
  KEY `userId` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COMMENT='后台操作日志表';
insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('1','2','焦点图位置添加,ID:[1]','::1','0'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('2','2','编辑焦点图位置,ID:[6]','::1','0'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('3','2','焦点图位置添加,ID:[1]','::1','1477620057'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('4','2','删除焦点图位置,ID:[7]','::1','1477620182'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('5','2','编辑权限菜单,ID:[53]','::1','1477620932'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('6','2','修改单页面：[2]','::1','1477621379'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('7','2','登陆成功:[zcc]','::1','1477621736'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('8','2','文章编辑,ID:[4]','::1','1477622846'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('9','2','编辑权限菜单,ID:[2]','::1','1477624281'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('10','2','编辑权限菜单,ID:[33]','::1','1477624316'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('11','2','编辑权限菜单,ID:[40]','::1','1477624385'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('12','2','编辑权限菜单,ID:[10]','::1','1477624451'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('13','2','添加权限菜单,ID:[1]','::1','1477642222'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('14','2','用户组修改:[2]','::1','1477642282'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('15','2','优化表[article]','::1','1477646846'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('16','2','文章编辑,ID:[4]','::1','1477647190'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('17','2','登陆成功:[admin]','202.101.161.156','1477879263'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('18','2','退出登录:[2]','202.101.161.156','1477879624'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('19','2','登陆成功:[admin]','202.101.161.156','1477879640'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('20','2','退出登录:[2]','202.101.161.156','1477880351'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('21','2','登陆成功:[admin]','202.101.161.156','1477880360'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('22','2','个人信息修改:[9]','202.101.161.156','1477880772'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('23','2','退出登录:[9]','202.101.161.156','1477880779'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('24','2','登陆成功:[admin]','202.101.161.156','1477880811'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('25','2','登陆成功:[admin]','202.101.161.156','1477892690'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('26','2','编辑权限菜单,ID:[12]','202.101.161.156','1477892818'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('27','2','添加权限菜单,ID:[1]','202.101.161.156','1477893122'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('28','2','用户组修改:[2]','202.101.161.156','1477893185'

);

insert into `system_log`(`id`,`user_id`,`remark`,`ip`,`op_time`) values('29','2','修复表[article]','202.101.161.156','1477894029'

);

CREATE TABLE `user` (
  `zid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(32) CHARACTER SET utf8mb4 NOT NULL COMMENT '用户姓名',
  `password` varchar(64) CHARACTER SET utf8mb4 NOT NULL COMMENT '用户密码',
  `status` tinyint(5) NOT NULL COMMENT '是否启用',
  `head` varchar(150) CHARACTER SET utf8mb4 DEFAULT NULL,
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`zid`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='后台用户表';
insert into `user`(`zid`,`uid`,`name`,`password`,`status`,`head`,`create_time`,`update_time`) values('1','1','? luffy丶橡皮人','4297f44b13955235245b2497399d7a93','1','\\static\\head\\20160612\\dda72ecf5ca829ae586141b46a9721f0.png','1465703454','1477537084'

);

insert into `user`(`zid`,`uid`,`name`,`password`,`status`,`head`,`create_time`,`update_time`) values('2','2','zcc','123456','1','/head/20161022/f384cc2d9df3723cc383b265e3e2d642.png','1461232560','1477129131'

);

insert into `user`(`zid`,`uid`,`name`,`password`,`status`,`head`,`create_time`,`update_time`) values('9','2','admin','admin12345','1','/head/20161031/21bed7a6e30f932e52352922aa787a9f.png','1477879226','1477880772'

);

