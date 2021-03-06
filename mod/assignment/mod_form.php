<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_assignment_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        // this hack is needed for different settings of each subtype
        if (!empty($this->_instance)) {
            if($ass = $DB->get_record('assignment', array('id'=>$this->_instance))) {
                $type = $ass->assignmenttype;
            } else {
                print_error('invalidassignment', 'assignment');
            }
        } else {
            $type = required_param('type', PARAM_ALPHA);
        }
        $mform->addElement('hidden', 'assignmenttype', $type);
        $mform->setDefault('assignmenttype', $type);
        $mform->addElement('hidden', 'type', $type);
        $mform->setDefault('type', $type);

        require($CFG->dirroot.'/mod/assignment/type/'.$type.'/assignment.class.php');
        $assignmentclass = 'assignment_'.$type;
        $assignmentinstance = new $assignmentclass();

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

//        $mform->addElement('static', 'statictype', get_string('assignmenttype', 'assignment'), get_string('type'.$type,'assignment'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'assignment'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('description', 'assignment'));

        $mform->addElement('modgrade', 'grade', get_string('grade'));
        $mform->setDefault('grade', 100);

        $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'assignment'), array('optional'=>true));
        $mform->setDefault('timeavailable', time());
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'assignment'), array('optional'=>true));
        $mform->setDefault('timedue', time()+7*24*3600);

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'preventlate', get_string('preventlate', 'assignment'), $ynoptions);
        $mform->setDefault('preventlate', 0);

        $mform->addElement('header', 'typedesc', get_string('type'.$type,'assignment'));
        $assignmentinstance->setup_elements($mform);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

}

