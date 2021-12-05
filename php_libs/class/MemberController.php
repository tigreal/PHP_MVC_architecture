<?php
/**
 * Description of MemberController
 *
 * @author AtomYah
 */
class MemberController extends BaseController {
    //----------------------------------------------------
    // Functions for members
    //----------------------------------------------------
    public function run() {
        // It is used for session start and authentication.
        $this->auth = new Auth();
        $this->auth->set_authname(_MEMBER_AUTHINFO);
        $this->auth->set_sessname(_MEMBER_SESSNAME);
        $this->auth->start();
        
        if ($this->auth->check()){
            // authorized
            $this->menu_member();
        }else{
            // not authorized
            $this->menu_guest();
        }
    }
    
    //----------------------------------------------------
    // Branch flag for members
    //----------------------------------------------------
    public function menu_member() {
        switch ($this->type) {
            case "logout":
                $this->auth->logout();
                $this->screen_login();
                break;
            case "modify":
                $this->screen_modify();
                break;
            case "delete":
                $this->screen_delete();
                break;
            default:
                $this->screen_top();
        }
    }
    
    //----------------------------------------------------
    // Branch flag for guests
    //----------------------------------------------------
    public function menu_guest() {
        switch ($this->type) {
            case "regist":
                $this->screen_regist();
                break;
            case "authenticate":
                $this->do_authenticate();
                break;
            default:
                $this->screen_login();
        }
    }
    //----------------------------------------------------
    // Display login page
    //----------------------------------------------------
    public function screen_login(){
        $this->form->addElement('text', 'username', 'UserName', ['size' => 15, 'maxlength' => 50]);
        $this->form->addElement('password', 'password', 'Password', ['size' => 15, 'maxlength' => 50]);
        $this->form->addElement('submit','submit','Login');
        $this->title = 'Login Page';
        $this->next_type = 'authenticate';
        $this->file = "login.tpl";
        $this->view_display();
    }
    
    public function do_authenticate(){
        // Operate member records' database
        $MemberModel = new MemberModel();
        $userdata = $MemberModel->get_authinfo($_POST['username']);
        if(!empty($userdata['password']) && $this->auth->check_password($_POST['password'], $userdata['password'])){
            $this->auth->auth_ok($userdata);
            $this->screen_top();
        } else {
            $this->auth_error_mess = $this->auth->auth_no();
            $this->screen_login();
        }
    }

    //----------------------------------------------------
    // Top page
    //----------------------------------------------------
    public function screen_top(){
        $this->view->assign('last_name',  $_SESSION[_MEMBER_AUTHINFO]['last_name']);
        $this->view->assign('first_name', $_SESSION[_MEMBER_AUTHINFO]['first_name']);
        $this->title = 'Member - Top Page';
        $this->file = 'member_top.tpl';
        $this->view_display();
    }

