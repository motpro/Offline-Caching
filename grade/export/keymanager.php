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


require_once '../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';

$id     = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);

require_capability('moodle/grade:export', $context);

print_grade_page_head($course->id, 'export', 'keymanager', get_string('keymanager', 'grades'));

$stredit         = get_string('edit');
$strdelete       = get_string('delete');

$data = array();
$keys = $DB->get_records_select('user_private_key', "script='grade/export' AND instance=? AND userid=?", array($course->id, $USER->id));
if ($keys) {
    foreach($keys as $key) {
        $line = array();
        $line[0] = format_string($key->value);
        $line[1] = $key->iprestriction;
        $line[2] = empty($key->validuntil) ? get_string('always') : userdate($key->validuntil);

        $buttons  = "<a title=\"$stredit\" href=\"key.php?id=$key->id\"><img".
                    " src=\"" . $OUTPUT->old_icon_url('t/edit') . "\" class=\"iconsmall\" alt=\"$stredit\" /></a> ";
        $buttons .= "<a title=\"$strdelete\" href=\"key.php?id=$key->id&amp;delete=1\"><img".
                    " src=\"" . $OUTPUT->old_icon_url('t/delete') . "\" class=\"iconsmall\" alt=\"$strdelete\" /></a> ";

        $line[3] = $buttons;
        $data[] = $line;
    }
}
$table = new html_table();
$table->head  = array(get_string('keyvalue', 'userkey'), get_string('keyiprestriction', 'userkey'), get_string('keyvaliduntil', 'userkey'), $stredit);
$table->size  = array('50%', '30%', '10%', '10%');
$table->align = array('left', 'left', 'left', 'center');
$table->width = '90%';
$table->data  = $data;
echo $OUTPUT->table($table);

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->button(html_form::make_button('key.php', array('courseid'=>$course->id), get_string('newuserkey', 'userkey')));
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
?>
