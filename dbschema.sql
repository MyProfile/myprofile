-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 13, 2012 at 02:28 PM
-- Server version: 5.5.24
-- PHP Version: 5.3.10-1ubuntu3.1

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

CREATE TABLE IF NOT EXISTS `pingback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webid` varchar(512) CHARACTER SET utf8 NOT NULL COMMENT 'WebID URI',
  `feed_hash` varchar(8) NOT NULL,
  `feed_type` tinyint(1) NOT NULL,
  `user_hash` varchar(20) DEFAULT NULL,
  `email` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pingback_messages`
--

CREATE TABLE IF NOT EXISTS `pingback_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` bigint(20) NOT NULL,
  `updated` bigint(20) NOT NULL,
  `etag` varchar(32),
  `from_uri` varchar(512) NOT NULL,
  `to_hash` varchar(8) DEFAULT NULL,
  `to_uri` varchar(512) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `pic` varchar(1024) DEFAULT NULL,
  `msg` varchar(10000) NOT NULL,
  `wall` tinyint(1) NOT NULL DEFAULT '0',
  `new` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `recovery`
--

CREATE TABLE IF NOT EXISTS `recovery` (
  `webid` varchar(512) NOT NULL,
  `email` varchar(512) DEFAULT NULL,
  `recovery_hash` varchar(40) DEFAULT NULL,
  `pair_hash` varchar(6) DEFAULT NULL,
  `expire` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`webid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE IF NOT EXISTS `votes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `webid` varchar(512) NOT NULL COMMENT 'The person who cast the vote.',
  `timestamp` bigint(20) NOT NULL COMMENT 'Timestamp for the vote.',
  `message_id` int(11) NOT NULL COMMENT 'Message ID.',
  `vote` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Stores the votes for each message.';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
