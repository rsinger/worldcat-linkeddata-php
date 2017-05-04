<?php
namespace WorldCatLD;

class XidResponse
{
    protected $manifestations = [];

    /**
     * @param array $manifestations
     */
    public function addManifestations(array $manifestations)
    {
        foreach ($manifestations as $manifestation) {
            $this->addManifestation($manifestation);
        }
    }

    /**
     * @param Manifestation $manifestation
     */
    public function addManifestation(Manifestation $manifestation)
    {
        $this->manifestations[] = $manifestation->toXid();
    }

    /**
     * @param string $fl
     * @return array
     */
    public function toArray($fl = '*')
    {
        return ['stat' => 'ok', 'list' => $this->manifestations];
    }

}