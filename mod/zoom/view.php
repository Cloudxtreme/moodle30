<?php
// This file is part of the Zoom plugin for Moodle - http://moodle.org/
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
 * Prints a particular instance of zoom
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_zoom
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/../../lib/moodlelib.php');

$config = get_config('mod_zoom');

list($course, $cm, $zoom) = zoom_get_instance_setup();

$event = \mod_zoom\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $zoom);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/zoom/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($zoom->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('zoom-'.$somevar);
 */

$cache = cache::make('mod_zoom', 'zoomid');
if (!($zoomuserid = $cache->get($USER->id))) {
    $zoomuserid = false;
    $service = new mod_zoom_webservice();
    // Not an error if this fails, since people don't need a Zoom account to view/join meetings.
    if ($service->user_getbyemail($USER->email)) {
        $zoomuserid = $service->lastresponse->id;
    }
    $cache->set($USER->id, $zoomuserid);
}
$userishost = ($zoomuserid == $zoom->host_id);

$stryes = get_string('yes');
$strno = get_string('no');
$strstart = get_string('start_meeting', 'mod_zoom');
$strjoin = get_string('join_meeting', 'mod_zoom');
$strunavailable = get_string('unavailable', 'mod_zoom');
$strtime = get_string('meeting_time', 'mod_zoom');
$strduration = get_string('duration', 'mod_zoom');
$strpassprotect = get_string('passwordprotected', 'mod_zoom');
$strpassword = get_string('password', 'mod_zoom');
$strjoinlink = get_string('join_link', 'mod_zoom');
$strjoinbeforehost = get_string('joinbeforehost', 'mod_zoom');
$strstartvideohost = get_string('starthostjoins', 'mod_zoom');
$strstartvideopart = get_string('startpartjoins', 'mod_zoom');
$straudioopt = get_string('option_audio', 'mod_zoom');
$strstatus = get_string('status', 'mod_zoom');
$strall = get_string('allmeetings', 'mod_zoom');

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($zoom->name), 2);
if ($zoom->intro) {
    echo $OUTPUT->box(format_module_intro('zoom', $zoom, $cm->id), 'generalbox mod_introbox', 'intro');
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_view';

$table->align = array('center', 'left');
$numcolumns = 2;

list($inprogress, $available, $finished) = zoom_get_state($zoom);

if ($available) {
    if ($userishost) {
        $buttonhtml = html_writer::tag('button', $strstart,
                array('type' => 'submit', 'class' => 'btn btn-success'));
        $aurl = new moodle_url($zoom->start_url);
    } else {
        $buttonhtml = html_writer::tag('button', $strjoin,
                array('type' => 'submit', 'class' => 'btn btn-primary'));
        $aurl = new moodle_url('/mod/zoom/loadmeeting.php', array('id' => $cm->id));
    }
    $buttonhtml .= html_writer::input_hidden_params($aurl);
    $link = html_writer::tag('form', $buttonhtml, array('action' => $aurl->out_omit_querystring()));
} else {
    $link = html_writer::tag('span', $strunavailable, array('style' => 'font-size:20px'));
}

$title = new html_table_cell($link);
$title->header = true;
$title->colspan = $numcolumns;
$table->data[] = array($title);

$sessionsurl = new moodle_url('/mod/zoom/report.php', array('id' => $cm->id));
$sessionslink = html_writer::link($sessionsurl, get_string('sessions', 'mod_zoom'));
$sessions = new html_table_cell($sessionslink);
$sessions->colspan = $numcolumns;
$table->data[] = array($sessions);

if ($zoom->type == ZOOM_RECURRING_MEETING) {
    $recurringmessage = new html_table_cell(get_string('recurringmeetinglong', 'mod_zoom'));
    $recurringmessage->colspan = $numcolumns;
    $table->data[] = array($recurringmessage);
} else {
    $table->data[] = array($strtime, userdate($zoom->start_time));
    $table->data[] = array($strduration, format_time($zoom->duration));
}

$haspassword = (isset($zoom->password) && $zoom->password !== '');
$strhaspass = ($haspassword) ? $stryes : $strno;
$table->data[] = array($strpassprotect, $strhaspass);

if ($zoomuserid === $zoom->host_id && $haspassword) {
    $table->data[] = array($strpassword, $zoom->password);
}

if ($userishost) {
    $table->data[] = array($strjoinlink, html_writer::link($zoom->join_url, $zoom->join_url));
}

$strjbh = ($zoom->option_jbh) ? $stryes : $strno;
$table->data[] = array($strjoinbeforehost, $strjbh);

$strvideohost = ($zoom->option_host_video) ? $stryes : $strno;
$table->data[] = array($strstartvideohost, $strvideohost);

$strparticipantsvideo = ($zoom->option_participants_video) ? $stryes : $strno;
$table->data[] = array($strstartvideopart, $strparticipantsvideo);

$table->data[] = array($straudioopt, $zoom->option_audio);

if ($zoom->type != ZOOM_RECURRING_MEETING) {
    if ($zoom->type == ZOOM_MEETING_EXPIRED) {
        $status = get_string('meeting_expired', 'mod_zoom');
    } else if ($finished) {
        $status = get_string('meeting_finished', 'mod_zoom');
    } else if ($inprogress) {
        $status = get_string('meeting_started', 'mod_zoom');
    } else {
        $status = get_string('meeting_not_started', 'mod_zoom');
    }

    $table->data[] = array($strstatus, $status);
}

$urlall = new moodle_url('/mod/zoom/index.php', array('id' => $course->id));
$linkall = html_writer::link($urlall, $strall);
$linktoall = new html_table_cell($linkall);
$linktoall->colspan = $numcolumns;
$table->data[] = array($linktoall);

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
