<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Preferences page for the stats report plugin.
 * Based on the grader report plugin.
 * @package gradebook
 */

require_once '../../../config.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';

$courseid = required_param('id', PARAM_INT);


/// Make sure they can even access this course
if(isset($DB) && !is_null($DB)) {
    $course = $DB->get_record('course', array('id' => $courseid));
} else {
    $course = get_record('course', 'id', $courseid);
}

if (!$course) {
    print_error('nocourseid');
}

require_login($course->id);

$context = get_context_instance(CONTEXT_COURSE, $course->id);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('gradereport/stats:view', $context);


require('preferences_form.php');
$mform = new stats_report_preferences_form('preferences.php', compact('course'));

// If data submitted, then process and store.
if ($data = $mform->get_data()) {
    foreach ($data as $preference => $value) {
        if (substr($preference, 0, 18) !== 'grade_report_stats') {
            continue;
        }

        if ($value == GRADE_REPORT_PREFERENCE_DEFAULT || strlen($value) == 0) {
            unset_user_preference($preference);
        } else {
            set_user_preference($preference, $value);
        }
    }
}

/// If cancelled go back to report
if ($mform->is_cancelled()){
    redirect($CFG->wwwroot . '/grade/report/stats/index.php?id='.$courseid);
}

print_grade_page_head($courseid, 'settings', 'stats', get_string('preferences', 'gradereport_stats'));

/// If USER has admin capability, print a link to the site config page for this report
/// TODO: Add admin config page for this report
if (has_capability('moodle/site:config', $systemcontext)) {
    echo '<div id="siteconfiglink"><a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/settings.php?section=gradereportstats">';
    echo get_string('changereportdefaults', 'grades');
    echo "</a></div>\n";
}

print_simple_box_start("center");

$mform->display();
print_simple_box_end();

print_footer($course);
?>