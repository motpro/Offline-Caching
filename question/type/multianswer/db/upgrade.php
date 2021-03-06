<?php  // $Id$

// This file keeps track of upgrades to 
// the multianswer qtype plugin
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_qtype_multianswer_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2008050800) {
        question_multianswer_fix_subquestion_parents_and_categories();
    /// multianswer savepoint reached
        upgrade_plugin_savepoint($result, 2008050800, 'qtype', 'multianswer');
    }

    return $result;
}

/**
 * Due to MDL-14750, subquestions of multianswer questions restored from backup will
 * have the wrong parent, and due to MDL-10899 subquestions of multianswer questions
 * that have been moved between categories will be in the wrong category, This code fixes these up.
 */
function question_multianswer_fix_subquestion_parents_and_categories() {
    global $CFG, $DB;

    $result = true;
    $rs = $DB->get_recordset_sql('SELECT q.id, q.category, qma.sequence
                                    FROM {question} q JOIN {question_multianswer} qma ON q.id = qma.question');
    if ($rs) {
        foreach ($rs as $q) {
            if (!empty($q->sequence)) {
                $result = $result && $DB->execute("UPDATE {question}
                                                      SET parent = ?, category = ?
                                                    WHERE id IN ($q->sequence) AND parent <> 0",
                                                  array($q->id, $q->category));
            }
        }
        $rs->close();
    } else {
        $result = false;
    }
    return $result;
}
?>
