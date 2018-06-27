<?php
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Datastore\DatastoreClient;

class Member extends CI_Controller {
    /**
     * バケット名
     */
    private const GCS_BUCKET_NAME = 'parts-testing-svc';

    /**
     * オブジェクト名
     */
    private const GCS_OBJECT_NAME = 'parts_test.html';

    /**
     * 年齢
     * @todo:dataset化
     */
    private const AGE_INFO_LIST = [
            ['value' => '10', 'label' => '10歳～19歳'],
            ['value' => '20', 'label' => '20歳～29歳'],
            ['value' => '30', 'label' => '30歳～39歳'],
            ['value' => '40', 'label' => '40歳～49歳'],
            ['value' => '50', 'label' => '50歳～'],
        ];

    /**
     * 性別
     * @todo:dataset化
     */
    private const GENDER_INFO_LIST = [
            ['value' => 'm', 'label' => '男性'],
            ['value' => 'f', 'label' => '女性'],
        ];

    /**
     * コンストラクタ
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->load->library(['gcp', 'twig']);
    }

    /**
     * Index
     */
    public function index()
    {
        // 仮データ
        $member = [
            'name' => '山田 太郎',
            'email' => 'yama@cevcsfesbdv.com',
            'age' => '20',
            'gender' => 'm',
            'memo' => 'ｍｅｍｏ５６７８９０１２３４５６７８９０１２３４５６７８９０',
        ];

        // View(Twig)
        $this->twig->view('member', [
                'member' => $member,
                'age_info_list' => self::AGE_INFO_LIST,
                'gender_info_list' => self::GENDER_INFO_LIST
        ]);
    }

    /**
     * submit
     */
    public function submit()
    {
        // POST→member
        $member = [
            'name' => $this->input->post('name', TRUE),
            'email' => $this->input->post('email', TRUE),
            'age' => $this->_get_label($this->input->post('age', TRUE), self::AGE_INFO_LIST),
            'gender' => $this->_get_label($this->input->post('gender', TRUE), self::GENDER_INFO_LIST),
            'memo' => $this->input->post('memo', TRUE),
        ];

        // View(Twig)
        $this->twig->view('member_complete', ['member' => $member]);
    }

    /**
     * ラベル取得
     * @param string $value 値
     * @param array $info_list リスト
     * @return string ラベル
     */
    private function _get_label(string $value, array $info_list) : string
    {
        $ret = '';
        foreach ($info_list as $info) {
            if (isset($info['value']) && isset($info['label']) && $info['value'] === $value) {
                $ret = $info['label'];
                break;
            }
        }
        return $ret;
    }
}
