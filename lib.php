<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_cart\utility\CartHelper;

function enrol_cart_render_navbar_output(renderer_base $renderer)
{
    if (!enrol_is_enabled('cart')) {
        return '';
    }

    $cart = CartHelper::getCurrent();
    return $renderer->render_from_template('enrol_cart/cart_button', [
        'count' => $cart ? $cart->count : 0,
        'view_url' => new moodle_url('/enrol/cart/view.php'),
    ]);
}