    //----------------------------------------------------
    // Member Registration
    //----------------------------------------------------
    public function screen_regist($adminauth = ""){
        $btn = ""; $btn2 = "";
        $this->file = "memberinfo_form.tpl"; // default

        // Setting default value for the form birthday
        $date_defaults = [
            'Y' => '1980',
            'm' => '1',
            'd' => '1',        
        ];

        $this->form->setDefaults(['birthday' => $date_defaults]);
        $this->make_form_controle();

        // Validate form
        if (!$this->form->validate()){
            $this->action = "form";
        }

        if($this->action == "form"){
            $this->title  = 'New Registration';
            $this->next_type    = 'regist';
            $this->next_action  = 'confirm';
            $btn = 'Confirm';
        }else if($this->action == "confirm"){
            $this->title  = 'Confirmation';
            $this->next_type    = 'regist';
            $this->next_action  = 'complete';
            $this->form->freeze();
            $btn = 'Register';
            $btn2= 'Back';
        }else if($this->action == "complete" && isset($_POST['submit2']) && $_POST['submit2'] == 'Back'){
            $this->title  = 'New Registration';
            $this->next_type    = 'regist';
            $this->next_action  = 'confirm';
            $btn = 'Confirm';
        }else if($this->action == "complete" && isset($_POST['submit']) && $_POST['submit'] == 'Register'){
            // Operate tempmember records' database
            $TempmemberModel = new TempmemberModel();
            // Operate members' database
            $MemberModel = new MemberModel();
            $userdata = $this->form->getSubmitValues();
            if( $MemberModel->check_username($userdata) || $TempmemberModel->check_username($userdata) ){
                $this->title = 'New Registration';
                $this->message = "The mail address has already been registered.";
                $this->next_type    = 'regist';
                $this->next_action  = 'confirm';
                $btn = 'Confirm';
            }else{
                // Used when using from admin
                if($this->is_admin && is_object($adminauth)){
                    $userdata['password'] = $adminauth->get_hashed_password($userdata['password']);
                }else{
                    $userdata['password'] = $this->auth->get_hashed_password($userdata['password']);
                }
                $userdata['birthday'] = sprintf("%04d%02d%02d",
                                                $userdata['birthday']['Y'],
                                                $userdata['birthday']['m'],
                                                $userdata['birthday']['d']);
                if($this->is_admin){
                    $MemberModel->regist_member($userdata);
                    $this->title   = 'Registration Completion';
                    $this->message = "Registration completed";
                }else{
                    $userdata['link_pass'] = hash('sha256', uniqid(rand(),1));
                    $TempmemberModel->regist_tempmember($userdata);
                    $this->mail_to_tempmember($userdata);
                    $this->title    = 'Mail Transmission Complete';
                    $this->message  = "We sent a confirmation e-mail to the registered e-mail address.<BR>";
                    $this->message .= "Please access the URL described in the email body and complete the registration.<BR>";
                }
                $this->file = "message.tpl";
            }
        }

        $this->form->addElement('submit','submit',  $btn );
        $this->form->addElement('submit','submit2', $btn2);
        $this->form->addElement('reset', 'reset',   'Cancel');
        $this->view_display();
    }

    //----------------------------------------------------
    // Member Modify
    //----------------------------------------------------
    public function screen_modify($adminauth = ""){
        $btn          = "";
        $btn2         = "";
        $this->file = "memberinfo_form.tpl";

        // Operate members' and tempmembers' database
        $MemberModel = new MemberModel();
        $TempmemberModel = new TempmemberModel();
        if($this->is_admin && $this->action == "form"){
            $_SESSION[_MEMBER_AUTHINFO] = $MemberModel->get_member_data_id($_GET['id']);
        }
        // Set default of forms pickin up from session _MEMBER_AUTHINFO
        $date_defaults = [
            'Y' => substr($_SESSION[_MEMBER_AUTHINFO]['birthday'], 0, 4),
            'm' => substr($_SESSION[_MEMBER_AUTHINFO]['birthday'], 4, 2),
            'd' => substr($_SESSION[_MEMBER_AUTHINFO]['birthday'], 6, 2),        
        ];

        $this->form->setDefaults(
            [
                'username'      => $_SESSION[_MEMBER_AUTHINFO]['username'],
                'last_name'     => $_SESSION[_MEMBER_AUTHINFO]['last_name'],
                'first_name'    => $_SESSION[_MEMBER_AUTHINFO]['first_name'],
                'states'        => $_SESSION[_MEMBER_AUTHINFO]['states'],
                'birthday'      => $date_defaults,
            ]
        );

        $this->make_form_controle();

        // Form validation
        if (!$this->form->validate()){
            $this->action = "form";
        }

        if($this->action == "form"){
            $this->title  = 'UPDATE PAGE';
            $this->next_type    = 'modify';
            $this->next_action  = 'confirm';
            $btn = 'Confirm';
        }else if($this->action == "confirm"){
            $this->title  = 'Confirmation Page';
            $this->next_type    = 'modify';
            $this->next_action  = 'complete';
            $this->form->freeze();
            $btn = 'Update';
            $btn2= 'Back';
        }else if($this->action == "complete" && isset($_POST['submit2']) && $_POST['submit2'] == 'Back'){
            $this->title  = 'UPDATE PAGE';
            $this->next_type    = 'modify';
            $this->next_action  = 'confirm';
            $btn = 'Confirm';
        }else if($this->action == "complete" && isset($_POST['submit']) && $_POST['submit'] == 'Update'){
           $userdata = $this->form->getSubmitValues();
                $this->title = 'Update Completed';
                $userdata['id']       = $_SESSION[_MEMBER_AUTHINFO]['id'];
                // Used when using from admin
                if($this->is_admin && is_object($adminauth)){
                    $userdata['password'] = $adminauth->get_hashed_password($userdata['password']);
                }else{
                    $userdata['password'] = $this->auth->get_hashed_password($userdata['password']);
                }
                $userdata['birthday'] = sprintf("%04d%02d%02d",
                                $userdata['birthday']['Y'],
                                $userdata['birthday']['m'],
                                $userdata['birthday']['d']);
                $MemberModel->modify_member($userdata);
                $this->message = "Member information was modified";
                $this->file = "message.tpl";
                if($this->is_admin){
                    unset($_SESSION[_MEMBER_AUTHINFO]);
                }else{
                    $_SESSION[_MEMBER_AUTHINFO] = $MemberModel->get_member_data_id($_SESSION[_MEMBER_AUTHINFO]['id']);
                }
            }
        

        $this->form->addElement('submit','submit',  $btn );
        $this->form->addElement('submit','submit2', $btn2);
        $this->form->addElement('reset', 'reset',   'Cancel');
        $this->view_display();
    }


