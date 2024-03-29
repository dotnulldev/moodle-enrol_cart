<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\utility;

use context_course;
use enrol_cart\object\Cart;
use enrol_cart\object\CookieCart;
use stdClass;

/**
 * The CartHelper class provides utility functions for managing shopping cart-related operations.
 */
class CartHelper
{
    /**
     * Return the first cart-enrolment-instance-id of a course.
     *
     * @param int $courseId The ID of the course.
     * @return int The cart enrolment instance ID of the course.
     */
    public static function getCourseInstanceId(int $courseId): int
    {
        global $DB;
        $instances = $DB->get_records(
            'enrol',
            [
                'courseid' => $courseId,
                'enrol' => 'cart',
                'status' => ENROL_INSTANCE_ENABLED,
            ],
            'sortorder ASC',
            'id',
        );
        foreach ($instances as $instance) {
            return $instance->id;
        }
        return 0;
    }

    /**
     * Return an active cart enrol record.
     *
     * @param int $instanceId The ID of the enrolment instance.
     * @return false|stdClass The cart enrolment record or false if not found.
     */
    public static function getInstance(int $instanceId)
    {
        global $DB;
        static $cache = [];
        if (!isset($cache[$instanceId])) {
            $instance = $DB->get_record('enrol', [
                'id' => $instanceId,
                'enrol' => 'cart',
                'status' => ENROL_INSTANCE_ENABLED,
            ]);
            if (
                (!$instance->enrolstartdate || $instance->enrolstartdate < time()) &&
                (!$instance->enrolenddate || $instance->enrolenddate > time())
            ) {
                $cache[$instanceId] = $instance;
            }
        }
        return $cache[$instanceId];
    }

    /**
     * Return true if the enrol method exists.
     *
     * @param int $instanceId The ID of the enrolment instance.
     * @return bool Returns true if the enrol method exists, false otherwise.
     */
    public static function hasInstance(int $instanceId): bool
    {
        return !!self::getInstance($instanceId);
    }

    /**
     * Check if a user is enrolled in a specified instance.
     *
     * This method checks whether a user is enrolled in the specified instance.
     *
     * @param int $instanceId The ID of the enrolment instance.
     * @param int $userId The ID of the user.
     * @param bool $anyInstance (Optional) Whether to check enrollment in any instance of the course.
     * @return bool Returns true if the user is enrolled, otherwise false.
     */
    public static function isUserEnrolled(int $instanceId, int $userId, bool $anyInstance = false): bool
    {
        global $DB;

        // If anyInstance flag is set and the instance exists, check enrollment in the course
        if ($anyInstance && self::hasInstance($instanceId)) {
            $instance = self::getInstance($instanceId);
            $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
            $context = context_course::instance($course->id);
            return is_enrolled($context, $userId); // Check enrollment in the course context
        }

        // Otherwise, check enrollment in the specified instance
        return $DB->record_exists('user_enrolments', [
            'enrolid' => $instanceId,
            'userid' => $userId,
            'status' => ENROL_USER_ACTIVE,
        ]);
    }

    /**
     * Return the cart object.
     *
     * @param bool $forceNew Create an active cart on the database for the current user.
     * @return Cart|CookieCart|null The shopping cart object.
     */
    public static function getCurrent(bool $forceNew = false)
    {
        global $USER;

        static $current = null;

        if (!$current) {
            if (!$USER->id || isguestuser()) {
                $current = new CookieCart();
            } else {
                $current = Cart::findCurrent($forceNew);
            }
        }

        return $current;
    }

    /**
     * Move the not-authenticated user cookie cart to the database when the user logs in.
     *
     * @return void
     * @see UserObserver::userLoggedIn()
     */
    public static function moveCookieCartToDB()
    {
        global $USER;

        $cookieCart = new CookieCart();

        if (empty($cookieCart->items) || !$USER->id || isguestuser()) {
            return;
        }

        $cart = Cart::findCurrent(true);
        foreach ($cookieCart->items as $item) {
            $cart->addItem($item->instance_id);
        }

        $cookieCart->flush();
    }
}
