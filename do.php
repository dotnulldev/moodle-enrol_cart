<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_cart\utility\CartHelper;

require_once '../../config.php';

$action = required_param('action', PARAM_ALPHANUMEXT);

// the cart enrolment disabled
if (!enrol_is_enabled('cart')) {
    print_error('error_disabled', 'enrol_cart');
}

// get current cart
$cart = CartHelper::getCurrent(true);

// add or remove an item or a course
if ($action == 'add' || $action == 'remove') {
    $instanceId = optional_param('instance', null, PARAM_INT);
    $courseId = optional_param('course', null, PARAM_INT);

    if (!$instanceId && !$courseId) {
        print_error('CourseID or InstanceID is required.');
    }

    if ($action == 'add') {
        if ($instanceId) {
            $cart->addItem($instanceId);
        } elseif ($courseId) {
            $cart->addCourse($courseId);
        }
    } elseif ($action == 'remove') {
        if ($instanceId) {
            $cart->removeItem($instanceId);
        } elseif ($courseId) {
            $cart->removeCourse($courseId);
        }
    }
}

redirect(new moodle_url('/enrol/cart/view.php'));
