<?php
/**
 * Description of TempmemberController
 *
 * @author AtomYah
 */
class TempmemberController extends BaseController {
    public function run(){
        if (isset($_GET['username']) && isset($_GET['link_pass'])){
        // checking if necessary two parameters
            // Operate database
            $TempmemberModel = new TempmemberModel();
            $userdata = $TempmemberModel->check_tempmember($_GET['username'], $_GET['link_pass']);
            if(!empty($userdata) && count($userdata) >= 1){
            // checking if mactch the parameters.
                // Delete from tempmember, and register to member table.
                $TempmemberModel->delete_tempmember_and_regist_member($userdata);
                $this->title = 'Registration Completion Page';
                $this->message = 'Registration is completed. Please login from the top page.';
            }else{
            // Not matching parameters
                $this->title = 'Error Page';
                $this->message = 'This URL is invalid.';
            }
        }else{
        // No necessary two parameters
            $this->title = 'Error Page';
            $this->message = 'This URL is invalid.';
        }
        $this->file = 'tempmember.tpl'; 
        $this->view_display();
    }    
    

}

?>
