<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Cart';
$string['pluginname_desc'] =
    'The cart enrolment method creates a shopping cart on the whole site and provides the possibility of adding the course to the shopping cart.';
$string['privacy:metadata'] = 'The cart enrolment plugin does not store any personal data.';

$string['cart:config'] = 'Configure cart enrol instances';
$string['cart:manage'] = 'Manage enrolled users';
$string['cart:unenrol'] = 'Unenrol users from course';
$string['cart:unenrolself'] = 'Unenrol self from the course';

$string['add_to_cart'] = 'Add to cart';
$string['cart_is_empty'] = 'Your cart is empty';
$string['price'] = 'Price';
$string['payable'] = 'Payable';
$string['total'] = 'Total';
$string['free'] = 'Free';
$string['checkout'] = 'Proceed to checkout';
$string['order_confirmation'] = 'Order Confirmation';
$string['select_payment_method'] = 'Select payment method';
$string['status_current'] = 'Current active';
$string['status_checkout'] = 'Checkout';
$string['status_canceled'] = 'Canceled';
$string['status_delivered'] = 'Delivered';
$string['unknown'] = 'Unknown';
$string['cart_status'] = 'Status';

$string['status'] = 'Enable manual enrolments';
$string['status_desc'] = 'Allow users to add a course to cart by default.';
$string['payment_account'] = 'Payment account';
$string['payment_account_help'] = 'Enrolment fees will be paid to this account.';
$string['cost'] = 'Cost';
$string['currency'] = 'Currency';
$string['assign_role'] = 'Assign role';
$string['assign_role_desc'] = 'Select the role to assign to users after making a payment.';
$string['enrol_period'] = 'Enrolment duration';
$string['enrol_period_desc'] =
    'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrol_period_help'] =
    'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
$string['enrol_start_date'] = 'Start date';
$string['enrol_start_date_help'] = 'If enabled, users can only be enrolled from this date onwards.';
$string['enrol_end_date'] = 'End date';
$string['enrol_end_date_help'] = 'If enabled, users can be enrolled until this date only.';

$string['error_enrol_end_date'] = 'The enrolment end date cannot be earlier than the start date.';
$string['error_cost'] = 'The cost must be a number.';
$string['error_status'] = 'Enrolments can not be enabled without specifying the payment account';
$string['error_invalid_cart'] = 'Invalid cart';
$string['error_disabled'] = 'The cart is disabled.';

$string['msg_delivered_successfully'] = 'Your enrolment for the course(s) below has been successfully completed.';
$string['msg_delivered_filed'] = 'There was a problem with your enrolment process.';
