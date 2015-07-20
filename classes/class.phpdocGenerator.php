<?php

/**
 * Main class
 *
 * @author   Gabriel Ionescu
 * @package  phpdocGenerator
 * @link     https://github.com/blchinezu/phpdocGenerator
 * 
 */

if( !class_exists('phpdocGenerator') ) {

/**
 * Class used for generating docs with phpdocumentor
 */
class phpdocGenerator extends class_sql {


    /**
     * Path to the generated docs (absolute path from server)
     *
     * @var  string
     */
    public $phpdocRoot = DOCS_PATH;


    /**
     * Path to logs
     *
     * @var  string
     */
    public $phpdocLogDir = LOG_PATH;


    /**
     * Path to the generated docs (URL)
     *
     * @var  string
     */
    public $phpdocRootWeb = DOCS_PATH_WEB;


    /**
     * The scanned directory which contains the code
     *
     * @var  string
     */
    public $directory = '';


    /**
     * Project name (it's saved in the db)
     *
     * @var  string
     */
    public $name = '';


    /**
     * Where to save the docs
     *
     * @var  string
     */
    public $target = '';


    /**
     * Folders in which docs can't be generated
     *
     * @var  array
     */
    public $reservedTargets = array();


    /**
     * Used template
     *
     * @var  string
     */
    public $template = 'responsive-twig';


    /**
     * Possible templates
     *
     * @var  array
     */
    public $validTemplates = array(
        // 'abstract',
        // 'checkstyle',
        // 'clean',
        // 'new-black',
        // 'old-ocean',
        'responsive-twig',
        'responsive',
        // 'xml',
        // 'zend'
        );


    /**
     * Exclusion rules
     *
     * @var  array
     */
    public $exclude = array(

        // Strict folder name
        '*/_fpdf/*',
        '*/_mypdf/*',
        '*/backup/*',
        '*/backups/*',
        '*/bak/*',
        '*/dompdf/*',
        '*/html2fpdf/*',
        '*/html2pdf/*',
        '*/mPDF/*',
        '*/mpdf/*',
        '*/old/*',
        '*/phpequations/*',
        '*/phpexcel/*',
        '*/PHPExcel/*',
        '*/simplehtmldom/*',
        '*/tcpdf/*',
        '*/tmp/*',

        // Ending of folder name
        '*-bak/*',
        '*-old/*',
        '*-tmp/*',
        '*.bak/*',
        '*.old/*',
        '*.tmp/*',
        '*_bak/*',
        '*_old/*',
        '*_tmp/*',

        // File extension
        '*.bak',
        '*.old',
        '*.tmp',

        // Anywhere
        '*Copy of *',

        );


    /**
     * DB table containing existing projects
     *
     * @var  string
     */
    public $projectsDB = SQL_PROJECTS_TABLE;


    /**
     * fileExplorer object
     *
     * @var  fileExplorer
     */
    public $explorer = NULL;


    /**
     * Init fileExplorer and call the class_sql construct
     *
     * @param  mysql_connection  $sql_handle  MySQL connection
     */
    public function __construct() {
        parent::__construct();
        $this->createTableIfRequired();
        $this->explorer = new fileExplorer( $this->phpdocRoot );
    }

    /**
     * Create the DB table if it doesn't exist
     */
    private function createTableIfRequired() {
        $query = "
            CREATE TABLE IF NOT EXISTS ".$this->projectsDB." (
                `id`       int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name`     varchar(255)     NOT NULL,
                `date`     datetime         NOT NULL,
                `from`     varchar(255)     NOT NULL,
                `to`       varchar(255)     NOT NULL,
                `template` varchar(255)     NOT NULL,
                `exclude`  blob             NOT NULL,
                `cmd`      blob             NOT NULL,
                `ip`       int(15) unsigned NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
            ";
        $this->q($query);
    }


    /**
     * Generate the command used to generate docs
     *
     * @return  string  Command
     */
    public function generateCommand() {

        $this->explorer->cd($this->phpdocRoot);

        if( trim($this->directory) == '' || !$this->explorer->isValidDir($this->directory) )
            return 'Invalid directory: "'.$this->directory.'"';

        if( trim($this->name) == '' )
            return 'Invalid name: "'.$this->name.'"';

        if( trim($this->target) == '' )
            return 'Invalid target: "'.$this->target.'"';

        if( in_array($this->target, $this->reservedTargets) )
            return 'Target not allowed: "'.$this->target.'"';

        if( !in_array($this->template, $this->validTemplates) )
            return 'Invalid template: "'.$this->template.'"';

        if( !is_array($this->exclude) )
            return 'Invalid excludes: "'.print_r($this->exclude, true).'"';

        // Binary
        $cmd = PHPDOC_BINARY;

        // Path to the code
        $cmd .= ' -d '.$this->explorer->escapePath($this->directory);

        // Path to the future docs
        $cmd .= ' -t '.$this->explorer->escapePath($this->phpdocRoot.'/'.$this->target);

        // Template
        $cmd .= ' --template='.$this->explorer->escapePath($this->template);

        // Exclusion rules
        if( !empty($this->exclude) ) {
            // $cmd .= ' --ignore="'.implode(',', $this->exclude).'"';
            $cmd .= ' --ignore='.$this->explorer->escapePath(implode(',', $this->exclude));
            // $cmd .= ' -i "'.implode(',', $this->exclude).'"';
            // $cmd .= ' -i '.$this->explorer->escapePath(implode(',', $this->exclude));
        }

        // Log output to file
        $cmd .= ' >> '.$this->explorer->escapePath( $this->getLogFilePath() ).' 2>&1';

        return $cmd;
    }


    /**
     * Save project to DB
     *
     * @param  string  $cmd  Command used to generate the docs
     */
    public function saveProject($cmd) {

        $query = "
            SELECT `id`
            FROM   ".$this->projectsDB."
            WHERE  `to` = '".mysql_real_escape_string($this->target)."'
            ";
        $result = $this->q($query);

        if( mysql_num_rows($result) == 1 ) {

            $row = mysql_fetch_assoc($result);
            $id = $row['id'];

            $query = "
                UPDATE ".$this->projectsDB." SET
                    `name`     = '".mysql_real_escape_string($this->name)."',
                    `date`     = '".date('Y-m-d H:i:s')."',
                    `from`     = '".mysql_real_escape_string($this->directory)."',
                    `to`       = '".mysql_real_escape_string($this->target)."',
                    `template` = '".mysql_real_escape_string($this->template)."',
                    `exclude`  = '".mysql_real_escape_string(json_encode($this->exclude))."',
                    `cmd`      = '".mysql_real_escape_string($cmd)."',
                    `ip`       = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'
                WHERE
                    `id` = ".intval($id)."
                ";
            $this->q($query);
        }
        else {

            $query = "
                INSERT INTO ".$this->projectsDB." SET
                    `name`     = '".mysql_real_escape_string($this->name)."',
                    `date`     = '".date('Y-m-d H:i:s')."',
                    `from`     = '".mysql_real_escape_string($this->directory)."',
                    `to`       = '".mysql_real_escape_string($this->target)."',
                    `template` = '".mysql_real_escape_string($this->template)."',
                    `exclude`  = '".mysql_real_escape_string(json_encode($this->exclude))."',
                    `cmd`      = '".mysql_real_escape_string($cmd)."',
                    `ip`       = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'
                ";
            $this->q($query);
        }
    }
    

    /**
     * Generate docs
     *
     * @return  mixed  true (success), false (not generated), string (params error)
     */
    public function generateDocumentation() {
        $cmd = $this->generateCommand();

        if( strpos($cmd, PHPDOC_BINARY) !== 0 )
            return $cmd;

        $this->saveProject($cmd);

        file_put_contents($this->getLogFilePath(), $cmd."\n\n");

        ob_start();
        system($cmd);
        ob_get_clean();

        return $this->isProjectDir($this->target);
    }


    /**
     * Get log file path
     *
     * @return  string  Absolute path
     */
    public function getLogFilePath() {
        return $this->phpdocLogDir.'/'.$this->explorer->safeFilename($this->target).'.log';
    }
    

    /**
     * Get array of existing projects
     *
     * @param  array   $select  What to return for each project (id, name, from, template...)
     * @param  string  $key     Project key
     * @param  string  $where   Conditions for the search
     *
     * @return  array  Projects
     */
    public function getSavedProjects( $select, $key = 'id', $where = '', $orderBy = '`to`' ) {
        $ret = array();

        if( !is_array($select) || empty($select) ) {
            $select = '*';
        }
        else {
            if( !in_array($key, $select) )
                $select[] = $key;
            $select = '`'.implode('`,`', $select).'`';
        }

        if( is_string($where) && !empty($where) )
            $where = 'WHERE '.$where;
        else
            $where = '';

        if( is_string($orderBy) && !empty($orderBy) )
            $orderBy = 'ORDER BY '.$orderBy;
        else
            $orderBy = '';

        $query = "
            SELECT ".$select."
            FROM   ".$this->projectsDB."
            ".$where."
            ".$orderBy;
        $result = $this->q($query);

        if( mysql_num_rows($result) == 0 )
            return $ret;

        while( $row = mysql_fetch_assoc($result) ) {

            if( isset($row['exclude']) ) {

                $row['exclude'] = json_decode($row['exclude']);
                if( !is_array($row['exclude']) )
                    $row['exclude'] = array();
            }
            
            $ret[ $row[$key] ] = $row;
        }
    
        return $ret;
    }
    

    /**
     * Get array of existing projects that are not present in the DB
     *
     * @param  array  $except  Array of ignored projects
     *
     * @return  array  Projects
     */
    public function getAlienProjects( $except = array() ) {
        $ret = array();
    
        $this->explorer->cd($this->phpdocRoot);

        $dirs = $this->explorer->ls_dirs();

        foreach( $dirs AS $dir ) {

            if( in_array($dir, $except) )
                continue;

            if( $this->isProjectDir($dir) ) {
                $ret[] = $dir;
            }
            else {
                $subdirs = $this->explorer->ls_dirs($dir);
                foreach( $subdirs AS $subdir ) {

                    $subdir = $dir.'/'.$subdir;

                    if( in_array($subdir, $except) )
                        continue;

                    if( $this->isProjectDir($subdir) ) {
                        $ret[] = $subdir;
                    }
                }
            }
        }
    
        return $ret;
    }


    /**
     * Check if a folder is a project
     *
     * @param  string  $path  Folder path
     *
     * @return  boolean  true (project), false (not project)
     */
    public function isProjectDir($path) {

        $this->explorer->cd($this->phpdocRoot);

        $path = $this->explorer->sanitizePath($path);

        return
            $this->explorer->isValidDir($path) &&
            // $this->explorer->isValidFile($path.'/classes.svg');
            $this->explorer->isValidFile($path.'/index.html');
    }
    

    /**
     * Delete the folder of a project
     *
     * @param  string   $relativePath  Relative path (relative to the root of this app)
     * @param  boolean  $preview       true (just show commands), false (actually delete)
     *
     * @return  mixed   false (if ok), string (if there's a problem)
     */
    public function removeProjectFolder($relativePath, $preview = true) {

        if( $this->explorer->isAbsolutePath($relativePath) )
            return 'phpdocGenerator::removeProjectFolder() has to receive a relative path!<br>Got an absolute path:<br>'.$relativePath;

        $this->explorer->cd($this->phpdocRoot);

        $path = $this->explorer->sanitizePath($relativePath);

        if( !$this->isProjectDir($path) )
            return 'The folder "'.$path.'" doesn\'t contain a project!';

        $result = $this->explorer->rm($path, $preview);

        if( is_string($result) )
            return $result;

        if( !$result )
            return 'Couldn\'t delete the folder:<br><br>"'.$path.'"!';

        return false;
    }
    

    /**
     * Delete a project (DB + folder)
     *
     * @param  integer  $id       DB project id
     * @param  boolean  $preview  true (only show commands), false (actually delete)
     *
     * @return  mixed   false (ok), string (if a problem occurred)
     */
    public function removeProject($id, $preview = true) {

        $query = "
            SELECT `to`
            FROM   ".$this->projectsDB."
            WHERE  `id` = '".intval($id)."'
            ";
        $result = $this->q($query);

        if( mysql_num_rows($result) == 0 )
            return 'Nu exista proiect cu ID = "'.$id.'"!';

        $row = mysql_fetch_assoc($result);
        $result = $this->removeProjectFolder($row['to'], $preview);

        $ret = '';

        if( is_string($result) )
            $ret .= $result;

        $query = "
            DELETE FROM ".$this->projectsDB." WHERE
                `id` = '".intval($id)."'
            ";

        if( $preview )
            return $ret.'<br>'.nl2br($query);
        
        return !$this->q($query);
    }


    /**
     * Transform the project name into 'target'
     *
     * @param  strng  $name  Project name
     *
     * @return  string  Target
     */
    public function name2target($name) {
        $target = preg_replace('/[^a-zA-Z0-9\/\-\(\)\[\]\.]/', '_', $name);
        return $target;
    }
    
    

}}


?>