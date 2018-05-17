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

    protected $redirectedExamples = [];

    /**
     * @param string $id
     */
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

    /**
     * @param string $id
     */
    public function findByOclcNumber($id)
    {
        $manifestation = $this->createManifestation();
        $manifestation->findByOclcNumber($id);
        $this->addExample($manifestation);
        $this->fetchWorkData($this->getId());
    }

    /**
     * @param string $isbn
     */
    public function findByIsbn($isbn)
    {
        $manifestation = $this->createManifestation();
        $manifestation->findByIsbn($isbn);
        $this->addExample($manifestation);
        $this->fetchWorkData($this->getId());
    }

    /**
     * @param Manifestation|array $example
     */
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
        $this->hydrateExamples($this->getUnresolvedWorkExamples());
        return $this->examples;
    }

    /**
     * @param array $ids
     */
    protected function hydrateExamples(array $ids)
    {
        if (!empty($ids)) {
            $resources = $this->fetchResources($ids);
            foreach ($resources as $id => $response) {
                if ($response['state'] === 'fulfilled') {
                    if ($response['value']->getStatusCode() === 200) {
                        $manifestation = new Manifestation();
                        $manifestation->setSourceData(json_decode($response['value']->getBody(), true));
                        $this->addExample($manifestation);
                        // Sometimes the examples of work actually resolve to a graph with a different id
                        if ($manifestation->getId() !== $id) {
                            $workExample = $this->getSubjectData()['workExample'];
                            $exampleIndex = array_search($id, $workExample);
                            $workExample[$exampleIndex] = $manifestation->getId();
                            $this->graph[$this->subjectDataIndex]['workExample'] = $workExample;
                            $this->redirectedExamples[$id] = $manifestation->getId();
                        }
                    } elseif ($response['value']->getStatusCode() === 404) {
                        $this->examples[$id] = null;
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getUnresolvedWorkExamples()
    {
        $subjectData = $this->getSubjectData();
        if (isset($subjectData['workExample'])) {
            if (is_array($subjectData['workExample'])) {
                $workExampleIds = $subjectData['workExample'];
            } else {
                $workExampleIds = [$subjectData['workExample']];
            }
        } else {
            $workExampleIds = [];
        }
        $workExampleIds = isset($subjectData['workExample']) ? $subjectData['workExample'] : [];
        return array_values(array_diff($workExampleIds, array_keys($this->examples)));
    }

    /**
     * @return bool
     */
    public function hasUnresolvedWorkExamples()
    {
        $unresolvedWorkExamples = $this->getUnresolvedWorkExamples();
        return !empty($unresolvedWorkExamples);
    }

    /**
     * Returns an associative array of URIs in the original workExample array that resolved to a different
     * Manifestation id.  The value is the redirected URI.
     *
     * @return array
     */
    public function getRedirectedExamples()
    {
        return $this->redirectedExamples;
    }

    /**
     * @param string $id
     */
    protected function fetchWorkData($id)
    {
        if (strpos($id, self::ID_PREFIX) === 0) {
            $location = $this->getRedirectLocation($id);
            if (is_array($location)) {
                $id = $location[0];
            } else {
                $id = $location;
            }
        }
        $this->fetchResourceData($id);
    }

    /**
     * For mocking
     *
     * @return Manifestation
     */
    protected function createManifestation()
    {
        return new Manifestation();
    }
}
