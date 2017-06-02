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
 * Tests.
 * https://phpunit.de/manual/current/en/appendixes.assertions.html
 * $ php admin/tool/phpunit/cli/init.php
 * $ vendor/bin/phpunit -v local_ciabinitialsprofilepics_testcase local/ciabinitialsprofilepics/tests/locallib_test.php
 *
 * @package     local_ciabinitialsprofilepics
 * @copyright   2017 Coach in a Box <paul.vaughan@coachinabox.biz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_ciabinitialsprofilepics_testcase extends advanced_testcase {

    public function test_gd_library_loaded() {
        $this->assertTrue(extension_loaded('gd'));
    }

    public function test_gd_library_functions() {
        $gdfunctions = [
            'imagealphablending',
            'imagecolorallocate',
            'imagecolorallocatealpha',
            'imagecreatetruecolor',
            'imagedestroy',
            'imagefill',
            'imagefilledellipse',
            'imagefilledpolygon',
            'imagefilledrectangle',
            'imagepng',
            'imagesavealpha',
            'imagettftext',
        ];
        foreach ($gdfunctions as $gdf) {
            $this->assertTrue(function_exists($gdf));
        }

        // Checks for PHP 7.2 and above.
        if (PHP_VERSION_ID >= 70200) {
            $php72gdfunctions = [
                'imageantialias'
            ];
            foreach ($php72gdfunctions as $gdf) {
                $this->assertTrue(function_exists($gdf));
            }
        } else {
            $this->markTestSkipped("The '{$gdf}' GD function is not available in this PHP version.");
        }
    }

    public function test_plugin_enabled() {
        $this->resetAfterTest(true);
        set_config('enabled', false, 'local_ciabinitialsprofilepics');
        $this->assertEquals(0, get_config('local_ciabinitialsprofilepics', 'enabled'));
        set_config('enabled', true, 'local_ciabinitialsprofilepics');
        $this->assertEquals(1, get_config('local_ciabinitialsprofilepics', 'enabled'));
    }

    public function test_basic_picture_checks() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['picture' => 12345]);

        $usercount = $DB->count_records('user');
        $this->assertEquals(4, $usercount);

        $this->assertEquals(0, $user1->picture);
        $this->assertEquals(12345, $user2->picture);
    }

    public function test_get_english_initials() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Cooper']);
        $res = ciabinitialsprofilepics_get_initials_from_user($user);
        $this->assertEquals(['A', 'C'], $res);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'bob', 'lastname' => 'holness']);
        $res = ciabinitialsprofilepics_get_initials_from_user($user);
        $this->assertEquals(['B', 'H'], $res);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Peter', 'middlename' => 'F', 'lastname' => 'Hamilton']);
        $res = ciabinitialsprofilepics_get_initials_from_user($user);
        $this->assertEquals(['P', 'H'], $res);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Peter F.', 'lastname' => 'Hamilton']);
        $res = ciabinitialsprofilepics_get_initials_from_user($user);
        $this->assertEquals(['P', 'F', 'H'], $res);

    }

    /**
     * Uses PHPUnit's testing data which contains around 60 English and non-English names.
     */
    public function test_get_nonenglish_initials() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');
        $this->resetAfterTest(true);

        $users = $expected = [];
        $generator = phpunit_util::get_data_generator();
        foreach ($generator->firstnames as $key => $value) {
            $users[$key] = $this->getDataGenerator()->create_user(['firstname' => $generator->firstnames[$key], 'lastname' => $generator->lastnames[$key]]);

            $encoding       = mb_detect_encoding($generator->firstnames[$key]);
            $expected[$key] = [
                mb_strtoupper(mb_substr($generator->firstnames[$key], 0, 1), $encoding),
                mb_strtoupper(mb_substr($generator->lastnames[$key], 0, 1), $encoding)
            ];
        }

        foreach ($users as $key => $user) {
            $res = ciabinitialsprofilepics_get_initials_from_user($user);
            $this->assertEquals($expected[$key], $res);
        }
    }

    public function test_random_colour_from_pool() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');

        $colour = ciabinitialsprofilepics_get_random_colour();
        $this->assertContains($colour, CIABINITIALSPROFILEPICS_COLOURS);
    }

    public function test_working_colour() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');

        $colour = ciabinitialsprofilepics_get_working_colour();
        $this->assertRegExp('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $colour);
    }

    public function test_random_initials() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');

        $initials = ciabinitialsprofilepics_get_random_initials(50);
        foreach ($initials as $initial) {
            $this->assertContains($initial, CIABINITIALSPROFILEPICS_INITIALSPOOL);
        }
    }

    public function test_colour_from_initials() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');

        for ($j = 1; $j <= 50; $j++) {
            $initials = ciabinitialsprofilepics_get_random_initials();
            $this->assertRegExp('/^#(?:[0-9a-fA-F]{3}){1,2}$/', ciabinitialsprofilepics_get_colour_from_initials($initials));
        }
    }

    public function test_image_generation_data() {
        global $CFG;
        require_once($CFG->dirroot . '/local/ciabinitialsprofilepics/locallib.php');
        $this->resetAfterTest(true);

        $imagedata = "data:image/png;base64,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";

        $user           = $this->getDataGenerator()->create_user(['firstname' => 'Antelope', 'lastname' => 'Zebra']);

        $initials       = ciabinitialsprofilepics_get_initials_from_user($user);
        $shape          = CIABINITIALSPROFILEPICS_SHAPE;
        $colour         = ciabinitialsprofilepics_get_working_colour($initials);
        $size           = 200;
        $fontsize       = 1.4;
        $fontalpha      = 0.2;

        $newimagedata   = create_and_dump_onscreen($initials, $shape, $colour, $size, $fontsize, $fontalpha);

        $this->assertContains('data:image/png;base64,', $newimagedata);
        $this->assertGreaterThan(7000, strlen($newimagedata));
        $this->assertEquals($imagedata, $newimagedata);

        $size           = 250;
        $newimagedata   = create_and_dump_onscreen($initials, $shape, $colour, $size, $fontsize, $fontalpha);
        $this->assertNotEquals($imagedata, $newimagedata);

        $size           = 200;
        $shape          = 'not_a_shape';
        $newimagedata   = create_and_dump_onscreen($initials, $shape, $colour, $size, $fontsize, $fontalpha);
        $this->assertEquals($imagedata, $newimagedata);

        // How to test no-settings output?
        // $newimagedata   = create_and_dump_onscreen(null, null, null, null, null, null);
        // $this->assertEquals($imagedata, $newimagedata);

    }

}
