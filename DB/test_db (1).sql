-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 16, 2025 at 11:15 PM
-- Server version: 8.0.42-0ubuntu0.20.04.1
-- PHP Version: 7.4.3-4ubuntu2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `user` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ;

--
-- Dumping data for table `activity_logs`
--


-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `author` varchar(100) NOT NULL,
  `type` enum('physical','e-book') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'e-book',
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `file_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '/var/www/html/lms/assets/aas.jpg',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `evaluation` decimal(3,0) NOT NULL,
  `material_type` enum('كتاب','مجلة','جريدة') NOT NULL DEFAULT 'كتاب',
  `isbn` varchar(13) DEFAULT NULL COMMENT 'الرقم الدولي المعياري للكتاب (ISBN)',
  `publication_date` date DEFAULT NULL COMMENT 'تاريخ نشر الكتاب',
  `page_count` int DEFAULT NULL COMMENT 'عدد صفحات الكتاب',
  `borrow_fee` decimal(10,2) NOT NULL DEFAULT '5000.00' COMMENT 'رسوم استعارة الكتاب',
  `has_discount` tinyint(1) DEFAULT '0',
  `discount_percentage` int DEFAULT '0',
  `book_of_the_month` tinyint(1) DEFAULT '0' COMMENT '1 يعني كتاب الشهر، 0 غير محدد'
) ;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `type`, `quantity`, `price`, `file_path`, `created_at`, `cover_image`, `category_id`, `description`, `evaluation`, `material_type`, `isbn`, `publication_date`, `page_count`, `borrow_fee`, `has_discount`, `discount_percentage`, `book_of_the_month`) VALUES
(4, 'جلسة علاجية', 'مارك روبنسن', 'e-book', 6, '60000.00', 'assets/files/6803010f85bf8_20250419044903.pdf', '2025-04-03 08:59:31', 'assets/images/books/680301eddd572_20250419045245.jpeg', 1, 'إذا كنت تحتاج إلى عدد أكبر من الفقرات يتيح لك مولد النص العربى زيادة عدد الفقرات كما تريد، النص لن يبدو مقسما ولا يحوي أخطاء لغوية، مول', '3', 'كتاب', '', '2025-04-09', 120, '5000.00', 1, 40, 1),
(5, 'أثر الفراشة', 'ماتيلدا', 'physical', 50, '120000.00', 'assets/files/680302b41512a_20250419045604.pdf', '2025-04-03 09:02:59', 'assets/images/books/680302b414f67_20250419045604.jpeg', 1, ' ا النص وامكانية تغييرة في اي وقت عن طريق ادارة الموقع . يتم اضافة هذا النص كنص تجريبي للمعاينة فقط وهو لا يعبر عن أي موضوع محدد ', '4', 'مجلة', '1254655', '2025-04-25', 10, '5000.00', 0, 0, 0),
(9, 'مفاتيح العمل', 'مارتن جوين', 'physical', 20, '30000.00', 'assets/files/6802b5ba9ad00_20250418232738.pdf', '2025-04-04 14:29:00', 'assets/images/books/680302c990992_20250419045625.jpeg', 10, ' إذا كنت تحتاج إلى عدد أكبر من الفقرات يتيح لك مولد النص العربى زيادة عددة لتصميم الموقع.', '4', 'كتاب', '4545445', '2025-04-24', 300, '5000.00', 1, 5, 0),
(13, 'الطيور', 'مارتن جوين', 'physical', 8, '16000.00', 'assets/files/680303170f117_20250419045743.pdf', '2025-04-04 17:31:17', 'assets/images/books/680303170f09d_20250419045743.jpeg', 16, ' يتكلم الكتاب عن انواع الطيور و اشكالها و مناطق تواجدها', '4', 'كتاب', '4544568', '2025-02-03', 399, '5000.00', 0, 0, 0),
(14, 'الحيوان', 'الجاحظ', 'physical', 6, '25.00', 'assets/files/67fec4c8b3005_20250415234248.pdf', '2025-04-04 17:31:44', 'assets/images/books/6803032736d5b_20250419045759.jpeg', 2, ' hfhgfh', '5', 'جريدة', '777777', '2025-04-10', 500, '5000.00', 0, 0, 0),
(18, 'نهج الصالحين', 'سامي يوسف', 'physical', 100, '10000.00', 'assets/files/6803033dd813c_20250419045821.pdf', '2025-04-06 20:14:37', 'assets/images/books/6803033dd8008_20250419045821.jpeg', 21, ' جريدة يومية', '4', 'جريدة', '1546425', '2025-04-25', 10, '5000.00', 1, 10, 0),
(21, 'ماجد', 'ماجد وحيد', 'e-book', 12, '5000.00', 'assets/files/680327a8c8b90_20250419073344.pdf', '2025-04-19 04:21:32', 'assets/images/books/680327a8c8ad3_20250419073344.jpeg', 2, ' هذا النص هو مثال لنص يمكن أن يستبدل في نفس المساحة، لقد تم توليد هذا النص من مولد النص العربى، حيث يمكنك أن تولد مثل هذا النص أو العديد من النصوص الأخرى إضافة إلى زيادة عدد الحروف التى يولدها التطبيق.', '3', 'جريدة', '14521', '2025-04-10', 10, '5000.00', 0, 0, 0),
(22, 'الحياة', 'مارك روبنسن', 'physical', 15, '5400.00', 'assets/files/6803280465bdd_20250419073516.pdf', '2025-04-19 04:31:23', 'assets/images/books/6803280465b61_20250419073516.jpeg', 3, '  لثقثقفثقفثقف', '3', 'كتاب', '56789', '2025-04-02', 10, '5000.00', 0, 0, 0),
(23, 'الامير', 'ميكافيللي', 'physical', 10, '15000.00', 'assets/files/680818e418e1d_20250423013204.pdf', '2025-04-22 22:32:04', 'assets/images/books/680818e418c14_20250423013204.jpeg', 24, ' يشيشسيشسيشسي', '3', 'كتاب', '1212121', '2024-01-30', 200, '5000.00', 0, 0, 0),
(24, 'اسرار الحياة', 'جوني ', 'physical', 10, '12000.00', 'assets/files/680a4d0b78a5d_20250424173907.pdf', '2025-04-24 14:39:07', 'assets/images/books/680a4d0b6eee0_20250424173907.jpeg', 2, ' يببببلا ىةتوةةس ل', '3', 'كتاب', '123456', '2025-04-25', 200, '5000.00', 0, 0, 0),
(25, 'الرسالة', 'مارتن جوين', 'physical', 10, '20000.00', 'assets/files/680a8acb0586e_20250424220235.pdf', '2025-04-24 19:02:35', 'assets/images/books/680a8acaec531_20250424220234.jpeg', 4, ' قفغقفغقف', '4', 'كتاب', '25252', '2025-04-17', 100, '5000.00', 0, 0, 0),
(26, 'ليالي الصحراء', 'مارك روبنسن', 'physical', 6, '10000.00', 'assets/files/680a91073fb26_20250424222911.pdf', '2025-04-24 19:29:11', 'assets/images/books/680a91073f992_20250424222911.jpeg', 1, 'هذا النص يمكن أن يتم تركيبه على أي تصميم دون مشكلة فلن يبدو وكأنه نص منسوخ، غير منظم، غير منسق، أو حتى غير مفهوم. لأنه مازال نصاً بديلاً ومؤقتاً.', '4', 'كتاب', '12345', '2025-05-01', 300, '5000.00', 0, 0, 0),
(27, 'ألوان و أجواء', 'عمر أبو ريشة', 'physical', 10, '12000.00', 'assets/files/680a9276c7baf_20250424223518.pdf', '2025-04-24 19:35:18', 'assets/images/books/680a9276c7b1e_20250424223518.jpeg', 1, ' لايوجد اي توصيف حاليا', '3', 'مجلة', '15243', '2025-04-16', 54, '5000.00', 0, 0, 0),
(28, 'جسمك يتذكر ', 'مارك جونسن', 'physical', 10, '12000.00', 'assets/files/680a95df02bf9_20250424224951.pdf', '2025-04-24 19:49:51', 'assets/images/books/680a95df02b5b_20250424224951.jpeg', 5, ' اا', '4', 'كتاب', '11221', '2025-04-23', 12, '5000.00', 0, 0, 0),
(29, 'الطبيعة', 'مارك روبنسن', 'physical', 45, '7900.00', 'assets/files/680a9707794d5_20250424225447.pdf', '2025-04-24 19:54:47', 'assets/images/books/680a970779420_20250424225447.jpeg', 5, 'هذا النص يمكن أن يتم تركيبه على أي تصميم دون مشكلة فلن يبدو وكأنه نص منسوخ، غير منظم، غير منسق، أو حتى غير مفهوم. لأنه مازال نصاً بديلاً ومؤقتاً.', '3', 'مجلة', '444', '2025-04-15', 54, '5000.00', 0, 0, 0),
(30, 'البادية', 'مارك روبنسن', 'e-book', 100, '1000.00', 'assets/files/680cb72b1b6ae_20250426133627.pdf', '2025-04-26 10:36:27', 'assets/images/books/684572945a596_20250608142300.jpg', 2, '  هذا النص هو مثال لنص يمكن أن يستبدل في نفس المساحة، لقد تم توليد هذا النص من مولد النص العربى، حيث يمكنك أن تولد مثل هذا النص أو العديد من النصوص الأخرى إضافة إلى زيادة عدد الحروف التى يولدها التطبيق.', '5', 'مجلة', '124578', '2025-04-25', 5, '5000.00', 1, 30, 0),
(31, 'الانضباط الذاتي', 'أحمد سمعان', 'physical', 54, '5555.00', 'assets/files/680cffab8606f_20250426184547.pdf', '2025-04-26 15:45:47', 'assets/images/books/681a0b7e1413f_20250506161542.jpg', 10, ' هذا النص يمكن أن يتم تركيبه على أي تصميم دون مشكلة فلن يبدو وكأنه نص منسوخ، غير منظم، غير منسق، أو حتى غير مفهوم. لأنه مازال نصاً بديلاً ومؤقتاً.', '4', 'كتاب', '534534', '2025-04-09', 20, '5000.00', 0, 0, 0),
(33, 'ماتلاب', 'ساري مالتن', 'physical', 6, '10000.00', 'assets/files/682a5d30d0b0d_20250519012032.pdf', '2025-05-18 22:20:32', 'assets/images/books/682a5d30d0a50_20250519012032.jpeg', 10, '0', '5', 'كتاب', '95864', '2025-05-07', 102, '5000.00', 1, 11, 0),
(34, 'عصر الأدلجة', 'مارك روبنسن', 'e-book', 10, '12000.00', 'assets/files/684985cad0418_20250611163402.pdf', '2025-06-01 22:13:26', 'assets/images/books/6845726f37ba2_20250608142223.jpeg', 13, 'ئؤئءؤئءؤئءؤئءؤئءؤ', '3', 'كتاب', '41415', '2025-05-14', 122, '5000.00', 0, 0, 0),
(55, 'sdfsdf', 'sdfsdf', 'e-book', 345, '435.00', '/var/www/html/lms/assets/files/68495cb61475c_20250611133846.pdf', '2025-06-11 10:38:46', 'assets/images/books/68495cb6145e2_20250611133846.jpeg', 13, '234234', '3', 'كتاب', '345', '2025-06-06', 345, '5000.00', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `book_ratings`
--

CREATE TABLE `book_ratings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `request_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `book_ratings`
--

INSERT INTO `book_ratings` (`id`, `user_id`, `book_id`, `request_id`, `rating`, `comment`, `created_at`) VALUES
(20, 62, 28, 233, 4, 'fdsfsdfsd', '2025-06-14 09:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_requests`
--

