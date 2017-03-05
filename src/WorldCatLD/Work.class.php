<?php
namespace WorldCatLD;

/**
 * Class Work
 * @package WorldCatLD
 */
class Work
{
    use Graph;
    use Resource;

    const ID_PREFIX = 'http://worldcat.org/entity/work/id/';

    /** @var Manifestation[]  */
    protected $examples = [];

    public function findById($id)
    {
        $url = parse_url($id);
        if (isset($url['scheme'])) {
            $workId = $id;
        } else {
            $workId = Work::ID_PREFIX . $id;
        }
        $this->fetchWorkData($workId);
    }

    public function findByOclcNumber($id)
    {
        $manifestation = new Manifestation();
        $manifestation->findByOclcNumber($id);
        $this->addExample($manifestation);
        $this->fetchWorkData($this->getId());
    }

    public function findByIsbn($isbn)
    {
        $manifestation = new Manifestation();
        $manifestation->findByIsbn($isbn);
        $this->addExample($manifestation);
        $this->fetchWorkData($this->getId());
    }

    public function addExample($example)
    {
        if (is_array($example)) {
            $exampleData = $example;
            $example = new Manifestation();
            $example->setSourceData($exampleData);
        }

        if ($example instanceof Manifestation) {
            $this->examples[$example->getId()] = $example;
            if (!isset($this->id)) {
                $this->id = $example->getWorkId();
            }
        }
    }

    /**
     * @return Manifestation[]
     */
    public function getWorkExample()
    {
        $this->hydrateExamples(
            array_diff(array_keys($this->examples), $this->getSourceData()['workExample'])
        );
        return $this->examples;
    }

    protected function hydrateExamples(array $ids)
    {
        if (!empty($ids)) {
            $resources = $this->fetchResources($ids);
            foreach ($resources as $id => $data) {
                $manifestation = new Manifestation();
                $manifestation->setSourceData(json_encode($data, true));
                $this->addExample($manifestation);
            }

        }
    }

    protected function fetchWorkData($id)
    {
        if (strpos($id, self::ID_PREFIX) === 0) {
            $location = $this->getRedirectLocation($id);
            $id = $location[0];
        }
        $this->fetchResourceData($id);
    }

}