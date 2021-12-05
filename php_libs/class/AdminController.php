<?php
/**
 * Description of AdminController
 *
 * @author AtomYah
 */
class AdminController extends BaseController {
    //----------------------------------------------------
    // For Admin function
    //----------------------------------------------------
    public function run() {
        // start session and authenticaton
        $this->auth = new Auth();
        $this->auth->set_authname(_ADMIN_AUTHINFO);
        $this->auth->set_sessname(_ADMIN_SESSNAME);
        $this->auth->start();

        if (!$this->auth->check() && $this->type != 'authenticate'){
            // not authorized
            $this->type = 'login';
        }
        
        // set the flag for admin
        $this->is_admin = true;

        // utilize MemberController for member records' controlling
        $MemberController = new MemberController($this->is_admin);
        
        switch ($this->type) {
            case "login":
                $this->screen_login();
                break;
            case "logout":
                $this->auth->logout();
                $this->screen_login();
                break;
            case "modify":
                $MemberController->screen_modify($this->auth);
                break;
            case "delete":
                $MemberController->screen_delete();
                break;
            case "list":
                $this->screen_list();
                break;
            case "regist":
                $MemberController->screen_regist($this->auth);
                break;
            case "authenticate":
                $this->do_authenticate();
                break;
            default:
                $this->screen_top();
        }
    }

    //----------------------------------------------------
    // Login page
    //----------------------------------------------------
    private function screen_login(){
        $this->form->addElement('text', 'username', 'UserName', ['size' => 15, 'maxlength' => 50]);
        $this->form->addElement('password', 'password', 'Password', ['size' => 15, 'maxlength' => 50]);
        $this->form->addElement('submit','submit','Login');
        $this->next_type = 'authenticate';
        $this->title = 'Login Page';
        $this->file = "admin_login.tpl";
        $this->view_display();
    }
    
    public function do_authenticate(){
        // Access admin database and authentication
        $AdminModel = new AdminModel();
        $userdata = $AdminModel->get_authinfo($_POST['username']);
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
    private function screen_top(){
        unset($_SESSION['search_key']);
        unset($_SESSION[_MEMBER_AUTHINFO]);
        unset($_SESSION['pageID']);
        $this->title = 'Admin - Top Page';
        $this->file = 'admin_top.tpl';
        $this->view_display();
    }    
    
    //----------------------------------------------------
    // Member list page
    //----------------------------------------------------
    private function screen_list(){
        $disp_search_key = "";
        $sql_search_key = "";
        // Dispose session variables
        unset($_SESSION[_MEMBER_AUTHINFO]);
        if(isset($_POST['search_key']) && $_POST['search_key'] != ""){
            unset($_SESSION['pageID']);
            $_SESSION['search_key'] = $_POST['search_key']; 
            $disp_search_key = htmlspecialchars($_POST['search_key'], ENT_QUOTES); 
            $sql_search_key = $_POST['search_key']; 
        }else{
            if(isset($_POST['submit']) && $_POST['submit'] == "Search"){
                unset($_SESSION['search_key']); 
                unset($_SESSION['pageID']);
            }else{
                if(isset($_SESSION['search_key'])){
                    $disp_search_key = htmlspecialchars($_SESSION['search_key'], ENT_QUOTES); 
                    $sql_search_key = $_SESSION['search_key']; 
                }
            }
        }
        // Operate member database
        $MemberModel = new MemberModel();
        list($data, $count) = $MemberModel->get_member_list($sql_search_key);
        list($data_perPage, $links) = $this->make_page_link($data);
        $this->view->assign('count', $count);
        $this->view->assign('data_perPage', $data_perPage);
        $this->view->assign('search_key', $disp_search_key);
        $this->view->assign('links', $links['all']);
        $this->title = 'Admin - Member List Page';
        $this->file = 'admin_list.tpl';
        $this->view_display();
    }    
}

?>