CREATE TABLE `borrow_requests` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `book_id` int DEFAULT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') NOT NULL,
  `processed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `loan_duration` int NOT NULL DEFAULT '0' COMMENT 'Days',
  `due_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reading_completed` tinyint(1) NOT NULL DEFAULT '0',
  `type` enum('borrow','purchase','renew',' penalty') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `renewed` tinyint DEFAULT '0',
  `last_penalty_date` date DEFAULT NULL,
  `total_penalty` decimal(10,2) DEFAULT '0.00'
) ;

--
-- Dumping data for table `borrow_requests`
--

INSERT INTO `borrow_requests` (`id`, `user_id`, `book_id`, `request_date`, `status`, `processed_at`, `loan_duration`, `due_date`, `reading_completed`, `type`, `amount`, `renewed`, `last_penalty_date`, `total_penalty`) VALUES
(232, 62, 55, '2025-06-13 19:40:24', 'approved', '2025-06-13 19:40:41', 1000, '2028-03-09 19:40:41', 0, 'purchase', '435.00', 0, NULL, '0.00'),
(233, 62, 28, '2025-06-13 19:40:24', 'approved', '2025-06-13 19:40:45', 1000, '2028-03-09 19:40:45', 0, 'purchase', '12000.00', 0, NULL, '0.00'),
(248, 62, 55, '2025-06-14 11:32:37', 'approved', '2025-06-14 11:34:34', 14, '2025-06-28 11:34:34', 0, 'renew', '7000.00', 1, '2025-06-14', '2000.00'),
(249, 62, 33, '2025-06-14 04:43:25', 'approved', '2025-06-14 04:44:48', 1000, '2028-03-10 04:44:48', 0, 'purchase', '8900.00', 0, NULL, '0.00'),
(250, 62, 31, '2025-06-14 04:43:25', 'approved', '2025-06-14 04:44:52', 1000, '2028-03-10 04:44:52', 0, 'purchase', '5555.00', 0, NULL, '0.00'),
(251, 62, 31, '2025-06-14 06:05:05', 'pending', '2025-06-14 06:05:05', 0, '2025-06-14 06:05:05', 0, 'renew', '7000.00', 1, '2025-06-10', '4000.00');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(50) NOT NULL
) ;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(10, 'أقتصاد'),
(21, 'إسلامية'),
(4, 'برمجة'),
(3, 'تاريخية'),
(29, 'تسالي'),
(24, 'خرافات'),
(5, 'ذكاء صنعي'),
(1, 'روايات'),
(6, 'رياضيات'),
(13, 'سياسة'),
(2, 'علمية'),
(16, 'علوم'),
(8, 'قصص قصيرة'),
(20, 'كيمياء');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `complaint_type` varchar(50) NOT NULL,
  `problem_type` varchar(50) DEFAULT NULL,
  `complaint` text NOT NULL,
  `additional_data` text,
  `created_at` datetime NOT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending'
) ;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `email`, `complaint_type`, `problem_type`, `complaint`, `additional_data`, `created_at`, `status`) VALUES
(18, 'rabi@g.c', 'account', 'undefined', 'لا استطيع تسجيل حساب جديد', '{\"account_issue_type\":\"creation\",\"last_success_login\":\"\"}', '2025-06-14 02:11:43', 'pending'),
(19, 'rabi@g.c', 'account', 'undefined', 'test', '{\"account_issue_type\":\"creation\",\"last_success_login\":\"\"}', '2025-06-14 02:19:35', 'resolved');

-- --------------------------------------------------------

--
-- Table structure for table `favorite_books`
--

CREATE TABLE `favorite_books` (
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `group_books`
--

CREATE TABLE `group_books` (
  `book_id` int NOT NULL,
  `group_id` int NOT NULL,
  `book_link` varchar(255) NOT NULL,
  `added_by` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `member_id` int NOT NULL,
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `join_requests`
--

CREATE TABLE `join_requests` (
  `request_id` int NOT NULL,
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `subject`, `message`, `is_read`, `created_at`) VALUES
(4, 65, 'مرحباً بك في نظامنا!', 'عزيزي <br><br>تم إنشاء حسابك بنجاح.<br>كلمة المرور الخاصة بك هي: <strong>123456</strong><br><br>ننصحك بتغيير كلمة المرور بعد تسجيل الدخول.<br><br>شكراً لانضمامك.', 1, '2025-06-13 23:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `request_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `is_read` tinyint DEFAULT '0',
  `link_read` tinyint(1) DEFAULT '0' COMMENT '0: غير مقروء، 1: مقروء'
) ;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `link`, `request_id`, `created_at`, `expires_at`, `is_read`, `link_read`) VALUES
(391, 5, 'طلب جديد: استعارة كتاب', 'http://localhost/lms//admin/dashboard.php?section=ops', 230, '2025-06-13 17:54:01', '2025-06-14 17:54:01', 1, 0),
(393, 5, 'طلب جديد: استعارة كتاب', 'http://localhost/lms//admin/dashboard.php?section=ops', 231, '2025-06-13 19:07:29', '2025-06-14 19:07:29', 1, 0),
(395, 62, 'تم شحن 50,000.00 ليرة بنجاح', 'http://localhost/lms/user/dashboard.php?section=funds', NULL, '2025-06-13 19:39:00', NULL, 1, 0),
(396, 5, 'طلب شراء جديد (2 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-13 19:40:24', NULL, 1, 0),
(397, 62, 'يمكنك تصفح كتاب sdfsdf على الرابط التالي', 'http://localhost/lms/read_book.php?request_id=232', 232, '2025-06-13 19:40:41', '2028-03-09 19:40:41', 1, 0),
(398, 62, 'يمكنك تصفح كتاب جسمك يتذكر  على الرابط التالي', 'http://localhost/lms/read_book.php?request_id=233', 233, '2025-06-13 19:40:45', '2028-03-09 19:40:45', 1, 0),
(399, 5, 'طلب شراء جديد (2 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-13 21:02:15', NULL, 1, 0),
(403, 5, 'طلب شراء جديد (1 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-13 21:18:47', NULL, 1, 0),
(405, 5, 'طلب شراء جديد (3 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-13 21:20:04', NULL, 1, 0),
(410, 5, 'طلب جديد: استعارة كتاب', 'http://localhost/lms//admin/dashboard.php?section=ops', 240, '2025-06-13 21:33:36', '2025-06-14 21:33:36', 1, 0),
(427, 5, 'طلب جديد: استعارة كتاب', 'http://localhost/lms//admin/dashboard.php?section=ops', 241, '2025-06-14 00:31:50', '2025-06-15 00:31:50', 1, 0),
(431, 5, 'طلب شراء جديد (2 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-14 00:59:22', NULL, 1, 0),
(435, 5, 'طلب شراء جديد (2 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-14 01:05:12', NULL, 1, 0),
(436, 5, 'طلب شراء جديد (1 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-14 01:18:01', NULL, 1, 0),
(437, 5, 'طلب شراء جديد (1 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-14 01:40:12', NULL, 1, 0),
(439, 5, 'شكوى#)', 'admin/complaint.php?request_id=', NULL, '2025-06-14 02:19:35', NULL, 1, 0),
(440, 62, 'تم شحن 2,000.00 ليرة بنجاح', 'http://localhost/lms/user/dashboard.php?section=funds', NULL, '2025-06-14 04:34:36', NULL, 1, 0),
(441, 5, 'طلب جديد: استعارة كتاب', 'http://localhost/lms//admin/dashboard.php?section=ops', 248, '2025-06-14 04:34:45', '2025-06-15 04:34:45', 1, 0),
(442, 62, 'يمكنك تصفح كتاب sdfsdf على الرابط التالي لمدة 14 يوم من تاريخ هذا الإشعار', 'http://localhost/lms/read_book.php?request_id=248', 248, '2025-06-14 04:35:21', '2025-06-28 04:35:21', 1, 0),
(443, 62, 'تم شحن 14,455.00 ليرة بنجاح', 'http://localhost/lms/user/dashboard.php?section=funds', NULL, '2025-06-14 04:43:20', NULL, 1, 0),
(444, 5, 'طلب شراء جديد (2 عناصر)', 'http://localhost/lms/admin/dashboard.php?section=ops', NULL, '2025-06-14 04:43:25', NULL, 1, 0),
(445, 62, 'يمكنك تحميل كتاب ماتلاب على الرابط التالي', 'http://localhost/lms/read_book.php?request_id=249', 249, '2025-06-14 04:44:48', '2028-03-10 04:44:48', 1, 0),
(446, 62, 'يمكنك تحميل كتاب الانضباط الذاتي على الرابط التالي', 'http://localhost/lms/read_book.php?request_id=250', 250, '2025-06-14 04:44:52', '2028-03-10 04:44:52', 1, 0),
(447, 62, 'تم شحن 7,000.00 ليرة بنجاح', 'http://localhost/lms/user/dashboard.php?section=funds', NULL, '2025-06-14 05:12:09', NULL, 1, 0),
(448, 5, 'طلب جديد: استعارة كتاب', 'http://localhost/lms//admin/dashboard.php?section=ops', 251, '2025-06-14 05:12:17', '2025-06-15 05:12:17', 1, 0),
(449, 62, 'يمكنك تصفح كتاب الانضباط الذاتي على الرابط التالي لمدة 14 يوم من تاريخ هذا الإشعار', 'http://localhost/lms/read_book.php?request_id=251', 251, '2025-06-14 05:12:42', '2025-06-28 05:12:42', 1, 0),
(450, 62, 'رصيد غير كافي للمستخدم 62. الرصيد الحالي: 0.00, المطلوب: 3000', 'http://localhost/lms/user/dashboard.php?section=ops', NULL, '2025-06-14 05:52:30', NULL, 1, 0),
(451, 62, 'رصيد غير كافي للمستخدم . الرصيد الحالي: 0.00, المطلوب: 3000', 'http://localhost/lms/user/dashboard.php?section=ops', NULL, '2025-06-14 06:01:19', NULL, 1, 0),
(452, 62, 'تم شحن 25,000.00 ليرة بنجاح', 'http://localhost/lms/user/dashboard.php?section=funds', NULL, '2025-06-14 06:04:51', NULL, 1, 0),
(453, 5, 'طلب تجديد استعارة (الطلب #251)', 'admin/renew_requests.php?request_id=251', 251, '2025-06-14 06:05:05', NULL, 1, 0),
(454, 5, 'طلب تجديد استعارة (الطلب #248)', 'admin/renew_requests.php?request_id=248', 248, '2025-06-14 11:32:37', NULL, 1, 0),
(455, 62, 'يمكنك تصفح كتاب sdfsdf على الرابط التالي لمدة 14 يوم من تاريخ هذا الإشعار', 'http://localhost/lms/read_book.php?request_id=248', 248, '2025-06-14 11:34:34', '2025-06-28 11:34:34', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `discounted_total` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending'
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `discounted_total`, `created_at`, `status`) VALUES
(58, 62, '12435.00', '12435.00', '2025-06-13 16:38:41', 'pending'),
(59, 62, '12435.00', '12435.00', '2025-06-13 16:40:24', 'pending'),
(60, 62, '12000.00', '12000.00', '2025-06-13 16:42:07', 'pending'),
(70, 62, '5990.00', '5990.00', '2025-06-14 01:24:44', 'pending'),
(71, 62, '5990.00', '5990.00', '2025-06-14 01:25:53', 'pending'),
(72, 62, '6990.00', '6690.00', '2025-06-14 01:26:37', 'pending'),
(73, 62, '6990.00', '6690.00', '2025-06-14 01:28:32', 'pending'),
(74, 62, '12435.00', '12435.00', '2025-06-14 01:29:39', 'pending'),
(75, 62, '12435.00', '12435.00', '2025-06-14 01:33:48', 'pending'),
(76, 62, '15555.00', '14455.00', '2025-06-14 01:36:10', 'pending'),
(77, 62, '15555.00', '14455.00', '2025-06-14 01:37:18', 'pending'),
(78, 62, '15555.00', '14455.00', '2025-06-14 01:38:47', 'pending'),
(79, 62, '15555.00', '14455.00', '2025-06-14 01:42:42', 'pending'),
(80, 62, '15555.00', '14455.00', '2025-06-14 01:43:25', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `book_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `original_price` decimal(10,2) DEFAULT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `request_id` int DEFAULT NULL,
  `book_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `payment_type` enum('purchase','topup','borrow','renew','penalty') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `request_id`, `book_id`, `amount`, `payment_date`, `status`, `transaction_id`, `user_id`, `payment_type`) VALUES
