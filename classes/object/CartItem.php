<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\object;

use core_course_list_element;
use core_payment\helper;
use dml_exception;
use moodle_url;
use stdClass;

/**
 * The CartItem class represents an item in a shopping cart and extends the functionality provided by BaseModel.
 *
 * @property int $id The unique identifier for the cart item.
 * @property int $cart_id The ID of the cart to which this item belongs.
 * @property int $instance_id The ID of the enrolment instance associated with this item.
 * @property int $price The price of the enrolment associated with this item.
 * @property int $payable The payable amount for the enrolment associated with this item.
 *
 * @property string $price_string The human-readable price.
 * @property string $payable_string The human-readable payable amount.
 *
 * @property int $course_id The ID of the course associated with this item.
 * @property string $course_name The shortname of the course associated with this item.
 * @property string $course_title The fullname of the course associated with this item.
 * @property string $course_image The URL of the course image associated with this item.
 *
 * @property Cart $cart The cart model associated with this item.
 * @property object{id: int, name: string, title: string} $course The course object associated with this item.
 */
class CartItem extends BaseModel
{
    /** @var int The item course id */
    public int $course_id;
    /** @var string The item course shortname */
    public string $course_name;
    /** @var string The item course fullname */
    public string $course_title;
    /** @var string The item course image URL */
    public string $course_image;
    /** @var string The human-readable price */
    public string $price_string;
    /** @var string The human-readable payable amount */
    public string $payable_string;
    /** @var Cart|CookieCart The item cart object */
    protected $cart;
    /** @var stdClass The item course object */
    private $_course;

    /**
     * Called after finding a cart item from the database.
     * Populates additional attributes and strings for convenient usage.
     *
     * @return void
     */
    public function afterFind()
    {
        // Populate course-related attributes and strings.
        if ($this->course) {
            $this->course_id = $this->course->id;
            $this->course_name = $this->course->name;
            $this->course_title = $this->course->title;
            $this->course_image = $this->getCourseImage();
        }

        // Convert price and payable to human-readable strings.
        $this->price_string = $this->getPriceString();
        $this->payable_string = $this->getPayableString();
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        return ['id', 'cart_id', 'instance_id', 'price', 'payable'];
    }

    /**
     * Delete the item from the database.
     *
     * @return bool True if the item is successfully deleted, false otherwise.
     */
    public function delete(): bool
    {
        global $DB;
        return $DB->delete_records('enrol_cart_items', ['id' => $this->id]);
    }

    /**
     * Return the cart item's cart model.
     *
     * @return Cart|CookieCart|null The cart model associated with this item.
     */
    public function getCart()
    {
        if (!$this->cart) {
            $this->cart = $this->cart_id ? Cart::findOne($this->cart_id) : new CookieCart();
        }
        return $this->cart;
    }

    /**
     * Return the price as a human-readable format.
     * @return string The formatted price string.
     */
    public function getPriceString(): string
    {
        if ($this->price > 0) {
            return helper::get_cost_as_string((float) $this->price, $this->getCart()->getCurrency());
        }
        return get_string('free', 'enrol_cart');
    }

    /**
     * Return the payable as a human-readable format.
     * @return string The formatted payable string.
     */
    public function getPayableString(): string
    {
        if ($this->payable > 0) {
            return helper::get_cost_as_string((float) $this->payable, $this->getCart()->getCurrency());
        }
        return get_string('free', 'enrol_cart');
    }

    /**
     * Retrieve the course object associated with the item.
     *
     * @return false|object{id: int, name: string, title: string} The course object.
     * @throws dml_exception
     */
    public function getCourse()
    {
        global $DB;

        if (!$this->_course) {
            $this->_course = $DB->get_record_sql(
                'SELECT c.id, c.shortname as name, c.fullname as title 
                 FROM {course} c 
                 INNER JOIN {enrol} e ON e.courseid = c.id 
                 WHERE e.id = :instance_id',
                ['instance_id' => $this->instance_id],
            );
        }

        return $this->_course;
    }

    /**
     * Retrieve the item course image URL.
     *
     * @return string The URL of the course image.
     */
    public function getCourseImage(): string
    {
        $courseListElement = new core_course_list_element($this->course);

        foreach ($courseListElement->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $path = implode('/', [
                    '/pluginfile.php',
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                ]);
                return (new moodle_url($path))->out();
            }
        }

        return '';
    }

    /**
     * Retrieve an array of cart item objects associated with a given cart ID.
     *
     * @param int $cartId The ID of the cart.
     * @return CartItem[] An array of cart item objects.
     */
    public static function findAll(int $cartId): array
    {
        global $DB;
        $rows = $DB->get_records('enrol_cart_items', ['cart_id' => $cartId], 'id ASC');
        return CartItem::populate($rows);
    }
}
