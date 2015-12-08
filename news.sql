/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50546
Source Host           : localhost:3306
Source Database       : irdata

Target Server Type    : MYSQL
Target Server Version : 50546
File Encoding         : 65001

Date: 2015-12-04 23:12:48
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for news
-- ----------------------------
DROP TABLE IF EXISTS `fenghuang_news`;
CREATE TABLE `fenghuang_news` (
  `id` bigint(255) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `publish_time` varchar(20) DEFAULT NULL,
  `content` mediumtext,
  `comments_num` varchar(32) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `crawled` varchar(255) DEFAULT NULL,
  `spider` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
