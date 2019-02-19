<?php
namespace Verivox\Request;

use Verivox\Verivox;

class OfferRequest implements Request
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
    private $guarantee;

    /**
     * @var string
     */
    private $prolongation;

    /**
     * @var string
     */
    private $cancellation;

    /**
     * @var string
     */
    private $locationId;

    /**
     * @var int
     */
    private $annualTotal;

    /**
     * @var int
     */
    private $ecoTariffOnly = 0;

    /**
     * @var int
     */
    private $offPeakPercentage;

    /**
     * @var float
     */
    private $heatingPower;

    /**
     * @var int
     */
    private $benchmarkTariffId;

    public function getHeaders()
    {
        if (Verivox::REQUEST_TYPE_ELECTRICITY == $this->requestType) {
            return [
                'Accept: application/vnd.verivox.' . $this->requestType . 'Offer-v6+xml',
                'Content-Type: application/vnd.verivox.' . $this->requestType . 'Criteria-v2+xml'
            ];
        }

        return [
            'Accept: application/vnd.verivox.' . $this->requestType . 'Offer-v5+xml',
            'Content-Type: application/vnd.verivox.' . $this->requestType . 'Criteria-v2+xml'
        ];
    }

    public function getXML()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');

        $xmlRoot = $dom->createElement("criteria");
        $xmlRoot->setAttribute('profile', 'h0');
        $xmlRoot->setAttribute('prepayment', "false");
        $xmlRoot->setAttribute('includePackageTariffs', "false");
        $xmlRoot->setAttribute('includeTariffsWithDeposit', "false");
        $xmlRoot->setAttribute('includeNonCompliantTariffs', "false");
        $xmlRoot->setAttribute('onlyProductsWithGoodCustomerRating', "false");
        $xmlRoot->setAttribute('onlyEcoTariffs', "false");
        $xmlRoot->setAttribute('bonusIncluded', "non-compliant");
        $xmlRoot->setAttribute('signupOnly', "true");
        $xmlRoot->setAttribute('maxResultsPerPage', 0);
        if (!empty($this->benchmarkTariffId)) {
            $xmlRoot->setAttribute('benchmarkTariffId', $this->benchmarkTariffId);
        } else {
            $xmlRoot->setAttribute('benchmarkTariffId', 0);
        }
        if (!empty($this->duration)) {
            $xmlRoot->setAttribute('maxContractDuration', $this->duration);
        }
        if (!empty($this->prolongation)) {
            $xmlRoot->setAttribute('maxContractProlongation', $this->prolongation);
        }
        if (!empty($this->locationId)) {
            $xmlRoot->setAttribute('paolaLocationId', $this->locationId);
        }
        $xmlRoot = $dom->appendChild($xmlRoot);

        if ($this->ecoTariffOnly == 1 && $this->requestType != 'gas') {
            $ecoOnly = $dom->createElement('includeEcoTariffs');
            $xmlRoot->appendChild($ecoOnly);
        }

        $usage = $dom->createElement("usage");

        if (Verivox::REQUEST_TYPE_ELECTRICITY == $this->requestType) {
            $usage->setAttribute('annualTotal', $this->annualTotal);
            $usage->setAttribute('offPeakPercentage', $this->offPeakPercentage);
        } else {
            $usage->setAttribute('annualTotal', $this->annualTotal);
            $usage->setAttribute('heatingPowerInKW', $this->heatingPower);
        }

        $xmlRoot->appendChild($usage);

        if (!empty($this->guarantee)) {
            $pg = $dom->createElement('priceGuarantee');
            $pg->setAttribute('minDurationInMonths', $this->guarantee);

            $xmlRoot->appendChild($pg);
        }

        if (!empty($this->cancellation)) {
            $cp = $dom->createElement('cancellationPeriod');
            if (substr($this->cancellation, 0, 1) == 'w') {
                $cp->setAttribute('amount', substr($this->cancellation, 1));
            } else {
                $cp->setAttribute('amount', $this->cancellation);
            }

            if (substr($this->cancellation, 0, 1) == 'w') {
                $cp->setAttribute('unit', 'week');
            } else {
                $cp->setAttribute('unit', 'month');
            }
            $xmlRoot->appendChild($cp);
        }

        return $dom->saveXML();
    }

    public function setEcoOnly($ecoOnly)
    {
        $this->ecoTariffOnly = (int)$ecoOnly;
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

    public function setMaxContractDuration($duration)
    {
        $this->duration = $duration;
    }

    public function setMaxProlongation($prolongation)
    {
        $this->prolongation = $prolongation;
    }

    public function setMaxCancellation($cancellation)
    {
        $this->cancellation = $cancellation;
    }

    public function setPriceGuarantee($priceGuarantee)
    {
        $this->guarantee = $priceGuarantee;
    }

    public function setAnnualTotal($annualTotal)
    {
        $this->annualTotal = $annualTotal;
    }

    public function setOffPeakPercentage($offPeakPercentage)
    {
        $this->offPeakPercentage = $offPeakPercentage;
    }

    public function setHeatingPower($heatingPower)
    {
        $this->heatingPower = $heatingPower;
    }

    public function setBenchmarkTariffId($id)
    {
        $this->benchmarkTariffId = $id;
    }

    public function getRequestUrl($partnerId, $campaignId)
    {
        if (Verivox::REQUEST_TYPE_ELECTRICITY == $this->requestType) {
            return 'https://www.verivox.de/servicehook/offers/electricity/postCode/' . $this->zipCode . '/?partnerId=' . $partnerId . '&campaignId=' . $campaignId;
        }
        return 'https://www.verivox.de/servicehook/offers/gas/postCode/' . $this->zipCode . '/?partnerId=' . $partnerId . '&campaignId=' . $campaignId;
    }

    public function getResultSet($raw)
    {
        $xml = new \SimpleXMLElement($raw);

        $return = [];

        foreach ($xml->offers as $container) {
            foreach ($container->offer as $offer) {
                $provider = $offer->provider;
                $providerAttributes = $provider->attributes();

                $tariff = $offer->tariff;
                $tariffAttributes = $tariff->attributes();

                $data = [
                    'provider' => [
                        'id' => (int)$providerAttributes['id'],
                        'logo' => (string)$providerAttributes['logoUrl'],
                        'name' => (string)$provider->content->text,
                        'url' => (string)$provider->link->attributes()['href']
                    ],
                    'tariff' => [
                        'id' => (int)$tariffAttributes['id'],
                        'permanentId' => (int)$tariffAttributes['permanentId'],
                        'name' => (string)$tariff->content->text,
                        'url' => (string)$offer->signup->responsive->attributes()['url'],
                        'eco' => (int)$tariffAttributes['isEcoTariff'],
                    ],
                    'cost' => [
                        'total' => (float)$offer->cost->totalCost->attributes()['amount'],
                        'total_no_bonus' => (float)$offer->cost->totalCostsExcludingSpecialBonuses->attributes()['amount'],
                        'savings' => (float)$offer->cost->savings->attributes()['amount'],
                        'currency' => (string)$offer->cost->totalCost->attributes()['unit'],
                        'items' => [],
                        'usage' => @(float)$offer->cost->usageCosts->attributes()['amount'],
                        'fixed' => @(float)$offer->cost->fixedCosts->attributes()['amount'],
                        'package' => @(float)$offer->cost->packageCosts->attributes()['amount'],
                        'setup' => @(float)$offer->cost->setupCosts->attributes()['amount'],
                        'bonus' => @(float)$offer->cost->sumOfOneTimeBonuses->attributes()['amount'],
                        'vat' => @(float)$offer->cost->vat->attributes()['percent'],
                        'unit' => @(float)$offer->cost->unitPrice->attributes()['amount'],
                    ],
                    'contract' => [],
                    'remark' => [],
                ];

                if (isset($offer->contractDetails->priceGuarantee)) {
                    $data['contract']['guarantee'] = [
                        'unit' => (string)$offer->contractDetails->priceGuarantee->attributes()['unit'],
                        'duration' => (int)$offer->contractDetails->priceGuarantee->attributes()['duration'],
                        'type' => (string)$offer->contractDetails->priceGuarantee->attributes()['type']
                    ];
                }

                if (isset($offer->contractDetails->cancellationPeriod)) {
                    $data['contract']['cancellation'] = [
                        'unit' => (string)$offer->contractDetails->cancellationPeriod->attributes()['unit'],
                        'duration' => (int)$offer->contractDetails->cancellationPeriod->attributes()['amount'],
                    ];
                }

                if (isset($offer->contractDetails->contractDuration)) {
                    $data['contract']['duration'] = [
                        'unit' => (string)$offer->contractDetails->contractDuration->attributes()['unit'],
                        'duration' => (int)$offer->contractDetails->contractDuration->attributes()['amount'],
                    ];
                }

                if (isset($offer->contractDetails->contractProlongation)) {
                    $data['contract']['prolongation'] = [
                        'unit' => (string)$offer->contractDetails->contractProlongation->attributes()['unit'],
                        'duration' => (int)$offer->contractDetails->contractProlongation->attributes()['amount'],
                    ];
                }

                foreach ($offer->cost->totalCost->totalCostItem as $item) {
                    $data['cost']['items'][$this->getCostItemType((string)$item->caption->text)] = [
                        'text' => (string)$item->content->text,
                        'type' => $this->getCostItemType((string)$item->caption->text)
                    ];
                }

                foreach ($offer->remarks->remark as $remark) {
                    $attributes = $remark->attributes();

                    $data['remark'][(int)$attributes['type']] = [
                        'text' => (string)$remark->content->text,
                        'body' => (string)$remark->content->tooltip->body,
                        'type' => (int)$attributes['type'],
                    ];
                }

                $return[] = $data;
            }
        }

        return $return;
    }

    private function getCostItemType($text)
    {
        $textTypeMapping = [
            'grund' => 'basic',
            'neukunden' => 'new-client',
            'sofort' => 'instant',
            'jubiläum' => 'jubilee',
            'arbeit' => 'unit'
        ];

        foreach ($textTypeMapping as $value => $type) {
            if (mb_stristr($text, $value)) {
                return $type;
            }
        }
    }
}