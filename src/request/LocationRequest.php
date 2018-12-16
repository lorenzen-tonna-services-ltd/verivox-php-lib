<?php
namespace Verivox\Request;

class LocationRequest implements Request
{
    const REQUEST_GAS = 'gas';
    const REQUEST_ELECTRICITY = 'electricity';

    /**
     * @var int
     */
    private $zipCode;

    /**
     * @var string
     */
    private $requestType;

    public function getHeaders()
    {
        return [
            'Accept: application/vnd.verivox.energyLocation-v2+xml'
        ];
    }

    public function getXML()
    {
        return null;
    }

    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    public function getRequestUrl($partnerId, $campaignId)
    {
        if (self::REQUEST_ELECTRICITY == $this->requestType) {
            return 'https://www.verivox.de/servicehook/locations/electricity/postCode/' . $this->zipCode . '/?partnerId=' . $partnerId;
        }
        return 'https://www.verivox.de/servicehook/locations/gas/postCode/' . $this->zipCode . '/?partnerId=' . $partnerId;
    }

    public function getResultSet($raw)
    {
        $xml = new \SimpleXMLElement($raw);

        $locations = [];
        foreach ($xml->location as $obj) {
            $locations[(int)$obj->attributes()['id']] = (string)$obj->fullName;
        }

        return [
            'main' => (string)$xml->attributes()['mainName'],
            'locations' => $locations
        ];
    }
}