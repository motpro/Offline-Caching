<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
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

if (isset($_SERVER['REMOTE_ADDR'])) { // this script is accessed only via the web!
    die;
    //echo "<xmp>";
}

require_once('../../../../../config.php');

if (!debugging('', DEBUG_DEVELOPER)) {
    die('Only for developers!!!!!');
}

// language correspondance: codes used in TinyMCE that matches a different
// code in Moodle. Note that languages without an existing folder are
// ignored if they are not in this array.
$langconversion = array(
    'no' => 'nb',
    'ko' => false,     // ignore Korean translation for now - does not parse
    'sr_lt' => false,  //'sr_lt' => 'sr' ignore the Serbian translation
    'zh_tw' => 'zh',
);

$targetlangdir = "$CFG->dirroot/../lang"; // change if needed
$tempdir       = "$CFG->dirroot/lib/editor/tinymce/extra/tools/temp";
$enfile        = "$CFG->dirroot/lang/en_utf8/editor_tinymce.php";


/// first update English lang pack
if (file_exists("$tempdir/en.xml")) {
    $old_strings = editor_tinymce_get_all_strings($enfile);


    //remove all strings from upstream - ignore our modifications for now
    // TODO: add support for merging of our tweaks
    $parsed = editor_tinymce_parse_xml_lang("$tempdir/en.xml");
    foreach ($parsed as $key=>$value) {
        if (array_key_exists($key, $old_strings)) {
            unset($old_strings[$key]);
        }
    }

    if (!$handle = fopen($enfile, 'w')) {
         echo "Cannot write to $filename !!";
         exit;
    }

    fwrite($handle, "<?php\n\n");

    foreach ($old_strings as $key=>$value) {
        fwrite($handle, editor_tinymce_encode_stringline($key, $value));
    }

    fwrite($handle, "\n\n\n\n// Automatically created strings from original TinyMCE lang files, please do not update yet\n\n");

    foreach ($parsed as $key=>$value) {
        fwrite($handle, editor_tinymce_encode_stringline($key, $value));
    }

    fclose($handle);
}

//now update all other langs
$en_strings = editor_tinymce_get_all_strings($enfile);

if (!file_exists($targetlangdir)) {
    echo "Can not find target lang dir: $targetlangdir !!";
}

$langs = new DirectoryIterator($targetlangdir);
foreach ($langs as $lang) {
    if ($lang->isDot() or $lang->isLink() or !$lang->isDir()) {
        continue;
    }

    $lang = $lang->getFilename();

    if ($lang == 'en' or $lang == 'en_utf8' or $lang == 'CVS' or $lang == '.settings') {
        continue;
    }

    $xmllang = str_replace('_utf8', '', $lang);
    if (array_key_exists($xmllang, $langconversion)) {
        $xmllang = $langconversion[$xmllang];
        if (empty($xmllang)) {
            echo "         Ignoring: $lang\n";
            continue;
        }
    }

    $xmlfile = "$tempdir/$xmllang.xml";
    if (!file_exists($xmlfile)) {
        echo "         Skipping: $lang\n";
        continue;
    }

    $langfile = "$targetlangdir/$lang/editor_tinymce.php";

    if (file_exists($langfile)) {
        $old_strings = editor_tinymce_get_all_strings($langfile);
        foreach ($old_strings as $key=>$value) {
            if (!array_key_exists($key, $en_strings)) {
                unset($old_strings[$key]);
            }
        }
    } else {
        $old_strings = array();
    }

    //remove all strings from upstream - ignore our modifications for now
    // TODO: add support for merging of our tweaks
    $parsed = editor_tinymce_parse_xml_lang($xmlfile);
    foreach ($parsed as $key=>$value) {
        if (array_key_exists($key, $old_strings)) {
            unset($old_strings[$key]);
        }
    }

    if (!$handle = fopen($langfile, 'w')) {
         echo "Cannot write to $filename !!";
         continue;
    }
    echo "Modifying: $lang\n";

    fwrite($handle, "<?php\n\n");

    foreach ($old_strings as $key=>$value) {
        fwrite($handle, editor_tinymce_encode_stringline($key, $value));
    }

    fwrite($handle, "\n\n\n\n// Automatically created strings from original TinyMCE lang files, please do not update yet\n\n");

    foreach ($parsed as $key=>$value) {
        fwrite($handle, editor_tinymce_encode_stringline($key, $value));
    }
    fclose($handle);
}
unset($langs);


die("\nFinished!");







/// ============ Utility functions ========================

function editor_tinymce_encode_stringline($key, $value) {
        $value = str_replace("%","%%",$value);              // Escape % characters
        $value = trim($value);                              // Delete leading/trailing white space
        return "\$string['$key'] = ".var_export($value, true).";\n";
}

function editor_tinymce_parse_xml_lang($file) {
    $result = array();

    $doc = new DOMDocument();
    $doc->load($file);
    $groups = $doc->getElementsByTagName('group');
    foreach($groups as $group) {
        $section = $group->getAttribute('target');
        $items = $group->getElementsByTagName('item');
        foreach($items as $item) {
            $name  = $item->getAttribute('name');
            $value = $item->textContent;
            $result["$section:$name"] = $value;
        }
    }
    return $result;
}

function editor_tinymce_get_all_strings($file) {
    global $CFG;

    $string = array();
    require_once($file);

    return $string;
}