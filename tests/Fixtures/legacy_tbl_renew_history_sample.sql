-- Sample rows from old_medeforum_db.tbl_renew_history (for automated tests)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

INSERT INTO `tbl_renew_history` (`id`, `renew_doctor_id`, `renewed_date`, `renew_month`, `renew_day`, `renew_year`, `renew_medeforum_amount`, `renew_insurance_amount`, `renew_insurance_coverage`, `renew_legal_service`, `renew_payment_mode`, `renew_plan_id`, `renew_policy_no`) VALUES
(3277, 9663, '2018-02-04', 'February', '04', '2018', '3900', '1180', NULL, '5', 'One Year', 3, '0310002714P109429551'),
(3709, 9663, '2019-02-04', '02', '04', '2018', '2720', '1180', NULL, '5', 'One Year', 3, '0310002714P109429551'),
(4107, 9663, '2021-02-04', '02', '04', '2021', '9033', '5664', NULL, '30', 'One Year', 3, '0310002719P114171106(2020-21)'),
(3280, 9666, '2017-03-29', '03', '29', '2019', '22850', '10305', NULL, '30', 'Three Year', 3, '0310002715P116382886'),
(4141, 9666, '2021-03-29', '03', '29', '2021', '8877', '4720', NULL, '50', 'One Year', 3, '0310002720P102458996(2020-21)');

COMMIT;
