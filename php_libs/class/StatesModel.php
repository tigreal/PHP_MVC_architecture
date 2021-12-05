<?php
/**
 * Description of StatesModel
 *
 * @author AtomYah
 */
class StatesModel extends BaseModel {
    //----------------------------------------------------
    // Get the states name
    //----------------------------------------------------
    public function get_states_data(){
        $key_array = [];
        try {
            $sql= "SELECT * FROM states";
            $stmh = $this->pdo->query($sql);
            while ($row = $stmh->fetch(PDO::FETCH_ASSOC)){
                $key_array[$row['id']] = $row['states']; 
            }
        } catch (PDOException $Exception) {
            print "error：" . $Exception->getMessage();
        }
        return $key_array;
    }
}

?>
