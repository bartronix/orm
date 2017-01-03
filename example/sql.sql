CREATE DATABASE IF NOT EXISTS `orm` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `orm`;

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `article` (`id`, `title`, `user_id`) VALUES
(1, 'first article', 1),
(2, 'second article', 1),
(3, 'another article', 2),
(4, 'yet another article', 3);

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_item` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `user` (`id`, `username`) VALUES
(1, 'john doe'),
(2, 'user 2'),
(3, 'user 3');

ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);