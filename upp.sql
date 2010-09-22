CREATE TABLE IF NOT EXISTS `lu_ImageCategory` (
  `ImageCategoryID` int(20) NOT NULL AUTO_INCREMENT,
  `ImageCategoryName` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`ImageCategoryID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=2 ;

INSERT INTO `lu_ImageCategory` (`ImageCategoryID`, `ImageCategoryName`) VALUES
(1, 'Random');

CREATE TABLE IF NOT EXISTS `tbl_Image` (
  `ImageID` int(20) NOT NULL AUTO_INCREMENT,
  `ImageCategoryID` int(20) NOT NULL,
  `MetaDataID` int(20) NOT NULL,
  `UserID` int(20) NOT NULL,
  `FileName` text COLLATE latin1_general_ci NOT NULL,
  `OldFileName` text COLLATE latin1_general_ci NOT NULL,
  `FileType` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `TmpName` varchar(14) COLLATE latin1_general_ci NOT NULL,
  `Error` int(1) NOT NULL,
  `InternalError` int(1) NOT NULL,
  `Size` int(20) NOT NULL,
  `IsPrivate` int(1) NOT NULL,
  `IsDeleted` int(1) NOT NULL,
  `DateCreated` int(10) NOT NULL,
  `CreatedBy` int(20) NOT NULL,
  `DateUpdated` int(10) NOT NULL,
  `UpdatedBy` int(20) NOT NULL,
  PRIMARY KEY (`ImageID`),
  KEY `FK_tbl_Image_lu_ImageCategory` (`ImageCategoryID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `tbl_MetaData` (
  `MetaDataID` int(20) NOT NULL AUTO_INCREMENT,
  `Title` varchar(75) DEFAULT NULL,
  `Description` varchar(150) DEFAULT NULL,
  `Keywords` varchar(500) DEFAULT NULL,
  `DateCreated` int(10) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `DateUpdated` int(10) DEFAULT NULL,
  `UpdatedBy` int(11) DEFAULT NULL,
  PRIMARY KEY (`MetaDataID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `tbl_User` (
  `UserID` int(20) NOT NULL AUTO_INCREMENT,
  `Username` varchar(25) COLLATE latin1_general_ci NOT NULL,
  `Password` varchar(40) COLLATE latin1_general_ci NOT NULL,
  `IPAddress` varchar(15) COLLATE latin1_general_ci NOT NULL,
  `LastLogin` int(10) NOT NULL,
  `DateCreated` int(10) NOT NULL,
  `CreatedBy` int(20) NOT NULL,
  `DateUpdated` int(10) NOT NULL,
  `UpdatedBy` int(20) NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

