--
-- Structure of the `api_print` table 
--

CREATE TABLE IF NOT EXISTS `api_print` (
  `id` int(11) NOT NULL auto_increment,
  `id_user` int(11) NOT NULL,
  `url` text NOT NULL,
  `content` text NOT NULL,
  `md5` varchar(100) NOT NULL,
  `nb` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Structure of the `api_print_user` table 
--

CREATE TABLE IF NOT EXISTS `api_print_user` (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(255) NOT NULL,
  `passwd` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `nb_get` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
