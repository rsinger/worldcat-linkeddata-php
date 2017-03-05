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

    public function findByIsbn($isbn)
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (strlen($isbn) !== 10 && strlen($isbn) !== 13) {
            throw new \InvalidArgumentException('ISBN length not 10 or 13');
        }
        $url = "{$this->baseUrl}isbn/{$isbn}";
        $location = $this->getRedirectLocation($url);
        if (!empty($location)) {
            $manifestationUrl = explode('/', $location[0]);
            $oclcNumber = array_pop($manifestationUrl);
            $this->findByOclcNumber($oclcNumber);
        }
    }


    public function getWorkId()
    {
        if (!isset($this->workId)) {
            $this->workId = $this->getSubjectData()['exampleOfWork'];
        }
        return $this->workId;
    }

    public function getExampleOfWork()
    {
        if (!isset($this->work)) {
            $workId = $this->getWorkId();
            $this->work = new Work();
            $this->work->findById($workId);
            $this->work->addExample($this);
        }
        return $this->work;
    }

    public function getWork()
    {
        return $this->getExampleOfWork();
    }

    /**
     * @return mixed
     */
    public function getOclcNumber()
    {
        if (!isset($this->oclcNumber)) {
            $uriParts = explode('/', $this->getId());
            $this->oclcNumber = array_pop($uriParts);
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

    public function toXid()
    {
        $data = [];
        if ($this->oclcnum) {
            if (!is_array($this->oclcnum)) {
                $data['oclcnum'] = [$this->oclcnum];
            } else {
                $data['oclcnum'] = $this->oclcnum;
            }
        }

        $isbns = $this->getIsbns();
        if (!empty($isbns)) {
            $data['isbn'] = $isbns;
        }
        if ($this->creator) {

        }
        return $data;
    }

    public function getIsbns()
    {
        foreach ($this->graph as $graph) {
            if (isset($graph['isbn'])) {
                return $graph['isbn'];
            }
        }
        return [];
    }
}