<?php

/**
 * Functions specific to plugin
 *
 * @package    local
 * @subpackage repositoryfileupload
 * @copyright  2015 UCL
 * @license    http://www.ucl.ac.uk
 */

include_once "$CFG->dirroot/lib/filelib.php";

$local_repositoryfileupload_sessprefix = 'local_repositoryfileupload_';
$local_repositoryfileupload_nonceprefix = $local_repositoryfileupload_sessprefix . 'nonce';

/**
 * Function to extend navigation block. Adds 'Create Test Accounts' item under 'course administration'
 *
 * @global moodle_page $PAGE
 * @param settings_navigation $settingsnav
 * @param context $context
  */
function local_repositoryfileupload_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('moodle/backup:backupcourse', context_course::instance($PAGE->course->id))) {
        return;
    }

    /**check if there are any course-wide repositories for this course.
    The option to upload files to repositories will be displayed only if there are any repositories for that course
     **/
    $repositories = array();
    $repositories = local_repositoryfileupload_getcourserepositories($PAGE->course->id);
    if(!empty($repositories)) {
        if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
            $strfoo = get_string('uploadfilesetting', 'local_repositoryfileupload');
            $url = new moodle_url('/local/repositoryfileupload/upload.php', array('courseid' => $PAGE->course->id));
            $foonode = navigation_node::create(
                $strfoo, $url, navigation_node::NODETYPE_LEAF, 'repositoryfileupload', 'repositoryfileupload', new pix_icon('a/add_file', $strfoo)
            );
            if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
                $foonode->make_active();
            }
            $settingnode->add_node($foonode);
        }
    }
}


/**
 * Function returns array of names of repositories registered for a
 * course
 *
 * @param int $courseid
 * @return array
 */
function local_repositoryfileupload_getcourserepositories($courseid){
    global $DB;
    $coursecontext = context_course::instance($courseid);

    $repositories = array();

    $sql = 'SELECT DISTINCT instanceid, value
              FROM {repository_instance_config}
              WHERE instanceid IN (
                SELECT id
                  FROM {repository_instances}
                  WHERE contextid = :contextid
                    AND typeid = (SELECT id FROM {repository} WHERE type = :type)
              ) AND name = :name';

    $repositories = $DB->get_records_sql($sql, array('contextid' => $coursecontext->id, 'type' => 'filesystem', 'name' => 'fs_path'));
    $repositorylist = array();
    foreach ($repositories as $repo) {
        $repositorylist[$repo->instanceid] = $repo->value;
    }
    ksort($repositorylist);
    return $repositorylist;
}


/**
 * Function to return list of directories within repositories dir in $dataroot
 *
 * @param string $dataroot
 * @return array
 */
function local_repositoryfileupload_getrepositorydirs($dataroot) {

    $dirlist = array();
    $path = $dataroot.'/repository/';

    //check if /repository/ exists
    if(!is_dir($path)) {
        return false;
    }

    // read all directories within /repository/
    foreach(scandir($path) as $dir){
        if(substr($dir, 0, 1) === '.'){
            continue;
        }
        if(is_dir($path.'/'.$dir)){
            $dirlist[] = $dir;
        }
    }

    return $dirlist;
}


/**
 * Function to return list of files in a repository. Only returns files, does
 * not return directories, is not recursive.
 *
 * @param string $dataroot - path to root of moodle data dir
 * @param string $repositoryname - name of the respository to list
 * @return boolean
 */
function local_repositoryfileupload_getrepositoryfiles($dataroot, $repositoryname) {

    $filelist = array();
    $path = $dataroot.'/repository/'.$repositoryname;

    //check if /repository/ exists
    if(!is_dir($path)){
        return false;
    }

    // read all files within repository
    foreach(scandir($path) as $item) {
        if ((substr($item, 0, 1) === '.') or (!is_file($path.'/'.$item))) {
            continue;
        }
        $filelist[] = $item;
    }

    return $filelist;
}


