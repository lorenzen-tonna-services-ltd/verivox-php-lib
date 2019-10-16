<?php
namespace Verivox\Request;

class TariffRequest implements Request
{
    const REQUEST_GAS = 'gas';
    const REQUEST_ELECTRICITY = 'electricity';

    /**
     * @var int
     */
    private $zipCode;

    /**
     * @var int
     */
    private $locationId;

    /**
     * @var int
     */
    private $supplierId;

    /**
     * @var string
     */
    private $requestType;

    /**
     * @var string
     */
    private $profile = 'private';

    public function getHeaders()
    {
        return [
            'Accept: application/vnd.verivox.energyTariffsByProvider-v1+xml'
        ];
    }

    public function getXML()
    {
        return null;
    }

    public function setSupplierId($supplierId)
    {
        $this->supplierId = $supplierId;
    }

    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    public function getRequestUrl($partnerId, $campaignId)
    {
        $url = 'https://www.verivox.de/servicehook/suppliers/' . $this->requestType . '/' . $this->supplierId . '/'. $this->profile .'/location/' . $this->zipCode . '/';
        if (!empty($this->locationId)) {
            $url .= $this->locationId .'/';
        }

        $url .= '?partnerId=' . $partnerId;

        return $url;
    }

    public function getResultSet($raw)
    {
        $xml = new \SimpleXMLElement($raw);

        $tariffs = [];
        foreach ($xml->provider as $obj) {
            foreach ($obj->products->private->product as $product) {
                $tariffs[(int)$product->attributes()['id']] = (string)$product->name->content->text;
            }
        }

        return [
            'tariffs' => $tariffs
        ];
    }
}