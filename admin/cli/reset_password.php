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
 * This script allows you to reset any local user password.
 *
 * @package    moodlecore
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    error_log("admin/cli/upgrade.php can not be called from web server!");
    exit;
}

require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once($CFG->libdir.'/clilib.php');      // cli only functions


// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false),
                                               array('h'=>'help'));

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $error = implode("\n  ", $unrecognized);
    cli_error("Unrecognized options:\n  $error \n. Please use --help option."); // TODO: localize
}

if ($options['help']) {

$help =
"Reset local user passwords, useful especially for admin acounts.

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help            Print out this help

Example: \$sudo -u wwwrun /usr/bin/php admin/cli/reset_password.php
"; //TODO: localize

    echo $help;
    die;
}
$prompt = "Password reset - enter username (manual authentication only)"; // TODO: localize
$username = cli_input($prompt);

if (!$user = $DB->get_record('user', array('auth'=>'manual', 'username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
    cli_error("Can not find user '$username'");
}

$prompt = "Enter new password"; // TODO: localize
$password = cli_input($prompt);

$errmsg = '';//prevent eclipse warning
if (!check_password_policy($password, $errmsg)) {
    cli_error($errmsg);
}

$hashedpassword = hash_internal_user_password($password);

$DB->set_field('user', 'password', $hashedpassword, array('id'=>$user->id));

echo "yay!\n";

exit(0); // 0 means success