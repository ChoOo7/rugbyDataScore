<?php
$match = $xfsign = null;

if(php_sapi_name() == "cli")
{
  $match = isset($argv[1]) ? $argv[1] : "d2OVreOh";
  $xfsign = "SW9D1eZo";
}else{
  $match = $_GET['match'];//"d2OVreOh";
  $xfsign = $_GET['xfsign'];//"SW9D1eZo";
}


//Prevent the full hostname to be searchable on github search :)
$hostnamePart1 = "flash";
$hostnamePart2 = "resultats";
$hostname = $hostnamePart1.$hostnamePart2;


$results = array();

$results['score1'] = "0";
$results['score2'] = "0";

/*
curl 'http://d.flashresultats.fr/x/feed/d_su_zgcMW8gI_fr_1' -H 'X-Fsign: SW9D1eZo' -H 'Referer: http://d.flashresultats.fr/x/feed/proxy' 
*/
$url = "http://d.".$hostname.".fr/x/feed/d_su_".$match."_fr_1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_REFERER, 'http://d.flashresultats.fr/x/feed/proxy');
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36', 'X-Fsign: '.$xfsign, 'Accept-Language: *', 'Connection: keep-alive', 'X-GeoIP: 1')); 
    
$ret = curl_exec($ch);
$tmp = explode('</table>', $ret);
$ret = $tmp[0].'</table>';
$ret = str_replace('&nbsp;', ' ', $ret);

//$ret = '<td class="score" rowspan="6"><span class="p1_home">20</span> - <span class="p1_away">6</span></td>';
/*
<td class="score"><span class="p2_home">0</span> - <span class="p2_away">0</span></td>
<td class="score" rowspan="3"><span class="p1_home">1</span> - <span class="p1_away">2</span></td>
*/
/*
<table id="parts" class="parts-first vertical"><tbody><tr class="stage-header stage-12"><td colspan="3" class="h-part">1. mi-temps</td></tr><tr class="odd"><td class="summary-vertical fl"><div class="wrapper"> </div></td><td class="score" rowspan="4"><span class="p1_home">3</span> - <span class="p1_away">10</span></td><td class="summary-vertical fr"><div class="wrapper"><div class="time-box">8'</div><div class="icon-box rugby-union-ball"><span class="icon rugby-union-ball"> </span></div>(Try)  <span class="participant-name">Bobo S.</span></div></td></tr><tr class="even"><td class="summary-vertical fl"><div class="wrapper"> </div></td><td class="summary-vertical fr"><div class="wrapper"><div class="time-box">9'</div><div class="icon-box rugby-union-ball"><span class="icon rugby-union-ball"> </span></div>(Conversion Goal)  <span class="participant-name">du Plessis W.</span></div></td></tr><tr class="odd"><td class="summary-vertical fl"><div class="wrapper"> </div></td><td class="summary-vertical fr"><div class="wrapper"><div class="time-box">14'</div><div class="icon-box rugby-union-ball"><span class="icon rugby-union-ball"> </span></div>(Penalty Goal)  <span class="participant-name">du Plessis W.</span></div></td></tr><tr class="even"><td class="summary-vertical fl"><div class="wrapper"><div class="time-box">18'</div><div class="icon-box rugby-union-ball"><span class="icon rugby-union-ball"> </span></div><span class="participant-name">Plisson J.</span> (Penalty Goal) </div></td><td class="summary-vertical fr"><div class="wrapper"> </div></td></tr></tbody></table>
*/

$score1 = 0;
$score2 = 0;
if(preg_match_all('!<td class="score"( rowspan="[0-9]+")*><span class="p[0-9]_home">([0-9]+)</span> - <span class="p[0-9]_away">([0-9]+)</span></td>!is', $ret, $matches))
{
  $nb = count($matches[0]);
  for($i = 0; $i < $nb; $i++)
  {
    
    $score1 += $matches[2][$i];
    $score2 += $matches[3][$i];
    
    if($score1 != "0" || $score2 != "0")
    {
      $results['score1'] = $score1;
      $results['score2'] = $score2;
    }
  }
}
$xml = simplexml_load_string($ret);


$results['actions'] = array();

$subSequence = 0;

foreach($xml->tbody->tr as $tr)
{
  $class = (string) $tr['class'];
  if($class == 'odd' || $class == 'even')
  {
    $action = "goal";
    $time = null;
    $who = null;
    $team = 0;
    
    foreach($tr->td as $td)
    {
      $team++;
      foreach($td->div as $div)
      {
        
	$divContent = trim((string)$div);
	if( true || $divContent)
	{
	  $action = $divContent;
	  foreach($div->div as $subDiv)
	  {
	    $cl = (string)$subDiv['class'];
	    if($cl == 'time-box')
	    {
	      $time = (string)$subDiv;
	    }
	    if(strpos($cl, 'soccer-ball') !== false)
	    {
	      $action = "goal";
	    }
	  }
	  foreach($div->span as $subDiv)
	  {
	    if($subDiv['class'] == 'participant-name')
	    {
	      $who = (string)$subDiv;
	      if(empty($who))
	      {
		$who = ((string)$subDiv->a);
	      }
	      
	      break 3;
	    }
	  }
	  
	}
      }
    }
  }else{
    $subSequence++;
    continue;
  }
  
  if($action == null)
  {
    continue;
  }
  
  $team = min(2, $team);
  
  $results['actions'][] = array(
    'action'=>$action,
    'time'=>$time,
    'who'=>$who,
    'team'=>$team,
    'playpart'=>$subSequence
  );
}


$url = "http://www.".$hostname.".fr/match/".$match."/";

$ch = curl_init();
curl_setopt($ch, CURLOPT_REFERER, 'http://d.flashresultats.fr/x/feed/proxy');
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36', 'X-Fsign: '.$xfsign, 'Accept-Language: *', 'Connection: keep-alive', 'X-GeoIP: 1')); 

$ret = curl_exec($ch);

$ret = str_replace(array("\n", "\t","\r"), array('', '', ''), $ret);
if(preg_match('!.*<td class="current\-result"><span class="scoreboard">([0-9]+)</span><span class="scoreboard\-divider">\-</span><span class="scoreboard">([0-9]+)</span></td>.*!uis', $ret, $matches))
{
  $results['score1'] = $matches[1];
  $results['score2'] = $matches[2];
}elseif(preg_match('!.*<td class="current\-result">(<span class="[a-zA-Z0-9-]*">)*<span class="scoreboard">([0-9]+)</span><span class="scoreboard\-divider">\-</span><span class="scoreboard">([0-9]+)</span>(</span>)*</td>.*!uis', $ret, $matches))
{
  $results['score1'] = $matches[2];
  $results['score2'] = $matches[3];
}

preg_match('!.*<td colspan="3" class="mstat">([^<]+)</td>.*!uis', $ret, $matches);
$results['state'] = html_entity_decode($matches[1]);


header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);