<?php // $Id$

    require_once("../../config.php");
    require_once("lib.php");

    $id       = required_param('id', PARAM_INT);          // course module ID
    $confirm  = optional_param('confirm', 0, PARAM_INT);  // commit the operation?
    $entry    = optional_param('entry', 0, PARAM_INT);    // entry id
    $prevmode = required_param('prevmode', PARAM_ALPHA);
    $hook     = optional_param('hook', '', PARAM_CLEAN);

    $strglossary   = get_string("modulename", "glossary");
    $strglossaries = get_string("modulenameplural", "glossary");
    $stredit       = get_string("edit");
    $entrydeleted  = get_string("entrydeleted","glossary");


    if (! $cm = get_coursemodule_from_id('glossary', $id)) {
        print_error("invalidcoursemodule");
    }

    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf');
    }

    if (! $entry = $DB->get_record("glossary_entries", array("id"=>$entry))) {
        print_error('invalidentry');
    }

    require_login($course->id, false, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $manageentries = has_capability('mod/glossary:manageentries', $context);

    if (! $glossary = $DB->get_record("glossary", array("id"=>$cm->instance))) {
        print_error('invalidid', 'glossary');
    }


    $strareyousuredelete = get_string("areyousuredelete","glossary");

    $navigation = build_navigation('', $cm);

    if (($entry->userid != $USER->id) and !$manageentries) { // guest id is never matched, no need for special check here
        print_error('nopermissiontodelentry');
    }
    $ineditperiod = ((time() - $entry->timecreated <  $CFG->maxeditingtime) || $glossary->editalways);
    if (!$ineditperiod and !$manageentries) {
        print_error('errdeltimeexpired', 'glossary');
    }

/// If data submitted, then process and store.

    if ($confirm and confirm_sesskey()) { // the operation was confirmed.
        // if it is an imported entry, just delete the relation

        if ($entry->sourceglossaryid) {
            if (!$newcm = get_coursemodule_from_instance('glossary', $entry->sourceglossaryid)) {
                print_error('invalidcoursemodule');
            }
            $newcontext = get_context_instance(CONTEXT_MODULE, $newcm->id);

            $entry->glossaryid       = $entry->sourceglossaryid;
            $entry->sourceglossaryid = 0;
            $DB->update_record('glossary_entries', $entry);

            // move attachments too
            $fs = get_file_storage();

            if ($oldfiles = $fs->get_area_files($context->id, 'glossary_attachment', $entry->id)) {
                foreach ($oldfiles as $oldfile) {
                    $file_record = new object();
                    $file_record->contextid = $newcontext->id;
                    $fs->create_file_from_storedfile($file_record, $oldfile);
                }
                $fs->delete_area_files($context->id, 'glossary_attachment', $entry->id);
                $entry->attachment = '1';
            } else {
                $entry->attachment = '0';
            }
            $DB->update_record('glossary_entries', $entry);

        } else {
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'glossary_attachment', $entry->id);
            $DB->delete_records("glossary_comments", array("entryid"=>$entry->id));
            $DB->delete_records("glossary_alias", array("entryid"=>$entry->id));
            $DB->delete_records("glossary_ratings", array("entryid"=>$entry->id));
            $DB->delete_records("glossary_entries", array("id"=>$entry->id));
        }

        add_to_log($course->id, "glossary", "delete entry", "view.php?id=$cm->id&amp;mode=$prevmode&amp;hook=$hook", $entry->id,$cm->id);
        redirect("view.php?id=$cm->id&amp;mode=$prevmode&amp;hook=$hook");

    } else {        // the operation has not been confirmed yet so ask the user to do so
        print_header_simple(format_string($glossary->name), "", $navigation,
                      "", "", true, update_module_button($cm->id, $course->id, $strglossary),
                      navmenu($course, $cm));
        $areyousure = "<b>".format_string($entry->concept)."</b><p>$strareyousuredelete</p>";
        $linkyes    = 'deleteentry.php';
        $linkno     = 'view.php';
        $optionsyes = array('id'=>$cm->id, 'entry'=>$entry->id, 'confirm'=>1, 'sesskey'=>sesskey(), 'prevmode'=>$prevmode, 'hook'=>$hook);
        $optionsno  = array('id'=>$cm->id, 'mode'=>$prevmode, 'hook'=>$hook);

        echo $OUTPUT->confirm($areyousure, new moodle_url($linkyes, $optionsyes), new moodle_url($linkno, $optionsno));

        echo $OUTPUT->footer();
    }
?>
