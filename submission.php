<?php
/* @file
 * File upload facility into server. Should be secure!
 */

if ($_SERVER['DOCUMENT_ROOT'] == '/srv/http') {
    $_SERVER['DOCUMENT_ROOT'] = '/srv/http/home/technoco/public_html';
}

define( 'ROOT', dirname($_SERVER['DOCUMENT_ROOT']) . '/' );
define( 'ROOT_PUBLIC_HTTP',  $_SERVER['DOCUMENT_ROOT'] . '/' );
define( 'PARTY_DATA',  ROOT . 'participant/' );
define( 'SUBEVENT',  'National Seminar' );
define( 'WEB',  'http://technocornerugm.com/' );

require(ROOT_PUBLIC_HTTP . 'lib/phpmailer/PHPMailerAutoload.php');
require_once(ROOT_PUBLIC_HTTP . 'lib/google.recaptcha/recaptchalib.php');

$ajax_response = array(
    'subevent' => array("ns", "Seminar Nasional"),
    'captcha' => 0,
    'uinfo' => 0,
    'paycheck' => 0,
    'card' => 0,
    'regform' => 0,
    'mailed' => 0,
    'success' => 0,
    'error' => 'None'
);

$user = null;

class UserInfo {
    static $ERROR_FILE_TYPE = 120;
    static $ERROR_FILE_SIZE = 121;

    // TODO: Change into serialized array soon, for compatibility to v5.4
    static $SUBEVENT_NS = array("ns", "Seminar Nasional");
    static $SUBEVENT_SDC = array("sdc", "Software Development Competition");
    static $SUBEVENT_LF = array("lf", "Line Follower");
    static $SUBEVENT_EEC = array("eec", "Electrical Engineering Competition");
    static $SUBEVENT_EXPO = array("expo", "Technocorner Expo");

    static $FILE_PAYCHECK = "paycheck-uploader";
    static $FILE_CARD = "card-uploader";
    static $FILE_REGFORM = "formulir-uploader";

    static $SUCCESS_PAYCHECK = 0b001;
    static $SUCCESS_CARD = 0b010;
    static $SUCCESS_REGFORM = 0b100;

    var $regid;    // Unique id
    var $name;     // Sign name, usual name

    // Sign up data, mostly for $SUBEVENT_NS
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
        global $ajax_response;

        $this->regid = UserInfo::generateRegistrationId($name);
        $this->name = $name;
        $this->subevent = $subevent;
        $this->paycheck_uploaded = false;
        $this->folder = PARTY_DATA . "data/" . $subevent[0] . "/" . $this->regid . "/";

        $ajax_response['subevent'] = $this->subevent;

