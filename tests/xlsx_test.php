<?php
require_once __DIR__ . '/../includes/xlsx_writer.php';
require_once __DIR__ . '/../includes/xlsx_reader.php';

return function (TestRunner $t) {

    $t->run('xlsx_col_letter converts 0-indexed column numbers correctly', function (TestRunner $t) {
        $t->assertEquals('A', xlsx_col_letter(0));
        $t->assertEquals('B', xlsx_col_letter(1));
        $t->assertEquals('Z', xlsx_col_letter(25));
        $t->assertEquals('AA', xlsx_col_letter(26));
        $t->assertEquals('AZ', xlsx_col_letter(51));
        $t->assertEquals('BA', xlsx_col_letter(52));
    });

    $t->run('xlsx_col_index_from_ref converts cell references back to 0-indexed columns', function (TestRunner $t) {
        $t->assertEquals(0, xlsx_col_index_from_ref('A1'));
        $t->assertEquals(1, xlsx_col_index_from_ref('B1'));
        $t->assertEquals(25, xlsx_col_index_from_ref('Z100'));
        $t->assertEquals(26, xlsx_col_index_from_ref('AA1'));
        $t->assertEquals(51, xlsx_col_index_from_ref('AZ1'));
    });

    $t->run('column letter/index conversion round-trips for the first 100 columns', function (TestRunner $t) {
        for ($i = 0; $i < 100; $i++) {
            $letter = xlsx_col_letter($i);
            $t->assertEquals($i, xlsx_col_index_from_ref($letter . '1'), "column {$i} -> {$letter}");
        }
    });

    $t->run('xlsx_col_index_from_ref returns null for a malformed reference', function (TestRunner $t) {
        $t->assertNull(xlsx_col_index_from_ref('not-a-ref'));
        $t->assertNull(xlsx_col_index_from_ref('123'));
    });

    $t->run('write_xlsx then read_xlsx round-trips a small table', function (TestRunner $t) {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_test');
        $headers = ['F-AP Number', 'Name', 'Days'];
        $rows = [
            ['315443', 'Abdullah, Mamman', '30'],
            ['318220', 'Ngozi Okafor', '10'],
        ];

        $ok = write_xlsx($tmp, $headers, $rows);
        $t->assertTrue($ok);

        $readBack = read_xlsx($tmp);
        unlink($tmp);

        $t->assertEquals(3, count($readBack)); // header + 2 data rows
        $t->assertEquals(['F-AP Number', 'Name', 'Days'], array_values($readBack[0]));
        $t->assertEquals(['315443', 'Abdullah, Mamman', '30'], array_values($readBack[1]));
        $t->assertEquals(['318220', 'Ngozi Okafor', '10'], array_values($readBack[2]));
    });

};
