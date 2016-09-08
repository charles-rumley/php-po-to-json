<?php namespace CharlesRumley\Tests;

use CharlesRumley\PoToJson;
use PHPUnit_Framework_TestCase;

class PoToJsonTest extends PHPUnit_Framework_TestCase
{
    public function testItConvertsPoToJson()
    {
        $expectedJson = file_get_contents($this->fixturePath('pl.json'));

        // todo this API should expect a string, not a path
        $poToJsonConverter = new PoToJson();
        $actualJson = $poToJsonConverter->withPoFile($this->fixturePath('pl.po'))->toRawJson();

        $this->assertEquals($this->reformatJson($expectedJson), $this->reformatJson($actualJson));
    }

    private function fixturePath($withFilename = null)
    {
        return sprintf('%s/%s/%s', __DIR__, 'fixtures', $withFilename);
    }

    public function testItConvertsPoToJedCompatibleJson()
    {
        $expectedJson = file_get_contents($this->fixturePath('pl-jed.json'));

        // todo this API should expect a string, not a path
        $poToJsonConverter = new PoToJson($this->fixturePath('pl.po'));
        $actualJson = $poToJsonConverter->withPoFile($this->fixturePath('pl.po'))->toJedJson();

        $this->assertEquals($this->reformatJson($expectedJson), $this->reformatJson($actualJson));
    }

    private function reformatJson($json)
    {
        return json_encode(json_decode($json), JSON_PRETTY_PRINT);
    }
}
