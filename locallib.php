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
 * @package     local_ciabinitialsprofilepics
 * @copyright   2017 Coach in a Box <paul.vaughan@coachinabox.biz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/vendor/autoload.php');
use Intervention\Image\ImageManager;

// O or 1. TODO: config option. Probably.
define('CIABINITIALSPROFILEPICS_BG_ALPHA', 0);

// This can be 'gd' or 'imagick', but Moodle already requires gd, so use that.
define('CIABINITIALSPROFILEPICS_DRIVER', 'gd');

// Default size.
define('CIABINITIALSPROFILEPICS_SIZE', 200);

// Shapes options.
define('CIABINITIALSPROFILEPICS_SHAPES_CIRCLE', 'circle');
define('CIABINITIALSPROFILEPICS_SHAPES_SQUARE', 'square');
define('CIABINITIALSPROFILEPICS_SHAPES_ROUNDEDSQUARE', 'rounded_square');
define('CIABINITIALSPROFILEPICS_SHAPES_UPSLASH', 'upslash');
define('CIABINITIALSPROFILEPICS_SHAPES_DOWNSLASH', 'downslash');
define('CIABINITIALSPROFILEPICS_SHAPES_LEFTSLASH', 'leftslash');
define('CIABINITIALSPROFILEPICS_SHAPES_RIGHTSLASH', 'rightslash');
define('CIABINITIALSPROFILEPICS_SHAPES_HEXAGON_HORIZONTAL', 'hexagon_horizontal');
define('CIABINITIALSPROFILEPICS_SHAPES_HEXAGON_VERTICAL', 'hexagon_vertical');
define('CIABINITIALSPROFILEPICS_SHAPES_CIAB', 'ciab');
define('CIABINITIALSPROFILEPICS_SHAPES_STAR', 'star');
define('CIABINITIALSPROFILEPICS_SHAPES', [
    CIABINITIALSPROFILEPICS_SHAPES_CIRCLE,
    CIABINITIALSPROFILEPICS_SHAPES_SQUARE,
    CIABINITIALSPROFILEPICS_SHAPES_ROUNDEDSQUARE,
    CIABINITIALSPROFILEPICS_SHAPES_UPSLASH,
    CIABINITIALSPROFILEPICS_SHAPES_DOWNSLASH,
    CIABINITIALSPROFILEPICS_SHAPES_LEFTSLASH,
    CIABINITIALSPROFILEPICS_SHAPES_RIGHTSLASH,
    CIABINITIALSPROFILEPICS_SHAPES_HEXAGON_HORIZONTAL,
    CIABINITIALSPROFILEPICS_SHAPES_HEXAGON_VERTICAL,
    CIABINITIALSPROFILEPICS_SHAPES_CIAB,
    CIABINITIALSPROFILEPICS_SHAPES_STAR,
]);

// If it's not a circle, it's square, or a rounded square.
define('CIABINITIALSPROFILEPICS_SHAPE', CIABINITIALSPROFILEPICS_SHAPES_SQUARE);

// Font to use.
// define('CIABINITIALSPROFILEPICS_FONT', 'opensans-regular.ttf');
define('CIABINITIALSPROFILEPICS_FONT', 'calibri.ttf');

// Colours.
define('CIABINITIALSPROFILEPICS_COLOURS', [
    // Default generic colours.
    "#1abc9c", "#2ecc71", "#3498db", "#9b59b6", "#34495e", "#16a085", "#27ae60", "#2980b9", "#8e44ad", "#2c3e50",
    "#f1c40f", "#e67e22", "#e74c3c", "#dce0e1", "#95a5a6", "#f39c12", "#d35400", "#c0392b", "#bdc3c7", "#7f8c8d",

]);

// Colours used to darken and lighten other colours by layering on top.
define('CIABINITIALSPROFILEPICS_COLOUR_DARKEN', [0, 0, 0, .3]);
define('CIABINITIALSPROFILEPICS_COLOUR_LIGHTEN', [255, 255, 255, .2]);

// Pool of letters (and numbers, symbols as appropriate) for testing and if no initials supplied.
// define('CIABINITIALSPROFILEPICS_INITIALSPOOL', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!?#@');
define('CIABINITIALSPROFILEPICS_INITIALSPOOL', 'ABCDE12345!?#@¥£€$¢₡₢₣₤₥₦₧₨₩₪₫₭₮₯₹·');

// Font sizes.
define('CIABINITIALSPROFILEPICS_FONTSIZE', [
    '1.4'   => get_string('extralarge', 'local_ciabinitialsprofilepics'),
    '1.2'   => get_string('large', 'local_ciabinitialsprofilepics'),
    '1.0'   => get_string('medium', 'local_ciabinitialsprofilepics'),
    '0.8'   => get_string('small', 'local_ciabinitialsprofilepics'),
]);