    //----------------------------------------------------
    // Delete member
    //----------------------------------------------------
    public function screen_delete(){
        // Operate members' database
        $MemberModel = new MemberModel();
        if($this->action == "confirm"){
            if($this->is_admin){
                $_SESSION[_MEMBER_AUTHINFO] = $MemberModel->get_member_data_id($_GET['id']);
                $this->message  = "Clicking [Delete] ";
                $this->message .= htmlspecialchars($_SESSION[_MEMBER_AUTHINFO]['last_name'], ENT_QUOTES) . ', ';
                $this->message .= htmlspecialchars($_SESSION[_MEMBER_AUTHINFO]['first_name'], ENT_QUOTES);
                $this->message .= " will be deleted.";
                $this->form->addElement('submit','submit', "Delete");
            }else{
                $this->message = "Clicking [Unsubscribe] will delete your all information and unsubscribe.";
                $this->form->addElement('submit','submit', "Unsubscribe");
            }
            $this->next_type  = 'delete';
            $this->next_action  = 'complete';
            $this->title = 'Really want to delete?';
            $this->file = 'delete_form.tpl';
        }else if($this->action == "complete"){
            $MemberModel->delete_member($_SESSION[_MEMBER_AUTHINFO]['id']);
            if($this->is_admin){
                    unset($_SESSION[_MEMBER_AUTHINFO]);
            }else{
                    $this->auth->logout();
            }
            $this->message = "Member was deleted.";
            $this->title = 'Delete Completion Page';
            $this->file = 'message.tpl';
        }
        $this->view_display();
    }
    
    
    
    //----------------------------------------------------
    // email function
    //----------------------------------------------------
    //
    // send email to temp members.
    //
    public function mail_to_tempmember($userdata){
        

        
        $to      = $userdata['username'];
        $subject = "Confirmation of your membership registration";
        $message =<<<EOM
    Mr/Ms.{$userdata['username']}

    Thank you for your registration.
    Complete your registration process by clicking the following URL.

    http://{$_SERVER['SERVER_NAME']}/tempmember.php?username={$userdata['username']}&link_pass={$userdata['link_pass']}

    Please delete the mail if you do not remember this email.


    --
    Sample Auth System

EOM;
        $add_header = "From: postmaster@localhost";
        
        //$add_header .= "From: xxxx@xxxxxxx\nCc: xxxx@xxxxxxx";

        mb_send_mail($to, $subject, $message, $add_header);

    }    
}
?>