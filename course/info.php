<?php // $Id$

/// Displays external information about a course

    require_once("../config.php");
    require_once("lib.php");

    $id   = optional_param('id', false, PARAM_INT); // Course id
    $name = optional_param('name', false, PARAM_RAW); // Course short name

    if (!$id and !$name) {
        print_error("unspecifycourseid");
    }

    if ($name) {
        if (!$course = $DB->get_record("course", array("shortname"=>$name))) {
            print_error("invalidshortname");
        }
    } else {
        if (!$course = $DB->get_record("course", array("id"=>$id))) {
            print_error("invalidcourseid");
        }
    }

    $site = get_site();

    if ($CFG->forcelogin) {
        require_login();
    }

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    if ((!course_parent_visible($course) || (! $course->visible)) && !has_capability('moodle/course:viewhiddencourses', $context)) {
        print_error('coursehidden', '', $CFG->wwwroot .'/');
    }

    print_header(get_string("summaryof", "", $course->fullname));

    echo $OUTPUT->heading(format_string($course->fullname) . '<br />(' . format_string($course->shortname) . ')');

    if ($course->guest || $course->password) {
        echo $OUTPUT->box_start('generalbox icons');
        if ($course->guest) {
            $strallowguests = get_string('allowguests');
            echo "<div><img alt=\"\" class=\"icon guest\" src=\"" . $OUTPUT->old_icon_url('i/guest') . "\" />&nbsp;$strallowguests</div>";
        }
        if ($course->password) {
            $strrequireskey = get_string('requireskey');
            echo "<div><img alt=\"\" class=\"icon key\" src=\"" . $OUTPUT->old_icon_url('i/key') . "\" />&nbsp;$strrequireskey</div>";
        }
        echo $OUTPUT->box_end();
    }


    echo $OUTPUT->box_start('generalbox info');

    echo format_text($course->summary, FORMAT_MOODLE, NULL, $course->id);

    if ($managerroles = get_config('', 'coursemanager')) {
        $coursemanagerroles = split(',', $managerroles);
        foreach ($coursemanagerroles as $roleid) {
            $role = $DB->get_record('role', array('id'=>$roleid));
            $canseehidden = has_capability('moodle/role:viewhiddenassigns', $context);
            $roleid = (int) $roleid;
            if ($users = get_role_users($roleid, $context, true, '', 'u.lastname ASC', $canseehidden)) {
                foreach ($users as $teacher) {
                    $fullname = fullname($teacher, has_capability('moodle/site:viewfullnames', $context));
                    $namesarray[] = format_string(role_get_name($role, $context)).': <a href="'.$CFG->wwwroot.'/user/view.php?id='.
                                    $teacher->id.'&amp;course='.SITEID.'">'.$fullname.'</a>';
                }
            }
        }

        if (!empty($namesarray)) {
            echo "<ul class=\"teachers\">\n<li>";
            echo implode('</li><li>', $namesarray);
            echo "</li></ul>";
        }
    }

    require_once("$CFG->dirroot/enrol/enrol.class.php");
    $enrol = enrolment_factory::factory($course->enrol);
    echo $enrol->get_access_icons($course);

    echo $OUTPUT->box_end();

    echo "<br />";

    echo $OUTPUT->close_window_button();

    echo $OUTPUT->footer();

?>
