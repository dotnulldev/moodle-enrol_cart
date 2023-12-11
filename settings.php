<?php

/**
 * @package    enrol_cart
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 */

use core\output\notification;
use core_payment\helper;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('enrol_cart_settings', '', get_string('pluginname_desc', 'enrol_cart')));

    // get currencies
    $currencies = enrol_get_plugin('cart')->getAvailableCurrencies();
    // payment accounts
    $context = context_system::instance();
    $accounts = helper::get_payment_accounts_menu($context);

    // no payment account warning
    if (empty($accounts)) {
        $notify = new notification(get_string('noaccountsavilable', 'payment'), notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('enrol_cart_no_account', '', $OUTPUT->render($notify)));
    } else {
        // account
        $settings->add(
            new admin_setting_configselect(
                'enrol_cart/payment_account',
                get_string('payment_account', 'enrol_cart'),
                '',
                '',
                $accounts,
            ),
        );
    }

    // no currency warning
    if (empty($currencies)) {
        $notify = new notification(get_string('nocurrencysupported', 'core_payment'), notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('enrol_cart_no_currency', '', $OUTPUT->render($notify)));
    } else {
        // currency
        $settings->add(
            new admin_setting_configselect(
                'enrol_cart/currency',
                get_string('currency', 'enrol_cart'),
                '',
                'IRR',
                $currencies,
            ),
        );
    }

    $settings->add(
        new admin_setting_heading(
            'enrol_cart_defaults',
            get_string('enrolinstancedefaults', 'admin'),
            get_string('enrolinstancedefaults_desc', 'admin'),
        ),
    );

    // default status
    $settings->add(
        new admin_setting_configselect(
            'enrol_cart/status',
            get_string('status', 'enrol_cart'),
            get_string('status_desc', 'enrol_cart'),
            ENROL_INSTANCE_DISABLED,
            enrol_get_plugin('cart')->getStatusOptions(),
        ),
    );

    // default role
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(
            new admin_setting_configselect(
                'enrol_cart/assign_role',
                get_string('assign_role', 'enrol_cart'),
                get_string('assign_role_desc', 'enrol_cart'),
                $student->id,
                $options,
            ),
        );
    }

    // enrol period
    $settings->add(
        new admin_setting_configduration(
            'enrol_cart/enrol_period',
            get_string('enrol_period', 'enrol_cart'),
            get_string('enrol_period_desc', 'enrol_cart'),
            0,
        ),
    );
}
