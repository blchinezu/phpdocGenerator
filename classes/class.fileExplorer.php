<?php

/**
 * This class provides file explorer capabilities for linux web servers
 *
 * @author   Gabriel Ionescu
 * @package  phpdocGenerator
 * @link     https://github.com/blchinezu/phpdocGenerator
 * 
 */

if( !class_exists('fileExplorer') ) {

/**
 * This class provides file explorer capabilities for linux web servers
 */
class fileExplorer {


    /**
     * The highest folder accessible. It must be an ABSOLUTE path!
     *
     * @var  string
     */
    protected $chroot = FILE_EXPLORER_CHROOT;


    /**
     * Current path
     *
     * @var  string
     */
    private $pwd;


    /**
     * Array of regex rules. If matched, the file/directory is not shown.
     *
     * @var  array
     */
    public $hideMatches = array(
        '/^\.{0,2}$/'
        );


    /**
     * The OS under which PHP is running
     *
     * @var string
     */
    public $OS;


    /**
     * Constructor.
     *   - Check if chroot is valid
     *   - Sanitize chroot
     *   - Opens given path
     *
     * @param  string  $startingDir  Path to the default starting directory
     */
    public function __construct($startingDir = './') {

        $this->setOsSpecifics();

        if( !$this->isAbsolutePath($this->chroot) )
            die('ERR: CHROOT is not an absolute path! ('.$this->chroot.')');

        if( !$this->isValidDir($this->chroot) )
            die('ERR: CHROOT is not a valid directory! ('.$this->chroot.')');

        $this->chroot = $this->sanitizePath($this->chroot);
        
        $this->cd($this->chroot);
        $this->cd($startingDir);
    }


    /**
     * Set the current OS
     */
    private function setOsSpecifics()  {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            $this->OS = 'windows';
        else
            $this->OS = 'linux';
    }


    /**
     * Escape a path that's going to be used with system() command
     *
     * @param  string  $path  Path to be escaped
     *
     * @return  string  Escaped path enclosed in '
     */
    public function escapePath($path) {

        // $path = str_replace("'", "\'", $path);
        // return "'".$path."'";

        return escapeshellarg($path);
    }


    /**
     * Check if the given path is absolute
     *
     * @param  string  $path  Absolute/Relative path
     *
     * @return  boolean  true if the given path is absolute
     */
    public function isAbsolutePath($path) {
        if( $this->OS == 'windows' )
            return preg_match('/^[A-Z]\:/', $path);
        else
            return strpos($path, '/') === 0;
                
    }


    /**
     * Get absolute path
     *
     * @param  string  $path  Absolute/Relative path
     *
     * @return  string  Absolute path
     */
    public function absolutePathOf($path = './') {
        return $this->isAbsolutePath($path) ? $path : $this->pwd.'/'.$path;
    }


    /**
     * Check if the given path respects the chroot limitation
     *
     * @param  string  $path  Given path
     *
     * @return  boolean  true if the given path is ok
     */
    public function pathInChroot($path = './') {
        return strpos($path, $this->chroot) === 0;
    }


    /**
     * Check if given path is valid
     *
     * @param  string  $path  Absolute/Relative path
     *
     * @return  boolean  true if the given path is valid
     */
    public function isValidPath($path = NULL) {
        return $this->isValidDir($path) || $this->isValidFile($path);
    }


    /**
     * Check if given path is a valid file
     *
     * @param  string  $path  Absolute/Relative path
     *
     * @return  boolean  true if the given path is valid
     */
    public function isValidFile($path = NULL) {
        clearstatcache();
        return $path !== NULL && is_file( $this->absolutePathOf($path) );
    }


    /**
     * Check if given path is a valid directory
     *
     * @param  string  $path  Absolute/Relative path
     *
     * @return  boolean  true if the given path is valid
     */
    public function isValidDir($path = NULL) {
        clearstatcache();
        return $path !== NULL && is_dir( $this->absolutePathOf($path) );
    }


    /**
     * Sanitize given path
     *   - Make it absolute
     *   - Make sure it's chrooted
     *   - Make sure it's valid
     *   - Clean text
     *
     * @param  string  $path  Absolute/Relative path
     *
     * @return  string  Correct path string
     */
    public function sanitizePath($path = './') {

        $path = $this->absolutePathOf($path);

        if( !$this->pathInChroot($path) )
            return $this->pwd;

        $path = preg_replace('/\/+/', '/', $path);
        $path = preg_replace('/\/\.\//', '/', $path);
        $path = preg_replace('/\/$/', '', $path);

        if( strpos($path, $this->chroot.'/..') === 0 )
            return $this->pwd;

        $newPath = $path;
        do {
            $path = $newPath;
            $newPath = preg_replace('/\/[a-zA-Z0-9\.\ \-\_]+\/\.\.\//', '/', $path);

        } while( $newPath != $path );
        $newPath = preg_replace('/\/[a-zA-Z0-9\.\ \-\_]+\/\.\.$/', '', $newPath);
        $newPath = preg_replace('/\/\.$/', '', $newPath);

        return $newPath;
    }


    /**
     * Change current directory to the given path
     *
     * @param  string  $path  New path
     */
    public function cd($path = NULL) {
        if( $this->isValidDir($path) )
            $this->pwd = $this->sanitizePath($path);
    }


    /**
     * Check if the given basename must be excluded by the $hideMatches patterns
     *
     * @param  string  $basename  File/Folder name
     *
     * @return  boolean  true if it must be excluded
     */
    protected function isHidden($basename) {
        foreach( $this->hideMatches AS $pattern )
            if( preg_match($pattern, $basename) )
                return true;
        return false;
    }


    /**
     * Returns the current location
     *
     * @return  string  Current path ($this->pwd)
     */
    public function pwd() {
        return $this->pwd;
    }
    

    /**
     * List directory contents
     *
     * @param  string  $path  Directory path
     *
     * @return  array  Directory contents (files and directories are separated and sorted)
     */
    public function ls($path = './') {
        clearstatcache();

        if( !$this->isValidDir($path) )
            return array();

        $path = $this->absolutePathOf($path);

        $handle = @opendir($path);

        if( !$handle )
            return array();

        $path .= '/';

        $contents = array(
            'd' => array(),
            'f' => array()
            );
        while( $filename = readdir($handle) ) { // ( $filename = readdir($handle) ) !==  false

            if( $this->isHidden($filename) )
                continue;

            if( is_file( $path.'/'.$filename ) )
                $contents['f'][] = $filename;
            else
                $contents['d'][]  = $filename;
        }

        closedir($handle);

        asort($contents['f'], SORT_NATURAL);
        asort($contents['d'],  SORT_NATURAL);

        return $contents;
    }


    /**
     * List subdirectories
     *
     * @param  string  $path  Directory path
     *
     * @return  array  Subdirectories
     */
    public function ls_dirs($path = './') {
        $ls = $this->ls($path);
        return $ls['d'];
    }


    /**
     * List files from given path
     *
     * @param  string  $path  Directory path
     *
     * @return  array  Files
     */
    public function ls_files($path = './') {
        $ls = $this->ls($path);
        return $ls['f'];
    }


    /**
     * Transform the given string to a valid file name
     *
     * @param  string  $filename  Unsafe file name
     *
     * @return  string  Safe file name
     */
    public function safeFilename($filename) {
        return preg_replace('/[^0-9a-zA-Z]/', '_', $filename);
    }


    /**
     * Delete the given folder/file
     *
     * @param  string   $path     Path to be removed
     * @param  boolean  $preview  true (echo the system command), false (actually execute the system command)
     *
     * @return  mixed   true (successfully removed), false (problem occured), string (preview command)
     */
    public function rm($path, $preview = true) {

        if( !$this->isValidPath($path) )
            return false;

        $path = $this->sanitizePath($path);

        $cmd = 'rm -Rf '.$this->escapePath($path);

        if( $preview )
            return $cmd;

        ob_start();
        system($cmd);
        ob_get_clean();

        return !$this->isValidPath($path);
    }


    /**
     * Return the last $lines lines of a file
     *
     * @param  string   $filepath  File path
     * @param  integer  $lines     Number of lines
     * @param  boolean  $adaptive  Adaptive/Fixed buffer
     *
     * @return  string   The last $lines lines of the file
     */
    function tail($filepath, $lines = 1, $adaptive = true) {

        // Open file
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;

        // Sets buffer size
        if( !$adaptive ) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

        // Jump to last character
        fseek($f, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") $lines -= 1;
        
        // Start reading
        $output = '';
        $chunk = '';

        // While we would like more
        while( ftell($f) > 0 && $lines >= 0 ) {

            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;

            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while( $lines++ < 0 ) {

            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }

        // Close file and return
        fclose($f);
        return trim($output);

    }

}

}

?>