<?php
/**
 * Created by PhpStorm.
 * User: abautu
 * Date: 12.02.2018
 * Time: 05:31
 */
require_once('config.php');
if ($_POST)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $SOLR_URL . "/update/extract");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
//    curl_setopt($ch, CURLOPT_VERBOSE, true);

    foreach($_FILES['file']['name'] as $i => $name) {
        if (empty($name)) {
            continue;
        }
        if ($_FILES['file']['error'][$i]) {
            echo "<p>Error in uploading file $name";
            continue;
        }
        $sha1_file = sha1_file($_FILES['file']['tmp_name'][$i]);
        $data = array(
            'literal.id' => $sha1_file,
            'literal.description' => $_POST['description'],
            'resource.name' => $name,
            'file' => curl_file_create($_FILES['file']['tmp_name'][$i], $_FILES['file']['type'][$i], $_FILES['file']['name'][$i]),
            'overwrite' => 'false',
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        if (!$result) {
            echo "<p>Failed to connect to server";
            continue;
        }
        $result = json_decode($result);
        if (!empty($result->responseHeader->status)) {
            echo "<p>Failed to send file $name to server";
//            var_dump($result);
            continue;
        }
        @mkdir($FILE_DIR,0777,TRUE);
        $uploadfile = $FILE_DIR . DIRECTORY_SEPARATOR . $sha1_file;
        if(!is_file($uploadfile . '.data')) {
            @move_uploaded_file($_FILES["file"]["tmp_name"][$i], $uploadfile . '.data');
        }
        file_put_contents($uploadfile. '_'.date('YmdHis') .'_indexed.meta', $name ."\n\n" . $_POST['description']);
        echo "<p>Ok! Sent file $name to server";
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'commit=true');
    $result = curl_exec($ch);
    curl_close ($ch);
}
?>
<html>
<head>
    <title>Update library</title>
</head>
<body>
    <form enctype="multipart/form-data" method="post">
        <p>Description:<br>
            <textarea name="description"></textarea>
        </p>
        <p>Files:<br><input type="file" name="file[]" /><br>
            <input type="file" name="file[]" /><br>
            <input type="file" name="file[]" /><br>
            <input type="file" name="file[]" /><br>
            <input type="file" name="file[]" />
        </p>
        <p>
            <input type="submit" name="update" value="Update" />
        </p>
    </form>
</body>
</html>
