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

    /**
     * @var array
     */
    protected $languagePreferences = [];

    /**
     * @return array
     */
    public function getGraphData()
    {
        return ['@context' => $this->context, '@graph' => $this->graph];
    }

    /**
     * @return mixed
     */
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
            if (isset($graph['@id'])) {
                if ($this->id && $graph['@id'] === $this->id) {
                    $this->subjectDataIndex = $index;
                    break;
                } elseif (strpos($graph['@id'], $className::ID_PREFIX) === 0 &&
                    isset($graph['@type']) && is_array($graph['@type'])) {
                    $this->subjectDataIndex = $index;
                    $this->id = $graph['@id'];
                    break;
                }
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
     * @return string
     */
    public function getId()
    {
        if (!isset($this->id)) {
            $this->findSubjectDataIndex();
        }
        return $this->id;
    }

    /**
     * @return array|string
     */
    public function getType()
    {
        return $this->getSubjectData()['@type'];
    }

    /**
     * @return array
     */
    public function getPropertyNames()
    {
        return array_keys($this->getSubjectData());
    }

    /**
     * @param string $name
     * @return array|Entity
     */
    public function __get($name)
    {
        $subject = $this->getSubjectData();
        if (isset($subject[$name])) {
            return $this->hydrateProperty($name);
        } elseif (isset($subject['@' . $name])) {
            if ($name === 'id') {
                return $this->getId();
            }
            return $this->hydrateProperty('@' . $name);
        }
    }

    /**
     * @param string $name
     * @return array|Entity|string
     */
    protected function hydrateProperty($name)
    {
        if (method_exists($this, 'get' . ucfirst($name))) {
            $method = 'get' . ucfirst($name);
            return $this->$method();
        }
        $subject = $this->getSubjectData();
        if (is_array($subject[$name])) {
            $languages = $this->getLanguagePreferences();

            if (isset($subject[$name]['@value'])) {
                return $subject[$name]['@value'];
            }
            $values = [];
            $langVals = [];
            foreach ($subject[$name] as $value) {
                // TODO: clean this up
                if (is_array($value) && array_key_exists('@language', $value)) {
                    $idx = array_search($value['@language'], $languages);
                    if (!isset($langVals[$value['@language']])) {
                        $langVals[$value['@language']] = [];
                    }
                    $langVals[$value['@language']][] = $value['@value'];
                } else {
                    $values[] = $this->hydratePropertyValue($value);
                }
            }
            if (!empty($langVals)) {
                foreach ($languages as $language) {
                    if (isset($langVals[$language])) {
                        $values = array_merge($values, $langVals[$language]);
                        break;
                    }
                }
            }
            if (count($values) === 1) {
                return $values[0];
            }
            return $values;
        } else {
            return $this->hydratePropertyValue($subject[$name]);
        }
    }

    /**
     * @param string $value
     * @return Entity|string
     */
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
                $entity->setLanguagePreferences(
                    $this->getLanguagePreferences()
                );
                return $entity;
            }
        }
        return $value;
    }

    /**
     * @param string $id
     * @return array|null
     */
    protected function getResourceFromGraph($id)
    {
        foreach ($this->graph as $graph) {
            if ($graph['@id'] === $id) {
                return $graph;
            }
        }
        return null;
    }

    /**
     * Returns the preferred order of languages, will always include 'en'
     * @return array
     */
    public function getLanguagePreferences()
    {
        if (!in_array('en', $this->languagePreferences)) {
            $this->languagePreferences[] = 'en';
        }
        return $this->languagePreferences;
    }

    /**
     * Sets the order of preferred languages to return literals
     *
     * @param array $languagePreferences
     */
    public function setLanguagePreferences(array $languagePreferences)
    {
        $this->languagePreferences = $languagePreferences;
    }
}