/**
 * Function to return hash of supplied repository name and filenames. Used
 * to check whether files have changed since form was generated. Returns
 * SHA512 hash string.
 *
 * @param string $repositoryid - id of the respository
 * @param array $filenames - array of filenames to hash
 * @return string
 */
function local_repositoryfileupload_getrepositoryfileshash($repositoryid, $filenames) {
    sort($filenames);
    $fileshash = hash('sha512', $repositoryid . implode($filenames));
    return $fileshash;
}


/**
 * Function to upload files to selected destination after checking for errors
 *
 * @param array $uploadfiles
 * @param string $destination
 * @return type
 */
function local_repositoryfileupload_uploadfiles(array $uploadfiles, $destination){

    $count = 0;
    $uploadedfiles = array();
    foreach ($uploadfiles['name'] as $f => $name) {
        if(move_uploaded_file($_FILES['uploadfiles']['tmp_name'][$f], $destination.$name)){
            $uploadedfiles['files'][$f] = get_string('success', 'local_repositoryfileupload', $name);
            $count++;
        }
    }
    $a = new stdClass();
    $a->count = $count;
    $a->path = $destination;
    $uploadedfiles['count'] = get_string('uploadfilescount', 'local_repositoryfileupload', $a);

    return $uploadedfiles;

}


/**
 * Function to process uploaded files for errors
 *
 * @param array $uploadfiles
 * @param string $destination
 * @return type
 */
function local_repositoryfileupload_processfiles($uploadfiles, $destination) {
    global $OUTPUT;

    $maxfilesize = ini_get('upload_max_filesize');
    $postmaxsize = ini_get('post_max_size');

    $uploadmaxfilesize = local_repositoryfileupload_getfilesizemb($maxfilesize);

    //get valid filetypes
    $valid_filetypes = &get_mimetypes_array();

    //initialise array to hold error messages
    $message = array();

    // Loop $_FILES to execute all files
    foreach ($uploadfiles['name'] as $f => $name) {
        echo $OUTPUT->container("Processing $name ..");

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        //check if there are any errors in uploaded file
        $errorcode = $uploadfiles['error'][$f];

        // call standard moodle function to check for erros within input _FILE
        $response = file_get_upload_error($errorcode);
        if($response !== ''){
            if($errorcode == 1){
                $response .= '. '.get_string('maxfilesize', 'local_repositoryfileupload', $maxfilesize);
            }
            $message['error'][$f] = $response;
            echo $OUTPUT->container('error..');
            continue;
        }

        //validate files if no errors
        if ($errorcode == 0 and $response == '') {

            if ($uploadfiles['size'][$f] > $uploadmaxfilesize) {
                // Skip large files
                $a = new stdClass();
                $a->maxfilesize = $maxfilesize;
                $a->name = $name;
                $message['error'][$f] = get_string('largefile', 'local_repositoryfileupload', $a);
                echo $OUTPUT->container('error..');
                continue;
            }

            if (empty($valid_filetypes[$extension])) {
                // Skip invalid file formats
                $message['error'][$f] = get_string('invalidformat', 'local_repositoryfileupload', $name);
                echo $OUTPUT->container('error..');
                continue;
            }

            if (move_uploaded_file($_FILES['uploadfiles']['tmp_name'][$f], $destination . $name)) {
                $message['files'][$f] = get_string('uploadsuccess', 'local_repositoryfileupload', $name);
                echo $OUTPUT->container('done..');
            }
        }
    }

    return $message;
}


/**
 * Function to delete files from repository
 *
 * @param array $filestodelete - filenames to delete
 * @param string $dataroot - path to root of moodle data dir
 * @param string $repositoryname - name of the repository to delete files from
 * @return type
 */
function local_repositoryfileupload_deletefiles($filestodelete, $dataroot, $repositoryname) {

    $path = $dataroot.'/repository/'.$repositoryname.'/';
    $count = 0;
    $deletedfiles = array();
    foreach ($filestodelete as $filename) {
        $result = unlink($path.$filename);
        $deletedfiles[$filename] = !is_file($path.$filename);
    }

    return $deletedfiles;

}


