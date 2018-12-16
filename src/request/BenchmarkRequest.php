<?php
namespace Verivox\Request;

class BenchmarkRequest implements Request
{
    /**
     * @var int
     */
    private $zipCode;

    /**
     * @var string
     */
    private $requestType;

    /**
     * @var string
     */
    private $duration;

    /**
     * @var string
     */
    private $locationId;

    /**
     * @var int
     */
    private $annualTotal;

    /**
     * @var float
     */
    private $heatingPower;

    public function getHeaders()
    {
        return [
            'Accept: application/vnd.verivox.' . $this->requestType . 'Benchmarks-v1+xml'
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

    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    public function setAnnualTotal($annualTotal)
    {
        $this->annualTotal = $annualTotal;
    }

    public function setHeatingPower($heatingPower)
    {
        $this->heatingPower = $heatingPower;
    }

    public function getRequestUrl($partnerId, $campaignId)
    {
        $url  = 'https://www.verivox.de/servicehook/benchmarks/' . $this->requestType . '/profiles/h0/locations/' . $this->zipCode . '/';
        if (!empty($this->locationId)) {
            $url .= $this->locationId . '/';
        }

        $url .= '?partnerId=' . $partnerId . '&campaignId=' . $campaignId;
        if (!empty($this->annualTotal)) {
            $url .= '&usage=' . $this->annualTotal;
        }

        if (!empty($this->heatingPower)) {
            $url .= '&heatingPower=' . $this->heatingPower;
        }

        return $url;
    }

    public function getResultSet($xml)
    {
        return $xml;
    }
}