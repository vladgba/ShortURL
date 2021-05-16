<?php
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_regex_encoding('UTF-8');

$servername = 'localhost';
$username = 'root';
$password = ''; // on localhost by default there is no password
$dbname = 'def';
$base_url = 'https://example.com/shorturl'; // it is your application url

$head = '<!DOCTYPE html>
<html>
<head>
   <title>ShortURL</title>
</head>
<body>
<script>
function myFunction() {
  var copyText = document.getElementById("myInput");
  copyText.select();
  document.execCommand("copy");
  var tooltip = document.getElementById("myTooltip");
  tooltip.innerHTML = "Copied: " + copyText.value;
}
</script>';

if (isset($_GET['r']) && $_GET['r'] != "") {
    $slug = $_GET['r'];
    try {
        $conn = new PDO('mysql:host=' . $servername . ';dbname=' . $dbname . ';charset=UTF8', $username, $password);
    }
    catch(PDOException $e) {
        die("DB connect error!");
    }
    $url = GetRedirectUrl($conn, $slug);
    header("location:" . $url);
    exit;
}

$form = '<h1>ShortURL</h1>
<form>
<input style="width: 500px; height: 22px;" type="url" name="url" required />
<input class="button" type="submit" value="Get"/>
</form>';
if (isset($_GET['url']) && $_GET['url'] != "") {
    $url = $_GET['url'];
    try {
        $conn = new PDO('mysql:host=' . $servername . ';dbname=' . $dbname . ';charset=UTF8', $username, $password);
    }
    catch(PDOException $e) {
        die("DB connect error!");
    }
    $slug = GetShortUrl($conn, $url);
    echo $head . '<center>' . $form . '<br/>Here is the short <a href="' . $base_url . '/' . $slug . '" target="_blank">' . 'link</a>: <br>';
    echo '<input type="text" value="' . $base_url . '/' . $slug . '" id="myInput"><button onclick="myFunction()">Copy URL</button></center>';
} else {
    echo $head . '<center>' . $form . '</center>';
}

function GetShortUrl($conn, $url) {
    try {
        $quer = $conn->prepare("SELECT * FROM `links` WHERE `url` = ? LIMIT 1");
        $quer->execute(array($url));
        if ($quer->rowCount() > 0) {
            $res = $quer->fetch(PDO::FETCH_ASSOC)['id'];
        } else {
            $quer = $conn->prepare("INSERT INTO `links` (`url`) VALUES (?)");
            $quer->execute(array($url));
            $stmt = $conn->query("SELECT LAST_INSERT_ID()");
            $res = $stmt->fetchColumn();
        }
		return base_convert($res, 10, 36);
    }
    catch(PDOException $e) {
        die("Failed to create short link : <hr/>" . $e->getMessage() . "<br/>");
    }
}

function GetRedirectUrl($conn, $slug) {
    try {
        $quer = $conn->prepare("SELECT * FROM `links` WHERE `id` = ? LIMIT 1");
        $quer->execute(array(addslashes(base_convert($slug, 36, 10))));
        if ($quer->rowCount() > 0) {
            $res = $quer->fetch(PDO::FETCH_ASSOC);
            return $res['url'];
        }
        else {
            die("Invalid ShortURL ID!");
        }
    }
    catch(PDOException $e) {
        die("Failed to get link: <hr/>" . $e->getMessage() . "<br/>");
    }
}

echo '</body></html>';