        $this->initMail();
    }

    static function buildFromRegId($regid, $subevent = array("ns", "Seminar Nasional")) {
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
        if ($file['size'] > $FILE_SIZE_LIMIT) {
            return $ERROR_FILE_SIZE;
        }

        // It's real size
        if (filesize($file['tmp_name']) > $FILE_SIZE_LIMIT) {
            return $ERROR_FILE_SIZE;
        }

        /* Check it's type, an image or docs? */
        // Get extension
        $fileext = explode('.', $file['name']);
        // Filter dangerous php script
        if (in_array("php", $fileext)) {
            return $ERROR_FILE_TYPE;
        }

        // Binary check? Later..
    }

    /*
     * Convert this obj field into csv (comma-separated value)
     */
    function toCsv() {
        global $ajax_response;

        $str = $this->regid . '; '
             . 'name: ' . $this->name . '; '
             . 'email: ' . $this->email . '; '
             . 'addr: ' . $this->address . '; '
             . 'phone: ' . $this->phone . '; '
             . 'dept: ' . $this->department . '; '
             . 'bukti: ' . var_export($this->paycheck_uploaded, true) . '; '
             . 'formulir: ' . var_export($this->regform_uploaded, true) . '; '
             . 'card: ' . var_export($this->card_uploaded, true) . PHP_EOL;

        $global_folder = PARTY_DATA . "data/" . $this->subevent[0] . "/";

        if (!(file_exists($global_folder) or mkdir($global_folder))) {
            $ajax_response['error'] = 'Failed to create folder: ' . $global_folder;
        }

        file_put_contents($global_folder . 'summary.csv', $str, FILE_APPEND);
    }

    /*
     * Serialize (write) UserInfo into file.
     */
    function saveUserInfo() {
        global $ajax_response;

        $user_file = $this->folder . "user.json";
        // Get JSON string format
        $jstr = json_encode($this, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        // Is the folder not yet exist?
        if (!file_exists($this->folder)) {
            if (!mkdir($this->folder, 0771, true)) {
                $ajax_response['error'] = 'Failed to create folder: ' . $this->folder;
                return false;
            }
        }

        // Write it to file
        if (!file_put_contents($user_file, $jstr)) {
            $ajax_response['error'] = 'Failed to put userinfo: ' . $user_file;
            return false;
        }

        $ajax_response['uinfo'] = 1;
    }

    /*
     * Move temporary file (after upload) into proper folder
     * @param $file one of $_FILES array e.g. $_FILES['new_upload']
     * @param $shortname name to put as new filename
     * @param $unexist_callback function name to call when file upload
     *                          fail or produce error
     */
    function saveUploadedFile($file, $shortname, $unexist_callback) {
        global $ajax_response;
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

            // Dir not exist
            if (!file_exists(dirname($file_path))) {
                // Create new dir when not exist
                mkdir(dirname($file_path), 0751, true);
            }

            // Delete file when already exist
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Move uploaded file from temporary storage
            if (!move_uploaded_file ($file['tmp_name'], $file_path)) {
                $ajax_response['error'] = 'Failed to move file: ' . $file_path;
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
        global $ajax_response;
        $shortname = "paycheck";
        $file = $_FILES[UserInfo::$FILE_PAYCHECK];
        $unexist_callback = null;

        // Mark paycheck uploaded
        $this->paycheck_uploaded = $this->saveUploadedFile($file, $shortname, $unexist_callback);
        $ajax_response[$shortname] = $this->paycheck_uploaded;
    }

    /*
     * Catch uploaded KTM / Kartu Pelajar
     */
    function saveCard() {
        global $ajax_response;
        $shortname = "card";
        $file = $_FILES[UserInfo::$FILE_CARD];
        $unexist_callback = null;

        $this->card_uploaded = $this->saveUploadedFile($file, $shortname, $unexist_callback);
        $ajax_response[$shortname] = $this->card_uploaded;
    }

    /*
     * Catch uploaded Registration form
     */
    function saveRegForm() {
        global $ajax_response;
        $shortname = "regform";
        $file = $_FILES[UserInfo::$FILE_REGFORM];
        $unexist_callback = null;

        $this->regform_uploaded = $this->saveUploadedFile($file, $shortname, $unexist_callback);
        $ajax_response[$shortname] = $this->regform_uploaded;
    }

    /*
     * Get a list of successfully uploaded files
     */
    function enumerateCurrentUpload() {
        global $ajax_response;
        $ret = 0;

        // Check uploaded progress
        if ($this->regform_uploaded) {
            $ret |= UserInfo::$SUCCESS_REGFORM;
            $ajax_response['regform'] = 1;
        }

        if ($this->card_uploaded) {
            $ret |= UserInfo::$SUCCESS_CARD;
            $ajax_response['card'] = 1;
        }

        if ($this->paycheck_uploaded) {
            $ret |= UserInfo::$SUCCESS_PAYCHECK;
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
    function mailInbox($subject, $msg_body) {
        global $ajax_response;

        //Set who the message is to be sent from
        $this->mail->setFrom('registration@technocornerugm.com', 'Technocorner 2015');
        //Set an alternative reply-to address
        $this->mail->addReplyTo('no-reply@technocornerugm.com', 'Technocorner 2015');
        //Set who the message is to be sent to
        $this->mail->addAddress( $this->email,  $this->name);
        //Set the subject line
        $this->mail->Subject = $subject;

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
    global $user;

    $user = new UserInfo($_POST['name'], UserInfo::$SUBEVENT_NS);
    $user->email = $_POST['email'];
    $user->phone = $_POST['phone'];
    $user->address = $_POST['address'];
    $user->department = $_POST['department'];

    $user->saveUserInfo();

    if ($_POST['upload_chk'] == "upload_y") {
        $user->savePaycheck();

        if ($user->checkUploadRequirement(UserInfo::$SUCCESS_PAYCHECK))
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

        $subject = 'Technocorner 2015 ' . $user->subevent[1] . ': Notification';
        $user->mailInbox($subject, $msg_body);
    }

    $user->toCsv();
}

function nsVerifyUser() {
    global $user;
    $user = UserInfo::buildFromRegId($_POST['regid']);
    $user->savePaycheck();

    $user->checkUploadRequirement(UserInfo::$SUCCESS_PAYCHECK);
}

/*
 * The followings are functions to be called correspond to the form
 * being filled.
 */

function formNatSeminar() {
    switch($_POST['formId']) {
        case 'ns-verify': {
            nsVerifyUser();
            break;
        }
        case 'ns-registration': {
            nsRegisterUser();
            break;
        }
    }
}

function formEEC() {
    global $ajax_response;
    global $user;

    $user = new UserInfo($_POST['name'], UserInfo::$SUBEVENT_EEC);
    $user->saveUserInfo();
    $user->saveCard();
    $user->saveRegForm();
    $user->savePaycheck();
    $user->toCsv();

    $ajax_response['success'] = $user->checkUploadRequirement(
        UserInfo::$SUCCESS_PAYCHECK
        | UserInfo::$SUCCESS_REGFORM
        | UserInfo::$SUCCESS_CARD
    );
}

/*
 * Function earliest called
 */
function main() {
    global $ajax_response;

    if (!$_POST['captcha']) {
        // the CAPTCHA was entered incorrectly
        $ajax_response['captcha'] = 0;
        $ajax_response['error'] = 'Captcha auth failed';
        return null;
    }

    $ajax_response['captcha'] = 1;

        // Get initial name of event
    $eventform = explode('-', $_POST['formId'])[0];
    $eventform = str_replace('#', '', $eventform);

    switch($eventform) {
        case 'ns': {
            formNatSeminar();
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
?>

<?php
/*
 * Interface
 */

function event($ev) {
    global $user;

    if ($user->subevent[0] == $ev) {
        return true;
    }

    return false;
}

function ns_check_email() {
    global $user;
    
    if (event("ns")) {
?>
  <p>Email telah di kirim ke alamat <i><? echo $user->email; ?></i>, jika belum menerima, mohon catat nomor registrasi di atas.</p>
<?
    }
}

?>
<html>
  <head>
    <style>
    html, body {
        font-size: 12pt;
        padding: 0;
        margin: 0;
    }

    .container {
        text-align: center;
        margin: 25vh auto;
        background-color: #343434;
        padding: 1em 0;
        color: #f0f0f0;
    }

    .inline-info {
        font-family: Monospace;
        font-weight: 700;
    }
    </style>
  </head>
  <body>
    <div></div>
    <div class="container">
      <h1>Terima Kasih</h1>
      <p>Anda telah berhasil mendaftar<br/><span class="inline-info"><? echo $user->subevent[1] ?>, Technocorner 2015</span></p>
      <? ns_check_email() ?>
      <p>Nomor Registrasi Anda<br/><span class="inline-info"><? echo $user->regid; ?></span></p>
      <p>Mohon untuk mencatat nomor registrasi di atas.</p>
      <button onclick="document.location = '<? echo $user->subevent[0] ?>.html'">Kembali</button>
    </div>
  </body>
</html>
