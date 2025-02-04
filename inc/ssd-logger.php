<?php

    /**
     * Logging class:
     * - contains lfile, lwrite and lclose public methods
     * - lfile sets path and name of log file
     * - lwrite writes message to the log file (and implicitly opens log file)
     * - lclose closes log file
     * - first call of lwrite method will open log file implicitly
     * - message is written with the following format: [d/M/Y:H:i:s] (script name) message
     */
    if (!class_exists("CNSD_Logger")) {

        class CNSD_Logger {

// declare log file and file pointer as private properties
            private $fp;
            public $disableLogger = FALSE;
            public $log_file; //log file
            //array of default emails which would recieve all error emails

// set log file
            public function lfile($path) {
                $this->log_file = $path;
            }

// write message to the log file
            public function lwrite($message, $is_error = false) {
                if ($this->disableLogger === TRUE) {
                    return FALSE;
                }
// if file pointer doesn't exist, then open log file
                if (!is_resource($this->fp)) {
                    $this->lopen();
                }

// define script name
                $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
// define current time and suppress E_WARNING if using the system TZ settings
// (don't forget to set the INI setting date.timezone)
                $time = @date('[d/M/Y:H:i:s]');
// write current time, script name and message to the log file
                fwrite($this->fp, "$time ($script_name) $message" . PHP_EOL);
            }

// close log file (it's always a good idea to close a file when you're done with it)
            public function lclose() {
                fclose($this->fp);
            }

// open log file (private method)
            private function lopen() {
// in case of Windows set default log file
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $log_file_default = 'c:/php/logfile.txt';
                }
// set default log file for Linux and other systems
                else {
                    $log_file_default = '/tmp/logfile.txt';
                }
// define log file from lfile method or use previously set default
                $lfile = $this->log_file ? $this->log_file : $log_file_default;
// open log file for writing only and place file pointer at the end of the file
// (if the file does not exist, try to create it)
                $this->fp = fopen($lfile, 'a');
            }

            /**
             * 
             * @param type $type - 0 for error , 1 for success
             * @param type $record - recordType of the object 
             * @param type $message - log message
             */

        }
}