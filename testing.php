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
 * Show a whole bunch of random profile pictures to test how they are generated.
 *
 * @package    local_ciabinitialsprofilepics
 * @copyright  2017 Coach in a Box <paul.vaughan@coachinabox.biz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');
require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/vendor/autoload.php');

admin_externalpage_setup('local_ciabinitialsprofilepics_testing');

echo $OUTPUT->header();

$l = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!?#@';
$ll = strlen($l) - 1;

$shape = ['square', 'circle', 'roundedsquare'];

echo $OUTPUT->heading('Testing', 2);

echo $OUTPUT->heading('Testing for Anything', 4);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['A', 'B'], 'square', 200)]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['C', 'D'], 'circle', 200)]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['E', 'F'], 'roundedsquare', 200)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['A', 'B'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['B', 'C'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['C', 'D'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['D', 'E'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['E', 'F'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['F', 'G'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['G', 'H'], 'circle', 100)]);
// echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['H', 'I'], 'circle', 100)]);

echo $OUTPUT->heading('Generic Lettters and Common Symbols', 4);
for ($j = 0; $j <= $ll; $j++) {
    echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen([$l[$j], $l[$j]], 'circle', 100)]);
}
echo html_writer::empty_tag('br');
for ($j = 0; $j <= $ll; $j++) {
    echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen([$l[$j], $l[$j]], 'square', 100)]);
}
echo html_writer::empty_tag('br');
for ($j = 0; $j <= $ll; $j++) {
    echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen([$l[$j], $l[$j]], 'roundedsquare', 100)]);
}

echo html_writer::empty_tag('hr');
echo $OUTPUT->heading('Random Letters, Numbers and Symbols', 4);
for ($j = 1; $j <= 40; $j++) {
    echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen([$l[rand(0, $ll)], $l[rand(0, $ll)]], $shape[rand(0, count($shape) - 1)], 100)]);
}

echo html_writer::empty_tag('hr');
echo $OUTPUT->heading('Different Sizes', 4);
foreach ([25, 33, 50, 75, 100, 150, 250, 333, 500] as $size) {
    $s = (string) $size;
    echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen([$s[0], $s[1]], 'circle', $size)]);
}

echo html_writer::empty_tag('hr');
echo $OUTPUT->heading('One Each of All ' . count(CIABINITIALSPROFILEPICS_COLOURS) . ' Colours', 4);
foreach (CIABINITIALSPROFILEPICS_COLOURS as $colour) {
    echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['A', 'B'], 'circle', 100, $colour)]);
}

// echo html_writer::empty_tag('hr');
// echo $OUTPUT->heading('Three Random Letters, Numbers and Symbols', 4);
// for ($j = 1; $j <= 10; $j++) {
//     echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen([$l[rand(0, $ll)], $l[rand(0, $ll)], $l[rand(0, $ll)]], $shape[rand(0, 1)], 100)]);
// }

echo html_writer::empty_tag('hr');
echo $OUTPUT->heading('Coach in a Box!', 4);

echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['C', 'o'], 'square', 150, '#007aa1')]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['a', 'c'], 'square', 150, '#007aa1')]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['h', ' '], 'square', 150, '#007aa1')]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['i', 'n'], 'circle', 150, '#00aee0')]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['a', ' '], 'circle', 150, '#00b3e6')]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['B', 'o'], 'square', 150, '#007aa1')]);
echo html_writer::empty_tag('img', ['src' => create_and_dump_onscreen(['x', '!'], 'square', 150, '#007aa1')]);


echo $OUTPUT->footer();
