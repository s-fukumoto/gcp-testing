<?php
use Google\Cloud\Datastore\DatastoreClient;

class Session extends CI_Controller {
    /**
     * 期限が過ぎたセッション情報を解放する
     */
    public function release()
    {
        log_message('info', ' *** Session Release Batch Job Start ***');

        $this->load->library(['gcp']);

        $entity_name = config_item('sess_entity_name');
        $expiration = config_item('sess_expiration');

        $query = $this->gcp->datastore->query()
            ->kind($entity_name)
            ->filter('timestamp', '<', (time() - $expiration))
            ->keysOnly();

        $entities = $this->gcp->datastore->runQuery($query);

        if ($entities !== FALSE) {

            foreach ($entities as $k => $entity) {
                try {
                    $this->gcp->datastore->delete($entity->key());
                    log_message('debug', 'Session - release(): Datastore Delete Success (key: '.$entity->key().')');
                } catch (Exception $e) {
                    log_message('error', 'Session - release(): Datastore Delete Error!! (key: '.$entity->key().') :'.$e->getMessage());
                }
            }
        }

        log_message('info', ' *** Session Release Batch Job End ***');
    }
}
