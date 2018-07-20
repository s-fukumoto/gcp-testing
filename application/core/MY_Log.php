<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Google\Cloud\Logging\LoggingClient;

/**
 * Logging Class (GCP Stackdriver Logging)
 */
class MY_Log extends CI_Log {

	/**
	 * Predefined logging levels
	 *
	 * @var array
	 */
	protected $_levels = array('ERROR' => 1, 'WARNING' => 2, 'DEBUG' => 3, 'INFO' => 4, 'ALL' => 5);

	/**
	 * Stackdriver Logging の使用可否
	 *
	 * @var	bool
	 */
	protected $_use_stackdriver = FALSE;

	/**
	 * Google ProjectId
	 *
	 * @var	string
	 */
	protected $_project_id = '';

	/**
	 * Stackdriver Logger 名称
	 *
	 * @var	string
	 */
	protected $_logger_name = 'my-app';

	/**
	 * Stackdriver Logging インスタンス
	 *
	 * @var	Google\Cloud\Logging\LoggingClient
	 */
	protected $_logging = NULL;

	/**
	 * Stackdriver Logger インスタンス
	 *
	 * @var	array(Google\Cloud\Logging\Logger)
	 */
	protected $_logger = [];

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();

		$config =& get_config();

		if ( ! empty($config['gcp_log_use_stackdriver']) && is_bool($config['gcp_log_use_stackdriver']))
		{
			$this->_use_stackdriver = $config['gcp_log_use_stackdriver'];
		}
		if ( ! empty($config['gcp_project_id']))
		{
			$this->_project_id = $config['gcp_project_id'];
		}
		if ( ! empty($config['gcp_logger_name']))
		{
			$this->_logger_name = $config['gcp_logger_name'];
		}

		// Stackdriver Logging,Logger 生成
		if ($this->_use_stackdriver === TRUE) {
			$this->_logging = new LoggingClient(empty($this->_project_id) ? [] : ['projectId' => $this->_project_id]);
			//$this->_logger[$this->_logger_name] = $this->_logging->logger($this->_logger_name);
			$this->_logger[$this->_logger_name] = $this->_logging->psrBatchLogger($this->_logger_name);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * logger 生成
	 * 
	 * 例1.新たなLoggerで継続して出力したいとき
	 * 
	 *     $log = $this->logger('new-logger');
	 *     $log->error('このエラーはnew-loggerに出力');
	 *     $log->info('この情報はnew-loggerに出力');
	 * 
	 * 
	 * 例2.一時的に新たなLoggerで出力したいとき
	 * 
	 *     $this->logger('new-logger')->error('このエラーだけnew-loggerに出力');
	 *
	 * 
	 * @param  string $logger_name 生成するLogger名
	 * @return MY_Log
	 */
	public function logger($logger_name)
	{
		// Loggingが無い、もしくは、Stackdriver を使用しない場合は、なにもせず返す
		if (is_null($this->_logging) || $this->_use_stackdriver === FALSE) {
			return $this;
		}

		// 生成済みでなければ生成する
		if ( ! isset($this->_logger[$logger_name])) {
			//$this->_logger[$logger_name] = $this->_logging->logger($logger_name);
			$this->_logger[$logger_name] = $this->_logging->psrLogger($logger_name);
		}

		// 名称を変更して返す
		$log = clone $this;
		$log->set_logger_name($logger_name);
		return $log;
	}

	// --------------------------------------------------------------------

	/**
	 * Logger名設定
	 *
	 * @param  string $logger_name 設定するLogger名
	 */
	public function set_logger_name($logger_name)
	{
		$this->_logger_name = $logger_name;
	}

	// --------------------------------------------------------------------

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string			$level		The error level: 'error', 'debug' or 'info'
	 * @param	string|array	$msg		The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @return	bool
	 */
	public function write_log($level, $msg)
	{
		// Loggingが無い、もしくは、Stackdriver を使用しない場合は、標準のLog出力を使用する
		if (is_null($this->_logging) || $this->_use_stackdriver === FALSE) {
			$msg = is_array($msg) ? json_encode($msg) : $msg;
			return parent::write_log($level, $msg);
		}

		if ($this->_enabled === FALSE)
		{
			return FALSE;
		}

		$level = strtoupper($level);

		if (( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
			&& ! isset($this->_threshold_array[$this->_levels[$level]]))
		{
			return FALSE;
		}

		// log書き込み
		$logger = $this->_logger[$this->_logger_name];
		//$logger->write($logger->entry($msg), ['severity' => $level]);
		$msg = is_array($msg) ? json_encode($msg) : $msg;
		$logger->log($level, $msg);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * error
	 *
	 * @param	string|array $msg  The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @return	bool
	 */
	public function error($msg)
	{
		return self::write_log('ERROR', $msg);
	}

	// --------------------------------------------------------------------

	/**
	 * warning
	 *
	 * @param	string|array $msg  The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @return	bool
	 */
	public function warning($msg)
	{
		return self::write_log('WARNING', $msg);
	}

	// --------------------------------------------------------------------

	/**
	 * debug
	 *
	 * @param	string|array $msg  The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @return	bool
	 */
	public function debug($msg)
	{
		return self::write_log('DEBUG', $msg);
	}

	// --------------------------------------------------------------------

	/**
	 * info
	 *
	 * @param	string|array $msg  The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @return	bool
	 */
	public function info($msg)
	{
		return self::write_log('INFO', $msg);
	}
}
