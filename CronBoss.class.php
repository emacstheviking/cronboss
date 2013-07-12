<?php
/**
 * @file CronBoss.class.php
 * @author Sean Charles <sean@objitsu.com>
 *
 * This class allows you to read and write the "crontab" file for any
 * account that your script has permissions to do so on your server.
 *
 * Once loaded, use the <tt>crontab()</tt> method to be given a list
 * of Strings that contain the file. What you do with this is up to
 * you put remember to save your modified crontab by calling the
 * method <tt>crontab($foo)</tt> where foo is the modified
 * Array<String> data.
 *
 * Here is a short example:

 <pre>
 $cron = new CronBoss();
 $jobs = $cron->crontab();

 //... modify $jobs...

 $jobs[] = '#my new entry';
 if ( $cron->crontab($jobs)->save()) {
 	echo "OK";
 }
 else {
 	//echo "FAILED", $cron->response();
 }
 </pre>

 * NOTES: For me, for now, this is as simple as I need but I have
 * planned some extensions. If you might find them useful get in
 * touch, it might just be enough to make me do them!

 * - add() / remove() / find() methods
 * - implement PHP Array/Iterable etc interfaces for slicker integration
 * - use proc_open() to reap stderr when things go wrong!
 * - implement MARKED SECTION so that existing cron is left as-is and
     any items added from here are maintained at the end of the file
     within a tagged comment block i.e.

     #CRONBOSS+
	 #CRONBOSS-

 *
 * DISCLAIMER HERE: If it works, I wrote it. If it breaks then it's
 * not my fault etc. Usual rules apply about using other peoples code!
 */
class CronBoss
{
	/**
	 * @brief Edit this to reflect your system installation.
	 */
	const CRONTAB = '/usr/bin/crontab';


	protected $userAccount; /*< User account to access the cron table for         */
	protected $cronEntries; /*< The internal Array<String> of the current crontab */
	protected $cronOutput;  /*< exec() output from last update attempt            */



	/**
	 * Construct a CronBoss instance around running process crontab.
	 *
	 * @param String $userAccuont contains an optional user account to
	 * modify which crontab is loaded. The process running this class
	 * MUST have the relevant permissions to be able to do this.
	 */
	function __construct( $userAccount = null )
	{
		$this->userAccount = $userAccount;
		$this->cronOutput  = null;
		$this->cronEntries = $this->load( $userAccount );
	}



	/**
	 * Load the current crontab file.
	 *
	 * @param String $userAccount contains the name of the user account
	 * for which the crontab is to be loaded. The process running this
	 * script is assumed to be in posession of all of the necessary
	 * privileges for this to work.
	 *
	 * @return Array containing the result of executing the "crontab -l"
	 * command with the optional user account name or NULL if it fails
	 * to execute the crontab command. Each line of the array will
	 * contain one line of the crontab output including blank lines.
	 *
	 * @note If your PHP is running in safe mode then this won't work as
	 * shell_exec() will be non-functional in that mode.
	 */
	protected function load( $userAccount = null )
	{
		$user = $userAccount ? "-u {$userAccount}" : '' ;

		exec(
			sprintf( "%s -l {$user}", self::CRONTAB ),
			$output,
			$status
		);

		return (0 == $status) ? $output : null;
	}



	/**
	 * Get or set the crontable data.
	 *
	 * @param Array $jobs contains the new list of Strings to be
	 * written to the crontable. If NULL is passed then it is
	 * interpreted as a read request for the current set of Strings.
	 *
	 * NOTE: THE APPLICATION ASSUMES FULL RESPNSIBILITY FOR WRITING
	 * NONSENSE OR NOT.
	 *
	 * @return $this when NULL is passed, esle Array<String>
	 */
	function crontab($jobs = null)
	{
		if (is_array($jobs)) {
			$this->cronEntries = $jobs;
		}
		else if (is_null($jobs)) {
			return $this->cronEntries;
		}
		return $this;
	}



	/*! @brief Answers last esxec() output */
	function response() { return $this->output; }



	/**
	 * Save a new crontab file.
	 *
	 * @param String $newCron is the complete new crontab file contents
	 * as a string. It is assumed that the string contains newlines in
	 * the appropriate places. Absolutely no checks are made on the
	 * contents of this string so it is completely possible that you
	 * might totally trash your existing crontab. Not my fault.
	 *
	 * @param String $userAccount This optional variable contains the
	 * user account for which the crontab is to be updated. This assumes
	 * that the process executing this code has all of the necessary
	 * privileges to do this.
	 *
	 * @return bool Answers true if all three of the following
	 * operations took place: the contents are written to a temp file,
	 * the temp file is closed and the new crontab was installed via a
	 * system call. If "false" is returned then you must assume that the
	 * old crontab contents are still in place.
	 */
	public function save( $userAccount = null )
	{
		$status      = false;
		$cronUpdated = false;
		$tmpFile     = tempnam( sys_get_temp_dir(), 'cronboss' );

		if ( $fh = fopen( $tmpFile, 'w' ))
		{
			$newCron = implode(PHP_EOL, $this->cronEntries);

			$cronUpdated =
				fwrite( $fh, rtrim( $newCron ) . PHP_EOL )
				&&
				fclose( $fh );

			if ($cronUpdated)
			{
				$user = $userAccount ? "-u " . escapeshellarg($userAccount) : '' ;

				exec(
					sprintf( "%s {$user} {$tmpFile}", self::CRONTAB ),
					$output,
					$status );

				unlink( $tmpFile );

				$this->output = implode("\n", $output);
				$cronUpdated = ( 0 == $status );
			}
		}
		return $cronUpdated;
	}
}
