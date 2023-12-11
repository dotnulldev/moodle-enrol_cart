<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\object;

use enrol_cart\utility\CartHelper;

/**
 * The CookieCart class allows for the creation of a shopping cart based on cookies.
 *
 * @example
 * ```
 * $courseId = optional_param('course', null, PARAM_INT);
 *
 * $cart = new CookieCart();
 * $cart->addCourse($courseId);
 * ```
 *
 * @property CartItem $items Represents an array of enrolment items in the cart.
 */
class CookieCart extends BaseCart
{
    /**
     * The name of the cookie storing cart items.
     * @var string
     */
    public string $cookieName = 'cart_items';

    /**
     * The path for which the cookie is available.
     * @var string
     */
    public string $cookiePath = '/';

    /**
     * The expiration time for the cookie (in seconds). Default is 30 days.
     * @var int
     */
    public int $cookieExpireTime = 86400 * 30; // 30 days

    /**
     * An array of the cart items ids that stored in cookie.
     * @var array
     */
    private array $_cookieItems = [];

    /**
     * Initializes the shopping cart by loading items from the cookie.
     */
    public function init()
    {
        if (isset($_COOKIE[$this->cookieName])) {
            $this->_cookieItems = json_decode(stripslashes($_COOKIE[$this->cookieName]), true);
        }
    }

    /**
     * Flushes the cookie cart.
     *
     * @return bool Returns true if the cookie is successfully cleared, false otherwise.
     */
    public function flush(): bool
    {
        return setcookie($this->cookieName, '', time(), $this->cookiePath);
    }

    /**
     * Updates the cookie with the current items in the cart.
     *
     * @return bool Returns true if the cookie is successfully updated, false otherwise.
     */
    protected function updateCookie(): bool
    {
        return setcookie(
            $this->cookieName,
            json_encode($this->_cookieItems),
            time() + $this->cookieExpireTime,
            $this->cookiePath,
        );
    }

    /**
     * Adds an enrolment item to the cart.
     *
     * @param int $instanceId The ID of the enrolment instance to be added.
     * @return bool Returns true if the item is successfully added to the cart, false otherwise.
     */
    public function addItem(int $instanceId): bool
    {
        if (!in_array($instanceId, $this->_cookieItems)) {
            $this->_cookieItems[] = $instanceId;
        }

        $this->refresh();

        return $this->updateCookie();
    }

    /**
     * Removes an enrolment item from the cart.
     *
     * @param int $instanceId The ID of the enrolment instance to be removed.
     * @return bool Returns true if the item is successfully removed from the cart, false otherwise.
     */
    public function removeItem(int $instanceId): bool
    {
        foreach ($this->_cookieItems as $key => $val) {
            if ($val == $instanceId) {
                unset($this->_cookieItems[$key]);
                $this->refresh();
                return $this->updateCookie();
            }
        }
        return false;
    }

    /**
     * Returns an array of CartItem objects representing the enrolment items in the cart.
     *
     * @return CartItem[] An array of CartItem objects.
     */
    public function getItems(): array
    {
        if (empty($this->_items)) {
            foreach ($this->_cookieItems as $instanceId) {
                if ($instance = CartHelper::getInstance($instanceId)) {
                    $this->_items[] = CartItem::populateOne([
                        'id' => $instance->id,
                        'cart_id' => 0,
                        'instance_id' => $instance->id,
                        'price' => $instance->cost,
                        'payable' => $instance->cost,
                        'cart' => $this,
                    ]);
                }
            }
        }
        return $this->_items;
    }

    /**
     * @inheritdoc
     *
     * Performs the checkout operation for the cookie-based shopping cart.
     * However, since cookie carts typically don't involve a traditional checkout process,
     * this implementation always returns false.
     *
     * @return bool Always returns false as checkout is not supported by cookie-based carts.
     */
    public function checkout(): bool
    {
        return false;
    }

    /**
     * Flush the cookie cart.
     * @return bool
     */
    public function cancel(): bool
    {
        return $this->flush();
    }

    /**
     * @inheritDoc
     * Delivery of order is not supported by the cookie cart, so always returns false.
     */
    public function deliver(): bool
    {
        return false;
    }
}
