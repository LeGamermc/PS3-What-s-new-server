#!/usr/bin/env python3

import urllib.request
import re
from datetime import datetime

print("Content-Type: text/html\n")

FEEDS = [
    ("What's new (XMB Section)", 'http://kns-srv2.zapto.org:82/xml/WHATSNEW.xml'),
    ("What's new: Game section", 'http://kns-srv2.zapto.org:82/xml/BILLBOARDGAME.xml'),
    ("What's new: Video section", 'http://kns-srv2.zapto.org:82/xml/BILLBOARDVIDEO.xml'),
    ("What's new: XMB TV Apps", 'http://kns-srv2.zapto.org:82/xml/BILLBOARDXMBTV.xml')
]

def fetch(url):
    try:
        with urllib.request.urlopen(url, timeout=15) as r:
            text = r.read().decode('utf-8')
        text = re.sub(r'<!--.*?-->', '', text, flags=re.DOTALL)
        text = re.sub(r'<(\w+)><\1/>', r'<\1></\1>', text)
        text = re.sub(r'<(\w+)>([^<]+)<\1/>', r'<\1>\2</\1>', text)
        text = re.sub(r'<(\w+)([^>]*)/>', r'<\1\2></\1>', text)
        text = re.sub(r'&(?!([a-zA-Z]+|#\d+);)', '&amp;', text)
        return text
    except:
        return None

def get(xml, tag):
    m = re.search(f'<{tag}[^>]*>(.*?)</{tag}>', xml, re.DOTALL)
    return m.group(1).strip() if m else ''

def get_attr(tag, attr):
    m = re.search(f'{attr}="([^"]*)"', tag)
    return m.group(1) if m else ''

def get_url(target):
    if 'psvp:play?url=' in target:
        return target.split('psvp:play?url=')[1].split()[0]
    m = re.search(r'(https?://[^\s"<>]+)', target)
    return m.group(1) if m else None

def is_active(from_date, until_date):
    if not from_date and not until_date:
        return True
    now = datetime.now()
    try:
        if from_date and datetime.fromisoformat(from_date[:19]) > now:
            return False
        if until_date and datetime.fromisoformat(until_date[:19]) <= now:
            return False
    except:
        pass
    return True

html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><link rel="stylesheet" href="styles.css"></head><body><main id="content">'

for name, url in FEEDS:
    xml = fetch(url)
    if not xml:
        continue
    
    # Try to find mtrl tags - first anywhere, then inside spc
    mtrls = re.findall(r'(<mtrl[^>]*>.*?</mtrl>)', xml, re.DOTALL)
    if not mtrls:
        spc = get(xml, 'spc')
        if spc:
            mtrls = re.findall(r'(<mtrl[^>]*>.*?</mtrl>)', spc, re.DOTALL)
    
    if not mtrls:
        continue
    
    html += f'<div class="category-section"><h2 class="category-title">{name}</h2><div class="carousel"><div class="carousel-inner">'
    
    for mtrl_full in mtrls:
        from_d = get_attr(mtrl_full, 'from')
        until_d = get_attr(mtrl_full, 'until')
        
        if not is_active(from_d, until_d):
            continue
        
        img = get(mtrl_full, 'url')
        if not img:
            continue
            
        item_name = get(mtrl_full, 'name')
        owner = get(mtrl_full, 'owner')
        desc = get(mtrl_full, 'desc')
        link = get_url(get(mtrl_full, 'target'))
        lastm = get_attr(mtrl_full, 'lastm')[:10] if get_attr(mtrl_full, 'lastm') else ''
        
        html += '<div class="item-card"><div class="item-image-container">'
        if link:
            html += f'<a href="{link}" target="_blank">'
        html += f'<img src="{img}" alt="{item_name}" class="item-image" onerror="this.style.display=\'none\';">'
        if link:
            html += '</a>'
        html += '</div><div class="item-content">'
        if item_name:
            html += f'<div class="item-name">{item_name}</div>'
        if owner:
            html += f'<div class="item-owner">{owner}</div>'
        if desc:
            html += f'<div class="item-desc">{desc}</div>'
        if lastm:
            html += f'<div class="item-meta">Updated: {lastm}</div>'
        html += '</div></div>'
    
    html += '</div></div></div>'

html += '</main></body></html>'
print(html)
