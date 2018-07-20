<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Logging\LoggingClient;
/**
 * Google Cloud Platform ライブラリ
 *
 */
class Gcp {

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
     * Logging
     * @var Google\Cloud\Logging\LoggingClient
     */
    public $logging;

    /**
     * 共通設定
     * @var array
     */
    private $_conf;

    /**
     * constructor
     */
    public function __construct() {
        $this->_conf = [
            'projectId' => config_item('gcp_project_id') ?? ''
        ];

        $storage_conf = $this->_conf;
        $this->storage = new StorageClient($storage_conf);

        $datastore_conf = $this->_conf + [
            'namespaceId' => config_item('gcp_datatore_name_space') ?? ''
        ];
        $this->datastore = new DatastoreClient($datastore_conf);

        $logging_conf = $this->_conf;
        $this->logging = new LoggingClient($logging_conf);
    }
}