(215, NULL, NULL, '50000.00', '2025-06-13 19:39:00', 'completed', 'TRX_1d9783d7304f9602', 62, 'topup'),
(216, 232, 55, '435.00', '2025-06-13 19:40:41', 'completed', 'TRX_9efb931d7adb2c02', 62, 'purchase'),
(217, 233, 28, '12000.00', '2025-06-13 19:40:45', 'completed', 'TRX_6cc1b67660ca9653', 62, 'purchase'),
(246, NULL, NULL, '2000.00', '2025-06-14 04:34:36', 'completed', 'TRX_b7b09abfc92586d2', 62, 'topup'),
(247, 248, 55, '7000.00', '2025-06-14 04:35:21', 'completed', 'TRX_536f5b2e95415b01', 62, 'borrow'),
(248, NULL, NULL, '14455.00', '2025-06-14 04:43:20', 'completed', 'TRX_6e057b65074c42b3', 62, 'topup'),
(249, 249, 33, '8900.00', '2025-06-14 04:44:48', 'completed', 'TRX_54d8e9d1bb01e0b0', 62, 'purchase'),
(250, 250, 31, '5555.00', '2025-06-14 04:44:52', 'completed', 'TRX_155104599c364109', 62, 'purchase'),
(251, NULL, NULL, '7000.00', '2025-06-14 05:12:09', 'completed', 'TRX_a709b2875d246fa9', 62, 'topup'),
(252, 251, 31, '7000.00', '2025-06-14 05:12:42', 'completed', 'TRX_bbd1475af46f2a11', 62, 'borrow'),
(253, 251, NULL, '1000.00', '2025-06-14 05:33:28', 'completed', 'TRX_bd870c2743e1f077', 62, 'penalty'),
(254, 251, NULL, '1000.00', '2025-06-14 05:43:22', 'completed', 'TRX_307bcab6da2bae3a', 62, 'penalty'),
(255, 251, NULL, '2000.00', '2025-06-14 05:50:33', 'completed', 'TRX_02973c86420ee9c0', 62, 'penalty'),
(256, NULL, NULL, '25000.00', '2025-06-14 06:04:51', 'completed', 'TRX_181f69c0ffeda72a', 62, 'topup'),
(257, 248, NULL, '2000.00', '2025-06-14 11:30:31', 'completed', 'TRX_2f9c3d1fd05e4368', 62, 'penalty'),
(258, 248, 55, '7000.00', '2025-06-14 11:34:34', 'completed', 'TRX_8bd0d2c3c03be24b', 62, 'renew');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` decimal(10,2) NOT NULL
) ;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'purchase_price', '50000.00'),
(2, 'rental_price', '7000.00'),
(3, 'late_fee', '1000.00');

-- --------------------------------------------------------

--
-- Table structure for table `slider_images`
--

CREATE TABLE `slider_images` (
  `id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `caption` varchar(50) NOT NULL
) ;

