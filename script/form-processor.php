<?php
/* @file
 * File upload facility into server. Should be secure!
 */

define( 'ROOTDIR',  $_SERVER['DOCUMENT_ROOT'] . '/tc/' );
define( 'PARTY_DATA',  $_SERVER['DOCUMENT_ROOT'] . '/participant/' );
define( 'SUBEVENT',  'National Seminar' );
define( 'WEB',  'http://kmteti.ft.ugm.ac.id/technocorner/' );

require(ROOTDIR . 'lib/phpmailer/PHPMailerAutoload.php');
require_once(ROOTDIR . 'lib/google.recaptcha/recaptchalib.php');

$ajax_response = array(
    'subevent' => array("semnas", "Seminar Nasional"),
    'captcha' => 0,
    'paycheck' => 0,
    'card' => 0,
    'regform' => 0,
    'mailed' => 0,
    'success' => 0
);

class UserInfo {
    const ERROR_FILE_TYPE = 120;
    const ERROR_FILE_SIZE = 121;

    const SUBEVENT_NS = array("semnas", "Seminar Nasional");
    const SUBEVENT_SDC = array("sdc", "Software Development Competition");
    const SUBEVENT_LF = array("lf", "Line Follower");
    const SUBEVENT_EEC = array("eec", "Electrical Engineering Competition");
    const SUBEVENT_EXPO = array("expo", "Technocorner Expo");

    const FILE_PAYCHECK = "paycheck-uploader";
    const FILE_CARD = "card-uploader";
    const FILE_FORMULIR = "formulir-uploader";

    const SUCCESS_PAYCHECK = 0b001;
    const SUCCESS_CARD = 0b010;
    const SUCCESS_REGFORM = 0b100;

    var $regid;    // Unique id
    var $name;     // Sign name, usual name

    // Sign up data, mostly for SUBEVENT_NS
    var $email;
    var $address;
    var $phone;
    var $department; // Where the user belong to? JTETi UGM?

    // Other metadata
    var $paycheck_uploaded; // Has current user upload a paymentcheck?
    var $regform_uploaded;  // Has current user upload a registration form?
    var $card_uploaded;     // Has current user upload a card?

    var $subevent;          // What subevent this user assigned
    var $folder;            // Where this userinfo stored

    var $mail;

    function __construct($name, $subevent) {
        $this->regid = UserInfo::generateRegistrationId($name);
        $this->name = $name;
        $this->subevent = $subevent;
        $this->paycheck_uploaded = false;
        $this->folder = PARTY_DATA . "data/" . $subevent[0] . "/" . $this->regid . "/";

        $ajax_response['subevent'] = $subevent;

        $this->initMail();
    }

    static function buildFromRegId($regid, $subevent = UserInfo::SUBEVENT_NS) {
        $user = new UserInfo("", $subevent);
        $user->regid = $regid;
        $user->folder = PARTY_DATA . "data/" . $subevent[0] . "/" .  $user->regid . "/";

        $ajax_response['subevent'] = $subevent;

        // Read UserInfo from JSON file
        $jstr = file_get_contents($user->folder . "user.json");
        $temp_user = json_decode($jstr, JSON_FORCE_OBJECT);

        $user->import($temp_user);
        return $user;
    }

    /*
     * Generate user registration id based on time() and name hash sha1()
     */
    static function generateRegistrationId($name) {
        return sha1($name);
    }

    /*
     * Security core function for uploaded file.
     * Detect any flaws, return validation code.
     */
    private function checkUploadedFile($file) {
        /* Check it's size: */
        $FILE_SIZE_LIMIT = 3000000; // byte

        // It's recorded size
        if ($file['size'] > FILE_SIZE_LIMIT) {
            return ERROR_FILE_SIZE;
        }

        // It's real size
        if (filesize($file['size']) > FILE_SIZE_LIMIT) {
            return ERROR_FILE_SIZE;
        }

        /* Check it's type, an image or docs? */
        // Get extension
        $fileext = explode('.', $file['name']);
        // Filter dangerous php script
        if (in_array("php", $fileext)) {
            return ERROR_FILE_TYPE;
        }

        // Binary check? Later..
    }

    /*
     * Convert this obj field into csv (comma-separated value)
     */
    function toCsv() {
        $str = $this->regid . ', '
             . $this->name . ', '
             . $this->email . ', '
             . $this->address . ', '
             . $this->phone . ', '
             . $this->department . ', '
             . 'bukti, ' . $this->paycheck_uploaded . ', '
             . 'formulir, ' . $this->regform_uploaded . ', '
             . 'card' . $this->card_uploaded;

        $global_folder = PARTY_DATA . "data/" . $subevent[0] . "/";

        file_put_contents($global_folder . 'summary.csv', $str, FILE_APPEND);
    }

