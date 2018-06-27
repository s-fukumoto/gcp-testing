<?php
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Datastore\DatastoreClient;

class Testing extends CI_Controller {
    /**
     * バケット名
     */
    private const GCS_BUCKET_NAME = 'parts-testing-svc';

    /**
     * オブジェクト名
     */
    private const GCS_OBJECT_NAME = 'parts_test.html';

    /**
     * コンストラクタ
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('gcp');
    }

    /**
     * Index Page for this controller.
     */
    public function index()
    {
        // Cloud Strage から静的パーツを取り込む
        $contents = '';
        $bucket = $this->gcp->storage->bucket(self::GCS_BUCKET_NAME);
        if ($bucket->exists()) {
            $object = $bucket->object(self::GCS_OBJECT_NAME);
            if ($object->exists()) {
                $contents = $object->downloadAsString();
            } else {
                $contents = '<p>Non Object!! ('.self::GCS_OBJECT_NAME.')</p>';
            }
        } else {
            $contents = '<p>Non Bucket!! ('.self::GCS_BUCKET_NAME.')</p>';
        }

        // Datastore へ閲覧数書き込み
        $count = 1;
        $key = $this->gcp->datastore->key('AccessInfo', 'count');
        $accessInfo = $this->gcp->datastore->lookup($key);
        if ($accessInfo) {
            $count = $accessInfo['value'] + 1;
            $accessInfo['value'] = $count;
            $this->gcp->datastore->update($accessInfo);
        } else {
            $accessInfo = $this->gcp->datastore->entity($key);
            $accessInfo['value'] = 1;
            $this->gcp->datastore->insert($accessInfo);
        }

        // View
        $this->load->view('testing', ['contents' => $contents ?? '', 'access_count' => (float)$count]);
    }
}
