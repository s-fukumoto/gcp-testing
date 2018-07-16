<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Datastore\DatastoreClient;
/**
 * Google Cloud Platform ライブラリ
 *
 */
class Gcp {
    /**
     * config
     */
    const GOOGLE_CLOUD_CONFIG_FILE = 'gcloud';

    /**
     * Storege
     * @var Google\Cloud\Storage\StorageClient
     */
    public $storage;

    /**
     * Datastore
     * @var Google\Cloud\Datastore\DatastoreClient
     */
    public $datastore;

    /**
     * コントローラのインスタンス
     * @var CI_Controller
     */
    private $_ci;

    /**
     * 共通設定
     * @var array
     */
    private $_conf;

    /**
     * constructor
     */
    public function __construct() {
        $this->_ci =& get_instance();
        $this->_ci->config->load(self::GOOGLE_CLOUD_CONFIG_FILE, TRUE);
        $this->_conf = [
            'keyFilePath' => $this->_ci->config->item('keyFilePath', self::GOOGLE_CLOUD_CONFIG_FILE) ?? ''
        ];

        $storage_conf = $this->_conf;
        $this->storage = new StorageClient($storage_conf);

        $datastore_conf = $this->_conf + [
            'namespaceId' => 'data_'.date('Ymd')
        ];
        $this->datastore = new DatastoreClient($datastore_conf);
    }
}