    /*
     * Serialize (write) UserInfo into file.
     */
    function saveUserInfo() {
        $user_file = $this->folder . "user.json";
        // Get JSON string format
        $jstr = json_encode($this, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        // Is the folder not yet exist?
        if (!file_exists($this->folder)) {
            if (!mkdir($this->folder, 0770, true)) {
                return false;
            } else {
                // TODO: change this!
                chgrp($this->folder, "abdillah");
                chgrp($this->folder, "abdillah");
            }
        }

        // Write it to file
        if (!file_put_contents($user_file, $jstr)) {
            return false;
        }
    }

    /*
     * Move temporary file (after upload) into proper folder
     * @param $file one of $_FILES array e.g. $_FILES['new_upload']
     * @param $shortname name to put as new filename
     * @param $unexist_callback function name to call when file upload
     *                          fail or produce error
     */
    function saveUploadedFile($file, $shortname, $unexist_callback) {
        // Is the file already uploaded? With no error?
        if (file_exists($file['tmp_name'])
            && is_uploaded_file($file['tmp_name'])
            && $file['error'] == 0)
        {
            // Get extension
            $fileext = explode('.', $file['name']);
            $fileext = $fileext[count($fileext) - 1];

            // Check file uploaded
            $this->checkUploadedFile($file);

            $file_path = $this->folder . $shortname . "." . $fileext;

            // Delete file when already exist
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Move uploaded file from temporary storage
            if (!move_uploaded_file ($file['tmp_name'], $file_path)) {
                return false;
            }

            return true;
        } else {
            // Call the callback
            if (is_callable($unexist_callback)) {
                call_user_func($unexist_callback);
            }
            return false;
        }
    }

    /*
     * Catch uploaded paycheck
     */
    function savePaycheck() {
        $shortname = "paycheck";
        $file = $_FILES[UserInfo::FILE_PAYCHECK];
        $unexist_callback = null;

        // Mark paycheck uploaded
        $this->paycheck_uploaded = $this->saveUploadedFile($file, $shortname, $unexist_callback);
    }

    /*
     * Catch uploaded KTM / Kartu Pelajar
     */
    function saveCard() {
        $shortname = "card";
        $file = $_FILES[UserInfo::FILE_CARD];
        $unexist_callback = null;

        $this->card_uploaded = $this->saveUploadedFile($file, $shortname, $unexist_callback);
    }

    /*
     * Catch uploaded Registration form
     */
    function saveRegForm() {
        $shortname = "formulir";
        $file = $_FILES[UserInfo::FILE_REGFORM];
        $unexist_callback = null;

        $this->regform_uploaded = $this->saveUploadedFile($file, $shortname, $unexist_callback);
    }

    /*
     * Get a list of successfully uploaded files
     */
    function enumerateCurrentUpload() {
        global $ajax_response;
        $ret = 0;

        // Check uploaded progress
        if ($this->regform_uploaded) {
            $ret |= UserInfo::SUCCESS_REGFORM;
            $ajax_response['regform'] = 1;
        }

        if ($this->card_uploaded) {
            $ret |= UserInfo::SUCCESS_CARD;
            $ajax_response['card'] = 1;
        }

        if ($this->paycheck_uploaded) {
            $ret |= UserInfo::SUCCESS_PAYCHECK;
            $ajax_response['paycheck'] = 1;
        }

        return $ret;
    }

    /*
     * Match between successfully uploaded files and required files
     * @return true when required files uploaded
     *         false when requred files incomplete
     */
    function checkUploadRequirement($requirement_flag) {
        // TODO: Give the process description
        $current_flag = $this->enumerateCurrentUpload();

        if (!(($current_flag & $requirement_flag) ^ $requirement_flag)) {
            return true;
        }

        return false;
    }

    /*
     * Import field from an array, useful in restoring object from JSON
     */
    public function import($array) {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    /*
     * Initialize user mail facility
     */
    function initMail() {
        //Create a new PHPMailer instance
        $this->mail = new PHPMailer;
        //Tell PHPMailer to use SMTP
        $this->mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $this->mail->SMTPDebug = 2;
        //Ask for HTML-friendly debug output
        $this->mail->Debugoutput = 'html';
        //Set the hostname of the mail server
        $this->mail->Host = 'smtp.gmail.com';
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->mail->Port = 587;
        //Set the encryption system to use - ssl (deprecated) or tls
        $this->mail->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $this->mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $this->mail->Username = "abdillah96.bu@gmail.com";
        //Password to use for SMTP authentication
        $this->mail->Password = "namakufa'iz";
    }

     /*
      * Send mail notification.
      */
    function mailInbox($msg_body) {
        global $ajax_response;

        //Set who the message is to be sent from
        $this->mail->setFrom('from@example.com', 'Technocorner 2015');
        //Set an alternative reply-to address
        $this->mail->addReplyTo('replyto@example.com', 'First Last');
        //Set who the message is to be sent to
        $this->mail->addAddress( $this->email,  $this->name);
        //Set the subject line
        $this->mail->Subject = 'Technocorner 2015 ' . $this->subevent[1] . ': Notification';

        // Send HTML Markup message
        $this->mail->msgHTML($msg_body);

        //Replace the plain text body with one created manually
        // $this->mail->AltBody = 'This is a plain-text message body';
        //Attach an image file
        // $this->mail->addAttachment('images/phpmailer_mini.png');
        //send the message, check for errors
        if (!$this->mail->send()) {
            $ajax_response['mail'] = 1;
        } else {
            $ajax_response['mail'] = 0;
        }
    }
}

function nsRegisterUser() {
    global $ajax_response;

    $user = new UserInfo($_POST['name'], UserInfo::SUBEVENT_NS);
    $user->email = $_POST['email'];
    $user->phone = $_POST['phone'];
    $user->address = $_POST['address'];
    $user->department = $_POST['department'];

    $user->saveUserInfo();
    $user->toCsv();

    if ($_POST['upload_chk'] == "upload_y") {
        $user->savePaycheck();

        if ($user->checkUploadRequirement(UserInfo::SUCCESS_PAYCHECK))
        {
            $ajax_response['paycheck'] = 1;
        } else {
            $ajax_response['paycheck'] = 0;
        }
    } else if ($_POST['upload_chk'] == "upload_n") {
        $msg_body    = "Hai ". $user->name . ",<br/>"
                     . "Anda telah terdaftar sebagai peserta " . $user->subevent[1] . " Technocorner 2015. "
                     . "Dimohon melakukan konfirmasi dengan <i>upload</i> bukti pembayaran di " . WEB . $user->subevent[0] . ".html" . "#verifikasi."
                     . "<br/>"
                     . "Mohon cantumkan nomor registrasi anda, yakni <b>" . $user->regid . "</b> pada saat upload bukti pembayaran."
                     . "<br/><br/><br/>"
                     . "Terima Kasih."
                     . "<br/>"
                     . "Salam Teknologi,"
                     . "<br/><br/>"
                     . "<b>Technocorner 2015</b>"
                     . "<hr/>"
                     . "No Reply : Email ini mohon untuk tidak dibalas.";

        $user->mailInbox($msg_body);
    }
}

function nsVerifyUser() {
    $user = UserInfo::buildFromRegId($_POST['regid']);
    $user->savePaycheck();

    $user->checkUploadRequirement(UserInfo::SUCCESS_PAYCHECK);
}

/*
 * The followings are functions to be called correspond to the form
 * being filled.
 */

function formNatSeminar() {
    switch($_POST['formId']) {
        case '#ns-verify': {
                nsVerifyUser();
                break;
        }
        case '#ns-registration': {
                nsRegisterUser();
                break;
        }
    }
}

function formSDC() {
    global $ajax_response;

    $user = new UserInfo($_POST['name'], UserInfo::SUBEVENT_SDC);
    $user->saveUserInfo();
    $user->toCsv();
    $user->saveCard();
    $user->saveRegForm();
    $user->savePaycheck();

    $ajax_response['success'] = $user->checkUploadRequirement(
        UserInfo::SUCCESS_PAYCHECK
        | UserInfo::SUCCESS_REGFORM
        | UserInfo::SUCCESS_CARD
    );
}

function formEEC() {
    global $ajax_response;

    $user = new UserInfo($_POST['name'], UserInfo::SUBEVENT_EEC);
    $user->saveUserInfo();
    $user->toCsv();
    $user->saveCard();
    $user->saveRegForm();
    $user->savePaycheck();

    $ajax_response['success'] = $user->checkUploadRequirement(
        UserInfo::SUCCESS_PAYCHECK
        | UserInfo::SUCCESS_REGFORM
        | UserInfo::SUCCESS_CARD
    );
}

function formLF() {
    global $ajax_response;

    $user = new UserInfo($_POST['name'], UserInfo::SUBEVENT_LF);
    $user->saveUserInfo();
    $user->toCsv();
    $user->saveCard();
    $user->saveRegForm();
    $user->savePaycheck();

    $ajax_response['success'] = $user->checkUploadRequirement(
        UserInfo::SUCCESS_PAYCHECK
        | UserInfo::SUCCESS_REGFORM
        | UserInfo::SUCCESS_CARD
    );
}

/*
 * Function earliest called
 */
function main() {
    global $ajax_response;

    // Test for recaptcha
    $privatekey = "6Lf1WwATAAAAADa0MEgI5iXEfvZ7HByT6wXikKCt";
    // $privatekey = "6LcP6AATAAAAAI-5S-W2XGSBPYsQKFmawam88Gnj";
    $resp = null;

    $reCaptcha = new ReCaptcha($privatekey);
    $resp = $reCaptcha->verifyResponse($_SERVER["REMOTE_ADDR"],
                                       $_POST["g-recaptcha-response"]);

    if (!$resp->success) {
        // the CAPTCHA was entered incorrectly
        $ajax_response['captcha'] = 0;
        return null;
    }
    $ajax_response['captcha'] = 1;

    // Get initial name of event
    $eventform = explode('-', $_POST['formId'])[0];
    $eventform = str_replace('#', '', $eventform);

    switch($eventform) {
        case 'sdc': {
                formSDC();
                break;
        }
        case 'ns': {
                formNatSeminar();
                break;
        }
        case 'lf': {
                formLF();
                break;
        }
        case 'eec': {
                formEEC();
                break;
        }
    }
}

// Solo function call!
main();

// Punch back the response
echo  json_encode($ajax_response);
?>
