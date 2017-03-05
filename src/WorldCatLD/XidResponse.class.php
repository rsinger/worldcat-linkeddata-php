<?php
namespace WorldCatLD;

class XidResponse
{
    protected $manifestations = [];

    public function addManifestations(array $manifestations)
    {
        foreach ($manifestations as $manifestation) {
            $this->addManifestation($manifestation);
        }
    }

    public function addManifestation(Manifestation $manifestation)
    {
        $this->manifestations[] = $manifestation->toXid();
    }

    public function toArray($fl = '*')
    {
        return ['stat' => 'ok', 'list' => $this->manifestations];
    }

}