<?php

function loadJson(string $fileName): array
{
    $json = @file_get_contents(__DIR__ . '/' . $fileName);
    return json_decode($json, true);
}

$listA = loadJson('1.json');
$listB = loadJson('2.json');
$updateList = [];
$startTime = microtime(true);

/*
// --------------------
// SLOW METHOD ~ 3.7 sec
// --------------------
foreach ($listA as $a) {
    if ($a['status'] == 'need_to_update') {
        foreach ($listB as $idx => $b) {
            if ($b['id'] == $a['id']) {
                $a['counter'] += $b['counter'];
                unset($listB[$idx]);  // remove element
                $updateList[] = $a;
                break;
            }
        }
    }
}
*/

// --------------------
// FAST METHOD ~ 0.09 sec
// --------------------
//   step 1: keep only "need_to_update"
$listA = array_filter($listA, function ($element) {
    return $element['status'] == 'need_to_update';
});

//   step 2: find intersect
array_uintersect($listA, $listB, function ($a, $b) use (&$updateList) {
    $res = $a['id'] <=> $b['id'];
    if ($res == 0) {
        $a['counter'] += $b['counter'];
        $updateList[] = $a;
    }
    return $res;
});

if (php_sapi_name() !== 'cli') {
    echo '<pre>';
}

echo 'Total execution time: ' . round(microtime(true) - $startTime, 3) . PHP_EOL;
echo 'Need to update: ' . count($updateList) . PHP_EOL;

foreach ($updateList as $row) {
    // upsert for PostgresSql
    $sql = "INSERT INTO bd.tbl_test AS x (id, status, counter) " .
           "VALUES (${row['id']}, '${row['status']}', ${row['counter']}) " .
           "ON CONFLICT (id) DO UPDATE SET counter = EXCLUDED.counter + x.counter;";

    echo $sql . PHP_EOL;
}
