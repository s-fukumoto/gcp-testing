<?php
use function GuzzleHttp\json_encode;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Session Datastore(Google cloud Pratform) Driver
 *
 * @package    CodeIgniter
 * @subpackage Libraries
 * @category   Sessions
 */
class CI_Session_datastore_driver extends CI_Session_driver implements SessionHandlerInterface {

    /**
     * Datastore instance
     *
     * @var Google\Cloud\Datastore\DatastoreClient
     */
    protected $_datastore;

    /**
     * Key prefix
     *
     * @var string
     */
    protected $_key_prefix = 'ci_session:';

    // ------------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $params Configuration parameters
     * @return void
     */
    public function __construct(&$params)
    {
        parent::__construct($params);

        if (empty($this->_config['save_path']))
        {
            log_message('error', 'Session: No Dataset save path configured.');
        }
        elseif ( ! file_exists($this->_config['save_path']))
        {
            log_message('error', 'Session: The configuration file of appointed Dataset does not exist. ('.$this->_config['save_path'].')');
        }

        if ($this->_config['match_ip'] === TRUE)
        {
            $this->_key_prefix .= $_SERVER['REMOTE_ADDR'].':';
        }

        $this->_config['entity_name'] = config_item('sess_entity_name');

        // 実行時設定
        ini_set('session.serialize_handler', (config_item('sess_serialize_handler') ?? 'php'));
    }

    // ------------------------------------------------------------------------

    /**
     * Open
     *
     * Sanitizes save_path and initializes connections.
     *
     * @param string $save_path Server path(s)
     * @param string $name Session cookie name, unused
     * @return bool
     */
    public function open($save_path, $name)
    {
        $this->_datastore = new Google\Cloud\Datastore\DatastoreClient([
            'keyFilePath' => $this->_config['save_path']
        ]);

        if ( ! isset($this->_datastore))
        {
            log_message('error', 'Session: A class definition of Datastore is not considered to be it. Please carry out package addition in composer. (https://github.com/GoogleCloudPlatform/google-cloud-php#google-cloud-datastore-ga)');
            return $this->_fail();
        }
 
        $this->php5_validate_id();

        return $this->_success;
    }

    // ------------------------------------------------------------------------

    /**
     * Read
     *
     * Reads session data and acquires a lock
     *
     * @param string $session_id Session ID
     * @return string Serialized session data
     */
    public function read($session_id)
    {
        if (isset($this->_datastore) && $this->_get_lock($session_id))
        {
            // Needed by write() to detect session_regenerate_id() calls
            $this->_session_id = $session_id;

            $key = $this->_datastore->key($this->_config['entity_name'], $this->_key_prefix.$session_id);
            $entity = $this->_datastore->lookup($key);
            $session_data = (string) $entity['data'];

            $this->_fingerprint = md5($session_data);
            return $session_data;
        }

        $this->_fingerprint = md5('');
        return $this->_fail();
    }

    // ------------------------------------------------------------------------

    /**
     * Write
     *
     * Writes (create / update) session data
     *
     * @param string $session_id Session ID
     * @param string $session_data Serialized session data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        if ( ! isset($this->_datastore))
        {
            return $this->_fail();
        }
        // Was the ID regenerated?
        elseif ($session_id !== $this->_session_id)
        {
            if ( ! $this->_release_lock() OR ! $this->_get_lock($session_id))
            {
                return $this->_fail();
            }

            $this->_fingerprint = md5('');
            $this->_session_id = $session_id;
        }

        $key = $this->_datastore->key($this->_config['entity_name'], $this->_key_prefix.$session_id);
        $entity = $this->_datastore->lookup($key) ?? $this->_datastore->entity($key);
        $entity['timestamp'] = time();
        $entity['data'] = $session_data;

        try {
            $this->_datastore->upsert($entity);

            $this->_fingerprint = md5($session_data);
            return $this->_success;

        } catch (Exception $e) {
            log_message('error', 'Session: Got DatastoreException on write(): '.$e->getMessage());
        }

        return $this->_fail();
    }

    // ------------------------------------------------------------------------

    /**
     * Close
     *
     * Releases locks and closes connection.
     *
     * @return bool
     */
    public function close()
    {
        if (isset($this->_datastore))
        {
            $this->_release_lock();
            if ( ! $this->_release_lock())
            {
                return $this->_fail();
            }

            $this->_datastore = NULL;
            return $this->_success;
        }

        return $this->_fail();
    }

    // ------------------------------------------------------------------------

    /**
     * Destroy
     *
     * Destroys the current session.
     *
     * @param string $session_id Session ID
     * @return bool
     */
    public function destroy($session_id)
    {
        if (isset($this->_datastore))
        {
            $key = $this->_datastore->key($this->_config['entity_name'], $this->_key_prefix.$session_id);
            $this->_datastore->delete($key);
            $this->_cookie_destroy();
            return $this->_success;
        }

        return $this->_fail();
    }

    // ------------------------------------------------------------------------

    /**
     * Garbage Collector
     *
     * Deletes expired sessions
     *
     * @param int $maxlifetime Maximum lifetime of sessions
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $query = $this->_datastore->query()
            ->kind($this->_config['entity_name'])
            ->filter('timestamp', '<', (time() - $maxlifetime))
            ->keysOnly();

        $entities = $this->_datastore->runQuery($query);

        if ($entities !== FALSE) {

            foreach ($entities as $k => $entity) {
                try {
                    $this->_datastore->delete($entity->key());
                    log_message('debug', 'Session: Delete was completed on gc(): key: '.$entity->key());
                } catch (Exception $e) {
                    log_message('error', 'Session: Got DatastoreException on gc() key: '.$entity->key().' :'.$e->getMessage());
                }
            }

            return $this->_success;
        }

        return $this->_fail();
    }

    // --------------------------------------------------------------------

    /**
     * Validate ID
     *
     * Checks whether a session ID record exists server-side,
     * to enforce session.use_strict_mode.
     *
     * @param string $id
     * @return bool
     */
    public function validateId($id)
    {
        $key = $this->_datastore->key($this->_config['entity_name'], $this->_key_prefix.$id);
        $entity = $this->_datastore->lookup($key);
        return ! empty($entity);
    }

    // ------------------------------------------------------------------------

    /**
     * Get lock
     *
     * Acquires an (emulated) lock.
     *
     * @param string $session_id Session ID
     * @return bool
     */
    protected function _get_lock($session_id)
    {
        $this->_lock = TRUE;
        return TRUE;
    }

    // ------------------------------------------------------------------------

    /**
     * Release lock
     *
     * Releases a previously acquired lock
     *
     * @return bool
     */
    protected function _release_lock()
    {
        if ($this->_lock)
        {
            $this->_lock = FALSE;
        }

        return TRUE;
    }
}
