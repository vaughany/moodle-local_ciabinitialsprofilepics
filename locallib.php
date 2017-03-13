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
 * Local libraries.
 *
 * @package    local_ciabinitialsprofilepics
 * @copyright  2017 Coach in a Box <paul.vaughan@coachinabox.biz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/vendor/autoload.php');
use Intervention\Image\ImageManager;

defined('MOODLE_INTERNAL') || die();

// O or 1. TODO: config option. Probably.
define('CIABINITIALSPROFILEPICS_BG_ALPHA', 0);

// Alpha-transparency of the letters. The colour is hard-coded to white.
define('CIABINITIALSPROFILEPICS_FG_ALPHA', 0.2);

// 'gd' or 'imagick'. TODO: config option, or choose one automatically. What does Moodle require?
define('CIABINITIALSPROFILEPICS_DRIVER', 'gd');

// Default size.
define('CIABINITIALSPROFILEPICS_SIZE', 500);

// If it's not a circle, it's square, or a rounded square..
define('CIABINITIALSPROFILEPICS_SHAPE', 'circle');

// Font to use.
// define('CIABINITIALSPROFILEPICS_FONT', 'opensans-regular.ttf');
define('CIABINITIALSPROFILEPICS_FONT', 'calibri.ttf');

// Colours.
define('CIABINITIALSPROFILEPICS_COLOURS', [
    // Default generic colours.
    "#1abc9c", "#2ecc71", "#3498db", "#9b59b6", "#34495e", "#16a085", "#27ae60", "#2980b9", "#8e44ad", "#2c3e50",
    "#f1c40f", "#e67e22", "#e74c3c", "#dce0e1", "#95a5a6", "#f39c12", "#d35400", "#c0392b", "#bdc3c7", "#7f8c8d",
]);

/**
 * Checks for the existence of a profile picture for the user.
 * @param core\event\user_created or core\event\user_updated event
 * @return bool
 */
function ciabinitialsprofilepics_profile_picture_exists($event) : bool {
    global $CFG, $DB;

    // If not enabled, bail.
    if (get_config('local_ciabinitialsprofilepics', 'enabled') == '0') {
        return true;
    }

    require_once($CFG->libdir . '/gdlib.php');

    $user = $DB->get_record('user', ['id' => $event->relateduserid]);
    if ($user->picture == 0) {
        return create_and_save_to_profile($user);
    }
    return true;
}

/**
 * Takes the user object and creates an image from the user's initials, saves it to their profile.
 * @param object user   Moodle user object.
 * @return bool
 */
function create_and_save_to_profile($user) {

    global $CFG, $DB;

    $canvas = ciabinitialsprofilepics_generate_profile_pic(ciabinitialsprofilepics_get_initials_from_user($user));

    $tempfile = $CFG->dataroot . '/temp/' . $user->id . '.png';
    $canvas->save($tempfile);

    $newpicture = (int) process_new_icon(context_user::instance($user->id, MUST_EXIST), 'user', 'icon', 0, $tempfile);
    // If you want to keep the generated images for some reason, comment out the next line.
    unlink($tempfile);

    $DB->set_field('user', 'picture', $newpicture, ['id' => $user->id]);

    return true;
}

/**
 * Creates an image and returns it as 'data-url' to be directly displayed on-screen.
 * @param array initials    An array containing two or more elements which are each a single-character string.
 * @param string shape      One of 'circle', 'square', or 'roundedsquare' at the time of writing.
 * @param int size          Size in pixels of image width and height.
 * @param string colour     Valid hex colour string with leading '#', e.g. '#f51' or '#3fd7dd'.
 * @return string           'data-url' encoded image.
 */
function create_and_dump_onscreen(array $initials = null, string $shape = null, int $size = null, string $colour = null) {
    $canvas = ciabinitialsprofilepics_generate_profile_pic($initials, $shape, $size, $colour);
    return (string) $canvas->encode('data-url');
}

/**
 * Takes a Moodle user object and creates/returns an array of single letters.
 * @param object user       User object for the created/updated user.
 * @return array            Initials.
 * TODO: potentially limit this to two initials only.
 */
function ciabinitialsprofilepics_get_initials_from_user($user) : array {
    $names = explode(' ', fullname($user));

    $initials = [];
    foreach ($names as $name) {
        $initials[] = strtoupper(trim($name[0]));
    }
    return $initials;
}

