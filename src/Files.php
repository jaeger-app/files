<?php
/**
 * Jaeger
 *
 * @copyright	Copyright (c) 2015-2016, mithra62
 * @link		http://jaeger-app.com
 * @version		1.0
 * @filesource 	./Files.php
 */
namespace JaegerApp;

use JaegerApp\Exceptions\FileException;

/**
 * Jaeger - Files Object
 *
 * Contains methods for interacting with the file system and files directly
 *
 * @package Files
 * @author Eric Lamb <eric@mithra62.com>
 */
class Files
{

    /**
     * Contains the details for any file collections
     * 
     * @var array
     */
    private $file_data = array();

    /**
     * Returns the file data collection
     * 
     * @return \mithra62\array
     */
    public function getFileData()
    {
        return $this->file_data;
    }

    /**
     * Sets the file to store for later
     * 
     * @param mixed $file
     *            The file to set
     * @param bool $reset
     *            Whether the counter should be reset when set
     * @return \mithra62\Files
     */
    public function setFileData($file = false, $reset = false)
    {
        if ($reset) {
            $this->file_data = array();
            if ($file) {
                $this->file_data[] = $file;
            }
        } else {
            $this->file_data[] = $file;
        }
        return $this;
    }

    /**
     * Writes data to $path creating it if it doesn't exist
     * 
     * @param string $path
     *            The path to the file
     * @param string $data
     *            The data to write
     * @param string $mode
     *            The access we need to stream the file
     * @return boolean
     */
    public function write($path, $data, $mode = 'w+')
    {
        if (! $fp = @fopen($path, $mode)) {
            throw new FileException('Can\'t open the file for writing! ' . $path);
        }
        
        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
    }

    /**
     * Returns the contents of a file
     * 
     * @param string $file
     *            The path to the file to read
     * @return boolean|string
     */
    public function read($file)
    {
        if (! file_exists($file)) {
            return FALSE;
        }
        
        return file_get_contents($file);
    }

    /**
     * Removes a file from the file system
     * 
     * @param string $path            
     * @return bool
     */
    public function delete($path)
    {
        if (file_exists($path) && is_writable($path)) {
            return unlink($path);
        }
    }

