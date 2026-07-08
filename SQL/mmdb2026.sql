-- Stand-in structure for view `vstu`
-- (See below for the actual view)
--
CREATE TABLE `vstu` (
`id` int(11)
,`matric_no` varchar(20)
,`full_name` varchar(100)
,`phone_no` varchar(20)
,`group_no` varchar(10)
,`life_motto` text
,`password` varchar(100)
,`photoStu` varchar(255)
,`photoStu_date` date
,`docStu` varchar(255)
,`docStu_date` date
,`audioStu` varchar(255)
,`audioStu_date` date
,`videoStu` varchar(255)
,`videoStu_date` date
);

-- --------------------------------------------------------

--
-- Structure for view `vstu`
--
DROP TABLE IF EXISTS `vstu`;
