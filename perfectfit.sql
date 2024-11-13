-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2024 at 05:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perfectfit`
--

-- --------------------------------------------------------

--
-- Table structure for table `favorite`
--

CREATE TABLE `favorite` (
  `id` int(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `gown_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorite`
--

INSERT INTO `favorite` (`id`, `email`, `gown_name`) VALUES
(10, 'beaeunicecarpio@gmail.com', 'Light Pink with Back Details Gown'),
(11, 'acagungun24@gmail.com', 'Light Pink with Back Details Gown, Sleeveless White Bridal Gown, Striped Tuxedo');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `theme` varchar(50) NOT NULL,
  `size` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL,
  `analysis` varchar(255) NOT NULL,
  `tone` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `tally` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `img`, `theme`, `size`, `color`, `analysis`, `tone`, `price`, `status`, `tally`) VALUES
(24, 'Pink Gown', 'a:3:{i:0;s:53:\"399543514_18323251861099078_3527471100193218839_n.jpg\";i:1;s:53:\"400783807_18323251825099078_7970613915087931956_n.jpg\";i:2;s:53:\"400199979_18323251843099078_9033740009358725667_n.jpg\";}', 'Prom', 'Small,Medium', 'Pink', 'Pale', 'Cool,Neutral', 55000, 0, '14'),
(25, 'Light Pink with Back Details Gown', 'a:2:{i:0;s:53:\"400509054_18323251558099078_1454628845225964202_n.jpg\";i:1;s:53:\"399539249_18323251576099078_2117300462010412340_n.jpg\";}', 'Prom', 'Small,Medium', 'Pink', 'Pale', 'Cool', 50000, 0, '19'),
(26, 'Red Cocktail Gown', 'a:2:{i:0;s:51:\"310474021_787993972262621_1167762117760430748_n.jpg\";i:1;s:51:\"310596431_636706121377496_3330490213306204387_n.jpg\";}', 'Prom', 'Small,Medium', 'Red', 'Pale', 'Warm', 15000, 0, '7'),
(30, 'Sleeve Lace with Necklace Bridal Gown', 'a:3:{i:0;s:53:\"442472308_18353862895099078_4338533067614269831_n.jpg\";i:1;s:52:\"442477269_18353862913099078_677964662073091792_n.jpg\";i:2;s:53:\"441322994_18353862904099078_2551818204309969021_n.jpg\";}', 'Wedding', 'Medium,Large', 'White', 'Fair', 'Cool,Neutral', 50000, 0, '23'),
(32, 'Tube White Bridal Gown', 'a:3:{i:0;s:53:\"460815479_18372221212099078_2192656664783314374_n.jpg\";i:1;s:53:\"460829751_18372221203099078_1715241451381319984_n.jpg\";i:2;s:53:\"460940398_18372221239099078_4806571011367564025_n.jpg\";}', 'Wedding', 'Medium,Large', 'White', 'Pale', 'Cool,Neutral', 50000, 0, '4'),
(33, 'Sleeveless White Bridal Gown', 'a:1:{i:0;s:53:\"449841208_18361018144099078_8101468053105344186_n.jpg\";}', 'Wedding', 'Medium,Large', 'White', 'Pale', 'Cool,Neutral', 65000, 0, '5'),
(34, 'Mermaid Off-Shoulder Gown', 'a:2:{i:0;s:53:\"445569153_18355399261099078_2001839810095596431_n.jpg\";i:1;s:53:\"445557458_18355399252099078_3307450106474684648_n.jpg\";}', 'Prom', 'Small,Medium', 'Maroon', 'Medium', 'Warm', 15000, 0, '0'),
(35, 'Off-Shoulder Beige Bridal Gown', 'a:1:{i:0;s:53:\"393121195_18319479742099078_2222847566171293451_n.jpg\";}', 'Wedding', 'Medium,Large', 'Beige', 'Medium', 'Neutral,Warm', 50000, 0, '0'),
(36, 'Butterfly Ball Gown', 'a:1:{i:0;s:53:\"362228501_18306030793099078_8928372622261096280_n.jpg\";}', 'Prom', 'Medium,Large', 'Pink', 'Pale', 'Cool', 40000, 0, '0'),
(37, 'Puff Sleeve Bridal Gown', 'a:1:{i:0;s:53:\"361590416_18306029800099078_1073556578748440941_n.jpg\";}', 'Wedding', 'Medium,Large', 'Beige', 'Fair', 'Neutral,Warm', 40000, 0, '0'),
(38, 'See-Throught Couture', 'a:1:{i:0;s:53:\"362218407_18306029686099078_1684367023132632984_n.jpg\";}', 'Formal', 'Small', 'White', 'Pale', 'Cool', 15000, 0, '0'),
(39, 'Fairy Gown', 'a:1:{i:0;s:53:\"361637483_18306029584099078_2379138001267753672_n.jpg\";}', 'Prom', 'Medium,Large', 'Pink', 'Pale', 'Warm', 10000, 0, '0'),
(40, 'Filipiniana with Cape', 'a:1:{i:0;s:51:\"361952644_18306029251099078_42479378792427276_n.jpg\";}', 'Formal', 'Small', 'Red', 'Pale', 'Cool', 55000, 0, '0'),
(41, 'Filipiniana Style Bridal Gown', 'a:1:{i:0;s:53:\"362241534_18306029122099078_6264360086529111706_n.jpg\";}', 'Wedding', 'Small,Medium', 'White', 'Pale', 'Cool,Neutral', 70000, 0, '8'),
(42, '2 in 1 Beige Bridal Gown', 'a:2:{i:0;s:53:\"362627286_18306028978099078_7243590668441910036_n.jpg\";i:1;s:52:\"326794933_1169859960336057_6389940474296711589_n.jpg\";}', 'Wedding', 'Medium,Large', 'Beige', 'Fair', 'Neutral,Warm', 50000, 0, '0'),
(43, 'Off-Shoulder Puff Sleeve Gown', 'a:2:{i:0;s:51:\"326639542_718194349705934_3296815692258663371_n.jpg\";i:1;s:52:\"326794933_1169859960336057_6389940474296711589_n.jpg\";}', 'Prom', 'Small,Medium', 'Pink', 'Pale', 'Warm', 50000, 0, '2'),
(45, 'Tube Butterfly Ball Gown', 'a:2:{i:0;s:53:\"391604077_10224019054605116_3178528595156720710_n.jpg\";i:1;s:52:\"391563693_10224019054725119_810484408744653145_n.jpg\";}', 'Prom', 'Small,Medium', 'Purple', 'Fair', 'Warm', 65000, 0, '3'),
(46, 'Tube Slit Gown', 'a:1:{i:0;s:53:\"391551739_10224019055205131_5340492720773757092_n.jpg\";}', 'Formal', 'Small,Medium', 'Light Blue', 'Pale', 'Cool', 10000, 0, '0'),
(47, 'Plain Off-Shoulder Bridal Gown', 'a:2:{i:0;s:52:\"373705384_10223714802999016_305773937297452652_n.jpg\";i:1;s:53:\"371508899_10223714802158995_7845303874359118436_n.jpg\";}', 'Wedding', 'Medium,Large', 'White', 'Pale', 'Cool,Neutral', 35000, 0, '0'),
(48, '2 in 1 Tube Slit Gown', 'a:2:{i:0;s:51:\"310824277_961678665234403_4151377469640422666_n.jpg\";i:1;s:52:\"310487813_1161533038048648_5623273512556699476_n.jpg\";}', 'Prom', 'Small,Medium', 'Pink', 'Pale', 'Cool', 8000, 0, '0'),
(49, 'Sleeveless Ball Gown', 'a:2:{i:0;s:51:\"310696027_828378588607809_2458843908266022238_n.jpg\";i:1;s:52:\"310735353_3279642379020887_7048718008125421892_n.jpg\";}', 'Prom', 'Small,Medium', 'Royal Blue', 'Medium', 'Cool', 50000, 0, '0'),
(50, 'Yellow Off-Shoulder Lace Ball Gown', 'a:2:{i:0;s:51:\"307999536_666982011095149_2993310259248895023_n.jpg\";i:1;s:51:\"307333721_473005721213335_6523876427803402364_n.jpg\";}', 'Prom', 'Medium,Large', 'Yellow', 'Medium', 'Neutral,Warm', 40000, 0, '0'),
(51, 'Sleeveless Backless Gown', 'a:2:{i:0;s:52:\"295928979_1571704449911461_6683049599075237667_n.jpg\";i:1;s:51:\"295853094_463678288568160_1120263850151765744_n.jpg\";}', 'Formal', 'Small,Medium', 'Purple', 'Pale', 'Warm', 35000, 0, '0'),
(52, 'Off-Shoulder Lace Long Sleeve Gown', 'a:2:{i:0;s:51:\"278939409_482582116944895_2708363710134215323_n.jpg\";i:1;s:51:\"278944420_509840057262169_1751613223422977795_n.jpg\";}', 'Prom', 'Small,Medium', 'Red', 'Fair', 'Cool,Warm', 35000, 0, '0'),
(53, 'Layered Gown', 'a:2:{i:0;s:52:\"245985866_1351356521949662_1844819543897445135_n.jpg\";i:1;s:51:\"246284624_301022431855467_6568571990316163293_n.jpg\";}', 'Formal', 'Medium,Large', 'Light Blue', 'Fair', 'Cool', 10000, 0, '0'),
(54, 'Fairy Puff Sleeve Gown', 'a:2:{i:0;s:51:\"248792340_1091137711696133_386086429510713819_n.jpg\";i:1;s:51:\"249552863_259290189478579_3623622574347838090_n.jpg\";}', 'Formal', 'Small', 'Light Blue', 'Pale', 'Cool', 10000, 0, '0'),
(55, 'Sleeveless Corset Like Bridal Gown', 'a:2:{i:0;s:48:\"324333941_687833836369345_5959664465298064_n.jpg\";i:1;s:51:\"324462890_855332032371305_6670091486451815650_n.jpg\";}', 'Wedding', 'Medium,Large', 'White', 'Pale', 'Cool,Neutral', 50000, 0, '0'),
(57, '2 IN 1 White Bridal Gown', 'a:3:{i:0;s:53:\"460975552_18372221374099078_8411473153094077923_n.jpg\";i:1;s:53:\"460710916_18372221341099078_6777917016483047127_n.jpg\";i:2;s:53:\"460812711_18372221392099078_6650651278277699335_n.jpg\";}', 'Wedding', 'Medium,Large', 'White', 'Pale', 'Cool, Neutral', 55000, 0, '0'),
(58, 'Tube Ball Gown', 'a:1:{i:0;s:53:\"399061720_18323252134099078_3354361793228428586_n.jpg\";}', 'Debut', 'Small,Medium', 'Mint Green', 'Pale', 'Cool, Neutral', 45000, 0, '0'),
(59, 'Off-Shoulder Puff Sleeve Corset Bridal Gown', 'a:3:{i:0;s:53:\"360124688_18306028768099078_6154960427221940950_n.jpg\";i:1;s:52:\"360101711_18306028810099078_705875749893142539_n.jpg\";i:2;s:53:\"362226603_18306028789099078_3332851005060825799_n.jpg\";}', 'Wedding', 'Small,Medium', 'White', 'Pale', 'Cool, Neutral', 45000, 0, '0'),
(62, '2 in 1 Off-Shoulder Lace Bridal Gown', 'a:3:{i:0;s:53:\"318937575_10222105623770541_4459009511721111105_n.jpg\";i:1;s:52:\"318953956_10222105622570511_208062833401103625_n.jpg\";i:2;s:53:\"319095845_10222105623050523_3525054224297191738_n.jpg\";}', 'Wedding', 'Small,Medium', 'Beige', 'Fair', 'Neutral, Warm', 50000, 0, '0'),
(63, 'Daisy Design Tux for Women', 'a:1:{i:0;s:51:\"429815747_826667076172210_2084577579161364631_n.jpg\";}', 'Formal', 'Small,Medium', 'Black', 'Pale', 'Cool, Neutral', 8500, 0, '0'),
(64, 'Simple Mustard Tux for Women', 'a:1:{i:0;s:50:\"429654238_826667186172199_796676200743703515_n.jpg\";}', 'Formal', 'Small,Medium', 'Yellow', 'Fair', 'Warm', 6000, 0, '0'),
(65, 'Cropped Tux for Women', 'a:1:{i:0;s:51:\"429680434_826667249505526_5204738427933815863_n.jpg\";}', 'Formal', 'Small,Medium', 'White', 'Pale', 'Cool', 6000, 0, '0'),
(66, 'Baby Pink Tux for Woman', 'a:1:{i:0;s:51:\"429824891_826666916172226_1571313499896598480_n.jpg\";}', 'Formal', 'Small,Medium', 'Pink', 'Fair', 'Cool', 6000, 0, '0'),
(67, 'Baby Blue Tux for Woman', 'a:1:{i:0;s:51:\"430210657_826667099505541_3099803734923804974_n.jpg\";}', 'Formal', 'Small,Medium', 'Blue', 'Pale', 'Cool', 6000, 0, '0'),
(68, 'Patterned Tuxedo ', 'a:1:{i:0;s:51:\"428616628_818932373612347_8243450746658486595_n.jpg\";}', 'Formal', 'Medium,Large', 'Brown', 'Fair', 'Cool, Warm', 8000, 0, '0'),
(69, 'Patterned Leaves Tuxedo ', 'a:1:{i:0;s:51:\"428617942_818932273612357_6902606536798452148_n.jpg\";}', 'Formal', 'Medium,Large', 'Blue', 'Fair', 'Cool', 8000, 0, '0'),
(70, 'Patterned with Pocket Tuxedo ', 'a:1:{i:0;s:51:\"428623752_818932463612338_4712076444157931176_n.jpg\";}', 'Formal', 'Medium,Large', 'Beige', 'Fair', 'Warm', 8000, 0, '0'),
(71, 'Barong', 'a:1:{i:0;s:50:\"428626587_818932563612328_965930829682526343_n.jpg\";}', 'Formal', 'Medium,Large', 'Beige', 'Fair', 'Warm', 8500, 0, '0'),
(72, 'Simple Tuxedo ', 'a:1:{i:0;s:51:\"428629762_818932586945659_8492033178875858126_n.jpg\";}', 'Formal', 'Medium,Large', 'White', 'Pale', 'Cool', 8000, 0, '0'),
(73, 'Simple Black Tuxedo ', 'a:1:{i:0;s:51:\"428609600_818932670278984_7030676714540055606_n.jpg\";}', 'Formal', 'Medium,Large', 'Black', 'Fair', 'Cool, Warm', 8000, 0, '0'),
(74, 'Simple Gray Tuxedo ', 'a:1:{i:0;s:51:\"428623059_818932680278983_6118797789893945207_n.jpg\";}', 'Formal', 'Medium,Large', 'Gray', 'Fair', 'Cool', 8000, 0, '0'),
(75, 'Simple Brown Tuxedo ', 'a:1:{i:0;s:51:\"428622547_818932716945646_6632469206465068427_n.jpg\";}', 'Formal', 'Medium,Large', 'Brown', 'Fair', 'Warm', 8000, 0, '0'),
(76, 'Striped Tuxedo', 'a:1:{i:0;s:51:\"428616295_818932866945631_6082769929393583535_n.jpg\";}', 'Formal', 'Medium,Large', 'Brown', 'Fair', 'Warm', 8000, 0, '0'),
(77, 'Simple Brown Tuxedo (Different Pants)', 'a:1:{i:0;s:51:\"428614616_818932920278959_6940045581275341273_n.jpg\";}', 'Formal', 'Medium,Large', 'Brown', 'Fair', 'Warm', 8000, 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `rent`
--

CREATE TABLE `rent` (
  `id` int(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `gownname_rented` varchar(255) NOT NULL,
  `date_rented` date NOT NULL,
  `duedate` date NOT NULL,
  `returned_date` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `request` varchar(10) NOT NULL,
  `service` varchar(10) NOT NULL,
  `total` int(100) NOT NULL,
  `r_pay` int(100) NOT NULL,
  `reservation` tinyint(1) NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `fname` varchar(50) NOT NULL,
  `sname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `contact` varchar(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL,
  `pfp` varchar(100) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(6) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`fname`, `sname`, `email`, `contact`, `password`, `type`, `pfp`, `is_verified`, `verification_code`, `reset_token`) VALUES
