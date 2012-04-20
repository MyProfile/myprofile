-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 04, 2012 at 09:41 PM
-- Server version: 5.1.61
-- PHP Version: 5.3.6-13ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `myprofile`
--

-- --------------------------------------------------------

--
-- Table structure for table `pingback`
--

DROP TABLE IF EXISTS `pingback`;
CREATE TABLE IF NOT EXISTS `pingback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webid` varchar(512) CHARACTER SET utf8 NOT NULL COMMENT 'WebID URI',
  `feed_hash` varchar(8) NOT NULL,
  `feed_type` tinyint(1) NOT NULL,
  `user_hash` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=344 ;

-- --------------------------------------------------------

--
-- Table structure for table `pingback_messages`
--

DROP TABLE IF EXISTS `pingback_messages`;
CREATE TABLE IF NOT EXISTS `pingback_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` bigint(20) NOT NULL,
  `from_uri` varchar(512) NOT NULL,
  `to_hash` varchar(8) DEFAULT NULL,
  `to_uri` varchar(512) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `pic` varchar(1024) DEFAULT NULL,
  `msg` varchar(10000) NOT NULL,
  `wall` tinyint(1) NOT NULL DEFAULT '0',
  `new` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=169 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