/**
 * Generates a profile image from the user's initials.
 * @param array initials    An array containing two or more elements which are each a single-character string.
 * @param string shape      One of 'circle', 'square', or 'roundedsquare' at the time of writing.
 * @param int size          Size in pixels of image width and height.
 * @param string colour     Valid hex colour string with leading '#', e.g. '#f51' or '#3fd7dd'.
 * @return Intervention image object
 */
function ciabinitialsprofilepics_generate_profile_pic(
    $initials = ['A', 'B'],
    $shape = CIABINITIALSPROFILEPICS_SHAPE,
    $size = CIABINITIALSPROFILEPICS_SIZE,
    string $colour = null
) {

    global $CFG;

    if (get_config('local_ciabinitialsprofilepics', 'randomcolour')) {
        $colour = CIABINITIALSPROFILEPICS_COLOURS[rand(0, count(CIABINITIALSPROFILEPICS_COLOURS) - 1)];

    } else if ($forcecolour = get_config('local_ciabinitialsprofilepics', 'forcecolour')) {
        $colour = $forcecolour;

    } else {
        if (is_null($colour)) {
            $charindex      = ord($initials[0]);
            // The '-5' starts capitals 'A' at 0, 'B' at 1 and so on. Just 'cos'! (Ignores 'a', 'b' etc.)
            $colourindex    = ($charindex - 5) % count(CIABINITIALSPROFILEPICS_COLOURS);
            $colour         = CIABINITIALSPROFILEPICS_COLOURS[$colourindex];
            // If no colour selected for some reason, pick a random one.
            if (!$colour) {
                $colour     = CIABINITIALSPROFILEPICS_COLOURS[rand(0, count(CIABINITIALSPROFILEPICS_COLOURS) - 1)];
            }
        }
    }

    $image = new ImageManager(['driver' => CIABINITIALSPROFILEPICS_DRIVER]);

    if ($shape == 'circle') {
        $canvas = $image->canvas(CIABINITIALSPROFILEPICS_SIZE, CIABINITIALSPROFILEPICS_SIZE, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->circle(CIABINITIALSPROFILEPICS_SIZE, CIABINITIALSPROFILEPICS_SIZE / 2, CIABINITIALSPROFILEPICS_SIZE / 2, function ($draw) use ($colour) {
            $draw->background($colour);
            // TODO: Borders? Looks bad. :(
            // $draw->border(5, '#0000ff');
        });

    } else if ($shape == 'roundedsquare') {
        $canvas = $image->canvas(CIABINITIALSPROFILEPICS_SIZE, CIABINITIALSPROFILEPICS_SIZE, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->circle(200, 100, 100, function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->circle(200, CIABINITIALSPROFILEPICS_SIZE - 100, 100, function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->circle(200, CIABINITIALSPROFILEPICS_SIZE - 100, CIABINITIALSPROFILEPICS_SIZE - 100, function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->circle(200, 100, CIABINITIALSPROFILEPICS_SIZE - 100, function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $points = [
            105, 0,
            CIABINITIALSPROFILEPICS_SIZE - 105, 0,
            CIABINITIALSPROFILEPICS_SIZE, 105,
            CIABINITIALSPROFILEPICS_SIZE, CIABINITIALSPROFILEPICS_SIZE - 105,
            CIABINITIALSPROFILEPICS_SIZE - 105, CIABINITIALSPROFILEPICS_SIZE,
            105, CIABINITIALSPROFILEPICS_SIZE,
            0, CIABINITIALSPROFILEPICS_SIZE - 105,
            0, 105
        ];
        $canvas->polygon($points, function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else {
        $canvas = $image->canvas(CIABINITIALSPROFILEPICS_SIZE, CIABINITIALSPROFILEPICS_SIZE, $colour);
        // If we want a border here, I think we'll need to make the canvas transparent and draw a square.
    }

    for ($j = 0; $j <= count($initials) - 1; $j++) {
        // For now, hard-coded limit of 2. TODO: future development to use more.
        if ($j == 2) {
            break;
        }
        $canvas->text($initials[$j], 175 * ($j + 1), 240, function ($font) {
            $font->file(__DIR__ . '/fonts/' . CIABINITIALSPROFILEPICS_FONT);
            $font->size(700);
            $font->color([255, 255, 255, CIABINITIALSPROFILEPICS_FG_ALPHA]);
            $font->valign('middle');
            $font->align('center');
            $font->angle(-15);
        });
    }

    // Resizing.
    if ($size != CIABINITIALSPROFILEPICS_SIZE) {
        $canvas->resize($size, $size);
    }

    return $canvas;
}
