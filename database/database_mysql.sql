ALTER TABLE `store_orders`
    ADD `lesson` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `digital`,
    ADD `postcode` VARCHAR(5) NULL DEFAULT NULL AFTER `lesson`,
    ADD `band` VARCHAR(5) NULL DEFAULT NULL AFTER `postcode`;

ALTER TABLE `store_products`
    ADD `lesson` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `digitalloc`,
    ADD `lessonrelation` VARCHAR(30) NULL DEFAULT NULL AFTER `lesson`;

INSERT INTO `store_config` (`setting`, `value`) VALUES
('email_lesson_purchase_subject', 'driving lessons order confirmation'),
('email_lesson_purchase_body', '<p>Dear %1$s %2$s</p>
<p>Thank you for your order #%3$s.</p>
<p>My name is %4$s and I have been assigned as your customer care officer. Please feel free to contact me if you have any queries about the goods or services ordered.</p>
<p>I will contact you very shortly to assign a local driving instructor and make arrangements for the training to start. I will also arrange for any goods to be despatched as soon as possible.</p>
<p>Please do not hesitate to contact me if you have any queries in the meantime.</p>
<p>Yours sincerely</p>
<p>%4$s</p>'),
('email_lesson_purchase_altbody', "Hi %1$s, \n\r\n\r This email has been sent to confirm that your password has been updated successfully. \n\r\n\r If you feel that your account has been updated by someone other than yourself please contact support %2$s/%3$s  if you have questions. \n\r\n\r Regards \n\r\n\r %4$s
Dear %1$s %2$s, \n\r\n\r Thank you for your order #%3$s. \n\r\n\r My name is %4$s and I have been assigned as your customer care officer. Please feel free to contact me if you have any queries about the goods or services ordered. \n\r\n\r I will contact you very shortly to assign a local driving instructor and make arrangements for the training to start. I will also arrange for any goods to be despatched as soon as possible. \n\r\n\r Please do not hesitate to contact me if you have any queries in the meantime. \n\r\n\r  Yours sincerely \n\r\n\r %4$s"),

('email_office_lesson_subject', 'Driving Lesson Order'),
('email_office_lesson_body', '<p>You have been assigned as the Customer Care Officer for order #%1$s from %2$s.</p>
<hr />
<p><strong>The order details are as follows:</strong></p>
<table width="595" border="0" cellpadding="0" cellspacing="0">
<tr>
<td><strong>Delivery Information</strong></td>
<td><strong>Billing Information</strong></td>
</tr>
<tr>
<td valign="top">%3$s %4$s %5$s<br />
%6$s<br />
%7$s<br />
%8$s<br />
%9$s<br />
%10$s<br /><br />
<strong>Phone No:</strong> %11$s<br />
<strong>Mobile No:</strong> %12$s<br />
<strong>Email:</strong> %13$s</td>
<td valign="top">%14$s %15$s %16$s<br />
%17$s<br />
%18$s<br />
%19$s<br />
%20$s<br />
%21$s</td>
</tr>
</table><br />
<hr />
<p>The postcode area entered by the the client for the lessons to be taken was <strong>%22$s</strong></p>
%23$s
<hr />
<p><strong>Please arrange to despatch any goods and contact the customer as soon as possible.</strong></p>'),
('email_office_lesson_altbody', 'You have been assigned as the Customer Care Officer for order #%1$s from %2$s. \n\r\n\r The order details are as follows: \n\r\n\r Delivery Information \n\r\n\r %3$s %4$s %5$s \n\r %6$s \n\r %7$s \n\r %8$s \n\r %9$s \n\r %10$s \n\r\n\r Phone No: %11$s \n\r Mobile No: %12$s \n\r Email: %13$s \n\r\n\r Billing Information \n\r\n\r %14$s %15$s %16$s \n\r %17$s \n\r %18$s \n\r %19$s \n\r %20$s \n\r %21$s \n\r\n\r The postcode area entered by the the client for the lessons to be taken was %22$s \n\r\n\r %23$s \n\r\n\r Please arrange to despatch any goods and contact the customer as soon as possible.');