<?php

namespace tests\unit;

use \WorldCatLD\Manifestation;
use PHPUnit\Framework\TestCase;

class ManifestationTest extends TestCase
{
	public function testClassDefinitions()
	{
		$manifestation = new Manifestation();
		$this->assertContains(
            'WorldCatLD\Graph',
            class_uses($manifestation)
        );
        $this->assertContains(
            'WorldCatLD\Resource',
            class_uses($manifestation)
        );
        $this->assertEquals(
            'http://www.worldcat.org/oclc/',
            Manifestation::ID_PREFIX
        );
	}
    
    public function testFindById()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['fetchResourceData'])
            ->getMock();
        
        $manifestation->expects($this->once())->method('fetchResourceData')
            ->with('http://example.com/1234');
        
        $manifestation->findById('http://example.com/1234');
    }
    
    public function testFindByIdWithOCLCNumber()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['fetchResourceData', 'findByOclcNumber'])
            ->getMock();
        
        $manifestation->expects($this->never())->method('fetchResourceData');
        $manifestation->expects($this->once())->method('findByOclcNumber')
            ->with('1234');
        
        $manifestation->findById('1234');
    }
    
    public function testFindByOCLCNumber()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['fetchResourceData'])
            ->getMock();
        
        $manifestation->expects($this->once())->method('fetchResourceData')
            ->with('http://www.worldcat.org/oclc/1234');
        
        $manifestation->findByOclcNumber('1234');
    }
    
    public function testFindByOCLCNumberDigitsOnly()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['fetchResourceData'])
            ->getMock();
        
        $manifestation->expects($this->once())->method('fetchResourceData')
            ->with('http://www.worldcat.org/oclc/1234');
        
        $manifestation->findByOclcNumber('abc12def34ghi');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid OCLC number sent
     */
    public function testFindByOCLCNumberThrowIfNotValid()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['fetchResourceData'])
            ->getMock();
        
        $manifestation->findByOclcNumber('abcdefghi');
    }    
    
    public function testFindByIsbn()
    {
                /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['getRedirectLocation', 'findById'])
            ->getMock();
        
        $manifestation->expects($this->once())->method('getRedirectLocation')
            ->with('http://www.worldcat.org/isbn/0123456789')
            ->will($this->returnValue(['http://www.worldcat.org/oclc/1234']));
        
        $manifestation->expects($this->once())->method('findById')
            ->with('http://www.worldcat.org/oclc/1234');
        
        $manifestation->findByIsbn('0123456789');
    }
    
    public function testGetWorkId()
    {
        $sourceData = [
            '@graph' => [
                [
                    '@id' => 'http://www.worldcat.org/oclc/1234',
                    'exampleOfWork' => 'http://www.worldcat.org/work/9876'
                ]
            ]
        ];
        
        $manifestation = new Manifestation();
        $manifestation->setSourceData($sourceData);
        $this->assertEquals(
            'http://www.worldcat.org/work/9876',
            $manifestation->getWorkId()
        );
    }
    
    public function testGetExampleOfWork()
    {
        $sourceData = [
            '@graph' => [
                [
                    '@id' => 'http://www.worldcat.org/oclc/1234',
                    'exampleOfWork' => 'http://www.worldcat.org/work/9876'
                ]
            ]
        ];
        
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['createWork'])
            ->getMock();
        /** @var \WorldCatLD\Work|PHPUnit_Framework_MockObject_MockObject **/
        $work = $this->getMockBuilder('\WorldCatLD\Work')
            ->setMethods(['findById', 'addExample'])
            ->getMock();
        
        $manifestation->expects($this->once())->method('createWork')
            ->will($this->returnValue($work));
        
        $work->expects($this->once())->method('findById')
            ->with('http://www.worldcat.org/work/9876');
        $work->expects($this->once())->method('addExample')
            ->with($manifestation);
        
        $manifestation->setSourceData($sourceData);
        
        $manifestation->getExampleOfWork();
        // Ensure we're lazy loading only once
        $manifestation->getExampleOfWork();
    }
    
    public function testGetWork()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['getExampleOfWork'])
            ->getMock();
        
        $manifestation->expects($this->once())->method('getExampleOfWork');
        
        $manifestation->getWork();
    }
    
    public function testGetOclcNumber()
    {
        /** @var Manifestation|PHPUnit_Framework_MockObject_MockObject **/
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['getId'])
            ->getMock();        
        $manifestation->expects($this->exactly(2))
            ->method('getId')
            ->will(
                $this->onConsecutiveCalls(
                        null, 'http://www.worldcat.org/oclc/1234'
                    )
                );
        
        $this->assertNull($manifestation->getOclcNumber());
        $this->assertEquals('1234', $manifestation->getOclcNumber());
    }
    
    public function testGetIsbns()
    {
        $manifestation = new Manifestation();
        $dir = dirname(__FILE__);
        $jsonld = file_get_contents($dir . '/fixtures/919758206.jsonld');        
        $sourceData = json_decode($jsonld, true);
        $manifestation->setSourceData($sourceData);
        $this->assertEquals(
            ['9783037196519', '3037196513'],
            $manifestation->getIsbns()
        );
    }
}