<?php

namespace tests\unit;

use \WorldCatLD\Manifestation;
use \WorldCatLD\Work;
use PHPUnit\Framework\TestCase;

class ManifestationTest extends TestCase
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
                    '@type' => [],
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
                    '@type' => [],
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
                    null,
                    'http://www.worldcat.org/oclc/1234'
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

    public function testGetProperties()
    {
        $manifestation = $this->getMockBuilder('\WorldCatLD\Manifestation')
            ->setMethods(['getExampleOfWork'])
            ->getMock();

        $manifestation->expects($this->once())->method('getExampleOfWork');
        $dir = dirname(__FILE__);
        $jsonld = file_get_contents($dir . '/fixtures/919758206.jsonld');
        $sourceData = json_decode($jsonld, true);
        $manifestation->setSourceData($sourceData);
        $properties = ['@id', '@type', 'oclcnum', 'placeOfPublication', 'about',
            'author', 'bookFormat', 'datePublished', 'description', 'exampleOfWork',
            'genre', 'inLanguage', 'isPartOf', 'isSimilarTo', 'name', 'productID',
            'url', 'workExample', 'describedby'];
        $this->assertEquals($properties, $manifestation->getPropertyNames());
        $this->assertEquals(
            'http://www.worldcat.org/oclc/919758206',
            $manifestation->id
        );
        $this->assertEquals(
            ['schema:CreativeWork', 'schema:MediaObject', 'schema:Book'],
            $manifestation->type
        );
        $this->assertEquals('919758206', $manifestation->oclcnum);
        $this->assertInstanceOf(
            '\WorldCatLD\Entity',
            $manifestation->placeOfPublication
        );
        $this->assertEquals(
            'schema:Place',
            $manifestation->placeOfPublication->type
        );
        $this->assertEquals(
            'sz',
            $manifestation->placeOfPublication->identifier
        );
        $this->assertContainsOnly('\WorldCatLD\Entity', $manifestation->about);
        $this->assertCount(7, $manifestation->about);
        $this->assertInstanceOf(
            '\WorldCatLD\Entity',
            $manifestation->author
        );
        $this->assertEquals('Olivier Lablée', $manifestation->author->name);
        $this->assertEquals('schema:EBook', $manifestation->bookFormat);
        $this->assertEquals('2015', $manifestation->datePublished);
        $this->assertEquals(
            [
                'Introduction to spectral geometry -- Detailed introduction to abstract spectral theory. Linear operators ; Closed operators ; Adjoint operators ; Spectrums and resolvent set ; Spectral theory of compact operators ; The spectral theorem multiplication operator form for unbounded operators on a Hilbert space ; Some complements on operators theory -- The Laplacian on a compact Riemannian manifold. Basic Riemannian geometry ; Analysis on manifolds -- Spectrum on the Laplacian on a compact manifold. Physical examples ; A class of spectral problems ; Spectral theorem for the Laplacian ; A detailed proof by a variational approach ; The minimax principle and applications ; Complements: the Schrödinger operator and the Hodge-de Rham Laplacian -- Direct problems in spectral geometry. Explicit calculation of the spectrum ; Qualitative properties of the spectrum ; The spectral partition function Z[subscript M] ; Eigenvalues and eigenfunctions of surfaces -- Intermezzo: "Can one hear the holes of a drum?" The main result ; Some useful spaces ; Electrostatic capacity and the Poincaré inequality ; A detailed proof of the main Theorem 6.1.1. -- Inverse problems in spectral geometry. Can one hear the shape of a drum? ; Length spectrum and trace formulas ; Milnor\'s counterexample ; Prescribing the spectrum on a manifold ; Heat kernel and spectral geometry ; The Minakshisundaram-Pleijel expansion and the Weyl formula ; Two planar isospectral nonisometric domains ; Few words about Laplacian and conformal geometry.',
                '"Spectral theory is a diverse area of mathematics that derives its motivations, goals and impetus from several sources. In particular, the spectral theory of the Laplacian on a compact Riemannian manifold is a central object in differential geometry. From a physical point of view, the Laplacian on a compact Riemannian manifold is a fundamental linear operator which describes numerous propagation phenomena: heat propagation, wave propagation, quantum dynamics, etc. Moreover, the spectrum of the Laplacian contains vast information about the geometry of the manifold. This book gives a self-contained introduction to spectral geometry on compact Riemannian manifolds, Starting with an overview of spectral theory on Hilbert spaces, the book proceeds to a description of the basic notions in Riemannian geometry. Then it makes its way to topics of main interests in spectral geometry.The topics presented include direct and inverse problems. Direct problems are concerned with computing or finding properties on the eigenvalues while the main issue in inverse problems is "knowing the spectrum of the Laplacian, can we determine the geometry of the manifold?" Addressed to students or young researchers, the present book is a first introduction in spectral theory applied to geometry. For readers interested in pursuing the subject further, this book will provide a basis for understanding principles, concepts and developments of spectral geometry"--Publisher\'s information.'
            ],
            $manifestation->description
        );
        $this->assertEquals('Electronic books', $manifestation->genre);
        $this->assertEquals('en', $manifestation->inLanguage);
        $this->assertInstanceOf('\WorldCatLD\Entity', $manifestation->isPartOf);
        $this->assertEquals(
            ['EMS textbooks in mathematics', 'EMS textbooks in mathematics.' ],
            $manifestation->isPartOf->name
        );
        $this->assertInstanceOf('\WorldCatLD\Entity', $manifestation->isSimilarTo);
        $this->assertEquals(
            'http://www.worldcat.org/oclc/919758206',
            $manifestation->isSimilarTo->isSimilarTo
        );
        $this->assertEquals(
            'Spectral theory in Riemannian geometry',
            $manifestation->name
        );
        $this->assertEquals('919758206', $manifestation->productID);
        $this->assertEquals(
            [
                'http://search.ebscohost.com/login.aspx?direct=true&scope=site&db=nlebk&db=nlabk&AN=1131556',
                'http://www.ems-ph.org/books/book.php?proj_nr=186'
            ],
            $manifestation->url
        );
        $this->assertInstanceOf(
            '\WorldCatLD\Entity',
            $manifestation->workExample
        );
        $this->assertEquals(
            ['9783037196519', '3037196513'],
            $manifestation->workExample->isbn
        );
        $this->assertInstanceOf(
            '\WorldCatLD\Entity',
            $manifestation->describedby
        );
        $manifestation->exampleOfWork;
    }

    public function testSetWork()
    {
        $manifestation = new Manifestation();
        $work = new Work();
        $manifestation->setWork($work);

        $this->assertSame($work, $manifestation->getWork());
    }
}
