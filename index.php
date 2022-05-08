<?php
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

$servername = 'localhost';
$username = 'root';
$password = ''; // on localhost by default there is no password
$dbname = 'def';
$base_url = 'https://example.com/shorturl/'; // it is your application url

try {
    $conn = new PDO('mysql:host=' . $servername . ';dbname=' . $dbname . ';charset=UTF8', $username, $password);
} catch(PDOException $e) {
    die("Cant connect to DB!");
}

if (isset($_GET['r']) && !empty($_GET['r'])) {
    $id = $_GET['r'];
    $url = GetRedirectUrl($conn, $id);
    header("location:" . $url);
    exit;
}

$ins = '';
if (isset($_GET['url']) && !empty($_GET['url'])) {
    $url = $_GET['url'];
    $id = GetShortUrl($conn, $url);
    $ins = '<br/>Here is the short <a href="' . $base_url . $id . '" target="_blank">' . 'link</a>: <br>
    <input type="text" value="' . $base_url . $id . '" id="cptx">
    <button onclick="copyUrl(this)">Copy URL</button>';
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>ShortURL</title>
</head>
<body>
    <script>
    function copyUrl(m) {
        let copyText = document.getElementById("cptx");
        copyText.select();
        document.execCommand("copy");
        m.textContent = "Copied!";
    }
    </script>
    <center>
        <h1>ShortURL</h1>
        <form>
            <input style="width:80%" type="url" name="url" required />
            <input class="button" type="submit" value="Get"/>
        </form>
    ' . $ins . '
    </center>
</body>
</html>';

function GetShortUrl($conn, $url) {
    try {
        $quer = $conn->prepare("SELECT `id` FROM `links` WHERE `url` = ? LIMIT 1");
        $quer->execute([$url]);
        if ($quer->rowCount() > 0) {
            $res = $quer->fetch(PDO::FETCH_ASSOC)['id'];
        } else {
            $quer = $conn->prepare("INSERT INTO `links` (`url`) VALUES (?)");
            $quer->execute([$url]);
            $res = $conn->lastInsertId();
        }
        return base_convert($res, 10, 36);
    } catch(PDOException $e) {
        die("Failed to create short link : <hr/>" . $e->getMessage() . "<br/>");
    }
}

function GetRedirectUrl($conn, $id) {
    try {
        $quer = $conn->prepare("SELECT `url` FROM `links` WHERE `id` = ? LIMIT 1");
        $quer->execute(array(addslashes(base_convert($id, 36, 10))));
        if ($quer->rowCount() > 0) {
            return $quer->fetch(PDO::FETCH_ASSOC)['url'];
        } else {
            die("Invalid ID!");
        }
    } catch(PDOException $e) {
        die("Failed to get link: <hr/>" . $e->getMessage() . "<br/>");
    }
}
