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
 * Abstraction of general file archives.
 *
 * @package    moodlecore
 * @subpackage file-packer
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class file_archive implements Iterator {

    /** Open archive if exists, fail if does not exist. */
    const OPEN      = 1;

    /** Open archive if exists, create if does not. */
    const CREATE    = 2;

    /** Always create new archive */
    const OVERWRITE = 4;

    /** Encoding of file names - windows usually expects DOS single-byte charset*/
    protected $encoding = 'utf-8';

    /**
     * Open or create archive (depending on $mode)
     * @param string $archivepathname
     * @param int $mode OPEN, CREATE or OVERWRITE constant
     * @param string $encoding archive local paths encoding
     * @return bool success
     */
    public abstract function open($archivepathname, $mode=file_archive::CREATE, $encoding='utf-8');

    /**
     * Close archive
     * @return bool success
     */
    public abstract function close();

    /**
     * Returns file stream for reading of content
     * @param int $index of file
     * @return stream or false if error
     */
    public abstract function get_stream($index);

    /**
     * Returns file information
     * @param int $index of file
     * @return info object or false if error
     */
    public abstract function get_info($index);

    /**
     * Returns array of info about all files in archive
     * @return array of file infos
     */
    public abstract function list_files();

    /**
     * Returns number of files in archive
     * @return int number of files
     */
    public abstract function count();

    /**
     * Add file into archive
     * @param string $localname name of file in archive
     * @param string $pathname localtion of file
     * @return bool success
     */
    public abstract function add_file_from_pathname($localname, $pathname);

    /**
     * Add content of string into archive
     * @param string $localname name of file in archive
     * @param string $contents
     * @return bool success
     */
    public abstract function add_file_from_string($localname, $contents);

    /**
     * Add empty directory into archive
     * @param string $local
     * @return bool success
     */
    public abstract function add_directory($localname);

    /**
     * Tries to convert $localname into another encoding,
     * please note that it may fail really badly.
     * @param strin $localname in utf-8 encoding
     * @return string
     */
    protected function mangle_pathname($localname) {
        if ($this->encoding === 'utf-8') {
            return $localname;
        }
        $textlib = textlib_get_instance();

        $converted = $textlib->convert($localname, 'utf-8', $this->encoding);
        $original  = $textlib->convert($converted, $this->encoding, 'utf-8');

        if ($original === $localname) {
            $result = $converted;

        } else {
            // try ascci conversion
            $converted2 = $textlib->specialtoascii($localname);
            $converted2 = $textlib->convert($converted2, 'utf-8', $this->encoding);
            $original2  = $textlib->convert($converted, $this->encoding, 'utf-8');

            if ($original2 === $localname) {
                //this looks much better
                $result = $converted2;
            } else {
                //bad luck - the file name may not be usable at all
                $result = $converted;
            }
        }

        $result = ereg_replace('\.\.+', '', $result);
        $result = ltrim($result); // no leadin /

        if ($result === '.') {
            $result = '';
        }

        return $result;
    }

    /**
     * Tries to convert $localname into utf-8
     * please note that it may fail really badly.
     * The resulting file name is cleaned.
     *
     * @param strin $localname in anothe encoding
     * @return string in utf-8
     */
    protected function unmangle_pathname($localname) {
        if ($this->encoding === 'utf-8') {
            return $localname;
        }
        $textlib = textlib_get_instance();

        $result = $textlib->convert($localname, $this->encoding, 'utf-8');
        $result = clean_param($result, PARAM_PATH);
        $result = ltrim($result); // no leadin /

        return $result;
    }

    /**
     * Returns current file info
     * @return object
     */
    //public abstract function current();

    /**
     * Returns the index of current file
     * @return int current file index
     */
    //public abstract function key();

    /**
     * Moves forward to next file
     * @return void
     */
    //public abstract function next();

    /**
     * Revinds back to the first file
     * @return void
     */
    //public abstract function rewind();

    /**
     * Did we reach the end?
     * @return boolean
     */
    //public abstract function valid();

}