<?php

namespace tests\unit;

use \WorldCatLD\Manifestation;
use \WorldCatLD\Work;
use PHPUnit\Framework\TestCase;

class WorkTest extends TestCase
{
    protected function setUp()
    {
        $className = get_class($this);
        $testName = $this->getName();
        echo " Test: {$className}->{$testName}\n";
        parent::setUp();
    }

    public function testClassDefinitions()
    {
        $work = new Work();
        $this->assertContains(
            'WorldCatLD\Graph',
            class_uses($work)
        );
        $this->assertContains(
            'WorldCatLD\Resource',
            class_uses($work)
        );
        $this->assertEquals(
            'http://worldcat.org/entity/work/id/',
            Work::ID_PREFIX
        );
    }

    public function testFindById()
    {
        $work = $this->getMockBuilder('\WorldCatLD\Work')
                     ->setMethods(['fetchWorkData'])
                     ->getMock();

        $work->expects($this->exactly(2))->method('fetchWorkData')
             ->with('http://worldcat.org/entity/work/id/4417312175');

        $work->findById('4417312175');
        $work->findById('http://worldcat.org/entity/work/id/4417312175');
    }

    public function testFindByOclcNumber()
    {
        $work = $this->getMockBuilder('\WorldCatLD\Work')
            ->setMethods(['fetchWorkData', 'createManifestation', 'addExample', 'getId'])
            ->getMock();
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
                              ->setMethods(['findByOclcNumber'])
                              ->getMock();
        $manifestation->expects($this->once())->method('findByOclcNumber')->with('846961644');
        $work->expects($this->once())->method('createManifestation')->will($this->returnValue($manifestation));
        $work->expects($this->once())->method('addExample')->with($manifestation);
        $work->expects($this->once())->method('getId')
             ->will($this->returnValue('http://worldcat.org/entity/work/id/4417312175'));
        $work->findByOclcNumber('846961644');
    }

    public function testFindByIsbn()
    {
        $work = $this->getMockBuilder('\WorldCatLD\Work')
            ->setMethods(['fetchWorkData', 'createManifestation', 'addExample', 'getId'])
            ->getMock();
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
                              ->setMethods(['findByIsbn'])
                              ->getMock();
        $manifestation->expects($this->once())->method('findByIsbn')->with('9781107367661');
        $work->expects($this->once())->method('createManifestation')->will($this->returnValue($manifestation));
        $work->expects($this->once())->method('addExample')->with($manifestation);
        $work->expects($this->once())->method('getId')
             ->will($this->returnValue('http://worldcat.org/entity/work/id/4417312175'));
        $work->findByIsbn('9781107367661');
    }

    public function testAddExample()
    {
        $manifestation = new Manifestation();
        $dir = dirname(__FILE__);
        $jsonld = file_get_contents($dir . '/fixtures/919758206.jsonld');
        $sourceData = json_decode($jsonld, true);
        $manifestation->setSourceData($sourceData);

        $work = $this->getMockBuilder('\WorldCatLD\Work')
                     ->setMethods(['getUnresolvedWorkExamples', 'hydrateExamples'])
                     ->getMock();
        $work->expects($this->once())->method('getUnresolvedWorkExamples')->will($this->returnValue([]));
        $work->addExample($manifestation);
        $example = $work->getWorkExample();
        $this->assertContains($manifestation, $example);
        $this->assertEquals('http://worldcat.org/entity/work/id/2444971821', $work->getId());
    }

    public function testAddExampleWithSourceData()
    {
        $manifestation = new Manifestation();
        $dir = dirname(__FILE__);
        $jsonld = file_get_contents($dir . '/fixtures/919758206.jsonld');
        $sourceData = json_decode($jsonld, true);
        $manifestation->setSourceData($sourceData);
        $work = $this->getMockBuilder('\WorldCatLD\Work')
                     ->setMethods(['getUnresolvedWorkExamples', 'hydrateExamples'])
                     ->getMock();
        $work->expects($this->once())->method('getUnresolvedWorkExamples')->will($this->returnValue([]));
        $work->addExample($sourceData);

        $this->assertEquals('http://worldcat.org/entity/work/id/2444971821', $work->getId());
        $example = $work->getWorkExample();
        $this->assertEquals(
            $manifestation->getGraphData(),
            $example['http://www.worldcat.org/oclc/919758206']->getGraphData()
        );
    }

    public function testHasUnresolvedWorkExamples()
    {
        \VCR\VCR::insertCassette('unresolvedWorkExamples');
        Work::$async = false;
        $work = new Work();
        $work->findById('2444971821');

        $this->assertTrue($work->hasUnresolvedWorkExamples());
        $work->getWorkExample();
        $this->assertFalse($work->hasUnresolvedWorkExamples());
        \VCR\VCR::eject();
    }
}
