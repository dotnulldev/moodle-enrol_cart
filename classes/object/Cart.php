<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\object;

use dml_exception;
use enrol_cart\utility\CartHelper;
use Exception;
use html_writer;

/**
 * The Cart class represents a shopping cart and extends the functionality provided by BaseCart.
 *
 * @inheritDoc
 *
 * @property int $id The unique identifier for the cart.
 * @property int $user_id The user ID associated with the cart.
 * @property int $status The status of the cart.
 * @property string $price The total price of items in the cart.
 * @property string $payable The total payable amount of items in the cart.
 * @property string $currency The currency code of the cart.
 * @property int $created_at The timestamp when the cart was created.
 * @property int $created_by The user ID who created the cart.
 * @property int $updated_at The timestamp when the cart was last updated.
 * @property int $updated_by The user ID who last updated the cart.
 *
 * @property bool $canEditItems Returns true if items in the cart can be edited, false otherwise.
 * @property CartItem[] $items An array of CartItem objects representing the items in the cart.
 */
class Cart extends BaseCart
{
    /**
     * @inheritdoc
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'id',
            'user_id',
            'status',
            'price',
            'payable',
            'currency',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
        ];
    }

    /**
     * Retrieve the cart price.
     * @return int|string The total price of items in the cart.
     */
    public function getPrice()
    {
        return $this->price ?: parent::getPrice();
    }

    /**
     * Retrieve the cart payable.
     * @return int|string The total payable amount of items in the cart.
     */
    public function getPayable()
    {
        return $this->payable ?: parent::getPayable();
    }

    /**
     * Retrieve the cart currency.
     * @return string The currency code of the cart.
     */
    public function getCurrency(): string
    {
        return $this->currency ?: parent::getCurrency();
    }

    /**
     * Add an item to the cart.
     *
     * @param int $instanceId The ID of the enrolment instance to be added to the cart.
     * @return bool True if the item is successfully added, false otherwise.
     */
    public function addItem(int $instanceId): bool
    {
        global $DB;

        if ($this->canEditItems && !$this->hasItem($instanceId) && ($instance = CartHelper::getInstance($instanceId))) {
            $item = (object) [
                'cart_id' => $this->id,
                'instance_id' => $instance->id,
                'price' => $instance->cost,
                'payable' => $instance->cost,
            ];
            if ($DB->insert_record('enrol_cart_items', $item)) {
                $this->refresh();
                return true;
            }
        }

        return false;
    }

