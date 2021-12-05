<?php
/**
 * Description of SystemModel
 *
 * @author AtomYah
 */
class AdminModel extends BaseModel {
    //----------------------------------------------------
    // Authentication by admin user name
    //----------------------------------------------------
    public function get_authinfo($username){
        $data = [];
        try {
            $sql= "SELECT * FROM admin WHERE username = :username limit 1";
            $stmh = $this->pdo->prepare($sql);
            $stmh->bindValue(':username',  $username,  PDO::PARAM_STR );
            $stmh->execute();
            $data = $stmh->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $Exception) {
            print "error：" . $Exception->getMessage();
        }
        return $data;
    }
}

?>
