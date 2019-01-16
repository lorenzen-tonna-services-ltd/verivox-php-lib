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
    private $locationId;

    /**
     * @var int
     */
    private $annualTotal;

    /**
     * @var int
     */
    private $offPeakPercentage;

    /**
     * @var float
     */
    private $heatingPower;

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
        $xmlRoot->setAttribute('signupOnly', "true");
        $xmlRoot->setAttribute('bonusIncluded', "non-compliant");
        $xmlRoot->setAttribute('onlyEcoTariffs', "false");
        $xmlRoot->setAttribute('maxResultsPerPage', 0);
        if (!empty($this->locationId)) {
            $xmlRoot->setAttribute('paolaLocationId', $this->locationId);
        }
        if (!empty($this->duration)) {
            $xmlRoot->setAttribute('maxContractDuration', $this->duration);
        }
        $xmlRoot->setAttribute('maxContractProlongation', 12);
        $xmlRoot->setAttribute('benchmarkTariffId', 0);
        $xmlRoot = $dom->appendChild($xmlRoot);

        $usage = $dom->createElement("usage");

        if (Verivox::REQUEST_TYPE_ELECTRICITY == $this->requestType) {
            $usage->setAttribute('annualTotal', $this->annualTotal);
            $usage->setAttribute('offPeakPercentage', $this->offPeakPercentage);
        } else {
            $usage->setAttribute('annualTotal', $this->annualTotal);
            $usage->setAttribute('heatingPowerInKW', $this->heatingPower);
        }

        $xmlRoot->appendChild($usage);

        $pg = $dom->createElement('priceGuarantee');
        $pg->setAttribute('minDurationInMonths', 12);

        $xmlRoot->appendChild($pg);

        $cp = $dom->createElement('cancellationPeriod');
        $cp->setAttribute('amount', 3);
        $cp->setAttribute('unit', 'month');

        return $dom->saveXML();
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
                        'desktop' => (string)$offer->signup->desktop->attributes()['url'],
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
                    $data['cost']['items'][(string)$item->caption->text] = [
                        'text' => (string)$item->content->text,
                        'type' => $this->getCostItemType((string)$item->content->text)
                    ];
                }

                foreach ($offer->remarks->remark as $remark) {
                    $attributes = $remark->attributes();

                    $data['remark'][] = [
                        'text' => (string)$remark->content->text,
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
        if (mb_stristr($text, 'grund')) {
            return 'basic';
        } else if (mb_stristr($text, 'neukunden')) {
            return 'new-client';
        } else if (mb_stristr($text, 'sofort')) {
            return 'instant';
        } else if (mb_stristr($text, 'jubiläum')) {
            return 'jubilee';
        }
    }
}