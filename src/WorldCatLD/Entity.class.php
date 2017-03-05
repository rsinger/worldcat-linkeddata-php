<?php
namespace WorldCatLD;

class Entity
{
    use Graph;

    public function __construct(array $entityData)
    {
        $this->id = $entityData['@id'];
        $this->setSourceData($entityData);
    }

    public function setSourceData(array $data)
    {
        $this->graph = [$data];
        $this->subjectDataIndex = 0;
    }
}