/**
 * Function to convert MB to Bytes
 *
 * @param type $sizemb
 * @return type
 */
function local_repositoryfileupload_getfilesizemb($sizemb){
    $sizebytes = (int)$sizemb * 1024 *1024;

    return $sizebytes;
}


/**
 * Function to print upload errors
 *
 * @param array $errors
 * @return \html_table
 */
function local_repositoryfileupload_printerror(array $errors){
    $table = new html_table();
    $table->width = "95%";
    $table->head = array('Errors');
    $columns = array('error');

    foreach($errors['error'] as $key => $message) {
        $table->data[] = array (
            $message
        );
    }
    return $table;
}


/**
 * Function to display uploaded files
 *
 * @param array $files
 * @return \html_table
 */
function local_repositoryfileupload_printuploadedfiles($files) {
    $table = new html_table();
    $table->width = "95%";
    $table->head = array('Uploaded Files');
    $columns = array('Files');

    foreach($files['files'] as $key => $message) {
        $table->data[] = array (
            $message
        );
    }
    return $table;
}


/**
 * Function to display deleted files
 *
 * @param array $deletedfiles - list of filenames deleted
 * @param array $repositoryname - name of repository the files were deleted from
 * @return \html_table
 */
function local_repositoryfileupload_printdeletedfiles($deletedfiles, $repositoryname) {
    $table = new html_table();
    $table->width = "95%";
    $a = $repositoryname;
    $table->head = array(get_string('deletedfilesheaders', 'local_repositoryfileupload', $a));

    foreach($deletedfiles as $filename => $result) {
        $str = $result ? 'deletesuccess' : 'deleteerror';
        $message = get_string($str, 'local_repositoryfileupload', $filename);
        $table->data[] = array (
            $message
        );
    }
    return $table;
}


/**
 * Function to generate HTML for link-based repository selector
 *
 * @param array $repositories - array of repositories to choose from, ($repositoryid => $repositoryname)
 * @param int $courseid
 * @param string $selectedrepository - name of currently selected repository, if any. Supply null if none selected.
 * @param string url - the base url to send users to when clicking a repository
 * @return type
 */
