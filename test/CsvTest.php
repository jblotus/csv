<?php

namespace League\Csv\test;

use League\Csv\Reader;
use League\Csv\Writer;
use PHPUnit_Framework_TestCase;
use SplFileInfo;
use SplFileObject;
use SplTempFileObject;

/**
 * @group csv
 */
class CsvTest extends PHPUnit_Framework_TestCase
{
    private $csv;

    private $expected = [
        ['john', 'doe', 'john.doe@example.com'],
        ['jane','doe','jane.doe@example.com'],
    ];

    public function setUp()
    {
        $tmp = new SplTempFileObject();
        foreach ($this->expected as $row) {
            $tmp->fputcsv($row);
        }

        $this->csv = Reader::createFromFileObject($tmp);
    }

    public function tearDown()
    {
        $this->csv = null;
    }

    public function testInterface()
    {
        $this->assertInstanceOf('IteratorAggregate', $this->csv);
        $this->assertInstanceOf('JsonSerializable', $this->csv);
    }

    public function testToHTML()
    {
        $this->assertContains("<table", $this->csv->toHTML());
    }

    public function testToXML()
    {
        $this->assertInstanceOf('DOMDocument', $this->csv->toXML());
    }

    public function testJsonSerialize()
    {
        $this->assertContains(['john', 'doe', 'john.doe@example.com'], json_decode(json_encode($this->csv), true));
    }

    /**
     * @param $rawCsv
     *
     * @dataProvider getIso8859Csv
     */
    public function testJsonSerializeAffectedByReaderOptions($rawCsv)
    {
        $csv = Reader::createFromString($rawCsv);
        $csv->setEncodingFrom('iso-8859-15');
        $csv->setOffset(799);
        $csv->setLimit(50);
        json_encode($csv);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    public static function getIso8859Csv()
    {
        return [[file_get_contents(__DIR__.'/data/prenoms.csv')]];
    }

    /**
     * @runInSeparateProcess
     */
    public function testOutputSize()
    {
        $this->assertSame(60, $this->csv->output("test.csv"));
    }

    /**
     * @runInSeparateProcess
     */
    public function testOutputHeaders()
    {
        if (! function_exists('xdebug_get_headers')) {
            $this->markTestSkipped();
        }
        $this->csv->output("test.csv");
        $headers = \xdebug_get_headers();
        $this->assertSame($headers[0], "Content-Type: application/octet-stream");
        $this->assertSame($headers[1], "Content-Transfer-Encoding: binary");
        $this->assertSame($headers[2], "Content-Disposition: attachment; filename=\"test.csv\"");
    }

    public function testToString()
    {
        $expected = "john,doe,john.doe@example.com\njane,doe,jane.doe@example.com\n";
        $this->assertSame($expected, $this->csv->__toString());
    }
}
