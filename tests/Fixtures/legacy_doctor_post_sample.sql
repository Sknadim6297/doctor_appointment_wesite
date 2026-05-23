-- Sample legacy tbl_doctor_post rows for tests
INSERT INTO `tbl_doctor_post` (`post_id`, `doctor_id`, `consignment_document_id`, `post_date`, `post_document_name`, `consignment_no`, `post_by`, `recieved_date`, `recieved_by`, `remark`, `created_date`, `created_by`, `edited_date`, `edited_by`, `post_month`, `post_year`, `tracking_link`) VALUES
(90001, 9663, 83, '2016-04-27', 'Consignment form', 'K87735856', 'DTDC', '2016-04-28', 'Office Desk', 'Imported remark', '2018-05-18', 1, NULL, 0, '05', '2018', ''),
(90002, 99999, 142, '2016-04-28', 'Orphan post', 'K99999999', 'DTDC', '2016-04-28', '', '', '2018-05-18', 1, NULL, 0, '05', '2018', 'https://example.com/track/90002');
