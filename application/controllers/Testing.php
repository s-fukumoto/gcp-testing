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
        } else {
            $accessInfo = $this->gcp->datastore->entity($key);
        }
        $accessInfo['value'] = $count;
        $this->gcp->datastore->upsert($accessInfo);

        // Log 書き込み(log_message)
        log_message('error', '*** LOG TEST [log_message] *** (ERROR)');
        log_message('warning', '*** LOG TEST [log_message] *** (WARNING)');
        log_message('debug', '*** LOG TEST [log_message] *** (DEBUG)');
        log_message('info', '*** LOG TEST [log_message] *** (INFO)');

        // Log 書き込み(Controllerで使う場合こちらも可)
        // こちらは、配列形式のメッセージもOK(stackdriverのjsonPayloadに対応)
        $this->log->error('*** LOG TEST [log class] *** (ERROR)');
        $this->log->logger('other-app')->error('*** LOG TEST [log class] *** (ERROR - other-app)');
        $this->log->warning('*** LOG TEST [log class] *** (WARNING)');
        $this->log->debug('*** LOG TEST [log class] *** (DEBUG)');
        $this->log->info('*** LOG TEST [log class] *** (INFO)');
        $test_data = [
            'id' => 123,
            'name' => 'abc',
            'item' => ['ZAQ', 'XSW', 'CDE'],
        ];
        $this->log->debug(['msg' => '*** LOG TEST [log class] *** (DEBUG - json)', 'data' => $test_data]);

        // ここからother-appに書き込む
        $log = $this->log->logger('other-app');
        $log->debug('*** LOG TEST [log class] *** (DEBUG - other-app)');
        $log->info('*** LOG TEST [log class] *** (INFO - other-app)');


        // View
        $this->load->view('testing', ['contents' => $contents ?? '', 'access_count' => (float)$count]);
    }
}
