<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Google Cloud Platform 用設定
 */
$config['keyFilePath'] = realpath(APPPATH.'../gcloud/key/app-service.json'); // アクセスキーファイル
$config['datatoreNameSpace'] = 'data_'.date('Ymd'); // Datastoreの名前空間指定
