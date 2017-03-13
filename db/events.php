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
 * Add event handlers for profile picture local plugin.
 *
 * @package    local_ciabinitialsprofilepics
 * @copyright  2017 Coach in a Box <paul.vaughan@coachinabox.biz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'     => '\core\event\user_created',
        'callback'      => 'ciabinitialsprofilepics_profile_picture_exists',
        'includefile'   => '/local/ciabinitialsprofilepics/locallib.php',
        'internal'      => false,
        'priority'      => 0,
    ],
    [
        'eventname'     => '\core\event\user_updated',
        'callback'      => 'ciabinitialsprofilepics_profile_picture_exists',
        'includefile'   => '/local/ciabinitialsprofilepics/locallib.php',
        'internal'      => false,
        'priority'      => 0,
    ],
];