('Andrei', 'Cagungun', 'acagungun24@gmail.com', '097891263', '$2y$10$ZDsIAMbXRuQ50EPVZrnETOsidrIyPXEkJ5lVbx0U3qjKrsxCVk1Wi', '', 'uploaded_img/Andrei.jpg', 1, '498736', NULL),
('Bea', 'Carpio', 'beaeunicecarpio@gmail.com', '097896123', '$2y$10$Y85k6DL0W1RCccWeE74bUeJ7XdvZ4njh4EfqDsD.ueH/pjxTQL4ES', '', 'uploaded_img/DEF.jpg', 1, '709934', NULL),
('Rich', 'Sabinian', 'richsabinianpampang@gmail.com', '098976', '$2y$10$pSIF3eM6M1KuZegFQDvdwe0D8GgkkIyRjsuZfwqEIZylo.VJG6X7O', 'admin', 'uploaded_img/DEF.jpg', 1, '573503', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_interactions`
--

CREATE TABLE `user_interactions` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `gown_id` int(11) NOT NULL,
  `interaction_type` enum('view','search') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_interactions`
--

INSERT INTO `user_interactions` (`id`, `email`, `gown_id`, `interaction_type`, `timestamp`) VALUES
(1, 'beaeunicecarpio@gmail.com', 48, 'view', '2024-11-03 02:38:39'),
(2, 'beaeunicecarpio@gmail.com', 38, 'view', '2024-11-03 02:38:42'),
(3, 'beaeunicecarpio@gmail.com', 51, 'view', '2024-11-03 02:38:50'),
(4, 'beaeunicecarpio@gmail.com', 52, 'view', '2024-11-03 02:38:53'),
(5, 'beaeunicecarpio@gmail.com', 73, 'view', '2024-11-03 02:38:56'),
(6, 'beaeunicecarpio@gmail.com', 57, 'view', '2024-11-03 02:38:59'),
(7, 'beaeunicecarpio@gmail.com', 57, 'view', '2024-11-03 02:39:00'),
(8, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-03 02:39:03'),
(9, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-03 02:39:04'),
(10, 'acagungun24@gmail.com', 58, 'view', '2024-11-03 02:39:15'),
(11, 'acagungun24@gmail.com', 33, 'view', '2024-11-03 02:39:18'),
(12, 'acagungun24@gmail.com', 36, 'view', '2024-11-03 02:39:21'),
(13, 'acagungun24@gmail.com', 38, 'view', '2024-11-03 02:39:24'),
(14, 'acagungun24@gmail.com', 63, 'view', '2024-11-03 02:39:26'),
(15, 'acagungun24@gmail.com', 59, 'view', '2024-11-03 02:39:29'),
(16, 'acagungun24@gmail.com', 68, 'view', '2024-11-03 02:39:31'),
(17, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:39:34'),
(18, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:39:37'),
(19, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:39:38'),
(20, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:39:40'),
(21, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:39:40'),
(22, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:39:41'),
(23, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:39:41'),
(24, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:39:42'),
(25, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:39:43'),
(26, 'acagungun24@gmail.com', 66, 'view', '2024-11-03 02:39:45'),
(27, 'acagungun24@gmail.com', 66, 'view', '2024-11-03 02:39:47'),
(28, 'acagungun24@gmail.com', 72, 'view', '2024-11-03 02:39:49'),
(29, 'acagungun24@gmail.com', 72, 'view', '2024-11-03 02:39:51'),
(30, 'acagungun24@gmail.com', 33, 'view', '2024-11-03 02:40:06'),
(31, 'acagungun24@gmail.com', 33, 'view', '2024-11-03 02:40:15'),
(32, 'acagungun24@gmail.com', 62, 'view', '2024-11-03 02:40:32'),
(33, 'acagungun24@gmail.com', 49, 'view', '2024-11-03 02:40:40'),
(34, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:40:56'),
(35, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:03'),
(36, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:04'),
(37, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:05'),
(38, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:05'),
(39, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:26'),
(40, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:28'),
(41, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:37'),
(42, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:38'),
(43, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:38'),
(44, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:39'),
(45, 'acagungun24@gmail.com', 38, 'view', '2024-11-03 02:41:39'),
(46, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:41'),
(47, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:41:42'),
(48, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:41:43'),
(49, 'acagungun24@gmail.com', 73, 'view', '2024-11-03 02:41:44'),
(50, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:41:46'),
(51, 'acagungun24@gmail.com', 50, 'view', '2024-11-03 02:41:53'),
(52, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:42:02'),
(53, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:42:04'),
(54, 'acagungun24@gmail.com', 76, 'view', '2024-11-03 02:43:14'),
(55, 'acagungun24@gmail.com', 24, 'view', '2024-11-03 03:16:44'),
(56, 'acagungun24@gmail.com', 30, 'view', '2024-11-03 03:16:57'),
(57, 'beaeunicecarpio@gmail.com', 32, 'view', '2024-11-04 12:42:31'),
(58, 'beaeunicecarpio@gmail.com', 32, 'view', '2024-11-04 12:42:44'),
(59, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:40'),
(60, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:46'),
(61, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:47'),
(62, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:47'),
(63, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:47'),
(64, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:48'),
(65, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:49'),
(66, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:49'),
(67, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:50'),
(68, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:50'),
(69, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:50'),
(70, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:50'),
(71, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:50'),
(72, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:51'),
(73, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:51'),
(74, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:51'),
(75, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:51'),
(76, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:08:52'),
(77, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:01'),
(78, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:02'),
(79, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:02'),
(80, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:02'),
(81, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:02'),
(82, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:03'),
(83, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:07'),
(84, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:12'),
(85, 'acagungun24@gmail.com', 46, 'view', '2024-11-04 14:09:14'),
(86, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:21'),
(87, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:26'),
(88, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:34'),
(89, 'acagungun24@gmail.com', 53, 'view', '2024-11-04 14:09:37'),
(90, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:39'),
(91, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:43'),
(92, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:09:54'),
(93, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:10:04'),
(94, 'acagungun24@gmail.com', 0, 'search', '2024-11-04 14:10:16'),
(95, 'acagungun24@gmail.com', 33, 'view', '2024-11-04 14:11:02'),
(96, 'acagungun24@gmail.com', 33, 'view', '2024-11-04 14:14:25'),
(97, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-04 17:51:34'),
(98, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-04 17:51:56'),
(99, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-04 17:52:12'),
(100, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-04 17:53:32'),
(101, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-04 17:53:43'),
(102, 'beaeunicecarpio@gmail.com', 26, 'view', '2024-11-04 17:58:16'),
(103, 'beaeunicecarpio@gmail.com', 26, 'view', '2024-11-04 17:58:30'),
(104, 'beaeunicecarpio@gmail.com', 26, 'view', '2024-11-04 17:59:31'),
(105, 'beaeunicecarpio@gmail.com', 26, 'view', '2024-11-04 17:59:33'),
(106, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-04 17:59:34'),
(107, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-04 17:59:43'),
(108, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-04 18:52:05'),
(109, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-04 18:52:17'),
(110, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-04 18:53:55'),
(111, 'beaeunicecarpio@gmail.com', 26, 'view', '2024-11-04 18:54:01'),
(112, 'beaeunicecarpio@gmail.com', 26, 'view', '2024-11-04 18:54:12'),
(113, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-04 18:54:14'),
(114, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-04 18:54:23'),
(115, 'beaeunicecarpio@gmail.com', 43, 'view', '2024-11-04 18:54:25'),
(116, 'beaeunicecarpio@gmail.com', 43, 'view', '2024-11-04 18:54:40'),
(117, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-04 18:54:42'),
(118, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-04 18:54:53'),
(119, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-04 19:01:05'),
(120, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-04 19:01:08'),
(121, 'acagungun24@gmail.com', 24, 'view', '2024-11-04 19:11:55'),
(122, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-05 11:24:25'),
(123, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-06 13:41:52'),
(124, 'beaeunicecarpio@gmail.com', 0, 'search', '2024-11-06 13:42:12'),
(125, 'beaeunicecarpio@gmail.com', 0, 'search', '2024-11-06 13:42:14'),
(126, 'beaeunicecarpio@gmail.com', 0, 'search', '2024-11-06 13:42:15'),
(127, 'beaeunicecarpio@gmail.com', 0, 'search', '2024-11-06 13:42:16'),
(128, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-06 13:42:23'),
(129, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-06 15:31:59'),
(130, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-06 15:32:11'),
(131, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-06 15:43:06'),
(132, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-06 15:43:15'),
(133, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-06 15:43:37'),
(134, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-06 15:43:46'),
(135, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-06 15:44:41'),
(136, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-06 15:45:33'),
(137, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-06 15:45:41'),
(138, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-06 15:45:43'),
(139, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-06 15:45:54'),
(140, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-06 15:46:00'),
(141, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-06 15:46:11'),
(142, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-06 15:46:13'),
(143, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-06 15:46:25'),
(144, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-08 12:05:02'),
(145, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:07:56'),
(146, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:09:36'),
(147, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:09:59'),
(148, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:10:51'),
(149, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:11:54'),
(150, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:11:58'),
(151, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:45'),
(152, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:45'),
(153, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:45'),
(154, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:48'),
(155, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:48'),
(156, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:48'),
(157, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:49'),
(158, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:49'),
(159, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:49'),
(160, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:50'),
(161, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:51'),
(162, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:51'),
(163, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:51'),
(164, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:52'),
(165, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:52'),
(166, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:12:56'),
(167, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:13:37'),
(168, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:13:53'),
(169, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 12:26:53'),
(170, 'beaeunicecarpio@gmail.com', 24, 'view', '2024-11-08 12:35:19'),
(171, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 12:35:41'),
(172, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 12:38:03'),
(173, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-08 12:38:50'),
(174, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 12:41:49'),
(175, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 12:47:11'),
(176, 'acagungun24@gmail.com', 25, 'view', '2024-11-08 12:47:52'),
(177, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 12:47:57'),
(178, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 12:49:12'),
(179, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:10:12'),
(180, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:15:16'),
(181, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:23:19'),
(182, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:24:44'),
(183, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:24:49'),
(184, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:25:46'),
(185, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:25:52'),
(186, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:25:55'),
(187, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:26:56'),
(188, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:28:21'),
(189, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:30:04'),
(190, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:30:09'),
(191, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:30:44'),
(192, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:32:12'),
(193, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:32:25'),
(194, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:32:30'),
(195, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:32:31'),
(196, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:32:31'),
(197, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:32:31'),
(198, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:33:06'),
(199, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:35:52'),
(200, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:36:50'),
(201, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:37:08'),
(202, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:38:06'),
(203, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:39:03'),
(204, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:44:34'),
(205, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:50:33'),
(206, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:51:45'),
(207, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:53:03'),
(208, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:53:10'),
(209, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:55:13'),
(210, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 13:55:30'),
(211, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:06:23'),
(212, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:10:09'),
(213, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:10:11'),
(214, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:11:37'),
(215, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:11:45'),
(216, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:11:53'),
(217, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:11:56'),
(218, 'acagungun24@gmail.com', 41, 'view', '2024-11-08 14:12:00'),
(219, 'acagungun24@gmail.com', 41, 'view', '2024-11-08 14:12:25'),
(220, 'acagungun24@gmail.com', 45, 'view', '2024-11-08 14:15:18'),
(221, 'acagungun24@gmail.com', 41, 'view', '2024-11-08 14:15:21'),
(222, 'acagungun24@gmail.com', 77, 'view', '2024-11-08 14:15:24'),
(223, 'acagungun24@gmail.com', 41, 'view', '2024-11-08 14:15:26'),
(224, 'acagungun24@gmail.com', 30, 'view', '2024-11-08 14:15:31'),
(225, 'acagungun24@gmail.com', 30, 'view', '2024-11-08 14:15:48'),
(226, 'acagungun24@gmail.com', 30, 'view', '2024-11-08 14:16:52'),
(227, 'acagungun24@gmail.com', 24, 'view', '2024-11-08 14:17:57'),
(228, 'acagungun24@gmail.com', 24, 'view', '2024-11-08 14:18:15'),
(229, 'acagungun24@gmail.com', 25, 'view', '2024-11-08 14:18:21'),
(230, 'acagungun24@gmail.com', 25, 'view', '2024-11-08 14:18:32'),
(231, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 14:18:44'),
(232, 'beaeunicecarpio@gmail.com', 25, 'view', '2024-11-08 14:18:56'),
(233, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:07:49'),
(234, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:08:07'),
(235, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:08:19'),
(236, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:08:29'),
(237, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:09:49'),
(238, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:12:18'),
(239, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:12:20'),
(240, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:12:22'),
(241, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-09 10:12:23'),
(242, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-09 10:12:29'),
(243, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:12:33'),
(244, 'beaeunicecarpio@gmail.com', 30, 'view', '2024-11-09 10:14:01'),
(245, 'beaeunicecarpio@gmail.com', 45, 'view', '2024-11-09 10:14:02'),
(246, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:14:04'),
(247, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:14:16'),
(248, 'beaeunicecarpio@gmail.com', 41, 'view', '2024-11-09 10:14:17'),
(249, 'acagungun24@gmail.com', 45, 'view', '2024-11-09 10:14:31'),
(250, 'acagungun24@gmail.com', 41, 'view', '2024-11-09 10:14:33'),
(251, 'acagungun24@gmail.com', 30, 'view', '2024-11-09 10:14:34'),
(252, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:52:08'),
(253, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:52:29'),
(254, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:52:46'),
(255, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:53:00'),
(256, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:53:02'),
(257, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:53:21'),
(258, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:53:34'),
(259, 'acagungun24@gmail.com', 25, 'view', '2024-11-11 16:53:37'),
(260, 'acagungun24@gmail.com', 26, 'view', '2024-11-11 16:57:36'),
(261, 'acagungun24@gmail.com', 26, 'view', '2024-11-11 17:01:27'),
(262, 'acagungun24@gmail.com', 39, 'view', '2024-11-12 04:16:55'),
(263, 'acagungun24@gmail.com', 39, 'view', '2024-11-12 04:17:23'),
(264, 'acagungun24@gmail.com', 26, 'view', '2024-11-12 04:39:31'),
(265, 'acagungun24@gmail.com', 24, 'view', '2024-11-12 04:39:32'),
(266, 'acagungun24@gmail.com', 24, 'view', '2024-11-12 04:39:35'),
(267, 'acagungun24@gmail.com', 36, 'view', '2024-11-12 04:39:37'),
(268, 'acagungun24@gmail.com', 24, 'view', '2024-11-12 04:39:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rent`
--
ALTER TABLE `rent`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `favorite`
--
ALTER TABLE `favorite`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `rent`
--
ALTER TABLE `rent`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `user_interactions`
--
ALTER TABLE `user_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
