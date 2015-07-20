<?php

/**
 * Treats AJAX requests
 *
 * @author   Gabriel Ionescu
 * @package  phpdocGenerator
 * @link     https://github.com/blchinezu/phpdocGenerator
 * 
 */

if( !class_exists('phpdocGenerator_ajax') ) {

/**
 * Treat AJAX requests
 */
class phpdocGenerator_ajax extends phpdocGenerator {


    /**
     * Launch the method with the received name through $_POST['func']
     */
    public function __construct() {
        parent::__construct();
        echo self::$_POST['func']();
    }


    /**
     * Show projects (normal + alien)
     *
     * @return  string  HTML
     */
    protected function proiecte_get() {

        $proiecte = '';

        $salvate = $this->getSavedProjects(array('id', 'name', 'to'), 'to');
        $externe = $this->getAlienProjects(array_keys($salvate));

        foreach( $salvate AS $proiect ) $proiecte .= 
            '<div class="dir project noTextSelect">'.
                '<div class="icon exclude" title="'.i18n('Remove project').'" onclick="proiecte_remove(\'salvat\', \''.$proiect['id'].'\');">'.
                '</div>'.
                '<div class="icon open" title="'.i18n('Open docs').'" onclick="proiecte_open(\''.$this->phpdocRootWeb.'/'.$proiect['to'].'\');">'.
                '</div>'.
                '<div class="dirname" onclick="proiecte_loadSalvat(\''.$proiect['id'].'\')">'.
                    $proiect['name'].
                '</div>'.
                '<span class="target hidden">'.
                    $proiect['to'].
                '</span>'.
            '</div>';

        foreach( $externe AS $proiect ) $proiecte .=
            '<div class="dir noTextSelect" onclick="">'.
                '<div class="icon exclude" title="'.i18n('Erase project').'" onclick="proiecte_remove(\'extern\', \''.$proiect.'\');">'.
                '</div>'.
                '<div class="icon open" title="'.i18n('Open docs').'" onclick="proiecte_open(\''.$this->phpdocRootWeb.'/'.$proiect.'\');">'.
                '</div>'.
                '<div class="dirname" onclick="proiecte_loadExtern(\''.$proiect.'\')">'.
                    $proiect.
                '</div>'.
                '<span class="target hidden">'.
                    $proiect.
                '</span>'.
            '</div>';

        return $proiecte;
    }
    

    /**
     * Return JSON containing the project details
     *
     * @return  string  JSON
     */
    protected function proiecte_loadSalvat() {

        $proiect = $this->getSavedProjects('*', 'id', "id = '".intval($_POST['id'])."'");

        if( !empty($proiect) ) {
            $proiect = array_shift($proiect);
            $proiect['exclude'] = implode("\n", $proiect['exclude']);
        }

        return json_encode($proiect);
    }


    /**
     * Transform project name into 'target' (where'll the project be saved)
     *
     * @return  string  Path
     */
    protected function nume_getTarget() {
        return $this->name2target($_POST['target']);
    }
    

    /**
     * Delete project
     *
     * @return  string  Result (success or not)
     */
    protected function proiecte_remove() {

        switch( $_POST['mod'] ) {

            case 'salvat': $func = 'removeProject';       break;
            case 'extern': $func = 'removeProjectFolder'; break;

            default: return i18n('Unknown mode').': "'.$_POST['mod'].'"';
        }

        $preview = $_POST['preview']=='false' ? false : true;

        $result = $this->$func($_POST['proiect'], $preview);

        if( $preview )
            return $result;

        if( $result === false )
            return i18n('The project has been removed');

        return $result;
    }
    

    /**
     * Show a certain folder (breadcrumbs + content)
     *
     * @return  string  HTML
     */
    protected function explorer_loadDir() {
        
        $fe = new fileExplorer( $_POST['path'] );

        $pwd = $fe->pwd();
        $ls  = $fe->ls();
        // Breadcrumbs
        $breadcrumbs = '';

        $newPath = '';
        foreach( explode('/', $pwd) AS $dir ) {
            if( $dir != '' ) {
                if( $fe->OS == 'windows' && preg_match('/^[A-Z]\:$/', $dir) )
                    $newPath .= $dir;
                else
                    $newPath .= '/'.$dir;
                // if( $newPath == '/wrk' ) continue;
                $breadcrumbs .= '<div class="dir noTextSelect" onclick="explorer_loadDir(\''.$newPath.'\')">'.$dir.'</div>';
            }
        }

        // List
        $list = '<div class="dir noTextSelect">'.
                    '<div class="dirname" onclick="explorer_loadDir(\''.$pwd.'/..\')">'.
                        '..'.
                    '</div>'.
                '</div>';
        if( isset($ls['d']) && !empty($ls['d']) )
            foreach( $ls['d'] AS $dir )
                $list .= 
                    '<div class="dir noTextSelect">'.
                        '<div class="icon exclude" title="'.i18n('Exclude dir').'" onclick="exclude_addRule(\''.$pwd.'/'.$dir.'/*\');">'.
                        '</div>'.
                        '<div class="dirname" onclick="explorer_loadDir(\''.$pwd.'/'.$dir.'\')">'.
                            $dir.
                        '</div>'.
                    '</div>';

        return 
            '<input type="hidden" id="pwd" value="'.$pwd.'">'.
            '<div class="breadcrumbs">'.
                '<div class="normal">'.$breadcrumbs.'</div>'.
                '<div class="edit">'.
                    '<input type="text" value="'.$pwd.'">'.
                    '<button onclick="explorer_loadDirManual();">OK</button>'.
                '</div>'.
                '<div class="icon toggleEdit" onclick="explorer_toggleEdit();"></div>'.
            '</div>'.
            '<div class="list">'.$list.'</div>';
    }
    

    /**
     * Return default exclusion filters
     *
     * @return  string  Filters
     */
    protected function exclude_defaults() {
        return implode("\n", $this->exclude);
    }


    /**
     * Return status of the current process
     *  - Time
     *  - Server Load
     *  - Process Status
     *  - Generator Output
     *
     * @return  string  JSON
     */
    protected function getAllStats() {

        $this->target = $this->name2target($_POST['target']);

        // Time
        $time = '<b>'.date('H : i : s').'</b>';

        // Load
        $load = implode(', &nbsp; ', sys_getloadavg());

        // Process
        ob_start();
        system('ps ax | grep -v grep | grep '.$this->explorer->escapePath(PHPDOC_BINARY).' | grep '.$this->explorer->escapePath($this->target).' | grep -v \'sh -c cd \' | awk \'{print $1}\'');
        $proc = trim(ob_get_clean());

        if( $proc != '' ) {
            $proc = '<span style="color:#E60000">'.i18n('RUNNING').' ['.i18n('PID').'='.str_replace("\n", ', ', $proc).']</span>'.
                '<div class="icon killProcess" onclick="forceStopPhpdoc();" title="'.i18n('Kill process').'"></div>';
        }
        else {
            $proc = '<span style="color:#09A800">'.i18n('STOPPED').'</span>';
        }

        // Log
        $logFile = $this->getLogFilePath();
        if( file_exists($logFile) )
            $log = nl2br($this->explorer->tail($logFile, '21'));
        else
            $log = '';

        // Return
        return json_encode(array(
            'time' => $time,
            'load' => $load,
            'proc' => $proc,
            'log'  => $log
            ));
    }


    /**
     * Kill running process
     *
     * @return  string  Result
     */
    protected function forceStopPhpdoc() {

        $this->target = $this->name2target($_POST['target']);

        ob_start();
        system('ps ax | grep -v grep | grep '.$this->explorer->escapePath(PHPDOC_BINARY).' | grep '.$this->explorer->escapePath($this->target).' | grep -v \'sh -c cd \' | awk \'{print $1}\'');
        $proc = trim(ob_get_clean());

        if( $proc == '' )
            return i18n('Process is not running.');

        $proc = explode("\n", $proc);
        $pids = array();
        foreach( $proc AS $pid ) {

            if( (string)$pid != (string)intval($pid) ) {
                echo i18n('Different').': "'.$pid.'" != "'.intval($pid).'"<br>';
                continue;
            }

            $pids[] = $pid;
        }

        if( empty($pids) )
            return i18n('Can\'t kill pids').': '.implode(', ', $proc);

        $cmd = 'kill -9 '.implode(' ', $pids);
        ob_start();
        system($cmd);
        $result = trim(ob_get_clean());

        if( !empty($result) )
            return nl2br($result);

        return 'OK';
    }


    /**
     * Generate docs
     * - Set vars received through POST
     * - Launch generator
     *
     * @return  string  Result
     */
    protected function genereazaDocumentatie() {

        ob_start();
        system('ps ax | grep -v grep | grep '.$this->explorer->escapePath(PHPDOC_BINARY).' | grep -v \'sh -c cd \' | awk \'{print $1}\'');
        $proc = trim(ob_get_clean());

        if( !empty($proc) )
            return i18n('There\'s at least one more process running already').':<br><br>'.i18n('PID').'='.str_replace("\n", ', ', $proc);

        $this->seteazaVariabileDinPOST();

        $result = $this->generateDocumentation();

        if( is_string($result) )
            return $result;

        if( !$result )
            return i18n('Didn\'t generate docs!');

        return 'OK';
    }
    

    /**
     * Set class vars with values received through POST
     */
    protected function seteazaVariabileDinPOST() {

        $this->exclude = array();
        $tmp = explode("\n", trim($_POST['exclude']));
        foreach( $tmp AS $reg ) {
            $reg = trim($reg);
            if( $reg != '' )
                $this->exclude[$reg] = $reg;
        }
        $this->exclude = array_values($this->exclude);

        $this->directory = $_POST['directory'];
        $this->name      = $_POST['target'];
        $this->target    = $this->name2target($_POST['target']);
        $this->template  = $_POST['template'];
    }
    

    /**
     * Generate command
     *
     * @return  string  Command
     */
    protected function genereazaPreviewComanda() {

        $this->seteazaVariabileDinPOST();

        return $this->generateCommand();
    }
    
}}

?>