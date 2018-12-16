<?php
namespace Verivox\Request;

interface Request
{
    public function getHeaders();
    public function getXML();
    public function getRequestUrl($partnerId, $campaignId);
    public function getResultSet($xml);
}