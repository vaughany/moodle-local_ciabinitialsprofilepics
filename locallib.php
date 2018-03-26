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
 * @package     local_initialsprofilepics
 * @copyright   2018 Paul Vaughan <paulieboo@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/initialsprofilepics/vendor/autoload.php');
use Intervention\Image\ImageManager;

// O or 1. TODO: config option. Probably.
define('INITIALSPROFILEPICS_BG_ALPHA', 0);

// This can be 'gd' or 'imagick', but Moodle already requires gd, so use that.
define('INITIALSPROFILEPICS_DRIVER', 'gd');

// Default size.
define('INITIALSPROFILEPICS_SIZE', 200);

// Shapes options.
define('INITIALSPROFILEPICS_SHAPES_CIRCLE', 'circle');
define('INITIALSPROFILEPICS_SHAPES_SQUARE', 'square');
define('INITIALSPROFILEPICS_SHAPES_ROUNDEDSQUARE', 'rounded_square');
define('INITIALSPROFILEPICS_SHAPES_UPSLASH', 'upslash');
define('INITIALSPROFILEPICS_SHAPES_DOWNSLASH', 'downslash');
define('INITIALSPROFILEPICS_SHAPES_LEFTSLASH', 'leftslash');
define('INITIALSPROFILEPICS_SHAPES_RIGHTSLASH', 'rightslash');
define('INITIALSPROFILEPICS_SHAPES_HEXAGON_HORIZONTAL', 'hexagon_horizontal');
define('INITIALSPROFILEPICS_SHAPES_HEXAGON_VERTICAL', 'hexagon_vertical');
define('INITIALSPROFILEPICS_SHAPES_STAR', 'star');
define('INITIALSPROFILEPICS_SHAPES', [
    INITIALSPROFILEPICS_SHAPES_CIRCLE,
    INITIALSPROFILEPICS_SHAPES_SQUARE,
    INITIALSPROFILEPICS_SHAPES_ROUNDEDSQUARE,
    INITIALSPROFILEPICS_SHAPES_UPSLASH,
    INITIALSPROFILEPICS_SHAPES_DOWNSLASH,
    INITIALSPROFILEPICS_SHAPES_LEFTSLASH,
    INITIALSPROFILEPICS_SHAPES_RIGHTSLASH,
    INITIALSPROFILEPICS_SHAPES_HEXAGON_HORIZONTAL,
    INITIALSPROFILEPICS_SHAPES_HEXAGON_VERTICAL,
    INITIALSPROFILEPICS_SHAPES_STAR,
]);

// If it's not a circle, it's square, or a rounded square.
define('INITIALSPROFILEPICS_SHAPE', INITIALSPROFILEPICS_SHAPES_SQUARE);

// Font to use.
define('INITIALSPROFILEPICS_FONT', 'opensans-regular.ttf');
// define('INITIALSPROFILEPICS_FONT', 'calibri.ttf');

// Colours.
define('INITIALSPROFILEPICS_COLOURS', [
    // Default generic colours.
    "#1abc9c", "#2ecc71", "#3498db", "#9b59b6", "#34495e", "#16a085", "#27ae60", "#2980b9", "#8e44ad", "#2c3e50",
    "#f1c40f", "#e67e22", "#e74c3c", "#dce0e1", "#95a5a6", "#f39c12", "#d35400", "#c0392b", "#bdc3c7", "#7f8c8d",
]);

// Colours used to darken and lighten other colours by layering on top.
define('INITIALSPROFILEPICS_COLOUR_DARKEN', [0, 0, 0, .3]);
define('INITIALSPROFILEPICS_COLOUR_LIGHTEN', [255, 255, 255, .2]);

// Pool of letters (and numbers, symbols as appropriate) for testing and if no initials supplied.
// define('INITIALSPROFILEPICS_INITIALSPOOL', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!?#@');
define('INITIALSPROFILEPICS_INITIALSPOOL', 'ABCDE12345!?#@¥£€$¢₡₢₣₤₥₦₧₨₩₪₫₭₮₯₹·');