// Font alpha-transparency.
define('CIABINITIALSPROFILEPICS_FONTALPHA', [
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
function create_and_save_to_profile($user) : bool {

    global $CFG, $DB, $usernew;

    $initials   = ciabinitialsprofilepics_get_initials_from_user($user);
    // TODO: config option.
    $shape      = get_config('local_ciabinitialsprofilepics', 'shape');
    $colour     = ciabinitialsprofilepics_get_working_colour($initials);
    $size       = 500;
    $fontsize   = get_config('local_ciabinitialsprofilepics', 'fontsize');
    $fontalpha  = get_config('local_ciabinitialsprofilepics', 'fontalpha');

    // Initials, shape, colour, size, fontsize, fontalpha.
    $canvas = ciabinitialsprofilepics_generate_profile_pic($initials, $shape, $colour, $size, $fontsize, $fontalpha);

    $dir = make_request_directory();
    $tempfile = $dir . $user->id . '.png';
    $canvas->save($tempfile);

    $newpicture = (int) process_new_icon(context_user::instance($user->id, MUST_EXIST), 'user', 'icon', 0, $tempfile);
    $DB->set_field('user', 'picture', $newpicture, ['id' => $user->id]);

    $usernew->picture = $newpicture;

    return true;
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
function create_and_dump_onscreen(
    array $initials = null,
    string $shape = null,
    string $colour = null,
    int $size = null,
    float $fontsize = null,
    float $fontalpha = null
) : string {
    $canvas = ciabinitialsprofilepics_generate_profile_pic($initials, $shape, $colour, $size, $fontsize, $fontalpha);
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
function ciabinitialsprofilepics_generate_profile_pic(
    array $initials = null,
    string $shape = null,
    string $colour = null,
    int $size = null,
    float $fontsize = null,
    float $fontalpha = null
) {

    // Should have all decent params passed in, so do brief sanity checks only.
    $initials   = $initials ?? ciabinitialsprofilepics_get_random_initials();
    $shape      = $shape ?? CIABINITIALSPROFILEPICS_SHAPE;
    $colour     = $colour ?? ciabinitialsprofilepics_get_random_colour();
    $size       = $size ?? CIABINITIALSPROFILEPICS_SIZE;
    $fontsize   = $fontsize ?? 1.4;
    $fontalpha  = $fontalpha ?? 0.2;

    global $CFG;

    $image = new ImageManager(['driver' => CIABINITIALSPROFILEPICS_DRIVER]);

    $hsize = (int) ($size / 2);
    if ($shape == CIABINITIALSPROFILEPICS_SHAPES_CIRCLE) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->circle($size - 1, $size / 2, $size / 2, function ($draw) use ($colour) {
            $draw->background($colour);
            // TODO: Borders? Looks bad. :(
            // $draw->border(5, '#0000ff');
        });

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_ROUNDEDSQUARE) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
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

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_SQUARE) {
        $canvas = $image->canvas($size, $size, $colour);
        // If we want a border here, I think we'll need to make the canvas transparent and draw a square.

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_UPSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([0, $hsize, $size, 0, $size, $hsize, 0, $size], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_DOWNSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([0, 0, $size, $hsize, $size, $size, 0, $hsize], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_LEFTSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([0, 0, $hsize, 0, $size, $size, $hsize, $size], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_RIGHTSLASH) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);
        $canvas->polygon([$hsize, 0, $size, 0, $hsize, $size, 0, $size], function ($draw) use ($colour) {
            $draw->background($colour);
        });

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_HEXAGON_HORIZONTAL) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);

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

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_HEXAGON_VERTICAL) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);

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

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_CIAB) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);

        $theta      = pi() / 2;
        $points1    = $points2 = $points3 = [];
        $numpoints  = 6;

        // Generates points for the outer (largest), middle, and inner (smallest) hexagon.
        for ($j = 0; $j <= $numpoints - 1; $j++) {
            $points1[] = (int) ($hsize * cos(2 * pi() * $j / $numpoints + $theta) + $hsize);
            $points1[] = (int) ($hsize * sin(2 * pi() * $j / $numpoints + $theta) + $hsize);
            $points2[] = (int) (($hsize / 2) * cos(2 * pi() * $j / $numpoints + $theta) + $hsize);
            $points2[] = (int) (($hsize / 2) * sin(2 * pi() * $j / $numpoints + $theta) + $hsize);
            $points3[] = (int) (($hsize / 3) * cos(2 * pi() * $j / $numpoints + $theta) + $hsize);
            $points3[] = (int) (($hsize / 3) * sin(2 * pi() * $j / $numpoints + $theta) + $hsize);
        }

        // Shadow bit (make a line, blur it).
        // https://github.com/Intervention/image/issues/240
        $height = 5;
        $canvas->polygon([
            $points1[10], $points1[11], $points1[0], $points1[1], $points1[2], $points1[3],
            $points1[2], $points1[3] - $height, $points1[0], $points1[1] - $height, $points1[10], $points1[11] - $height
        ], function ($draw) use ($colour) {
            $draw->background('#000');
        });
        $canvas->blur(50);

        $canvas->polygon([
            $points1[0], $points1[1], $points1[2], $points1[3], $points1[4], $points1[5],
            $points2[4], $points2[5], $points2[2], $points2[3], $points2[0], $points2[1]
        ], function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->polygon([
            $points1[4], $points1[5], $points1[6], $points1[7], $points1[8], $points1[9],
            $points2[8], $points2[9], $points2[6], $points2[7], $points2[4], $points2[5]
        ], function ($draw) use ($colour) {
            $draw->background($colour);
        });
        $canvas->polygon([
            $points1[8], $hsize, $points1[10], $points1[11], $points1[0], $points1[1],
            $points2[0], $points2[1]
        ], function ($draw) use ($colour) {
            $draw->background($colour);
        });

        // Draw the small hexagon.
        $canvas->polygon($points3, function ($draw) use ($colour) {
            $draw->background($colour);
        });

        // Adding the alpha-level stuff.
        $canvas->polygon([
            $points1[0], $points1[1], $points1[2], $points1[3], $points1[4], $points1[5],
            $points2[4], $points2[5], $points2[2], $points2[3], $points2[0], $points2[1]
        ], function ($draw) {
            $draw->background(CIABINITIALSPROFILEPICS_COLOUR_DARKEN);
        });
        $canvas->polygon([
            $points1[4], $points1[5], $points1[6], $points1[7], $points1[8], $points1[9],
            $points2[8], $points2[9], $points2[6], $points2[7], $points2[4], $points2[5]
        ], function ($draw) {
            $draw->background(CIABINITIALSPROFILEPICS_COLOUR_LIGHTEN);
        });
        $canvas->polygon([
            $points3[0], $points3[1], $points3[2], $points3[3], $points3[4], $points3[5],
            $hsize, $hsize
        ], function ($draw) {
            $draw->background(CIABINITIALSPROFILEPICS_COLOUR_DARKEN);
        });
        $canvas->polygon([
            $points3[4], $points3[5], $points3[6], $points3[7], $points3[8], $points3[9],
            $hsize, $hsize
        ], function ($draw) {
            $draw->background(CIABINITIALSPROFILEPICS_COLOUR_LIGHTEN);
        });

    } else if ($shape == CIABINITIALSPROFILEPICS_SHAPES_STAR) {
        $canvas = $image->canvas($size, $size, [255, 255, 255, CIABINITIALSPROFILEPICS_BG_ALPHA]);

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
            $font->file(__DIR__ . '/fonts/' . CIABINITIALSPROFILEPICS_FONT);
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
function ciabinitialsprofilepics_get_random_colour() {
    return CIABINITIALSPROFILEPICS_COLOURS[rand(0, count(CIABINITIALSPROFILEPICS_COLOURS) - 1)];
}

/**
 * Gets random initials.
 * @param int number            The number of initials required; defaults to 2.
 */
function ciabinitialsprofilepics_get_random_initials(int $number = null) {
    $number = $number ?? 2;
    $len    = strlen(CIABINITIALSPROFILEPICS_INITIALSPOOL) - 1;
    $out    = [];
    for ($j = 1; $j <= $number; $j++) {
        $out[] = substr(CIABINITIALSPROFILEPICS_INITIALSPOOL, rand(0, $len), 1);
    }
    return $out;
}

/**
 * Gets the working colour choice, depending on settings.
 * @param array initials         User's initials.
 */
function ciabinitialsprofilepics_get_working_colour(array $initials = null) {
    $initials = $initials ?? ['A', 'Z'];

    // Forced to a specific colour.
    if ($forcecolour = get_config('local_ciabinitialsprofilepics', 'forcecolour')) {
        $colour = $forcecolour;

    } else if (get_config('local_ciabinitialsprofilepics', 'randomcolour')) {
        // Random choice.
        $colour = ciabinitialsprofilepics_get_random_colour();

    } else {
        // One based on the first letter of your names.
        $colour = ciabinitialsprofilepics_get_colour_from_initials($initials);
    }

    return $colour;
}

/**
 * Gets the colour based on the user's initials.
 * @param array initials        User's initials.
 */
function ciabinitialsprofilepics_get_colour_from_initials(array $initials = null) {
    $initials = $initials ?? ['A', 'Z'];

    $charindex      = ord($initials[0]);
    // The '-5' starts capitals 'A' at 0, 'B' at 1 and so on. Just 'cos! (Ignores 'a', 'b' etc.)
    $colourindex    = ($charindex - 5) % count(CIABINITIALSPROFILEPICS_COLOURS);
    $colour         = CIABINITIALSPROFILEPICS_COLOURS[$colourindex];
    // If no colour selected for some reason, pick a random one.
    if (!$colour) {
        $colour     = ciabinitialsprofilepics_get_random_colour();
    }

    return $colour;
}
