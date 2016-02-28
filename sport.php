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

$idAction=0;
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
//var_dump($ret);
$xml = simplexml_load_string($ret);


$results['actions'] = array();

$subSequence = 0;

foreach($xml->tbody->tr as $tr)
{
  $class = (string) $tr['class'];
  if($class == 'odd' || $class == 'even')
  {
    $action = "goal";
    $reason = null;
    $time = null;
    $who = null;
    $team = 0;
    $subOutName = null;
    $assist = null;
    
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
	    if($cl == 'time-box' || $cl == 'time-box-wide')
	    {
	      $time = (string)$subDiv;
	    }
	    if(strpos($cl, 'soccer-ball') !== false)
	    {
	      $action = "goal";
	    }
	    if(strpos($cl, 'r-card') !== false)
	    {
	      $action = "redcard";
	    }
	  }
	  
	  
	  foreach($div->span as $subDiv)
	  {
            $class = $subDiv['class'];
	    if($class == 'substitution-out-name')
	    {
	      $subOutName = trim((string)$subDiv);
	      if(empty($subOutName))
	      {
		$subOutName = ((string)$subDiv->a);
	      }
            }
	    if($class == 'assist')
	    {
	      $assist = trim(trim(trim((string)$subDiv), '()'));
	      if(empty($assist))
	      {
		$assist = ((string)$subDiv->a);
	      }
            }
          }
	  
	  
	  foreach($div->span as $subDiv)
	  {
            $class = $subDiv['class'];
	    if($class == 'substitution-out-name')
	    {
	      $subOutName = trim((string)$subDiv);
	      if(empty($subOutName))
	      {
		$subOutName = ((string)$subDiv->a);
	      }
	      break;
            }
          }
	  foreach($div->span as $subDiv)
	  {
            $class = $subDiv['class'];
	    if(strpos($class, 'subincident') !== false)
	    {
	      $reason = trim((string)$subDiv, '()');
	    }
	    if($class == 'participant-name' || $class == 'substitution-in-name')
	    {
	      $who = trim((string)$subDiv);
	      if(empty($who))
	      {
		$who = ((string)$subDiv->a);
	      }
	      
	      
	      if($class == 'substitution-in-name')
	      {
                $action='substitution';
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
  
  if(strpos($time, '+') !== false)
  {
    $tmp = str_replace("'", "", $time);
    $tmp = explode('+', $tmp);
    $time = $tmp[0] + $tmp[1];
    $time .= "'";
  }
  
  
  $results['actions'][] = array(
    'id'=>$idAction++,
    'action'=>$action,
    'reason'=>$reason,
    'substitution-out-name'=>$subOutName,
    'action-assist-name'=>$assist,
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