// Font sizes.
define('INITIALSPROFILEPICS_FONTSIZE', [
    '1.4'   => get_string('extralarge', 'local_initialsprofilepics'),
    '1.2'   => get_string('large', 'local_initialsprofilepics'),
    '1.0'   => get_string('medium', 'local_initialsprofilepics'),
    '0.8'   => get_string('small', 'local_initialsprofilepics'),
]);

// Font alpha-transparency.
define('INITIALSPROFILEPICS_FONTALPHA', [
    '0.2'   => '20%',
    '0.3'   => '30%',
    '0.4'   => '40%',
    '0.5'   => '50%',
    '0.6'   => '60%',
    '0.7'   => '70%',
    '0.8'   => '80%',
]);

/**
 * Checks for the existence of a profile picture for the user.
 * @param core\event\user_created or core\event\user_updated event
 * @return bool
 */
function initialsprofilepics_profile_picture_exists($event) : bool {
    global $CFG, $DB;

    // If not enabled, bail.
    if (get_config('local_initialsprofilepics', 'enabled') == '0') {
        return true;
    }

    require_once($CFG->libdir . '/gdlib.php');

    $user = $DB->get_record('user', ['id' => $event->relateduserid]);
    if ($user->picture == 0) {
        return initialsprofilepics_create_and_save_to_profile($user);
    }
    return true;
}

/**
 * Takes the user object and creates an image from the user's initials, saves it to their profile.
 * @param object user   Moodle user object.
 * @return bool
 */
function initialsprofilepics_create_and_save_to_profile($user) : bool {

    global $CFG, $DB, $usernew;

    if (!isset($usernew)) {
        return false;
    }

    $initials   = initialsprofilepics_get_initials_from_user($user);
    // TODO: config option.
    $shape      = get_config('local_initialsprofilepics', 'shape');
    $colour     = initialsprofilepics_get_working_colour($initials);
    $size       = 500;
    $fontsize   = get_config('local_initialsprofilepics', 'fontsize');
    $fontalpha  = get_config('local_initialsprofilepics', 'fontalpha');

    // Initials, shape, colour, size, fontsize, fontalpha.
    $canvas = initialsprofilepics_generate_profile_pic($initials, $shape, $colour, $size, $fontsize, $fontalpha);

    $tempfile = initialsprofilepics_save_to_disk($canvas, $user);

    $newpicture = (int) process_new_icon(context_user::instance($user->id, MUST_EXIST), 'user', 'icon', 0, $tempfile);
    $DB->set_field('user', 'picture', $newpicture, ['id' => $user->id]);

    $usernew->picture = $newpicture;

    return true;
}

/**
 * Performs the action of saving the canvas object to disk, temporarily.
 * @param object canvas         Canvas image object.
 * @param object user           User object.
 * @return path to temporary file and folder.
 */
function initialsprofilepics_save_to_disk($canvas, $user) {
    $tempfile = make_request_directory() . $user->id . '.png';
    $canvas->save($tempfile);
    return $tempfile;
}

/**
 * Creates an image and returns it as 'data-url' to be directly displayed on-screen.
 * @param array initials        An array containing two or more elements which are each a single-character string.
 * @param string shape          One of 'circle', 'square', or 'roundedsquare' at the time of writing.
 * @param string colour         Valid hex colour string with leading '#', e.g. '#f51' or '#3fd7dd'.
 * @param int size              Size in pixels of image width and height.
 * @param float fontsize        A multiplication factor for font size.
 * @param float fontalpha       Alpha-transparency.
 * @return string               'data-url' encoded image.
 */
function initialsprofilepics_create_and_dump_onscreen(
    array $initials = null,
    string $shape = null,
    string $colour = null,
    int $size = null,
    float $fontsize = null,
    float $fontalpha = null
) : string {
    $canvas = initialsprofilepics_generate_profile_pic($initials, $shape, $colour, $size, $fontsize, $fontalpha);
    return (string) $canvas->encode('data-url');
}

/**
 * Takes a Moodle user object and creates/returns an array of single letters.
 * @param object user       User object for the created/updated user.
 * @return array            Initials.
 * TODO: potentially limit this to two initials only.
 */
