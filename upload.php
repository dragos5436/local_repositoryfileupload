<?php

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/local/repositoryfileupload/lib.php');

global $CFG, $PAGE, $OUTPUT, $USER;

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('local/repositoryfileupload:upload', $context);

$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$url = $CFG->wwwroot . '/local/repositoryfileupload/upload.php';
$PAGE->set_url($url);

// Get respoitories related to a course
$repositories = local_repositoryfileupload_getcourserepositories($course->id);


///////////////////////
// PROCESS FORM

$requestmethod = $_SERVER['REQUEST_METHOD'];

if (isset($_POST) and $requestmethod == 'POST') {

    // UPLOAD FILES
    if ($_POST['upload']) {

        $filesarray = $_FILES['uploadfiles'];

        //-- if we decide to use system wide repositoies list
        //$respositoryid = $_POST['repository'];
        //$repositoryname = $directorylist[$respositoryid];
        //--

        // Check repository is valid
        $repositoryid = $_POST['repositoryid'];
        if (! array_key_exists($repositoryid, $repositories)) {
            local_repositoryfileupload_showerror(get_string('invalidrepository', 'local_repositoryfileupload'), 'upload', $courseid);
        }
        $repositoryname = $repositories[$repositoryid];

        // Check nonce
        $nonce = $_POST['nonce'];
        $nonceid = $_POST['nonceid'];
        if (!local_repositoryfileupload_checknonce($nonceid, $nonce)) {
            local_repositoryfileupload_showformerror('invalidnonce', 'upload', $courseid);
        }

        // Upload files to server path: $CFG->dataroot/repository/
        $repositoryroot = $CFG->dataroot . '/repository/';
        $destination = $repositoryroot . $repositoryname . '/';

        // Check uploaded files for errors and upload
        echo $OUTPUT->header();

        // Check if directory actually exists. we dont want to proceed further if the directory name within $CFG->dataroot/repository/ has changed/deleted
        if(!is_dir($destination)) {
            local_repositoryfileupload_showerror(get_string('directorynotfound', 'local_repositoryfileupload', $repositoryname), 'upload', $courseid);
        }

        echo $OUTPUT->heading('File upload in progress......');
        $result = local_repositoryfileupload_processfiles($filesarray, $destination);
        if (!empty($result['error'])) {
            //display errors
            echo $OUTPUT->heading(get_string('errornotification', 'local_repositoryfileupload'));
            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
            $table = local_repositoryfileupload_printerror($result);
            echo html_writer::table($table);
            $return = new moodle_url('/local/repositoryfileupload/upload.php', array('courseid' => $courseid));
            echo $OUTPUT->continue_button($return);
            echo $OUTPUT->box_end();
        }

        if(!empty($result['files'])){
            // Move uploaded files after checking for error
            $filecount = count($result['files']);
            $a = new stdClass();
            $a->count = $filecount;
            $a->path = $destination;
            echo $OUTPUT->heading(get_string('uploadfilescount', 'local_repositoryfileupload', $a));
            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
            $table = local_repositoryfileupload_printuploadedfiles($result);
            echo html_writer::table($table);
            $return = new moodle_url('/local/repositoryfileupload/upload.php', array('courseid' => $courseid));
            echo $OUTPUT->continue_button($return);
            echo $OUTPUT->box_end();
        }

        echo $OUTPUT->footer();
        die;

    } else if ($_POST['cancel']) {
        $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
        redirect($redirecturl);
    }
}

echo $OUTPUT->header();

// Set tabs with current tab
$currenttab = 'uploadfiles';
include('tabs.php');

echo $OUTPUT->heading(get_string('upload_outputheading', 'local_repositoryfileupload'));
echo $OUTPUT->notification(get_string('upload_notification_msg', 'local_repositoryfileupload'), 'notifysuccess');
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
$nonceinfo = local_repositoryfileupload_generatenonce();
$nonce = $nonceinfo['nonce'];
$nonceid = $nonceinfo['id'];
require "$CFG->dirroot/local/repositoryfileupload/forms/upload_form.html";
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