--
-- Dumping data for table `slider_images`
--

INSERT INTO `slider_images` (`id`, `image_path`, `is_active`, `created_at`, `caption`) VALUES
(23, 'assets/slides/6846dfda76d22_WhatsAppImage2024-01-22at9.51.36PM1.jpeg', 1, '2025-06-09 13:21:30', ''),
(24, 'assets/slides/6846dfe044768_WhatsAppImage2024-01-22at9.51.35PM1.jpeg', 1, '2025-06-09 13:21:36', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `user_type` enum('admin','user','') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint NOT NULL DEFAULT '1',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`, `created_at`, `status`, `is_deleted`, `deleted_at`, `password_reset_token`, `password_reset_expires`, `remember_token`) VALUES
(5, 'admin', 'admin@gmail.com', '$2y$10$sX7ETpVV4sueRCwqHz3PGeCOra/hmJWR9xtrwPyq0JfWI4KjM38XK', 'admin', '2025-04-03 10:34:26', 1, 0, NULL, NULL, NULL, 'ec34ca4278783e5cc363b563b770c29a4bde8b0b9229258646f57b688183c10430a0bb6a124037b9253d6210d51289509b1b53ca248e608079bd8d689f9b59c6'),
(62, 'tala', 'tala@g.c', '$2y$10$XPZVpWwfKBNoN5.Q8Fmufuru0kmOp7B.QBeAydkOS7RIJmRMPKF/m', 'user', '2025-06-13 16:09:39', 1, 0, NULL, NULL, NULL, NULL),
(65, 'rabi', 'rabi@g.c', '$2y$10$DZrrt3lnQSqwiYwz0cLE.OYhXGdl0fnHNNfsm8/7AubrSWtYnQ2Fy', 'user', '2025-06-13 23:29:29', 1, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `group_id` int NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `owner_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `unique_code` varchar(50) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `user_categories`
--

CREATE TABLE `user_categories` (
  `user_id` int NOT NULL,
  `category_id` int NOT NULL
) ;

--
-- Dumping data for table `user_categories`
--

INSERT INTO `user_categories` (`user_id`, `category_id`) VALUES
(62, 10);

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `balance`, `created_at`) VALUES
(171, 5, '125838.00', '2025-06-13 14:54:39'),
(173, 62, '16000.00', '2025-06-13 16:39:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `book_ratings`
--
ALTER TABLE `book_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorite_books`
--
ALTER TABLE `favorite_books`
  ADD PRIMARY KEY (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `group_books`
--
ALTER TABLE `group_books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `fk_group_books` (`group_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_group` (`group_id`);

--
-- Indexes for table `join_requests`
--
ALTER TABLE `join_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_join_requests` (`group_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `fk_payments_user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `slider_images`
--
ALTER TABLE `slider_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `unique_code` (`unique_code`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD PRIMARY KEY (`user_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=884;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `book_ratings`
--
ALTER TABLE `book_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=252;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `group_books`
--
ALTER TABLE `group_books`
  MODIFY `book_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `member_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `join_requests`
--
ALTER TABLE `join_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=456;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `slider_images`
--
ALTER TABLE `slider_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `users_groups`
--
ALTER TABLE `users_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `book_ratings`
--
ALTER TABLE `book_ratings`
  ADD CONSTRAINT `book_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_ratings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`),
  ADD CONSTRAINT `book_ratings_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `borrow_requests` (`id`);

--
-- Constraints for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  ADD CONSTRAINT `borrow_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrow_requests_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorite_books`
--
ALTER TABLE `favorite_books`
  ADD CONSTRAINT `favorite_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorite_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `group_books`
--
ALTER TABLE `group_books`
  ADD CONSTRAINT `fk_group_books` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_books_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`group_id`),
  ADD CONSTRAINT `group_books_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `fk_group` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`group_id`),
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `join_requests`
--
ALTER TABLE `join_requests`
  ADD CONSTRAINT `fk_join_requests` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_requests_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`group_id`),
  ADD CONSTRAINT `join_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `borrow_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD CONSTRAINT `users_groups_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD CONSTRAINT `user_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