function initialsprofilepics_get_initials_from_user($user) : array {
    $names = explode(' ', fullname($user));

    $initials = [];
    foreach ($names as $name) {
        $encoding   = mb_detect_encoding($name);
        $initials[] = mb_strtoupper(mb_substr($name, 0, 1), $encoding);
    }
    return $initials;
}

/**
 * Generates a profile image from the user's initials.
 * @param array initials        An array containing two or more elements which are each a single-character string.
 * @param string shape          One of 'circle', 'square', or 'rounded_square' at the time of writing.
 * @param string colour         Valid hex colour string with leading '#', e.g. '#f51' or '#3fd7dd'.
 * @param int size              Size in pixels of image width and height
 * @param float fontsize        A multiplication factor for font size.
 * @param float fontalpha       Alpha-transparency.
 * @return Intervention image object
 */
function initialsprofilepics_generate_profile_pic(
    array $initials = null,
    string $shape = null,
    string $colour = null,
    int $size = null,
    float $fontsize = null,
    float $fontalpha = null
) {

    // Should have all decent params passed in, so do brief sanity checks only.
    $initials   = $initials     ?? initialsprofilepics_get_random_initials();
    $shape      = $shape        ?? INITIALSPROFILEPICS_SHAPE;
    $colour     = $colour       ?? initialsprofilepics_get_random_colour();
    $size       = $size         ?? INITIALSPROFILEPICS_SIZE;
    $fontsize   = $fontsize     ?? 1.4;
    $fontalpha  = $fontalpha    ?? 0.2;

    global $CFG;

    $image = new ImageManager(['driver' => INITIALSPROFILEPICS_DRIVER]);

    $hsize = (int) ($size / 2);
    if ($shape == INITIALSPROFILEPICS_SHAPES_CIRCLE) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->circle($size - 1, $size / 2, $size / 2, function ($draw) use ($colour) {
            $draw->background($colour);
            // TODO: Borders? Looks bad. :(
            // $draw->border(5, '#0000ff');
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_ROUNDEDSQUARE) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->circle($hsize, $hsize / 2, $hsize / 2, function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->circle($hsize, $size - ($hsize / 2) - 1, $hsize / 2, function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->circle($hsize, $size - ($hsize / 2), $size - ($hsize / 2), function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->circle($hsize, ($hsize / 2), $size - ($hsize / 2), function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $rsize = $hsize / 2;
        $points = [
            $rsize, 0,
            $size - $rsize, 0,
            $size, $rsize,
            $size, $size - $rsize,
            $size - $rsize, $size,
            $rsize, $size,
            0, $size - $rsize,
            0, $rsize
        ];
        $canvas->polygon($points, function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_SQUARE) {
        $canvas = $image->canvas($size, $size, $colour);
        // If we want a border here, I think we'll need to make the canvas transparent and draw a square.

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_UPSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([0, $hsize, $size, 0, $size, $hsize, 0, $size], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_DOWNSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([0, 0, $size, $hsize, $size, $size, 0, $hsize], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_LEFTSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([0, 0, $hsize, 0, $size, $size, $hsize, $size], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_RIGHTSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([$hsize, 0, $size, 0, $hsize, $size, 0, $size], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_HEXAGON_HORIZONTAL) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);

        // http://stackoverflow.com/questions/7198144/how-to-draw-a-n-sided-regular-polygon-in-cartesian-coordinates
        $theta  = 0; // Hexagon with flat sides at the top and bottom.
        $points = 6;
        for ($j = 0; $j <= $points - 1; $j++) {
            $x[$j] = (int) ($hsize * cos(2 * pi() * $j / $points + $theta) + $hsize);
            $y[$j] = (int) ($hsize * sin(2 * pi() * $j / $points + $theta) + $hsize);
        }
        $points = [
            $x[0], $y[0],
            $x[1], $y[1],
            $x[2], $y[2],
            $x[3], $y[3],
            $x[4], $y[4],
            $x[5], $y[5],
        ];
        $canvas->polygon($points, function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_HEXAGON_VERTICAL) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);

        $theta  = pi() / 2; // Hexagon with flat sides at the sides.
        $points = 6;
        for ($j = 0; $j <= $points - 1; $j++) {
            $x[$j] = (int) ($hsize * cos(2 * pi() * $j / $points + $theta) + $hsize);
            $y[$j] = (int) ($hsize * sin(2 * pi() * $j / $points + $theta) + $hsize);
        }
        $points = [
            $x[0], $y[0],
            $x[1], $y[1],
            $x[2], $y[2],
            $x[3], $y[3],
            $x[4], $y[4],
            $x[5], $y[5],
        ];
        $canvas->polygon($points, function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == INITIALSPROFILEPICS_SHAPES_STAR) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, INITIALSPROFILEPICS_BG_ALPHA]);

        // TODO: how to make the star point 'up' properly.
        $theta      = 2.2;
        $points     = [];
        $numpoints  = 5;

        // Generates points.
        for ($j = 0; $j <= $numpoints - 1; $j++) {
            $points[] = (int) ($hsize * cos(2 * pi() * $j / $numpoints + $theta) + $hsize);
            $points[] = (int) ($hsize * sin(2 * pi() * $j / $numpoints + $theta) + $hsize);
        }

        $canvas->polygon([$points[0], $points[1], $points[4], $points[5], $points[8], $points[9], $points[2], $points[3], $points[6], $points[7]], function ($draw) use ($colour) {
            $draw->background($colour);
        });

        // Fill in the centre.
        $canvas->fill($colour, $hsize, $hsize);

    } else {
        $canvas = $image->canvas($size, $size, $colour);
    }

    // Draw the text.
    for ($j = 0; $j <= count($initials) - 1; $j++) {
        // For now, hard-coded limit of 2. TODO: future development to use more.
        if ($j == 2) {
            break;
        }
        $canvas->text($initials[$j], ($size * .35) * ($j + 1), ($size * .5), function ($font) use ($size, $fontsize, $fontalpha) {
            $font->file(__DIR__ . '/fonts/' . INITIALSPROFILEPICS_FONT);
            $font->size($size * $fontsize);
            $font->color([255, 255, 255, $fontalpha]);
            $font->valign('middle');
            $font->align('center');
            $font->angle(-15);
        });
    }

    // Resizing.
    if ($size != $size) {
        $canvas->resize($size, $size);
    }

    return $canvas;
}

/**
 * Gets a random colour from the pool of colours.
 */
function initialsprofilepics_get_random_colour() {
    return INITIALSPROFILEPICS_COLOURS[rand(0, count(INITIALSPROFILEPICS_COLOURS) - 1)];
}

/**
 * Gets random initials.
 * @param int number            The number of initials required; defaults to 2.
 */
function initialsprofilepics_get_random_initials(int $number = null) {
    $number = $number ?? 2;
    $len    = strlen(INITIALSPROFILEPICS_INITIALSPOOL) - 1;
    $out    = [];
    for ($j = 1; $j <= $number; $j++) {
        $out[] = substr(INITIALSPROFILEPICS_INITIALSPOOL, rand(0, $len), 1);
    }
    return $out;
}

/**
 * Gets the working colour choice, depending on settings.
 * @param array initials         User's initials.
 */
function initialsprofilepics_get_working_colour(array $initials = null) {
    $initials = $initials ?? ['A', 'Z'];

    // Forced to a specific colour.
    if ($forcecolour = get_config('local_initialsprofilepics', 'forcecolour')) {
        $colour = $forcecolour;

    } else if (get_config('local_initialsprofilepics', 'randomcolour')) {
        // Random choice.
        $colour = initialsprofilepics_get_random_colour();

    } else {
        // One based on the first letter of your names.
        $colour = initialsprofilepics_get_colour_from_initials($initials);
    }

    return $colour;
}

/**
 * Gets the colour based on the user's initials.
 * @param array initials        User's initials.
 */
function initialsprofilepics_get_colour_from_initials(array $initials = null) {
    $initials = $initials ?? ['A', 'Z'];
    $colour = false;

    $charindex      = ord($initials[0]);
    $colourindex    = $charindex % count(INITIALSPROFILEPICS_COLOURS);
    if ($colourindex >= 0) {
        $colour         = INITIALSPROFILEPICS_COLOURS[$colourindex];
    }
    // If no colour selected for some reason, pick a random one.
    if (!$colour) {
        $colour     = initialsprofilepics_get_random_colour();
    }

    return $colour;
}
