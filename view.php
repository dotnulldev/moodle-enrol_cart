<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_cart\object\Cart;
use enrol_cart\utility\CartHelper;

require_once '../../config.php';

global $PAGE, $OUTPUT, $CFG, $USER;

$id = optional_param('id', null, PARAM_INT);

if ($id) {
    require_login();
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'enrol_cart'));
$PAGE->set_heading(get_string('pluginname', 'enrol_cart'));
$PAGE->set_pagetype('cart');
$PAGE->set_url(new moodle_url('/enrol/cart/view.php'));

// render a cart view
if ($id) {
    $cart = Cart::findOne($id);
    if ($cart && ($cart->user_id == $USER->id || has_capability('enrol/cart:manage', $context))) {
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('enrol_cart/view', [
            'cart' => $cart,
            'items' => $cart->items,
            'status_string' => $cart->getStatusStringFormatted(),
            'price_string' => $cart->getPriceString(),
            'payable_string' => $cart->getPayableString(),
        ]);
        echo $OUTPUT->footer();
        exit();
    }
    print_error('error_invalid_cart', 'enrol_cart');
}

// get current active cart
$cart = CartHelper::getCurrent();

// render the current active cart view
if ($cart && !$cart->isEmpty) {
    // refresh the cart to validate and update the items price
    $cart->refresh();

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('enrol_cart/view_current', [
        'cart' => $cart,
        'items' => $cart->items,
        'price_string' => $cart->getPriceString(),
        'payable_string' => $cart->getPayableString(),
    ]);
    echo $OUTPUT->footer();
    exit();
}

// render the empty cart view
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('enrol_cart/view_empty', []);
echo $OUTPUT->footer();
