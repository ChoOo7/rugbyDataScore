<?php
function getStatisticsTypes($statisticName)
{
    $statisticName = str_replace('é', 'e', $statisticName);
    $statisticName = strtolower(trim($statisticName));
    switch ($statisticName) {
        case 'poss' . 'ess' . 'ion de ba' . 'lle':
            return 0;
        case 'ti' . 'rs au b' . 'ut':
            return 1;
        case 'ti' . 'rs ca' . 'dres':
            return 2;
        case 'ti' . 'rs non ca' . 'dres':
            return 3;
        case 'tir' . 's bloq' . 'ues':
            return 4;
        case 'corners':
            return 5;
        case 'hor' . 's-jeu':
            return 6;
        case 'sauv' . 'etages d' . 'u gard' . 'ien':
            return 7;
        case 'fau' . 'tes':
            return 8;
        case 'carto' . 'ns jaunes':
            return 9;
        case 'carto' . 'ns rouges':
            return 10;
        case 'coup fr' . 'ancs':
            return 11;
        case 'touche':
            return 12;
        case 'tot'.'al p'.'as'.'ses':
            return 13;
        case 'pa'.'sse'.'s cor'.'rectes':
            return 14;
    }

    return false;
}

$match = $xfsign = null;

if (php_sapi_name() == "cli") {
    $match = isset($argv[1]) ? $argv[1] : "E5H"."9pu"."op";
    $xfsign = "SW9"."D1e"."Zo";
    $loadStats = true;
} else {
    $match = $_GET['match'];//"E5H . . 9puop";
    $xfsign = $_GET['xfsign'];// "SW9"."D1e"."Zo";
    $loadStats = !!$_GET['loadStats'];
}
if (empty($xfsign)) {
    $xfsign = "SW9"."D1e"."Zo";
}


//Prevent the full hostname to be searchable on github search :)
$hostnamePart1 = "flash";
$hostnamePart2 = "resultats";
$hostname = $hostnamePart1 . $hostnamePart2;


$results = array();

$results['score1'] = "0";
$results['score2'] = "0";
$results['scores'] = array();

$url = "http://d." . $hostname . ".fr/x/feed/d_su_" . $match . "_fr_1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_REFERER, 'http://d.fl' . 'ash' . 'resu' . 'ltats.fr/x/feed/proxy');
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36', 'X-Fsign: ' . $xfsign, 'Accept-Language: *', 'Connection: keep-alive', 'X-GeoIP: 1'));

$ret = curl_exec($ch);
$tmp = explode('</table>', $ret);
$ret = $tmp[0] . '</table>';
$ret = str_replace('&nbsp;', ' ', $ret);

$idAction = 0;
$score1 = 0;
$score2 = 0;
if (preg_match_all('!<td class="score"( rowspan="[0-9]+")*><span class="p[0-9]_home">([0-9]+)</span> - <span class="p[0-9]_away">([0-9]+)</span></td>!is', $ret, $matches)) {
    $nb = count($matches[0]);
    for ($i = 0; $i < $nb; $i++) {

        $local1Score = $matches[2][$i];
        $local2Score = $matches[3][$i];

        $results['scores'][] = array($local1Score, $local2Score);

        if ($i < 3) {

            $score1 += $local1Score;
            $score2 += $local2Score;

            if ($score1 != "0" || $score2 != "0") {
                $results['score1'] = $score1;
                $results['score2'] = $score2;
            }
        }
        /*
        else
        {
            if ($local1Score > $local2Score) {
                $score1++;
            } else {
                $score2++;
            }
            $results['score1'] = $score1;
            $results['score2'] = $score2;
        }
        */
    }
}
//var_dump($ret);
$xml = simplexml_load_string($ret);


$results['actions'] = array();


$subSequence = 0;

foreach ($xml->tbody->tr as $tr) {
    $class = (string)$tr['class'];
    if ($class != 'odd' && $class != 'even') {
        $subSequence++;
        continue;
    }

    $action = "goal";
    $reason = "";
    $time = "";
    $who = "";
    $team = 0;
    $subOutName = "";
    $assist = "";

    foreach ($tr->td as $td) {
        $team++;
        foreach ($td->div as $div) {
            $divContent = trim((string)$div);
            $action = $divContent;
            foreach ($div->div as $subDiv) {
                $cl = (string)$subDiv['class'];
                if ($cl == 'time-box' || $cl == 'time-box-wide') {
                    $time = (string)$subDiv;
                }
                if (strpos($cl, 'soccer-ball') !== false) {
                    $action = "goal";
                }
                if (strpos($cl, 'r-card') !== false) {
                    $action = "redcard";
                }
                if (strpos($cl, 'y-card') !== false) {
                    $action = "yellowcard";
                }

                $content = (string)$div;
                if ($content == '(Pénalty)') {
                    $reason = 'penalty';
                }
            }


            foreach ($div->span as $subDiv) {
                $class = $subDiv['class'];
                if ($class == 'substitution-out-name') {
                    $subOutName = trim((string)$subDiv);
                    if (empty($subOutName)) {
                        $subOutName = ((string)$subDiv->a);
                    }
                }
                if ($class == 'assist') {
                    $assist = trim(trim(trim((string)$subDiv), '()'));
                    if (empty($assist)) {
                        $assist = ((string)$subDiv->a);
                    }
                }
            }


            foreach ($div->span as $subDiv) {
                $class = $subDiv['class'];
                if ($class == 'substitution-out-name') {
                    $subOutName = trim((string)$subDiv);
                    if (empty($subOutName)) {
                        $subOutName = ((string)$subDiv->a);
                    }
                    break;
                }
            }
            foreach ($div->span as $subDiv) {
                $class = $subDiv['class'];
                if (strpos($class, 'subincident') !== false) {
                    $reason = trim((string)$subDiv, '()');
                }
                if ($class == 'participant-name' || $class == 'substitution-in-name') {
                    $who = trim((string)$subDiv);
                    if (empty($who)) {
                        $who = ((string)$subDiv->a);
                    }


                    if ($class == 'substitution-in-name') {
                        $action = 'substitution';
                    }
                    break 3;
                }
            }
        }
    }

    if ($action == null) {
        continue;
    }

    $team = min(2, $team);

    if (strpos($time, '+') !== false) {
        $tmp = str_replace("'", "", $time);
        $tmp = explode('+', $tmp);
        $time = $tmp[0] + $tmp[1];
        $time .= "'";
    }

    if ($action == "goal" && $who == "") {
        continue;
    }

    $results['actions'][] = array(
        'id' => $idAction++,
        'action' => $action,
        'reason' => $reason,
        'substitutionoutname' => $subOutName,
        'actionassistname' => $assist,
        'time' => $time,
        'who' => $who,
        'team' => $team,
        'playpart' => $subSequence
    );
}


