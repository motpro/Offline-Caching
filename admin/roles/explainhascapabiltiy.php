<?php
require(dirname(__FILE__) . '/../../config.php');

// Get parameters.
$userid = required_param('user', PARAM_INTEGER); // We use 0 here to mean not-logged-in.
$contextid = required_param('contextid', PARAM_INTEGER);
$capability = required_param('capability', PARAM_CAPABILITY);

// Get the context and its parents.
$context = get_context_instance_by_id($contextid);
if (!$context) {
    print_error('unknowncontext');
}
$contextids = get_parent_contexts($context);
array_unshift($contextids, $context->id);
$contexts = array();
foreach ($contextids as $contextid) {
    $contexts[$contextid] = get_context_instance_by_id($contextid);
    $contexts[$contextid]->name = print_context_name($contexts[$contextid], true, true);
}

// Validate the user id.
if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid));
    if (!$user) {
        print_error('nosuchuser');
    }
} else {
    $frontpagecontext = get_context_instance(CONTEXT_COURSE, SITEID);
    if (!empty($CFG->forcelogin) ||
            ($context->contextlevel >= CONTEXT_COURSE && !in_array($frontpagecontext->id, $contextids))) {
        print_error('cannotgetherewithoutloggingin', 'role');
    }
}

// Check access permissions.
require_login();
if (!has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride',
        'moodle/role:override', 'moodle/role:assign'), $context)) {
    print_error('nopermissions', '', get_string('explainpermissions'));
}

// This duplicates code in load_all_capabilities and has_capability.
$systempath = '/' . SYSCONTEXTID;
if ($userid == 0) {
    if (!empty($CFG->notloggedinroleid)) {
        $accessdata = get_role_access($CFG->notloggedinroleid);
        $accessdata['ra'][$systempath] = array($CFG->notloggedinroleid);
    } else {
        $accessdata = array();
        $accessdata['ra'] = array();
        $accessdata['rdef'] = array();
        $accessdata['loaded'] = array();
    }
} else if (isguestuser($user)) {
    $guestrole = get_guest_role();
    $accessdata = get_role_access($guestrole->id);
    $accessdata['ra'][$systempath] = array($guestrole->id);
} else {
    load_user_accessdata($userid);
    $accessdata = $ACCESS[$userid];
}
if ($context->contextlevel > CONTEXT_COURSE && !path_inaccessdata($context->path, $accessdata)) {
    load_subcontext($userid, $context, $accessdata);
}

// Load the roles we need.
$roleids = array();
foreach ($accessdata['ra'] as $roleassignments) {
    $roleids = array_merge($roleassignments, $roleids);
}
$roles = $DB->get_records_list('role', 'id', $roleids);
$rolenames = array();
foreach ($roles as $role) {
    $rolenames[$role->id] = $role->name;
}
$rolenames = role_fix_names($rolenames, $context);

// Make a fake role to simplify rendering the table below.
$rolenames[0] = get_string('none');

// Prepare some arrays of strings.
$cssclasses = array(
    CAP_INHERIT => 'inherit',
    CAP_ALLOW => 'allow',
    CAP_PREVENT => 'prevent',
    CAP_PROHIBIT => 'prohibit',
    '' => ''
);
$strperm = array(
    CAP_INHERIT => get_string('inherit', 'role'),
    CAP_ALLOW => get_string('allow', 'role'),
    CAP_PREVENT => get_string('prevent', 'role'),
    CAP_PROHIBIT => get_string('prohibit', 'role'),
    '' => ''
);

// Start the output.
print_header(get_string('explainpermissions', 'role'));
print_heading(get_string('explainpermissions', 'role'));

// Print a summary of what we are doing.
$a = new stdClass;
if ($userid) {
    $a->fullname = fullname($user);
} else {
    $a->fullname = get_string('nobody');
}
$a->context = reset($contexts)->name;
$a->capability = $capability;
echo '<p>' . get_string('explainpermissionsdetails', 'role', $a) . '</p>';

// Print the table header rows.
echo '<table class="generaltable explainpermissions"><thead>';
echo '<tr><th scope="col" rowspan="2" class="header">' . get_string('assignmentcontext', 'role') .
        '</th><th scope="row" class="header overridecontextheader">' . get_string('overridecontext', 'role') . '</th>';
foreach ($contexts as $con) {
    echo '<th scope="col" rowspan="2" class="header overridecontext">' . $con->id . '</th>';
}
echo '</tr>';
echo '<tr><th scope="col" class="header rolename">' . get_string('role') . '</th></tr>';
echo '</thead><tbody>';

// Now print the bulk of the table.
foreach ($contexts as $con) {
    if (!empty($accessdata['ra'][$con->path])) {
        $ras = $accessdata['ra'][$con->path];
    } else {
        $ras = array(0);
    }
    $firstcell = '<th class="cell assignmentcontext" rowspan="' . count($ras) . '">' . $con->id . ' ' . $con->name . '</th>';
    $rowclass = ' class="newcontext"';
    foreach ($ras as $roleid) {
        $extraclass = '';
        if (!$roleid) {
            $extraclass = ' noroles';
        }
        echo '<tr' . $rowclass . '>' . $firstcell . '<th class="cell rolename' . $extraclass . '" scope="row">' . $rolenames[$roleid] . '</th>';
        foreach ($contexts as $ocon) {
            if ($roleid == 0) {
                $perm = '';
            } else {
                if (isset($accessdata['rdef'][$ocon->path . ':' . $roleid][$capability])) {
                    $perm = $accessdata['rdef'][$ocon->path . ':' . $roleid][$capability];
                } else {
                    $perm = CAP_INHERIT;
                }
            }
            if ($perm === CAP_INHERIT && $ocon->id == SYSCONTEXTID) {
                $permission = get_string('notset', 'role');
            } else {
                $permission = $strperm[$perm];
            }
            echo '<td class="cell ' . $cssclasses[$perm] . '">' . $permission . '</td>';
        }
        echo '</tr>';
        $firstcell = '';
        $rowclass = '';
    }
}
echo '</tbody></table>';

// Finish the page.
echo get_string('explainpermissionsinfo', 'role');
if ($userid && $capability != 'moodle/site:doanything' &&
        has_capability('moodle/site:doanything', $context, $userid) &&
        !has_capability($capability, $context, $userid, false)) {
    echo '<p>' . get_string('explainpermissionsdoanything', 'role', $capability) . '</p>';
}
close_window_button();
print_footer('empty');
?>