<?php

/**
 * Main HTML page
 *
 * @author   Gabriel Ionescu
 * @package  phpdocGenerator
 * @link     https://github.com/blchinezu/phpdocGenerator
 * 
 */

if( !class_exists('phpdocGenerator_standalone') ) {

/**
 * Main page
 */
class phpdocGenerator_standalone {


    /**
     * Constructor. Launch processing and echo.
     */
    public function __construct() {
        $this->process();
        echo $this->show();
    }


    /**
     * Processing part (useless)
     */
    protected function process() {

    }
    

    /**
     * Return page header
     *
     * @return  string  HTML
     */
    protected function showHead() { 
        return '<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="content-type" content="text/html; charset=utf-8" >
            <title>'.i18n('phpdocGenerator').'</title>

            <link rel="stylesheet" href="'.CSS_PATH.'jquery-ui.min.css?t='.filemtime(CSS_PATH.'jquery-ui.min.css').'" type="text/css" media="screen" />
            <link rel="stylesheet" href="'.CSS_PATH.'css.css?t='.filemtime(CSS_PATH.'css.css').'" type="text/css" media="screen" />

            <script type="text/javascript" src="'.JS_PATH.'jquery.min.js?t='.filemtime(JS_PATH.'jquery.min.js').'"></script>
            <script type="text/javascript" src="'.JS_PATH.'jquery-ui.min.js?t='.filemtime(JS_PATH.'jquery-ui.min.js').'"></script>
            <script type="text/javascript" src="'.JS_PATH.'js.js?t='.filemtime(JS_PATH.'js.js').'"></script>

            <script> window.i18n = '.json_encode($GLOBALS['i18n']).'; </script>
        </head>
        <body>';
    }


    /**
     * 
     *
     * @return  string  HTML
     */
    protected function showBody() {
        return '
            <div id="proiecte" class="tab">
                <div class="title">
                    '.i18n('Projects').'
                </div>
                <div class="content"></div>
            </div>
            <div id="proiect">
                <div id="nume" class="tab">
                    <div class="title">
                        '.i18n('Project').'
                    </div>
                    <input type="text" value="" placeholder="'.i18n('Project name...').'"
                        onkeyup="nume_getTarget();"
                        onchange="nume_getTarget();"
                        click="nume_getTarget();"
                        >
                    <span class="target hidden"></span>
                </div>
                <div id="template" class="tab">
                    <div class="title">
                        '.i18n('Template').'
                    </div>
                    <select onchange="genereazaPreviewComanda();">
                        '.$this->templateOptions().'
                    </select>
                </div>
                <div id="explorer" class="tab">
                    <div class="title">
                        '.i18n('Documented path').'
                        <div class="right icon defaults" title="'.i18n('Default folder').'" onclick="explorer_loadDir(\'/\');"></div>
                    </div>
                    <div class="content"></div>
                </div>
                <div id="exclude" class="tab">
                    <div class="title">
                        '.i18n('Exclusion rules').'
                        <div class="right icon defaults" title="'.i18n('Default values').'" onclick="exclude_defaults();"></div>
                    </div>
                    <div class="content">
                        <textarea onkeyup="genereazaPreviewComanda();"></textarea>
                    </div>
                </div>
                <div id="comanda" class="tab">
                    <div class="title">
                        '.i18n('Command').'
                    </div>
                    <input type="text" value="" readonly="readonly">
                    <button onclick="genereazaDocumentatie();">
                        <span>'.i18n('Generate').'</span>
                        <img src="'.IMAGES_PATH.'loadingSmall.gif" class="icon">
                    </button>
                </div>
            </div>
            <div id="liveStats">
                <div id="running" class="tab">
                    <div class="title">'.i18n('Process Status').'</div>
                    <div class="content">-</div>
                </div>
                <div id="serverTime" class="tab">
                    <div class="title">'.i18n('Time').'</div>
                    <div class="content">-</div>
                </div>
                <div id="serverLoad" class="tab">
                    <div class="title">'.i18n('Server Load').'</div>
                    <div class="content">-</div>
                </div>
                <div id="phpdocLog" class="tab">
                    <div class="title">'.i18n('Log').'</div>
                    <div class="content">-</div>
                </div>
                <div id="butonInchidere">
                    <button onclick="closeStats();">'.i18n('OK').'</button>
                </div>
            </div>
            <div id="dialog"></div>
            ';
    }

    /**
     * Template options
     *
     * @return string HTML
     */
    protected function templateOptions() {
        $ret = '';

        $pdg = new phpdocGenerator;
        foreach( $pdg->validTemplates AS $template ) $ret .=
            '<option value="'.$template.'">'.$template.'</option>';

        return $ret;
    }
    


    /**
     * Return page footer
     *
     * @return  string  HTML
     */
    protected function showFoot() {
        return '</body></html>';
    }
    

    /**
     * Show page
     *
     * @return  string  HTML
     */
    protected function show() {
        $af = '';

        $af .= $this->showHead();
        $af .= $this->showBody();
        $af .= $this->showFoot();

        return $af;
    }


}}

?>