function local_repositoryfileupload_repositoryselector($repositories, $courseid, $selectedrepositoryid, $url) {

    $output = '<ul>';
    foreach ($repositories as $repositoryid => $repositoryname) {
        $output .= '<li>';
        if ($repositoryid == $selectedrepositoryid) {
            $output .= htmlspecialchars(ucfirst($repositoryname) . ' [selected]');
        } else {
            $params = array('courseid' => $courseid, 'repositoryid' => $repositoryid);
            $qs = http_build_query($params);
            $href = $url . '?' . $qs;
            $output .= '<a href="' . $href . '">' . htmlspecialchars(ucfirst($repositoryname)) . '</a>';
        }
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}


/**
 * Function to create a nonce and store it in the session. Creates an id for the nonce
 * which is used in the key the nonce is stored under in session. Returns nonce and
 * nonce id. id must be remembered and supplied when checking the nonce with checknonce().
 *
 * @param int expires - time the nonce is valid for in minutes. Default 20 mins
 * @return array
 */
function local_repositoryfileupload_generatenonce($expires=20) {
    global $local_repositoryfileupload_nonceprefix;

    $time = gettimeofday();
    $epoch = $time['sec'];
    $salt = $time['usec'];
    $nonce = hash('sha512', openssl_random_pseudo_bytes(115) . $salt);
    // generate nonceid, make sure it's not already in use
    do {
        $nonceid = mt_rand();
        $sesskey = $local_repositoryfileupload_nonceprefix . $nonceid;
    } while (array_key_exists($sesskey, $_SESSION));

    $_SESSION[$sesskey] = array('nonce' => $nonce, 'expires' => $epoch + (60 * $expires));
    return array('id' => $nonceid, 'nonce' => $nonce);
}

/**
 * Function to check a nonce against that stored in session. Removes nonce from session if match.
 *
 * @param string nonceid - identifier for this nonce, this should match the id returned by generatenonce when this nonce was generated
 * @param string checknonce - the nonce to check
 * @return bool
 */
function local_repositoryfileupload_checknonce($nonceid, $checknonce) {
    global $local_repositoryfileupload_nonceprefix;

    $sesskey = $local_repositoryfileupload_nonceprefix . $nonceid;
    $result = false;

    local_repositoryfileupload_expirenonces();

    if (isset($_SESSION[$sesskey])) {
        // Session value is set
        if ($_SESSION[$sesskey]['nonce'] == $checknonce) {
            $result = true;
        }
        // Always unset, this is a one time nonce. If someone is trying to guess it
        // want to make it impossible to succeed.
        unset($_SESSION[$sesskey]);
    }

    return $result;
}


/**
 * Function to check nonces stored in session and remove any that have expired. Returns
 * number of nonces that were removed.
 *
 * @return int
 */
function local_repositoryfileupload_expirenonces() {
    global $local_repositoryfileupload_nonceprefix;

    $sessprefix = $local_repositoryfileupload_nonceprefix;
    $count = 0;

    foreach ($_SESSION as $key => $value) {
        // check if key begins with sessname
        if (substr($key, 0, strlen($sessprefix)) === $sessprefix) {
            $epoch = time();
            if ($epoch > $value['expires']) {
                unset($_SESSION[$key]);
                $count += 1;
            }
        }
    }

    return $count;
}

/**
 * Shows a generic error page with continue button
 *
 * @param string $errormsg - the text of the error to display
 * @param string $action - the action that you were trying to perform when the error was generated. Should be one of 'upload' or 'delete'.
 * @param int $courseid - the id of the course being accessed
 * @param string $continuepath - the URL the continue button directs the user to. If not supplied, defaults to "/local/repositoryfileupload/$action.php"
 * @param array $continueparams - any extra URL parameters that need to be included with the continue button URL
 */
function local_repositoryfileupload_showerror($errormsg, $action, $courseid, $continuepath = null, $continueparams = array()) {
    global $OUTPUT;
    echo $OUTPUT->header();
    $string = $action . '_outputheading';
    echo $string;
    echo $OUTPUT->heading(get_string($string, 'local_repositoryfileupload'));
    echo $OUTPUT->notification(get_string($action . '_notification_msg', 'local_repositoryfileupload'), 'notifysuccess');
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    echo $OUTPUT->heading(get_string('errors', 'local_repositoryfileupload'));
    echo $OUTPUT->notification($errormsg, 'notifyproblem');
    if ($continuepath === null) {
        $continuepath = "/local/repositoryfileupload/$action.php";
        $continueparams = array('courseid' => $courseid);
    }
    $return = new moodle_url($continuepath, $continueparams);
    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die;
}

/**
 * Shows an error page with continue button. Provides a shortcut method for generating errors
 * which use the 'Session may have expired (<specific error>)...' format of error message, which
 * is useful for form validation errors and security problems.
 *
 * @param string $errorstringkey - the key in strings file $string array for the error that is to appear in parentheses
 * @param string $action - the action that you were trying to perform when the error was generated. Should be one of 'upload' or 'delete'.
 * @param int $courseid - the id of the course being accessed
 * @return int
 */
function local_repositoryfileupload_showformerror($errorstringkey, $action, $courseid) {
    $a = new stdClass();
    $a->error = get_string($errorstringkey, 'local_repositoryfileupload');
    switch ($action) {
        case 'delete': $a->action = 'deleted'; break;
        case 'upload': $a->action = 'uploaded'; break;
    }
    $errormessage = get_string('formerror', 'local_repositoryfileupload', $a);
    local_repositoryfileupload_showerror($errormessage, $action, $courseid);
}
