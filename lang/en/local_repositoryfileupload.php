<?php

/**
 * Language strings
 *
 * @package    local
 * @subpackage repositoryfileupload
 * @copyright  2014 UCL
 * @license    http://www.ucl.ac.uk
 */

$string['pluginname'] = 'Respository file upload';

// Settings
$string['settings'] = 'Respository File Upload';
$string['uploadmaxfilesize'] = 'Upload max filesize';
$string['uploadmaxfilesizedesc'] = 'Uploaded files whose size exceeds this limit will be rejected';

// Capabilities
$string['repositoryfileupload:upload'] = 'Upload files to repository';
$string['repositoryfileupload:delete'] = 'Delete files from repository';

// Upload form
$string['upload_outputheading'] = 'Upload files to repository';
$string['uploadfilesetting'] = 'Upload to Repository';
$string['repositorylist'] = 'Upload repository';
$string['repositorylist_help'] = 'Please select repository to upload files to';
$string['fileupload'] = 'Upload Files';
$string['fileupload_help'] = 'Select files for upload to the repository. Files should be optimised for size since large files will affect performance.';
$string['upload'] = 'Upload Files';
$string['cancel'] = 'Cancel';
$string['upload_notification_msg'] = "<p>This file repository upload feature is only available via direct request from the E-Learning Environments team. It is not enabled for all courses.
Specific permissions are required to upload files to repositories.</p>
<p>If you do not see a correctly associated course repository, please do not attempt to upload a file, and contact ele@ucl.ac.uk to resolve.</p>";
$string['uploadfile_msg'] = 'Files uploaded with the same name will overwrite previous versions';

// Repository delete
$string['delete_outputheading'] = 'Delete files from Repository';
$string['delete_notification_msg'] = "<p>This delete feature is only available via direct request from the via E-Learning Environments team. It is not enbaled for all courses.
Specific permissions are required to delete files from repositories.</p>
<p>If you do not see a correctly associated course repository, please do not attempt to delete files, and contact ele@ucl.ac.uk to resolve.</p>";
$string['deletedfilesheaders'] = 'Files deleted from {$a}';
$string['deletedfiles'] = '{$a->count} {$a->filenoun} deleted';

// Delete form
$string['fileslistheader'] = 'Select files to delete from repository {$a}';
$string['fileselectlabel'] = 'To select multiple files, click while holding ctrl or shift';
$string['repositorylistdel'] = 'Select a repository';
$string['deletefiles'] = 'Delete selected files';

// Error messages
$string['errors'] = 'Errors found';
$string['invalidcourse'] = 'Invalid course';
$string['nofiles'] = 'There are no files uploaded';
$string['emptyrepo'] = 'No files in repository';
$string['errorfile'] = '{$a} has some errors';
$string['largefile'] = '{$a->name} file exceeded the maximum size limit set by the server. Current file size limit: {$a->maxfilesieze} ';
$string['maxfilesize'] = 'Current file size limit: {$a} ';
$string['invalidformat'] = '{$a} is not a valid format';
$string['exceeduploadnumberlimit'] = 'You have exceeded the maximum number of files allowed for upload. You have uploaded {$a->uploadcount} files. You can upload at maximum {$a->maxfileuploads} files';
$string['invalidrepository'] = 'The repository specified is not a valid repository';
$string['norepositories'] = 'No repositories for this course';
$string['directorynotfound'] = '{$a} directory not found';
$string['formerror'] = 'Your session may have expired ({$a->error}). No files have been {$a->action}. Please try again.';
$string['invalidnonce'] = 'invalid nonce';
$string['filesencmap'] = 'filesencmap not found in session';
$string['filesencmaprepo'] = 'delete repository mismatch';
$string['filestodeleteidx'] = 'filestodelete index out of range';
$string['deleteerror'] = '{$a} error encountered while deleting. File has not been deleted';
$string['repositorymodified'] = 'repository mismatch';

// Output header
$string['errornotification'] = 'There are some errros in uploaded files';

// Success
$string['uploadfilescount'] = '{$a->count} files uploaded under {$a->path}';
$string['uploadsuccess'] = '{$a} uploaded successfully';
$string['deletesuccess'] = '{$a} deleted successfully';
