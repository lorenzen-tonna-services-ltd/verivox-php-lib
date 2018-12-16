<?php
namespace Verivox;

use Verivox\Request\BenchmarkRequest;
use Verivox\Request\LocationRequest;
use Verivox\Request\OfferRequest;
use Verivox\Request\Request;

class Verivox
{
    const REQUEST_TYPE_GAS = 'gas';
    const REQUEST_TYPE_ELECTRICITY = 'electricity';

    /**
     * @var string
     */
    private $partnerId;

    /**
     * @var string
     */
    private $campaignId;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;


    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setPartnerId($partnerId)
    {
        $this->partnerId = $partnerId;
    }

    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function getCampaignId()
    {
        return $this->campaignId;
    }

    public function getLocations($zipCode, $requestType)
    {
        $locationRequest = new LocationRequest();
        $locationRequest->setRequestType($requestType);
        $locationRequest->setZipCode($zipCode);

        return $this->doRequest($locationRequest);
    }

    public function getGasOffers($zipCode, $locationId, $duration, $annualTotal, $offPeakPercentage, $heatingPower)
    {
        $gasRequest = new OfferRequest();
        $gasRequest->setRequestType(self::REQUEST_TYPE_GAS);
        $gasRequest->setZipCode($zipCode);
        $gasRequest->setLocationId($locationId);
        $gasRequest->setMaxContractDuration($duration);
        $gasRequest->setAnnualTotal($annualTotal);
        $gasRequest->setOffPeakPercentage($offPeakPercentage);
        $gasRequest->setHeatingPower($heatingPower);

        return $this->doRequest($gasRequest);
    }

    public function getElectricityOffers($zipCode, $locationId, $duration, $annualTotal, $offPeakPercentage, $heatingPower)
    {
        $electricityRequest = new OfferRequest();
        $electricityRequest->setRequestType(self::REQUEST_TYPE_ELECTRICITY);
        $electricityRequest->setZipCode($zipCode);
        $electricityRequest->setLocationId($locationId);
        $electricityRequest->setMaxContractDuration($duration);
        $electricityRequest->setAnnualTotal($annualTotal);
        $electricityRequest->setOffPeakPercentage($offPeakPercentage);
        $electricityRequest->setHeatingPower($heatingPower);

        return $this->doRequest($electricityRequest);

    }

    public function getBenchmarkOffers($zipCode, $requestType, $usage, $heatingPower, $locationId)
    {
        $benchmarkRequest = new BenchmarkRequest();
        $benchmarkRequest->setRequestType($requestType);
        $benchmarkRequest->setZipCode($zipCode);
        $benchmarkRequest->setAnnualTotal($usage);
        $benchmarkRequest->setHeatingPower($heatingPower);
        $benchmarkRequest->setLocationId($locationId);

        return $this->doRequest($benchmarkRequest);
    }

    private function doRequest(Request $request)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_URL, $request->getRequestUrl($this->partnerId, $this->campaignId));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request->getHeaders());
        $requestXML = $request->getXML();
        if (!empty($requestXML)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS,  $request->getXML());
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
        curl_close($ch);

        $rs = $request->getResultSet($result);

        return $rs;
    }

}
