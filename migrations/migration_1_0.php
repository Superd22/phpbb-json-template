<?php namespace scfr\phpbbJsonTemplate\migration;

class Migration10 extends \phpbb\db\migration\migration
{
    public function update_schema()
    {
        return array(
            array('custom', 
                array(
                    array(&$this, 'create_db'),
                    array(&$this, 'populate_db'),
                )
            ),
        );
    }
    
    public function create_db() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}_privmsgs_convo` 
        ( 
            `id` INT NOT NULL AUTO_INCREMENT, 
            `user_id` INT NOT NULL, `last_time` INT NOT NULL, 
            `root_level`INT NOT NULL,
            `creation_time` INT NULL, 
            `last_time` INT NULL, 
            PRIMARY KEY (`id`), 
            UNIQUE INDEX `id_UNIQUE` (`id` ASC), 
            INDEX `unique_convo_per_user` (`id` ASC, `user_id` ASC)
        )";

        $this->sql_query($sql);
    }

    public function populate_db($start, $limit) {
        global $db;
        
    }
    
    public function revert_schema() {
        return array();
    }
}
?>