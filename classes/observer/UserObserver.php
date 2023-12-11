<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\observer;

use core\event\user_loggedin;
use enrol_cart\utility\CartHelper;

/**
 * The UserObserver class observes user login events and performs actions accordingly.
 */
class UserObserver
{
    /**
     * Handles the user_loggedin event, triggered when a user successfully logs in.
     *
     * @param user_loggedin $event The user_loggedin event object.
     * @return void
     */
    public static function userLoggedIn(user_loggedin $event)
    {
        // Move the contents of the user's cookie cart to the database upon login.
        CartHelper::moveCookieCartToDB();
    }
}
