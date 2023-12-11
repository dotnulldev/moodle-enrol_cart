<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\object;

use core_payment\helper;
use dml_exception;
use enrol_cart\utility\CartHelper;

/**
 * The BaseCart class provides a foundation for shopping cart functionality.
 *
 * @property CartItem[] $items An array of cart items.
 * @property int $count The total count of items in the cart.
 * @property bool $isEmpty Returns true if there are no items in the cart.
 * @property int $paymentAccountId The cart payment account ID.
 */
abstract class BaseCart extends BaseModel
{
    /** @var array An array of the cart items */
    protected array $_items = [];

    /** @var int The cart is currently active */
    public const STATUS_CURRENT = 0;
    /** @var int The user is in the process of checking out */
    public const STATUS_CHECKOUT = 10;
    /** @var int The cart has been canceled by the user */
    public const STATUS_CANCELED = 70;
    /** @var int The items in the cart have been delivered to the user */
    public const STATUS_DELIVERED = 90;

    /**
     * Return an array of possible status values for the cart.
     * @return string[] An associative array of status values with their corresponding labels.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_CURRENT => get_string('status_current', 'enrol_cart'),
            self::STATUS_CHECKOUT => get_string('status_checkout', 'enrol_cart'),
            self::STATUS_CANCELED => get_string('status_canceled', 'enrol_cart'),
            self::STATUS_DELIVERED => get_string('status_delivered', 'enrol_cart'),
        ];
    }

    /**
     * Retrieve the cart currency.
     * @return string The currency code.
     */
    public function getCurrency(): string
    {
        return (string) get_config('enrol_cart', 'currency');
    }

    /**
     * Return the total price amount of the cart items.
     * @return int|string The total price amount.
     */
    public function getPrice()
    {
        $price = 0;
        foreach ($this->items as $item) {
            $price += $item->price;
        }
        return $price;
    }

    /**
     * Return the cart price as a human-readable format.
     * @return string The formatted price string.
     */
    public function getPriceString(): string
    {
        if ($this->getPrice() > 0) {
            return helper::get_cost_as_string((float) $this->getPrice(), $this->getCurrency());
        }
        return get_string('free', 'enrol_cart');
    }

    /**
     * Return the total payable amount of the cart items.
     * @return int|string The total payable amount.
     */
    public function getPayable()
    {
        $payable = 0;
        foreach ($this->items as $item) {
            $payable += $item->payable;
        }
        return $payable;
    }

    /**
     * Return the cart payable as a human-readable format.
     * @return string The formatted payable string.
     */
    public function getPayableString(): string
    {
        if ($this->getPayable() > 0) {
            return helper::get_cost_as_string((float) $this->getPayable(), $this->getCurrency());
        }
        return get_string('free', 'enrol_cart');
    }

    /**
     * Return the total count of items in the cart.
     * @return int The total count of items.
     */
    public function getCount(): int
    {
        return count($this->items);
    }

    /**
     * Returns true if there are no items in the cart.
     * @return bool True if the cart is empty, false otherwise.
     */
    public function getIsEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Return the cart payment account ID.
     * @return int The cart payment account ID.
     * @throws dml_exception
     */
    public function getPaymentAccountId(): int
    {
        return (int) get_config('enrol_cart', 'payment_account');
    }

    /**
     * Add a course to the cart.
     * @param int $courseId The ID of the course to be added to the cart.
     * @return bool True if the course is successfully added, false otherwise.
     */
    public function addCourse(int $courseId): bool
    {
        $instanceId = CartHelper::getCourseInstanceId($courseId);
        if ($instanceId) {
            return $this->addItem($instanceId);
        }
        return false;
    }

    /**
     * Remove a course from the cart.
     * @param int $courseId The ID of the course to be removed from the cart.
     * @return bool True if the course is successfully removed, false otherwise.
     */
    public function removeCourse(int $courseId): bool
    {
        $instanceId = CartHelper::getCourseInstanceId($courseId);
        if ($instanceId) {
            return $this->removeItem($instanceId);
        }
        return false;
    }

    /**
     * Returns true if the cart contains an item with the specified enrolment instance ID.
     * @param int $instanceId The enrolment instance ID to check.
     * @return bool True if the item is in the cart, false otherwise.
     */
    public function hasItem(int $instanceId): bool
    {
        foreach ($this->items as $item) {
            if ($item->instance_id == $instanceId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Refreshes the cart items.
     * @return void
     */
    public function refresh()
    {
        $this->_items = [];
    }

    /**
     * Add an enrol item to the cart.
     * @param int $instanceId The enrolment instance ID to be added to the cart.
     * @return bool True if the item is successfully added, false otherwise.
     */
    abstract public function addItem(int $instanceId): bool;

    /**
     * Remove an enrol item from the cart.
     * @param int $instanceId The enrolment instance ID to be removed from the cart.
     * @return bool True if the item is successfully removed, false otherwise.
     */
    abstract public function removeItem(int $instanceId): bool;

    /**
     * Return an array of cart items.
     * @return CartItem[] An array of CartItem objects representing the cart items.
     */
    abstract public function getItems(): array;

    /**
     * Initiates the checkout process for the cart.
     *
     * This method typically handles the necessary steps to finalize a purchase,
     * such as payment processing and order confirmation.
     *
     * @return bool True if the checkout process is successful, false otherwise.
     */
    abstract public function checkout(): bool;

    /**
     * Cancels the cart and removes associated items.
     *
     * This method is used to cancel the current cart, removing any items
     * that were added to it during the shopping session.
     *
     * @return bool True if the cart cancellation is successful, false otherwise.
     */
    abstract public function cancel(): bool;

    /**
     * Delivers the items in the cart to the user.
     *
     * This method is responsible for processing and delivering the selected items
     * to the user, typically by enrolling them in courses or finalizing any other
     * relevant transactions associated with the cart items.
     *
     * @return bool True if the delivery process is successful, false otherwise.
     */
    abstract public function deliver(): bool;
}
