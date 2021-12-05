<?php
/**
 * Description of BaseController
 *
 * @author AtomYah
 */
class BaseController {
    protected $type;
    protected $action;
    protected $next_type;
    protected $next_action;
    protected $file;
    protected $form;
    protected $renderer;
    protected $auth;
    protected $is_admin = false;
    protected $view;
    protected $title;
    protected $message;
    protected $auth_error_mess;
    protected $login_state;
    private   $debug_str;
    
    public function __construct($flag=false){
        $this->set_admin($flag);
        // Prepare for VIEW
        $this->view_initialize();
    }
    

    public function set_admin($flag){
        $this->is_admin = $flag;
    }
    
    private function view_initialize(){
        // Screen display class
        $this->view = new Smarty;
        // Smarty related directory settings
        $this->view->template_dir = _SMARTY_TEMPLATES_DIR;
        $this->view->compile_dir  = _SMARTY_TEMPLATES_C_DIR;
        $this->view->config_dir   = _SMARTY_CONFIG_DIR;
        $this->view->cache_dir    = _SMARTY_CACHE_DIR;

        // Input check class
        $this->form    = new HTML_QuickForm();
        // Classes for using HTML_QickForm and Smarty
        $this->renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->view);
        $this->form->accept($this->renderer);

        // Decide the behavior by request variable type and action.
        if(isset($_REQUEST['type'])){   $this->type   = $_REQUEST['type'];}
        if(isset($_REQUEST['action'])){ $this->action = $_REQUEST['action'];}

        // Common variables
        $this->view->assign('is_admin',   $this->is_admin );
        $this->view->assign('SCRIPT_NAME', _SCRIPT_NAME);
        $this->view->assign('add_pageID',  $this->add_pageID());

    }


    //----------------------------------------------------
    // Load forms and variables, incorporate them into templates and display them.
    //----------------------------------------------------
    protected function view_display(){
        // Display contents such as session variables
        $this->debug_display();

        // View login status
        $this->disp_login_state();
        
        $this->view->assign('title', $this->title);
        $this->view->assign('auth_error_mess', $this->auth_error_mess);
        $this->view->assign('message', $this->message);  
        $this->view->assign('disp_login_state', $this->login_state);
        $this->view->assign('type',    $this->next_type);
        $this->view->assign('action',  $this->next_action);
        $this->view->assign('debug_str', $this->debug_str);
        $this->form->accept($this->renderer);
        $this->view->assign('form', $this->renderer->toArray());
        $this->view->display($this->file);
       
    }
    
    //----------------------------------------------------
    // Display during login
    //----------------------------------------------------
    private function disp_login_state(){
        if(is_object($this->auth) && $this->auth->check()){
            $this->login_state = ($this->is_admin)? 'During admin login' : 'During member login';
        }
    }    
    
    
    //----------------------------------------------------
    // Setting of member information input items and input rules
    //----------------------------------------------------
    public function make_form_controle(){
        $StatesModel = new StatesModel;
        $states_array = $StatesModel->get_states_data();
        $options = [ 
            'format'    => 'Ymd',
            'minYear'   => 1950,
            'maxYear'   => date("Y"),
        ];
        if($this->type == 'modify'){
        $this->form->addElement('text',   'username',     'username', ['size' => 30,'readonly' => 'readonly']);
        }else{
        $this->form->addElement('text',   'username',     'username', ['size' => 30]);    
        }
        $this->form->addElement('text',   'password',     'password',             ['size' => 30]);
        $this->form->addElement('text',   'last_name',    'LastName',                    ['size' => 30]);
        $this->form->addElement('text',   'first_name',   'FirstName',                    ['size' => 30]);
        $this->form->addElement('date',   'birthday',     'Birthday', $options);
        $this->form->addElement('select', 'states',          'States',   $states_array);

        $this->form->addRule('username',  'Please enter your e-mail address.','required', null, 'server');
        $this->form->addRule('username',  'The format of the e-mail address is invalid.',   'email', null, 'server');
        $this->form->addRule('password',  'Please enter the password.',     'required', null, 'server');
        $this->form->addRule('password',  'Please enter the password within 4 to 16 characters.','rangelength', [4, 16], 'server');
        $this->form->addRule('password',  'Please use alphanumeric characters, symbols (_ -!? # $% &) For the password.','regex', '/^[a-zA-z0-9]*$/', 'server');
        $this->form->addRule('last_name', 'Please input last name', 'required', null, 'server');
        $this->form->addRule('first_name','Please input first name', 'required', null, 'server');

        $this->form->applyFilter('__ALL__', 'trim');
    }
    
    
    //----------------------------------------------------
    // Search processing functions
    //----------------------------------------------------
    //
    // add pageID to URL
    //
    public function add_pageID(){


        $add_pageID = "";
        if(isset($_GET['pageID']) && $_GET['pageID'] != ""){
            $add_pageID = '&pageID=' . $_GET['pageID'];
            $_SESSION['pageID'] = $_GET['pageID'];
        }else if(isset($_SESSION['pageID']) && $_SESSION['pageID'] != ""){
            $add_pageID = '&pageID=' . $_SESSION['pageID'];
        }
        return $add_pageID;
    }

    //----------------------------------------------------
    // Pagination process
    //----------------------------------------------------
    public function make_page_link($data){

        // Use Pager/Jumping
        require_once 'Pager/Jumping.php';

        $params = [
            'mode'      => 'Jumping',
            'perPage'   => 10,
            'delta'     => 10,
            'itemData'  => $data,
            'extraVars' => array(
            'type'      => 'list',
            'action'    => 'form',
            ),
        ];

        // Use Pager/Jumping
        $pager = new Pager_Jumping($params);

        $data_perPage  = $pager->getPageData();
        $links = $pager->getLinks();    
        return [$data_perPage, $links];
    }    

    //----------------------------------------------------
    // Debug display function
    //----------------------------------------------------
    public function debug_display(){
        if(_DEBUG_MODE){
            $this->debug_str = "";
            if(isset($_SESSION)){
                $this->debug_str .= '<BR><BR>$_SESSION<BR>'; 
                $this->debug_str .= var_export($_SESSION, TRUE);
            }
            if(isset($_POST)){
                $this->debug_str .= '<BR><BR>$_POST<BR>'; 
                $this->debug_str .= var_export($_POST, TRUE);
            }
            if(isset($_GET)){
                $this->debug_str .= '<BR><BR>$_GET<BR>'; 
                $this->debug_str .= var_export($_GET, TRUE);
            }
            // Debug mode setting of smarty.
            // Display variables in template in popup window
            $this->view->debugging = _DEBUG_MODE;
        }
    }
}

?>
