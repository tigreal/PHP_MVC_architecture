<?php
/**
 * Description of TempmemberModel
 *
 * @author AtomYah
 */
class TempmemberModel extends BaseModel {
    //----------------------------------------------------
    // Regist temporary member
    //----------------------------------------------------
    public function regist_tempmember($userdata){
        try {
            $this->pdo->beginTransaction();
            $sql = "INSERT  INTO tempmember (username, password, last_name, first_name, birthday, states, link_pass, reg_date )
            VALUES ( :username, :password, :last_name, :first_name, :birthday, :states , :link_pass, now() )";
            $stmh = $this->pdo->prepare($sql);
            $stmh->bindValue(':username',   $userdata['username'],   PDO::PARAM_STR );
            $stmh->bindValue(':password',   $userdata['password'],   PDO::PARAM_STR );
            $stmh->bindValue(':last_name',  $userdata['last_name'],  PDO::PARAM_STR );
            $stmh->bindValue(':first_name', $userdata['first_name'], PDO::PARAM_STR );
            $stmh->bindValue(':birthday',   $userdata['birthday'],   PDO::PARAM_STR );
            $stmh->bindValue(':states',     $userdata['states'],        PDO::PARAM_INT );
            $stmh->bindValue(':link_pass',  $userdata['link_pass'],  PDO::PARAM_STR );
            $stmh->execute();
            $this->pdo->commit();
        } catch (PDOException $Exception) {
            $this->pdo->rollBack();
            print "error：" . $Exception->getMessage();
        }
    }

    //----------------------------------------------------
    // If there is more than one username in the temporary registration table, true is returned.
    //----------------------------------------------------
    public function check_username($userdata){
        try {
            $sql= "SELECT * FROM tempmember WHERE username = :username ";
            $stmh = $this->pdo->prepare($sql);
            $stmh->bindValue(':username',  $userdata['username'], PDO::PARAM_STR );
            $stmh->execute();
            $count = $stmh->rowCount();
        } catch (PDOException $Exception) {
            print "error：" . $Exception->getMessage();
        }
        if($count >= 1){
            return true;
        }else{
            return false;
        }
    }

    //----------------------------------------------------
    // Function when accessing by clicking the link sent by registration confirmation email
    //----------------------------------------------------
    public function check_tempmember($username, $link_pass){
        $data = [];
        try {
            $sql= "SELECT * FROM tempmember WHERE username = :username AND link_pass = :link_pass limit 1 ";
            $stmh = $this->pdo->prepare($sql);
            $stmh->bindValue(':username',  $username,  PDO::PARAM_STR );
            $stmh->bindValue(':link_pass', $link_pass, PDO::PARAM_STR );
            $stmh->execute();
            $data = $stmh->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $Exception) {
            print "error：" . $Exception->getMessage();
        }
        return $data;
    }

    //----------------------------------------------------
    // Delete tempmember, and regist member
    //----------------------------------------------------
    public function delete_tempmember_and_regist_member($userdata){
        try {
            $this->pdo->beginTransaction();
            $sql = "DELETE FROM tempmember WHERE id = :id";
            $stmh = $this->pdo->prepare($sql);
            $stmh->bindValue(':id', $userdata['id'], PDO::PARAM_INT );
            $stmh->execute();
            $sql = "INSERT  INTO member (username, password, last_name, first_name, birthday, states, reg_date )
            VALUES ( :username, :password, :last_name, :first_name, :birthday, :states , now() )";
            $stmh = $this->pdo->prepare($sql);
            $stmh->bindValue(':username',   $userdata['username'],   PDO::PARAM_STR );
            $stmh->bindValue(':password',   $userdata['password'],   PDO::PARAM_STR );
            $stmh->bindValue(':last_name',  $userdata['last_name'],  PDO::PARAM_STR );
            $stmh->bindValue(':first_name', $userdata['first_name'], PDO::PARAM_STR );
            $stmh->bindValue(':birthday',   $userdata['birthday'],   PDO::PARAM_STR );
            $stmh->bindValue(':states',        $userdata['states'],        PDO::PARAM_INT );
            $stmh->execute();
            $this->pdo->commit();
        } catch (PDOException $Exception) {
            $this->pdo->rollBack();
            print "error：" . $Exception->getMessage();
        }
    }
}
?>