# Initials Profile Pictures generator

![Example #1](./examples/ciabinitialsprofilepics_example.png)

![Example #2](./examples/ciabinitialsprofilepics_example2.png)

A local plugin for Moodle which automatically creates profile pictures for users based on their initials.

Written by Paul Vaughan (<paul.vaughan@coachinabox.biz>, [@MoodleVaughany](http://twitter.com/moodlevaughany)), &copy; 2017 [Coach in a Box](http://www.coachinabox.biz/).


## Introduction

For our Moodle installation, we wanted unique profile pictures for each user instead of the generic default 'silhouette' image, without the requirement of each user uploading their own. This plugin automatically and silently creates a profile picture for each user from their initials when their profile is created or saved. A few classes were found which did roughly what we wanted, and they were used as inspiration for this plugin, as was the way the Android Contacts app displays people without images.

This plugin uses the [Intervention Image library for PHP](http://image.intervention.io/) and was inspired by [this wrapper](https://github.com/yohang88/letter-avatar_) for it.


## Installation

### Compatibility

This plugin has so far only been tested with Moodle 3.2, but will probably work with all of the 3.x and most of the 2.x branches.

### Requirements

* PHP's GD image library.

This plugin uses the [Intervention Image library for PHP](http://image.intervention.io/) (it and it's dependencies are included with the plugin; there is no need to use Composer) and was inspired by [this wrapper](https://github.com/yohang88/letter-avatar_) for it, with a tip of the hat to the way default contact images are displayed in the Android Contacts app.


### Installing

Install as you would any local Moodle plugin, by copying or cloning the `ciabinitialsprofilepics` folder into your Moodle's `local/` folder. If you use `git clone`, please ensure the folder name is exactly as stated as it will not install or work otherwise.

Log in to your Moodle as an administrator and head to the Notifications page, where all being well, Moodle will discover the new plugin and install it.

At the time of writing, there is only an on/off switch, but I plan to make more of the plugin configurable over time.


## How It Works

This plugin hooks in to Moodle's events system, so what whenever a user is created or their profile updated, if a profile picture wasn't included, it makes one from the user's initials and saves it to the user's profile. If you upload a profile picture yourself, that will of course be used, however if a profile image is deleted, this plugin kicks in and generates a new one.


### Edit an existing user (or yourself)

1. Go to your profile.
2. Click Edit Profile.
3. If you already have a profile picture, check the Delete box underneath it.
4. Save your profile.

When the page reloads, you should see a new profile picture. The colour you get is dependant on your first initial, and is not random. It will not change if you recreate the image!

If you have rights to edit other people's profiles, following the above steps for other users will generate a new profile picture for that user.


### Creating new users

As new users are created, they will automatically get a profile picture created for them by default.


## Configuration

**Enabled:** Check to enable; uncheck to disable. Note that this will not remove profile pictures created by this plugin.

**Random Colour:** Colours are usually chosen based on a user's first initial, so that if an image is removed and recreated, that user will get the same background colour. Check this option to choose a colour from the built-in palette utterly at random instead.

**Force Colour**: Pick a colour or type in a valid CSS hexadecimal colour code to force all images to use this colour.

**Note:** Some parts of the plugin without configuration settings can still be changed if you are happy to hack the `locallib.php` file.  Most common settings are constants at the beginning of the file and have names like `CIABINITIALSPROFILEPICS_BG_ALPHA`.


## To Do

Ideas I have had for future developments. If you want a feature added, raise an issue or send a pull request. :)

* Shape selection. Some Moodle themes show pictures as circles but the plugin generates square images initially.
* Alpha-transparency options.
* Multiple font choices, probably from a folder in the plugin, possibly using [Google Fonts](https://github.com/google/fonts).
* Editing of the built-in colour palette.
* Option to find all users without a profile picture and make them one.
  * Potentially do the above e.g. daily, via a scheduled task.
* Option to remove all profile pictures and generate new ones.
* Image generation exemptions for certain user types (such as admins) or users (whitelisted by ID).
* Properly handle less (one) or more (up to four?) initials per image.


## Bugs

I've noticed that the images this plugin generates are around 18-20kb, give or take, but when the image has been processed and saved into Moodle, the image's size jumps up to around 150kb. This seems a little odd and unnecessary!


## Changelog

* 2017-03-13:     v1.0        Initial release.


## Licence

This plugin is free software: you can redistribute it and/or modify it under the terms of the [GNU General Public License](https://www.gnu.org/licenses/gpl.txt) as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see <http://www.gnu.org/licenses/>.
