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
	 * Stackdriver Logger 名称の接頭語
	 *
	 * @var	string
	 */
	protected $_logger_name_prefix = 'my-app';

	/**
	 * Stackdriver Logging インスタンス
	 *
	 * @var	Google\Cloud\Logging\LoggingClient
	 */
	protected $_logging = NULL;

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
		if ( ! empty($config['gcp_logger_name_prefix']))
		{
			$this->_logger_name_prefix = $config['gcp_logger_name_prefix'];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string			$level				The error level: 'error', 'debug' or 'info'
	 * @param	string|array	$msg				The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @param	string|null		$log_name_prefix	Stackdriver の log名称を一時的に変更したい場合に指定
	 * @return	bool
	 */
	public function write_log($level, $msg, $log_name_prefix = NULL)
	{
		// Stackdriver Logging インスタンス生成
		if ($this->_logging === NULL && $this->_use_stackdriver === TRUE) {
			$this->_logging = new LoggingClient(empty($this->_project_id) ? [] : ['projectId' => $this->_project_id]);
		}

		// Loggerが無い、もしくは、Stackdriver を使用しない場合は、標準のLog出力を使用する
		if ($this->_logging === NULL || $this->_use_stackdriver === FALSE) {
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

		// log名称（stackdriverでフィルターできる）
		$logger_name = $log_name_prefix ?? $this->_logger_name_prefix;
		$logger_name .= '-'.strtolower($level);

		/*
		$logger = $this->_logging->psrLogger($logger_name);
		*/
		$logger = $this->_logging->logger($logger_name);

		/*
		try {
			$logger->log($level, $message);
		} catch (InvalidArgumentException $e) {
			return FALSE;
		}
		*/
		$entry = $logger->entry($msg);
		$logger->write($entry, ['severity' => $level]);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * error
	 *
	 * @param	string|array	$msg				The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @param	string|null		$log_name_prefix	Stackdriver の log名称を一時的に変更したい場合に指定
	 * @return	bool
	 */
	public function error($msg, $log_name_prefix = NULL)
	{
		return self::write_log('ERROR', $msg, $log_name_prefix);
	}

	// --------------------------------------------------------------------

	/**
	 * warning
	 *
	 * @param	string|array	$msg				The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @param	string|null		$log_name_prefix	Stackdriver の log名称を一時的に変更したい場合に指定
	 * @return	bool
	 */
	public function warning($msg, $log_name_prefix = NULL)
	{
		return self::write_log('WARNING', $msg, $log_name_prefix);
	}

	// --------------------------------------------------------------------

	/**
	 * debug
	 *
	 * @param	string|array	$msg				The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @param	string|null		$log_name_prefix	Stackdriver の log名称を一時的に変更したい場合に指定
	 * @return	bool
	 */
	public function debug($msg, $log_name_prefix = NULL)
	{
		return self::write_log('DEBUG', $msg, $log_name_prefix);
	}

	// --------------------------------------------------------------------

	/**
	 * info
	 *
	 * @param	string|array	$msg				The error message logにデータをJSONで出力する場合は連想配列で設定
	 * @param	string|null		$log_name_prefix	Stackdriver の log名称を一時的に変更したい場合に指定
	 * @return	bool
	 */
	public function info($msg, $log_name_prefix = NULL)
	{
		return self::write_log('INFO', $msg, $log_name_prefix);
	}
}
