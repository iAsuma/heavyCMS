/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : base_admin

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-04-22 15:12:59
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for admin_user
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '用户姓名',
  `login_name` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL COMMENT '登录手机号',
  `email` varchar(50) DEFAULT NULL COMMENT '登录邮箱',
  `password` varchar(32) DEFAULT NULL,
  `head_img` varchar(200) DEFAULT NULL COMMENT '用户头像',
  `status` tinyint(2) DEFAULT NULL COMMENT '状态 1 正常 0 待审核 -1 删除 -2 冻结',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `create_by` int(11) DEFAULT NULL COMMENT '创建人id',
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of admin_user
-- ----------------------------
INSERT INTO `admin_user` VALUES ('1', '管理员', 'admin', '', '', 'b8c6551bbe8f6f6e653b2bc854b24379', null, '1', '1556601911', '0', '');
INSERT INTO `admin_user` VALUES ('2', '阿斯玛', 'asuma', '', 'sqiu_li@163.com', 'b8c6551bbe8f6f6e653b2bc854b24379', null, '1', null, null, null);
INSERT INTO `admin_user` VALUES ('3', '', 'test', '', 'test@123.com', 'b8c6551bbe8f6f6e653b2bc854b24379', null, '-1', '1587537661', '1', null);

-- ----------------------------
-- Table structure for application_config
-- ----------------------------
DROP TABLE IF EXISTS `application_config`;
CREATE TABLE `application_config` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(30) DEFAULT NULL COMMENT '应用名称',
  `app_id` varchar(32) DEFAULT NULL,
  `app_secret` varchar(64) DEFAULT NULL,
  `app_token` varchar(64) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT '应用类型 1 公众号 2 小程序',
  `mch_id` varchar(32) DEFAULT NULL,
  `partnerkey` varchar(64) DEFAULT NULL,
  `cert_path` varchar(255) DEFAULT NULL,
  `key_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of application_config
-- ----------------------------

-- ----------------------------
-- Table structure for articles
-- ----------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT '文章标题',
  `sub_title` varchar(500) DEFAULT NULL,
  `content` longtext COMMENT '内容',
  `author` varchar(20) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL COMMENT '状态 1正常 0 待审核 -1删除 -2 冻结',
  `column_id` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL COMMENT '发布时间',
  `cover_imgs` varchar(1000) DEFAULT NULL,
  `author_user_id` int(11) DEFAULT '0' COMMENT '投稿人ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of articles
-- ----------------------------

-- ----------------------------
-- Table structure for article_column
-- ----------------------------
DROP TABLE IF EXISTS `article_column`;
CREATE TABLE `article_column` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL COMMENT '状态 1 正常 -2 关闭 -1 删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of article_column
-- ----------------------------

-- ----------------------------
-- Table structure for auth_group
-- ----------------------------
DROP TABLE IF EXISTS `auth_group`;
CREATE TABLE `auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 开启 -2 关闭 -1 删除',
  `rules` varchar(200) NOT NULL DEFAULT '',
  `remark` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of auth_group
-- ----------------------------
INSERT INTO `auth_group` VALUES ('1', '超级管理员', '1', 'all', '最高管理员权限，拥有所有权限');
INSERT INTO `auth_group` VALUES ('2', '管理员', '1', '1,2,3,4,5,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,33,37,38,39,40,41,42,43,44,45', '拥有除权限管理、操作日志外的所有权限');

