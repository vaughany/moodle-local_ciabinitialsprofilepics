<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Add a page/pages to admin menu.
 *
 * @package     local_initialsprofilepics
 * @copyright   2018 Paul Vaughan <paulieboo@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/locallib.php');

// Lots of goodness here: /admin/settings/users.php.
if ($hassiteconfig) {

    // Link to a 'testing' page.
    $ADMIN->add('development', new admin_externalpage('local_initialsprofilepics_testing',
        get_string('pluginname:testing', 'local_initialsprofilepics'),
        new moodle_url('/local/initialsprofilepics/testing.php')));

    // Create settings page.
    $settings = new admin_settingpage('local_initialsprofilepics', get_string('pluginname', 'local_initialsprofilepics'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_initialsprofilepics/settings',
        get_string('settings', 'local_initialsprofilepics'),
        ''
    ));

    // Check for the GD image library.
    $settings->add(new admin_setting_php_extension_enabled(
        'local_initialsprofilepics/gdenabled',
        get_string('gdenabled', 'local_initialsprofilepics'),
        get_string('gdenabled_help', 'local_initialsprofilepics'),
        'gd'
    ));

    // Enable or disable the plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_initialsprofilepics/enabled',
        get_string('enabled', 'local_initialsprofilepics'),
        get_string('enabled_help', 'local_initialsprofilepics'),
        1
    ));

    // Choose a random colour for each user rather than one based on their first initial.
    $settings->add(new admin_setting_configcheckbox(
        'local_initialsprofilepics/randomcolour',
        get_string('randomcolour', 'local_initialsprofilepics'),
        get_string('randomcolour_help', 'local_initialsprofilepics'),
        0
    ));

    // Specify a colour to force.
    $settings->add(new admin_setting_configcolourpicker(
        'local_initialsprofilepics/forcecolour',
        get_string('forcecolour', 'local_initialsprofilepics'),
        get_string('forcecolour_help', 'local_initialsprofilepics'),
        '',
        null
    ));

    // Shape.
    $shapes = [];
    foreach (INITIALSPROFILEPICS_SHAPES as $shape) {
        $shapes[$shape] = get_string("shape:{$shape}", 'local_initialsprofilepics');
    }
    $settings->add(new admin_setting_configselect(
        'local_initialsprofilepics/shape',
        get_string('shape', 'local_initialsprofilepics'),
        get_string('shape_help', 'local_initialsprofilepics'),
        INITIALSPROFILEPICS_SHAPES_SQUARE,
        $shapes
    ));

    // Font size.
    $settings->add(new admin_setting_configselect(
        'local_initialsprofilepics/fontsize',
        get_string('fontsize', 'local_initialsprofilepics'),
        get_string('fontsize_help', 'local_initialsprofilepics'),
        '1.4',
        INITIALSPROFILEPICS_FONTSIZE
    ));

    // Font alpha-transparency.
    $settings->add(new admin_setting_configselect(
        'local_initialsprofilepics/fontalpha',
        get_string('fontalpha', 'local_initialsprofilepics'),
        get_string('fontalpha_help', 'local_initialsprofilepics'),
        '0.2',
        INITIALSPROFILEPICS_FONTALPHA
    ));

    $testinglink = (object) ['link' => $CFG->wwwroot . '/local/initialsprofilepics/testing.php'];
    $settings->add(new admin_setting_heading(
        'local_initialsprofilepics/testing',
        get_string('testing', 'local_initialsprofilepics'),
        get_string('testing_help', 'local_initialsprofilepics', $testinglink)
    ));

}
