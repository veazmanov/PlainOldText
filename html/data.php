<?php
// ./html/data.php

$site_name = "Plain Old Text";

// Option B: If oldnews-articles-only is NEXT TO the html/ folder (one level up)
$articles_base = dirname(__DIR__) . '/oldnews-articles-only';

$publications = [
    'the_liberator'      => 'The Liberator',
    'boston_pilot'       => 'The Boston Pilot',
    'sussex-advertiser'  => 'Sussex Advertiser'
];

$metadata = [];

if (is_dir($articles_base)) {
    $items = array_diff(scandir($articles_base), ['.', '..']);
    
    foreach ($items as $item) {
        $item_path = $articles_base . '/' . $item;
        
        if (is_dir($item_path)) {
            $json_file = $item_path . '/data.json';
            
            if (file_exists($json_file)) {
                // Fetch content and trim any invisible trailing whitespace or structural anomalies
                $raw_json = trim(file_get_contents($json_file));
                $json_data = json_decode($raw_json, true);
                
                if ($json_data) {
                    $year  = $json_data['year'] ?? '0000';
                    $month = $json_data['month'] ?? '00';
                    $day   = $json_data['day'] ?? '00';
                    
                    $date_obj = DateTime::createFromFormat('Y-n-j', "$year-$month-$day");
                    $display_date = $date_obj ? $date_obj->format('j M, Y') : "$day/$month/$year";

                    $sort_date    = "$year-$month-$day";

                    $metadata[$item] = [
                        'title'      => $json_data['title'] ?? 'Untitled Article',
                        'day_name'   => '', 
                        'day_num'    => $day,
                        'source_url' => $json_data['source_url'] ?? null,
                        'date'       => $display_date,
                        'sort_date'  => $sort_date,
                        'pub_key'    => $json_data['newspaper'] ?? '',
                        'summary'    => $json_data['summary'] ?? '',
                        'page'       => $json_data['page'] ?? null,   // <-- new
                        
                        // FIX: Explicitly save the dynamic root-relative link here!
                        'link'       => '/' . $item 
                    ];
                }
            }
        }
    }
}
