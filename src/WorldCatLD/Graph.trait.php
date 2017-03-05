<?php
namespace WorldCatLD;

/**
 * Class Graph
 * @package WorldCatLD
 */
trait Graph
{
    protected $id;

    protected $context = [];

    protected $graph = [];
    protected $sourceData = [];

    protected $subjectDataIndex;

    public function getGraphData()
    {
        return ['@context' => $this->context, '@graph' => $this->graph];
    }

    public function getSubjectData()
    {
        if (!isset($this->subjectDataIndex)) {
            $this->findSubjectDataIndex();
        }

        return $this->graph[$this->subjectDataIndex];
    }

    protected function findSubjectDataIndex()
    {
        $className = __CLASS__;
        foreach ($this->graph as $index => $graph) {
            if (isset($graph['@id']) && strpos($graph['@id'], $className::ID_PREFIX) === 0) {
                $this->subjectDataIndex = $index;
                $this->id = $graph['@id'];
                break;
            }
        }
    }

    /**
     * @param array $sourceData
     */
    public function setSourceData(array $sourceData)
    {
        if (isset($sourceData['@context'])) {
            $this->context = $sourceData['@context'];
        }
        if (isset($sourceData['@graph'])) {
            $this->graph = $sourceData['@graph'];
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        if (!isset($this->id)) {
            $this->findSubjectDataIndex();
        }
        return $this->id;
    }

    public function getType()
    {
        return $this->getSubjectData()['@type'];
    }

    public function getPropertyNames() {
        return array_keys($this->getSubjectData());
    }

    public function __get($name) {
        $subject = $this->getSubjectData();
        if (isset($subject[$name])) {
            return $this->hydrateProperty($name);
        } elseif (isset($subject['@' . $name])) {
            return $this->hydrateProperty('@' . $name);
        }
    }

    protected function hydrateProperty($name)
    {
        if (method_exists($this, 'get' . ucfirst($name))) {
            $method = 'get' . ucfirst($name);
            return $this->$method();
        }
        $subject = $this->getSubjectData();
        if (is_array($subject[$name])) {
            $values = [];
            foreach ($subject[$name] as $value) {
                $values[] = $this->hydratePropertyValue($value);
            }
            return $values;
        } else {
            return $this->hydratePropertyValue($subject[$name]);
        }
    }

    protected function hydratePropertyValue($value)
    {
        if (is_array($value)) {
            $value['@id'] = '_' . uniqid();
            $entity = new Entity($value);
            return $entity;
        }
        if (strpos($value, 'http') === 0) {
            $resource = $this->getResourceFromGraph($value);
            if ($resource) {
                $entity = new Entity($resource);
                return $entity;
            }
        }
        return $value;
    }

    protected function getResourceFromGraph($id)
    {
        foreach ($this->graph as $graph) {
            if ($graph['@id'] === $id) {
                return $graph;
            }
        }
        return null;
    }
}