    /**
     * Remove an item from the cart.
     *
     * @param int $instanceId The ID of the enrolment instance to be removed from the cart.
     * @return bool True if the item is successfully removed, false otherwise.
     */
    public function removeItem(int $instanceId): bool
    {
        if ($this->canEditItems) {
            foreach ($this->items as $item) {
                if ($item->instance_id == $instanceId && $item->delete()) {
                    $this->refresh();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     * @return CartItem[] An array of CartItem objects representing the cart items.
     */
    public function getItems(): array
    {
        if (empty($this->_items)) {
            $this->_items = CartItem::findAll($this->id);
        }
        return $this->_items;
    }

    /**
     * Refresh the cart items.
     * Calculate the price and payable.
     * Remove disabled or invalid enrol items.
     * @return void
     */
    public function refresh()
    {
        global $DB, $USER;
        $this->_items = [];

        if ($this->canEditItems) {
            // reset price and payable to calculate again
            $this->price = 0;
            $this->payable = 0;

            // remove disabled or invalid enrol from the cart
            $items = CartItem::findAll($this->id);
            foreach ($items as $item) {
                if (!CartHelper::hasInstance($item->instance_id)) {
                    $item->delete();
                }
            }

            // update db
            $DB->update_record(
                'enrol_cart',
                (object) [
                    'id' => $this->id,
                    'price' => $this->getPrice(),
                    'payable' => $this->getPayable(),
                    'updated_at' => time(),
                    'updated_by' => $USER->id,
                ],
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function checkout(): bool
    {
        return $this->setStatus(self::STATUS_CHECKOUT);
    }

    /**
     * @inheritDoc
     */
    public function cancel(): bool
    {
        return $this->setStatus(self::STATUS_CANCELED);
    }

    /**
     * Deliver method processes the user course enrolments.
     *
     * @return bool True if the delivery is successful, false otherwise.
     */
    public function deliver(): bool
    {
        global $DB;

        try {
            // Start a delegated transaction to ensure atomicity.
            $transaction = $DB->start_delegated_transaction();

            // Get the cart enrolment plugin.
            $plugin = enrol_get_plugin('cart');

            // Loop through each item in the cart for delivery.
            foreach ($this->items as $item) {
                // Retrieve enrolment instance details from the database.
                $instance = $DB->get_record(
                    'enrol',
                    [
                        'id' => $item->instance_id,
                        'enrol' => 'cart',
                    ],
                    '*',
                    MUST_EXIST,
                );

                // Set the enrolment period (if applicable).
                $timeStart = 0;
                $timeEnd = 0;
                if ($instance->enrolperiod) {
                    $timeStart = time();
                    $timeEnd = $timeStart + $instance->enrolperiod;
                }

                // Enrol the user in the course using the cart plugin.
                $plugin->enrol_user($instance, $this->user_id, $instance->roleid, $timeStart, $timeEnd);
            }

            // Update the cart status to indicate successful delivery.
            $this->setStatus(self::STATUS_DELIVERED);

            // Allow the transaction to commit.
            $transaction->allow_commit();

            // Return true to indicate successful delivery.
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of an exception.
            $transaction->rollback($e);

            // Return false to indicate delivery failure.
            return false;
        }
    }

    /**
     * Return the cart object.
     * @param bool $forceNew Create an active cart on the database for the current user.
     * @return Cart|null
     */
    public static function findCurrent(bool $forceNew = false): ?Cart
    {
        global $DB, $USER;

        static $current = null;

        if (!$current) {
            $cart = $DB->get_record('enrol_cart', [
                'user_id' => $USER->id,
                'status' => self::STATUS_CURRENT,
            ]);
            if (!$cart && $forceNew) {
                $cart = (object) [
                    'user_id' => $USER->id,
                    'status' => self::STATUS_CURRENT,
                    'created_at' => time(),
                    'created_by' => $USER->id,
                ];
                $cart->id = $DB->insert_record('enrol_cart', $cart);
            }
            $current = $cart ? static::populateOne($cart) : null;
        }

        return $current;
    }

    /**
     * @param int $id The ID of the cart to retrieve.
     * @return Cart|null
     * @throws dml_exception
     */
    public static function findOne(int $id): ?Cart
    {
        global $DB;
        $cart = $DB->get_record('enrol_cart', [
            'id' => $id,
        ]);
        if ($cart) {
            return static::populateOne($cart);
        }
        return null;
    }

    /**
     * Check if items in the shopping cart can be edited.
     *
     * @return bool Returns true if the cart is currently active and items can be edited, false otherwise.
     */
    public function getCanEditItems(): bool
    {
        return $this->status == self::STATUS_CURRENT;
    }

    /**
     * Update the cart status value and save on the database.
     *
     * @param int $status The new status value for the cart.
     * @return bool True if the status is successfully updated, false otherwise.
     * @throws dml_exception
     */
    protected function setStatus(int $status): bool
    {
        $statusOptions = self::getStatusOptions();
        if (isset($statusOptions[$status])) {
            global $DB, $USER;
            $this->status = $status;
            $data = [
                'id' => $this->id,
                'status' => $this->status,
                'updated_at' => time(),
                'updated_by' => $USER->id,
            ];
            if ($status == self::STATUS_CHECKOUT) {
                $data['currency'] = $this->getCurrency();
            }
            return $DB->update_record('enrol_cart', (object) $data);
        }
        return false;
    }

    /**
     * Get the status string of the current cart status.
     *
     * @return string Returns the status string corresponding to the current cart status. If the status is not found in the options, it returns the string 'unknown'.
     */
    public function getStatusString(): string
    {
        $statusOptions = self::getStatusOptions();
        return $statusOptions[$this->status] ?? get_string('unknown', 'enrol_cart');
    }

    /**
     * Get the formatted status string for display.
     *
     * @return string Returns the formatted status string as an HTML span element with the appropriate CSS class.
     */
    public function getStatusStringFormatted(): string
    {
        $className = 'badge';
        switch ($this->status) {
            case self::STATUS_CHECKOUT:
                $className = 'badge badge-info';
                break;
            case self::STATUS_DELIVERED:
                $className = 'badge badge-success';
                break;
            case self::STATUS_CANCELED:
                $className = 'badge badge-warning';
                break;
        }
        return html_writer::tag('span', $this->getStatusString(), ['class' => $className]);
    }
}