$url = "http://www." . $hostname . ".fr/match/" . $match . "/";

$ch = curl_init();
curl_setopt($ch, CURLOPT_REFERER, 'http://d.flash' . 'resu' . 'ltats.fr/x/feed/proxy');
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36', 'X-Fsign: ' . $xfsign, 'Accept-Language: *', 'Connection: keep-alive', 'X-GeoIP: 1'));

$ret = curl_exec($ch);

$ret = str_replace(array("\n", "\t", "\r"), array('', '', ''), $ret);

if (preg_match('!.*<td class="current\-result"><span class="scoreboard">([0-9]+)</span><span class="scoreboard\-divider">\-</span><span class="scoreboard">([0-9]+)</span></td>.*!uis', $ret, $matches)) {
    $results['score1'] = $matches[1];
    $results['score2'] = $matches[2];
} elseif (preg_match('!.*<td class="current\-result">(<span class="[a-zA-Z0-9-]*">)*<span class="scoreboard">([0-9]+)</span><span class="scoreboard\-divider">\-</span><span class="scoreboard">([0-9]+)</span>(</span>)*</td>.*!uis', $ret, $matches)) {
    $results['score1'] = $matches[2];
    $results['score2'] = $matches[3];
}


preg_match('!.*<td colspan="3" class="mstat">([^<]+)</td>.*!uis', $ret, $matches);
$results['state'] = html_entity_decode($matches[1]);


$stats = array();
if ($loadStats) {
    $url = "http://d.flash" . "resul" . "tats.fr/x/feed/d" . "_st" . "_$match" . "_fr" . "_1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_REFERER, 'http://d.flash' . 'resu' . 'ltats.fr/x/feed/proxy');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36', 'X-Fsign: ' . $xfsign, 'Accept-Language: *', 'Connection: keep-alive', 'X-GeoIP: 1'));

    $ret = curl_exec($ch);
    $ret = str_replace('&nbsp;', ' ', $ret);
    $ret = explode('<div id="tab-st' . 'atistics-' . '0-stati' . 'stic" style="display: none;">', $ret);
    if (count($ret) > 1) {
        $ret = explode('</div><div id="tab-sta' . 'tistics' . '-1-st' . 'atistic" style="display: none;">', $ret[1]);
        if ($ret) {
            $ret = $ret[0];
        } else {
            $ret = '';
        }
    } else {
        $ret = '';
    }

    if ($ret && $xml = simplexml_load_string($ret)) {
        foreach ($xml->tr as $tr) {
            $statType = getStatisticsTypes((string)$tr->td[1]);
            if (false === $statType) {
                continue;
            }

            $stats[] = array(
                'type' => $statType,
                'teamHome' => intval(str_replace('%', '', (string)$tr->td[0]->div[0]), 10),
                'teamAway' => intval(str_replace('%', '', (string)$tr->td[2]->div[1]), 10)
            );
        }
    }
}
$results['stats'] = $stats;

$url = "http://d.flash" . "resul" . "tats.fr/x/feed/d" . "_pv" . "_$match" . "_fr" . "_1";
$ch = curl_init();
curl_setopt($ch, CURLOPT_REFERER, 'http://d.flash' . 'resu' . 'ltats.fr/x/feed/proxy');
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36', 'X-Fsign: ' . $xfsign, 'Accept-Language: *', 'Connection: keep-alive', 'X-GeoIP: 1'));

$ret = curl_exec($ch);
$ret = explode('<div class="bottom-block">', $ret);

$minutes = -1;
$additionnalTime = -1;
if ($ret) {
    $ret = str_replace('sec"title="', 'sec" title="', $ret[0]);
    $ret = str_replace('&nbsp;', ' ', $ret);

    if ($xml = simplexml_load_string($ret)) {
        try {
            $formattedTimeString = (string)($xml->tr->td->div);
            $minutes = intval($formattedTimeString); // Récupère la première partie
            $additionnalTime = 0;
            if (false  !== $pos = strpos($formattedTimeString, '+')) {
                $additionnalTime = intval(substr($formattedTimeString, $pos+1));
            }
        } catch(Exception $e) {
            // Be quiet
        }
    }
}

$results['time'] = array('minutes' => $minutes, 'overtime' => $additionnalTime);

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);