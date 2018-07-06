# GCP検証プロジェクト ( PHP7 - CodeIgniter3 )
Google Cloud Platform のサービスをPHP環境にて検証するためのプロジェクトです。  
CodeIgniterのサンプルも兼ねてます。

---
## 検証環境
 - PHP 7.2
 - CodeIgniter 3.1.9
 - Google Cloud SDK 203.0.0

---
## 準備

1. **sampleコードを配置**  
    ```
    $ git clone https://github.com/s-fukumoto/gcp-testing.git
    ```

2. **サービスアカウントの作成とキー取得**  
    Local環境で実施する場合はAPIキーが必要になります。  
    本Projectではアカウントキー(JSON)を設置する方法にしています。  
    [公式手順](https://cloud.google.com/docs/authentication/getting-started?authuser=0&hl=ja)に沿って、JSONのキーをダウンロードし、以下のファイル名で配置します。
    ```
    $ cd gcp-testing 
    $ cp xxxxxx.json ./gcloud/key/app-service.json
    ```
    もし、Keyの格納先を変更したい場合は、
    ```bash
    $ vi gcp-testing/application/config/gcloud.php
    ```
    以下の設定を変更してください。
    ```php
    $config['keyFilePath'] = realpath(APPPATH.'../gcloud/key/app-service.json'); // アクセスキーファイル
    ```

3. **Local環境**  
    [こちら](https://github.com/s-fukumoto/docker-gae-php)からdockerでのLocal開発環境を構築します。　　

4. **Cloud環境**  
    `app.yaml`を作成します。  
    サンプルを用意しているのでコピーして使用します。  
    設定内容は自由に変更してください。
    ```
    $ cd gcp-testing 
    $ cp sample_app.yaml app.yaml
    ```
    gcloudの環境でデプロイします。deploy情報は[こちら](https://github.com/s-fukumoto/docker-gae-php)を参考にしてください。
    ```
    $ gcloud app deploy
    ```

---
## Sample
1. **Cloud Storege から静的パーツを埋め込んで表示**  
    Cloud Storege で`parts-testing-svc`という名称でバケットを作成します。[バケットの作成手順](https://cloud.google.com/storage/docs/creating-buckets?hl=ja#storage-create-bucket-console)  
    ※必要であればバケットの権限に、サービスアカウントの閲覧権限を付与してください。  

    作成したバケットに`parts_test.html`というHTMLを配備します。
    ```
    http://localhost:8080/testing  

      ※以下 Cloud環境での確認については、ドメイン部分を以下に読み替えてください。
        https://[YOUR_PROJECT_ID].appspot.com      ※[YOUR_PROJECT_ID]はGCPのプロジェクトID
    ```

2. **Cloud Datastore に閲覧数を保存してページ表示**  
    以下にアクセスするたびに閲覧数をDatastoreに保存しています。
    ```
    http://localhost:8080/testing  
    ```

3. **セッションを Cloud Datastore に保存**  
    CodeigniterのセッションドライバをDatastore用に作成してます。(lock制御は未実装)  
    他のドライバへ変更する場合は、[Codeigniterのセッション](http://codeigniter.jp/user_guide/3/libraries/sessions.html#id15)を参照してください。  
    ファイルドライバのみ動作確認済みです。  
    GAEではmemcacheによるsession保存がSDKで提供されてるので、通常はそちらが推奨です。  
    Codeigniterでは、sessionライブラリを使用しないようにすれば、memcacheになると思われます。（未検証）  
    ```
    http://localhost:8080/member  
    ```

4. **Cloud Datastore に保存したセッション情報の期限切れをバッチ処理で削除**  
    前記のドライバにてガベージコレクションも動作しますが、アクセス数過多を考慮して、cronによるバッチ処理でセッション情報を開放します。
    以下を実行すれば処理されます。
    ```
    http://localhost:8080/batch/session/release  
    ```
    これを`cron.yaml`で定義します。  
    サンプルを用意しているのでコピーして使用します。  
    設定内容は自由に変更してください。
    ```
    $ cd gcp-testing 
    $ cp sample_cron.yaml cron.yaml
    ```
    gcloudの環境でデプロイします。
    ```
    $ gcloud app deploy cron.yaml
    ```


---
## GCP memo
[GCP PHP document](https://cloud.google.com/php/docs/)

1. **NGINXの設定**  
    NGINXの設定は`app.yaml`が配置されている場所と同じ場所に、NGINXの設定ファイルを配備します。 
