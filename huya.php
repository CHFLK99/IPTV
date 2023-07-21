<?php

class Huya {
    private $rid;

    public function __construct($rid) {
        $this->rid = $rid;
    }

    private function md5huya($str) {
        return md5($str);
    }

    private function format($realstr) {
        $i = explode("?", $realstr)[0];
        $b = explode("?", $realstr)[1];
        $r = explode("/", $i);
        $s = preg_replace("/\\.(flv|m3u8)/", "", $r[count($r)-1]);
        $c = explode("&", $b);
        $cnil = array();
        $n = array();
        foreach ($c as $v) {
            if (strlen($v) > 0) {
                $cnil[] = $v;
                $narr = explode("=", $v);
                $n[$narr[0]] = $narr[1];
            }
        }
        $c = $cnil;
        $fm = urldecode($n["fm"]);
        $ub64 = base64_decode($fm);
        $u = $ub64;
        $p = explode("_", $u)[0];
        $f = strval(round(microtime(true) * 100));
        $l = $n["wsTime"];
        $t = "0";
        $h = $p . "_" . $t . "_" . $s . "_" . $f . "_" . $l;
        $m = $this->md5huya($h);
        $y = $c[count($c)-1];
        $url = sprintf("%s?wsSecret=%s&wsTime=%s&u=%s&seqid=%s&%s", $i, $m, $l, $t, $f, $y);
        return $url;
    }

    public function getLiveUrl() {
        $liveurl = "https://m.huya.com/" . $this->rid;
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Mobile/15E148 Safari/604.1\r\n" .
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
            )
        );
        $context = stream_context_create($opts);
        $body = file_get_contents($liveurl, false, $context);
        $freg = '/"(?i)liveLineUrl":"([\s\S]*?)",/';
        if (preg_match($freg, $body, $res) && isset($res[1]) && strlen($res[1]) > 0) {
            $nstr = base64_decode($res[1]);
            $realstr = $nstr;
            if (strpos($realstr, "replay") !== false) {
                return "https:" . $realstr;
            } else {
                $liveurl = $this->format($realstr);
                $realurl = str_replace(array("hls", "m3u8", "&ctype=tars_mobile"), array("flv", "flv", ""), $liveurl);
                return "https:" . $realurl;
            }
        } else {
            return null;
        }
    }
}

if (isset($_GET['rid'])) {
    // 实例化 Huya 类，传入虎牙主播的房间 ID
    $huya = new Huya($_GET['rid']);

    // 获取实时流或回放流的 URL
    $liveUrl = $huya->getLiveUrl();

    if (!is_null($liveUrl)) {
      header("Location: " . $liveUrl);
      exit;
    } else {
      echo "Failed to fetch live link.";
      exit;
    }
}

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Huya Live Link</title>
  </head>
  <body>
    <form action="huya.php" method="get">
      <label for="rid">Rid:</label>
      <input type="text" id="rid" name="rid">
      <input type="submit" value="播放">
    </form>
  </body>
</html>