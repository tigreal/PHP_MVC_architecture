<?php
/**
 * Description of Auth
 *
 * @author AtomYah
 */
class Auth {
    // initialize session variables
    private $authname; // Store session infomation
    private $sessname; // Session name
    public function __construct() {

    }

    public function set_authname($name){
        $this->authname = $name;
    }
    
    public function get_authname(){
        return $this->authname;
    }

    public function set_sessname($name){
        $this->sessname = $name;
    }
    
    public function get_sessname(){
        return $this->sessname;
    }

    public function start(){
        // Just return when session is active
        if(session_status() ===  PHP_SESSION_ACTIVE){
            return;
        }
        if($this->sessname != ""){
            session_name($this->sessname);
        }
        // Start session
        session_start();
    }
    
    // Authentication check
    public function check(){
        if(!empty($_SESSION[$this->get_authname()]) && $_SESSION[$this->get_authname()]['id'] >= 1){
            return true;
        }
    }

    public function get_hashed_password($password) {
        // Cost parameter
        // From 04 to 31. Larger number More secure.
        $cost = 10;

        // Generate random charactor string
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');

        // Generate salt
        $salt = sprintf("$2y$%02d$", $cost) . $salt;

        $hash = crypt($password, $salt);
        
        return $hash;
    }
    
    
    // Returns true if the passwords match
    public function check_password($password, $hashed_password){
        if ( crypt($password, $hashed_password) == $hashed_password ) {
            return true;
        }
    }
    
    // Acquisition of authentication information
    public function auth_ok($userdata){
        session_regenerate_id(true);
        $_SESSION[$this->get_authname()] = $userdata;
    }

    public function auth_no(){
        return 'The user name or password is incorrect.'."\n";
    }
    

    // Discard authentication information
    public function logout(){
        // Empty the session variable
		$_SESSION = [];

        // Delete cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();
    }

// 


}

?>
