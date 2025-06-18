<?php

function loadJson(string $fileName): array
{
    $json = @file_get_contents(__DIR__ . '/' . $fileName);
    return json_decode($json, true);
}

$startTime = microtime(true);

$listA = loadJson('1.json');
$listB = loadJson('2.json');

$updateList = [];

/*
// ---------------------------
// VERY SLOW METHOD ~ 3.4 sec
// ---------------------------
foreach ($listA as $a) {
    if ($a['status'] === 'need_to_update') {
        foreach ($listB as $idx => $b) {
            if ($b['id'] === $a['id']) {
                $updateList[$a['id']] = $a['counter'] + $b['counter'];
                unset($listB[$idx]);  // remove element
                break;
            }
        }
    }
}
*/

// -----------------------
// FAST METHOD ~ 0.13 sec
// -----------------------
//  Step 1: keep only "need_to_update"
$listA = array_filter($listA, function ($element) {
    return $element['status'] === 'need_to_update';
});

//  Step 2: find intersect
array_uintersect($listA, $listB, function ($a, $b) use (&$updateList) {
    $res = $a['id'] <=> $b['id'];
    if ($res === 0) {
        $updateList[$a['id']] = $a['counter'] + $b['counter'];
    }
    return $res;
});

unset($listA, $listB);

if (php_sapi_name() !== 'cli') {
    echo '<pre>';
}
echo 'Total execution time: ' . round(microtime(true) - $startTime, 3) . PHP_EOL;
echo 'Need to update: ' . count($updateList) . PHP_EOL;

foreach ($updateList as $id => $counter) {
    // upsert command for PostgresSql
    $sql = "INSERT INTO bd.tbl_test AS x (id, counter) " .
           "VALUES ($id, $counter) " .
           "ON CONFLICT (id) DO UPDATE SET counter = EXCLUDED.counter + x.counter;";

    echo $sql . PHP_EOL;
}
