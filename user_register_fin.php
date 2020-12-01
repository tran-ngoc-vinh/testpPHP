<?php

ob_start();
session_start();

//共通関数のinclude

require_once('common_function.php');
require_once('user_data.php');

//日付関数(date)を（後で）使うのでタイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');

//ユーザー入力情報を保持する配列を準備する
$user_input_data=array();

//「バラメタの一覧」を把握
$params=array('name','email','pass_1','pass_2');
//データを取得する
foreach($params as $p) {
    $user_input_data[$p]=(string)@$_POST[$p];
}
//確認
//var_dump($user_input_data);
//ユーザ入力のvalidate
//.................

//きほんのエラーチェック
$error_detail=validate_user($user_input_data);

//CSRチェック
if(false==is_csrf_token()){
    //「CSRF　トークエラー」であることを配列に格納しておく
    $error_detail["error_csrf"]=true;
}

//エラーが出たら入力ページに遷移する
if(false===empty($error_detail)){
    //エラー情報をセッションに入れて待ちまわる
    $_SESSION['output_buffer']=$error_detail;
    //入力値をセッションに入れて待ちまわる
    //XXX　「keyが重複しない」はずなので加算演算子でok
    $_SESSION['output_buffer']+=$user_input_data;

    
    // 入力ページに遷移する
    header('Location: ./user_register.php');
    exit;
}

// DBハンドルの取得
$dbh = get_dbh();

// INSERT文の作成と発行
// ------------------------------
// 準備された文(プリペアドステートメント)の用意
$sql = 'INSERT INTO users(name, email, pass, created, updated)
             VALUES (:name, :email, :pass, :created, :updated);';
$pre = $dbh->prepare($sql);

// 値のバインド
$pre->bindValue(':name', $user_input_data['name'], PDO::PARAM_STR);
$pre->bindValue(':email', $user_input_data['email'], PDO::PARAM_STR);
// パスワードは「password_hash関数」を用いる：絶対に、何があっても「そのまま(平文で)」入れないこと！！
$pre->bindValue(':pass', password_hash($user_input_data['pass_1'], PASSWORD_DEFAULT), PDO::PARAM_STR);
// 日付(MySQLのバージョンが高ければ"DEFAULT CURRENT_TIMESTAMP"に頼る、という方法も一つの選択肢)
$pre->bindValue(':created', date(DATE_ATOM), PDO::PARAM_STR);
$pre->bindValue(':updated', date(DATE_ATOM), PDO::PARAM_STR);

// SQLの実行
$r = $pre->execute();
if (false === $r) {
    // XXX 本当はもう少し丁寧なエラーページを出力する
    echo 'システムでエラーが起きました';
    exit;
}

// 正常に終了したので、セッション内の「出力用情報」を削除する
unset($_SESSION['output_buffer']);

?>
<!DOCTYPE HTML>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>DB講座上級</title>
  <style type="text/css">
    .error { color: red; }
  </style>
</head>

<body>
  入力いただきましてありがとうございました。
</body>
</html>

}