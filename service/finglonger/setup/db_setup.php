<?php
/**
 * Variables used for database setup.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

//Listing of all the system tables created below.  Used as a check to determine existance of finglonger setup.  
$fl_systemTableNames = array('fl_emails', 
						'fl_integrations', 
						'fl_resources', 
						'fl_users',
						'fl_user_password_reset_log',
						'fl_user_types',
						'fl_user_type_resource_access',
						'fl_resource_categories',
						'fl_integration_meta',
						'fl_settings',
						'fl_setting_meta'
);

//SQL statement to create all of the system tables.  No data just the structure.  
//All data is managed by setup.php if you are looking for it.
$fl_systemTablesSQL = "--

CREATE TABLE `fl_emails` (
`email_id` int(11) NOT NULL,
  `email_name` varchar(250) NOT NULL,
  `email_subject` varchar(125) NOT NULL,
  `email_body_text` longtext,
  `email_body_html` longtext
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_integrations`
--

CREATE TABLE `fl_integrations` (
`integration_id` int(11) NOT NULL,
  `integration_name` varchar(125) NOT NULL,
  `enabled` varchar(5) NOT NULL DEFAULT 'false'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_integration_meta`
--

CREATE TABLE `fl_integration_meta` (
`integration_meta_id` int(11) NOT NULL,
  `integration_id` int(11) NOT NULL,
  `meta_name` varchar(255) NOT NULL,
  `meta_value` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_resources`
--

CREATE TABLE `fl_resources` (
`resource_id` int(11) NOT NULL,
  `resource_name` varchar(125) NOT NULL,
  `resource_category_id` int(11) DEFAULT 2,
  `token_exempt` varchar(5) NOT NULL DEFAULT 'false',
  `locked` varchar(5) NOT NULL DEFAULT 'false',
  `resource_missing` varchar(5) NOT NULL DEFAULT 'false'
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_resource_categories`
--

CREATE TABLE `fl_resource_categories` (
`resource_category_id` int(11) NOT NULL,
  `resource_category_name` varchar(125) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_settings`
--

CREATE TABLE `fl_settings` (
`setting_id` int(11) NOT NULL,
  `setting_name` varchar(125) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_setting_meta`
--

CREATE TABLE `fl_setting_meta` (
`settings_meta_id` int(11) NOT NULL,
  `setting_id` int(11) NOT NULL,
  `setting_category` varchar(125) DEFAULT NULL,
  `meta_name` varchar(125) NOT NULL,
  `meta_value` varchar(250) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_users`
--

CREATE TABLE `fl_users` (
`user_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL DEFAULT '1',
  `user_name` varchar(125) NOT NULL,
  `password` varchar(125) NOT NULL,
  `first_name` varchar(125) DEFAULT NULL,
  `last_name` varchar(125) DEFAULT NULL,
  `email` varchar(125) NOT NULL,
  `email_confirmation_required` varchar(5) NOT NULL DEFAULT 'false',
  `email_confirmation_token` varchar(250) DEFAULT NULL,
  `email_confirmed` varchar(5) NOT NULL DEFAULT 'false',
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_user_password_reset_log`
--

CREATE TABLE `fl_user_password_reset_log` (
`user_password_reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(250) NOT NULL,
  `token` varchar(25) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_used` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=215 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_user_types`
--

CREATE TABLE `fl_user_types` (
`user_type_id` int(11) NOT NULL,
  `user_type` varchar(125) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `locked` varchar(5) NOT NULL DEFAULT 'false'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fl_user_type_resource_access`
--

CREATE TABLE `fl_user_type_resource_access` (
`user_type_resource_access_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `get_allowed` varchar(5) NOT NULL DEFAULT 'false',
  `post_allowed` varchar(5) NOT NULL DEFAULT 'false',
  `delete_allowed` varchar(5) NOT NULL DEFAULT 'false'
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fl_emails`
--
ALTER TABLE `fl_emails`
 ADD PRIMARY KEY (`email_id`), ADD UNIQUE KEY `email_name` (`email_name`);

--
-- Indexes for table `fl_integrations`
--
ALTER TABLE `fl_integrations`
 ADD PRIMARY KEY (`integration_id`);

--
-- Indexes for table `fl_integration_meta`
--
ALTER TABLE `fl_integration_meta`
 ADD PRIMARY KEY (`integration_meta_id`), ADD KEY `integraion_id` (`integration_id`);

--
-- Indexes for table `fl_resources`
--
ALTER TABLE `fl_resources`
 ADD PRIMARY KEY (`resource_id`), ADD UNIQUE KEY `resource_name` (`resource_name`), ADD KEY `resource_category_id` (`resource_category_id`);

--
-- Indexes for table `fl_resource_categories`
--
ALTER TABLE `fl_resource_categories`
 ADD PRIMARY KEY (`resource_category_id`);

--
-- Indexes for table `fl_settings`
--
ALTER TABLE `fl_settings`
 ADD PRIMARY KEY (`setting_id`), ADD UNIQUE KEY `fl_settings_name` (`setting_name`);

--
-- Indexes for table `fl_setting_meta`
--
ALTER TABLE `fl_setting_meta`
 ADD PRIMARY KEY (`settings_meta_id`), ADD KEY `setting_id` (`setting_id`);

--
-- Indexes for table `fl_users`
--
ALTER TABLE `fl_users`
 ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `user_name` (`user_name`), ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `fl_user_password_reset_log`
--
ALTER TABLE `fl_user_password_reset_log`
 ADD PRIMARY KEY (`user_password_reset_id`);

--
-- Indexes for table `fl_user_types`
--
ALTER TABLE `fl_user_types`
 ADD PRIMARY KEY (`user_type_id`), ADD UNIQUE KEY `user_type` (`user_type`);

--
-- Indexes for table `fl_user_type_resource_access`
--
ALTER TABLE `fl_user_type_resource_access`
 ADD PRIMARY KEY (`user_type_resource_access_id`), ADD KEY `user_type_id` (`user_type_id`), ADD KEY `resource_id` (`resource_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fl_emails`
--
ALTER TABLE `fl_emails`
MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `fl_integrations`
--
ALTER TABLE `fl_integrations`
MODIFY `integration_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `fl_integration_meta`
--
ALTER TABLE `fl_integration_meta`
MODIFY `integration_meta_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `fl_resources`
--
ALTER TABLE `fl_resources`
MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT for table `fl_resource_categories`
--
ALTER TABLE `fl_resource_categories`
MODIFY `resource_category_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `fl_settings`
--
ALTER TABLE `fl_settings`
MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `fl_setting_meta`
--
ALTER TABLE `fl_setting_meta`
MODIFY `settings_meta_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `fl_users`
--
ALTER TABLE `fl_users`
MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `fl_user_password_reset_log`
--
ALTER TABLE `fl_user_password_reset_log`
MODIFY `user_password_reset_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=215;
--
-- AUTO_INCREMENT for table `fl_user_types`
--
ALTER TABLE `fl_user_types`
MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `fl_user_type_resource_access`
--
ALTER TABLE `fl_user_type_resource_access`
MODIFY `user_type_resource_access_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `fl_integration_meta`
--
ALTER TABLE `fl_integration_meta`
ADD CONSTRAINT `fl_integration_meta_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `fl_integrations` (`integration_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fl_resources`
--
ALTER TABLE `fl_resources`
ADD CONSTRAINT `fl_resources_ibfk_1` FOREIGN KEY (`resource_category_id`) REFERENCES `fl_resource_categories` (`resource_category_id`);

--
-- Constraints for table `fl_setting_meta`
--
ALTER TABLE `fl_setting_meta`
ADD CONSTRAINT `fl_setting_meta_ibfk_1` FOREIGN KEY (`setting_id`) REFERENCES `fl_settings` (`setting_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fl_users`
--
ALTER TABLE `fl_users`
ADD CONSTRAINT `fl_users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `fl_user_types` (`user_type_id`);

--
-- Constraints for table `fl_user_type_resource_access`
--
ALTER TABLE `fl_user_type_resource_access`
ADD CONSTRAINT `fl_user_type_resource_access_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `fl_user_types` (`user_type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fl_user_type_resource_access_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `fl_resources` (`resource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

";