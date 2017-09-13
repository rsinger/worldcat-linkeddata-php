<?php
namespace WorldCatLD;

class Entity
{
    use Graph;

    /**
     * @param array $entityData
     */
    public function __construct(array $entityData)
    {
        $this->id = $entityData['@id'];
        $this->setSourceData($entityData);
    }

    /**
     * @param array $data
     */
    public function setSourceData(array $data)
    {
        $this->graph = [$data];
        $this->subjectDataIndex = 0;
    }
}
