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
 * Clasa folosita pentru generarea documentatiei cu phpdocumentor
 */
class phpdocGenerator extends class_sql {


    /**
     * Locatia in care se tine documentatia generata (Locatie absoluta din server)
     *
     * @var  string
     */
    public $phpdocRoot = DOCS_PATH;


    /**
     * Locatia in care se tin logurile
     *
     * @var  string
     */
    public $phpdocLogDir = LOG_PATH;


    /**
     * Locatia in care se tine documentatia generata (URL)
     *
     * @var  string
     */
    public $phpdocRootWeb = DOCS_PATH_WEB;


    /**
     * Folderul pentru care se face documentatia
     *
     * @var  string
     */
    public $directory = '';


    /**
     * Numele proiectului (se salveaza in baza de date)
     *
     * @var  string
     */
    public $name = '';


    /**
     * Folderul in care se salveaza documentatia
     *
     * @var  string
     */
    public $target = '';


    /**
     * Foldere in care nu se poate genera documentatie
     *
     * @var  array
     */
    public $reservedTargets = array();


    /**
     * Template-ul folosit
     *
     * @var  string
     */
    public $template = 'responsive-twig';


    /**
     * Template-uri posibile
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
     * Reguli de excludere
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
     * Tabela cu proiectele existente
     *
     * @var  string
     */
    public $projectsDB = SQL_PROJECTS_TABLE;


    /**
     * Tine un obiect de tipul fileExplorer
     *
     * @var  fileExplorer
     */
    public $explorer = NULL;


    /**
     * Constructor. Initializeaza obiect fileExplorer si apeleaza constructorul din class_sql.
     *
     * @param  mysql_connection  $sql_handle  Conexiune MySQL
     */
    public function __construct() {
        parent::__construct();
        $this->createTableIfRequired();
        $this->explorer = new fileExplorer( $this->phpdocRoot );
    }


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
     * Formeaza comanda cu care se genereaza documentatia
     *
     * @return  string  Comanda de executat
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

        // Executabil
        $cmd = PHPDOC_BINARY;

        // Locatia programului pentru care se face documentatia
        $cmd .= ' -d '.$this->explorer->escapePath($this->directory);

        // Locatia in care se stocheaza documentatia
        $cmd .= ' -t '.$this->explorer->escapePath($this->phpdocRoot.'/'.$this->target);

        // Template-ul folosit
        $cmd .= ' --template='.$this->explorer->escapePath($this->template);

        // Reguli de excludere
        if( !empty($this->exclude) ) {
            // $cmd .= ' --ignore="'.implode(',', $this->exclude).'"';
            $cmd .= ' --ignore='.$this->explorer->escapePath(implode(',', $this->exclude));
            // $cmd .= ' -i "'.implode(',', $this->exclude).'"';
            // $cmd .= ' -i '.$this->explorer->escapePath(implode(',', $this->exclude));
        }

        // Logheaza output-ul comenzii intr-o fila
        $cmd .= ' >> '.$this->explorer->escapePath( $this->getLogFilePath() ).' 2>&1';

        return $cmd;
    }


    /**
     * Salveaza un proiect in baza de date
     *
     * @param  string  $cmd  Comanda cu care se genereaza proiectul
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
     * Genereaza documentatie
     *
     * @return  mixed  true (succes), false (nu s-a generat), string (eroare parametri)
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
     * Intoarce locatia filei in care se tine output-ul generarii
     *
     * @return  string  Locatie absoluta
     */
    public function getLogFilePath() {
        return $this->phpdocLogDir.'/'.$this->explorer->safeFilename($this->target).'.log';
    }
    

    /**
     * Intoarce array de proiecte
     *
     * @param  array   $select  Ce sa intoarce pentru fiecare proiect (id, name, from, template...)
     * @param  string  $key     Cheia pe care sa o aiba proiectul
     * @param  string  $where   String de conditii pentru query
     *
     * @return  array  Proiecte
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
     * Intoarce proiecte existente in $this->phpdocRoot care nu-s salvate
     *
     * @param  array  $except  Array de proiecte pe care sa nu le intoarca
     *
     * @return  array  Proiecte
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
     * Verifica daca un folder contine un proiect sau nu
     *
     * @param  string  $path  Locatia folder-ului
     *
     * @return  boolean  true (daca e proiect), false (daca nu e proiect)
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
     * Sterge folder-ul unui proiect
     *
     * @param  string   $relativePath  Locatia relativa a proiectului (relative de work/phpdocumentor/)
     * @param  boolean  $preview       true (doar se afiseaza comenzile), false (executa comenzile de stergere)
     *
     * @return  mixed   false (daca totul e in regula), string (daca a aparut o problema)
     */
    public function removeProjectFolder($relativePath, $preview = true) {

        if( $this->explorer->isAbsolutePath($relativePath) )
            return 'Functia phpdocGenerator::removeProjectFolder() trebuie sa primeasca o locatie relativa!<br>S-a primit locatie absoluta:<br>'.$relativePath;

        $this->explorer->cd($this->phpdocRoot);

        $path = $this->explorer->sanitizePath($relativePath);

        if( !$this->isProjectDir($path) )
            return 'Folder-ul "'.$path.'" nu contine un proiect!';

        $result = $this->explorer->rm($path, $preview);

        if( is_string($result) )
            return $result;

        if( !$result )
            return 'Nu s-a putut sterge folder-ul:<br><br>"'.$path.'"!';

        return false;
    }
    

    /**
     * Sterge un proiect (din baza de date + folder)
     *
     * @param  integer  $id       ID-ul proiectului din baza de date
     * @param  boolean  $preview  true (doar se afiseaza comenzile), false (executa comenzile de stergere)
     *
     * @return  mixed   false (daca totul e in regula), string (daca a aparut o problema)
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
     * Transforma numele de proiect in 'target'
     *
     * @param  strng  $name  Nume proiect
     *
     * @return  string  Target
     */
    public function name2target($name) {
        $target = preg_replace('/[^a-zA-Z0-9\/\-\(\)\[\]\.]/', '_', $name);
        return $target;
    }
    
    

}}


?>