-- ----------------------------
-- Table structure for auth_group_access
-- ----------------------------
DROP TABLE IF EXISTS `auth_group_access`;
CREATE TABLE `auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of auth_group_access
-- ----------------------------
INSERT INTO `auth_group_access` VALUES ('1', '1');
INSERT INTO `auth_group_access` VALUES ('2', '2');
INSERT INTO `auth_group_access` VALUES ('3', '2');

-- ----------------------------
-- Table structure for auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL DEFAULT '',
  `title` char(20) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL COMMENT '规则类型 1 模块 2 子模块 3 节点',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 正常 -2 关闭 -1删除',
  `condition` char(100) NOT NULL DEFAULT '',
  `sorted` smallint(1) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '上级ID',
  `run_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '表现类型 1 普通 2 异步',
  `is_menu` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否菜单 1 是 0 否',
  `icon` varchar(30) DEFAULT NULL,
  `is_logged` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否记录日志 1 是 0 否',
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of auth_rule
-- ----------------------------
INSERT INTO `auth_rule` VALUES ('1', 'index', '首页', '1', '1', '', '0', '0', '1', '1', 'fa-home', '0', '');
INSERT INTO `auth_rule` VALUES ('2', 'Panel/index', '控制台', '3', '1', '', '0', '1', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('3', 'AuthSet', '权限', '1', '1', '', '999', '0', '1', '1', 'fa-universal-access', '0', '');
INSERT INTO `auth_rule` VALUES ('4', 'AuthSet/admins', '后台管理员', '3', '1', '', '1', '3', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('5', 'AuthSet/roles', '角色管理', '3', '1', '', '2', '3', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('6', 'AuthSet/permissions', '权限管理', '3', '1', '', '3', '3', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('7', 'AuthSet/operationLog', '操作日志', '3', '1', '', '4', '3', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('17', 'Shop-goods', '商品管理', '2', '1', '', '2', '16', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('16', 'Shop', '微商城', '1', '1', '', '6', '0', '1', '1', 'fa-shopping-bag', '0', '');
INSERT INTO `auth_rule` VALUES ('18', 'Shop/goods', '商品列表', '3', '1', '', '0', '17', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('19', 'Goods/create', '发布商品', '3', '1', '', '2', '17', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('20', 'Shop/classification', '商品分类', '3', '1', '', '3', '17', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('21', 'Order', '订单管理', '2', '1', '', '3', '16', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('22', 'Order/index', '订单列表', '3', '1', '', '0', '21', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('23', 'Order/returnOrder', '退货/退款管理', '3', '1', '', '0', '21', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('24', 'Shop-main', '首页管理', '2', '1', '', '1', '16', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('25', 'Shop/banner', '轮播图', '3', '1', '', '0', '24', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('26', 'Shop/recommended', '推荐位', '3', '1', '', '0', '24', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('27', 'User', '用户管理', '1', '1', '', '50', '0', '1', '1', 'fa-user', '0', '');
INSERT INTO `auth_rule` VALUES ('28', 'User/index', '用户列表', '3', '1', '', '0', '27', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('29', 'BasisSet', '基础设置', '1', '1', '', '100', '0', '1', '1', 'fa-gear', '0', '');
INSERT INTO `auth_rule` VALUES ('30', 'BasisSet/appset', '应用配置', '3', '1', '', '0', '29', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('31', 'SystemSet/userinfo', '系统资料', '3', '1', '', '0', '29', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('33', 'Shop/goodsSku', 'SKU详情列表', '3', '1', '', '1', '17', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('37', 'Contents', '内容管理', '1', '1', '', '1', '0', '1', '1', 'fa-book', '0', '');
INSERT INTO `auth_rule` VALUES ('38', 'Contents/articles', '文章管理', '3', '1', '', '0', '37', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('39', 'Contents/column', '栏目管理', '3', '1', '', '0', '37', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('40', 'Element', '组件管理', '1', '1', '', '0', '0', '1', '1', 'fa-cube', '0', '');
INSERT INTO `auth_rule` VALUES ('41', 'Element/banner', '轮播图', '3', '1', '', '0', '40', '1', '1', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('42', 'AuthSet/adminEdit', '添加/编辑管理员', '3', '1', '', '0', '3', '1', '0', '', '0', '');
INSERT INTO `auth_rule` VALUES ('43', 'AuthSet/changeAdminStatus', '修改管理员状态', '3', '1', '', '99', '3', '2', '0', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('44', 'AuthSet/roleAdd', '添加/编辑角色', '3', '1', '', '0', '3', '1', '0', 'fa-circle-thin', '0', '');
INSERT INTO `auth_rule` VALUES ('45', 'AuthSet/changeRoleStatus', '修改角色状态', '3', '1', '', '99', '3', '2', '0', 'fa-circle-thin', '0', '');

-- ----------------------------
-- Table structure for banners
-- ----------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT NULL,
  `landing_url` varchar(800) DEFAULT NULL,
  `img` varchar(200) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1' COMMENT ' 1 正常 -1删除',
  `sorted` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of banners
-- ----------------------------

-- ----------------------------
-- Table structure for operation_log
-- ----------------------------
DROP TABLE IF EXISTS `operation_log`;
CREATE TABLE `operation_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `auth_name` varchar(50) NOT NULL DEFAULT '' COMMENT '权限标识',
  `auth_title` varchar(80) DEFAULT NULL COMMENT '权限名称',
  `auth_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '行为描述',
  `ip` varchar(80) NOT NULL DEFAULT '',
  `record_time` datetime NOT NULL,
  `behavior_user` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of operation_log
-- ----------------------------
INSERT INTO `operation_log` VALUES ('1', 'Login/checklogin', '登录', '登录页登录系统', '127.0.0.1', '2020-04-22 14:16:39', 'admin');
INSERT INTO `operation_log` VALUES ('2', 'AuthSet/pulladmin', '权限', '添加了管理员test', '127.0.0.1', '2020-04-22 14:41:01', 'admin');
INSERT INTO `operation_log` VALUES ('3', 'Login/checklogin', '登录', '登录页登录系统', '127.0.0.1', '2020-04-22 14:41:26', 'admin');
INSERT INTO `operation_log` VALUES ('4', 'Login/checklogin', '登录', '登录页登录系统', '127.0.0.1', '2020-04-22 14:41:39', 'test');
INSERT INTO `operation_log` VALUES ('5', 'AuthSet/updateadmin', '权限', '修改了管理员test的信息', '127.0.0.1', '2020-04-22 14:49:13', 'test');
INSERT INTO `operation_log` VALUES ('6', 'AuthSet/changeadminstatus', '权限', '冻结了管理员asuma的账号', '127.0.0.1', '2020-04-22 14:54:30', 'test');
INSERT INTO `operation_log` VALUES ('7', 'AuthSet/changeadminstatus', '权限', '开启了管理员asuma的账号', '127.0.0.1', '2020-04-22 14:54:31', 'test');
INSERT INTO `operation_log` VALUES ('8', 'AuthSet/changerolestatus', '权限', '关闭了角色组管理员', '127.0.0.1', '2020-04-22 14:59:27', 'test');
INSERT INTO `operation_log` VALUES ('9', 'AuthSet/changerolestatus', '权限', '开启了角色组管理员', '127.0.0.1', '2020-04-22 14:59:28', 'test');
INSERT INTO `operation_log` VALUES ('10', 'AuthSet/addnewrole', '权限', '修改了角色组管理员的信息', '127.0.0.1', '2020-04-22 15:01:49', 'admin');
INSERT INTO `operation_log` VALUES ('11', 'AuthSet/changeadminstatus', '权限', '删除了管理员test', '127.0.0.1', '2020-04-22 15:12:47', 'admin');

-- ----------------------------
-- Table structure for shop_banner
-- ----------------------------
DROP TABLE IF EXISTS `shop_banner`;
CREATE TABLE `shop_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '标题',
  `img` varchar(200) NOT NULL COMMENT '图片',
  `landing_url` varchar(500) DEFAULT NULL COMMENT '着陆页地址',
  `sorted` int(255) NOT NULL DEFAULT '0',
  `status` tinyint(4) DEFAULT NULL COMMENT '状态 1正常 -1删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_banner
-- ----------------------------

-- ----------------------------
-- Table structure for shop_classification
-- ----------------------------
DROP TABLE IF EXISTS `shop_classification`;
CREATE TABLE `shop_classification` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品分类表',
  `name` varchar(30) NOT NULL,
  `pid` int(11) DEFAULT '0' COMMENT '父级ID',
  `main_img` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_classification
-- ----------------------------

-- ----------------------------
-- Table structure for shop_goods
-- ----------------------------
DROP TABLE IF EXISTS `shop_goods`;
CREATE TABLE `shop_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品表',
  `goods_name` varchar(255) DEFAULT NULL,
  `classification_id` int(11) DEFAULT '0' COMMENT '分类id',
  `goods_sku_attributes` varchar(3000) NOT NULL COMMENT '例子：[["尺寸",["120x150cm"]],["颜色",["红","黄蓝","白色"]]]',
  `introduction` varchar(255) DEFAULT NULL COMMENT '商品介绍',
  `create_time` int(11) DEFAULT NULL COMMENT '时间戳',
  `is_sold` tinyint(4) NOT NULL COMMENT '是否上架 1 是 0 否',
  `goods_imgs` varchar(1000) DEFAULT NULL COMMENT '商品图片，多个以逗号 ,分隔',
  `description` varchar(8000) DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT '状态 1 正常 -1 删除',
  `post_type` tinyint(4) DEFAULT NULL COMMENT '邮寄方式 1 免邮 2 不免邮，买家承担 3 自提',
  `freight` decimal(10,0) DEFAULT '0' COMMENT '运费',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_goods
-- ----------------------------

-- ----------------------------
-- Table structure for shop_goods_pics
-- ----------------------------
DROP TABLE IF EXISTS `shop_goods_pics`;
CREATE TABLE `shop_goods_pics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `good_img_url` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_goods_pics
-- ----------------------------

-- ----------------------------
-- Table structure for shop_goods_reviews
-- ----------------------------
DROP TABLE IF EXISTS `shop_goods_reviews`;
CREATE TABLE `shop_goods_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(2000) CHARACTER SET utf8mb4 NOT NULL,
  `user_id` int(11) NOT NULL,
  `imgs` varchar(500) DEFAULT NULL COMMENT '多个图片逗号,分隔',
  `order_id` int(11) DEFAULT NULL,
  `goods_id` int(11) NOT NULL,
  `goods_sku_id` int(11) NOT NULL COMMENT '评价表',
  `stars` tinyint(4) DEFAULT NULL COMMENT '星级',
  `is_anonymous` tinyint(4) DEFAULT NULL COMMENT '是否匿名 1 是 0 否',
  `create_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_goods_reviews
-- ----------------------------

-- ----------------------------
-- Table structure for shop_goods_sku
-- ----------------------------
DROP TABLE IF EXISTS `shop_goods_sku`;
CREATE TABLE `shop_goods_sku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL COMMENT '商品id',
  `sku` varchar(800) NOT NULL COMMENT '例子：[{"title":"尺寸","attr":"120x150cm"},{"title":"颜色","attr":"红"}]',
  `is_sold` tinyint(4) NOT NULL COMMENT '是否上架',
  `price` decimal(10,2) DEFAULT NULL COMMENT '售卖价格',
  `market_price` decimal(10,2) DEFAULT NULL COMMENT '市场价/原价',
  `sku_img` varchar(255) DEFAULT NULL COMMENT 'sku主图',
  `stocks` int(4) DEFAULT NULL COMMENT '库存',
  `status` tinyint(255) DEFAULT NULL COMMENT '状态 1 正常 -1 删除',
  `create_time` int(11) DEFAULT NULL COMMENT '时间戳',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_goods_sku
-- ----------------------------

-- ----------------------------
-- Table structure for shop_order
-- ----------------------------
DROP TABLE IF EXISTS `shop_order`;
CREATE TABLE `shop_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `price` decimal(10,2) DEFAULT NULL COMMENT '订单总价格(包含运费）',
  `user_id` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL COMMENT '订单生成时间',
  `pay_money` decimal(10,2) DEFAULT NULL COMMENT '实际支付金额',
  `pay_type` tinyint(4) DEFAULT NULL COMMENT '支付类型  1微信支付  2 支付宝 3 积分 4 网银',
  `pay_time` int(11) DEFAULT NULL COMMENT '订单支付时间',
  `third_trade_no` varchar(100) DEFAULT NULL COMMENT '第三方支付流水号',
  `receiver_name` varchar(50) DEFAULT NULL COMMENT '客户姓名',
  `receiver_phone` char(11) DEFAULT NULL COMMENT '客户电话',
  `receiver_address` varchar(255) DEFAULT NULL COMMENT '客户地址',
  `post_type_str` varchar(20) DEFAULT NULL COMMENT '邮寄方式',
  `order_status` tinyint(4) DEFAULT NULL COMMENT '订单状态：0未付款，1待发货（已付款）， 2 待收货（已发货），3 已完成 ，4 已取消 ，5退款中， 6已退款  11 申请退款(未发货时)  31  申请退货退款(已完成时) 32 已完成并已评价',
  `status` tinyint(4) DEFAULT NULL COMMENT '数据状态 1 正常 -1 删除 ',
  `express_company` varchar(50) DEFAULT NULL COMMENT '快递公司',
  `express_code` varchar(32) DEFAULT NULL COMMENT '快递单号',
  `delivery_time` int(11) DEFAULT NULL COMMENT '发货时间',
  `freight` decimal(10,2) DEFAULT NULL COMMENT '运费',
  `complete_time` int(11) DEFAULT NULL COMMENT '订单完成时间/取消时间/删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_order
-- ----------------------------

-- ----------------------------
-- Table structure for shop_order_detail
-- ----------------------------
DROP TABLE IF EXISTS `shop_order_detail`;
CREATE TABLE `shop_order_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL,
  `goods_name` varchar(255) DEFAULT NULL,
  `goods_sku` varchar(255) DEFAULT NULL,
  `goods_img` varchar(1000) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT '单价',
  `goods_num` tinyint(4) DEFAULT NULL COMMENT '商品数量',
  `create_time` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `goods_id` int(11) DEFAULT NULL COMMENT '商品id',
  `goods_sku_id` int(11) DEFAULT NULL COMMENT '商品sku ID',
  `status` tinyint(4) DEFAULT '1' COMMENT '数据状态 1 正常 -1 删除',
  PRIMARY KEY (`id`),
  KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_order_detail
-- ----------------------------

-- ----------------------------
-- Table structure for shop_order_return
-- ----------------------------
DROP TABLE IF EXISTS `shop_order_return`;
CREATE TABLE `shop_order_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单退货详情',
  `return_order_no` varchar(32) DEFAULT NULL COMMENT '退货订单号',
  `order_no` varchar(32) DEFAULT NULL,
  `refund_fee` decimal(10,2) DEFAULT NULL COMMENT '退款金额',
  `user_id` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL COMMENT '申请时间',
  `type` tinyint(4) DEFAULT NULL COMMENT '退款类型 1 仅退款 2 退货退款',
  `status` tinyint(4) DEFAULT NULL COMMENT '1 退款成功 -2 退款取消/审核不通过 0 待审核 2 退款审核通过',
  `audit_time` int(11) DEFAULT NULL COMMENT '审核时间',
  `complete_time` int(11) DEFAULT NULL COMMENT '退款成功/取消时间',
  `remark` varchar(500) DEFAULT NULL COMMENT '退款原因或备注',
  PRIMARY KEY (`id`),
  KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_order_return
-- ----------------------------

-- ----------------------------
-- Table structure for shop_receiver_address
-- ----------------------------
DROP TABLE IF EXISTS `shop_receiver_address`;
CREATE TABLE `shop_receiver_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `phone` char(11) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL COMMENT '省',
  `city` varchar(255) DEFAULT NULL COMMENT '市',
  `district` varchar(255) DEFAULT NULL COMMENT '区',
  `address` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认地址 1 是 0 否',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_receiver_address
-- ----------------------------

-- ----------------------------
-- Table structure for shop_reco_goods
-- ----------------------------
DROP TABLE IF EXISTS `shop_reco_goods`;
CREATE TABLE `shop_reco_goods` (
  `goods_id` int(11) NOT NULL,
  `rec_id` varchar(255) NOT NULL,
  `create_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_reco_goods
-- ----------------------------

-- ----------------------------
-- Table structure for shop_reco_place
-- ----------------------------
DROP TABLE IF EXISTS `shop_reco_place`;
CREATE TABLE `shop_reco_place` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `sorted` int(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_reco_place
-- ----------------------------

-- ----------------------------
-- Table structure for shop_shopping_cart
-- ----------------------------
DROP TABLE IF EXISTS `shop_shopping_cart`;
CREATE TABLE `shop_shopping_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `goods_id` int(11) DEFAULT NULL COMMENT '商品ID',
  `goods_sku_id` int(11) DEFAULT NULL,
  `goods_name` varchar(300) DEFAULT NULL,
  `goods_img` varchar(255) DEFAULT NULL,
  `goods_sku` varchar(300) DEFAULT NULL,
  `goods_num` int(11) DEFAULT NULL COMMENT '商品数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of shop_shopping_cart
-- ----------------------------

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '昵称',
  `name` varchar(50) DEFAULT NULL COMMENT '姓名',
  `phone` char(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `gender` tinyint(4) DEFAULT '0' COMMENT '性别 1 男 2 女 0 未知',
  `country` varchar(255) DEFAULT NULL COMMENT '国',
  `province` varchar(255) DEFAULT NULL COMMENT '省',
  `city` varchar(255) DEFAULT NULL COMMENT '市',
  `status` tinyint(4) DEFAULT NULL COMMENT '状态 1 正常 -1删除',
  `headimgurl` varchar(255) DEFAULT NULL,
  `wx_openid` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of users
-- ----------------------------

-- ----------------------------
-- Table structure for user_goods_collection
-- ----------------------------
DROP TABLE IF EXISTS `user_goods_collection`;
CREATE TABLE `user_goods_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户收藏表',
  `user_id` int(11) NOT NULL,
  `goods_id` int(11) NOT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of user_goods_collection
-- ----------------------------
