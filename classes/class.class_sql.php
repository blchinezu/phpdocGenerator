<?php

/**
 * SQL Wrapper
 *
 * @author   Gabriel Ionescu
 * @package  phpdocGenerator
 * @link     https://github.com/blchinezu/phpdocGenerator
 * 
 */

if( class_exists('class_sql') === false ) {

class class_sql {

    private $sql_handle;

    public function __construct() {
        $this->sql_connect();
    }

    public function __destruct() {
        $this->sql_close();
    }

    private function sql_connect() {

        if( !$this->sql_handle )
            $this->sql_handle = @mysql_connect(SQL_HOST, SQL_USER, SQL_PASS) OR die(mysql_error());

        @mysql_select_db(SQL_DB) or die(mysql_error());

        if( $this->sql_handle )
            $this->q("SET NAMES utf8");
    }

    private function sql_close() {
        if( $this->sql_handle )
            @mysql_close($this->sql_handle);
    }
    
    protected function q($query, $echo = false) {

        if( $echo )
            echo '<div class="queryEcho" style="text-align:left;">'.nl2br(trim($query)).'</div>';
        
        $result = mysql_query($query, $this->sql_handle)
                OR die('<hr><b>QUERY:</b><br><pre>'.$query.'</pre><hr><b>ERROR:</b><br>'.mysql_error().'<hr>');

        if( $echo )
            echo '<div>';

        return $result;
    }

}

}

?>