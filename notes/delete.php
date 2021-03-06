<?php // $Id$

require_once('../config.php');
require_once('lib.php');

// retrieve parameters
$noteid = required_param('id', PARAM_INT);

// locate note information
if (!$note = note_load($noteid)) {
    print_error('invalidid');
}

// locate course information
if (!$course = $DB->get_record('course', array('id'=>$note->courseid))) {
    print_error('invalidcourseid');
}

// locate user information
    if (!$user = $DB->get_record('user', array('id'=>$note->userid))) {
        print_error('invaliduserid');
    }

// require login to access notes
require_login($course);

// locate context information
$context = get_context_instance(CONTEXT_COURSE, $course->id);

// check capability
if (!has_capability('moodle/notes:manage', $context)) {
    print_error('nopermissiontodelete', 'notes');
}

if (empty($CFG->enablenotes)) {
    print_error('notesdisabled', 'notes');
}

if (data_submitted() && confirm_sesskey()) {
//if data was submitted and is valid, then delete note
    $returnurl = $CFG->wwwroot . '/notes/index.php?course=' . $course->id . '&amp;user=' . $note->userid;
    if (note_delete($noteid)) {
        add_to_log($note->courseid, 'notes', 'delete', 'index.php?course='.$note->courseid.'&amp;user='.$note->userid . '#note-' . $note->id , 'delete note');
    } else {
        print_error('cannotdeletepost', 'notes', $returnurl);
    }
    redirect($returnurl);

} else {
// if data was not submitted yet, then show note data with a delete confirmation form
    $strnotes = get_string('notes', 'notes');
    $optionsyes = array('id'=>$noteid, 'sesskey'=>sesskey());
    $optionsno  = array('course'=>$course->id, 'user'=>$note->userid);

// output HTML
    if (has_capability('moodle/course:viewparticipants', $context) || has_capability('moodle/site:viewparticipants', get_context_instance(CONTEXT_SYSTEM))) {
        $nav[] = array('name' => get_string('participants'), 'link' => $CFG->wwwroot . '/user/index.php?id=' . $course->id, 'type' => 'misc');
    }
    $nav[] = array('name' => fullname($user), 'link' => $CFG->wwwroot . '/user/view.php?id=' . $user->id. '&amp;course=' . $course->id, 'type' => 'misc');
    $nav[] = array('name' => get_string('notes', 'notes'), 'link' => $CFG->wwwroot . '/notes/index.php?course=' . $course->id . '&amp;user=' . $user->id, 'type' => 'misc');
    $nav[] = array('name' => get_string('delete'), 'link' => '', 'type' => 'activity');
    print_header($course->shortname . ': ' . $strnotes, $course->fullname, build_navigation($nav));
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'notes'), new moodle_url('delete.php',$optionsyes),  new moodle_url('index.php',$optionsno));
    echo '<br />';
    note_print($note, NOTES_SHOW_BODY | NOTES_SHOW_HEAD);
    echo $OUTPUT->footer();
}
?>
