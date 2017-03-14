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
 * English strings for local_ciabinitialsprofilepics.
 *
 * @package    local_ciabinitialsprofilepics
 * @copyright  2017 Coach in a Box <paul.vaughan@coachinabox.biz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'CiaB Initials Profile Pics';
$string['pluginname:testing'] = 'CiaB Initials Profile Pics - Testing';

$string['settings'] = 'Settings';

$string['enabled'] = 'Enabled?';
$string['enabled_help'] = 'Check this box to make this plugin generate profile pictures; uncheck it to stop.';

$string['randomcolour'] = 'Random colour';
$string['randomcolour_help'] = "Colours are automatically chosen based on a user's first initial. Check this to choose a colour from the built-in palette utterly at random instead.";

$string['forcecolour'] = 'Force a specific colour';
$string['forcecolour_help'] = "If you would like all images to use a specific background colour, choose it from the palette or type it in to the text box. To remove it, remove everything from the text box. (This overrides the 'Random Colour' option, above.)";

$string['gdenabled'] = 'GD image library missing.';
$string['gdenabled_help'] = 'The GD image library is a prerequisite for this plugin AND Moodle, but does not seem to be installed.';

$string['testing'] = 'Testing';
$string['testing_help'] = '<a href="{$a->link}">Click here</a> to test the plugin works correctly. (Save any changes first.)';
