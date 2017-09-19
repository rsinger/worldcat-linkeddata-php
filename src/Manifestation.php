<?php
namespace WorldCatLD;

class Manifestation
{
    use Graph;
    use Resource;

    const ID_PREFIX = 'http://www.worldcat.org/oclc/';

    protected $oclcNumber;

    protected $workId;

    /** @var  Work */
    protected $work;

    /**
     * @param string $id
     */
    public function findById($id)
    {
        $url = parse_url($id);
        if (isset($url['scheme'])) {
            $this->fetchResourceData($id);
            $this->id = $id;
        } else {
            $this->findByOclcNumber($id);
        }
    }

    /**
     * @param string $id
     * @throws \InvalidArgumentException
     */
    public function findByOclcNumber($id)
    {
        $id = preg_replace('/\D/', '', $id);
        if (empty($id)) {
            throw new \InvalidArgumentException('Invalid OCLC number sent');
        }
        $url = self::ID_PREFIX . $id;
        $this->fetchResourceData($url);
        $this->id = $url;
    }

    /**
     * @param string $isbn
     * @throws \InvalidArgumentException
     */
    public function findByIsbn($isbn)
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (strlen($isbn) !== 10 && strlen($isbn) !== 13) {
            throw new \InvalidArgumentException('ISBN length not 10 or 13');
        }
        $url = "{$this->baseUrl}isbn/{$isbn}";
        $location = $this->getRedirectLocation($url);
        if (\is_array($location)) {
            $location = $location[0];
        }
        if (\strpos($location, self::ID_PREFIX) === false) {
            $location = self::ID_PREFIX . \basename($location);
        }

        if (!empty($location)) {
            $this->findById($location);
        }
    }

    /**
     * @return string
     */
    public function getWorkId()
    {
        if (!isset($this->workId)) {
            $this->workId = $this->getSubjectData()['exampleOfWork'];
        }
        return $this->workId;
    }

    /**
     * @return Work
     */
    public function getExampleOfWork()
    {
        if (!isset($this->work)) {
            $workId = $this->getWorkId();
            $work = $this->createWork();
            $work->findById($workId);
            $work->addExample($this);
            $this->work = $work;
        }
        return $this->work;
    }

    /**
     * @return Work
     */
    public function getWork()
    {
        return $this->getExampleOfWork();
    }

    /**
     * @return string
     */
    public function getOclcNumber()
    {
        if (!isset($this->oclcNumber)) {
            $id = $this->getId();
            if ($id) {
                $uriParts = explode('/', $id);
                $this->oclcNumber = array_pop($uriParts);
            }
        }
        return $this->oclcNumber;
    }

    /**
     * @param \WorldCatLD\Work $work
     */
    public function setWork($work)
    {
        $this->work = $work;
    }

    /**
     * @return array
     */
    public function getIsbns()
    {
        foreach ($this->graph as $graph) {
            if (isset($graph['isbn'])) {
                return $graph['isbn'];
            }
        }
        return [];
    }

    protected function createWork()
    {
        return new Work();
    }
}