    /**
     * Removes all the files in the given $path
     *
     * Files have to be writable and/or owned by the system user in order to be processed
     * 
     * @param string $path            
     * @param string $del_dir            
     * @param number $level            
     * @param array $exclude            
     * @return boolean
     */
    public function deleteDir($path, $del_dir = false, $level = 0, $exclude = array())
    {
        // Trim the trailing slash
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        
        if (! $current_dir = @opendir($path)) {
            return false;
        }
        
        $exclude[] = '.';
        $exclude[] = '..';
        
        while (false !== ($filename = @readdir($current_dir))) {
            if (! in_array($filename, $exclude)) {
                if (is_dir($path . DIRECTORY_SEPARATOR . $filename)) {
                    if (substr($filename, 0, 1) != '.') {
                        $this->deleteDir($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $level + 1, $exclude);
                    }
                } else {
                    $this->delete($path . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }
        @closedir($current_dir);
        
        if ($del_dir == true && $level >= 0) {
            return @rmdir($path);
        }
        
        return true;
    }

    /**
     * Format a number of bytes into a human readable format.
     * Optionally choose the output format and/or force a particular unit
     * 
     * @param string $val
     *            The number to format
     * @param number $digits
     *            How many digits to display
     * @param string $mode
     *            Either SI or EIC to determine either 1000 or 1024 bytes
     * @param string $bB
     *            Whether to use b or B formatting
     * @return string
     */
    public function filesizeFormat($val, $digits = 3, $mode = "IEC", $bB = "B")
    {
        $si = array(
            "",
            "k",
            "M",
            "G",
            "T",
            "P",
            "E",
            "Z",
            "Y"
        );
        $iec = array(
            "",
            "Ki",
            "Mi",
            "Gi",
            "Ti",
            "Pi",
            "Ei",
            "Zi",
            "Yi"
        );
        switch (strtoupper($mode)) {
            case "SI":
                $factor = 1000;
                $symbols = $si;
                break;
            case "IEC":
                $factor = 1024;
                $symbols = $iec;
                break;
            default:
                $factor = 1000;
                $symbols = $si;
                break;
        }
        switch ($bB) {
            case "b":
                $val *= 8;
                break;
            default:
                $bB = "B";
                break;
        }
        for ($i = 0; $i < count($symbols) - 1 && $val >= $factor; $i ++) {
            $val /= $factor;
        }
        $p = strpos($val, ".");
        if ($p !== false && $p > $digits) {
            $val = round($val);
        } elseif ($p !== false) {
            $val = round($val, $digits - $p);
        }
        
        return round($val, $digits) . " " . $symbols[$i] . $bB;
    }

    /**
     * Given the full system path to a file it will force the "Save As" dialogue of browsers
     * 
     * @param unknown $filename            
     * @param string $force_name            
     */
    public function fileDownload($filename, $force_name = FALSE)
    {
        // required for IE, otherwise Content-disposition is ignored
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        
        $file_extension = strtolower(substr(strrchr($filename, "."), 1));
        
        if ($filename == "") {
            throw new FileException('Download file NOT SPECIFIED! ' . $filename);
        } elseif (! file_exists($filename)) {
            throw new FileException('Download file NOT Found! ' . $filename);
        }
        ;
        switch ($file_extension) {
            case "pdf":
                $ctype = "application/pdf";
                break;
            case "exe":
                $ctype = "application/octet-stream";
                break;
            case "zip":
                $ctype = "application/zip";
                break;
            case "doc":
                $ctype = "application/msword";
                break;
            case "xls":
                $ctype = "application/vnd.ms-excel";
                break;
            case "ppt":
                $ctype = "application/vnd.ms-powerpoint";
                break;
            case "gif":
                $ctype = "image/gif";
                break;
            case "png":
                $ctype = "image/png";
                break;
            case "rtf":
                $ctype = "text/rtf";
                break;
            case "jpeg":
            case "jpg":
                $ctype = "image/jpg";
                break;
            default:
                $ctype = "application/zip";
        }
        
        $filesize = filesize($filename);
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: $ctype");
        header("Content-Disposition: attachment; filename=\"" . ($force_name ? $force_name : basename($filename)) . "\";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . $filesize);
        
        if ($fd = fopen($filename, "r")) {
            while (! feof($fd)) {
                $buffer = fread($fd, 1024 * 8);
                echo $buffer;
            }
        }
        fclose($fd);
        exit();
    }

    /**
     * Returns all the files in a directory
     * 
     * @param string $source_dir
     *            The path to the directory
     * @param boolean $include_path
     *            Whether to include the full path or jsut file name
     * @param boolean $recursion
     *            Whether to drill down into further directories
     * @return array|boolean
     */
    public function getFilenames($source_dir, $include_path = true, $recursion = true)
    {
        $fp = @opendir($source_dir);
        if ($fp) {
            // reset the array and make sure $source_dir has a trailing slash on the initial call
            if ($recursion === false) {
                $this->setFileData(false, true);
                $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
            
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($source_dir . $file) && strncmp($file, '.', 1) !== 0) {
                    $this->getFilenames($source_dir . $file . DIRECTORY_SEPARATOR, $include_path, TRUE);
                } elseif (strncmp($file, '.', 1) !== 0) {
                    $filedata = ($include_path == TRUE) ? $source_dir . DIRECTORY_SEPARATOR . $file : $file;
                    $this->setFileData($filedata);
                }
            }
            
            return $this->getFileData();
        } else {
            return false;
        }
    }

    /**
     * Copies a directory to another
     * 
     * @param string $dir
     *            The path to copy
     * @param string $destination
     *            The path to save all the files to
     */
    public function copyDir($dir, $destination)
    {
        foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(trim($dir), \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
            
            if ($item->isDir()) {
                mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), '0777', true);
            } else {
                copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }
}