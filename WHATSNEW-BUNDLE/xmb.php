<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$XML_FEEDS = [
    ['name' => "What's new (XMB Section)", 'url' => 'http://kns-srv2.zapto.org:82/xml/WHATSNEW.xml'],
    ['name' => "What's new: Game section", 'url' => 'http://kns-srv2.zapto.org:82/xml/BILLBOARDGAME.xml'],
    ['name' => "What's new: Video section", 'url' => 'http://kns-srv2.zapto.org:82/xml/BILLBOARDVIDEO.xml'],
    ['name' => "What's new: XMB TV Apps", 'url' => 'http://kns-srv2.zapto.org:82/xml/BILLBOARDXMBTV.xml']
];

function fetchXML($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $text = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    if ($text === false) return null;

    // Only the minimal cleaning that was in the original JS
    $text = preg_replace('/<!--[\s\S]*?-->/', '', $text);
    $text = preg_replace('/<(\w+)><\1\/>/', '<$1></$1>', $text);
    $text = preg_replace('/<(\w+)>([^<]+)<\1\/>/', '<$1>$2</$1>', $text);
    $text = preg_replace('/<(\w+)([^>]*)\/>/', '<$1$2></$1>', $text);
    $text = preg_replace('/&(?!([a-zA-Z]+|#\d+);)/', '&amp;', $text);

    $dom = new DOMDocument();
    if (@$dom->loadXML($text) === false) return null;
    return $dom;
}

function getElementText($parent, $tagName) {
    $elements = $parent->getElementsByTagName($tagName);
    return $elements->length > 0 ? trim($elements->item(0)->textContent) : '';
}

function extractPlayableUrl($targetText) {
    if (!$targetText) return null;
    if (strpos($targetText, 'psvp:play?url=') !== false) {
        return trim(explode('psvp:play?url=', $targetText)[1]);
    }
    if (preg_match('/(https?:\/\/[^ \s;"]+)/i', $targetText, $matches)) {
        return $matches[1];
    }
    if (preg_match('/url=([^\s;&]+)/i', $targetText, $matches)) {
        $url = $matches[1];
        $url = preg_replace('/^http:\//', 'http://', $url);
        $url = preg_replace('/^https:\//', 'https://', $url);
        return $url;
    }
    return null;
}

function parseMtrl($mtrlElement) {
    $urls = $mtrlElement->getElementsByTagName('url');
    $imageUrl = $urls->length > 0 ? trim($urls->item(0)->textContent) : '';
    $targetText = getElementText($mtrlElement, 'target');
    $from = $mtrlElement->getAttribute('from') ?? '';

    return [
        'id'           => $mtrlElement->getAttribute('id'),
        'from'         => $from,
        'until'        => $mtrlElement->getAttribute('until'),
        'lastm'        => $mtrlElement->getAttribute('lastm'),
        'name'         => getElementText($mtrlElement, 'name'),
        'owner'        => getElementText($mtrlElement, 'owner'),
        'desc'         => getElementText($mtrlElement, 'desc'),
        'url'          => $imageUrl,
        'clickableUrl' => extractPlayableUrl($targetText)
    ];
}

function isItemActive($item) {
    $now = new DateTime();
    if ($item['from']) {
        $fromDate = new DateTime($item['from']);
        if ($now < $fromDate) return false;
    }
    if ($item['until']) {
        $untilDate = new DateTime($item['until']);
        if ($now >= $untilDate) return false;
    }
    return true;
}

function escapeHtml($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function createItemCard($item) {
    $html = '<div class="item-card">';
    $html .= '<div class="item-image-container">';
    if ($item['clickableUrl']) {
        $html .= '<a href="' . escapeHtml($item['clickableUrl']) . '" target="_blank">';
    }
    $html .= '<img src="' . escapeHtml($item['url']) . '" alt="' . escapeHtml($item['name'] ?? 'Content') . '" class="item-image" onerror="this.style.display=\'none\';">';
    if ($item['clickableUrl']) $html .= '</a>';
    $html .= '</div><div class="item-content">';
    if ($item['name']) $html .= '<div class="item-name">' . escapeHtml($item['name']) . '</div>';
    if ($item['owner']) $html .= '<div class="item-owner">' . escapeHtml($item['owner']) . '</div>';
    if ($item['desc']) $html .= '<div class="item-desc">' . escapeHtml($item['desc']) . '</div>';
    if ($item['lastm']) $html .= '<div class="item-meta">Updated: ' . substr($item['lastm'], 0, 10) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

function createCategorySection($name, $items) {
    $html = '<div class="category-section">';
    $html .= '<h2 class="category-title">' . escapeHtml($name) . '</h2><div class="carousel"><div class="carousel-inner">';
    if (count($items) === 0) {
        $html .= '<div class="no-items">No active content</div>';
    } else {
        foreach ($items as $item) {
            $html .= createItemCard($item);
        }
    }
    $html .= '</div></div></div>';
    return $html;
}

function processFeed($xml, $feedName) {
    if (!$xml) return '';
    $mtrlElements = $xml->getElementsByTagName('mtrl');
    if ($mtrlElements->length === 0) {
        $spc = $xml->getElementsByTagName('spc');
        if ($spc->length > 0) {
            $mtrlElements = $spc->item(0)->getElementsByTagName('mtrl');
        }
    }
    $items = [];
    foreach ($mtrlElements as $mtrl) {
        $item = parseMtrl($mtrl);
        if (isItemActive($item) && $item['url']) {
            $items[] = $item;
        }
    }
    if (count($items) > 0) {
        usort($items, function($a, $b) {
            return (intval($a['id'] ?? 0)) - (intval($b['id'] ?? 0));
        });
        return createCategorySection($feedName, $items);
    }
    return '';
}

$anyContent = false;
$contentHtml = '';
foreach ($XML_FEEDS as $feed) {
    $doc = fetchXML($feed['url']);
    $section = processFeed($doc, $feed['name']);
    if ($section !== '') {
        $anyContent = true;
        $contentHtml .= $section;
    }
}
if (!$anyContent) {
    $contentHtml = '<div class="error">No content loaded. Check your connection or refresh.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main id="content"><?php echo $contentHtml; ?></main>
</body>
</html>
