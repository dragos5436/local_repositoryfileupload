<?php

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/local/repositoryfileupload/lib.php');
require_once($CFG->dirroot.'/local/repositoryfileupload/forms/repositoryfileupload_delete_form.php');

global $CFG, $PAGE, $OUTPUT, $USER;

$courseid = required_param('courseid', PARAM_INT);
$repositoryid = optional_param('repositoryid', null, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('local/repositoryfileupload:delete', $context);

$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$url = $CFG->wwwroot . '/local/repositoryfileupload/delete.php';
$PAGE->set_url($url);

// Get respoitories related to a course
$repositories = local_repositoryfileupload_getcourserepositories($course->id);


///////////////////////
// PROCESS FORM

// Check valid repo for this course
if ($repositoryid != null) {
    if (! array_key_exists($repositoryid, $repositories)) {
        local_repositoryfileupload_showerror(get_string('invalidrepository', 'local_repositoryfileupload'), 'upload', $courseid);
    }
} else if (count($repositories) == 1) {
    // If only one valid repo then automatically select this
    $repositoryid = array_keys($repositories)[0];
}
$repositoryname = array_key_exists($repositoryid, $repositories) ? $repositories[$repositoryid] : null;

// Instantiate form
$mform = new local_repositoryfileupload_delete_form(NULL, array('courseid' => $course->id, 'repositoryid' => $repositoryid, 'repositoryname' => $repositoryname));

$showreposelector = false;
$showfilesform = false;
$showdeletefileresults = false;

// Form processing is done here
if ($mform->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form
    $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirecturl);

} else if ($fromform = $mform->get_data()) {
    // Do we have any files to delete?
    if (isset($fromform->filestodelete) and (count($fromform->filestodelete) > 0)) {
        // Yes, we have some files to delete. Delete files and output delete confirmation page.

        $rawdata = $mform->get_submitted_data();

        // Check nonce
        $nonce = $rawdata->nonce;
        $nonceid = $rawdata->nonceid;
        if (!local_repositoryfileupload_checknonce($nonceid, $nonce)) {
            local_repositoryfileupload_showformerror('invalidnonce', 'delete', $courseid);
        }

        // Check filelist hasn't changed / correct repo is being requested
        $files = local_repositoryfileupload_getrepositoryfiles($CFG->dataroot, $repositoryname);
        $fileshash = local_repositoryfileupload_getrepositoryfileshash($repositoryid, $files);
        if ($fromform->fileshash != $fileshash) {
            local_repositoryfileupload_showformerror('repositorymodified', 'delete', $courseid);
        }

        // Check the supplied indices are in range
        $filesenccount = count($files);
        foreach ($fromform->filestodelete as $idx) {
            if (($idx < 0) or ($idx > $filesenccount - 1)) {
                local_repositoryfileupload_showformerror('filestodeleteidx', 'delete', $courseid);
            }
        }

        // Calculate filenames to delete from supplied indices
        $filestodelete = array();
        foreach ($fromform->filestodelete as $idx) {
            $filestodelete[] = $files[$idx];
        }

        // Delete files
        $deletedfiles = local_repositoryfileupload_deletefiles($filestodelete, $CFG->dataroot, $repositoryname);
        $deletedcount = count(array_filter($deletedfiles));
        $showdeletefileresults = true;
        $showreposelector = false;
        $showfilesform = false;

    } else {
        // No files to delete
        $showreposelector = true;
        $showfilesform = true;
    }

} else {
    $showreposelector = true;
    $showfilesform = true;
}

if (!$repositoryid) {
    $showreposelector = true;
    $showfilesform = false;
}


////////////////////////
// PAGE OUTPUT

echo $OUTPUT->header();

// Set tabs page with current tab
$currenttab = 'removefiles';
include('tabs.php');

echo $OUTPUT->heading(get_string('delete_outputheading', 'local_repositoryfileupload'));
echo $OUTPUT->notification(get_string('delete_notification_msg', 'local_repositoryfileupload'), 'notifysuccess');
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

// Output file delete results
if ($showdeletefileresults) {
    $table = local_repositoryfileupload_printdeletedfiles($deletedfiles, $repositoryname);
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    echo html_writer::table($table);
    $a =  new stdClass();
    $a->count = $deletedcount;
    $a->filenoun = $deletedcount == 1 ? 'file' : 'files';
    echo $OUTPUT->heading(get_string('deletedfiles', 'local_repositoryfileupload', $a));
    echo $OUTPUT->box_end();
    $return = new moodle_url($url, array('courseid' => $courseid, 'repositoryid' => $repositoryid));
    echo $OUTPUT->continue_button($return);
}

// Output repository selection list if more than one repo for this course
$reposcount = count($repositories);
if ($showreposelector) {
    if ($reposcount > 1) {
        echo $OUTPUT->heading(get_string('repositorylistdel', 'local_repositoryfileupload'), 2, 'mdl-left');
        echo local_repositoryfileupload_repositoryselector($repositories, $courseid, $repositoryid, $url);
    } else if ($reposcount == 0) {
        echo $OUTPUT->heading(get_string('norepositories', 'local_repositoryfileupload'), 2, 'mdl-left');
        $showfilesform = false;
    }
}

// Display the files selection form
if ($showfilesform) {
    $mform->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
