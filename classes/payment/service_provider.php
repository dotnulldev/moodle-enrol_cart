<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\payment;

use core\notification;
use core_payment\local\entities\payable;
use enrol_cart\object\BaseCart;
use enrol_cart\object\Cart;
use moodle_url;

class service_provider implements \core_payment\local\callback\service_provider
{
    /**
     * @inheritdoc
     */
    public static function get_payable(string $paymentArea, int $itemId): payable
    {
        global $USER;
        $cart = Cart::findOne($itemId);
        if ($cart && $cart->user_id == $USER->id) {
            $cart->refresh();
            if (
                $cart->getPayable() > 0 &&
                ($cart->status == BaseCart::STATUS_CURRENT || $cart->status == BaseCart::STATUS_CHECKOUT)
            ) {
                $cart->checkout();
                return new payable($cart->payable, $cart->currency, $cart->paymentAccountId);
            }
        }
        return new payable(-1, '', -1);
    }

    /**
     * @inheritdoc
     */
    public static function get_success_url(string $paymentArea, int $itemId): moodle_url
    {
        return new moodle_url('/enrol/cart/view.php', ['id' => $itemId]);
    }

    /**
     * @inheritdoc
     */
    public static function deliver_order(string $paymentArea, int $itemId, int $paymentId, int $userId): bool
    {
        $cart = Cart::findOne($itemId);
        if ($cart->user_id == $userId && $cart->deliver()) {
            notification::success(get_string('msg_delivered_successfully', 'enrol_cart'));
            return true;
        }
        notification::error(get_string('msg_delivered_failed', 'enrol_cart'));
        return false;
    }
}
