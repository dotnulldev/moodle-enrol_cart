<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use enrol_cart\utility\CartHelper;

require_once '../../config.php';

global $PAGE, $OUTPUT, $CFG;

require_login();

$url = new moodle_url('/enrol/cart/checkout.php');
$context = context_system::instance();
$title = get_string('pluginname', 'enrol_cart') . ' - ' . get_string('checkout', 'enrol_cart');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagetype('cart');
$PAGE->set_url($url);

$cart = CartHelper::getCurrent();
if (!$cart || $cart->isEmpty) {
    redirect(new moodle_url('/enrol/cart/view.php'));
    exit();
}

$cart->refresh();

// no need to pay
if ($cart->getPayable() <= 0) {
    $cart->checkout();
    if ($cart->deliver()) {
        notification::success(get_string('msg_delivered_successfully', 'enrol_cart'));
    } else {
        notification::error(get_string('msg_delivered_failed', 'enrol_cart'));
    }
    redirect(new moodle_url('/enrol/cart/view.php', ['id' => $cart->id]));
    exit();
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('enrol_cart/checkout', [
    'cart' => $cart,
    'items' => $cart->items,
    'price_string' => $cart->getPriceString(),
    'payable_string' => $cart->getPayableString(),
]);
echo $OUTPUT->footer();
