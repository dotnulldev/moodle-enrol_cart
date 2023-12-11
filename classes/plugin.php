<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

class enrol_cart_plugin extends enrol_plugin
{
    /**
     *Retrieve the list of available currencies with language strings that the payment system supports.
     *
     * @return array
     */
    public function getAvailableCurrencies(): array
    {
        $currencyCodes = helper::get_supported_currencies();

        $currencies = [];
        foreach ($currencyCodes as $code) {
            $currencies[$code] = new lang_string($code, 'core_currencies');
        }

        uasort($currencies, 'strcmp');

        return $currencies;
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    public function getStatusOptions(): array
    {
        return [
            ENROL_INSTANCE_ENABLED => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'),
        ];
    }

    /**
     * Return an array of valid options for the role_id.
     *
     * @param stdClass $instance
     * @param context $context
     * @return array
     */
    public function getRoleIdOptions(stdClass $instance, context $context): array
    {
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $this->get_config('assign_role'));
        }
        return $roles;
    }

    /**
     * @inheritdoc
     */
    public function get_info_icons(array $instances): array
    {
        $found = false;
        foreach ($instances as $instance) {
            if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
                continue;
            }
            if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
                continue;
            }
            $found = true;
            break;
        }
        if ($found) {
            return [new pix_icon('icon', get_string('pluginname', 'enrol_cart'), 'enrol_cart')];
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function roles_protected(): bool
    {
        // Users with role assign cap may tweak the roles later.
        return false;
    }

    /**
     * @inheritdoc
     */
    public function allow_unenrol(stdClass $instance): bool
    {
        // Users with unenrol cap may unenrol other users manually - requires enrol/cart:unenrol.
        return true;
    }

    /**
     * @inheritdoc
     */
    public function allow_manage(stdClass $instance): bool
    {
        // Users with manage cap may tweak period and status - requires enrol/cart:manage.
        return true;
    }

    /**
     * @inheritdoc
     */
    public function show_enrolme_link(stdClass $instance): bool
    {
        return $instance->status == ENROL_INSTANCE_ENABLED;
    }

    /**
     * Returns true if the user can add a new instance in this course.
     * @param int $courseid
     * @return boolean
     * @throws coding_exception
     */
    public function can_add_instance($courseid): bool
    {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (empty(helper::get_supported_currencies())) {
            return false;
        }

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/cart:config', $context)) {
            return false;
        }

        // Multiple instances supported - different cost for different roles.
        return true;
    }

    /**
     * The enrol_cart plugin support standard UI.
     *
     * @return boolean
     */
    public function use_standard_editing_ui(): bool
    {
        return true;
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array|null $fields instance fields
     * @return int|null id of new instance, null when can not be created
     * @throws coding_exception
     */
    public function add_instance($course, array $fields = null): ?int
    {
        if ($fields && !empty($fields['cost'])) {
            $fields['cost'] = unformat_float($fields['cost']);
            unset($fields['currency']);
        }
        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data): bool
    {
        if ($data) {
            $data->cost = unformat_float($data->cost);
            $instance->currency = null;
            unset($data->currency);
        }
        return parent::update_instance($instance, $data);
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance): string
    {
        global $USER, $OUTPUT, $DB;
        // user enrolled
        if (
            $DB->record_exists('user_enrolments', [
                'userid' => $USER->id,
                'enrolid' => $instance->id,
            ])
        ) {
            return '';
        }

        // enrol not started
        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return '';
        }

        // enrol ended
        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return '';
        }

        $price = helper::get_cost_as_string((float) $instance->cost, $this->get_config('currency'));
        return $OUTPUT->box(
            $OUTPUT->render_from_template('enrol_cart/enrol_page', [
                'price' => $instance->cost,
                'price_string' => $price,
                'instance_id' => $instance->id,
                'name' => $instance->name,
            ]),
        );
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     * @throws dml_exception
     * @throws coding_exception
     * @throws restore_step_exception
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid)
    {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = [
                'courseid' => $data->courseid,
                'enrol' => $this->get_name(),
                'roleid' => $data->roleid,
                'cost' => $data->cost,
                'currency' => $data->currency,
            ];
        }
        if ($merge && ($instances = $DB->get_records('enrol', $merge, 'id'))) {
            $instance = reset($instances);
            $instanceId = $instance->id;
        } else {
            $instanceId = $this->add_instance($course, (array) $data);
        }
        $step->set_mapping('enrol', $oldid, $instanceId);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     * @throws coding_exception
     */
    public function restore_user_enrolment(
        restore_enrolments_structure_step $step,
        $data,
        $instance,
        $userid,
        $oldinstancestatus
    ) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @throws coding_exception
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context)
    {
        // instance name
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        // instance status
        $mform->addElement('select', 'status', get_string('status', 'enrol_cart'), $this->getStatusOptions());
        $mform->setDefault('status', $this->get_config('status'));

        // cost
        $mform->addElement('text', 'cost', get_string('cost', 'enrol_cart'), ['dir' => 'ltr']);
        $mform->setType('cost', PARAM_RAW);

        // currency only show
        $mform->addElement(
            'select',
            'currency',
            get_string('currency', 'enrol_cart'),
            $this->getAvailableCurrencies(),
            ['disabled' => true],
        );
        $mform->setDefault('currency', $this->get_config('currency'));

        // role
        $mform->addElement(
            'select',
            'roleid',
            get_string('assign_role', 'enrol_cart'),
            $this->getRoleIdOptions($instance, $context),
        );
        $mform->setDefault('roleid', $this->get_config('assign_role'));

        // enrol period
        $mform->addElement('duration', 'enrolperiod', get_string('enrol_period', 'enrol_cart'), [
            'optional' => true,
            'defaultunit' => 86400,
        ]);
        $mform->setDefault('enrolperiod', $this->get_config('enrol_period'));
        $mform->addHelpButton('enrolperiod', 'enrol_period', 'enrol_cart');

        // enrol start date
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrol_start_date', 'enrol_cart'), [
            'optional' => true,
        ]);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrol_start_date', 'enrol_cart');

        // enrol end date
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrol_end_date', 'enrol_cart'), [
            'optional' => true,
        ]);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrol_end_date', 'enrol_cart');

        // warning text
        if (enrol_accessing_via_instance($instance)) {
            $warningText = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warningText);
        }
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     * @throws coding_exception
     */
    public function edit_instance_validation($data, $files, $instance, $context): array
    {
        $errors = [];

        //  enrol_end_date validate
        if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('error_enrol_end_date', 'enrol_cart');
        }

        // cost validate
        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost)) {
            $errors['cost'] = get_string('error_cost', 'enrol_cart');
        }

        // status validate
        if ($data['status'] == ENROL_INSTANCE_ENABLED && !$this->get_config('payment_account')) {
            $errors['status'] = get_string('error_status', 'enrol_cart');
        }

        // validate params
        $typeErrors = $this->validate_param_types($data, [
            'name' => PARAM_TEXT,
            'status' => array_keys($this->getStatusOptions()),
            'roleid' => array_keys($this->getRoleIdOptions($instance, $context)),
            'enrolperiod' => PARAM_INT,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT,
        ]);

        // return errors
        return array_merge($errors, $typeErrors);
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace): int
    {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     * @throws coding_exception
     */
    public function can_delete_instance($instance): bool
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/cart:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     * @throws coding_exception
     */
    public function can_hide_show_instance($instance): bool
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/cart:config', $context);
    }
}
